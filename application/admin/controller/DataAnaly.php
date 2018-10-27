<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 数据分析首页
*/
class DataAnaly extends Admin
{
	/**
	 * 邦保养关注度
	 * @return [type] [description]
	 */
	public function maint()
	{
		// 查询u_user表  获取关注邦保养小程序的人数
		$count = Db::table('u_user')->count();
		if($count > 0){
			$this->result($count,1,'获取关注度成功！');
		}else{
			$this->result(0,0,'暂无关注！');
		}
	}


	/**
	 * 邦保养参与度
	 * @var string
	 */
	public function partDegree()
	{
		// 查询u_card 获取用户购卡信息
		$count = $this->card();
		if($count > 0){
			$this->result($count,1,'获取参与度成功！');
		}else{
			$this->result(0,0,'暂无参与度！');
		}
	}

	/**
	 * 市级代理全国总数量
	 * @return [type] [description]
	 */
	public function agent()
	{
		// 查询cg_supply（供应商表）的总条数
		$count = Db::table('cg_supply')->count();
		if($count > 0){
			$this->result($count,1,'获取市级代理数量成功！');
		}else{
			$this->result(0,0,'暂无市级代理！');
		}
	}

	/**
	 * 运营商全国总数量
	 * @return [type] [description]
	 */
	public function operator()
	{
		// 查询ca_agent(运营商表) 已支付系统使用费的总条数
		$count = Db::table('ca_agent')->where('status',2)->count();
		if($count > 0){
			$this->result($count,1,'获取运营商数量成功！');
		}else{
			$this->result(0,0,'暂无运营商');
		}
	}


	/**
	 * 维修厂全国总数量
	 * @return [type] [description]
	 */
	public function shop()
	{
		// 查询 cs_shop(维修厂表) 已支付系统使用费的总条数
		$count = $this->shopNum();
		if($count > 0){
			$this->result($count,1,'获取维修厂数量成功！');
		}else{
			$this->result(0,0,'暂无维修厂');
		}
	}

	/**
	 * 车主全国总数量  （购卡成功）
	 * @return [type] [description]
	 */
	public function userCard()
	{
		// 查询购卡成功的车主数量
		$count = Db::table('u_card')->where('pay_status',1)->group('uid')->count();
		if($count > 0){
			$this->result($count,1,'获取车主数量成功');
		}else{
			$this->result(0,0,'暂无车主购买邦保养卡');
		}

	}

	/**
	 * 获取邦保养服务次数
	 * @return [type] [description]
	 */
	public function serviceNum()
	{
		// 查询维修厂服务次数 查询cs_income表（维修厂收入表）
		$count = Db::table('cs_income')->count();
		if($count > 0){
			$this->result($count,1,'获取服务次数成功');
		}else{
			$this->result(0,0,'暂无服务');
		}
	}

	/**
	 * 交易总金额
	 * @return [type] [description]
	 */
	public function cardPrice()
	{
		$price = $this->price();
		if($price > 0){
			$this->result($price,1,'获取交易总金额成功');
		}else{
			$this->result(0,0,'暂无交易');
		}

	}

	/**
	 * 参与平均数
	 * @return [type] [description]
	 */
	public function partAvera()
	{
		//获取全国的售卡量
		$card = $this->card();
		// 获取全国维修厂数量
		$shop = $this->shopNum();
		$avera = ceil($card / $shop);
		if($avera){
			$this->result($avera,1,'获取参与平均值成功！');
		}else{
			$this->result(0,0,'暂无数据！');
		}

	}

	/**
	 * 交易金额平均值
	 * @return [type] [description]
	 */
	public function priceAvera()
	{
		// 获取全国交易总额 
		$price = $this->price();
		// 获取维修厂数量
		$shop = $this->shopNum();
		$avera = ceil($price / $shop);
		if($avera){
			$this->result($avera,1,'获取交易金额平均值成功！');
		}else{
			$this->result(0,0,'暂无数据！');
		}
	}

	/**
	 * 全国售卡总量
	 * @return [type] [description]
	 */
	private function card()
	{
		return Db::table('u_card')->where('pay_status',1)->count();
	}

	/**
	 * 全国维修厂数量
	 * @return [type] [description]
	 */
	private function shopNum()
	{
		return Db::table('cs_shop')->where('audit_status','>=',0)->where('audit_status','<>',6)->count();
	}

	/**
	 * 全国交易总额
	 * @return [type] [description]
	 */
	private function price()
	{
		return Db::table('u_card')->sum('card_price');
	}





}