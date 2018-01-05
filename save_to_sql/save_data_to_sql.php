#!/usr/bin/php
<?php

set_time_limit(0);

$options = getopt('d:s::');
$dirName = __DIR__ . '/' . $options['d'];
$beginIndex = $options['s'];
// echo $beginIndex;

class ExportData2Sql {
    static $mysql;
    static $searchLog;
    static $unmatchedLog;
    static $insertLog;
    static $updateLog;
    static $processLog;
    private $dir = '';
    private $beginIndex = 1;

    function __construct($dirName, $beginIndex = 1) {
        $hostname = '127.0.0.1';
        $dbName = 'cart';
        $dbUsername = 'root';
        $dbPassword = '123456';
        
        self::$mysql = mysqli_connect($hostname, $dbUsername, $dbPassword);
        mysqli_select_db(self::$mysql, $dbName);
        mysqli_autocommit(self::$mysql,TRUE);

        $this->dir = $dirName;
        if (!is_dir($dirName))
        {
            // echo $this->dir;
            exit("Please input dirctory\n");
        }

        $this->beginIndex = $beginIndex;
        // var_dump($this->beginIndex);
        // var_dump($this->dir);
    }

    public function index() {
        //files 列表为['1688_data_1.json','1688_data_2.json']
        $files = scandir($this->dir);
        
        $logDir = getcwd().'/log/';
        if (!is_dir($logDir)) {
            mkdir($logDir);
        }

        self::$searchLog = fopen($logDir.'/search_error.log', 'a+');//not relative product in opencart
        self::$insertLog = fopen($logDir.'/insert_error.log', 'a+');//error: insert into oc_product_matched
        self::$updateLog = fopen($logDir.'/update_error.log', 'a+');//error: update oc_product表的matching_status出错
        self::$unmatchedLog = fopen($logDir.'/unmatched_error.log', 'a+');//not matched products
        self::$processLog = fopen($logDir.'/process.log', 'a+');
        //每个文件固定1000条json记录，因为不断有新文件生成（按序号递增），扫描目录中的文件，按从大到小排序，序号最大的文件先不处理
        rsort($files);
        unset($files[0]);

        sort($files);//从小到大处理
        //files 列表为['1688_data_1.json','1688_data_2.json']
        foreach ($files as $file) {
            $filePath = $this->dir . '/' . $file;
            if (empty(is_file($filePath))) continue;

            //脚本传入参数-s,文件处理的起始序号，只处理不小于这个序号的文件
            if (substr(basename($file,'.json'),10) < $this->beginIndex) {
                continue;
            }

            $data = file_get_contents($filePath);
            $data = json_decode($data, true);

            // echo $file."\n";
            foreach ($data as $values) {
                foreach ($values as $cfId => $matchedProducts) {
                    # code...
                
    //                print($cfId);
                    //未匹配到商品
                    if (empty($matchedProducts['status'])) {
                        fwrite(self::$unmatchedLog, 'file:' .$file. ',cf_id:'.$cfId."\n");
                        continue;
                    }

                    //插入数据去重
                    $processedData = file_get_contents($logDir.'/process.log');
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

                    //不要重复插入matched_products
                    $sql = 'select * from oc_product_matched where product_id = ' . $productId;
                    $query = mysqli_query(self::$mysql, $sql);
                    if (!empty(mysqli_fetch_assoc($query)['id'])) {
                        continue;
                    }

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
                            .",". addslashes($matchedInfo['matching_degree'])
                            .",". addslashes($matchedInfo['product_price'])
                            .",'". addslashes($matchedInfo['product_link'])
                            ."','". addslashes($matchedInfo['product_title'])
                            ."','". addslashes($matchedInfo['product_image'])
                            ."','". addslashes($matchedInfo['manufacturer_name'])
                            ."','". addslashes($matchedInfo['manufacturer_link'])
                            ."','". addslashes($matchedInfo['manufacture_location'])//manufacturer_location拼写错误
                            ."',". addslashes($matchedInfo['purchase_number'])
                            .",". addslashes($matchedInfo['confidence_degree_link'])
                            .",". $now
                            .",". $now
                            ."),";
                    }
                    $insertInfo = rtrim($insertInfo, ',');

                    $sql .= $insertInfo;
    //                var_dump($sql);

                    self::saveAndUpdate($sql, $file, $cfId, intval($matchedProducts['status']), $matchedProducts);
                    echo "file:". $file . ",cf_id:" . $cfId."\n";
                }
            }
        }

        fclose(self::$searchLog);
        fclose(self::$insertLog);
        fclose(self::$updateLog);
        fclose(self::$processLog);
    }

    static function saveAndUpdate($query, $file, $id = 0, $status = 0, $matching = []) {
        // mysqli_begin_transaction(self::$mysql);
        $insertResult = mysqli_query(self::$mysql, $query);

        //oc_product表未MyISAM表，不支持事务
        $updateResult = true;
        if ($insertResult) {
            $updateResult = mysqli_query(self::$mysql, 'update oc_product set matching_status = '. $status .' where cf_id = '.$id);
            
            if (empty($updateResult)) {
                fwrite(self::$updateLog, 'file:' . $file . ',cf_id:' . $id . "\n");
            }
            fwrite(self::$processLog, 'file:' .$file. ',cf_id:'.$id."\n");
        } else {
            echo $query;
            fwrite(self::$insertLog, "error:" . mysqli_error(self::$mysql).',file:' .$file. ',cf_id:'.$id.",matching_data:" . json_encode($matching) . "\n");       
        }
    }

    function __destruct() 
    {
        mysqli_close(self::$mysql);
        echo "successed\n";
    }
}

$task = new ExportData2Sql($dirName, $beginIndex);
$task->index();
