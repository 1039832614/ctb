<?php 
namespace app\admin\validate;

use think\Validate;

class Manage extends Validate
{
    protected $rule = [
      'phone|手机号'  	=> 'require|mobile',
      'uname|用户名'	=>	'require',
    ];

}