<?php 
namespace app\shop\validate;
use think\Validate;

/**
 * 验证手机号以及验证码
 * 新版本使用
 */
class Phone extends Validate
{
	protected $rule = [
      'r_phone|手机号'	=>	'require|mobile',
      'code|验证码' => 'require'
    ];
}