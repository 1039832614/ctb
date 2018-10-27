<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
use think\facade\Request;

/**
 * 
 *  消息提示
 */

class SmocMsg extends Admin
{
  
   /*
    *  消息提示
    *  
    */
   
    public  function msgall()
    { 
       $msgall['appMsg'] = $this->appMsg();
       $msgall['regMsg'] = $this->regMsg();
       $msgall['addMsg'] = $this->addMsg();
       $msgall['offMsg'] = $this->offMsg();
       $msgall['putMsg'] = $this->putMsg();
       $msgall['comMsg'] = $this->comMsg();
       $msgall['joinMsg'] = $this->joinMsg();
       $this->result($msgall,1,'获取成功');
    }

    /*
     *  审核申请信息条数
     * 
     */
     
    public function appMsg()
    {  
       // 注册审核消息条数
       $regMsg = $this->regMsg();
       // 增加地区消息条数
       $addMsg = $this->addMsg();
       // 取消地区消息条数
       $offMsg = $this->offMsg();
       // 提现消息条数
       $putMsg = $this->putMsg();
       return $regMsg+$addMsg+$offMsg+$putMsg;
    }

    /*
     *  注册审核消息条数
     *  
     */
    
    public function regMsg()
    {
       $count = DB::table('sm_area')
		      	// ->where('pay_status',1)
		      	
		    	->whereIn('audit_status',[0,1])
		    	->field('audit_status')
		        ->group('sm_id')
		        ->having('min(create_time)')
		        ->select();
        if(empty($count)){
        	return 0;
        }  
        $size = 0;                               
        foreach ($count as $key => $value) {
            if($value['audit_status']==0){
            	$size ++;
            }
        }
       return $size;
    }

    /*
     *  确认加盟消息条数
     *
     */
    
    public function joinMsg()
    {
        return 0;
    }
    
   
    /*
     *  增加地区审核消息条数
     *
     */
    
    public function addMsg()
    {
    	$count = DB::table('sm_area a')
                 ->join('sm_user b','a.sm_id = b.id')
                 // ->where('a.pay_status',1)
                 ->whereIn('a.audit_status',[0,1,2])
                 // ->where('sm_mold',2)
                 ->field('a.id,a.audit_status,a.sm_id')
                 // ->group('sm_id')
                 ->select();
                 // print_r($count);die;
    	if(empty($count)){
    		return 0;
    	}
    
	    
	$ids = array_unique(array_column($count,'sm_id')); 
	
	// 删除注册信息
	foreach ($count as $k => $v) {
	   
	   if(in_array($v['sm_id'],$ids)){
	      unset($count[$k]);
	    
	      foreach ($ids as $key => $value) {
	         if( $v['sm_id']==$value && $v['audit_status']!=2){

	             unset($ids[$key]);
	             break;
	         }
	      }     
	   }
	   // 去除已审核数据
	   if($v['audit_status'] == 1 || $v['audit_status'] == 2 ){
	           
	           unset($count[$k]); 
	    }
	  
	}

	return count($count);
    
    }

    /*
     *   取消地区消息条数
     * 
     */
    
    public function offMsg()
    {
    	$count = Db::table('sm_apply_cancel a')
        ->join('sm_area b','a.sid = b.id')
        ->where('a.status',0)->count();
    	return $count;
    }

    /*
     *   提现申请消息条数
     * 
     */    
    
    public function putMsg()
    {
        $put = DB::table('sm_apply_cash')->where('audit_status',0)->count();
        return $put;
    }
    
    /*
     *  投诉记录消息条数
     * 
     */
    
    public function comMsg()
    {
    	$com = Db::table('sm_complaint')->where('sm_status',0)->where('status',1)->count();
    	return $com;
    }
    

  


}