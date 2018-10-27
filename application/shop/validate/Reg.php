<?php 
/**
 * 注册验证
 */
namespace app\shop\validate;
use think\Validate;

class Reg extends Validate
{
	protected $rule = [
      'usname|用户名'  	=> 'require|min:2|unique:cs_shop',
      'company|公司名称'	=>	'require|min:4',
      'short_name|公司简称' => 'require|min:2|max:5',
      'leader|负责人'  	=> 'require|min:2',
      'passwd|密码' => 'require|min:6',
      'spasswd|确认密码' =>  'require|confirm:passwd',
      'phone|手机号'	=>	'require|mobile|unique:cs_shop',
      'code|验证码' => 'require|number|length:4'
    ];
}