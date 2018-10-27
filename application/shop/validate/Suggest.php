<?php 
/**
 * 汽修厂意见反馈检测
 */
namespace app\shop\validate;
use think\Validate;

class Suggest extends Validate
{
	protected $rule = [
      'title|标题'  	=> 'require',
      'content|内容'	=>	'require'
    ];
}