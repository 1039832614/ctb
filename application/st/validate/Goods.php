<?php 
namespace app\st\validate;
use think\Validate;

class Goods extends Validate
{
	protected $rule = [
		'name|产品名称' => 'require',
	];
}