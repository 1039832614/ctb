<?php 
namespace app\merchant\validate;

use think\Validate;
/**
 * 登录验证
 */
class Login extends Validate
{
	protected $rule = [
		'login|登录账号' => 'require',
		'pwd|密码'      => 'require',
	]; 
	protected $message = [
		'code.confirm' => '验证码错误',
	];
}