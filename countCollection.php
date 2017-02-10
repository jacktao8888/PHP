<?php
class CountClothes extends BaseCtrl {
    public function __construct()
    {
        parent::__construct('CountClothes');
    }

    public function countPlayer() {
        $refId = array (1134,8107,9505,9361);
        $refId = implode(',', $refId);
        $dao = new UserDao();
        $sql = 'select players.id from User users left join Player players on users.id = players.userId where users.lastLoginTime > ?';
        $data = $dao->tbl()->sql($sql, '2016-12-01 00:00:00')->fetchAll();
        //$data = $dao->findAll(array('lastLoginTime > ? left join Player on User.id = Player.userId', '2016-12-01 00:00:00'));
        //debug($data);
        $players = array();
        foreach ($data as $item) {
            $players[] = $item['id']; //$players[]里面的数据有几十万条
        }
        //debug($players);
        $data = array();
        $num10 = 0;
        $num20 = 0;
        $num30 = 0;
        $num40 = 0;
        $num50 = 0;
        $num60 = 0;
        $num70 = 0;
        $num80 = 0;
        $num90 = 0;
        $num100 = 0;
        $counts = count($players);
        $times = floor($counts/1000);
        $dao = new PlayerClothesDao();
        //$players[]里面的数据有几十万条，分批次处理！
        for($i = 0;$i <= $times;$i++) {
            $temp = array();
            for($j = ($i * 1000);$j < ($i * 1000 + 1000);$j++) {
                if (empty($players[$j])) continue;
                $temp[] = $players[$j];
            }
            $temp = implode(',', $temp);
            //使用PlayerClothes表里面的组合索引(playerId,refId),使用count(num)而不是sum(num),是求种类的总和，而不是数量总和！
            $sql = 'select count(num) sum from PlayerClothes where PlayerClothes.playerId in ('.$temp.')'.' and PlayerClothes.refId in ('.$refId.') group by PlayerClothes.playerId';
            $data = $dao->tbl()->sql($sql)->fetchAll();
            foreach ($data as $item) {
                if ($item['sum'] > floor(171 * 0.1)) {
                    $num10++;
                }
                if ($item['sum'] > floor(171 * 0.2)) {
                    $num20++;
                }
                if ($item['sum'] > floor(171 * 0.3)) {
                    $num30++;
                }
                if ($item['sum'] > floor(171 * 0.4)) {
                    $num40++;
                }
                if ($item['sum'] > floor(171 * 0.5)) {
                    $num50++;
                }
                if ($item['sum'] > floor(171 * 0.6)) {
                    $num60++;
                }
                if ($item['sum'] > floor(171 * 0.7)) {
                    $num70++;
                }
                if ($item['sum'] > floor(171 * 0.8)) {
                    $num80++;
                }
                if ($item['sum'] > floor(171 * 0.9)) {
                    $num90++;
                }
                if ($item['sum'] >= 171) {
                    $num100++;
                }
            }
        }
        debug('收集度10%：'.$num10);
        debug('收集度20%：'.$num20);
        debug('收集度30%：'.$num30);
        debug('收集度40%：'.$num40);
        debug('收集度50%：'.$num50);
        debug('收集度60%：'.$num60);
        debug('收集度70%：'.$num70);
        debug('收集度80%：'.$num80);
        debug('收集度90%：'.$num90);
        debug('收集度100%：'.$num100);
    }
}
