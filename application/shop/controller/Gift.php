<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;
/**
* 礼品兑换
*/
class Gift extends Shop
{
	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		parent::initialize();
	}

	/**
	 * 兑换列表 20180829 09:04 xjm gcate
	 */
	public function index()
	{
		$page = input('post.page') ? : 1;
		// 获取每页条数
		$pageSize = 10;
		// 获取分页总条数
		$count = Db::table('cs_gift')->where('sid',$this->sid)->where('status',2)->count();
		$rows = ceil($count / $pageSize);
		// 获取数据
		$list = Db::table('cs_gift')
				->alias('g')
				->join(['u_card'=>'c'],'g.cid = c.id')
				->field('excode,g.card_number,gcate,gift_name,gid,ex_time')
				->where('status',2)
				->where('gcate',1)
				->where('g.sid',$this->sid)
				->order('g.id desc')
				->page($page, $pageSize)
				->select();
		// 返回给前端
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 进行兑换 
	 */
	public function handle()
	{
		$excode = input('post.excode');

		//在哪个店铺兑换都可以。20180901 修订。
		// 检测兑换码是否有效
		$res = Db::table('cs_gift')
					 ->field('cid,gcate,gid')
					 ->where('excode',$excode)
					 ->where('status',1)
					 ->find();
		// 如果符合兑换则进行兑换
		if($res){
			// 开启事务
			Db::startTrans();
			// 检测库存是否充足
			$gs = Db::table('cs_ration')
					->where('sid',$this->sid)
					->where('materiel',$res['gid'])
					->value('stock');
			if($gs <= 0) $this->result('',0,'该赠品库存不足，请进行补货后再进行兑换');
			// 如果充足则减少库存
			$se = Db::table('cs_ration')
					->where('sid',$this->sid)
					->where('materiel',$res['gid'])
					->setDec('stock',1);
			// 兑换码失效，兑换信息改变
			$ex = Db::table('cs_gift')
					->where('excode',$excode)
					->update(['ex_time'=>time(),'status'=>2]);
			// 获取礼品名称
			$gift = Db::table('cs_gift')
					->where('excode',$excode)
					->value('gift_name');
			// 进行事务处理
			if($se !== false && $ex !== false){
				Db::commit();
				$this->result('',1,'成功兑换，礼品为'.$gift);
			}else{
				Db::rollback();
				$this->result('',0,'兑换失败');
			}
		}else{
			$this->result('',0,'兑换码不符合兑换条件');
		}
	}
}