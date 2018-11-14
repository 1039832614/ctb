<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
use think\facade\Request;

/**
 * 
 * 服务经理列表
 */

class SmocList extends Admin
{

   /*
    *  服务经理列表
    *  type  1服务经理  2运营总监
    */
   
   public function list($type=1){
      
     $field = ',count(a.area) areasum';
     if($type == 2){
      	$field = '';
     }

     // 获取服务经理信息
     $data = DB::table('sm_area a')
             ->leftjoin('sm_user c','a.sm_id = c.id')
             ->where('a.sm_type',$type)
             ->where('a.audit_status',1)
             ->where('a.sm_mold','<>',2)
             ->field('c.id,a.sm_id,c.name,c.phone,sum(a.money) price,a.sm_mold,a.sm_type'.$field)
             ->group('a.sm_id')
             ->select();
             // print_r($data);die;
             // echo $type;die;
     if(empty($data)){
     	$this->result('',0,'暂无数据');
     }
     if($type == 1){
	     // 获取服务经理总投诉次数
	     $datas = DB::table('sm_complaint')
	             ->where('sm_status',0)
	             ->where('status',1)
	             ->group('sm_id')
	             ->field('sm_id,count(id) compSize')
	             ->select();
               // print_r($datas);die;
	     if (!empty($datas)){
	         $comp = $datas;
       }else{
         $comp = array();
       }
     }
     // print_r($comp);die;
     if($type == 2){
     	// 获取运营总监信息 or 成员信息
     	$arr = Db::table('sm_team')->field('id,team_name,sm_header_id,sm_member_id')->select();
       
      if(!empty($arr)){
        // $arr 存在 构造 数组 item 和 ids
        foreach ($arr as $key => $value) {
           $arr[$key]['sm_id'] = $value['sm_header_id'];

        }
        $item = null;
        foreach ($arr as $key => $value) {
           $item[$key]['sm_id'] = $value['sm_id'];
           $item[$key]['item_id'] = $value['id'];
           $item[$key]['item'] = $value['sm_header_id'];
           $item[$key]['team_name'] = $value['team_name'];
           $item[$key]['arr']  = explode(',', $value['sm_member_id']);
           $ids[] = explode(',', $value['sm_member_id']);
        }
      }
     
     	
      // print_r($item);die;
      if(!empty($ids)){
         
          // 删除空数组
          $ids = $this->delFiles($ids);

    		  $ids = $this->onArr($ids);
          if(!empty($ids)){
               $ids = array_values($ids);
          }
        
         	$comp = DB::table('sm_complaint')
    	             // ->where('sm_status',0)
    	             // ->where('status',1)
    	             ->whereIn('sm_id',$ids)
    	             ->group('sm_id')
    	             ->field('sm_id,count(id) compSize')
    	             ->select();                                        
    	    if (!empty($comp)){
                foreach ($item as $key => $value) {
                    foreach ($comp as $k => $v) {
                        if(in_array($v['sm_id'],$value['arr'])){
                            if(!isset($item[$key]['compSize'])){
                                $item[$key]['compSize'] = 0;
                            }
                            $item[$key]['compSize'] += $v['compSize'];
                            $item[$key]['sm_id'] = $value['item'];

                        }elseif(isset($item[$key]['team_name'])) {
                            $item[$key]['sm_id'] = $value['item'];
                        }

                    }
                    unset($item[$key]['arr']);
                }

                foreach ($item as $key => $value) {

                    if(!isset($value['compSize'])){
                        $item[$key]['compSize'] = 0;
                    }
                }

                $comp = $item;
            }else{
              $comp = [];
    	        
            }

        }else{
          // 添加投诉次数
          if (isset($item)) {
            foreach ($item as $key => $value) {
              if(!$this->delFiles($value['arr'])){
                $item[$key]['compSize'] = 0;
              }
            } 
           
          }else{
            $item = null;
          }
          $comp = $item;
        }



     }

// print_r($item);die;
     // 获取服务经理收益
     $price = DB::table('sm_income')
              ->where('person_rank',$type)
              ->where('if_finish', 1)
              ->field('sm_id,sum(money) money')
              ->group('sm_id')
              ->select();  
           
           
     // 获取提现金额
     $put = Db::table('sm_apply_cash')->where('audit_status',1)->group('sm_id')->field('sum(money) money,sm_id')->select();

     // 组合数据
     $data = $this->meragelist($data,$comp,$price,$type,$put);
     // 服务经理时组合数据
     if ($type == 1) {
        $partner = $this->partner(); // 获取业务合作用户
        $data = array_merge($data, $partner);
     }
     if(empty($data)){
     	$this->result('',0,'暂无数据');
     } 

     // 搜索
     $search  = input('post.search');   // 搜索内容
     $ms_type = input('post.ms_type');  // 服务经理 or 运营中心 1
     $status  = input('post.status');   // 正常 or 投诉期   2
     $data = $this->serach($data,trim($search),$ms_type,$status);
     
     // 分页
     $this->PolPage($data,0,5);
     
   }

    /**
     * 售卡分成
     * 
     */
   
