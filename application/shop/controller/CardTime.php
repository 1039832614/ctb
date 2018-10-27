<?php 
namespace app\shop\controller;
use app\base\controller\Base;
use think\Db;
/**
* 每月定时30辆车奖励2000元
*/
class CardTime extends Base
{
	
	/**
	 * 每月清空维修厂售卡 定时每月一号凌晨
	 * @return [type] [description]
	 */
	public function cardMonthClear()
	{
		$shop = Db::table('cs_shop')->where('audit_status',2)->setField('card_month',0);
	}



	/**
	 * 查询完成30辆任务的维修厂，給其增加2000元
	 * @return [type] [description]
	 */
	public function cardShopBalance()
	{
		// 查询月销售大于等于30辆车的维修厂id
		$sids = Db::table('cs_shop')->where('card_month',30)->column('id');
		// 增加维修厂余额2000元
		$balance = Db::table('cs_shop')->whereIn('id',$sids)->setInc('balance',2000);
	}
}