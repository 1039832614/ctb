<?php 
namespace app\admin\validate;

use think\Validate;

class Giftpro extends Validate
{
    protected $rule = [
      'name|产品名称'  	=> 'require',
      'content|产品描述'	=>	'require',
      'image|产品图片'	=>	'require',
      'price|产品价格'	=>	'require',
      'prob|产品概率'	=>	'require',
      'draw|领取方式'	=>	'require',
      
    ];
 

}