    public function getDiv()
    {
        $id = input('post.sm_id');
        $type = input('post.type');
        if (!$id || !$type) {
            $this->result('', 0, '缺少必要参数');
        }
        
        // 获取售卡分成
        $count = Db::table('sm_income')->where('sm_id', $id)->count();
        if (!$count) {
            $this->Result('', 0, '暂无数据');
        }
        $column = DB::table('sm_income a')
                  ->join('u_card b', 'a.cid = b.id')
                  ->where('a.sm_id', $id)
                  ->where('if_finish', 1)
                  ->where('a.person_rank', $type)
                  ->field('b.plate, b.cate_name, b.sale_time, b.card_price, a.money')
                  ->page(input('post.page')?:1 ,8)
                  ->select();
        $rows = ceil($count / 8);
        if (!$column){
            $this->Result('', 0, '暂无数据');
        }
        $this->result(['rows'=>$rows, 'list'=>$column], 1, '获取成功');        
    }

   /*
    *  服务经理权限区域
    *
    */
   
   public function jurList()
   {
       $result = Request::has('sm_id','post');
       if(!$result){
       	$this->result('',0,'参数错误');
       }
       $data = DB::table('sm_area a')
               ->join('co_china_data b','a.area = b.id')
               ->where('a.sm_id',$result['sm_id'])
               ->where('audit_status',1)
               ->where('sm_mold','<>',2)
               ->field('b.name,a.sm_status,a.sm_mold,a.create_time')
               ->paginate(6)
               ->toArray();
               
        if(empty($data['data'])){
        	$this->result('暂无数据');
        }

        foreach ($data['data'] as $key => $value) {
          if($value['sm_mold']==0){
            $data['data'][$key]['sm_mold'] = '冷静期';
          }
          if($value['sm_mold']==1){
            $data['data'][$key]['sm_mold'] = '正常';
          }
          if($value['sm_mold']==2){
            $data['data'][$key]['sm_mold'] = '取消合作';
          }
          if($value['sm_status'] == 1){
          	$data['data'][$key]['sm_status'] = '业务合作';
          }
          if($value['sm_status'] == 2){
          	$data['data'][$key]['sm_status'] = '区域加盟';
          }
          if($value['create_time'] && !empty($value['create_time'])){
            $data['data'][$key]['create_time'] = Date('Y/m/d H:i',strtotime($value['create_time']));
          }
        }
       $this->result($data,1,'获取成功');
   }

   /*
    *  服务经理收益
    *
    */
   
   public function prcList($type = 1)
   {
   	 $result = Request::has('sm_id','post');
     if(!$result){
       	$this->result('',0,'参数错误');
     }

     $data = DB::table('sm_income')->where('person_rank',$type)->where('sm_id',$result['sm_id'])->field('sum(money) money,type')->group('type')->select();
     if(empty($data)){
     	$this->result('',0,'暂无数据');
     }
     // print_r($data);die;
     $arr = [];
     foreach ($data as $key => $value) {
       if($value['type']==1){
           $arr['item'] = $value['money'];
       }elseif(!isset($arr['item'])){
        $arr['item'] = 0;
       }
       if($value['type']==2){
           $arr['exp'] = $value['money'];
       }elseif(!isset($arr['exp'])){
        $arr['exp'] = 0;
       }
       if($value['type']== 3){
           $arr['admin'] = $value['money'];
       }elseif(!isset($arr['admin'])){
        $arr['admin'] = 0;
       }
     } 

     $arr['sum'] = $arr['item']+$arr['exp']+$arr['admin'];
   
     $this->result($arr,1,'获取成功');
   }
   
   /*
    *  服务经理设置默认值
    * 
    */
   
   public function upCheck()
   {
       $id = input('post.sm_id');
       if(!$id){
       	$this->result('',0,'参数错误');
       }
       $data = Db::table('sm_area a')
               ->join('co_china_data b','a.area = b.id')
               ->where('a.sm_id',$id)
               ->where('audit_status',1)
               ->where('sm_type',1)
               ->where('a.sm_mold','<>',2)
               ->field('a.id,a.sm_id,b.name,a.sm_profit,a.sm_status,a.team_raw,a.exp_raw,a.task_raw,a.admin_raw')
               ->select();
               // print_r($data);die;
       if(empty($data)){
      
       	$this->result('',0,'暂无数据');
       }
       $arr = null;
       foreach ($data as $k => $v) {
       	  $arr[$k]['area'] = $v['name'];
          $arr[$k]['son']  = $v; 
       }                        
       
       $unshift = $arr[0];
       $unshift['area'] = '全部设置';

       array_unshift($arr,$unshift);
       $this->result($arr,1,'获取成功');
       
   }

   /*
    *  服务经理设置 奋勇
    *
    */
  
   public function upCent()
   {
       $result = Request::has('sm_id,sm_profit,sm_status,team_raw,exp_raw,task_raw,admin_raw','post');
       $id = input('post.id');
       if(!$result || !$id){
       	$this->result('',0,'参数错误');
       } 
       $all = input('post.all');
       if($all==1){
       
          $res = DB::table('sm_area')->where('audit_status',1)->where('id',$id)->update($result);
           
       }else{
       	  $res = DB::table('sm_area')->where('sm_id',$result['sm_id'])->update($result);
          // echo DB::table('sm_area')->getLastsql();die;/
    
       }
       // echo DB::table('sm_area')->getLastsql();die;
       if($res==false){
       	$this->result('',0,'设置失败');
       }

      // 日志写入
      $data = DB::table('sm_area a')->join('sm_user b','a.sm_id = b.id') 
              ->field('a.sm_id,a.sm_type,b.name')
              ->where('a.id',$id)
              ->find();
      // print_r($data);die;
      if($data['sm_type'] == 1){
        $type = '服务经理';
      }
      if($data['sm_type'] == 2){
        $type = '运营总监';
      } 
      $GLOBALS['err'] = $this->ifName().'设置了'.$type.'【'.$data['name'].'】的分佣'; 
      $this->estruct();

       $this->result('',1,'设置成功');

   }
   

