<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;

/**
 * 大转盘
 */
class Giftpro extends Admin
{

    public function provinceList()
    {
        $province = $this->province();
        // return $province;
        if($province) $this->result($province,1,'获取省成功');
        $this->result('',0,'暂无数据');
    }

    public function cityL()
    {   
        $pid = input('post.pid');
        $city = $this->city($pid);
        if($city) $this->result($city,1,'获取市成功');
        $this->result('',0,'暂无数据');
    }


     /*
      *  图片上传
      */
     public function GiftFile()
     {  
        
        if(!isset($_FILES['image'])) $this->result('',0,'未找到name为image的图片');
     	return upload('image','Gift','http://localhost/cc/public/');
     }    
     /*
      *  赠品上传
      */
     
     public function UploadGift()
     {
        
        $data = input('post.');
        $validate = validate('Giftpro');
        if(!$validate->check($data)) $this->result('',0,$validate->getError());
        $res = Db::table('u_prize')->strict(false)->insert($data);
        if(!$res) $this->result('',0,'上传失败');
        // 日志写入
        $GLOBALS['err'] = $this->ifName().'上传了赠品【'.$data['name'].'】';
        $this->estruct();
        $this->result('',1,'上传成功');
     }
     
     /**
      *  赠品列表
      *  @param  page 页数
      */
     
     public function GiftShow()
     {
         $size = 8;
         $page = input('post.page')? :1;
         $count = DB::table('u_prize')->count();
         if(empty($count)) $this->result('',0,'数据为空');
         $rows = ceil($count/$size);
         $data = Db::table('u_prize')->page($page,$size)->select();
         if($data==false) $this->result('',0,'获取数据失败');
         $this->result(['rows'=>$rows,'list'=>$data],1,'获取成功');
     }

     /*
      *  赠品修改--获取默认值
      */
     
     public function GitfCheck()
     {  
        $id = input('post.id');
        if(empty($id)) $this->result('',0,'参数为空');
        $data = Db::table('u_prize')->where('id',$id)->find();
        if(!$data) $this->result('',0,'获取失败');
        $this->result($data,1,'获取成功');
     }

     /*
      *  赠品修改
      */
    
    public function GitfSave()
    {
       
    	$data = input('post.');
    	if(empty($data['id'])) $this->result('',0,'id为空');
    	$validate = validate('Giftpro');
        // 删除旧图片
        if(isset($data['imgdel']) && $data['image']) $this->imgDel($data['imgdel']);
         
    	if(!$validate->check($data)) $this->result('',0,$validate->getError());
        $res = Db::table('u_prize')->strict(false)->update($data);

        if($res === false) $this->result('',0,'修改失败');

        // 日志写入
        $GLOBALS['err'] = $this->ifName().'修改了赠品【'.$data['name'].'】';
        $this->estruct();

        $this->result('',1,'修改成功');
    }
    
     /*
      *  赠品删除
      */    
    public function GitfDel()
    {   
        $id = input('post.id');
        if(empty($id)) $this->result('',0,'参数为空');

        $url = DB::table('u_prize')->where('id',$id)->value('image');
        // 日志写入
        $this->imgDel($url); 
    	$res = Db::table('u_prize')->delete($id);
    	if(!$res) $this->result('',0,'删除失败');
        
        $name = DB::table('u_prize')->where('id',$id)->value('name');
        $GLOBALS['err'] = $this->ifName().'删除了赠品【'.$name.'】';
        $this->estruct();

    	$this->result('',1,'删除成功');
    }

     /*
      *  中奖信息添加
      */
     
     public function LuckPost()
     {
        $data = input('post.');
        $validate = validate('Luck');
        if(!$validate->check($data)) $this->result('',0,$validate->getError());
        $res = Db::table('u_winner')->strict(false)->insert($data);
        if(!$res) $this->Result('',0,'提交失败');
        
        // 日志写入
        $name = Db::table('u_prize')->where('id',$data['aid'])->value('name');  

        $GLOBALS['err'] = '小程序用户'.$data['name'].'喜中【'.$name.'】';
        $this->estruct();

        $this->result('',1,'提交成功');
     }

     /*
      *  中奖列表未发货
      */
     
     public function LuckNot()
     {
     	$this->LuckShow(0);
     }     

     /*
      *  中奖列表已发货
      */
     
     public function LuckAlear()
     {
     	$this->LuckShow(1);
     } 

     public function Export()
     {
        $area = input('post.area');
        if(empty($area)) $this->result('',0,'参数错误');
        $xlsData = Db::table('u_winner a')
               ->join('u_prize b','','a.aid = b =  = b.id')
               ->field('a.man,a.p,an,a.phone,a.a,ne,a.address,a.d,ss,a.details,a.t,ls,a.time,b.n,me,b.name')
               ->where(['a.area'=>$area,'a.status'=>1])->select();
        if(!$xlsData) $this->result('',0,'此地区数据为空');
        $xlsName  = "中奖列表";
        $xlsCell  = array(
            array('man','中奖用户姓名'),
            array('phone','手机号'),
            array('address','地区'),
            array('details','详细地区'),
            array('time','中奖时间'),
            array('name','中奖物品'),
        );  
                                                                    
        $this->exportExcel($xlsName,$xlsCell,$xlsData);
        DB::table('u_winner')->where(['area'=>$area,'status'=>1])->update(['status'=>1]);
     } 



    


   

     /*
      *  中奖列表
      *  @param $status 1未发货 2已发货
      */
     
     public function LuckShow($status)
     {  
     	$size = 8;
     	$page = input('post.page')? :1;
        $count = Db::table('u_winner')->where('status',$status)->count();
        $rows = ceil($page/$size);
        $data = Db::table('u_winner a')->where('a.status',$status)
                ->leftjoin('u_prize b','a.aid = b.id')
                ->where('a.member','=',0)
                ->page($page,$size)
                ->field('a.man,a.phone,a.address,a.details,a.status,b.name')
                ->select();
        if($data==false) $this->result('',0,'暂无数据');
        $this->result(['rows'=>$rows,'list'=>$data],1,'获取成功');
     }

    
}     