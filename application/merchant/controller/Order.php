<?php 
namespace app\merchant\controller;
use app\base\controller\Merchant;
use think\Db;
use think\File;
/**
 * 订单 异常订单
 */
class Order extends Merchant
{
	/**
	 * 初始化方法
	 * @return [type] [description]
	 */
	public function initialize()
	{

	}
    /**
     * 物料申请订单列表 维修厂名称、联系方式、负责人、状态
     * @return [type] [description]
     */
	public function orderList()
	{

	}
    /**
     * 物料申请订单分类检索 维修厂名称、联系方式、负责人、状态
     * @return [type] [description]
     */
	public function searchOrder()
	{

	}
	/**
	 * 物料申请订单详情 产品名称、规格、数量、车的品牌车型排量、维修厂位置
	 * @return [type] [description]
	 */
	public function orderDetail()
	{

	}
	/**
	 * 发货 获取提交的产品编码以及物流电话、物流公司名称
	 * @return [type] [description]
	 */
	public function deliverGoods()
	{

	}
	/**
	 * 异常订单列表 车主姓名、车主电话、维修厂名、维修厂电话、订单时间、状态
	 */
	public function abnormalOrderList()
	{

	}
	/**
	 * 回收物料操作按钮
	 * @return [type] [description]
	 */
	public function callIn()
	{

	}
}