   /*
    *  服务经理取消区域默认值
    *
    */
   
   public function calCent()
   {
      $id = input('post.sm_id');
      if(!$id){
      	$this->result('',0,'参数错误');
      }
      $count = DB::table('sm_area a')
              ->join('co_china_data b','a.area = b.id')
              ->where('a.sm_id',$id)
              ->where('a.audit_status',1)
              ->count();
      if(empty($count)){
        $this->result('',0,'暂无数据');
      }
      $rows = ceil($count/6);
      $data = DB::table('sm_area a')
              ->join('co_china_data b','a.area = b.id')
              ->field('b.name,a.id,a.sm_mold is_exits')
              ->where('a.audit_status',1)
              ->where('a.sm_id',$id)
              ->page(input('post.page')?:1,6)
              ->select();
      if(empty($data)){
      	$this->result('',0,'暂无数据');
      }
      $this->result(['rows'=>$rows,'list'=>$data],1,'获取成功');

   }

   /*
    *  服务经理取消区域设置
    *
    */
   
   public function calSa()
   {
      $id = input('post.id');
      $reason = input('post.reason') ?:'取消';
      if(!$id){
      	$this->result('',0,'参数错误');
      }
      // $type = Db::table('sm_area')->where('id',$id)->value('is_exits');
      // $type = $type==1?2:1;

      $result = DB::table('sm_area')->where('id',$id)->update(['sm_mold'=>2 , 'if_read'=>0 , 'reason'=>$reason, 'audit_time'=>time()]);
      if(!$result){
      	$this->result('',0,'设置失败');
      }
      
      // 日志写入
      $area = Db::table('sm_area a')->join('co_china_data b','a.area = b.id')->where('a.id',$id)->value('name');
      $data = DB::table('sm_area a')->join('sm_user b','a.sm_id = b.id') 
              ->field('a.sm_id,a.sm_type,b.name')
              ->where('a.id',$id)
              ->find();
      // print_r($data);die;
      if($data['sm_type'] == 1){
        $type = '服务经理';
      }
      if($data['sm_type'] == 2){
        $type = '运营总监';
      } 
      $GLOBALS['err'] = $this->ifName().'取消了'.$type.'【'.$data['name'].'】的'.$area.'区域'; 
      $this->estruct();

      $this->result('',1,'设置成功');      
   }

  /*
   *  运营总监列表
   *  
   */
  
  public function DicList()
  {
  	$this->list(2);
  }
 
  /*
   *  运营总监列表团队信息
   *  
   */
  
  public function itemDet()
  {
  	$id = input('post.item_id');
  	if(!$id){
  		$this->result('',0,'参数错误');
  	}
  	$data = DB::table('sm_team a')
  	        ->join('sm_area b','a.sm_header_id = b.sm_id')
  	        ->join('sm_user c','a.sm_header_id = c.id')
  	        ->field('a.id,a.team_name,a.leader,c.phone,c.head_pic,a.sm_member_id,b.sm_mold,a.create_time,b.sm_status')
  	        ->where('a.sm_header_id',$id)
  	        ->find();

    if(!$data){
    	$this->result('',0,'暂无数据');
    }

    $data['sm_member_id'] =count( explode(',', $data['sm_member_id']) );
	if($data['sm_mold']==0){
	   $data['sm_mold'] = '冷静期';
	}
	if($data['sm_mold']==1){
	   $data['sm_mold'] = '正常';
	}
	if($data['sm_mold']==2){
	   $data['sm_mold'] = '取消合作';
	}
  if($data['sm_mold']==3){
     $data['sm_mold'] = '申请取消合作';
  }
  if($data['sm_mold']==4){
     $data['sm_mold'] = '申请加盟';
  }
	$data['create_time'] = Date('Y/m/d H:i',strtotime($data['create_time']));
    $this->result($data,1,'获取成功');
  }  

  /*
   *  运营总监列表团队信息
   *  
   */
  
  public function sonList()
  { 
    $id = input('post.item_id');
  	if(!$id){
  		$this->result('',0,'参数错误');
  	}
    $item = Db::table('sm_team')->where('id',$id)->value('sm_member_id');
    if(empty($item)){
    	$this->result('',0,'暂无成员');
    }
    $arr = explode(',',$item);
    $data = Db::table('sm_area a')
            ->leftjoin('co_china_data b','a.area = b.id')
            ->leftjoin('sm_user c','a.sm_id = c.id')
            ->field('c.name,c.phone,b.name area')
            ->whereIn('a.sm_id',$arr)
            ->group('a.sm_id')
            ->select();
    if(empty($data)){
    	$this->result('',0,'获取失败');
    }
    $this->result($data,1,'获取成功');
  }



  /*
   *  运营总监列表 查看投诉内容
   *  
   */
  
