<?php
/**
 * Created by PhpStorm.
 * User: fantang
 * Date: 2017/7/18
 * Time: 下午2:34
 */
if (PHP_SAPI != 'cli') die('Pls run from command!');

error_reporting(0);

$_SERVER['HTTP_HOST'] = '';
define('SYS_START_TIME', microtime(true));
define('LD_ENTRY', 'index');

session_start();

$server = new GuildDaemon();
$server->run();

class GuildDaemon {
    private $_server;
    private $_routes = array();
    private $_setting = array();

    const KEY_FD_PLAYER = 'guild_chat_fd_player';

    /**
     * @var LdLogger
     */
    private $_logger;

    /**
     * @var Redis
     */
    private static $_redis;

    public function __construct() {
        swoole_set_process_name('php guild.php manager');
        require __DIR__.'/../constants.inc.php';
        require __DIR__.'/../config.inc.php';
        require __DIR__.'/../header.php';

        $setting = parse_ini_file(__DIR__.'/../setting.ini', true);
        $this->_setting = $setting['guild server'];
        self::$_redis = new Redis();
    }

    public function run() {
        $this->_server = new swoole_server($this->_setting['bind'], $this->_setting['port']);
        $config = array(
            'worker_num' => $this->_setting['worker_num'],
            'log_file' => $this->_setting['log_file'],
            'backlog' => $this->_setting['backlog'],
            'user' => $this->_setting['user'],
            'group' => $this->_setting['group'],
            'daemonize' => $this->_setting['daemonize'],
            'dispatch_mode' => 2,
            'open_tcp_nodelay' => true,
            'open_length_check' => true,
            'package_length_type' => 'N',
            'package_length_offset' => 1,//第N个字节是包长度的值
            'package_body_offset' => 5,//第N个字节开始算长度
            'package_max_length' => 2000000,//协议最大长度
            'heartbeat_idle_time' => 300,
            'heartbeat_check_interval' => 60,
            'task_max_request' => 100,
        );
        $this->_server->set($config);
        $this->_server->on('Receive', array($this, 'receive'));
        $this->_server->on('WorkerStart', array($this, 'workerStart'));
        $this->_server->on('Start', array($this, 'start'));
        $this->_server->on('Close', array($this, 'close'));
        $this->_server->start();
    }

    public function receive(swoole_server $server, $fd, $fromId, $data) {
        $cmd = unpack('ncmd', substr($data, 5,2));
        if ($cmd['cmd'] == API_SOCKET_HEARTBEAT) {//心跳包直接返回
            $game = new Game();
            $notifyData = $game->getResponse(array());
            $data = $this->_getData($notifyData, API_SOCKET_HEARTBEAT);
            $server->send($fd, $data);
            return;
        }

        $body = substr($data, 7);
        $body = json_decode($body, true);
        if (empty($body['playerId'])) return;

        $route = $this->_routes[$cmd['cmd']];
        $body['fd'] = $fd;
        $body['fromId'] = $fromId;
        $body['server'] = $server;
        $body['cmd'] = $cmd['cmd'];
        $app = new LdApplication($route['ctrl'], $route['act']);
        $app->run($body);
    }

    public function workerStart(swoole_server $server, $workerId) {
        $this->_routes = require __DIR__.'/../bootstrap/routes.php';
        $this->_logger = LdKernel::getInstance()->getLoggerHandler();

        if ($workerId >= $server->setting['worker_num']) {
            swoole_set_process_name('php guild.php task worker');
        } else {
            swoole_set_process_name('php event worker');
        }
    }

    public function start(swoole_server $server) {
        swoole_set_process_name('php guild.php master');
        //记录进程文件
        $dir = $this->_setting['pidfile'];
        if (!is_dir($dir)) mkdir($dir);
        $masterPidFile = $dir.'guild.master.pid';
        file_put_contents($masterPidFile, $server->master_pid);

        $managerPidFile = $dir.'guild.manager.pid';
        file_put_contents($managerPidFile, $server->manager_pid);
        //echo "Hello client";
    }

    public function close(swoole_server $server, $fd, $fromId) {
        $connectionInfo = $server->connection_info($fd);
        $ipSeg = explodeSafe($connectionInfo['remote_ip'], '.');
        if (in_array($ipSeg[0], [10, 127, 192])) return;//内网IP段

        $redis = new Redis();
        $redis->connect(MQ_HOST, MQ_PORT);
        $redis->select(10);

        $playerId = $redis->hGet(self::KEY_FD_PLAYER, $fd);

        if (!empty($playerId)) {
            $route = $this->_routes[5004];

            $body['playerId'] = $playerId;
            $body['fd'] = $fd;
            $body['fromId'] = $fromId;
            $body['server'] = $server;
            $body['cmd'] = 5004;

            $app = new LdApplication($route['ctrl'], $route['act']);
            $app->run($body);
        }

        $redis->close();
    }

    /**
     * 得到完整的数据包
     *
     * @param string $data 数据体
     * @param int $cmd 命令码
     * @return string
     */
    public function _getData($data, $cmd) {
        $len = strlen($data);
        return pack('C', 134).pack('N', $len+2).pack('n', $cmd).$data;
    }
}
