<?php 
namespace app\admin\validate;

use think\Validate;

class Luck extends Validate
{
    protected $rule = [
      'aid|产品id'  	=> 'require',
      'uid|用户id'	=>	'require',
      'address|收货地址'	=>	'require',
      'details|详细地址'	=>	'require',
      'man|收货人'	=>	'require',
      'phone|收货人电话'	=>	'require',
      'area|区域id' =>'require'
    ];
    

}