  public function comList()
  {
    $id = input('post.item_id');
  	if(!$id){
  		$this->result('',0,'参数错误');
  	}
    $item = Db::table('sm_team')->where('id',$id)->value('sm_member_id');
    if(empty($item)){
    	$this->result('',0,'暂无成员');
    }
    $arr = explode(',',$item);
    $count = DB::table('sm_complaint a')
            ->join('sm_user b','a.sm_id = b.id')
            ->field(' a.id,b.name,b.phone,a.create_time,a.sm_status,a.handle_time,a.name tousu,a.phone tousuphone,a.content')
            ->whereIn('sm_id',$item)
            ->group('a.id')
            ->count();
    if(empty($count)){
      $this->result('',0,'暂无数据');
    }
    $rows = ceil($count/6);
    $data = DB::table('sm_complaint a')
            ->join('sm_user b','a.sm_id = b.id')
            ->field(' a.id,b.name,b.phone,a.create_time,a.sm_status,a.handle_time,a.name tousu,a.phone tousuphone,a.content')
            ->whereIn('sm_id',$item)
            ->group('a.id')
            ->page(input('post.page')?:1,6)
            ->select();
     if(empty($data)) $this->result('',0,'暂无数据');
     foreach ($data as $key => $value) {
        if(!empty($value['create_time'])){
		   $data[$key]['create_time'] = Date('Y/m/d H:i',strtotime($value['create_time']));  
		}else{
			$data[$key]['create_time'] = '---';
		}   
		if(!empty($value['handle_time'])){
		   $data[$key]['handle_time'] = Date('Y/m/d H:i',$value['handle_time']);  
		}else{
			$data[$key]['handle_time'] = '---';
		}  	
     }
     $this->result(['rows'=>$rows,'list'=>$data],1,'获取成功');
    
  }
  /*
   *  运营总监列表 查看投诉内容
   *  
   */
  
  public function clde()
  {
    $id = input('post.id');
  	if(!$id){
  		$this->result('',0,'参数错误');
  	}      
    $count = Db::table('sm_complaint')->where('id',$id)->value('content');
    if(!$count){
    	$this->result('',0,'暂无数据');
    }
    $this->result($count,1,'获取成功');
  }
   
  /*
   *  运营总监列表 查看利益
   *  
   */
    
  public function sPlist()
  {
  	$this->prcList(2);
  }

  /*
   *  运营总监列表 分佣默认值
   *  
   */

   public function pcheckSav()
   {
     $id = input('post.id');
       if(!$id){
       	$this->result('',0,'参数错误');
     }
     $id = DB::table('sm_area')->where('sm_id',$id)->where('sm_type',2)->where('audit_status',1)->value('id');
     $data = DB::table('sm_area')->where('id',$id)->field('sm_profit,sm_status,team_raw,exp_raw,task_raw,admin_raw')->find();

     if(empty($data)){
         // return 0;
         $this->result('',0,'暂无数据');
     }
     $this->result($data,1,'获取成功');
   }

  /*
   *  运营总监列表 分佣设置
   *  
   */
  
   public function priceSav()
   {
       $result = Request::has('sm_profit,sm_status,team_raw,exp_raw,admin_raw','post');
       $id = input('post.id');
       if(!$result || !$id){
       	$this->result('',0,'参数错误');
       } 
       $id = DB::table('sm_area')->where('sm_id',$id)->where('sm_type',2)->where('audit_status',1)->value('id');
       $res = DB::table('sm_area')->where('audit_status',1)->where('id',$id)->update($result);   
       // echo DB::table('sm_area')->getLastsql();die;
       if($res==false){
       	$this->result('',0,'设置失败');
       }
       $this->result('',1,'设置成功');

   }
  


  /*
   *  运营总监列表 取消
   *  
   */
  
  public function quxiao()
  {
       $id = input('post.sm_id');
       $reason = input('post.reason');
       if(!$id){
        $this->result('',0,'参数错误');
       }  
       $result = Db::table('sm_area')->where('sm_id',$id)->update(['sm_mold'=>2,'reason'=>$reason ,'audit_time'=>time()]);
       // 修改身份为游客
       DB::table('sm_user')->where('id' ,$id)->update(['person_rank'=>0]);
       // 删除团队
       DB::table('sm_team')->where('sm_header_id', $id)->delete();
       Db::table('sm_team_invite')->where('sm_header_id', $id)->delete();
       
       if($result===false){
        $this->result('',0,'设置失败');
       }
       $this->result('',1,'设置成功');          
  }
  
  /*
   *  投诉列表
   */
  
