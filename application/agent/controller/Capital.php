<?php
namespace app\agent\controller;
use app\base\controller\Agent;
use think\Db;
use Msg\Sms;
/**
* 资金管理
*/
class Capital extends Agent
{
	function initialize()
	{
		parent::initialize();
		$this->income='ca_income';
	}

	/**
	 * 收入明细列表
	 * @return 列表
	 */
	public function index()
	{
		$page = input('post.page') ? : 1;
		$pageSize = 6;
		$count = Db::table('ca_income')->where('aid',$this->aid)->count();
		$rows = ceil($count / $pageSize);
		$list=Db::table('ca_income ci')
					->join('cs_shop cs','ci.sid=cs.id')
					->join('co_car_cate cc','cc.id=ci.car_cate_id')
					->join('u_user uu','ci.uid=uu.id')
					->where('ci.aid',$this->aid)
					->field('ci.id,odd_number,cs.company,cs.phone,amount,ci.create_time')
					->order('id desc')
					->page($page,$pageSize)->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 收入明细详情
	 * @return 列表
	 */
	public function detail()
	{	
		$id=input('post.id');
		return Db::table('ca_income ci')
				->join('cs_shop cs','ci.sid=cs.id')
				->join('co_car_cate cc','cc.id=ci.car_cate_id')
				->join('u_user uu','ci.uid=uu.id')
				->field('odd_number,cs.phone,amount,ci.create_time,company,name')
				->where('ci.id',$id)
				->select();
	}

	/**
	 * 提现申请页面
	 * @return [type] [description]
	 */
	public function forward()
	{	
		$arr = Db::table('ca_agent ca')
				->where('aid',$this->aid)
				->field('bank,account,bank_name,balance')
				->find();
		if($arr){
			$this->result($arr,1,'获取提现信息成功');
		}else{
			$this->result($arr,0,'获取提现信息失败');
		}
	}

	/**
	 * 运营商提现申请操作
	 * @return [json] [成功或失败]
	 */
	public function cashApply()
	{
		
		$data = input('post.');
		$check = $this->sms->compare($data['phone'],$data['code']);
		if($check !== false) {
			Db::startTrans();
			$time = Db::table('ca_apply_cash')
					->where([
						'aid' => $this->aid
					])
					->order('id desc')
					->limit(1)
					 ->value("UNIX_TIMESTAMP(create_time)");
			if(time() < $time + 1209600){
				$this->result('',0,'两次提现间隔最少为15天');
			}
			// 判断是否填写金额
			if(empty($data['money'])){
				Db::rollback();
				$this->result('',0,'提现金额不能为空');
			}
			if($data['money'] < 0) {
				Db::rollback();
				$this->result('',0,'提现金额不能小于0');
			}
			// 判断运营商余额是否充足 20180921 将1000元保留押金注释 xjm
			// $money = $this->ifMoney($data['money'],$this->aid);
			// if($data['money'] > $money){
			// 	Db::rollback();
			// 	$this->result('',0,'您本次提现最多为'.$money);
			// }
			// 减少运营商总金额
			$result = Db::table('ca_agent')->where('aid',$this->aid)->dec('balance',$data['money'])->update();
			if($result !== false){
				$data['odd_number']=build_order_sn();
				$data['aid']=$this->aid;
				// 获取运营商现有余额
				$data['sur_amount'] = Db::table('ca_agent')->where('aid',$this->aid)->value('balance');
				// 提现申请插入数据库
				$res=Db::table('ca_apply_cash')->strict(false)->insert($data);
				if($res){
					Db::commit();
					$this->result('',1,'申请成功,请等待审核');
				}else{
					Db::rollback();
					$this->result('',0,'申请成功,请等待审核');
				}
			}else{
				Db::rollback();
				$this->result('',0,'减少运营商余额失败');
			}
		} else {
			$this->result('',0,'手机验证码无效或已过期');
		}
	}


	/**
	 * 提现明细列表
	 * @return [json]
	 */
	public function cash()
	{	
		$page = input('post.page');
		$pageSize = 6;
		$count = Db::table('ca_apply_cash')->where('aid',$this->aid)->count();
		$rows = ceil($count / $pageSize);
		
		$list = Db::table('ca_apply_cash')
				->where('aid',$this->aid)
				->field('odd_number,account,money,create_time,audit_time,audit_status')
				->order('id desc')->page($page,$pageSize)->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 判断运营商申请取现的金额是否大于现有金额
	 * @param  [type] $money [所取现金额]
	 * @param  [type] $aid   [运营商id]
	 * @return [type]        [布尔值]
	 */
	public function ifMoney($money,$aid)
	{
		// 获取运营商现有所有收入
		$balance=Db::table('ca_agent')->where('aid',$aid)->value('balance');
		$money = $balance - 1000 > 0 ?$balance-1000: 0 ;
		return $money;
	}
	/**
	 * 2018/8/6 徐佳孟新增
	 * 首页资金管理图标上通过次数
	 * @return [type] [description]
	 */
	public function passNum()
	{
		$count = Db::table('ca_apply_cash')
					->where('aid',$this->aid)
					->where('audit_status',1)
					->count();
		if($count > 0) {
			$this->result($count,1,'获取数据成功');
		} else {
			$this->result('',0,'暂无数据');
		}
	}
	/**
	 * 
	 * 2018/8/6 徐佳孟新增
	 * 首页资金管理图标上驳回次数
	 * @return [type] [description]
	 */
	public function rejectNum()
	{
		$count = Db::table('ca_apply_cash')
					->where('aid',$this->aid)
					->where('audit_status',2)
					->count();
		if($count > 0) {
			$this->result($count,1,'获取数据成功');
		} else {
			$this->result('',0,'暂无数据');
		}
	}
	/**
     * 发送短信验证码
     */
    public function getCode()
    {
        $mobile = Db::table('ca_agent')
                    ->where('aid',$this->aid)
                    ->value('phone');
        $code = $this->apiVerify();
        $content = "您本次提现的验证码是【{$code}】，请不要泄露个任何人。";
        $res = $this->sms->send_code($mobile,$content,$code);
        $this->result('',1,'验证码已发送');
    }
}