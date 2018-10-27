<?php 
namespace app\merchant\validate;

use think\Validate;

class Reg extends Validate
{
	protected $rule = [
		'login|登录账号'   => 'require|unique:cp_merchant',
		'pwd|密码'         =>  'require',
		'r_pwd|确认密码'   => 'require|confirm:pwd',
		'company|公司名称' => 'require',
		'province|省份'    => 'require',
		'city|市'          =>  'require',
		'county|区县'      => 'require',
		'address|详细地址' => 'require',
		'leader|负责人'    => 'require',
		'bank|开户行'      => 'require|length:4|number',
		'branch|开户分行'  => 'require',
		'bank_name|开户名' => 'require',
		'account|提款账号' => 'require|number',
		'phone|手机号'     => 'require|mobile|unique:cp_merchant',
		'code|验证码'      => 'require|number',
	];
	protected $message = [
		'r_pwd.confirm' => '两次密码不一致',
		'phone.unique' => '该手机号已被注册'
	];
}