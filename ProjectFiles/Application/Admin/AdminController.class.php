<?php
namespace Admin\Controller;
use Think\Controller;

class AdminController extends Controller {
    public function index(){
        if(!session('adminUser')){
            $this->redirect('/admin.php?c=Login');
        }
        $user = D('Admin')->select();
        
        $this->assign('users',$user);
    	$this->display();
    }
    public function add(){
        if($_POST){
            if(!$_POST['username']){
                return show(0,'用户名不能为空');   
            }
            if(!$_POST['password']){
                return show(0,'密码不能为空');
            }
            if(!$_POST['realname']){
                return show(0,'真实姓名不能为空');
            }
            
            $res = D('Admin')->insert($_POST);
            if(!$res){
                return show(0,'新增失败');
            }else{
                return show(1,'新增成功');
            }
        }else{
            $this->display();
        }
    }
    public function setStatus(){
        try {
            $id = intval($_POST['id']);
            $status = $_POST['status'];
            $data['status'] = $status;
            $res = D('Admin')->updateById($id,$data);
            if($res){
                return show(1,'操作成功');
            }else{
                return show(0,'操作失败');
            }
        } catch (Exception $e) {
            return show(0,$e->getMessage());
        }
    }
}
