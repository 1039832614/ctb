<?php 
namespace app\admin\validate;

use think\Validate;

class MessageManage extends Validate
{
	protected $rule = [
		'title|标题'    => 'require|max:30',
		'content|内容'  => 'require',
		'sendto|发送人' => 'require',
	];
}