<?php 
namespace app\agent\validate;

use think\Validate;

class  ModifyPass extends Validate
{
    protected $rule = [
      'pass|原密码'  	=> 'require',
      'npass|新密码'	=>	'require|confirm:qpass',
      'qpass|确认密码'	=>	'require',
      'phone|手机号'	=>	'require|mobile',
      'code|验证码'		=>	'require|length:4',
    ];
    protected $message=[
    	'npass.confirm'		=>'两次密码不一致',
    ];

}