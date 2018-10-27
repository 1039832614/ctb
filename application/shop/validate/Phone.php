<?php 
namespace app\shop\validate;
use think\Validate;

class Phone extends Validate
{
	protected $rule = [
      'r_phone|手机号'	=>	'require|mobile'
    ];
}