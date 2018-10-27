<?php 
namespace app\admin\validate;

use think\Validate;

class Auth extends Validate
{
    protected $rule = [
      'action|方法'  	=> 'require',
      'name|权限名称'	=>	'require',
    ];

}