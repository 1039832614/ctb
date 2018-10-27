<?php 
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
use Pay\WorkerEpay;
/**
 * 技师文章管理
 */
class WorkerArticle extends Admin
{
	/**
	 * 技师文章未审核列表
	 */
	public function unCheckList()
	{
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('tn_article')
				 ->where('status',0)
				 ->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('tn_article')
				->alias('a')
				->join('tn_user u','a.uid = u.id')
				->where('a.status',0)
				->order('audit_time desc')
				->page($page,$pageSize)
				->field('aid,title,thumb,status,content,u.id as uid,u.name,u.nick_name,mold,create_time')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		} else {
			$this->result('',0,'暂无数据');
		}
	}
	/**
	 * 技师文章已审核列表
	 */
	public function checkList()
	{
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('tn_article')
				 ->where([
				 	'status' => [1,2,3]
				 ])
				 ->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('tn_article')
				 ->alias('a')
				 ->join('tn_user u','a.uid = u.id')
				 ->where([
				 	'a.status' => [1,2,3]
				 ])
				 ->order('audit_time desc')
				 ->page($page,$pageSize)
				 ->field('aid,title,thumb,content,u.name,u.nick_name,mold,create_time,status,audit_time')
				 ->select();
		if($count > 0){
			foreach ($list as $key => $value) {
				if($list[$key]['status'] == 2){
					$list[$key]['reward'] = Db::table('tn_worker_reward')
							->where('type',2)
							->where('acid',$value['aid'])
							->value('reward');
				} else {
					$list[$key]['reward'] = null;
				}
			}
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		} else {
			$this->result('',0,'暂无数据');
		}

	}
	/**
	 * 技师文章通过操作
	 */
	public function passArticle()
	{
		$aid = input('post.aid');
		$res = Db::table('tn_article')
				->where('aid',$aid)
				->update(['status' => '1','audit_time'=>time()]);
		if($res) {
			// 日志写入
			$company = Db::table('tn_article')
				       ->where('aid',$aid)
				       ->value('uid');
		    $company = Db::table('tb_user')->where('id',$company)->value('name');		       
			$GLOBALS['err'] = $this->ifName().'通过了【'.$company.'】的文章';
			$this->estruct(); 
			$this->result('',1,'已通过');
		} else {
			$this->result('',0,'操作失败');
		}
	}
	/**
	 * 技师文章推荐操作
	 */
	public function recommendArticle()
	{
		$data = input('post.');
		$validate = validate('WorkerArticleMoney');
		if($validate->check($data)){
			Db::startTrans();
			$res = Db::table('tn_article')
					->where('aid',$data['aid'])
					->update(['status'=>'2','audit_time'=>time()]);
			//构建技师成长基金记录
			$trade_no = build_only_sn();
			$info = [
				'wid'         => $data['uid'],
				'mold'        => $data['mold'],
				'type'        => 2,
				'acid'        => $data['aid'],//文章id
				'reward'      => $data['money'],
				'trade_no'    => $trade_no,
				'create_time' => time()
			];
			//技师文章奖励金入库
			$worker_re = Db::table('tn_worker_reward')
								->insert($info);
			if($res && $worker_re) {
				Db::commit();
				// 获取技师的openid
				$openid = Db::table('tn_user')->where('	id',$data['uid'])->value('openid');
				$epay = new WorkerEpay();
			    $epay->dibs($trade_no,$openid,$data['money']*100,'技师文章推荐奖励金');
				$this->result('',1,'提交成功，奖励金会发放到技师的微信余额中');
			} else {
				Db::rollback();
				$this->result('',0,'提交失败');
			}
		} else {
			$this->result('',0,$validate->getError());
		}	
	}
	/**
	 * 技师文章驳回操作
	 */
	public function rejectArticle()
	{
		$aid = input('post.aid');
		$res = Db::table('tn_article')
				->where('aid',$aid)
				->update(['status' => '3','audit_time'=>time()]);
		if($res) {
			$this->result('',1,'已驳回');
		} else {
			$this->result('',0,'驳回失败');
		}
	}

	/**
	 * 状态复原
	 * @return [type] [description]
	 */
	public function togo()
	{
		$aid = input('post.aid');
		$res = Db::table('tn_article')
				->where('aid',$aid)
				->setField('status','0');
		if($res){
			$this->result('',1,'复原成功');
		} else {
			$this->result('',0,'复原失败');
		}
	}
}