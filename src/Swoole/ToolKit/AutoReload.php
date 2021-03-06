<?php
namespace Swoole\ToolKit;

require __DIR__.'/NotFound.php';

class AutoReload
{
    /**
     * @var resource
     */
    protected $inotify;

    protected $pid = null;

    protected $reloadFileTypes = array('.php' => true);

    protected $watchFiles = array();

    public $afterNSeconds = 10;

    /**
     * 默认是不重启的
     */
    protected $reloading = false;
    // 默认的监听事件类型
    protected $events = IN_MODIFY | IN_DELETE | IN_CREATE | IN_MOVE;

    /**
     * 根目录
     * @var array
     */
    protected $rootDirs = [];

    function putLog($log)
    {
        $_log = "[".date('Y-m-d H:i:s')."]\t".$log."\n";
        echo $_log;
    }

    /**
     * @param $serverPid
     * @throws NotFound
     */
    function __construct($serverPid=null)
    {

        if(is_string($serverPid)) {
            $content = file_get_contents($serverPid);
            $this->pid = intval($content);
        }else if(is_int($serverPid)) {
            $this->pid = $serverPid;
        }else{
            $this->pid = intval(shell_exec("netstat -ntlp | grep 9501 | awk '{print $7}' | awk -F '/' '{print $1}'"));
            if(!is_int($this->pid)) {
                throw new NotFound("$serverPid=".$serverPid." is error,must be set the master progress pid_log same as swoole setted or pid");
            }
        }
        
        if (posix_kill($serverPid, 0) === false)
        {
            throw new NotFound("Process#$serverPid not found.");
        }

        $this->inotify = inotify_init();

        swoole_event_add($this->inotify, function ($ifd) {
            $events = inotify_read($this->inotify);
            if (!$events)
            {
                return;
            }

            if (!$this->reloading)
            {
                foreach($events as $ev)
                {
                    if ($ev['mask'] == IN_IGNORED)
                    {
                        continue;
                    }
                    else if ($ev['mask'] == IN_CREATE or $ev['mask'] == IN_DELETE or $ev['mask'] == IN_MODIFY or $ev['mask'] == IN_MOVED_TO or $ev['mask'] == IN_MOVED_FROM)
                    {
                        $fileType = '.'.pathinfo($ev['name'], PATHINFO_EXTENSION);
                        //非重启类型
                        if (!isset($this->reloadFileTypes[$fileType]))
                        {
                            continue;
                        }
                    }
                    //正在reload，不再接受任何事件，冻结10秒
                    if (!$this->reloading)
                    {
                        $this->putLog("after ".$this->afterNSeconds." seconds reload the server");
                        //有事件发生了，进行重启
                        swoole_timer_after($this->afterNSeconds * 1000, array($this, 'reload'));
                        $this->reloading = true;
                    }
                }
            }
        });
    }

    function reload()
    {
        $this->putLog("reloading");
        //向主进程发送信号
        posix_kill($this->pid, SIGUSR1);
        //清理所有监听
        $this->clearWatch();
        //重新监听
        foreach($this->rootDirs as $root)
        {
            $this->watch($root);
        }
        //继续进行reload
        $this->reloading = false;
    }

    /**
     * 添加文件类型
     * @param $type
     */
    function addFileType($type)
    {
        $type = trim($type, '.');
        $this->reloadFileTypes['.' . $type] = true;
    }

    /**
     * 添加事件
     * @param $inotifyEvent
     */
    function addEvent($inotifyEvent)
    {
        $this->events |= $inotifyEvent;
    }

    /**
     * 清理所有inotify监听
     */
    function clearWatch()
    {
        foreach($this->watchFiles as $wd)
        {
            inotify_rm_watch($this->inotify, $wd);
        }
        $this->watchFiles = array();
    }

    /**
     * @param $dir
     * @param bool $root
     * @return bool
     * @throws NotFound
     */
    function watch($dir, $root = true)
    {
        //目录不存在
        if (!is_dir($dir))
        {
            throw new NotFound("[$dir] is not a directory.");
        }
        //避免重复监听
        if (isset($this->watchFiles[$dir]))
        {
            return false;
        }
        //根目录
        if ($root)
        {
            $this->rootDirs[] = $dir;
        }

        $wd = inotify_add_watch($this->inotify, $dir, $this->events);
        $this->watchFiles[$dir] = $wd;

        $files = scandir($dir);
        foreach ($files as $f)
        {
            if ($f == '.' or $f == '..')
            {
                continue;
            }
            $path = $dir . '/' . $f;
            //递归目录
            if (is_dir($path))
            {
                $this->watch($path, false);
            }
            //检测文件类型
            $fileType = '.'.pathinfo($f, PATHINFO_EXTENSION);

            if (isset($this->reloadFileTypes[$fileType]))
            {
                $wd = inotify_add_watch($this->inotify, $path, $this->events);
                $this->watchFiles[$path] = $wd;
            }
        }
        return true;
    }

    function run()
    {
        swoole_event_wait();
    }
}
