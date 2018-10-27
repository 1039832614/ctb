<?php
namespace app\base\controller;
use app\base\controller\Base;
use think\Db;

/**
 * 惠养车
 */
class St extends base
{
	public function initialize()
	{
		parent::initialize();
		$this->sid = $this->ifToken();
	}

	/**
	 * 检测用户是否存在
	 */
	public function isExist()
	{

	}
	/**
	 * 获取当前用户手机号
	 * @return [type] [description]
	 */
	public function getMobile()
	{

	}
	/**
	 * 获取维修厂的运营商
	 * @return [type] [description]
	 */
	public function getAgent()
	{

	}
	/**
	 * 绑定运营商
	 * @param [type] $sid [description]
	 */
	public function setAgent($sid)
	{

	}
}