<?php

define("HOST", '127.0.0.1');
define("PORT", '5432');
define("USER", 'root');
define("PASSWORD", '123456');

class Model_Pgsql {
    private static $instance = null;

    private $conn = null;

    private $table_name = '';

    private function __construct($dbname = null, $tablename = null) {
//        $this->conn = pg_connect('host=' . HOST . ' port=' . PORT . ' dbname=' . $dbname . ' user=' . USER . ' password=' . PASSWORD)
//            or die('Connect Failed: ' . pg_last_error());
        $dsn = 'pgsql:dbname=test host=localhost';
        $this->conn = new PDO($dsn, USER, PASSWORD);
        $this->table_name = $tablename;
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self('test', 'users');
        }

        return self::$instance;
    }

    public function fetchRow($sql) {
        $result = pg_query($this->conn, $sql);
        var_dump($result);
        echo $result;
        if (!$result) {
            echo "query did not execute\n";
        }

        if (pg_num_rows($result) == 0) {
            echo "0 records\n";
        } else {
            print_r(pg_fetch_all($result));
        }

    }

    public function query($user_name) {
        $sql = 'SELECT * FROM ' . $this->table_name . ' WHERE user_name=:uname';

//        $sql = 'SELECT * FROM ' . $this->table_name . ' WHERE user_name=' . $this->conn->quote($user_name);
//        var_dump($this->conn->query($sql));
//        var_dump($this->conn->errorInfo());
//        var_dump($this->conn->query($sql)->fetchAll(PDO::FETCH_NAMED));
//        exit();

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':uname', $user_name, PDO::PARAM_STR);
        $stmt->execute();
        $stmt->bindColumn(1, $telephone, PDO::PARAM_STR);
        $stmt->bindColumn(2, $age, PDO::PARAM_STR);
