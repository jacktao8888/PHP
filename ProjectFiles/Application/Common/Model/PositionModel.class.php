<?php
namespace Common\Model;
use Think\Model;

class PositionModel extends Model {
    private $_db = '';
    
    public function __construct(){
        $this->_db = M('position');
    }
    
    public function getNormalPositions(){
        $conditions['status'] = array('neq',-1);
        $list = $this->_db->where($conditions)->order('id')->select();
        
        return $list;
    }
}