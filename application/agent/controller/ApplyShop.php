<?php
namespace app\agent\controller;
use app\base\controller\Agent;
use think\Db;
/**
* 修车厂物料申请
*/
class ApplyShop extends Agent
{	
	function initialize()
	{
		parent::initialize();
	}


	/**
	 * 修理厂申请物料未审核列表
	 * @return [type] [description] 
	 */
	public function index()
	{
		$page = input('post.page');
		$data=$this->apply(0,$this->aid,$page);
		if($data){
			$this->result($data,1,'获取未审核列表成功');
		}else{
			$this->result('',0,'没有数据');
		}
	}


	/**
	 * 未审核列表详情
	 * @return [type] [description]
	 */
	public function indDetail()
	{
		$id = input('post.id');
		$data = $this->detail(0,$id);
		if($data){
			$this->result($data,1,'获取未审核列表详情成功');
		}else{
			$this->result('',0,'没有数据');
		}
	}


	/**
	 * 修理厂申请物料已审核发货列表
	 * @return [type] [description]
	 */
	public function audit()
	{	
		$page = input('post.page');
		$data=$this->apply(1,$this->aid,$page);
		if($data){
			$this->result($data,1,'获取已发货列表成功');
		}else{
			$this->result('',0,'没有数据');
		}
	}



	/**
	 * 已审核列表详情
	 * @return [type] [description]
	 */
	public function audDetail()
	{
		$id = input('post.id');
		$data = $this->detail(1,$id);
		if($data){
			$this->result($data,1,'获取已发货列表详情成功');
		}else{
			$this->result('',0,'没有数据');
		}
	}



	/**
	 * 完成发货
	 * @return [type] [description]
	 */
	public function complete()
	{
		$page = input('post.page');
		$data=$this->apply(2,$this->aid,$page);
		if($data){
			$this->result($data,1,'获取完成列表成功');
		}else{
			$this->result($data,0,'没有数据');
		}
	}



	/**
	 * 完成发货列表详情
	 * @return [type] [description]
	 */
	public function comDetail()
	{
		$id = input('post.id');
		$data = $this->detail(2,$id);

		if($data){
			$this->result($data,1,'获取已发货列表详情成功');
		}else{
			$this->result('',0,'没有数据');
		}
	}


	/**
	 * 修车厂物料申请列表
	 * @return [json] 修车厂物料列表 
	 */
	public function apply($status,$aid,$page)
	{
		$page = $page ? : 1;
		$pageSize = 10;
		$count = Db::table('cs_apply_materiel')->where(['aid'=>$aid,'audit_status'=>$status])->count();
		$rows = ceil($count / $pageSize);
		$list=Db::table('cs_apply_materiel sa')
			->join('cs_shop ss','sa.sid=ss.id')
			->where(['sa.aid'=>$aid,'sa.audit_status'=>$status])
			->field('sa.id,sa.sid,company,leader,phone,sa.create_time,over_time')
			->order('id desc')
			->page($page,$pageSize)->select();
		if($count > 0){                   
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 修车厂物料申请详情
	 * @return [json] 修车厂物料列表 
	 */
	public function detail($status,$id)
	{	
		
		$list=Db::table('cs_apply_materiel sa')
			->join('cs_shop ss','sa.sid=ss.id')
			->where('sa.id',$id)
			->field('sa.id,sa.sid,company,leader,phone,sa.create_time,sa.audit_status,if_delay,sa.audit_time')
			->find();
		$detail = Db::table('cs_apply_materiel')->where('id',$id)->json(['detail'])->find();
		return ['list'=>$list,'detail'=>$detail['detail']];
	}





	/**
	 * 物料申请确认操作
	 * @return [type] 操作失败或成功
	 */
	public function adopt()
	{	
		// 获取该订单id
		$id=input('post.id');
		$sid=input('post.sid');
		Db::startTrans();
		// 给修车厂发送短信
		$this->applyCode($sid);
		// 减少运营商库存
		$this->incRation($id,$this->aid);
		//修车厂申请 状态改为已审核
		$res = $this->status('cs_apply_materiel',$id,1);
		if($res == true){
			// 修改修车厂物料申请审核时间
			$result = Db::table('cs_apply_materiel')->where('id',$id)->setField('audit_time',time());
			if($result !== false){

				Db::commit();
				$this->result('',1,'您已确认,请尽快发货');
			}else{
				Db::rollback();
				$this->result('',0,'操作失败');
			}
			
		}else{
			Db::rollback();
			$this->result('',0,'操作失败');
		}
	}



	/**
	 * 运营商申请延迟
	 * @return [type] 
	 */
	public function delayed()
	{
		$id=input('post.id');
		$if_delay = Db::table('cs_apply_materiel')->where('id',$id)->value('if_delay');
		if($if_delay == 1){
			$this->result('',2,'您已申请过延时！');
		}
		// 点击延时over_time延迟三天
		$res=Db::table('cs_apply_materiel')->where(['id'=>$id,'aid'=>$this->aid])->setField('if_delay',1);
		if($res){
			$this->result('',1,'延时成功');
		}else{
			$this->result('',0,'延时失败');
		}
	}

	
	/**
	 * 发送货品之后给修车厂发送短信
	 * @param  [type] $sid [description]
	 * @return [type]      [description]
	 */
	private function applyCode($sid)
	{
		// 修改物料申请审核时间
		// 给修车厂发送短信
		$phone=Db::table('cs_shop')->where('id',$sid)->value('phone');
		$content="您申请的货物已发出,请注意查收";
		return $this->smsVerify($phone,$content);
	}

	/**
	 * 减少运营商库存
	 * @param  [type] $sid [description] 修车厂id
	 * @param  [type] $aid [description] 运营商id
	 * @return [type]      [description]
	 */
	public function incRation($id,$aid)
	{
		// 获取修车厂所申请的物料
		$data = Db::table('cs_apply_materiel')->where('id',$id)->json(['detail'])->find();
		foreach($data['detail'] as $k=>$v){
			$res = Db::table('ca_ration')
					->where(['materiel'=>$v['materiel_id'],'aid'=>$aid])
					->dec('materiel_stock',$v['num'])
					->update();
		}
		if($res !== false){
			return  true;
		}
		
	}
}