  public function tousu()
  {  
     $search = trim(input('post.search'));
     $status = input('post.status');
    
    if($status == 2){
      $status = [0];
    }
    if($status == 0){
      $status = [0,1];
    }

     $page = input('post.page')?:1;

     $data = DB::table('sm_complaint a')
            ->join('sm_user b','a.sm_id = b.id')
            ->field(' a.id,b.name,b.phone,a.create_time,a.sm_status,a.handle_time,a.content,a.name tousuren')
                        ->wherelike('b.name','%'.$search.'%')
            ->whereIn('sm_status',$status)
            ->group('a.id')
            ->count();

     $rows = ceil($data/8);

  	 $data = DB::table('sm_complaint a')
            ->join('sm_user b','a.sm_id = b.id')
            ->field(' a.id,b.name,b.phone,a.create_time,a.sm_status,a.handle_time,a.content,a.name tousuren')
            ->group('a.id')
            ->wherelike('b.name','%'.$search.'%')
            ->whereIn('sm_status',$status)
            ->page($page,8)
            ->select();


      // 获取运营总监   
      $arr = Db::table('sm_team a')->join('sm_user b','a.sm_header_id = b.id')->field('b.name,a.sm_member_id')->select();
      foreach ($arr as $key => $value) {
        $arr[$key]['son'] = explode(',', $value['sm_member_id']);                                                                         
      }      
      // print_r($arr);die;
      foreach ($data as $key => $value) {
        if(!empty($arr)){ 
             foreach ($arr as $k => $v) {
                  if(in_array($value['id'], $v['son'])){
                      if(!isset($data[$key]['yyzj'])){
                        $data[$key]['yyzj'] = '';
                      }
                      $data[$key]['yyzj'] .= ' '.$v['name'].' ';
                  }elseif(!isset($data[$key]['yyzj'])){
                      $data[$key]['yyzj'] = '暂无运营总监';
                  }

             }
          }else{
            $data[$key]['yyzj'] = '暂无运营总监';
          }

      }

// print_r($data);die;

     if(empty($data)) $this->result('',0,'暂无数据');
     foreach ($data as $key => $value) {
        if(!empty($value['create_time'])){
		       $data[$key]['create_time'] = Date('Y/m/d H:i',strtotime($value['create_time']));  
    		}else{
    			 $data[$key]['create_time'] = '---';
    		}   
    		if(!empty($value['handle_time'])){
    		   $data[$key]['handle_time'] = Date('Y/m/d H:i',$value['handle_time']);  
    		}else{
    			 $data[$key]['handle_time'] = '---';
    		}  	
     }
     $search = input('post.search');
     $status = input('post.status');
     // $data = $this->Tsearch($data,$search,$status);
     if(empty($data)){
      $this->result('',0,'暂无数据');
     }

     $this->result(['list'=>$data,'rows'=>$rows],1,'获取成功');
  }


  /**
   * 投诉详情
   * @param  [type] $id [投诉列表id]
   * @param  [type] $type [投诉类型]
   * @return [type]     [description]
   */
  public function tsDetail($id)
  {
  	 $list = Db::table('sm_complaint sc')
  	 		->where('id',$id)
  	 		->field('company,phone,type,name,pro_id,city_id,county_id')
  	 		->find();
  	 $address = Db::table('co_china_data')->whereIn('id',$list['pro_id'].','.$list['city_id'].','.$list['county_id'])->column('name');
  	 $list['address'] = implode('',$address);
  	 if($list) $this->result($list,1,'获取投诉人详情成功');
  	 $this->result('',0,'暂无数据');
  }

  
  public function aaa()
  {
    $sm_id = input('post.sm_id');
    $this->checkPaus($sm_id);
  }
 
  
   /*
   *  服务经理 暂停分佣默认值
   * 
   */
  
  public function checkPaus($sms_id=0)
  {
    if($sms_id > 0 ){
      $id = $sms_id;
    }
    if($sms_id==0){
       $id = input('post.id');
    }


    if(empty($id)){
      $this->result('',0,'参数错误');
    }
    // $sm_id = DB::table('sm_area')
    $data = Db::table('sm_sus')->where('area_id',$id)->field('area_id id, team_time, exp_time, task_time, admin_time')->find();
    
    if(empty($data)){
      // $this->result('',0,'暂无数据');
      $data['id'] = $id;
      $data['team_time'] = 0;$data['exp_time'] = 0;$data['task_time'] = 0;$data['admin_time'] = 0;
    }
  
      
      if(!empty($data['team_time'])){
        $data['team_time'] = round(($data['team_time'] - time()) / 3600 / 24);
      }else{
        $data['team_time'] = 0;
      }
      if(!empty($data['exp_time'])){
        $data['exp_time'] = round(($data['exp_time'] - time()) / 3600 / 24);
      }else{
        $data['exp_time'] = 0;
      }
      if(!empty($data['task_time'])){
        $data['task_time'] = round(($data['task_time'] - time()) / 3600 / 24);
      }else{
        $data['task_time'] = 0;
      }
      if(!empty($data['admin_time'])){
        $data['admin_time'] = round(($data['admin_time'] - time()) / 3600 / 24);
      }else{
        $data['admin_time'] = 0;
      }


    
    $this->result($data,1,'获取成功');
  }

  /*
   *  服务经理 暂停分佣 单独设置
   * 
   */
  
