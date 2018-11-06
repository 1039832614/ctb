<?php 
namespace app\shop\validate;
use think\Validate;

class RegThird extends Validate
{
	//开户名，提现卡号，开户行，开户分行
	protected $rule = [
		'account_name|开户名'  => 'require',
		'account|提现卡号'     => 'require',
		'bank|开户行'          => 'require',
		'branch|开户分行'      => 'require' 
	];
}