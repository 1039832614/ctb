<?php
namespace app\shop\validate;
use think\Validate;

class Commodity extends Validate
{
	protected $rule = [
		'name|商品名称' => 'require',
		'pic|商品图片'  => 'require',
		'detail|图文详情' => 'require'
	];

}