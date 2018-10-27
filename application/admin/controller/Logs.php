<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 运营商审核列表
*/
class Logs extends Admin
{

  /**
    * 日志列表 
    */
   
   public function LogList()
   {   
   	   $size = 8;
   	   $page = input('post.page') ? :1;
   	   $count = DB::table('co_log')->count();
   	   if(empty($count)) $this->result('',0,'数据为空');
   	   $rows = ceil($count/$size);
   	   $data  = Db::table('co_log')->page($page,$size)->order('id desc')->select();
       if($data){
          $this->result(['rows'=>$rows,'list'=>$data],1,'获取成功');
       }else{
          $this->result('',0,'暂无数据');
       }
   	   
   }
    
  /**
    * 日志删除 
    */    
   
   public function LogDet()
   {
   	  $id = input('post.id');
   	  if(empty($id)) $this->result('',0,'参数错误');
   	  $res  = Db::table('co_log')->where('id',$id)->delete();
   	  if(!$res) $this->result('',0,'删除失败');
   	  $this->result('',1,'删除成功');
   }
 
}