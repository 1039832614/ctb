<?php 
namespace app\st\validate;

use think\Validate;

class Login extends Validate
{
	protected $rule = [
		'usname|用户名' => 'require',
		'passwd|密码'   => 'require'
	];
}
