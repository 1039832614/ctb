<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;

/**
 * 车主数据分析
 */

class Carview extends Admin
{

   /**
    *  车主数据分析列表
    */  
   
   public function ownerList()
   {   
      $area = input('post.area');
      if(empty($area)) $this->result('',0,'地区参数为空');      

      $page = input('post.page')?:1;
      // $count = Db::table('u_user')->count();
      // 接受搜索内容
      $search = input('post.search');
      // 判断是否为纯数字 
      if(is_numeric($search))
      { 
        $phone = '%'.$search.'%';
        $plate = '%';
      }else{
        $phone = '%';
        $plate = '%'.$search.'%';
      }
      

      
      $data = DB::table('u_user a')
              ->leftjoin('u_card b','a.id = b.uid')
              ->leftjoin('cs_shop c','b.sid = c.id')
              ->leftjoin('cs_shop_set d','c.id = d.sid')
              ->field('a.id,b.plate,a.name,a.phone,c.usname,count(b.uid) parsum,count(b.uid)-1 plexsum,sum(b.remain_times) moresum,d.province,d.city,d.county')
              ->group('a.id')
              ->whereLike('a.phone',$phone)
              ->whereLike('b.plate',$plate)
              ->where('d.province',$area)
              ->page($page,4)
              ->select();
      if(empty($data)) $this->result('',0,'暂无数据');
      $count = DB::table('u_user a')
              ->leftjoin('u_card b','a.id = b.uid')
              ->leftjoin('cs_shop c','b.sid = c.id')
              ->leftjoin('cs_shop_set d','c.id = d.sid')
              
              ->group('a.id')
              ->whereLike('a.phone',$phone)
              ->whereLike('b.plate',$plate)
              ->where('d.province',$area)
              
              ->count();
      

      if(empty($count)) $this->result('',0,'暂无数据');

      $rows = ceil($count/4);
  
      $price = Db::table('u_card')->field('share_uid,count(id)*10 price')->group('share_uid')->having('share_uid>0')->select();
	      foreach ($data as $key => $value) {
	      	 foreach ($price as $k => $v) {
	      	 	  if(!isset($data[$key]['price'])){
	      	 	  	   $data[$key]['price'] = 0;
	      	 	  }
	      	 	  if($value['id'] == $v['share_uid'])
	      	 	  {     
	                    $data[$key]['price'] = $v['price'];
	      	 	  }
              $data[$key]['areas'] = $value['province'].$value['city'].$value['county'];
	      	 }
	      }
       
       if(!empty(input('post.phone')) || !empty(input('post.plate')))
       {
       	  $this->result($data,1,'检索成功');
       }
       $this->result(['rows'=>$rows,'list'=>$data],1,'获取车主列表成功');
    
   }



   /**
    *  介绍奖励
    */  
   
   public function Price()
   {
   	$uid = input('post.id');
    if(empty($uid)) $this->result('',0,'参数错误');
    $page = input('post.page')?:1;
    $count = Db::table('u_share_income')->where('uid',$uid)->count();
    if(empty($count)) $this->result('',0,'暂无介绍奖励');
    $rows = ceil($count/8);
    $data = Db::table('u_share_income a')
    ->join('u_user b','a.uid = b.id')
    ->where('a.uid',$uid)->field('b.name,a.reward,a.create_time')
    ->page($page,8)
    ->select();
    // $data = json_encode($data);
    // $data = json_decode($data,true);
   	$this->result(['rows'=>$data,'list'=>$rows],1,'获取成功');

   }





}