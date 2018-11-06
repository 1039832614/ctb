<?php 
namespace app\shop\validate;
use think\Validate;

class RegTwo extends Validate
{
	protected $rule = [
		'company|公司名称' => 'require|min:4|unique:cs_shop',
		'short_name|公司简称' => 'require|min:2|unique:cs_shop',
		'leader|负责人' => 'require' 
	];
}