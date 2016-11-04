<?php
    //公用的方法
    
function show($status,$message,$data=array()){
    $result = array(
            'status' => $status,
            'message' => $message,
            'data' => $data
        );
        
    exit(json_encode($result));
}

function getMd5Password($password){
    return md5($password.'MD5_PRE');
}

function getMenuType($type){
    return $type ==1 ? "后台菜单" : '前端导航';
}

function status($status){
    if($status == 0){
        $str = '关闭';
    }elseif($status == 1){
        $str = '正常';
    }elseif($status == -1){
        $str = '删除';
    }
    return $str;
}

function getAdminMenuUrl($nav){
    $url = '/admin.php?c='.$nav['c'].'&a='.$nav['f'];
    if($nav['f'] == 'index'){
        $url = '/admin.php?c='.$nav['c'];
    }
    return $url;
}

function setActive($navc){
    $c = strtolower(CONTROLLER_NAME);
    if(strtolower($navc) == $c){
        return 'class="active"';
    }else{
        return '';
    }
}

function showKind($status,$data){
    header('Content-type:application/json;charset=utf-8');
    if($status == 0){
        exit(json_encode(array('error'=>0,'url'=>$data)));
    }    
    exit(json_encode(array('error'=>1,'message'=>$data)));
}

function getLoginUsername(){
    return $_SESSION['adminUser']['username'];
}

function getCatName($navs,$id){
    foreach($navs as $nav){
        $navlist[$nav['menu_id']] = $nav['name'];
    }
    return $navlist[$id] ? $navlist[$id] : '';
}

function getCopyFrom($id){
    $copyFrom = C("COPY_FROM");
    return $copyFrom[$id] ? $copyFrom[$id] : '';
}

function isThumb($thumb){
    if($thumb){
        return '<span style="color:red">有</span>';
    }
    return '无';
}
