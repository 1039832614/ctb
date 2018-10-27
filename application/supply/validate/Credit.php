<?php 
namespace app\supply\validate;

use think\Validate;

class Credit extends Validate
{
    protected $rule = [
      'name|物料名称'	=>	'require',
      'size|数量'	=>	'require',
      'remarks|备注'	=>	'require',
    ];


}