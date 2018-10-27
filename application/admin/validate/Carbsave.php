<?php
namespace app\admin\validate; 

use think\Validate;

class Carbsave extends Validate
{
    protected $rule =   [
        'id'  => 'require',
        'key'   => 'require',
        'value' => 'require'

    ];
    
    protected $message  =   [
        'id.require' => 'id必须',
        'key.require' => 'key为空',
        'value.require'   => 'value为空'
  
    ];
    
}