<?php 
namespace app\shop\validate;
use think\Validate;

class Info extends Validate
{
	//维修厂名称，负责人，主修，所在省市县，详细地址，服务电话，店铺简介，店铺照片，营业执照
	protected $rule = [
		'company|店铺名称' => 'require|min:4',
		'leader|负责人'    => 'require|min:2',
		'major|主修'      => 'require',
		'province|省份' => 'require',
		'city|城市' => 'require',
		'county|区县' => 'require',
		'address|详细地址' => 'require',
		'serphone|服务电话' => 'require|mobile',
		'about|店铺简介' => 'require',
		'photo|店铺照片' => 'require',
		'license|营业执照' => 'require'
	];
}