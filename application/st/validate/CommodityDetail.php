<?php 
namespace app\st\validate;
use think\Validate;

class CommodityDetail extends Validate
{
	protected $rule = [
		'standard|规格' => 'require',
		'standard_detail|规格详情' => 'require',
		'sell_number|已售出数量' => 'number',
		'stock_number|库存数量' => 'require|number',
		'market_price|市场价' => 'require|float',
		'activity_price|活动价' => 'require|float' 
	];

	protected $message = [
		'market_price.float' => '市场价格请精确到小数点',
		'activity_price.float' => '活动价格请精确到小数点'
	];
}