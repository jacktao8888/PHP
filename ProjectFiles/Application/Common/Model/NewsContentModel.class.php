<?php
namespace Common\Model;
use Think\Model;

class NewsContentModel extends Model {
    private $_db = '';
    
    public function __construct(){
        $this->_db = M('news_content');
    }
    public function insert($data=array()){
        if(!$data || !is_array($data)){
            return 0;
        }
        $data['create_time'] = time();
        if(isset($data['content']) && $data['content']){
            $data['content'] = htmlspecialchars($data['content']);
        }
        return $this->_db->add($data);
    }
    public function find($id){
        $data = array();
        $data['news_id'] = $id;
        $list = $this->_db->where($data)->find();
                    
        return $list;
    }
    public function updateNewsById($id,$data){
        if(!$id || !is_numeric($id)){
            throw_exception('ID不合法');
        }
        if(!$data || !is_numeric($data)){
            throw_exception('更新数据不合法');
        }
        if(isset($data['content']) && $data['content']){
            $data['content'] = htmlspecialchars($data['content']);
        }
        
        return $this->_db->where('news_id='.$id)->save($data);
    }
}