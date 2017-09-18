<?php
/**
 * Created by PhpStorm.
 * User: fantang
 * Date: 2017/7/19
 * Time: 下午3:23
 */
class Guild extends Game {
    const KEY_GUILD_CHAT_FD = 'chat_fd_guild_';

    private $server = null;
    private $needLog = true;
    private $redis = null;

    private $cmd = null;
    private $fd = null;
    private $fromId = null;

    public function __construct() {
        parent::__construct();
        $this->redis = new Redis();

        $this->redis->connect(MQ_HOST, MQ_PORT);
        $this->redis->select(6);

    }

    public function beforeAction($action, $params) {
        $this->playerId = $params['playerId'];
        $this->player = PlayerModel::get($this->playerId);
        if (empty($this->player)) {
            return $this->errResponse(-2, Lang::get('jjc.player_not_exist'));
        }

        $this->server = $params['server'];
        $this->cmd = $params['cmd'];
        $this->fd = $params['fd'];
        $this->fromId = $params['fromId'];
        unset($params['fd'], $params['fromId'], $params['server'], $params['cmd']);

        $this->params = $params;
        if (!$this->isSignValid($this->params)) {
            //$this->dao->disconnect();
            $this->redis->close();
            return $this->errResponse(-2, "Sign mismatch");
        }

        $this->redis->connect(MQ_HOST, MQ_PORT);
        $this->redis->select(6);
        $GLOBALS['player'] = $this->player;
    }

    public function enterChat() {
        $channelId = $this->params['channel'];//频道ID：1，世界；2，公会

        if (2 == $channelId) {
            //初始化
            if (!$this->redis->exists(self::KEY_GUILD_CHAT_FD . $this->player['guildId'])) {
                $this->redis->hSet(self::KEY_GUILD_CHAT_FD . $this->player['guildId'], 'init', 1);
            }
            $this->redis->hSet(self::KEY_GUILD_CHAT_FD . $this->player['guildId'], $this->playerId, json_encode(array('fd' => $this->fd, 'fromId' => $this->fromId)));

            $result = array();
            $this->send($this->playerId, 5001, array('historyMessages' => $result, 'channel' => $channelId));
        }
        $this->redis->close();
    }

    public function sendMessage() {
        $chatMembers = $this->redis->hKeys(self::KEY_GUILD_CHAT_FD . $this->player['guildId']);
        foreach ($chatMembers as $member) {
            if ($this->playerId == $member) continue;
            $data = array(
                'channel' => $this->params['channel'],
                'message' => $this->params['message'],
                'playerId' => $this->playerId,
                'name' => $this->player['name'],
                'avatar' => $this->player['avatar'],
                'sendTime' => time(),
                'vip' => $this->player['vip'],
            );
            $this->send($member, 5003, $data);
        }

        $this->send($this->playerId, 5002, array('channel' => $this->params['channel']));
        $this->redis->close();
    }

    public function leaveChat() {

        if (2 == $this->params['channel']) {
            $this->redis->hDel(self::KEY_GUILD_CHAT_FD . $this->player['guildId'], $this->playerId);
        }

        $this->send($this->playerId, 5004, array('channel' => $this->params['channel']));

        $this->redis->close();
    }

    private function send($playerId = null, $cmd = null, $data = array()) {
        is_null($playerId) && $playerId = $this->playerId;
        is_null($cmd) && $cmd = $this->cmd;

        $body = $this->response($data, false);
        $len = strlen($body);
        $sendData = pack('C', 134).pack('N', $len+2).pack('n', $cmd).$body;
        $fd = $this->getFd($playerId);

        $rtn = $this->server->send($fd['fd'], $sendData, $fd['fromId']);
        if ($this->needLog) {
            $this->log->debug(sprintf('playerId is %s,send to playerId is %s, cmd is %s, request is %s, response is %s, sending result is %s, fd is %s, connection info is %s',
                $this->playerId, $playerId, $cmd, json_encode($this->params), json_encode($data), intval($rtn), $fd['fd'], json_encode($this->server->connection_info[$fd['fd']])));
        }

        return true;
    }

    private function getFd($playerId) {
        //$player = PlayerModel::get($playerId);
        $this->redis->connect(MQ_HOST, MQ_PORT);
        $this->redis->select(6);

        return json_decode($this->redis->hGet(self::KEY_GUILD_CHAT_FD.$this->player['guildId'], $playerId), true);
    }
}
