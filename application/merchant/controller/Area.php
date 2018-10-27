<?php 
namespace app\merchant\controller;
use app\base\controller\Merchant;
use think\Db;

/**
 * 供应地区 
 */
class Area extends Merchant
{
	/**
	 * 初始化方法
	 * @return [type] [description]
	 */
	public function initialize()
	{

	}
	/**
	 * 列表总方法 供应项，类型，区域，申请时间，审核状态
	 * @param  [type] $page   [当前页]
	 * @param  [type] $status [状态]
	 * @return [type]         [description]
	 */
	public function list($page,$status)
	{

	} 
	/**
	 * 未审核列表
	 * @return [type] [description]
	 */
	public function unAuditList()
	{

	}
	/**
	 * 已审核列表，即已通过列表
	 * @return [type] [description]
	 */
	public function auditList()
	{

	}

	/**
	 * 已驳回列表
	 * @return [type] [description]
	 */
	public function rejectList()
	{

	}
	/**
	 * 详情 供应项 已选区域 营业执照，产品图文介绍，检测报告，保险公司承包单
	 * @return [type] [description]
	 */
	public function getDetail(){

	}
}