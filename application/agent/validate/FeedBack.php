<?php 
namespace app\agent\validate;

use think\Validate;

class FeedBack extends Validate
{
    protected $rule = [
      'title|反馈标题'  	=> 'require',
      'content|反馈内容'	=>	'require',
    ];

}
