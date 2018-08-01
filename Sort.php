<?php
ini_set('memory_limit', '2048M');

$original_arr = array(23,53,32,3,6,56,66,90,1,123,234,632);

//桶排序
function bucketsSort($arr) {
    $num = count($arr);
//var_dump($arr);
    $min = $arr[0];
    $max = $arr[0];
    for ($i = 0; $i < $num; $i++) {
        if ($arr[$i] > $max) {
            $max = $arr[$i];
        }
        if ($arr[$i] < $min) {
            $min = $arr[$i];
        }
    }
//var_dump($min);
//var_dump($max);exit();
    $buckets = array();
    for ($i = $min; $i <= $max; $i++) {
        $buckets[$i] = 0;
    }
//var_dump($buckets);exit();
    foreach ($arr as $value) {
        $buckets[$value]++;
    }
//var_dump($buckets);exit();
    foreach ($buckets as $key => $value) {
        if (empty($value)) continue;
        while ($value--) {
            echo $key . ' ';
        }
    }
}

function divideArr($arr, $divide) {
    $count = array();

    for ($j = 0; $j < 10; $j++) {
        $count[$j] = array();
    }
    foreach ($arr as $value) {
        $index = ($value / $divide) % 10;
        $count[$index][] = $value;
    }
    return $count;
}

//基数排序
function baseSort ($arr) {
    for ($i = 0; $i <=2; $i++) {
        $divide = pow(10, $i);

        $count = divideArr($arr, $divide);
//var_dump($count);exit();
        $arr = array();
        for ($j = 0; $j < 10; $j++) {
            if ($count[$j]) {
                $arr = array_merge($arr, $count[$j]);
//                var_dump($arr);
            }
        }
    }
//var_dump($arr);exit();
    foreach ($arr as $value) {
        echo $value . ' ';
    }
}


bucketsSort($original_arr);
echo "\n";
baseSort($original_arr);
echo "\n";
