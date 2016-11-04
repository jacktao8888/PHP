<?php
namespace Admin\Controller;
use Think\Controller;

class PositionController extends Controller {
    public function index(){
        if(!session('adminUser')){
            $this->redirect('/admin.php?c=Login');
        }
    	$this->display();
    }
}