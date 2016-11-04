<?php
namespace Common\Model;
use Think\Model;

class PositionContentModel extends Model {
    private $_db = '';
    
    public function __construct(){
        $this->_db = M('position_content');
    }
    public function insert($data=array()){
        if(!$data || !is_array($data)){
            throw_exception('导入的数据不合法');
        }
        
        return $this->_db->add($data);
    }
    public function getPositionContent($data,$page,$pageSize){
        $offset = ($page - 1)*$pageSize;
        if($data){
            $data['title'] = array('like','%'.$data['title'].'%');
        }
        return $this->_db->where($data)->order('id')->limit($offset,$pageSize)->select();
    }
    public function getPosCount(){
        $data['status'] = 1;
        
        return $this->_db->where($data)->count();
    }
    public function updateById($id,$status){
        if(!$id || !is_numeric($id)){
            throw_exception('ID不合法');
        }
        if(!$status){
            throw_exception('更新数据不合法');
        }
        return $this->_db->where('id='.$id)->save($status);       
    }
    public function find($id){
        
        return $this->_db->where('id='.$id)->find();
    }
    public function save($id,$data){
        if(!$id || !is_numeric($id)){
            throw_exception('ID不合法');
        }
        if(!$data || !is_array($data)){
            throw_exception('更新数据不合法');    
        }
        
        return $this->_db->where('id='.$id)->save($data);
    }
}