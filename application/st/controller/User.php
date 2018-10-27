<?php 
namespace app\st\controller;
use app\base\controller\St;
use think\Db;
/**
 * 
 */
class User extends St
{
	public function initialize()
	{
		parent::initialize();
	}
	/**
	 * 获取店铺信息
	 */
	public function getInfo()
	{
		$data = input();
	}
	/**
	 * 完善店铺信息
	 * @return [type] [description]
	 */
	public function insertInfo()
	{

	}
	/**
	 * 修改店铺信息
	 * @return [type] [description]
	 */
	public function alterInfo()
	{

	}
	/**
	 * 修改密码
	 * @return [type] [description]
	 */
	public function doPasswd()
	{

	}
}