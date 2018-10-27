<?php 
/**
 * 汽修厂服务验证
 */
namespace app\shop\validate;
use think\Validate;

class Service extends Validate
{
	protected $rule = [
      'service|服务名称'  	=> 'require|min:2',
      'cover|配图'	=>	'require',
      'price|价值' => 'require|number',
      'about|介绍' => 'require'
    ];
}