<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
 * 小程序 区域分配
 */
class Program extends Admin
{
	
	  /**
	   * 获取市
	   * @return  json
	   */      
   
	   public function provinces($id,$check)
	   {  
          //获取市
	   	  $res = Db::table('co_china_data')->where('pid',$id)->select();
	   	  //获取已被选市
	   	  $res['check'] = $this->CityCheck($check);
	      if($res==null) $this->result('',0,'数据为空');
	      $this->result($res,1,'获取成功');
	   }
      
	  /**
	   * 获取市 
	   * @param  $id 省级id $cid 小程序id
	   */     
       
       public function driving()
       {    
       	    $id = input('post.id');
       	    $check = input('post.cid');

       	    if($id==null || $check==null) $this->result('',0,'参数为空');
        	return $this->provinces($id,$check);            
       }
        
      

	  /**
	   * 获取省
	   * @return  json
	   */  
	   public function Pcity()
	   {  
	   	 $data = $this->province();

	   	 if($data==null) $this->result('',0,'获取失败');

	   	 $this->result($data,1,'获取成功');
	   }

       
       
    /**
	   *  添加
	   * @return  json
	   */   
       
       public function RegionAdd()
       {
       	  $data = input('post.');
       	 
       	  if(input('post.gid')==null || input('post.city')==null) $this->result('',0,'参数错误');
          
          //判断当前区域是否已被选择
          $result = Db::table('am_astrict')->where(['gid'=>$data['gid'],'city'=>$data['city']])->find();
          if(!empty($result)) $this->result('',0,'当前区域已被选择');
          // $data['name'] = Db::table('co_china_data')->where('id',$data['city'])->value('name');
          $urban = $this->ProCity($data['city']);
          
          $data['name'] = $urban['name'];
          $data['urban']= $urban['urban'];

          //插入数据
       	  $res = Db::table('am_astrict')->strict(false)->insert($data);
          
          if(!$res) $this->result('',0,'添加失败');


            // 日志写入
            $GLOBALS['err'] = $this->ifName().'添加了'.$urban['name'].$urban['urban'].'有效区域'; 
            $this->__destruct();


          $this->result('',1,'添加成功');
       }

    /**
	   * 有效区域
	   * @return  json
	   */ 
      
      public function checks()
      { 
      	 $id = input('post.id');
      	 if($id==null) $this->result('',0,'参数为空');
      	 $city = Db::table('am_astrict')->where('gid',$id)->field('id,name,urban')->select();
         
         if(empty($city)) $this->result('',0,'数据为空');

         $this->result($city,1,'获取成功');
      }

    /**
	   * 有效区域删除
	   * @return  json
	   */       
     public function ChecDel()
     {
      	 $id = input('post.id');
      	 if($id==null) $this->result('',0,'参数为空');
      	 $res = Db::table('am_astrict')->where('id',$id)->delete();
      	 if(!$res) $this->result('',0,'删除失败');
         
            // 日志写入
            $data = DB::table('am_astrict')->where('id',$id)->field('name','urban')->find();
            $GLOBALS['err'] = $this->ifName().'删除了'.$data['name'].$data['urban'].'有效区域'; 
            $this->__destruct();

      	 $this->result('',1,'删除成功');     	 
     }

      /**
	   * 获取省市
	   * @return  json
	   */ 
      public function ProCity($id)
      {  
      	 //获取市
         $city = DB::table('co_china_data')->where('id',$id)->field('pid,name')->find();

         $name = DB::table('co_china_data')->where('id',$city['pid'])->value('name');
         
         if(empty($name)) $this->result('',0,'参数错误');
       
         // return $name." ".$city['name'];
         return ['name'=>$name,'urban'=>$city['name']];
      }  
   

      /**
	   * 删除
	   * @return  json
	   */  
      
      public function RegionDel()
      {
          $res = Db::table('am_astrict')->where('id',$id)->delete();
          if(!$res) $this->result('',0,'删除失败');
          $this->result('',1,'删除成功');
      }



      /**
	   *  获取已选择市
	   *  @param  小程序id 1邦保养 2约驾小程序 3技师小程序
	   *  @return  array
	   */  
      
      public function CityCheck($id)
      {
      	 
      	 $city = Db::table('am_astrict')->where('gid',$id)->column('city');
      
         return $city;        
      }
     
}
