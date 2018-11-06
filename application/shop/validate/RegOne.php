<?php 
namespace app\shop\validate;
use think\Validate;

class RegOne extends Validate
{
	protected $rule = [
		'usname|账号' => 'require|min:2|unique:cs_shop',
		'passwd|密码' => 'require|min:6',
		'spasswd|确认密码' => 'require|min:6|confirm:passwd',
		'phone|手机号' => 'require|mobile',
		'code|验证码' => 'require|number|length:4' 
	];
	protected $message = [
		'spasswd.confirm' => '两次密码请输入一致'
	];
}