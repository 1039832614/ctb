<?php 
namespace app\admin\validate;

use think\Validate;

class SystemSet extends Validate
{
	protected $rule = [
		'money' => 'number',
	];
	protected $message = [
		'money.number' => '金额必须是数字',
	];
}