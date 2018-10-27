<?php 
namespace app\st\controller;
use app\base\controller\St;
use think\Db;

/**
 * 评论管理
 */
class Comment extends St
{
	/*
	 * 进程初始化
	 */
	public function initialize()
	{
		parent::initialize();
	}

    /*
    * 搜索
    */
    public function firshSearch($key,$value,$page,$ifsigning)
    {
        $pageSize = 8;
        $count = Db::table('st_evaluate')
            ->alias('a')
            ->join('st_user b','a.uid=b.id','LEFT')
            ->join('st_commodity c','a.cid=c.id','LEFT')
            ->where([
                ['sid','=',$this->sid],
                ['a.ifsigning','=',$ifsigning],
                ['b.'.$key,'like','%'.$value.'%'],
            ])
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('st_evaluate')
            ->alias('a')
            ->join('st_user b','a.uid=b.id','LEFT')
            ->join('st_commodity c','a.cid=c.id','LEFT')
            ->field('a.id,a.content,a.class,FROM_UNIXTIME(a.create_time) as create_time,b.name,b.phone,c.name as wname,a.isshow')
            ->where([
                ['sid','=',$this->sid],
                ['a.ifsigning','=',$ifsigning],
                ['b.'.$key,'like','%'.$value.'%'],
            ])
            ->order('a.create_time DESC')
            ->page($page,$pageSize)
            ->select();
        foreach ($list as $key=>$value){
            if ($list[$key]['isshow'] == 1){
                $list[$key]['isshow'] = false;
            }else{
                $list[$key]['isshow'] = true;
            }
        }
        if ($count > 0) {
            $this->result(['list' => $list, 'rows' => $rows], 1, '获取数据成功');
        } else {
            $this->result('', 1, '暂无数据');
        }

    }

    /*
     *搜索  评价人姓名
     */
    public function reName()
    {
        $key = input('post.key');  //搜索类型
        $value = input('post.value');  //搜索内容
        $ifsigning = input('post.ifsigning'); //是否签约 0  未签约   1  已签约
        $page = input('post.page')? : 1;
        return $this->firshSearch($key,$value,$page,$ifsigning);
    }

    /*
    *搜索  评价人手机号
    */
//    public function rePhone()
//    {
//        $key = input('post.key');  //搜索类型
//        $value = input('post.value');  //搜索内容
//        $ifsigning = input('post.ifsigning'); //是否签约 0  未签约   1  已签约
//        $page = input('post.page')? : 1;
//        return $this->firshSearch($key,$value,$page,$ifsigning);
//    }



    /*
     *搜索   按 产品名称
     */
    public function secondSearch($key,$value,$page,$ifsigning)
    {
        $pageSize = 8;
        $count = Db::table('st_evaluate')
            ->alias('a')
            ->join('st_user b','a.uid=b.id','LEFT')
            ->join('st_commodity c','a.cid=c.id','LEFT')
            ->where([
                ['sid','=',$this->sid],
                ['a.ifsigning','=',$ifsigning],
                ['c.'.$key,'like','%'.$value.'%'],
            ])
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('st_evaluate')
            ->alias('a')
            ->join('st_user b','a.uid=b.id','LEFT')
            ->join('st_commodity c','a.cid=c.id','LEFT')
            ->field('a.id,a.content,a.class,FROM_UNIXTIME(a.create_time) as create_time,b.name,b.phone,c.name as wname,a.isshow')
            ->where([
                ['sid','=',$this->sid],
                ['a.ifsigning','=',$ifsigning],
                ['c.'.$key,'like','%'.$value.'%'],
            ])
            ->order('a.create_time DESC')
            ->page($page,$pageSize)
            ->select();
        foreach ($list as $key=>$value){
            if ($list[$key]['isshow'] == 1){
                $list[$key]['isshow'] = false;
            }else{
                $list[$key]['isshow'] = true;
            }
        }
        if ($count > 0) {
            $this->result(['list' => $list, 'rows' => $rows], 1, '获取数据成功');
        } else {
            $this->result('', 1, '暂无数据');
        }

    }

    /*
   *搜索  预约产品名称
   */
    public function rePname()
    {
        $key = input('post.key');  //搜索类型
        $value = input('post.value');  //搜索内容
        $ifsigning = input('post.ifsigning'); //是否签约 0  未签约   1  已签约
        $page = input('post.page')? : 1;
        return $this->secondSearch($key,$value,$page,$ifsigning);
    }


