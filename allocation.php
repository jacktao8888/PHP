<?php

$input = [
    'order_list' => [
        array(
            'order_id' => 1,
            'amount' => 100,
            'overdue_days' => 2,
        ),
        array(
            'order_id' => 2,
            'amount' => 30,
            'overdue_days' => 2,
        ),
        array(
            'order_id' => 3,
            'amount' => 70,
            'overdue_days' => 3,
        ),
//        array(
//            'order_id' => 4,
//            'amount' => 120
//        ),
//        array(
//            'order_id' => 8,
//            'amount' => 30
//        ),
//        array(
//            'order_id' => 9,
//            'amount' => 50
//        ),
//        array(
//            'order_id' => 7,
//            'amount' => 20
//        ),
//        array(
//            'order_id' => 6,
//            'amount' => 200
//        ),
//        array(
//            'order_id' => 10,
//            'amount' => 80
//        ),
//        array(
//            'order_id' => 11,
//            'amount' => 40
//        ),
//        array(
//            'order_id' => 13,
//            'amount' => 90
//        ),
//        array(
//            'order_id' => 12,
//            'amount' => 240
//        ),
    ],
    'operator_list' => [
        array(
            'operator_id' => 1,
            'case_num' => 1,
            'case_total' => 100
        ),
        array(
            'operator_id' => 2,
            'case_num' => 3,
            'case_total' => 500
        ),
        array(
            'operator_id' => 3,
            'case_num' => 2,
            'case_total' => 400
        ),
        array(
            'operator_id' => 4,
            'case_num' => 1,
            'case_total' => 80
        ),
    ],
];

class ControllerAllocation {
//    private function distributeRecursively(&$operators, &$orders, &$outResult, &$circle = 0) {
//        //催单列表为空，结束递归
//        if (empty($orders)) {
//            return $outResult;
//        }
//
//        if ($circle <= 0) {
//            $circle = $this->sortByNumber($operators);
//            $this->distributeRecursively($operators, $orders, $outResult, $circle);
//        } else {
//            for ($i = 0; $i < $circle; $i++) {
//                $operators[$i]['case_num']++;
//                $operators[$i]['case_total'] += $orders[$i]['amount'];
//
//                unset($orders[$i]);
//
//                $outResult[] = array(
//                    'operator_id' => $operators[$i]['operator_id'],
//                    'order_id' => $orders[$i]['order_id']
//                );
//
//                $circle--;
//            }
//        }
//
//        return $outResult;
//    }

    private function sortByNumber(&$list) {
        //按接单数从小到大排序
        $count = count($list);
        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count - $i - 1; $j++) {
                if ($list[$j]['case_num'] > $list[$j + 1]['case_num']) {
                    $tmp = $list[$j];
                    $list[$j] = $list[$j + 1];
                    $list[$j + 1] = $tmp;
                }
            }
        }

//        print_r($list);
//        return;

        //接单数全部一样，按接单金额从小到大排序
        $number = $count;   //催单数一样的操作员个数
        for ($i = 1; $i < $count; $i++) {
            if ($list[$count - $i]['case_num'] - $list[0]['case_num'] >= 1) {
                $number--;
                continue;
            } else {
                $this->sortByTotal($list, $number);
                break;
            }
        }

        return $number;
    }

    private function sortByTotal(&$list, $num) {
        //按接单历史总额从小到大排序
        for ($i = 0; $i < $num; $i++) {
            for ($j = 0; $j < $num - $i - 1; $j++) {
                if ($list[$j]['case_total'] > $list[$j + 1]['case_total']) {
                    $tmp = $list[$j];
                    $list[$j] = $list[$j + 1];
                    $list[$j + 1] = $tmp;
                }
            }
        }

//        echo "----";
//        print_r($list);
//        echo "----";

        $number = $num;     //催单金额一样的操作员个数
        for ($i = 0; $i < $num; $i++) {
            if ($list[$num - $i - 1]['case_total'] > $list[0]['case_total']) {
                $number--;
                continue;
            }
        }

        for ($i = 0; $i < $number; $i++) {
            for ($j = 0; $j < $number - $i - 1; $j++) {
                if ($list[$j]['operator_id'] > $list[$j + 1]['operator_id']) {
                    $tmp = $list[$j];
                    $list[$j] = $list[$j + 1];
                    $list[$j + 1] = $tmp;
                }
            }
        }
    }

    public function operateHistory($in) {
        $in = json_decode($in, true);
        $orderList = $in['order_list'];
        $operatorList = $in['operator_list'];
        $orderNum = count($orderList);
        if (!empty($in) && !empty($in['operator_list']) && !empty($in['order_list']) && !empty($in['order_list'][0]['overdue_days'])) {
//            //催单金额从大到小排序
//            for ($i = 0; $i < $orderNum; $i++) {
//                for ($j = 0; $j < $orderNum - $i - 1; $j++) {
//                    if ($orderList[$j]['amount'] < $orderList[$j + 1]['amount']) {
//                        $tmp = $orderList[$j];
//                        $orderList[$j] = $orderList[$j + 1];
//                        $orderList[$j + 1] = $tmp;
//                    }
//                }
//            }
            //按逾期天数从小到大排序,逾期天数相同，按金额从大到小排序
            for ($i = 0; $i < $orderNum; $i++) {
                for ($j = 0; $j < $orderNum - $i - 1; $j++) {
                    if ($orderList[$j]['overdue_days'] > $orderList[$j + 1]['overdue_days']) {
                        $tmp = $orderList[$j];
                        $orderList[$j] = $orderList[$j + 1];
                        $orderList[$j + 1] = $tmp;
                    } else if ($orderList[$j]['overdue_days'] == $orderList[$j + 1]['overdue_days']) {
                        if ($orderList[$j]['amount'] < $orderList[$j + 1]['amount']) {
                            $tmp = $orderList[$j];
                            $orderList[$j] = $orderList[$j + 1];
                            $orderList[$j + 1] = $tmp;
                        }
                    }
                }
            }
//            print_r($orderList);
        } elseif (!empty($in) && !empty($in['operator_list']) && !empty($in['order_list']) && empty($in['order_list'][0]['overdue_days'])) {
            //催单金额从大到小排序
            for ($i = 0; $i < $orderNum; $i++) {
                for ($j = 0; $j < $orderNum - $i - 1; $j++) {
                    if ($orderList[$j]['amount'] < $orderList[$j + 1]['amount']) {
                        $tmp = $orderList[$j];
                        $orderList[$j] = $orderList[$j + 1];
                        $orderList[$j + 1] = $tmp;
                    }
                }
            }
//            print_r($orderList);
        }

        $out = [];
        while (!empty($orderList)) {
            $circle = $this->sortByNumber($operatorList);
            for ($i = 0; $i < $circle; $i++) {
                if (empty($orderList[$i])) {
                    continue;
                }

                $operatorList[$i]['case_num']++;
                $operatorList[$i]['case_total'] += $orderList[$i]['amount'];

                $out[] = array(
                    'operator_id' => $operatorList[$i]['operator_id'],
                    'order_id' => $orderList[$i]['order_id']
                );

                unset($orderList[$i]);
            }

            $orderList = array_values($orderList);
        }

        print_r($out);
    }
}

$obj = new ControllerAllocation();

if (empty($input['order_list']) || empty($input['operator_list'])) {
    exit('error order_list or operator_list');
} else if (!empty($input['order_list']) && !empty($input['order_list'][0]['overdue_days'])) {
    $obj->operateHistory(json_encode($input));
} else {
    $obj->operateNew(json_encode($input));
}
