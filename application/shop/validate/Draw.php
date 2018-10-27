<?php 
/**
 * 账户提现测试
 */
namespace app\shop\validate;
use think\Validate;

class Draw extends Validate
{
	protected $rule = [
      'money|提现金额' => 'require|number'
    ];


}