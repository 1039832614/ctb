<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;

/**
* 物料管理
*/
class Mate extends Shop
{
	
	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		parent::initialize();
	}

	/**
	 * 获取物料库存
	 */
	public function remain()
	{
		$info = Db::table('cs_ration')
				->alias('r')
				->join(['co_bang_cate'=>'c'],'r.materiel = c.id')
				->field('r.ration,r.stock,c.name')
				->where('r.sid',$this->sid)
				->select();
		if($info){
			$this->result($info,1,'获取数据成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 进行物料申请  2018.8.22 孙烨兰修改
	 * 2018.08.29 18:41 xjm
	 */
	public function apply()
	{
		$count = $this->mateCount();
		if($count > 0){
			// 列出所有需要补充物料
			$info = Db::table('cs_ration')
					->alias('r')
					->join(['co_bang_cate'=>'c'],'r.materiel = c.id')
					->field('materiel as materiel_id,(ration - stock) as apply,c.name as materiel')
					->where('sid',$this->sid)
					->where('stock < ration')
					->select();
			$this->result($info,1,'需要补充的物料');
		}else{
			$this->result('',0,'当前物料充足无需补充物料');
		}
	}	

	/**
	 * 进行物料申请
	 */
	public function handle()
	{
		$data = input('post.');
		// 构建数组
		$aid = $this->getAgent();
		$arr = [
			'apply_sn' => build_only_sn(),
			'sid' => $this->sid,
			'aid' => $aid,
			'detail' => $data['detail']
		];
		// 检测是否有未处理的订单，防止重复提交  //有未完成的便不能再次申请 2018-10-8 cjx
		$count = Db::table('cs_apply_materiel')->where('sid',$this->sid)->where('audit_status','=',0)->where('audit_status','=',1)->count();
		if($count > 0){
			$this->result('',0,'您有未处理的订单，请确认！');
		}else{
			// 进行数据插入
			if(Db::table('cs_apply_materiel')->strict(false)->insert($arr)){
				$this->result('',1,'提交成功');
			}else{
				$this->result('',0,'提交失败');
			}
		}
	}

	/**
	 * 取消物料申请订单
	 */
	public function cancel()
	{
		$id = input('post.id');
		$reason = input('post.reason');
		if(trim($reason) == ''){
			$this->result('',0,'取消理由不能为空');
		}
		// 检测订单处理状态
		$status = Db::table('cs_apply_materiel')->where('id',$id)->value('audit_status');
		// 如果订单未处理，则可以取消
		if($status == 0){
			$res = Db::table('cs_apply_materiel')->where('id',$id)->update(['audit_status'=>3,'reason'=>$reason]);
			if($res !== false){
				$this->result('',1,'提交成功');
			}else{
				$this->result('',0,'提交失败');
			}
		}else{
			// 如果订单已处理，则无法进行取消
			$this->result('',0,'订单已处理，无法取消！');
		}
	}

	/**
	 * 物料申请记录
	 */
	public function log()
	{
		$page = input('post.page') ? : 1;
		// 获取每页条数
		$pageSize = 10;
		// 获取分页总条数
		$count = Db::table('cs_apply_materiel')->where('sid',$this->sid)->count();
		$rows = ceil($count / $pageSize);
		// 查询数据内容
		$list = Db::table('cs_apply_materiel')
				->field('apply_sn,create_time,audit_status,audit_time,if_delay,reason,id')
				->where('sid',$this->sid)
				->order('id desc')
				->page($page, $pageSize)
				->select();

		foreach ($list as $k => $v) {
			if(!empty($list[$k]['audit_time'])){
				$list[$k]['audit_time']=date('Y-m-d H:i:s',$list[$k]['audit_time']);
			}
		}
		// 返回给前端
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 订单详情
	 */
	public function detail()
	{
		$id = input('post.id');
		$info = Db::table('cs_apply_materiel')->where('id',$id)->field('apply_sn,create_time,FROM_UNIXTIME(audit_time) as audit_time,detail')->find();
		if($info){
			$this->result($info,1,'获取数据成功');
		}else{
			$this->result('',0,'数据异常');
		}
	}

	/**
	 * 确认收货
	 */
	public function confirm()
	{
		$id = input('post.id');
		// 检测订单处理状态
		$info = Db::table('cs_apply_materiel')->where('id',$id)->field('aid,detail,audit_status,create_time,if_delay')->find();
		// 如果订单未处理，则可以取消
		if($info['audit_status'] == 1){
			// 对运营商延迟进行罚款处理
			$create_time = strtotime($info['create_time']);
			// 如果申请了延迟则计算为2个72小时,如果未申请延迟则计算为72小时
			$end_time = ($info['if_delay']==1) ? ($create_time + 2*72*60*60) : ($create_time + 72*60*60);
			// // 如果结束时间小于当前时间，则认定为延迟  暂时取消 2018-10-8 cjx
			// if($end_time < time()){
			// // 对运营商进行罚款
			// Db::table('ca_agent')
			//   ->where('aid',$info['aid'])
			//   ->inc('fines_num')
			//   ->inc('fines',200)
			//   ->dec('balance',200)
			//   ->update();
			// //构建处罚入库信息
			// $arr = [
			// 	'aid' => $info['aid'],
			// 	'fine' => 200,
			// 	'shop_name' =>  $this->getShopName(),
			// 	'materiel_time' => $info['create_time']
			// ];
			// //新增延迟处罚数据
			// $df_inc = Db::table('ca_delayed_fines')->insert($arr);
			// // // 对汽修厂进行补偿
			// // $cs_inc = Db::table('cs_shop')->where('id',$this->sid)->setInc('balance',100);
			// // 短信通知运营商
			// $ca_mobile = Db::table('ca_agent')->where('aid',$info['aid'])->value('phone');
			// $ca_msg = "因您有货物未及时送达，您的账户被扣除200元，详情请登录系统查看维修厂物料申请。";
			// $this->sms->send_code($ca_mobile,$ca_msg);
			// // // 短信通知汽修厂
			// // $cs_mobile = Db::table('cs_shop')->where('id',$this->sid)->value('phone');
			// // $cs_msg = "因您申请的物料未及时送达，系统为您补助100元，请登录系统查看账户余额。";
			// // $this->sms->send_code($cs_mobile,$cs_msg);
			// }
			// 更改订单状态
			$change_status = Db::table('cs_apply_materiel')->where('id',$id)->update(['audit_status'=>2,'over_time'=>time()]);
			// 根据申请详情对维修厂库存进行增加
			$oils = json_decode($info['detail'],true);
			foreach ($oils as $k => $v) {
				Db::table('cs_ration')->where('sid',$this->sid)->where('materiel',$v['materiel_id'])->setInc('stock',$v['num']);
			}
			// 返回前端数据
			if($change_status !== false){
				$this->result('',1,'确认收货成功');
			}else{
				$this->result('',0,'确认收货失败');
			}
		}else{
			// 如果订单已处理，则无法进行取消
			$this->result('',0,'订单状态无效，无法确认收货！');
		}
	}
	/**
	 * 获取维修厂名称
	 * @return [type] [description]
	 */
	public function getShopName(){
		return Db::table('cs_shop')
				->where('id',$this->sid)
				->value('company');
	}
}
