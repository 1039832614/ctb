<?php 
namespace app\supply\validate;

use think\Validate;

class Reg extends Validate
{
    protected $rule = [
      'login|登录账号'  	=> 'require|unique:cg_supply',
      'pass|密码'   	    => 'require',
      'qpass|确认密码'  	=> 'require|confirm:pass',
      'company|公司名称' 	=> 'require|unique:cg_supply',
      'province|省份'     => 'require',
      'city|市'	          => 'require',
      // 'county|区县'	      => 'require',
      'address|详细地址'	=> 'require',
      'leader|负责人'	    => 'require',
      'bank|开户行'	      => 'require',
      'branch|开户分行'	  => 'require',
      'bank_name|开户名'  => 'require',
      'account|提款账号'	=> 'require|number',
      'phone|手机号'	    => 'require|mobile|unique:cg_supply',
      'code|验证码'       => 'require|number',
      'simple|公司简称'   => 'require|max:25'
    ];
    protected $message=[
    	'qpass.confirm'		=>'两次密码不一致',
    ];
}