  public function pause()
  {
       $result = Request::has('id,team_time,exp_time,task_time,admin_time');
       if(!$result){
          $this->result('',0,'参数错误');
       }
       // if(!$result['team_time'] && !$result['exp_time'] && !$result['task_time'] && !$result['admin_time'] ){
       //  $this->result('',0,'缺少必要参数');
       // }
       $arr2['area_id'] = $result['id'];
       DB::table('sm_sus')->whereIn('area_id',$result['id'])->delete();
       // 团队开启时间
       if(!empty($result['team_time'])){
          $arr2['team_time'] = strtotime("+".floor($result['team_time'])." days");
          $arr['team_raw'] = 1;
       }
       // 开发奖励开启时间
       if(!empty($result['exp_time'])){
          $arr2['exp_time'] = strtotime("+".floor($result['exp_time'])." days");
          $arr['exp_raw'] = 1;
       }
       // 任务奖励开启时间
       if(!empty($result['task_time'])){
          $arr2['task_time'] = strtotime("+".floor($result['task_time'])." days");
          $arr['task_raw'] = 1;
       }
       // 管理奖励开启时间
       if(!empty($result['admin_time'])){
          $arr2['admin_time'] = strtotime("+".floor($result['admin_time'])." days");
          $arr['admin_raw'] = 1;
       }       
       // print_r($arr);
       // print_r($arr2);die;
      // 启动事务
      Db::startTrans();
      try {

          // 关闭奖励
          DB::table('sm_area')->where('id',$result['id'])->update($arr);

          // 设置开启时间
          DB::table('sm_sus')->insert($arr2);
          // 提交事务
          Db::commit();
          
      } catch (\Exception $e) {
          // 回滚事务
          Db::rollback();
          $this->result('',0,'设置失败');
      }

    

      $this->result('',1,'设置成功');
       
  } 
 



 public function ddd()
 {                                                                          
   $this->Allpause();
 }

 /*
   *  服务经理 暂停分佣 全部设置
   * 
   */
  
  public function Allpause()
  {
       $result = Request::has('sm_id,team_time,exp_time,task_time,admin_time','post',false);
       if(!$result){
          $this->result('',0,'参数错误');
       }
       if(!$result['team_time'] && !$result['exp_time'] && !$result['task_time'] && !$result['admin_time'] ){
        $this->result('',0,'缺少必要参数');
       }
       // $result['sm_id'] = input('post.sm_id');
       $datas = Db::table('sm_area')->where('sm_id',$result['sm_id'])->where('audit_status',1)
               ->where('sm_type',1)->column('id');
       
       if(empty($datas)){
        $this->Result('',0,'数据错误');
       }
       DB::table('sm_sus')->whereIn('area_id',$datas)->delete();
       // 团队开启时间
       if(!empty($result['team_time'])){
          $arr2['team_time'] = strtotime("+".floor($result['team_time'])." days");
          $arr['team_raw'] = 1;
       }else{
          $arr2['team_time'] = 0;
       }
       // 开发奖励开启时间
       if(!empty($result['exp_time'])){
          $arr2['exp_time'] = strtotime("+".floor($result['exp_time'])." days");
          $arr['exp_raw'] = 1;
       }else{
          $arr2['exp_time'] = 0;
       }
       // 任务奖励开启时间
       if(!empty($result['task_time'])){
          $arr2['task_time'] = strtotime("+".floor($result['task_time'])." days");
          $arr['task_raw'] = 1;
       }else{
          $arr2['task_time'] = 0;
       }
       // 管理奖励开启时间
       if(!empty($result['admin_time'])){
          $arr2['admin_time'] = strtotime("+".floor($result['admin_time'])." days");
          $arr['admin_raw'] = 1;
       }else{
          $arr2['admin_time'] = 0;
       }

      $all = [];
      foreach ($datas as $key => $value) {
         $all[$key]['area_id'] = $value;
         $all[$key]['team_time'] = $arr2['team_time'];
         $all[$key]['exp_time'] = $arr2['exp_time'];
         $all[$key]['task_time'] = $arr2['task_time'];
         $all[$key]['admin_time'] = $arr2['admin_time'];
      }
    
// print_r($all);die;
      // 启动事务
      Db::startTrans();
      try {
        
          // 关闭奖励
          DB::table('sm_area')->where('sm_id',$result['sm_id'])->where('audit_status',1)->where('sm_type',1)->update($arr);
          // echo DB::table('sm_area')->getLastsql();die;
          // 设置开启时间
          DB::table('sm_sus')->insertAll($all);

          // 提交事务
          Db::commit();
          
      } catch (\Exception $e) {
          // 回滚事务
          Db::rollback();
          $this->result('',0,'设置失败');
      }
      $this->result('',1,'设置成功');
       
  } 





   
   public function dingshi()
   { 
    // echo time()+5000;die;
      $data = DB::table('sm_sus')->select();
      if(empty($data)){
        exit;
      }
      $time = time()+650;
      $time2 = time();
      $arr = null;

      foreach ($data as $key => $value) {
          // echo Date('Y-m-d H:i:s',$value['team_time']);
          if($value['team_time']+650>time() && $value['team_time']<$time){
           
              $arr['team_raw'] = 1;
          }
          if($value['exp_time']+650>time() && $value['exp_time']<$time){
            
              $arr['exp_raw'] = 1;
          }
          if($value['task_time']+650>time() && $value['task_time']<$time){
             
              $arr['task_raw'] = 1;
          }
          if($value['admin_time']+650>time() && $value['admin_time']<$time){
              
              $arr['admin_raw'] = 1;
          }
          
          if( isset($arr['team_raw']) ||  isset($arr['exp_raw']) || isset($arr['team_raw']) || isset($arr['admin_raw'])  ){
            $id = $value['area_id'];
          }
          if($arr){
          
            // $ids = array_column($arr,'id');
            $result = DB::table('sm_area')->where('area',$id)->update($arr);
            // $result = Db::table('sm_sus')->where('area_id',$id)->delete();
              
          }
          
      }
     
   }


