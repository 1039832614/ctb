<?php 
namespace app\base\controller;
use app\base\controller\Shop;
use think\Db;

/**
* 维修厂公共类
*/
class Shop extends Base
{
	public function initialize()
	{
		parent::initialize();
		$this->sid=$this->ifToken();
	}
	/**
	 * 检测用户是否存在
	 */
	public function isExist()
	{
		return Db::table('cs_shop')->where('id',$this->sid)->count();
	}


	/**
	 * 获取当前用户手机号
	 */
	public function getMobile()
	{
		return Db::table('cs_shop')->where('id',$this->sid)->value('phone');
	}

	/**
	 * 店铺是否出现库存预警
	 */
	public function mateCount()
	{
		return Db::table('cs_ration')
				->where('sid',$this->sid)
				->where('stock < warning')
				->count();
	}
	/**
	 * 获取维修厂的代理商
	 */
	public function getAgent()
	{
		return Db::table('cs_shop')->where('id',$this->sid)->value('aid');
	}

	/**
	 * 绑定代理商
	 */
	public function setAgent($sid)
	{
		$county = Db::table('cs_shop_set')->where('sid',$sid)->value('county_id');
		$aids = Db::table('ca_area')->select();
		$aid = 1;
		foreach ($aids as $k => $v) {
			if($county == $v['area']){
				$aid = $v['aid'];
				break;
			}
		}
		Db::table('cs_shop')->where('id',$sid)->setField('aid',$aid);
	}
}