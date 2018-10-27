<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 服务经理奖励设置
*/
class SmSet extends Admin
{
		/*
		 *服务经理/运营总监奖励设置奖励设置
		 */
		public function set()
        {
            // team_reward 团队奖励
            // devel_reward 开发奖励
            // task_reward 任务奖励
            // task_num  任务数量
            // maid 分佣百分比
            // status  1服务经理  2运营总监
            $data = input('post.');
            $validate = validate('SmSet');
            unset($data['token']);
            if($validate->check($data)){
                // 查询 是否有该状态的数据
                $count = Db::table('am_sm_set')->where('status',$data['status'])->count();
                if ($count > 0){
                    // 修改 该状态下的数据
                    $arr = [
                        'team_reward' => $data['team_reward'],
                        'devel_reward' => $data['devel_reward'],
                        'task_reward' => $data['task_reward'],
                        'task_num' => $data['task_num'],
                        'maid' => $data['maid']
                    ];
                    $res = Db::table('am_sm_set')
                        ->where('status',$data['status'])
                        ->update($arr);
                    if ($res !== false){
                        $this->result('',1,'设置成功');
                    }else{
                        $this->result('',0,'设置失败');
                    }
                }else{
                    $res = Db::table('am_sm_set')->insert($data);
                    if($res){
                        $this->result('',1,'设置成功');
                    }else{
                        $this->result('',0,'设置失败');
                    }
                }
            } else {
                $this->result('',0,$validate->getError());
            }
        }


		/**
		 * 奖励设置默认值
		 */
		public function setList()
		{
			$list = Db::table('am_sm_set')->select();
			if($list){
				$this->result($list,1,'获取奖励内容成功');
			}else{
				$this->result('',0,'暂无内容');
			}
		}

}