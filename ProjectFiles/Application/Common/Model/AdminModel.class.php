<?php
namespace Common\Model;
use Think\Model;

class AdminModel extends Model{
    private $_db = '';
    public function __construct(){
        $this->_db = M('admin');
    }

    public function getAdminByUsername($username){
        $ret = $this->_db->where('username="'.$username.'"')->find();
        return $ret;
    }
    public function select(){
        $condition['status'] = array('neq',-1);
        return $this->_db->where($condition)->select();
    }
    public function insert($data){
        if(!$data || !is_array($data)){
            return false;
        }
        return $this->_db->add($data);
    }
    public function updateById($id,$data=array()){
        if(!$id || !is_numeric($id)){
            throw_exception('ID不合法');
        }
        if(!$data || !is_array($data)){
            throw_exception('数据不合法');
        }
        
        return $this->_db->where('admin_id='.$id)->save($data);
    }
}
