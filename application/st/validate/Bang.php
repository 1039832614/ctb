<?php 
/**
 * 做邦保养验证
 */
namespace app\shop\validate;
use think\Validate;

class Bang extends Validate
{
	protected $rule = [
      // 'plate|车牌号'  	=> 'require',
      'phone|手机号'	=>	'require|mobile'
    ];
}