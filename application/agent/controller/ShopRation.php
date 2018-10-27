<?php
namespace app\agent\controller;
use app\base\controller\Agent;
use think\Db;
/**
* 修理厂配给
*/
class ShopRation extends Agent
{
	/**
	 * 修车厂配给列表
	 * @return [type] [description]
	 */
	public function index()
	{	
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$where=[['aid','=',$this->aid],['audit_status','=',2]];
		$count = Db::table('cs_shop')->where($where)->count();
		$rows = ceil($count / $pageSize);

		$list=Db::table('cs_shop')->where($where)->page($page,$pageSize)->select();
		if($count > 0){                   
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 提高修车厂配给
	 * @return [type] 
	 */
	public function incRation()
	{	
		$sid=input('post.sid');
		Db::startTrans();
		// 判断运营商可开通修车厂大于0
		$shop_nums = Db::table('ca_agent')->where('aid',$this->aid)->value('shop_nums');
		if($shop_nums <= 0 ){
			$this->result('',0,'您可开通修车厂为 0,不可将修车厂配给提高！');
		}
		// 运营商可开通修车厂名额减少一个，已开通修车厂名额增加一个
		// 运营商开通修车厂库存减少一组
		if($this->credit($this->aid) == true){
			// 记录修车厂提高配给
			if($this->recordRation($sid,$this->aid) == true && $this->shopInc() == true){
				// 增加修车厂配给和库存量
				if($this->addRation($sid)==true){
					Db::commit();
					$this->result('',1,'操作成功');
				}else{
					Db::rollback();
					$this->result('',0,'操作失败');
				}
			}else{
				Db::rollback();
				$this->result('',0,'记录修车厂提高配给失败');
			}
		}else{
			Db::rollback();
			$this->result('',0,'运营商减少库存失败');
		}

	}



	/**
	 * 记录修车厂提高配给
	 * @param [type] $sid 修车厂id
	 */
	public function recordRation($sid,$aid)
	{
			$arr=['sid'=>$sid,'aid'=>$aid];
		$res=Db::table('cs_increase')->json(['record'])->insert($arr);
		if($res){
			return true;
		}
	}



	/**
	 * 增加修车厂配给和库存
	 * @param [type] $sid [description]
	 */
	private function addRation($sid)
	{
		$arr=$this->bangCate();
		foreach ($arr as $k => $v) {
			$where=[['sid','=',$sid],['materiel','=',$v['id']]];
			$res=Db::table('cs_ration')
				->where($where)
				->inc('stock',$v['def_num'])
				->inc('ration',$v['def_num'])
				->update();
		}
		if($res !== false){
			return true;
		}
	}

	
		/**
	 * 开通维修厂奖励运营商1000元  
	 * @return [type] [description]
	 */
	public function shopInc()
	{
		//查看开发奖励剩余次数
		$devel = Db::table('ca_agent')->where('aid',$this->aid)->value('devel');
		if($devel > 0){
			// 减少开发奖励次数,增加开放奖励金额，增加总余额
			$develNum = Db::table('ca_agent')
						->where('aid',$this->aid)
						->dec('devel',1)
						->inc('awards',1000)
						->inc('balance',1000)
						->update();
			if($develNum !== false){
				return true;
			}	
		}else{
			// 不增加运营商余额
			return true;
		}
	}
	/**
	 * 开通维修厂奖励列表 8月15日孙烨兰修改
	 * @return [type] [description]
	 */
	public function awards_list(){
		$list = Db::table('cs_increase ci')
				->join('cs_shop cs','cs.id = ci.sid')
				->where('cs.aid',$this->aid)
				->where('audit_status',2)
				->order('cs.audit_time asc')
				->field('company,phone,cs.audit_time as create_time')
				->select();

		$arr = Db::table('cs_increase ci')
				->join('cs_shop cs','cs.id = ci.sid')
				->where('cs.aid',$this->aid)
				->where('audit_status',2)
				->field('company,phone,ci.create_time')
				->order('ci.create_time asc')
				->select();

		foreach ($list as $k => $v) {
			$list[$k]['create_time'] =date('Y-m-d H:i:s', $list[$k]['create_time']);
		}
		$list =array_merge($list,$arr);
		$count = count($list); 
		$list = $this->sorts($list,$count);
		foreach ($list as $key => $value) {
			$list[$key]['awards'] = 1000;
		}
		$list = array_slice($list,0,5);
		if($list){
			$this->result($list,'1','获取成功');
		}else{
			$this->result('','0','暂无数据');
		}
	}
	/**
	 * 根据距离排序
	 * @return [type] [description]
	 *
	 */
	public function sorts($list,$count){
		//把距离最小的放到前面
		//双重for循环, 每循环一次都会把一个最大值放最后
		for ($i = 0; $i < $count - 1; $i++) 
		{	
			//由于每次比较都会把一个最大值放最后, 所以可以每次循环时, 少比较一次
			for ($j = 0; $j < $count - 1 -  $i; $j++) 
			{	
				if ($list[$j]['create_time'] > $list[$j + 1]['create_time']) 
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
	 * 罚款列表 8月12日孙烨兰修改
	 * @return [type] [description]
	 */
	public function agent_msg(){
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('cs_apply_materiel ca')
				->join('cs_shop cp','ca.sid=cp.id')
				->where('ca.aid',$this->aid)
				->whereTime('com_time','>',strtotime('create_time'))
				->count();
				
		$rows = ceil($count / $pageSize);
		$list = Db::table('cs_apply_materiel ca')
				->join('cs_shop cp','ca.sid=cp.id')
				->where('ca.aid',$this->aid)
				->whereTime('com_time','>',strtotime('create_time'))
				->field('company,phone,com_time')
				->page($page,$pageSize)
				->select();
		foreach ($list as $key => $value) {
			$list[$key]['com_time'] =date('Y-m-d H:i:s', $list[$key]['com_time']);
			$list[$key]['fine'] = 200;
		}
		if($list){
			$this->result(['list'=>$list,'rows'=>$rows],'1','获取成功');
		}else{
			$this->result('','0','暂无数据');
		}
	}




}