   public function xia()
   {
     $id =input('post.id');
     if(!$id){
        $this->result('',0,'参数错误');
     }
     $data = Db::table('sm_area a')->join('sm_user b','a.sm_id = b.id')
             ->join('co_china_data c','a.area  = c.id')
             ->field('b.name,b.head_pic,c.name area')
             ->where('a.id',$id)
             ->find();
      if(empty($data)){
        $this->result('',0,'暂无数据');
      }
      $this->result($data,1,'获取成功');
   }
   
   // 获取提现详情
   public function dePut(){
       $id = input('post.sm_id');
       if(!$id){
        $this->result('',0,'参数错误');
       }
       $count = Db::table('sm_apply_cash')->where('audit_status',1)->where('sm_id',$id)->count();
       if(empty($count)){
        $this->result('',0,'暂无数据');
       }       
       $rows = ceil($count/6);
       $data = Db::table('sm_apply_cash')->where('audit_status',1)->where('sm_id',$id)->field('money,create_time,arrive_time')->page(input('post.page')?:1,6)->select();
       if(empty($data)){
        $this->result('',0,'暂无数据');
       }
       $this->Result(['rows'=>$rows,'list'=>$data],1,'获取成功');
   }

   /*
    *  合并
    * 
    */
   
   public function meragelist($data,$comp,$price,$type,$put)
   {   
      // print_r($data);die;
      if(empty($data)){
      	$this->result('',0,'暂无数据');
      }   
 
      foreach ($data as $key => $value) {
      	   
          // type = 1 获取提现  
          if($type == 1){
            if(!empty($put)){
              foreach ($put as $k => $v) {
                  if($value['sm_id'] == $v['sm_id']){
                        $data[$key]['put'] = $v['money'];
                  }elseif(!isset($data[$key]['put'])){
                        $data[$key]['put'] = 0;
                  }
              }
            }else{
              $data[$key]['put'] = 0;
            }
            
          }

      	  // 添加投诉次数字段 
      	  if(!empty($comp)){

      	  	foreach ($comp as $k => $v) {
                  

      	  	    if($value['id'] == $v['sm_id']){

      	  	    	if($type==2){
                  
      	  		     	$data[$key]['team_name'] = $v['team_name'];
      	  		     	$data[$key]['item_id'] = $v['item_id'];
      	  	     	}

      	  	     	if(!isset($data[$key]['compSize'])){
      	  	     		$data[$key]['compSize'] = 0;
      	  	     	}
                  // elseif(isset($data[$key]['compSize'])){
                    // echo 1;die;
                  $data[$key]['compSize'] += $v['compSize'];
                  // }
                  
      	  	    }elseif(!isset($data[$key]['compSize'])){
      	  	    	$data[$key]['compSize'] = 0;
      	  	    }

                if(!isset($data[$key]['team_name'])){
      	  	    	$data[$key]['team_name'] = '暂无团队';
                  $data[$key]['item_id'] = null;
      	  	    }

                
                
      	    }

      	  }else{
      	  	if(!isset($data[$key]['comSize'])){
      	  		$data[$key]['compSize'] = 0;
      	  	}
      	 }
      	  
          
          // 合并收益
          if(empty($price)){
            $data[$key]['money'] = 0;
          }else{
            foreach ($price as $k => $v) {
                if(!empty($price)){
                    if($value['id'] == $v['sm_id']){
                      $data[$key]['money'] = $v['money'];
                    }elseif(!isset($data[$key]['money'])){
                      $data[$key]['money'] = 0;
                    }
                }else{
                  if(!iseet($data[$key]['money'])){
                    $data[$key]['money'] = 0;
                  } 
                }
                        
            }
            
          }
               
          if($type==2){
            // 删除取消合作的数据
            if($value['sm_mold']==2){
              // $data[$key]['sm_mold'] = '取消合作';
              unset($data[$key]);
            }
          }

          // 修改状态
          if($value['sm_mold']==0){
            $data[$key]['sm_mold'] = '冷静期';
          }
          if($value['sm_mold']==1){
            $data[$key]['sm_mold'] = '正常';
          }
          if($value['sm_mold']==2 && $type!=2){
            $data[$key]['sm_mold'] = '取消合作';
          }
          if($value['sm_mold']==3){
            $data[$key]['sm_mold'] = '申请取消合作';
          }
          if($value['sm_mold']==4){
            $data[$key]['sm_mold'] = '申请加盟';
          }
          
      }
      // 添加 投诉期 状态  
      foreach ($data as $key => $value) {
        if($value['compSize']>0){
          $data[$key]['pstatus'] = 1;
        }
        if($value['compSize']==0){
          $data[$key]['pstatus'] = 0;
        }
        // 删除空数据
        if(empty($value['id'])){
          unset($data[$key]);
        }
      }
      return $data;

   }
  /**
   * 
   * 分页
   */
   public function PolPage($arr,$page=1,$size=2)
  {   
  	  $page = input('post.page')?:1;
      // 获取总条数
      $length = count($arr);
      
      // 获取总页数
      $rows = ceil($length/$size);
      // 页数起始
      $onsize = ($page-1)*$size;

      $arr = array_slice($arr,$onsize,$size);
      
      // array_multisort(array_column($arr,'sale_card'),SORT_DESC,$arr);
      $this->result(['rows'=>$rows,'list'=>$arr],1,'获取成功');
  }
  
  /*
   *  搜索
   * 
   */
  
