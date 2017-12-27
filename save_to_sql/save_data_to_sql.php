#!/usr/bin/php
<?php

set_time_limit(0);

$options = getopt('d:');
$dirName = __DIR__ . '/' . $options['d'];

class ExportData2Sql {
    static $mysql;
    static $searchLog;
    static $unmatchedLog;
    static $insertLog;
    static $processLog;
    private $dir = '';

    function __construct($dirName) {
        $hostname = '127.0.0.1';
        $dbName = 'cart';
        $dbUsername = 'root';
        $dbPassword = '123456';
        
        self::$mysql = mysqli_connect($hostname, $dbUsername, $dbPassword);
        mysqli_select_db(self::$mysql, $dbName);
        mysqli_autocommit(self::$mysql,FALSE);

        $this->dir = $dirName;
        if (!is_dir($dirName)) 
        {
            // echo $this->dir;
            exit("Please input dirctory\n");
        }
    }

    public function index() {
        $files = scandir($this->dir);
        self::$searchLog = fopen(getcwd().'/search_error.log', 'a+');//not relative product in opencart
        self::$insertLog = fopen(getcwd().'/insert_error.log', 'a+');//error: insert into oc_product_matched
        self::$unmatchedLog = fopen(getcwd().'/unmatched_error.log', 'a+');//not matched products
        self::$processLog = fopen(getcwd().'/process.log', 'a+');
        //每个文件固定1000条json记录，因为不断有新文件生成（按序号递增），扫描目录中的文件，按从大到小排序，序号最大的文件先不处理
        rsort($files);
        unset($files[0]);

        sort($files);//从小到大处理
        foreach ($files as $file) {
            $filePath = $this->dir . '/' . $file;
            if (empty(is_file($filePath))) continue;

            $data = file_get_contents($filePath);
            $data = json_decode($data, true);

            foreach ($data as $cfId => $matchedProducts) {
//                print($cfId);
                //未匹配到商品
                if (empty($matchedProducts['status'])) {
                    fwrite(self::$unmatchedLog, 'file:' .$file. ',cf_id:'.$cfId."\n");
                    continue;
                }

                //插入数据去重
                $processedData = file_get_contents(getcwd().'/process.log');
                preg_match_all('/cf_id:(\d+)/', $processedData, $processedCfId);
//                print_r($processedCfId[1]);
                if (in_array($cfId, $processedCfId[1])) {
//                    echo "repeat";
                    continue;
                }

                $sql = 'select product_id from oc_product where cf_id = ' . $cfId;
                //查询cf_id与opencart中product_id对应关系
                $query = mysqli_query(self::$mysql, $sql);
                $productId = mysqli_fetch_assoc($query)['product_id'];

                //未对应到opencart商品
                if (empty($productId)) {
                    //记录未搜索到的product_id
                    fwrite(self::$searchLog, 'file:' .$file. ',cf_id:'.$cfId."\n");
                    continue;
                };

                //,link,title,image,manufaturer,manufaturer_link,manufacturer_location,purchase_number,confidence_degree
                $sql = "insert into oc_product_matched (product_id,matching_degree,price,link,title,image,manufacturer,manufacturer_link,manufacturer_location,purchase_number,confidence_degree,create_time,update_time) values ";
                $insertInfo = '';
                $now = time();
                foreach ($matchedProducts as $key => $matchedInfo) {
                    if ($key == 'status') continue;
                    //所有的字段以字符串形式存储。不可靠，sql字段顺序要保证一致！
//                    $insertInfo = implode("','",$matchedInfo);
//                    $insertInfo = $productId ."','". $insertInfo ."','". $now ."','". $now;
//                    $insertInfo = "('" . $insertInfo . "'),";
                    $insertInfo .= "(" . intval($productId)
                        .",". intval($matchedInfo['matching_degree'])
                        .",". $matchedInfo['product_price']
                        .",'". $matchedInfo['product_link']
                        ."','". $matchedInfo['product_title']
                        ."','". $matchedInfo['product_image']
                        ."','". $matchedInfo['manufacturer']
                        ."','". $matchedInfo['manufacturer_link']
                        ."','". $matchedInfo['manufacturer_location']
                        ."',". $matchedInfo['purchase_number']
                        .",". $matchedInfo['confidence_degree']
                        .",". $now
                        .",". $now
                        ."),";
                }
                $insertInfo = rtrim($insertInfo, ',');

                $sql .= $insertInfo;
//                var_dump($sql);

                self::saveAndUpdate($sql, $file, $cfId, intval($matchedProducts['status']));
            }
        }

        fclose(self::$searchLog);
        fclose(self::$insertLog);
        fclose(self::$processLog);
    }

    static function saveAndUpdate($query, $file, $id = 0, $status = 0) {
        mysqli_begin_transaction(self::$mysql);
        $insertResult = mysqli_query(self::$mysql, $query);

        //oc_product表未MyISAM表，不支持事务
        $updateResult = true;
        if (!empty($status)) {
            $updateResult = mysqli_query(self::$mysql, 'update oc_product set matching_status = '. $status .' where cf_id = '.$id);
        }

        //出错回滚事务
        if (empty($insertResult) || empty($updateResult)) {
            var_dump(mysqli_rollback(self::$mysql));
            fwrite(self::$insertLog, mysqli_error(self::$mysql).'file:' .$file. ',cf_id:'.$id."\n");
        } else {
            mysqli_commit(self::$mysql);
            fwrite(self::$processLog, 'file:' .$file. ',cf_id:'.$id."\n");
        }
    }

    function __destruct() 
    {
        mysqli_close(self::$mysql);
        echo "successed\n";
    }
}

$task = new ExportData2Sql($dirName);
$task->index();
