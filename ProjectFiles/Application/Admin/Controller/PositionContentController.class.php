<?php
namespace Admin\Controller;
use Think\Controller;

class PositionContentController extends Controller {
    public function index(){
        $data = array();
        
        if(!session('adminUser')){
            $this->redirect('/admin.php?c=Login');
        }
        
        if($_REQUEST['position_id']){
            $data['position_id'] = $_REQUEST['position_id'];
            $this->assign('positionId',$_REQUEST['position_id']);
        }
        
        if($_GET['title']){
            $data['title'] = $_GET['title'];
            $this->assign('title',$_GET['title']);
        }
        
        $page = $_GET['p'] ? $_GET['p'] : 1;
        $pageSize = 5;

        $data['status'] = array('neq',-1);
        
        $posContents = D('PositionContent')->getPositionContent($data,$page,$pageSize);
        $count = D('PositionContent')->getPosCount($data);
        
        $res = new \Think\Page($count,$pageSize);
        $pageRes = $res->show();
        
        $posIds = D('Position')->getNormalPositions();
        
        $this->assign('posIds',$posIds);
        $this->assign('posContents',$posContents);
        $this->assign('pageRes',$pageRes);
    	$this->display();
    }
    public function add(){
        if($_POST){
            if(!$_POST['title']){
                return show(0,'标题不能为空');
            }
            if(!$_POST['url']){
                return show(0,'URL不能为空');
            }
            
            try{
                $res = D('PositionContent')->insert($_POST);
                if($res){
                    return show(1,'新增成功');
                }else{
                    return show(0,'新增失败');
                }
            }catch(Exception $e){
                return show(0,$e->getMessage());
            }
        }else{
            $posIds = D('Position')->getNormalPositions();
            
            $this->assign('posIds',$posIds);
            $this->display();   
        }
        
    }
    public function setStatus(){
        if($_POST){
            $id = $_POST['id'];
            $status = array('status'=>$_POST['status']);
            try{
                $res = D('PositionContent')->updateById($id,$status);
                if($res){
                    return show(1,'操作成功');
                }else{
                    return show(0,'操作失败');
                }
            }catch(Exception $e){
                return show(0,$e->getMessage());
            }
        }
        
        return show(0,'没有提交数据');
    }
    public function edit(){
        $id = $_GET['id'];
        $data = D('PositionContent')->find($id);
        
        $posIds = D('Position')->getNormalPositions();
        
        $this->assign('posIds',$posIds);
        $this->assign('posContent',$data);
        $this->display();
    }
    public function save(){
        if($_POST){
            $data = $_POST;
            $id = intval($data['id']);
            unset($data['id']);
            
            try{
                $res = D('PositionContent')->save($id,$data);
                if($res){
                    return show(1,'更新成功');
                }else{
                    return show(0,'更新失败');    
                }
            }catch(Exception $e){
                return show(0,$e->getMessage());
            }
        }
        
        return show(0,'没有提交数据');
    }
}