  public function serach($data,$search,$ms_type,$status)
  {  

    // 搜索内容 并且 状态 不存在时 不做处理
    if(empty($search) && !$status){
      return $data;
    }
    $result = '';

   
   if(!empty($status)){
      // 删除状态不匹配的数据
      if($status == 2){
        $status = 0;
      }                               
      foreach ($data as $key => $value) {
      
        if(isset($value['pstatus']) && $value['pstatus'] != $status){
              unset($data[$key]);
         }
      }
    }
// print_r($data);die;
    if(empty($data)){
      $this->result('',0,'暂无数据');
    }    
    // 取出与搜索匹配的数据
    foreach($data as $k=>$v){

      // 判断为 服务经理 还是 运营中心
      if($v['sm_type']==$ms_type){
        $result[] = $v;
      }         
    }
    

    if(empty($search)){
      return $data;
    }
    if(empty($result)){
      $this->result('',0,'暂无数据');
    }
    $r = [];
    foreach ($result as $key => $v) {
      // 获取搜索数据
      if(!empty($search) && strstr($v['name'],$search)){
          $r[] = $v;
      }          
    }    

    return $r;
  }

/*
 *  投诉列表搜索
 * 
 */
  
  public function Tsearch($data,$search,$status)
  {  
     if(empty($search)){
      return $data;
     }
     
      // 
      if(!empty($status)){
        if($status == 2){
          $status = 0;
        }
        // 删除状态不匹配的数据
        foreach ($data as $key => $value) {
            if($value['sm_status'] != $status){

              unset($data[$key]);
            }
        }
      }
      if(empty($data)){
        $this->result('',0,'暂无数据');
      }
     $result = '';
     foreach ($data as $k => $v) {
       // 获取搜索数据
       if(!empty($search) && strstr($v['name'],$search)){
          $result[] = $v;
       }

    }

    if(empty($search)){
      return $data;
    }

    if(empty($result)){
      $this->result('',0,'暂无数据');
    }
    
    // $this->result('',0,'');
    return $result;

  }

   





   /*
    * 转换为 一维数组
    */
   public function onArr($array){
     	$result=array();
	    array_walk_recursive($array,function($value) use (&$result){
		  array_push($result,$value);
		});
		return $result;
   }
    
    // 删除空数据
    public function delFiles($files)
    {
        // 终止
        if (!$files) {
            return;
        }

        foreach ($files as $key => $file) {
            // 如果是数组 ， 重新进入
            if (is_array($file)) {
                $files[$key] = $this->delFiles($files[$key]);
            } 
            //  空key 做相应操作
            if (empty($files[$key])) {
                // print_r($files[$Key]);die;
                // unset($files[$key]);
                unset($files[$key]);
            }
        }
                                                                                                                                               
        return $files;
    }
   

    public function getPro()
    {
        //获取所有的省份
        $pro = Db::table('co_china_data')
                // ->where('pid',1)
                ->select();
        //获取所有被选择的省份
        $area = Db::table('sm_area')
                // ->where([
                //     'pay_status' => 1
                // ])
                ->field('id,area')
                ->select();
        foreach ($area as $key=>$value){
            foreach ($pro as $k=>$v){
                if ($area[$key]['area'] == $pro[$k]['id']){
                    unset($pro[$k]);
                }
            }
        }
      
        $this->result($pro,1,'获取成功');
    }


    public function ccFiles($files)
    {
        // 终止
        if (!$files) {
            return;
        }

        foreach ($files as $key => $file) {
          
            // 如果是数组 ， 重新进入
            if (is_array($file)) {
                $files[$key] = $this->ccFiles($files[$key]);
            } 
            //  空key 做相应操作
            if (empty($files[$key])) {
                // print_r($files[$Key]);die;
                // unset($files[$key]);
                unset($files[$key]);
            }
        }
                                                                                                                                               
        return $files;
    }

    /**
     *  获取服务经理---业务合作
     */
    
    public function partner()
    {   
        // 获取id、名称、手机号
        $data = DB::table('sm_user')->where('joinStatus', 0)->field('id, name, phone')->select();
        if (!$data) {
            return null;
        }
        // 获取金额
        $ids = array_column($data, 'id');
        
        $price = DB::table('sm_income')
                 ->whereIn('sm_id', $ids)
                 ->where('if_finish', 0)
                 // ->whereIn('cash_status', [])
                 ->field('sm_id, sum(money) price')
                 ->group('sm_id')
                 ->select();

        // 组合数据
        foreach ($data as $k => $v) {
            if (isset($price)) {
                foreach ($price as $key => $value) {
                    if ($v['id'] == $value['sm_id']) {
                        $data[$k]['price'] = $value['price'];   // 获取金额
                    }
                }
            } else {
                $data[$key]['price'] = 0;
            }
            $data[$k]['sm_id'] = $data[$k]['id']; // 获取服务经理id
            $data[$k]['id'] = 666666;             // 定义id
            $data[$k]['sm_mold'] = '业务合作';    // 冷静期？？正常？？业务合作？？
            $data[$k]['sm_type'] = 1;        // 1 服务经理
            $data[$k]['areasum'] = 0;
            $data[$k]['put'] =  0;        // 提现金额
            $data[$k]['compSize'] = 0;    // 投诉次数
            $data[$k]['pstatus'] = 2;     // 投诉期状态 ？？３？？业务合作？？
        }

                                                                                                                 
        return $data;
    }

}