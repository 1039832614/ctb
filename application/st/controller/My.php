<?php 
namespace app\st\controller;
use app\base\controller\St;
use think\Db;
use MAP\Map;

/**
 * 产品管理
 */
class My extends St
{
	/*
	 * 进程初始化
	 */
	public function initialize()
	{
		parent::initialize();
	}

	/*
	 * 获取维修厂名称
	 */
	public function shop_name()
    {
        $ifsigning = input('post.ifsigning');   // 是否签约   0  未签约  1  已签约
        if($ifsigning == 0){
            $shop_name = Db::table('st_shop')
                ->where('id',$this->sid)
                ->value('company');
        }else{
            $shop_name = Db::table('cs_shop')
                ->where('id',$this->sid)
                ->value('company');
        }
        $this->result($shop_name,1,'店铺名称返回成功');
    }

	/*
	 *店铺信息(在本新系统登陆注册的店铺才会显示)
	 */
	public function shopInfo()
    {
        $info = Db::table('st_shop')
            ->alias('a')
            ->join('st_shop_set b','a.id=b.sid','LEFT')
            ->where('a.id',$this->sid)
            ->field('a.company,a.usname,a.phone,b.detail,b.province,b.city,b.county,b.address,b.photo')
            ->find();
        $info['photo'] = json_decode($info['photo']);
        if ($info){
            $this->result($info,1,'店铺信息返回成功');
        }else{
            $this->result('',1,'店铺信息返回失败');
        }
    }

    /*
     *修改密码
     */
    public function changePass()
    {
        $data = input('post.');
        $info = Db::table('st_shop')->where('id',$this->sid)->find();  //查找该用户的信息
        if ($info['passwd'] == get_encrypt($data['oldPass'])){
            if ($data['newPass'] == $data['rePass']){
                $res = Db::table('st_shop')
                    ->where('id',$this->sid)
                    ->setField('passwd',get_encrypt($data['newPass']));
                if ($res){
                    $this->result('',1,'密码修改成功,请重新登录');
                }else{
                    $this->result('',0,'密码修改失败');
                }
            }else{
                $this->result('',0,'两次输入的密码不一致');
            }
        }else{
            $this->result('',0,'请输入正确的原密码');
        }
    }

    /*
     *完善店铺信息
     */
    public function wellShop()
    {
        $data = input('post.');   //完善的信息
        $validate = validate('Wellshop');
        if ($validate->check($data)){
            $count = Db::table('st_shop_set')->where('sid',$this->sid)->count();  //判断是否已经完善
            $address = $data['province'].$data['city'].$data['county'].$data['address'];   //根据地址获取经纬度
//            $arr = explode(',',$data['photo']);
            $data['photo'] = json_encode($data['photo']);
            $data['sid'] = $this->sid;
            unset($data['ifsigning']);
            //获取经纬度
            $maps = new Map;
            $data['lng'] =  $maps->maps($address)['lng'];
            $data['lat'] =  $maps->maps($address)['lat'];
            // 检测countyid是否更新
            if($data['county_id'] == ''){
                unset($data['county_id']);
            }
            if ($count > 0){
                unset($data['token']);
                $save = Db::table('st_shop_set')->where('sid',$this->sid)->update($data);
                if($save !== false){
                    $this->setAgent($this->sid);
                    $this->result('',1,'店铺完善信息成功');
                }else{
                    $this->result('',0,'店铺完善信息失败');
                }
            }else{
                $ree = Db::table('st_shop_set')->strict(false)->insert($data);

                //将st_shop表中的完善信息状态改为  1  已完善
                $res = Db::table('st_shop')->where('id',$this->sid)->setField('iswell',1);
                if ($ree && $res){
                    $this->setAgent($this->sid);    //查找该地区的运营商ID
                    $this->result('',1,'店铺完善信息成功');
                }else{
                    $this->result('',0,'店铺完善信息失败');
                }
            }
        }else{
            $this->result('',0,$validate->getError());
        }
    }

    /**
     * 上传图片
     */
    public function uploadImg()
    {
        return upload('file','st/photo','https://cc.ctbls.com');
    }
}