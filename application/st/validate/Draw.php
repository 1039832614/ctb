<?php 
/**
 * 账户提现测试
 */
namespace app\shop\validate;
use think\Validate;

class Draw extends Validate
{
	protected $rule = [
      'money|提现金额' => 'require|number|isInt:1'
    ];

    /**
     * 提现金额必须为100的整数倍
     */
    protected function isInt($value){
    	return is_int($value/100) ? true : '提现金额必须是100整数倍';
    }

}