    /*
    *搜索 评价时间段
    *
    */
//    public function thirdSearch($key,$arr,$page,$ifsigning)
//    {
//        $pageSize = 8;
//        $count = Db::table('st_evaluate')
//            ->alias('a')
//            ->join('st_user b','a.uid=b.id','LEFT')
//            ->join('st_commodity c','a.cid=c.id','LEFT')
//            ->where('sid',$this->sid)
//            ->where('a.ifsigning',$ifsigning)
//            ->whereBetweenTime('a.'.$key,$arr['sarttime'],$arr['endtime'])
//            ->count();
//        $rows = ceil($count / $pageSize);
//        $list = Db::table('st_evaluate')
//            ->alias('a')
//            ->join('st_user b','a.uid=b.id','LEFT')
//            ->join('st_commodity c','a.cid=c.id','LEFT')
//            ->field('a.id,a.content,a.class,FROM_UNIXTIME(a.create_time) as create_time,b.name,b.phone,c.name as wname,a.isshow')
//            ->where('sid',$this->sid)
//            ->where('a.ifsigning',$ifsigning)
//            ->whereBetweenTime('a.'.$key,$arr['sarttime'],$arr['endtime'])
//            ->order('a.create_time DESC')
//            ->page($page,$pageSize)
//            ->select();
//        foreach ($list as $key=>$value){
//            if ($list[$key]['isshow'] == 1){
//                $list[$key]['isshow'] = false;
//            }else{
//                $list[$key]['isshow'] = true;
//            }
//        }
//        if ($count > 0) {
//            $this->result(['list' => $list, 'rows' => $rows], 1, '获取数据成功');
//        } else {
//            $this->result('', 1, '暂无数据');
//        }
//
//    }
    /*
   *搜索  评价时间段
   */
//    public function reTime()
//    {
//        $key = input('post.key');  //搜索类型
//        $v = input('post.value');  //搜索开始时间
//        $s = json_decode($v,true);
//        unset($v);
//        foreach ($s as $k=>$value){
//            $arr = [
//                'sarttime'=>strtotime($s[$k]['starttime']),
//                'endtime'=>strtotime($s[$k]['endtime'])
//            ];
//        }
//        $ifsigning = input('post.ifsigning'); //是否签约 0  未签约   1  已签约
//        $page = input('post.page')? : 1;
//        return $this->thirdSearch($key,$arr,$page,$ifsigning);
//    }



	/*
	 *产品评论管理
	 */
	public function comment()
    {
        $pageSize = 8;
        $ifsigning = input('post.ifsigning'); //是否签约 0  未签约   1  已签约
        $page = input('post.page')? : 1;
        $count = Db::table('st_evaluate')
            ->alias('a')
            ->join('st_user b','a.uid=b.id','LEFT')
            ->join('st_commodity c','a.cid=c.id','LEFT')
            ->where(['sid'=>$this->sid,'a.ifsigning'=>$ifsigning])
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('st_evaluate')
            ->alias('a')
            ->join('st_user b','a.uid=b.id','LEFT')
            ->join('st_commodity c','a.cid=c.id','LEFT')
            ->field('a.id,a.content,a.class,FROM_UNIXTIME(a.create_time) as create_time,b.name,b.phone,c.name as wname,a.isshow')
            ->where(['sid'=>$this->sid,'a.ifsigning'=>$ifsigning])
            ->order('a.create_time DESC')
            ->page($page,$pageSize)
            ->select();
        foreach ($list as $key=>$value){
            if ($list[$key]['isshow'] == 1){
                $list[$key]['isshow'] = false;
            }else{
                $list[$key]['isshow'] = true;
            }
        }
        if($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'获取数据成功');
        } else {
            $this->result('',1,'暂无数据');
        }
    }

//    /*
//     *显示评论
//     */
//    public function show()
//    {
//        $id = input('post.id');   //评论ID
//        $res = Db::table('st_evaluate')
//            ->where('id',$id)
//            ->setField('isshow',2);
//        if($res) {
//            $this->result('',1,'显示评论成功');
//        } else {
//            $this->result('',0,'显示评论失败');
//        }
//
//    }
//
//    /*
//     *不显示评论
//     */
//    public function nshow()
//    {
//        $id = input('post.id');   //评论ID
//        $res = Db::table('st_evaluate')
//            ->where('id',$id)
//            ->setField('isshow',1);
//        if($res) {
//            $this->result('',1,'不显示评论成功');
//        } else {
//            $this->result('',0,'不显示评论失败');
//        }
//    }

    /*
     *是否显示评论
     */
    public function ifShow()
    {
        $id = input('post.id');  //评论ID
        $res = Db::table('st_evaluate')->field('isshow')->where('id',$id)->find();   //获取该评论的是否显示状态
        if ($res['isshow'] == 2){  //   2   显示 状态
            $ret = Db::table('st_evaluate')->where('id',$id)->setField('isshow',1);
            if($ret) {
                $this->result('',1,'不显示评论成功');
            } else {
                $this->result('',0,'不显示评论失败');
            }
        }elseif ($res['isshow'] == 1){
            $ret = Db::table('st_evaluate')->where('id',$id)->setField('isshow',2);
            if($ret) {
                $this->result('',1,'显示评论成功');
            } else {
                $this->result('',0,'显示评论失败');
            }
        }
    }

}