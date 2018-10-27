<?php 
namespace app\supply\validate;

use think\Validate;

class Login extends Validate
{
    protected $rule = [
      'login|登录账号'  	=> 'require',
      'pass|密码'	=>	'require',
      // 'verify|验证码'	=>	'require|length:4|confirm:verify',
    ];
    // protected $message=[
    // 	'code.confirm'		=>'验证码错误',
    // ];

}