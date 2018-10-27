<?php 
namespace app\supply\controller;
use app\base\controller\supply;
use think\File;
use think\Db;

/**
*  审核管理  or 运营商列表
*/
class Auma extends supply{




/*--------------------审核列表----------------------------------------------*/
   /**
    * 运营商审核列表 -- 未审核
    * @param  page
    * @return json
   */   
  
     public function reject_true()
     {   
          
         $this->Operator_list($this->gid,0,input('post.page'));
     }

  /**
    * 运营商审核详情
    * @param  id
    * @return json
   */   
  
     public function true_details()
     {   
         $aid = input('post.aid');

         if ($aid) {
           
           $this->vif($aid,0);

           $data = Db::table('ca_agent')->where('aid',$aid)->field('company,leader,license,province,city,county,address,regions')->find();

             if ($data) {
              $this->result($data,0,'获取成功');
             }
             $this->result('',1,'获取失败');

         }else{
          $this->result('',1,'未找到aid');
         }
        
     }


  /**
    * 运营商审核确定
    * @param  yid  reject
    * @return json
   */  
  
       public function true_confirm()
       {
          $id = input('post.aid');

          if(!$id) $this->result('',1,'参数错误');
          $this->vif($id,0);
          $res = DB::table('cg_supply_triall')->where(['yid'=>$id,'gid'=>$this->gid])->update(['status'=>1,'reject_time'=>date('Y-m-d H:i:s',time())]);
          if($res){
            $this->result('',0,'合作成功');
          }
          $this->result('',1,'合作失败');

          
       }

  /**
    * 运营商审核驳回
    * @param  yid  reject
    * @return json
   */  
    
    public function Reject()
    {
       
       $yid = input('post.aid');
       $this->vif($yid,0);
       $reject = input('post.reject');
       if(!$yid || !$reject) $this->result('',0,'参数为空'); 
                                                                                                 

       $status = DB::table('cg_supply_triall')->where(['gid'=>$this->gid,'yid'=>$yid])->value('status');
       
       if($status==2) $this->result('',1,'已经驳回'); 

       $res = DB::table('cg_supply_triall')->where(['gid'=>$this->gid,'yid'=>$yid])
              ->update(['reject'=>$reject,'reject_time'=>date('Y-m-d H:i:s',time()),'status'=>2]);

       if(!$res) $this->result('',1,'驳回失败'); 

       $this->result('',0,'驳回成功'); 
    }

    
 /**
	 * 运营商列表
	 * @param   id status page
	 * @return 
	 */
    
    public function  Operator_list($id,$status=1,$page=1)
    {
       $size = 10;  
       $max = Db::table('cg_supply_triall a')
              ->join('ca_agent c','a.yid = c.aid')
              ->where(['a.gid'=>$id,'a.status'=>$status])
              ->count();
        if (!$max) {
        	$this->result('',0,'数据为空');
        }
        $wei = ceil($max/$size);
        
        $data = Db::table('cg_supply_triall a')
              ->join('ca_agent c','a.yid = c.aid')
              ->where(['a.gid'=>$id,'a.status'=>$status])
              ->page($page,$size)
              ->field('c.aid,c.company,c.leader,c.phone,c.sale_card,c.close_end_time')
              ->select();
             
        $this->result(['max'=>$wei,'list'=>$data],0,'获取成功');
    } 
  

    public function vif($aid,$sta)
    {
       $status = Db::table('cg_supply_triall')->where(['gid'=>$this->gid,'yid'=>$aid])->value('status');

         $status = $status===$sta?99:$status;
         if(empty($status) || $status!==99) $this->result('',1,'违规操作');  
    }
    


    


/*--------------------运营商列表----------------------------------------------*/

 /**
	 * 运营商列表
	 * @param  page
	 * @return json
	 */  
  
     public function reject_false()
     {   
        
         $this->Operator_list($this->gid,1,input('post.page'));
     }
  
     

 /**
	 * 运营商取消合作
	 * @param   yid cancel
	 * @return json
	 */  
     
     public function cancel()
     {
        
       $yid = input('post.aid');
       $reject = input('post.cancel');
       if(!$yid || !$reject) $this->result('',0,'参数为空'); 
       
       $this->vif($yid,1);

       $res = DB::table('cg_supply_triall')->where(['gid'=>$this->gid,'yid'=>$yid])
              ->update(['cancel'=>$reject,'reject_time'=>date('Y-m-d H:i:s',time()),'status'=>3]);

       if(!$res) $this->result('',1,'取消失败'); 

       $this->result('',0,'取消合作成功');

     }
   

    /**
	 * 运营商详情
	 * @param  yid
	 * @return json
	 */ 
  
    public function Operator_details()
    {
        
        $yid = input('post.aid');
        
        $this->vif($yid,1);

        $res = Db::table('ca_agent a')
               ->where('a.aid',$yid)
               ->join('ca_agent_set b','a.aid = b.aid')
               ->field('a.license,a.company,a.leader,a.regions,a.usecost,a.phone,a.open_shop,b.complain_count')
               ->find();

        if(!$res)  $this->result('',1,'获取信息失败'); 
        
        $this->result($res,0,'获取信息成功');
 

    }
    
  /**
	 * 获取地区
	 * @param  yid
	 * @return json
	 */ 
   public function cc($id)
   {
       $a1 = DB::table('co_china_data')->where('id',$id)->find();
       $a2 = DB::table('co_china_data')->where('id',$a1['pid'])->find();
       $a3 = DB::table('co_china_data')->where('id',$a2['pid'])->find();

       return $a1['name'].$a2['name'].$a3['name'];
   }

    /**
	 * 运营商地区详情
	 * @param  yid
	 * @return json
	 */ 
  
    public function oper_area()
    {
       $yid = input('post.aid');
       $this->vif($yid,1);
       $res = DB::table('ca_area')->where('aid',$yid)->column('area');
 
       foreach ($res as $key => $value) {
       	 $arr[] = $this->cc($value);
       }
       
       if ($arr) {
       	    $this->result($arr,0,'获取信息成功');
       }
   
    }




}