<?php 
namespace app\agent\validate;
use think\Validate;

class Phone extends Validate
{
	protected $rule = [
      'r_phone|手机号'	=>	'require|mobile'
    ];
}