<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;
use Msg\Sms;

/**
 * 汽修厂投诉管理
 */
class Complain extends Shop
{

	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		parent::initialize();
	}


//	/**
//	 * 获取投诉列表
//	 */
//	public function index()
//	{
//		$page = input('post.page');
//		// 获取总条数
//		$count = Db::table('cs_complain')->where('sid',$this->sid)->count();
//		$pageSize = 10;
//		$rows = ceil($count / $pageSize);
//		$list = Db::table('cs_complain')
//				->where('sid',$this->sid)
//				->field('id,title,create_time')
//				->order('id desc')
//				->page($page, $pageSize)
//				->select();
//		// 返回给前端
//		if($count > 0){
//			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
//		}else{
//			$this->result('',0,'暂无数据');
//		}
//	}
//
//	/**
//	 * 进行投诉
//	 */
//	public function add()
//	{
//		// 获取运营商列表
//		$ad = Db::table('cs_shop')->field('aid,company')->where('id',$this->sid)->find();
//		// 构建投诉数据
//		$arr = [
//			'sid' => $this->sid,
//			'company' => $ad['company'],
//			'aid' => $ad['aid'],
//			'title' => input('post.title'),
//			'content' => input('post.content')
//		];
//		$add = Db::table('cs_complain')->strict(false)->insert($arr);
//		if($add){
//			$this->result('',1,'投诉成功');
//		}else{
//			$this->result('',0,'提交失败，请重新提交');
//		}
//
//	}
//
//	/**
//	 * 投诉详情
//	 */
//	public function detail()
//	{
//		$id = input('post.id');
//		$data = Db::table('cs_complain')->field('title,content,create_time')->where('id',$id)->find();
//		if($data){
//			$this->result($data,1,'获取成功');
//		}else{
//			$this->result('',0,'暂无数据');
//		}
//	}

	/**
	 * 获取运营商的名称
	 */
	public function agent()
	{
		$aid = $this->getAgent();
		$agent_name = Db::table('ca_agent')->where('aid',$aid)->value('company');
		if($agent_name){
			$this->result($agent_name,1,'获取成功');
		}else{
			$this->result('',0,'不存在该运营商');
		}
	}


    /*******************************************维修厂投诉***************************************************/

    // 判断 维修厂 是投诉  运营商  还是 服务经理
    public function selection()
    {
        // 类型   1  运营商  2   服务经理
        $type = input('post.type');
        if ($type == 1){
            $info = $this->agentInfo($this->sid);
        }elseif ($type == 2){
            $info = $this->ifManager($this->sid);
        }
        if ($info){
            $this->result($info,1,'数据获取成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }

    // 获取 运营商 信息
    public function agentInfo($sid)
    {
        // 查询维修厂的运营商ID
        $agent_id = Db::table('cs_shop')->where('id',$sid)->value('aid');
        // 查询运营商的ID和名称
        $info = Db::table('ca_agent')
            ->where('aid',$agent_id)
            ->where('status','<>',6)
            ->field('aid as id,company as name')
            ->find();
        if ($info){
            $info['code'] = 1;
            return $info;
        }else{
            $info['code'] = 0;
            $info['msg'] = '此区域无运营商,暂时无法投诉';
            return $info;
        }
    }

    // 判断该维修厂区域是否有服务经理
    public function ifManager($sid)
    {
        // 搜索该维修厂的市级ID
        $city_id = Db::table('co_china_data')
            ->alias('a')
            ->join('cs_shop_set b','a.id = b.county_id','LEFT')
            ->where('b.sid',$sid)
            ->value('a.pid');
        // 查询是否有该区域的服务经理
        $if_exist = Db::table('sm_area')
            ->where('area',$city_id)
            ->where('pay_status',1)
            ->where('audit_status',1)
            ->where('is_exits',1)
            ->where('sm_type',1)
            ->where('sm_mold','<>',2)
            ->where('sm_mold','<>',3)
            ->field('sm_id')
            ->find();
        if ($if_exist){
            // 查询该服务经理的姓名 ID
            $info = Db::table('sm_user')
                ->where('id',$if_exist['sm_id'])
                ->field('id,name')
                ->find();
            $info['code'] = 1;
            return $info;
        }else{
            $info['code'] = 0;
            $info['msg'] = '该区域不存在服务经理,暂时无法投诉';
            return $info;
        }
    }

    // 维修厂 投诉 操作
    public function complaint()
    {
        $data = input('post.');
        if ($data['type'] == 1){
            $this->add($data);
        }elseif ($data['type'] == 2 ){
            // 最多输入50个汉字
            if (strlen($data['content']) > 150){
                $this->result('',0,'最多可输入50个汉字');
            }
            // 查询维修厂省级和市级ID
            $shop_info = Db::table('cs_shop_set')
                ->where('sid',$this->sid)
                ->field('province,city,county_id,serphone')
                ->find();
            // 查询维修厂市级ID
            $city_id = Db::table('co_china_data')->where('name',$shop_info['city'])->value('id');
            // 查询 省级ID
            $pro_id = Db::table('co_china_data')->where('name',$shop_info['province'])->value('id');
            // 查询维修厂负责人姓名
            $name = Db::table('cs_shop')->where('id',$this->sid)->field('company,leader')->find();
            $arr = [
                'sm_id' => $data['sm_id'],
                'uid' => $this->sid,
                'pro_id' => $pro_id,
                'city_id' => $city_id,
                'county_id' => $shop_info['county_id'],
                'name' => $name['leader'],
                'company' => $name['company'],
                'phone' => $shop_info['serphone'],
                'title' => $data['title'],
                'content' => $data['content'],
                'create_time' => date('Y-m-d H:i:s',time()),
                'sm_status' => 0,
                'status' => 1,
                'type' => 3,   //  维修厂

            ];
            $ret = Db::table('sm_complaint')->strict(false)->insert($arr);
            if ($ret){
                $this->result('',1,'投诉成功');
            }else{
                $this->result('',0,'投诉失败');
            }
        }
    }


    /**
     * 进行投诉
     */
    public function add($data)
    {
        // 获取运营商列表
        $ad = Db::table('cs_shop')->field('aid,company')->where('id',$this->sid)->find();
        // 构建投诉数据
        $arr = [
            'sid' => $this->sid,
            'company' => $ad['company'],
            'aid' => $ad['aid'],
            'title' => $data['title'],
            'content' => $data['content']
        ];
        $add = Db::table('cs_complain')->strict(false)->insert($arr);
        if($add){
            $this->result('',1,'投诉成功');
        }else{
            $this->result('',0,'提交失败，请重新提交');
        }

    }

    // 维修厂 撤回 投诉 操作
    public function withdrawComplaint()
    {
        // 投诉ID
        $id = input('post.id');
        // 投诉的类型
        $type = input('post.type');
        if ($type == 2){
            $ret = Db::table('sm_complaint')
                ->where('id',$id)
                ->update(['sm_status'=>1,'status'=>2,'handle_time'=>time()]);
            if ($ret){
                $this->result('',1,'撤回投诉成功');
            }else{
                $this->result('',0,'撤回投诉失败');
            }
        }
    }

    public function index()
    {
        $pageSize = 8;  // 每页条数
        $page = input('post.page')? : 1;
        $data1 = $this->complaintList();
        $data2 = $this->operatorList();
        // 将 两个列表进行合并
        $list = array_merge($data1,$data2);
        if (!empty($list)){
            $start = ($page-1)*$pageSize; // 每次分页开始的位置
            $total = count($list);  // 总条数
            $rows = ceil($total/$pageSize);  // 分页
            $list = array_slice($list,$start,$pageSize);
        }
        if ($list){
            $this->result(['list'=>$list,'rows'=>$rows],1,'数据返回成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }


    // 维修厂 投诉 服务经理 列表
    public function complaintList()
    {
        $list = Db::table('sm_complaint')
            ->where('uid',$this->sid)
            ->where('type',3)
            ->field('id,sm_id,title,create_time,status')
            ->order('create_time DESC')
            ->select();
        // 查询 服务经理信息  姓名
        foreach ($list as $key=>$value){
            $list[$key]['name'] = Db::table('sm_user')
                ->where('id',$list[$key]['sm_id'])
                ->value('name');
            $list[$key]['type'] = 2;
            unset($list[$key]['sm_id']);

        }
        if ($list){
            return $list;
//            $this->result($list,1,'数据返回成功');
        }else{
            return array();
//            $this->result('',0,'暂无数据');
        }
    }

    /**
     * 维修厂 投诉 运营商 列表
     */
    public function operatorList()
    {
        $list = Db::table('cs_complain')
            ->where('sid',$this->sid)
            ->field('id,aid,title,create_time')
            ->order('id desc')
            ->select();
        foreach ($list as $key=>$value){
            $list[$key]['name'] = Db::table('ca_agent')
                ->where('aid',$list[$key]['aid'])
                ->value('company');
            $list[$key]['type'] = 1;
            $list[$key]['status'] = 2;
            unset($list[$key]['aid']);
        }
        // 返回给前端
        if($list){
            return $list;
//            $this->result($list,1,'获取成功');
        }else{
            return array();
//            $this->result('',0,'暂无数据');
        }
    }


    /**
     * 投诉详情
     */
    public function detail()
    {
        // 投诉ID  投诉类型  1  运营商  2  服务经理
        $par = input('post.');
        if ($par['type'] == 1){
            $data = Db::table('cs_complain')->field('title,content,create_time')->where('id',$par['id'])->find();
        }elseif ($par['type'] == 2){
            $data = Db::table('sm_complaint')->field('title,content,create_time')->where('id',$par['id'])->find();
        }
        if($data){
            $this->result($data,1,'获取成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }


}