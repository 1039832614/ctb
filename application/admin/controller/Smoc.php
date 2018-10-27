<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
use think\facade\Request;
use Pay\Epay;
/**
 * 
 * 服务经理审核模块 
 */

class Smoc extends Admin
{  
    
    
    /*
     *  注册审核
     * 
     */
    
    public function regAudit($field=1,$status=0)
    {  

       // 申请地区
       if($field==1){
          $fields = 'min(b.create_time) create_time';
          $group = 'a.id';
          $status = [0,1];
          $sml = [0,1,2,3,4]; 
       }
       // 2 为增加地区
       // 增加地区
       if($field == 2 || $field == 3){
          $fields = 'b.create_time,b.sm_mold,b.area areaid';
          $group = 'b.id';
          $status = [0,1,2];
          $sml = [0,1,2,3,4];
       }

       if($field == 2){
          $sml = [0,1,2,3,4];
       }
       // 取消区域审核
       if($field == 3){
          $sml = 3;
       }
       // 申请加盟
       if($field == 4){
          $sml = 4;
       }
       // 查询数据
       $data = Db::table('sm_user a')
               ->leftjoin('sm_area b' ,'a.id = b.sm_id')
               ->join('co_china_data c','b.area = c.id')
               ->field('a.id uid,b.id,b.sm_type,c.name area,a.name,a.phone,a.head_pic,b.money,b.audit_status,'.$fields)
               // ->where('b.pay_status',1)
               ->whereIn('b.audit_status',$status)
               ->whereIn('b.sm_mold',$sml)
               ->group($group)
               ->order('b.create_time ASC')
               // ->page($page,8)
               // ->having('b.audit_status!=0')
               // ->paginate(8)
               // ->toArray();
               ->select();

// print_r($data);die;
        $arr['data'] = $data;
        if(empty($arr['data'])){
          $this->result('',0,'暂无数据');
        }

        // 修改数据
        $data = $this->savType($arr,$field);

        // 分页
        $this->PolPage($data['data']);
    }    
    


    /*
     *  列表详情
     *
     */
    
    public function regDetail()
    {
      $result = Request::has('uid','post');
      if(!$result){
        $this->result('',0,'参数错误');
      }
      // $data = Db::table('sm_user')->where('id',$result['uid'])->field('name,phone,bank_name,bank_code,bank_branch,account,head_pic')->find();
      $data = Db::table('sm_user a')->leftjoin('co_bank_code b','a.bank_code = b.code')
              ->field('a.name,a.phone,a.bank_name,b.name bank_code,a.bank_branch,a.account,a.head_pic')
              ->where('a.id',$result['uid'])
              ->find();
      if(!$data){
        $this->result('',0,'获取数据失败');
      }
      $this->result($data,1,'获取成功');
      
    }
    /*
     *  注册审核通过 默认值
     *
     */
    
    public function checkREg()
    {
      $id = input('post.id');
      if(!$id){
        $this->result('',0,'参数错误');
      }
      
      $data = Db::table('sm_area a')
              ->join('co_china_data b','a.area = b.id')
              ->field('b.name,a.sm_type,a.sm_profit,a.sm_status')
              ->where('a.id',$id)
              ->find();
      if(empty($data)){
        $this->Result('',0,'暂无数据');
      }
      if($data['sm_type'] == 1){
        $data['sm_type'] = '服务经理';
      }elseif($data['sm_type']==2){
        $data['sm_type'] = '运营总监';
      }

      $this->result($data,1,'获取成功');

    }
    /*
     *  注册审核通过
     *
     */
    
    public function regPass()
    {
      $result = Request::has(['id','sm_profit','sm_mold'],'post');
      if(!$result){
        $this->result('',0,'参数错误');
      }
      if(!$result['id']){
        $this->result('',0,'必要参数不能为空');
      }
      $result['audit_status'] = 1;
      $result['audit_time'] = time();
      $result['sm_status'] = $result['sm_mold'];
      // 审核人
      $result['audit_person'] = $this->ifName();
      unset($result['sm_mold']);
      // 修改用户身份
      $data = DB::table('sm_area a')
              ->where('a.id',$result['id'])
              ->join('sm_user b','a.sm_id = b.id')
              ->field('a.sm_id,a.sm_type,b.name,a.sm_type,a.sm_status,a.task_raw')
              ->find();

      Db::table('sm_user')
      ->where('id',$data['sm_id'])
      ->update(['person_rank'=>$data['sm_type']]);

      // 判断 是加盟还是 合作
      if($result['sm_status'] == 1){
        // 合作 关闭任务奖励
        $result['task_raw'] = 0;
      }else{
        $result['task_raw'] = 1;
      }      

      $re = Db::table('sm_area')
                  ->where('id',$result['id'])
                  ->update($result);


      if($re==false){
        $this->result('',0,'确定失败');
      }

      // 日志写入

      if($data['sm_type'] == 1){
        $type = '服务经理';
      }
      if($data['sm_type'] == 2){
        $type = '运营总监';
      }
      $GLOBALS['err'] = $this->ifName().'通过了'.$type.'【'.$data['name'].'】的地区申请'; 
      $this->estruct();

      $this->result('',1,'确定成功');
    }

