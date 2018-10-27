<?php 
namespace app\supply\controller;
use app\base\controller\supply;
use think\File;
use think\Db;
use Msg\Msg;
/**
*  消息管理 --
*/
class Message extends supply{
   
   function initialize(){
		parent::initialize();		
		$this->coMsg=new Msg();

	}
  

   /*
    * 插入总后台消息
    */

   public function remind()
   {
 
     $this->coMsg->getUrMsg(8,'cg_msg',$this->gid);

   }

   
	/**
	 * 获取全部消息
	 * @return json
	 */
    
    public function  MsgAll()
    {   
    	$page = input('post.page')? input('post.page'):1;
        $data = $this->coMsg->msgList('cg_msg',$this->gid,$page,999);

         $this->isNull($data['list']);
         $this->Msgchange($data);
    }

    /**
	 * 获取已读消息
	 * @return json
	 */
    
    public function  MsgRead()
    {   
    	$page = input('post.page')? :1;
        $data = $this->coMsg->msgLists('cg_msg',$this->gid,1,$page);
        $this->isNull($data['list']);
        $this->Msgchange($data);
    }

    /**
	 * 获取未读消息
	 * @return json
	 */
    
    public function  MsgUnread()
    {   
    	$page = input('post.page')? 1:1;
        $data = $this->coMsg->msgLists('cg_msg',$this->gid,0,$page);
        $this->isNull($data['list']);
        $this->Msgchange($data);
    }


    /**
	 * 获取消息详情
	 * @return json
	 */
    
    public function MsgDetails()
    {   
    	$mid = input('post.mid');
    	if($mid){
    	    $res = $this->coMsg->msgDetail('cg_msg',$mid,$this->gid,8);
    	    if($res){
    	    	$this->result($res,1,'获取成功');
    	    }else{
    	    	$this->result('',0,'获取失败');
    	    }
        }else{
        	$this->result('',0,'参数错误');
        }
    }
    

    /**
     *  获取未读消息次数
     */
        public function  MsgCount()
    {   
        $count = Db::table('cg_msg')->where(['uid'=>$this->gid,'status'=>0])->count();
        $this->result($count,1,'获取未读总条数成功');
    }
   
    /**
     *  获取未读消息次数
     */
    
    public function Msgchange($data)
    {
        foreach ($data['list'] as $k => $v) {
            // 转换成时间戳
            $time = strtotime($v['create_time']);
            $data['list'][$k]['number'] = Date('d',$time);
            $data['list'][$k]['date']   = Date('Y-m',$time);
            $data['list'][$k]['time']       = Date('H:i:s',$time);
        }
        $this->result($data,1,'获取成功');
    }

}