<?php 
namespace app\admin\validate;

use think\Validate;

class WorkerArticleMoney extends Validate
{
    protected $rule = [
      'money'  	=> 'require|number|between:1,10',
    ];
    protected $message = [
    	'money.require' => '必须输入奖励金额',
    	'money.number'  => '必须输入数字',
    	'money.between' => '必须在1-10元间',
    ];
}