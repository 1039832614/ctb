<?php 
namespace app\st\validate;
use think\Validate;

class Wellshop extends Validate
{
	protected $rule = [
		'province|省' => 'require',
		'city|市' => 'require',
		'county|县区' => 'require',
		'address|详细地址' => 'require',
		'photo|店铺图片' => 'require',
		'detail|店铺详情' => 'require',
		'serphone|店铺电话' => 'require|mobile'
	];
}