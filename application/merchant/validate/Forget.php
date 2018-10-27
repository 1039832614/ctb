<?php 
namespace app\merchant\validate;
use think\Validate;

class Forget extends Validate
{
	protected $rule = [
      'phone|手机号'	=>	'require|mobile',
      'code|手机验证码'	=>	'require|length:4',
      'pwd|密码' => 'require|min:6',
      'r_pwd|确认密码' =>  'require|confirm:pwd'
    ];
} 