//        var_dump($telephone);
//        var_dump($age);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        print_r($result);
        var_dump($telephone);
        var_dump($age);

        return ;
    }

    public function select($where = array(), $attrs = array()) {
        $selectFileds = !empty($attrs['select']) ? $attrs['select'] : '*';

        $sql = 'SELECT ' . $selectFileds . ' FROM ' . $this->table_name . $this->where($where, $attrs);

        $ret = $this->conn->query($sql);
        if ($ret === false) {
            exit("DB Error: [$sql] " . $this->conn->errorInfo()[2] . "\n");
        }

        return $ret->fetchAll(PDO::FETCH_NAMED);
    }

    public function operate($sql, $mode = false) {
        try {
            $res = $this->conn->exec($sql);

            if ($res === false) {
                echo 'DB mod error: ' . $sql;
                return false;
            }
        } catch (PDOException $e) {
            echo 'DB mod error:' . $e->getMessage() . ' SQL: ' . $sql;

            return false;
        }

        if ($mode) return $res;

        return true;
    }

    public function insert($data) {
//        $res = pg_insert($this->conn, $this->table_name, $assoc_array);
//        if ($res) {
//            echo "Data is inserted successfully\n";
//        } else {
//            echo "insert Failed\n";
//        }

        $sql = 'INSERT INTO ' . $this->table_name . $this->insertDataFormat($data);
        $ret = $this->operate($sql);
        if ($ret === false) {
            echo 'DB error: ' . $sql . $this->getErrorInfo();
            return false;
        }

        return $this->conn->lastInsertId();
    }

    public function insertMultiple($row, $rowData) {
        $sql = 'INSERT ' . $this->table_name . $this->insertDataFormat($row, $rowData);
        $ret = $this->operate($sql, true);
        if ($ret === false) {
            echo 'DB error: ' . $sql . $this->getErrorInfo();
            return false;
        }

        return $ret;
    }

    private function getErrorInfo() {
        if ($this->conn) {
            $err = $this->conn->errorInfo();
            return $err[2];
        }

        return 'DB connect not initialed';
    }

    /**
     * @example
     * 单行数据
     * $row = array(
     *      'key1' => 'val1',
     *      'key2' => 'val2'
     * );
     * output: (key1,key2) VALUES ('val1','val2')
     *
     * 多行数据
     * $row = array('key1','key2');
     * $rowData = array(
     *      array('val1','val2'),
     *      array('val3','val4')
     * );
     * output: (key1,key2) VALUES ('val1','val2'),('val3','val4')
     *
     * @param      $row
     * @param null $rowData
     *
     * @return string
     */
    private function insertDataFormat($row, $rowData = null) {
        if ($rowData) {
            $keys = $row;
        } else {
            $keys = array_keys($row);
            $rowData = array(array_values($row));
        }

        $keySqls = ' (' . implode(',', $keys) . ')';

        $valSqls = '';
        foreach ($rowData as $data) {
            $data = array_map(array($this, '_escape'), $data);
            $valSqls .= '(' . implode(',', $data) . '),';
        }

        $valSqls = trim($valSqls, ',');

        return "$keySqls VALUES $valSqls";
    }

    private function where($where, $attrs = array()) {
        $sql = '';
        if (!empty($where)) {
            $whereSql = $this->_where($where);
            if ($whereSql) {
                $sql .= ' WHERE ' . $whereSql;
            }
        }

        if ($attrs) {
            if (isset($attrs['group_by'])) {
                $sql .= ' GROUP BY ' . $attrs['group_by'];
            }

            if (isset($attrs['having'])) {
                $sql .= ' HAVING ' . $attrs['having'];
            }

            if (isset($attrs['order_by'])) {
                $sql .= ' ORDER BY ' . $attrs['order_by'];
            }

            if (isset($attrs['limit'])) {
                $sql .= ' LIMIT ' . $attrs['limit'];
            }

            if (isset($attrs['offset'])) {
                $sql .= ' OFFSET ' . $attrs['offset'];
            }
        }

        return $sql;
    }

    private function _where($where) {
        if (empty($where) || !is_array($where)) return '';

        $logic = '';

        if (isset($where['__logic'])) {
            $logic = $where['__logic'];
            unset($where['__logic']);
        }

        $conds = array();
        foreach ($where as $key => $val) {
            $conds[] = $this->_cond($key, $val);
        }
        if (!$logic) {
            $logic = 'AND';
        }
        
        $sql = implode(" $logic ", array_filter($conds));
        return $sql;
    }

    private function _cond($name, $value) {
        if (!is_array($value)) {
            $value = $this->_escape($value);
            return "$name=$value";
        }

        $logic = 'OR';
        if (isset($value['__logic'])) {
            $logic = $value['__logic'];
            unset($value['__logic']);
        }

        $conds = array();
        if ($this->_is_num_array($value)) {
            foreach ($value as $k => $v) {
                $conds[] = $this->_cond($this, $v);
            }
        } else {
            foreach ($value as $k => $v) {
                $v = $this->_escape($v);
                $conds[] = "$name $k $v";
            }
        }

        return '(' . implode(" $logic ", $conds) . ')';
    }

    private function _is_num_array($arr) {
        if (!is_array($arr)) return false;

        foreach ($arr as $key => $value) {
            if (!is_numeric($key)) return false;
        }

        return true;
    }

    private function _escape($str) {
        if ($str === NULL) {
            return 'NULL';
        }

        if ($str[0] == ':') {
            return substr($str, 1);
        }

//        if (stripos($str, '&/!') === 0) {
//            return $this->conn->quote(substr($str, 3));
//        }

        if (stripos($str, '&/') === 0) {
            return substr($str, 2);
        }

        return $this->conn->quote($str);
    }

    public function test() {
        $quote_str = $this->conn->quote('adfas \'fsd');
        echo $quote_str;
    }

    public function __call($name, $arguments) {
        if (stripos($name, 'getById')) {
            $id = $arguments[0];
            $where = array(
                'id' => $id
            );
            return $this->select($where);
        }
    }

    public function __destruct() {
        @pg_close($this->conn);
    }
}

$insert_data = array(
    'id' => 4,
    'user_name' => 'jack',
    'age' => 28,
    'telephone' => '3433433333',
    'ext' => 1
);
Model_Pgsql::getInstance()->insert($insert_data);

//$sql = 'SELECT * FROM users WHERE id = 1;';
//Model_Pgsql::getInstance()->fetchRow($sql);

//Model_Pgsql::getInstance()->query('admin');

//Model_Pgsql::getInstance()->test();

$where = array(
    'id' => array('in' => '&/(1,2)'),
    'user_name' => array('like' => 'admin\'s%'),
//    'age' => array('>' => 8)
);

$attr = array(
    'select' => 'telephone,user_name',
    'order_by' => 'age desc',
);

//$res = Model_Pgsql::getInstance()->select($where, $attr);
//var_dump($res);