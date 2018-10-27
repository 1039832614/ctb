<?php 
namespace app\agent\validate;

use think\Validate;

class Forget extends Validate
{
    protected $rule = [
      'phone|手机号'  	=> 'require|mobile',
      'code|验证码'	=>	'require|length:4',
      'pass|密码'	=>	'require',
      'qpass|确认密码' =>  'require|confirm:pass',
    ];

}