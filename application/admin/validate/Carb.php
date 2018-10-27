<?php
namespace app\admin\validate; 

use think\Validate;

class Carb extends Validate
{
    protected $rule =   [
        'brand'   => 'require',
        'type'  => 'require|unique:co_car_cate',
        'series'   => 'require',
        'oil' => 'require',  
        'litre' => 'require',
        'month' => 'require',
        'km' =>'require',
        'price' => 'require',
        'filter' =>'require'  
    ];
    
    protected $message  =   [
        'brand.require' => 'brand为空',
        'type.require' => '车型不能为空',
        'type.unique' => '该车型已存在',
        'series.require'   => '排量不能为空',
        'oil.require'   => '用油不能为空',
        'litre.require'   => '升数不能为空',
        'month.require'   => '保养周期不能为空',
        'km.require'   => '保养公里数不能为空',
        'price.require'   => '价格不能为空',
        'filter.require'   => '滤芯补贴不能为空'   
    ];
    
}