    /*
     *  注册审核驳回
     *
     */
    
    public function regDown()
    {
      $id = input('post.id');
      $reason = input('post.reason');
      if(!$id || !$reason){
        $this->result('',0,'必要参数不能为空');
      }
      $result = Db::table('sm_area')->where('id',$id)->where('audit_status',0)
                ->update(['reason'=>$reason,'audit_status'=>2,'audit_time'=>time(),'audit_person'=>$this->ifName() ,'if_read'=>0]);
      
      // $type = Db::table('sm_area')->where('id',$id)->field('sm_id,sm_type')->find();
      $data = DB::table('sm_area a')->join('sm_user b','a.sm_id = b.id') 
              ->field('a.sm_id,a.sm_type,b.name')
              ->where('a.id',$id)
              ->find();
      DB::table('sm_user')->where('id',$data['sm_id'])->update(['person_rank'=>$data['sm_type']]);


      if(!$result){
        $this->result('',0,'驳回操作失败');
      }
      

      // 日志写入
      if($data['sm_type'] == 1){
        $type = '服务经理';
      }
      if($data['sm_type'] == 2){
        $type = '运营总监';
      }      
      $GLOBALS['err'] = $this->ifName().'驳回了'.$type.'【'.$data['name'].'】的地区申请'; 
      $this->estruct();

      $this->result('',1,'驳回成功');
    }
   
    /*
     * 增加地区审核
     *
     */
    
    public function addAudit()
    {
      // echo time();die;
       $this->regAudit(2);
    }   

    /*
     * 取消地区审核列表
     *
     */
    
    public function callAudit()
    {
      $this->regAudit(3,1);
      if(!$result){
        $this->result('',0,'参数错误');
      }

      
    }

    /*
     * 取消地区理由详情
     *   
     */
    
    public function callReason()
    {
      $result = Request::has('id','post');
      if(!$result){
        $this->result('',0,'参数错误');
      }
 
      $reason = DB::table('sm_apply_cancel')->where('sid',$result['id'])->where('status',0)->value('cancel_reason');
      // $reason = DB::table('sm_area')->where('id',$result['id'])->value('reason');
      if(!$reason){
        $this->result('',0,'获取理由失败');
      }
        $this->result($reason,1,'获取成功');
    }

    /*
     * 取消地区审核驳回
     *
     */
    
