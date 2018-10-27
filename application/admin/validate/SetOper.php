<?php 
namespace app\admin\validate;

use think\Validate;

class SetOper extends Validate
{
    protected $rule = [
      'cover|油品图片' => 'require',
      'price|油品价钱' => 'require',
      'about|油品简介' => 'require',
    ];
}