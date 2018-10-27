<?php 
namespace app\st\controller;
use app\base\controller\St;
use think\Db;

/**
 * 产品管理
 */
class Goods extends St
{
	/*
	 * 进程初始化
	 */
	public function initialize()
	{
		parent::initialize();
	}
	/**
	 * 商品图片上传
	 */
    public function uploadPic()
    {
        return upload('pic','st/photo','https://cc.ctbls.com/');
    }

	/**
	 * 获取商品规格
	 * @return [type] [description]
	 */
	public function standard()
	{
		$list = Db::table('st_commodity_standard')
				->select();
		if($list) {
			$this->result($list,1,'获取数据成功');
		} else {
			$this->result('',0,'暂无数据');
		}
	}

	/*
	 *判断是否完善店铺信息
	 */
	public function iswell()
    {
        $ifsigning = input('post.ifsigning');  // 是否签约 0  未签约  1  已签约
        if ($ifsigning == 0){
            $info = Db::table('st_shop')->field('iswell')->where('id',$this->sid)->find();
            if ($info['iswell'] != 0){
                $this->result('',0,'已经完善店铺信息');
            }else{
                $this->result('',1,'请先完善店铺信息');
            }
        }elseif ($ifsigning == 1){
            $info = Db::table('cs_shop_set')->field('id')->where('sid',$this->sid)->count();
            if ($info > 0){
                $this->result('',0,'已经完善店铺信息');
            }else{
                $this->result('',1,'请先完善店铺信息');
            }
        }
    }

	/**
	 * 产品上传
	 */
	public function addGoods()
	{
        //产品信息
        $data = input('post.');
        $time = time();
        $data['pnum'] = time().rand(10000,99999);
        $data['sid'] = $this->sid;
//        $data['detail'] = strip_tags($data['detail']);
        Db::startTrans();
        $cid = Db::table('st_commodity')
            ->strict(false)
            ->insertGetId($data);
//        $stan = json_decode($data['standard'],true);
//        unset($data['standard']);
        $stan = $data['standard'];
        foreach ($stan as $key => $value) {     //将其余信息加入到产品详情表中
            $arr = [
                'cid' => $cid,
                'standard' => $stan[$key]['standard'],
                'standard_detail' => $stan[$key]['standard_detail'],
                'sell_number' => $stan[$key]['sell_number'],
                'stock_number' => $stan[$key]['stock_number'],
                'market_price' => $stan[$key]['market_price'],
                'activity_price' => $stan[$key]['activity_price'],
                'create_time' => $time
            ];
            $res[] = Db::table('st_commodity_detail')->insert($arr);
        }
        unset($data['standard']);
        $as = !(in_array(0,$res));//判断产品是否加入成功
        if($cid && $as) {
            Db::commit();
            $this->result('',1,'添加产品成功');
        } else {
            Db::rollback();
            $this->result('',0,'添加产品失败');
        }
	}

	/*
	 *搜索
	 */
	public function search($key,$value,$page,$ifsigning)
    {
        $pageSize = 8;
        $count = Db::table('st_commodity')
            ->where($key,'like','%'.$value.'%')
            ->where(['sid'=>$this->sid,'ifsigning'=>$ifsigning])
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('st_commodity')
            ->where($key,'like','%'.$value.'%')
            ->where(['sid'=>$this->sid,'ifsigning'=>$ifsigning])
            ->order('create_time desc')
            ->page($page,$pageSize)
            ->field('id,pnum,name,pic,detail,virtualnum,stocknum,status,mold,s_state')
            ->select();
        if($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'获取数据成功');
        } else {
            $this->result('',0,'暂无数据');
        }

    }

    //产品搜索  产品名称
    public function pname()
    {
        $key = input('post.key');  //搜索类型
        $value = input('post.value'); //搜索内容
        $ifsigning = input('post.ifsigning');  //是否签约  0  未签约   1  已签约
        $page = input('post.page')? : 1;
        return $this->search($key,$value,$page,$ifsigning);
    }
    //产品搜索  产品种类
    public function pmold()
    {
        $key = input('post.key');  //搜索类型
        $value = input('post.value'); //搜索内容
        $ifsigning = input('post.ifsigning');  //是否签约  0  未签约   1  已签约
        $page = input('post.page')? : 1;
        return $this->search($key,$value,$page,$ifsigning);
    }

	//列表
