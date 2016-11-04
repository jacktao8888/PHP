<?php
namespace Admin\Controller;
use Think\Controller;

class BasicController extends Controller {
    public function index(){
        if(!session('adminUser')){
            $this->redirect('/admin.php?c=Login');
        }
        
        $basic = D('Basic')->select();
        $this->assign('basic',$basic);
    	$this->display();
    }
    public function add(){
        if($_POST){
            if(!$_POST['title']){
                return show(0,'站点标题不能为空');            
            }
            if(!$_POST['keywords']){
                return show(0,'站点关键词不能为空');
            }
            if(!$_POST['description']){
                return show(0,'站点描述不能为空');
            }
            
            try{
                D('Basic')->save($_POST);
                return show(1,'配置成功');
            }catch(Exception $e){
                return show(0,$e->getMessage());
            }
        }else{
            return show(0,'没有提交的数据');
        }
    }
}