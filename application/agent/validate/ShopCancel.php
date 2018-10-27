<?php 
namespace app\agent\validate;

use think\Validate;

class  ShopCancel extends Validate
{
    protected $rule = [
      'reason|原因'  	=> 'require',
      'content|选择内容'	=>	'require',
      
    ];

}