//	public function index($page,$status,$sid,$ifsigning)
	public function index()
	{
        $ifsigning = input('post.ifsigning'); //是否签约 0  未签约   1  已签约
        $page = input('post.page')? : 1;
		$pageSize = 8;
		$count = Db::table('st_commodity')
                 ->where(['sid'=>$this->sid,'ifsigning'=>$ifsigning])
				 ->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('st_commodity')
                 ->where(['sid'=>$this->sid,'ifsigning'=>$ifsigning])
				 ->order('create_time desc')
				 ->page($page,$pageSize)
				 ->field('id,pnum,name,pic,detail,virtualnum,stocknum,status,mold,s_state')
				 ->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取数据成功');
		} else {
			$this->result('',0,'暂无数据');
		}

	}

	/*
	 *查看驳回原因
	 */
	public function rejectRson()
    {
        $id = input('post.id');  //产品的ID
        $info = Db::table('st_commodity')->field('reason')->where('id',$id)->find();
        if ($info){
            $this->result($info,1,'驳回理由数据返回成功');
        }else{
            $this->result('',0,'驳回理由数据返回失败');
        }
    }
	/**
	 * 商品上架
	 */
	public function putaway()
	{
		$id = input('post.id');
		$res = Db::table('st_commodity')
				->where('sid',$this->sid)
				->where('id',$id)
				->setField('s_state',1);
		if($res) {
			$this->result('',1,'上架成功');
		} else {
			$this->result('',0,'上架失败');
		}
	}
	/**
	 * 商品下架
	 */
	public function soldout()
	{
		$id = input('post.id');
		$res = Db::table('st_commodity')
				->where('sid',$this->sid)
				->where('id',$id)
				->setField('s_state',0);
		if($res) {
			$this->result('',1,'下架成功');
		} else {
			$this->result('',0,'下架失败');
		}
	}
	/*
	 *产品删除
	 * 产品规格删除
	 */
	public function del()
    {
        $id = input('post.id');    //  产品 ID
        $count = Db::table('st_order')
            ->where('cid',$id)
            ->count();
        if ($count == 0){
            $res = Db::table('st_commodity')
                ->where('sid',$this->sid)
                ->where('id',$id)
                ->delete();
            $ree = Db::table('st_commodity_detail')
                ->where('cid',$id)
                ->delete();
            $ret = Db::table('st_evaluate')
                ->where('cid',$id)
                ->delete();
            $rse = Db::table('st_evaluate')
                ->where('cid',$id)
                ->delete();
            if($res) {
                $this->result('',1,'删除产品及该产品下所有规格成功');
            } else {
                $this->result('',0,'删除产品及该产品下所有规格失败');
            }
        }else{
            $this->result('',2,'该产品下有订单数据，不允许删除');
        }

    }
    //产品规格删除
	public function del_detail()
    {
        $id = input('post.id');  //规格ID
        $res = Db::table('st_commodity_detail')
            ->where('id',$id)
            ->delete();
        if ($res){
            $this->result('',1,'删除产品规格成功');
        }else{
            $this->result('',0,'删除产品规格失败');
        }
    }


	/**
	 * 产品详情
	 */
	public function goodsDetail()
	{
		$id = input('post.id');  //产品ID
		$info['shop'] = Db::table('st_commodity')->field('name,pic,detail,mold')->where('id',$id)->find();
		$info['spec'] = Db::table('st_commodity_detail')
            ->field('id as did,standard,standard_detail,sell_number,stock_number,market_price,activity_price')
            ->where('cid',$id)
            ->select();
		if($info) {
			$this->result($info,1,'获取原数据成功');
		} else {
			$this->result('',0,'获取原数据失败');
		}
	}

	/**
	 * 修改商品信息
	 */
	public function alterGoods()
	{
        //产品信息
        $data = input('post.');  // 产品修改信息
        unset($data['token']);
//        $detail = strip_tags($data['detail']);
        $cid = Db::table('st_commodity')
            ->update(['name'=>$data['name'],'detail'=>$data['detail'],'pic'=>$data['pic'],'mold'=>$data['mold'],'id'=>$data['id']]);
//        $stan = json_decode($data['standard'],true);
        $stan = $data['standard'];
        foreach ($stan as $key => $value) {     //将其余信息修改数据到产品详情表
            $arr = [
                'standard' => $stan[$key]['standard'],
                'standard_detail' => $stan[$key]['standard_detail'],
                'sell_number' => $stan[$key]['sell_number'],
                'stock_number' => $stan[$key]['stock_number'],
                'market_price' => $stan[$key]['market_price'],
                'activity_price' => $stan[$key]['activity_price'],
            ];
//            var_dump($arr);exit;
            $res[] = Db::table('st_commodity_detail')->where('id',$stan[$key]['did'])->update($arr);
        }
        unset($data['standard']);
        if($cid || $res) {
            $this->result('',1,'修改产品成功');
        } else {
            $this->result('',0,'修改产品失败');
        }
	}

    /*
     * 商品图文详情图片接口
     */
    public function images()
    {
        //图片文件的生成
        return upload('file','st/photo','https://cc.ctbls.com/');

    }
}