<?php
namespace  app\agent\controller;
use app\base\controller\Agent;
use think\Db;
/**
* 运营商向后台取消合作模块
*/
class CancelCoop extends Agent
{
	
	function initialize()
	{
		parent::initialize();
	}

	/**
	 * 取消合作操作
	 * @return [type] [description]
	 */
	public function index()
	{	
		// 获取取消合作理由
		$reason = input('post.reason');
		if($reason){

			$data = $this->aDatil($this->aid);
			$data['reason'] = $reason;
			$data['aid']=$this->aid;
			if(Db::table('ca_apply_cancel')->json(['region'])->insert($data)){
				$this->result('',1,'取消合作申请成功,等待总后台审核');
			}else{
				$this->result('',0,'取消合作失败');
			}

		}else{
			$this->result('',0,'取消合作理由不能为空');
		}
		
		
	}


	/**
	 * 获取运营商公司名、负责人、所管辖地区id
	 * @param  [type] $aid [ 运营商id ]
	 * @return [type]      [description]
	 */
	public function aDatil($aid)
	{
		// 运营商公司名负责人
		$data=Db::table('ca_agent')->where('aid',$aid)->field('company,leader')->find();
		// 运营商管辖区县id
		$area=Db::table('ca_area')->where('aid',$aid)->column('area');
		$data['region']=$area;
		return $data;
		
	}


	
}