<?php 
namespace app\supply\controller;
use app\base\controller\supply;
use think\File;
use think\Db;

/**
*  取消合作
*/
class Dissolution extends supply{

    /**
	 * 获取取消合作列表
	 * @return json
	 */
     
    public function Dissolution($status,$page)
    {
        
       $size = 10;  
       $max = Db::table('cg_supply_triall a')
              ->join('ca_agent c','a.yid = c.aid')
              ->where(['a.gid'=>$this->gid,'a.status'=>$status])
              ->count();
        if (!$max) {
        	$this->result('',0,'数据为空');
        }
        $wei = ceil($max/$size);
        
        $data = Db::table('cg_supply_triall a')
              ->join('ca_agent c','a.yid = c.aid')
              ->where(['a.gid'=>$this->gid,'a.status'=>$status])
              ->page($page,$size)
              ->field('c.aid,c.company,c.leader,c.phone,a.create_time,c.service_time,a.reject_time,a.cancel')
              ->select();
             
        $this->result(['max'=>$wei,'list'=>$data],1,'获取成功');

    }
    

  /**
	 * 获取取消合作列表
	 * @return json
	 */
	public function Dissolution_false()
	{   
		$page = input('post.page')? :1;
	
		$this->Dissolution(3,$page);
	}

    /**
	 * 确认取消
	 * @return json
	 */
    public function false_submit()
    {
       $yid = input('post.aid');
       if(!$yid) $this->result('0',0,'参数为空');
       $status = Db::table('cg_supply_triall')->where(['gid'=>$this->gid,'yid'=>$yid])->value('status');
       if($status==4) $this->result('',0,'已取消合作');
       $res = Db::table('cg_supply_triall')->where(['gid'=>$this->gid,'yid'=>$yid])->update(['status'=>4,'reject_time'=>date("Y-m-d H:i:s",time())]);

       if($res){
       	  $this->result('',1,'取消合作成功');
       }else{
       	 $this->result('',0,'取消合作失败');
       }
           
    }


    /**
	 * 取消合作成功列表
	 * @return json
	 */
	public function Dissolution_true()
	{   
		$page = input('post.page')? :1;
		$this->Dissolution(4,$page);
	}
       
 
    /**
	 * 取消合作成功详情
	 * @return json
	 */    

    // public function 




}