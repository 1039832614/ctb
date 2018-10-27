<?php
namespace app\agent\controller;
use app\base\controller\Agent;
use think\Db;
use think\Controller;
use Ana\ShopAna;

/**
* 运营商数据分析
*/
class Info extends Agent
{	
	function initialize()
	{
		parent::initialize();
		$this->ShopAna = new ShopAna();
		$this->id = Db::table('cs_shop')->where('aid',$this->aid)->where('audit_status',2)->column('id');
	}
	/*******************************************************************/
    /*   2018.09.05 08:53 张乐召   补充  邦保养 关注度和参与
     * 运营商 邦保养参与度 与  邦保养关注
     */
    public function attention()
    {
        $count = $this->ShopAna->deliver_num($this->id);
        if ($count){
            $this->result($count,1,'数据返回成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }

	/*******************************************************************/
    //月份
    public function arr($rank){
        $res = [];
    foreach ($rank as $key => $val) {
        $res[$val['month']] = $val['number'];
    }
    for ($i=1;$i<13;$i++){
        if (!array_key_exists($i,$res)){
            $res[$i] = 0;
        }
    }
    $arr = array();
    ksort($res);
    foreach ($res as $k=>$v){
        $arr[] = $v;
    }
    return $arr;
}
	// 交易金额
	public function money_num(){
		$list = Db::table('u_card')
				->where([
					['sid','in',$this->id],
					['pay_status','=',1],
				])
				->field("DATE_FORMAT(sale_time,'%c') as month,sum(card_price) as number")
				->where("DATE_FORMAT(sale_time,'%Y') = YEAR(CurDate())")
				->group('month')
				->select();
		$list = $this->arr($list);
		if($list){
			$this->result($list,1,'获取数据成功');
		} else {
			$this->result('',0,'暂无数据');
		}
	}
	//售卡总数
	public function card_num(){
		$list = $this->ShopAna->card($this->id,'1,4');
	}
	// 复购量
	public function repetition(){
		$list = $this->ShopAna->repetition_num($this->id);
	}
	//消耗物料
	public function dissipation_num(){
		$list = Db::table('cs_income')
            ->where('sid','in',$this->id)
            ->field("DATE_FORMAT(create_time,'%c') as month,sum(litre) as number")
			->where("DATE_FORMAT(create_time,'%Y') = YEAR(CurDate())")
			->group('month')
            ->select();
       $list = $this->arr($list);
        if($list){
            $this->result($list,1,'获取数据成功');
        } else {
            $this->result('',0,'暂无数据');
        }
	}
	//服务次数
	public function technician_num(){
		// $list = $this->ShopAna->worker_serve($this->id);
        $list = Db::table('cs_income')
                    ->where([
                        ['sid','in',$this->id]
                    ])
                    ->field("DATE_FORMAT(create_time,'%c') as month,count(id) as number")
                    ->where("DATE_FORMAT(create_time,'%Y') = YEAR(CurDate())")
                    ->group('month')
                    ->select();
        $arr = $this->arr($list);
        if ($arr){
            $this->result($arr,1,'获取成功');
        }else{
            $this->result('',0,'暂无数据');
        }
	}
	//好评奖励
	public function good(){
        //  2018.09.05 16:35 张乐召  修改
		$list = Db::table('u_comment')
				->where('sid','in',$this->id)
                ->where('tn_star',5)
                ->where('shop_star',5)
				->field("DATE_FORMAT(create_time,'%c') as month,count(id) as number")
				->where("DATE_FORMAT(create_time,'%Y') = YEAR(CurDate())")
				->group('month')
				->select();
		$list = $this->arr($list);
        if($list){
            $this->result($list,1,'获取数据成功');
        } else {
            $this->result('',0,'暂无数据');
        }
	}
	/*
     *售卡详情
     */
    public function sell_card()
    {
        $pageSize = 8;
        $page = input('post.page')? : 1;
        $count = Db::table('u_card c')
        		->join('cs_shop s','c.sid=s.id')
            	->where('sid','in',$this->id)
            	->where('pay_status',1)
                ->where('s.audit_status',2)
            	->group('c.id')
            	->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('u_card c')
        		->join('cs_shop s','c.sid=s.id')
	            ->where('sid','in',$this->id)
	            ->field('company,leader,phone,count(c.id) as num,sum(card_price) as price')
	            ->where('pay_status',1)
                ->where('s.audit_status',2)
	            ->page($page,$pageSize)
	            ->group('c.id')
	            ->select();
        if($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }
    //期初配给  详情 
    public function initial()
    {
       $pageSize = 8;
        $page = input('post.page')? : 1;
        $count = Db::table('co_bang_cate')
            ->where('id','in','2,3,4,5,7')
            ->field('def_num')
            ->count();
        $pages = ceil($count / $pageSize);
        $list = Db::table('co_bang_cate')
            ->where('id','in','2,3,4,5,7')
            ->field('def_num as ration,name')
            ->page($page,$pageSize)
            ->select();
        $data = Db::table('ca_agent')->where('aid',$this->aid)->value('audit_time');
        $list[] = date('Y-m-d H:i:s',$data);
        if ($count > 0){
            $this->result(['list'=>$list,'pages'=>$pages],1,'获取成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }
    //配送物料
	public function distribution(){
        $this->ShopAna->embody($this->id);
	}
	/*
     *物料补充
     */
    public function embody_num()
    {
         $pageSize = 8;
        $page = input('post.page')? : 1;
        $count = Db::table('ca_apply_materiel')
            ->where('aid',$this->aid)
            ->count();
        $totalpages = ceil($count / $pageSize);
        $data = Db::table('ca_apply_materiel')
            ->where('aid',$this->aid)
            ->field('odd_number,detail,create_time')
            ->page($page,$pageSize)
            ->select();
        $lists= array();
        foreach ($data as $key=>$value){
            $list[$key] = json_decode($data[$key]['detail'],true);
            foreach ($list[$key] as $k=>$v){
                switch ($list[$key][$k]['materiel_id']){
                    case 2:
                        $lists[$key]['num1'] =   $list[$key][$k]['num'];
                        break;
                    case 3:
                        $lists[$key]['num2'] = $list[$key][$k]['num'];
                        break;
                    case 4:
                        $lists[$key]['num3'] = $list[$key][$k]['num'];
                        break;
                    case 5:
                        $lists[$key]['num4'] = $list[$key][$k]['num'];
                        break;
                    case 7:
                        $lists[$key]['num5'] = $list[$key][$k]['num'];
                        break;
                }
            }
            $lists[$key]['odd_number'] = $data[$key]['odd_number'];
            $lists[$key]['create_time'] = $data[$key]['create_time'];
        }
        foreach ($lists as $key => $value) {
            for ($i=1; $i < 6; $i++) { 
                if(empty($value['num'.$i])){
                    $lists[$key]['num'.$i] = 0;
                }
            }
        }
        if ($count > 0){
            $this->result(['list'=>$lists,'totalpages'=>$totalpages],1,'获取成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }

	//物料库存 
	public function bang_num(){
		$pageSize = 8;
        $page = input('post.page')? : 1;
        $count = Db::table('ca_ration')
            ->alias('a')
            ->join('co_bang_cate b','a.materiel = b.id','LEFT')
            ->where('aid',$this->aid)
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('ca_ration')
            ->alias('a')
            ->join('co_bang_cate b','a.materiel = b.id','LEFT')
            ->where('aid',$this->aid)
            ->field('b.name,a.materiel_stock')
            ->page($page,$pageSize)
            ->select();
        if ($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
        }else{
            $this->result('',0,'暂无数据');
        }
	}
	//增加配给 
    public function  allocate(){
        $pageSize = 8;
        $page = input('post.page')? : 1;
        $count = Db::table('ca_increase')
            ->where('aid',$this->aid)
            ->where('audit_status','1')
            ->count();
        $rows = ceil($count / $pageSize);
        $data = Db::table('ca_increase')
            ->where('aid',$this->aid)
            ->where('audit_status','1')
            ->field('create_time,regions,price')
            ->page($page,$pageSize)
            ->select();
        //地区
        $area = Db::table('ca_increase')
            ->where('aid',$this->aid)
            ->where('audit_status','1')
            ->field('area')
            ->select();
        $list = array();
        $num = Db::table('co_bang_cate')->field('def_num')->whereIn('id','2,3,4,5,7')->select();
        foreach ($data as $key=>$value){
            $list[] = [
                'bang2'=>$num[0]['def_num']*$data[$key]['regions'],
                'bang3'=>$num[1]['def_num']*$data[$key]['regions'],
                'bang4'=>$num[2]['def_num']*$data[$key]['regions'],
                'bang5'=>$num[3]['def_num']*$data[$key]['regions'],
                'life'=>$num[4]['def_num']*$data[$key]['regions'],
                'create_time'=>$data[$key]['create_time'],
                'price'=>$data[$key]['price']
            ];
        }
        foreach ($area as $k => $v) {
            $list[$k]['area'] = Db::table('co_china_data')
                    ->where('id','in',explode(',',$area[$k]['area']))
                    ->column('name');
            $list[$k]['area'] = implode(',', $list[$k]['area']);
        }
        if ($count > 0){
            $this->result(['list'=>$list,'totalpages'=>$rows],1,'获取成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }
    
    //期初授信 
	public function allotment_num(){
		$pageSize = 8;
        $page = input('post.page')? : 1;
        $count = Db::table('co_bang_cate')
            ->where('id','in','2,3,4,5,7')
            ->field('def_num')
            ->count();
        $pages = ceil($count / $pageSize);
        $list = Db::table('co_bang_cate')
            ->where('id','in','2,3,4,5,7')
            ->field('def_num as ration,name')
            ->page($page,$pageSize)
            ->select();
        $data = Db::table('ca_agent')->where('aid',$this->aid)->value('audit_time');
        $list[] = date('Y-m-d H:i:s',$data);
        if ($count > 0){
            $this->result(['list'=>$list,'pages'=>$pages],1,'获取成功');
        }else{
            $this->result('',0,'暂无数据');
        }
	}
   	/*
     *增加授信 
     */
    public function credit_num()
    {
        $pageSize = 8;
        $page = input('post.page')? : 1;
        $count = Db::table('ca_increase')
            ->where('aid',$this->aid)
            ->where('audit_status','1')
            ->count();
        $rows = ceil($count / $pageSize);
        $data = Db::table('ca_increase')
            ->where('aid',$this->aid)
            ->where('audit_status','1')
            ->field('create_time,regions,price')
            ->page($page,$pageSize)
            ->select();
        //地区
        $area = Db::table('ca_increase')
            ->where('aid',$this->aid)
            ->where('audit_status','1')
            ->field('area')
            ->select();
        $list = array();
        $num = Db::table('co_bang_cate')->field('def_num')->whereIn('id','2,3,4,5,7')->select();
        foreach ($data as $key=>$value){
            $list[] = [
                'bang2'=>$num[0]['def_num']*$data[$key]['regions'],
                'bang3'=>$num[1]['def_num']*$data[$key]['regions'],
                'bang4'=>$num[2]['def_num']*$data[$key]['regions'],
                'bang5'=>$num[3]['def_num']*$data[$key]['regions'],
                'life'=>$num[4]['def_num']*$data[$key]['regions'],
                'create_time'=>$data[$key]['create_time'],
                'price'=>$data[$key]['price']
            ];
        }
        foreach ($area as $k => $v) {
            $list[$k]['area'] = Db::table('co_china_data')
                    ->where('id','in',explode(',',$area[$k]['area']))
                    ->column('name');
            $list[$k]['area'] = implode(',', $list[$k]['area']);
        }
        if ($count > 0){
            $this->result(['list'=>$list,'totalpages'=>$rows],1,'获取成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }
    /*
     *数据总接口
     */
     public function total_num()
    {
        $list['1'] = $this->bang(2);
        $list['2'] = $this->bang(3);
        $list['3'] = $this->bang(4);
        $list['4'] = $this->bang(5);
        $list['5'] = $this->bang(7);
        foreach ($list as $key => $value) {
            $list['1']['name'] = 'SM半合成(邦保养1号)';
            $list['2']['name'] = 'SN合成(邦保养2号)';
            $list['3']['name'] = 'SN全合成(邦保养3号)';
            $list['4']['name'] = 'SN脂类全合成(邦保养4号)';
            $list['5']['name'] = '“惠生活”豪华大礼包';
        }
        if ($list){
            $this->result($list,1,'数据返回成功');
        }else{
            $this->result($list,0,'数据返回失败');
        }
    }
    //运营库存
    public function bang($number)
    {
        //期初配给 
        $list['rationing'] = Db::table('ca_ration')
            ->alias('a')
            ->join('co_bang_cate b','a.materiel = b.id','LEFT')
            ->where('a.materiel = '.$number)
            ->where('aid',$this->aid)
            ->sum('def_num');
        if ($number == 7){
            $list['rationing'] = $list['rationing'].'台';
        }else{
            $list['rationing'] = $list['rationing'].'L';
        }
        //配送物料
        $data = Db::table('cs_apply_materiel')
            ->where('aid',$this->aid)
            ->field('detail')
            ->select();
        if (count($data) != 0){
            foreach ($data as $key=>$value){
                $data[$key]['detail'] = json_decode($data[$key]['detail'],true);
            }
            foreach ($data as $key => $value){
                $detail[] = $data[$key]['detail'];
            }
            $num = 0;
            foreach ($detail as $key => $value){
                $arr = $detail[$key];
                if(!empty($arr)){
                    foreach ($arr as $key => $value){
                        if($arr[$key]['materiel_id'] == $number){
                            $num += $arr[$key]['num'];
                        }
                    }
                }
               
            }
            if ($number == 7){
                $list['embody'] = $num.'台';
            }else{
                $list['embody'] = $num.'L';
            }
        }else{
            $list['embody'] = 0;
        }
        //物料补充
        $data = Db::table('ca_apply_materiel')
            ->where('aid',$this->aid)
            ->field('detail')
            ->select();
        if (count($data) != 0){
            foreach ($data as $key=>$value){
                $data[$key]['detail'] = json_decode($data[$key]['detail'],true);
            }
            foreach ($data as $key => $value){
                $detail[] = $data[$key]['detail'];
            }
            $num = 0;
            foreach ($detail as $key => $value){
                $arr = $detail[$key];
                if(!empty($arr)){
                     foreach ($arr as $key => $value){
                        if($arr[$key]['materiel_id'] == $number){
                            $num += $arr[$key]['num'];
                        }
                    }
                }
            }
            if ($number == 7){
                $list['buchong'] = $num.'台';
            }else{
                $list['buchong'] = $num.'L';
            }
        }else{
            $list['buchong'] = 0;
        }
        //物料库存
        $list['surplus'] = Db::table('ca_ration')
            ->alias('a')
            ->join('co_bang_cate b','a.materiel = b.id','LEFT')
            ->where('a.materiel = '.$number)
            ->where('aid',$this->aid)
            ->sum('materiel_stock');
        if ($number == 7){
            $list['surplus'] = $list['surplus'].'台';
        }else{
            $list['surplus'] = $list['surplus'].'L';
        }
        //增加配给
         $datas = Db::table('ca_increase')
                    ->where('aid',$this->aid)
                    ->where('audit_status',1)
                    ->sum('regions');
        $num = Db::table('co_bang_cate')->where('id',$number)->value('def_num');
        if ($datas != 0){
            if ($number == 7){
                $list['record'] = $num * $datas.'台';
            }else{
                $list['record'] = $num * $datas.'L';
            }
        }else{
            $list['record'] = 0;
        }
        return $list;
    }
    /**
     * @author lucky
     * 期初授信 授信库存
     * @return   [type]
     */
   public function ration(){
        $list = Db::table('ca_ration ar')
                ->join('co_bang_cate bc','ar.materiel=bc.id')
                ->join('ca_increase i','i.aid = ar.aid')
                ->field('bc.id,bc.name,bc.def_num*i.regions as ration,ar.materiel_stock')
                ->where('ar.aid',$this->aid)
                ->order('i.id asc')
                ->limit(5)
                ->select();
        foreach ($list as $key=>$value){
            if ($list[$key]['id'] == 7){
                $list[$key]['ration'] = $list[$key]['ration'].'台';
                $list[$key]['materiel_stock'] = $list[$key]['materiel_stock'].'台';
            }else{
                $list[$key]['ration'] = $list[$key]['ration'].'L';
                $list[$key]['materiel_stock'] = $list[$key]['materiel_stock'].'L';
            }
        }
        $count = count($list);
        $list = $this->sort($list,$count,'id');
        if($list){
            $this->result($list,'1','获取成功');
        }else{
            $this->result('','0','暂无数据');
        }
    }
    public function sort($list,$count,$mold){
        //把距离最小的放到前面
        //双重for循环, 每循环一次都会把一台最大值放最后
        for ($i = 0; $i < $count - 1; $i++) 
        {   
            //由于每次比较都会把一台最大值放最后, 所以可以每次循环时, 少比较一次
            for ($j = 0; $j < $count - 1 -  $i; $j++) 
            {   
                if ($list[$j]["$mold"] > $list[$j + 1]["$mold"]) 
                {
                    $tmp = $list[$j];
                    $list[$j] = $list[$j + 1];
                    $list[$j + 1] = $tmp;
                }
            }
        }
        return $list;
    }


    /**
     * 运营商列表
     * @return [type] [description]
     */
    public function agentList()
    {
        $id = input('post.id');
        $search = input('post.search');
        $data = [['company'=>'全部运营商','aid'=>0]];
        // 查询该省得所有市
        $city = Db::table('co_china_data')->where('pid',$id)->column('id');
        // 查询所有县
        $county = Db::table('co_china_data')->whereIn('pid',$city)->column('id');
        // 查询供应所有县的运营商id
        $aid = Db::table('ca_area')->whereIn('area',$county)->column('aid');
        if($aid){
            if(!empty($search)){
                // echo 1;exit;
                // 查询所有运营商公司名称及id
                $arr = Db::table('ca_agent')->whereIn('aid',$aid)->where('company','like','%'.$search.'%')->field('company,aid')->select();
                foreach ($arr as $k => $v) {
                    $data[] = $v;
                }
                $this->result($data,1,'获取运营商列表成功');
            }else{
                 // 查询所有运营商公司名称及id
                $arr = Db::table('ca_agent')->whereIn('aid',$aid)->field('company,aid')->select();
                foreach ($arr as $k => $v) {
                    $data[] = $v;
                }
                $this->result($data,1,'获取运营商列表成功');
            }
            
        }else{
            $this->result($data,0,'该省暂无运营商');
        }
    }


        /**
     * 邦保养关注度
     * @return [type] [description]
     */
    public function maint()
    {
        // 查询u_user表  获取关注邦保养小程序的人数
        $count = Db::table('u_user')->count();
        if($count > 0){
            $this->result($count,1,'获取关注度成功！');
        }else{
            $this->result(0,0,'暂无关注！');
        }
    }


    /**
     * 邦保养参与度
     * @var string
     */
    public function partDegree()
    {
        // 查询u_card 获取用户购卡信息
        $count = $this->card();
        if($count > 0){
            $this->result($count,1,'获取参与度成功！');
        }else{
            $this->result(0,0,'暂无参与度！');
        }
    }

     /**
     * 全国售卡总量
     * @return [type] [description]
     */
    private function card()
    {
        return Db::table('u_card')->where('pay_status',1)->count();
    }

}