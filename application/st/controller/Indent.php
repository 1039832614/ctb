<?php 
namespace app\st\controller;
use app\base\controller\St;
use think\Db;

/**
 * 预约单管理
 */
class Indent extends St
{
	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		parent::initialize();
	}
	/**
	 * 预约单列表
	 * @param  [type] $status [description]
	 * @param  [type] $page   [description]
	 * @return [type]         [description]
	 */
	public function list($status,$page)
	{

	}
	/**
	 * 预约列表（未发生）
	 * @return [type] [description]
	 */
	public function unhappen()
	{

	}
	/**
	 * 已完成列表
	 * @return [type] [description]
	 */
	public function happen()
	{

	}
	/**
	 * 预约超时列表
	 * @return [type] [description]
	 */
	public function timeout()
	{

	}
	/**
	 * 预约单完成操作
	 * @return [type] [description]
	 */
	public function handle()
	{

	}
	/**
	 * 预约单放弃操作
	 * @return [type] [description]
	 */
	public function giveUp()
	{

	}
}