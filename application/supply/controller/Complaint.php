<?php 
namespace app\supply\controller;
use app\base\controller\supply;
use think\File;
use think\Db;

/**
*  投诉管理
*/
class Complaint extends supply{


   /**
	 * 获取投诉列表
	 * @return json
	 */
       public function Complaint_show()
       {    

       	  $page = input('post.page')?input('post.page'):1;
       	  $p = 10;

       	  $max  = Db::table('u_complain a')
       	       ->join('cg_supply b','a.gid = b.gid')
       	       ->join('u_user c','a.uid = c.id')
       	       ->field('b.company,c.name,c.phone,a.content,a.create_time')
       	       ->count();
          $wei = ceil($max/$p);

          $data  = Db::table('u_complain a')
       	       ->join('cg_supply b','a.gid = b.gid')
       	       ->join('u_user c','a.uid = c.id')
       	       ->field('b.company,c.name,c.phone,a.content,a.create_time')
       	       ->page($page,$p)
       	       ->select();
           $this->result(['max'=>$wei,'list'=>$data],1,'获取成功');
       }











}