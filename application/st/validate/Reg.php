<?php 
namespace app\st\validate;
use think\Validate;

class Reg extends Validate
{
	protected $rule = [
		'usname|用户名' => 'require|min:2',
		'company|公司名称' => 'require|min:4',
		'leader|负责人' => 'require|min:2',
		'passwd|密码' => 'require|min:6',
		'spasswd|确认密码' => 'require|confirm:passwd',
		'phone|手机号' => 'require|mobile|unique:st_shop',
		'code|验证码' => 'require|number|length:4'
	];
}