    public function rejReason()
    {
      
      $result = Request::has('id,reason','post');
      $id = $result['id'];
      if(!$result){
        $this->result('',0,'参数错误');
      }
      if(!$result['id'] || !$result['reason']){
        $this->result('',0,'缺少必要参数');
      }
      $arr['reason'] = $result['reason'];
      // $arr['read_status'] = 1;
      $arr['audit_time'] = time();
      $arr['status']  = 2;
      // 审核人
      $arr['audit_person'] = $this->ifName();

      $area = DB::table('sm_area')->where('id',$result['id'])->value('area');
      $result2 = DB::table('sm_area')->where('id',$result['id'])->update(['sm_mold'=>0 , 'if_read'=>0]);

      $result = Db::table('sm_apply_cancel')->where('sid',$result['id'])->where('status',0)->update($arr);
      

        
      if($result == false){
        $this->result('',0,'驳回失败');
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
      $GLOBALS['err'] = $this->ifName().'驳回了'.$type.'【'.$data['name'].'】的取消地区'; 
      $this->estruct();

      $this->result('',1,'驳回成功');
    }

    /*
     * 取消地区审核通过
     *
     */
    
    public function callPass()
    {
      $result['id'] = input('post.id');
      if(!$result['id']){
        $this->result('',0,'缺少必要参数');
      }

      // $area = DB::table('sm_area')->where('id',$result['id'])->value('area'); 
      // if(empty($area)){
      //   $this->result('',0,'数据错误');
      // }
      $arr['read_status'] = 0;
      $arr['audit_time'] = time();
      $arr['status']  = 1;      
      // 审核人
      $arr['audit_person'] = $this->ifName(); 
      
      Db::startTrans();
      try {
          // 取消合作通过
          Db::table('sm_apply_cancel')->where('sid',$result['id'])->whereIn('status',0)->update($arr);
          Db::table('sm_area')->where('id',$result['id'])->update(['sm_mold'=>2 ,'if_read'=>0]);
          // 提交事务
          Db::commit();
          
      } catch (\Exception $e) {
          // 回滚事务
          Db::rollback();
          $this->result('',0,'通过失败');
      }

      // 日志写入
      $data = DB::table('sm_area a')->join('sm_user b','a.sm_id = b.id') 
              ->field('a.sm_id,a.sm_type,b.name')
              ->where('a.id',$result['id'])
              ->find();
      // print_r($data);die;
      if($data['sm_type'] == 1){
        $type = '服务经理';
      }
      if($data['sm_type'] == 2){
        $type = '运营总监';
      } 
      $GLOBALS['err'] = $this->ifName().'通过了'.$type.'【'.$data['name'].'】的取消地区'; 
      $this->estruct();

      $this->result('',1,'通过成功');
    }
    
    /*
     *  申请提现列表
     * 
     */
    
    public function putList()
    {
       $page = input('post.page')?:1;
       $rows = Db::table('sm_apply_cash')->where('audit_status',0)->count();
       if(!$rows){
        $this->result('',0,'暂无数据');
       }
       $rows = ceil($rows/8);
       $data = Db::table('sm_apply_cash a')
               ->leftjoin('sm_user b','a.sm_id = b.id')
               ->join('co_bank_code c','b.bank_code = c.code')
               ->field('a.id,a.sm_id,b.name,b.phone,b.bank_name,c.name back,b.bank_branch,b.account,a.money,a.create_time,a.audit_status')
               ->where('a.audit_status',0)
               // ->where('a.audit_status',0)
               // ->group('a.sm_id')
               ->page($page,8)
               ->select();
               // print_r($data);die;

       $arr = [];
       foreach ($data as $key => $value) {
            // 删除通过的数据
            if($value['audit_status'] == 1){
              if(!isset($arr[$value['sm_id']]['putsize'])){
                $arr[$value['sm_id']]['putsize'] = 0;
              }
              $arr[$value['sm_id']]['putsize']+=1;
              unset($data[$key]);
            }elseif(!isset($arr[$value['sm_id']]['putsize'])){
                   $arr[$value['sm_id']]['putsize'] = 0;
            }
       }
    
       // 获取提现次数
       if(!empty($arr)){
        foreach ($data as $key => $value) {
          foreach ($arr as $k => $v) {
              if($value['sm_id'] == $k){
                if(!isset($data[$key]['putsize'])){
                  $data[$key]['putsize'] = 0;
                }
                $data[$key]['putsize'] += $v['putsize'];
              }
          }
        }
       }else{
        foreach ($data as $key => $value) {
          $data[$key]['putsize'] = 0;
        }
       }

     $this->result(['rows'=>$rows,'list'=>$data],1,'获取成功');
       
    }
    
    /*
     *  申请提现驳回
     * 
     */
    
    public function turnPut()
    {
      $id = input('post.id');
      $reason = input('post.reason');
      if(!$id || !$reason){
         $this->result('',0,'缺少必要参数');
      }
      $result = Db::table('sm_apply_cash')->where('id',$id)->update(['audit_time'=>time(),'audit_status'=>2,'audit_person'=>$this->ifName(),'reason'=>$reason,'if_read'=>0]);
       
       //  2018 10 19 新增      
      // 获取sm_id
      $sm_id = Db::table('sm_apply_cash')->where('id',$id)->value('sm_id');
      // 修改为已驳回
      $result2 = Db::table('sm_income')->where('sm_id',$sm_id)->where('cash_status',2)->update(['cash_status' => 3]);


      if(!$result){
        $this->result('',0,'设置失败');
      }

      // 日志写入
      $data = DB::table('sm_apply_cash a')
              ->join('sm_user b','a.sm_id = b.id')
              ->field('b.name,b.person_rank,a.money')
              ->where('a.id',$id)
              ->find();
      $type = '';
      if($data['person_rank'] == 1){
        $type = '服务经理';
      }
      if($data['person_rank'] == 2){
        $type = '运营总监';
      } 
      $GLOBALS['err'] = $this->ifName().'驳回了'.$type.'【'.$data['name'].'】的提现申请'.$data['money'].'元'; 
      $this->estruct();
      $this->result('',1,'设置成功');
    }


    /*
     *  申请提现通过
     * 
     */
    
    public function passPut()
    {
      $id = input('post.id');
      // $price = input('post.price');
      if(!$id){
         $this->result('',0,'缺少必要参数');
      }
      
      $order = Db::table('sm_apply_cash a')
               ->join('sm_user b','a.sm_id = b.id')
               ->where('a.id',$id)

               ->field('a.odd_number,a.account,a.account_name,a.bank_code,a.money,b.phone,a.create_time')
               ->find();

      // 收取手续费1.5/1000
      $cmms = ($order['money']*15/10000 < 1 ) ? 1 : round($order['money']*15/10000,2);
      $pmoney = $order['money'] - $cmms;
      // $pmoney = 1;
      $epay = new Epay();

      // $order['account'] =  6222020402036770327;
      // $order['account_name'] = '徐佳孟';
      // $order['bank_code'] = 1002;
      // $order['phone'] = 17601634143;
      // $order['odd_number'] = 'BA01604448525324';    

      $res = $epay->toBank($order['odd_number'],$order['account'],$order['account_name'],$order['bank_code'],$pmoney*100,$order['account_name'].'服务经理申请提现');
      // 提现成功后操作
      if($res['return_code']=='SUCCESS' && $res['result_code']=='SUCCESS'){
          // 更新数据
          $arr = [
            'audit_time' => time(),
            'audit_status' => 1,
            'wx_cmms' => $cmms,
            'cmms_amt' => $res['cmms_amt']/100,
            'audit_person' => $this->ifName(),
            'if_read' => 0, // 新增
          ];
          $save = Db::table('sm_apply_cash')->where('id',$id)->update($arr);
          
          // 获取sm_id
          $sm_id = Db::table('sm_apply_cash')->where('id',$id)->value('sm_id');
          // 修改为已驳回
          $result2 = Db::table('sm_income')->where('sm_id',$sm_id)->where('cash_status',2)->update(['cash_status' => 1 ,'read_status'=>0]);


          if($save !== false){
            // 处理短信参数
            $time = $order['create_time'];
            $money = $order['money'];
            // 发送短信给运营商
            $content = "您于【{$time}】提交的【{$money}】元的提现申请，通过审核，24小时内托管银行支付到账（节假日顺延）！";
            $send = $this->sms->send_code($order['phone'],$content);
            

            // 日志写入
            $data = DB::table('sm_apply_cash a')
                    ->join('sm_user b','a.sm_id = b.id')
                    ->field('b.name,b.person_rank')
                    ->where('a.id',$id)
                    ->find();
            $type = '';
            if($data['person_rank'] == 1){
              $type = '服务经理';
            }
            if($data['person_rank'] == 2){
              $type = '运营总监';
            } 
            $GLOBALS['err'] = $this->ifName().'通过了'.$type.'【'.$data['name'].'】的提现申请'.$order['money'].'元'; 
            $this->estruct();
            

            $this->result('',1,'处理成功');

          }else{
            // 进行异常处理
            $errData = ['apply_id'=>$id,'apply_cate'=>1,'audit_person'=>$audit_person];
            Db::table('am_apply_cash_error')->insert($errData);
            $this->result('',0,'打款成功，处理异常，请联系技术部');
          }

      }else{
         // 返回错误信息
         $this->result('',0,$res['err_code_des']);
      }


      // if(!$result){
      //   $this->result('',0,'设置失败');
      // }
      // $this->result('',1,'设置成功');
    }



   /*
    *  混乱的集合
    *   
    */   
   
   public function savType($data,$field)
   {     
// print_r($data);die;
        // 新增地区时 删除注册数据
         if($field == 2){
            
           $ids = array_unique(array_column($data['data'],'uid')); 
           foreach ($data['data'] as $k => $v) {
               
               if(in_array($v['uid'],$ids)){
                  unset($data['data'][$k]);
                  foreach ($ids as $key => $value) {
                     if($value == $v['uid'] && $v['audit_status']!=2){
                         
                         unset($ids[$key]);
                     }
                  }
               }
              
           }
         }
        
        foreach ($data['data'] as $k => $v) {

            if($v['create_time'] && !empty($v['create_time'])){
              $data['data'][$k]['create_time'] = Date('Y/m/d H:i',strtotime($v['create_time']));
            }
            if($v['sm_type'] == 1){
               $data['data'][$k]['sm_type'] = '服务经理';
            }
            if($v['sm_type'] == 2){
               $data['data'][$k]['sm_type'] = '运营总监';
            }
            // 注册审核 删除不是 未审核的
            if($field == 1 || $field == 2){
               if($v['audit_status'] == 1 || $v['audit_status'] == 2 ){
                   
                   unset($data['data'][$k]); 
               }
            }
            

            


            // 增加地区审核  ， 取消地区审核
            if($field != 1){
           
                unset($data['data'][$k]['sm_type']);
                unset($data['data'][$k]['head_pic']);

            }
          
        }

        $aids = array_column($data['data'], 'id');
        $area = array_column($data['data'], 'areaid');

        // 获取投诉次数
        if($field !=1 ){
          //  获取奏效的投诉次数
            $tousu = Db::table('sm_complaint')->where('sm_status',0)->where('status',1)->field('sm_id,count(id) size')->group('sm_id')->select();
            if(!empty($tousu)){
               
               foreach ($data['data'] as $key => $value) {
                    foreach ($tousu as $k => $v) {
                        // 给每个用户添加投诉次数
                        if($value['uid']==$v['sm_id']){
                          $data['data'][$key]['count'] = $v['size'];
                        }elseif(!isset($data['data'][$key]['count'])){
                          $data['data'][$key]['count'] = 0;  
                        }

                    }
                    unset($data['data'][$key]['audit_status']);
               }
                  
            }

        }   

        // 获取取消合作地区
        if($field == 3){


            $area = Db::table('sm_apply_cancel a')
                   ->join('sm_area c','a.sid = c.id')
                   ->join('co_china_data b','c.area = b.id')
                   ->whereIn('a.sid',$aids)
                   ->where('a.status',0)
                   ->field('distinct(a.id),b.name,a.cancel_reason,a.create_time,a.sid')
                   ->select();
              // print_r($data['data']);die;
               
            if(empty($area)){
               $this->result('',0,'暂无数据');
            }
         // print_R($area);die;
            foreach ($data['data'] as $key => $value) {

                foreach ($area as $k => $v) {
                   if($value['id'] == $v['sid']){
                       $data['data'][$key]['area'] = $v['name'];
                       $data['data'][$key]['cancel_reason'] = $v['cancel_reason'];
                       $data['data'][$key]['create_time'] = $v['create_time'];
                   }

                }
               

            }
            

        }

        // 更新 key
        sort($data['data']);
        $data['data']  = array_values($data['data']);
        if(empty($data['data'])){
          $this->result('',0,'暂无数据');
        }
        // print_r($data);die;
        // 修改状态
        foreach ($data['data'] as $key => $v) {

          if(isset($v['sm_mold'])){
              if($v['sm_mold']==0){
                $data['data'][$key]['sm_mold'] = '冷静期';
              }
              if($v['sm_mold']==1){
                $data['data'][$key]['sm_mold'] = '正常';
              }
              if($v['sm_mold']==2){
                $data['data'][$key]['sm_mold'] = '取消合作';
              }
              if($v['sm_mold']==3){
                $data['data'][$key]['sm_mold'] = '申请取消合作';
              }
              if($v['sm_mold']==4){
                $data['data'][$key]['sm_mold'] = '申请加盟';
              }
       
          }
        }
        // print_r($data);die;
        return $data;
   }

  /**
   * 
   * 分页
   */
   public function PolPage($arr,$page=1,$size=8)
  {   
      $page = input('post.page')?:1;
      // 获取总条数
      $length = count($arr);
      
      // 获取总页数
      $rows = ceil($length/$size);
      // 页数起始
      $onsize = ($page-1)*$size;

    // echo $onsize.'000'.$size;die;
      $arr = array_slice($arr,$onsize,$size);
      
      if(!$arr){
        $this->result('',0,'暂无数据');
      }
      // array_multisort(array_column($arr,'sale_card'),SORT_DESC,$arr);
      $this->result(['last_page'=>$rows,'data'=>$arr],1,'获取成功');
  }

}

