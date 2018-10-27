<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
/**
 * 公共路由
 */

// 获取省级城市
Route::post('province','base/Base/province');
// 获取市级城市 @param pid
Route::post('city','base/Base/city');
Route::post('base/Base/city','base/Base/city');
Route::get('base/Base/province','base/Base/province');


//市级代理模块
Route::group('supply',function(){
  //市级代理列表
  Route::post('index','admin/SupplyList/index');
  // 运营商数量详情
  Route::post('agent','admin/SupplyList/agentNum');
  // 区域详情
  Route::post('region','admin/SupplyList/region');
  // 邦保养卡详情
  Route::post('card','admin/SupplyList/cardNum');

  //申请物料
  // 申请列表
  Route::post('apply','admin/SupplyMateriel/apply');
  // 通过列表
  Route::post('adoptList','admin/SupplyMateriel/adoptList');
  // 驳回列表
  Route::post('rejectList','admin/SupplyMateriel/rejectList');
  // 物料详情
  Route::post('detail','admin/SupplyMateriel/detail');
  // 物料申请通过
  Route::post('adoptMateriel','admin/SupplyMateriel/adopt');
  // 物料申请驳回
  Route::post('matReject','admin/SupplyMateriel/reject');
  // 物料申请通过列表 打印按钮
  // Route::post('reject','admin/SupplyMateriel/reject');
  // 取消合作申请列表
  Route::post('canApply','admin/SupplyCancel/cancelApply');
  // 取消合作通过列表
  Route::post('canAdopt','admin/SupplyCancel/cancelAdopt');
  // 取消合作驳回列表
  Route::post('canRej','admin/SupplyCancel/cancelReject');
  // 取消列表滑动公司名称显示详情
  Route::post('company','admin/SupplyCancel/company');
  // 取消列表 取消合作理由
  Route::post('canReason','admin/SupplyCancel/canReason');
  // 取消合作驳回理由
  Route::post('canReject','admin/SupplyCancel/reject');
  // 取消合作通过
  Route::post('adoptPost','admin/SupplyCancel/adopt');
  // 取消合作驳回
  Route::post('rejectPost','admin/SupplyCancel/reject');
  // 取消合作物品交接
  Route::post('handGood','admin/SupplyCancel/handGood');



  // 资金提现申请列表
  Route::post('forApply','admin/SupplyForward/forApply');
  // 资金提现通过列表
  Route::post('forAdoptList','admin/SupplyForward/forAdopt');
  // 资金提现驳回列表
  Route::post('forRejectList','admin/SupplyForward/forReject');
  // 收入明细  物料收入
  Route::post('matPrice','admin/SupplyForward/matPrice');
  // 收入明细  售卡收入
  Route::post('cardPrice','admin/SupplyForward/cardPrice');
  // 资金管理  提现明细
  Route::post('putForward','admin/SupplyForward/putForward');
  // 资金管理  提现审核
  Route::post('auditList','admin/SupplyForward/auditList');
  // 资金管理  审核通过操作
  Route::post('forAdopt','admin/SupplyForward/adopt');
  // 资金管理  审核驳回操作
  Route::post('forRej','admin/SupplyForward/reject');
  // 资金管理  查看驳回理由
  Route::post('rejReason','admin/SupplyForward/rejReason');
  // 资金管理  查看驳回理由
  Route::post('forReason','admin/SupplyForward/rejReason');


  // 添加地区
  //申请列表
  Route::post('areaApp','admin/SupplyArea/appList');
  //通过列表
  Route::post('areaAd','admin/SupplyArea/adList');
  //驳回列表
  Route::post('areaRej','admin/SupplyArea/rejList');
  //地区详情
  Route::post('areaRegion','admin/SupplyArea/region');
  //添加地区详情
  Route::post('areaList','admin/SupplyArea/addList');
  //添加地区物料详情
  Route::post('areaMat','admin/SupplyArea/materiel');
  // //添加地区查看驳回理由
  // Route::post('areaRea','admin/SupplyArea/reaDetail');
  //添加区域通过操作
  Route::post('areaPost','admin/SupplyArea/adopt');
  //添加区域驳回操作
  Route::post('reject','admin/SupplyArea/reject');

});



// 数据分析
Route::group('analy',function(){
	// 邦保养关注度
	Route::post('maint','admin/DataAnaly/maint');
	// 邦保养参与度
	Route::post('part','admin/DataAnaly/partDegree');
	// 市级代理全国总数量
	Route::post('agent','admin/DataAnaly/agent');
	// 运营商全国总数量
	Route::post('oper','admin/DataAnaly/operator');
	// 维修厂全国总数量
	Route::post('shop','admin/DataAnaly/shop');
	// 全国车主数量
	Route::post('user','admin/DataAnaly/usercard');
	// 邦保养服务次数
	Route::post('service','admin/DataAnaly/serviceNum');
	// 交易总额
	Route::post('price','admin/DataAnaly/cardPrice');
	// 参与邦保养平均值
	Route::post('avera','admin/DataAnaly/partAvera');
	// 参与售卡平均值
	Route::post('pravera','admin/DataAnaly/priceAvera');
	// 地图页面
	// 地图首页（省名称id）
	Route::post('map','admin/Map/index');
	//鼠标滑动某某省显示该省数据（市级代理、运营商、维修厂、车主、参与次数平均值、售卡平均值） 
	Route::post('province','admin/Map/deliNum');
	//该省交易总额按月 
	Route::post('total','admin/Map/total');
	//该省售卡总数按月
	Route::post('card','admin/Map/card');
	//该省服务次数  按月
	Route::post('serMonth','admin/Map/service');
	//该省好评次数  按月
	Route::post('praise','admin/Map/praiseNum');
	//该省物料消耗 按月
	Route::post('material','admin/Map/material');
	//该省复购次数 按月
	Route::post('repeatTime','admin/Map/repeatTime');
	//该省市级代理售卡平均值 按月
	Route::post('munCard','admin/Map/munCard');
	//该省运营商售卡平均值 按月
	Route::post('agCard','admin/Map/agentCard');
	//该省维修厂售卡平均值 按月
	Route::post('shCard','admin/Map/shopCard');
	//该省市级代理交易金额平均值 按月
	Route::post('munPrice','admin/Map/munPrice');
	//该省运营商交易金额平均值 按月
	Route::post('agPrice','admin/Map/agentPrice');
	//该省维修厂交易金额平均值 按月
	Route::post('shPrice','admin/Map/shopPrice');


});


/**
 * 公共路由
 */

// // 获取省级城市
// Route::post('province','base/Base/province');
// // 获取市级城市 @param pid
// Route::post('city','base/Base/city');


/**
 * 微信小程序 大转盘>>>赠品路由
 */
Route::group('Gift',[
   // 大转盘赠品图片上传
   'file'   => 'admin/Giftpro/GiftFile', 	
   // 大转盘赠品上传
   'add'    => 'admin/Giftpro/UploadGift', 
   // 大转盘赠品列表
   'list'   => 'admin/Giftpro/GiftShow', 
   // 大转盘赠品修改获取默认值
   'check'  => 'admin/Giftpro/GitfCheck',   
   // 大转盘赠品修改
   'update' => 'admin/Giftpro/GitfSave',     
   // 大转盘赠品删除
   'delete' => 'admin/Giftpro/GitfDel',     
]);
/**
 * 微信小程序 大转盘>>>中奖信息
 */
Route::group('Luck',[
   // 大转盘中奖信息添加
   'add'   => 'admin/Giftpro/LuckPost', 	
   // 大转盘中奖信息未发货
   'start' => 'admin/Giftpro/LuckNot', 
   // 大转盘中奖信息已发货
   'end'   => 'admin/Giftpro/LuckAlear', 
   // 大转盘中奖信息导出Excel
   'excel' => 'admin/Giftpro/Export', 

]);
/**
 * 供应商数据分析 
 */
Route::group('ForAnalysis',[
    // 根据地区获取供应商列表
    'area'  => 'supply/Regview/suppArea',
    // 根据地区获取供应商列表 搜索
    'sera'  => 'supply/Regview/suppSer',
/**
 * 区域汇总 
 */
    // 邦保养关注度
    'Followview'    => 'supply/Followview/ProFollow',
    // 邦保养参与度
    'close'         => 'supply/Followview/ProUse',
    // 区域汇总 交易金额
    'reg/price'     => 'supply/Regview/CooList',
    // 每月售卡总额
    'reg/salem'     => 'supply/Regview/cardPrice',
    // 每月总额
    'reg/maxprice'     => 'supply/Regview/MaxPrice',
    // 区域汇总 售卡总数
    'reg/card'      => 'supply/Regview/SaleCard',
    // 区域汇总 维修厂数量
    'reg/scale'     => 'supply/Regview/FactorySize',
    // 区域汇总 复购数
    'reg/repeat'    => 'supply/Regview/RepeatSize',       // 未完成
    // 区域汇总 消耗物料
    'reg/consume'   => 'supply/Regview/Consume',    
    // 区域汇总 服务次数
    'reg/service'   => 'supply/Regview/Service',
    // 区域汇总 好评次数
    'reg/good'      => 'supply/Regview/GoodSize',
    // 区域汇总 运营商汇总
    'reg/operator'  => 'supply/Regview/OperatorList',
                             
/**
 * 资金详情 
 */    
    // 资金 资金总览
    'price/price'   => 'supply/Followview/PriceAll', 
// 资金 资金总览2
    'price/prices'   => 'supply/Followview/PriceAll',
    // 售卡详情
    'price/card'    => 'supply/Followview/CardDetails',
    // 提现详情 
    'price/put'     => 'supply/Followview/PutDetails',
/**
 * 物料库
 */ 
    // 供应库存
    'posit/gstock'  => 'supply/Stockview/GStock',
    // 期初配给
    'posit/stageStock'  => 'supply/Stockview/StageStock',
    // 物料配送
    'posit/supp'  => 'supply/Stockview/SuppStock',
    // 物料补充
    'posit/lement'  => 'supply/Stockview/Lement',
    // 物料库存
    'posit/wstock'  => 'supply/Stockview/wstock',
    // 增加配给
    'posit/incos'  => 'supply/Stockview/IconS',
    // 增加配给
    'posit/inCon'  => 'supply/Stockview/inCon',
    // 业务排名
    'posit/buslist'  => 'supply/Regview/OperatorList',
    // 业务排名
    'posit/busorder'  => 'supply/Regview/OperatOrder',
    // 业务排名搜索
    'posit/busser'  => 'supply/Regview/SaleSearch',
]);

/**
 * 维修厂数据分析 
 */

Route::group('Operate',[
/**
 *  单一维修厂 数据分析
 */ 
    // 根据地区获取维修厂列表
    'area'  => 'admin/Shopview/Wlist',
    // 根据地区获取维修厂列表  搜索
    'eara'  => 'admin/Shopview/ccc',
    // 关注度 or 参与度 
    'single/Followview' => 'admin/Shopview/Follow',
    // 资金详情
    'single/Price'      => 'admin/Shopview/PriceDetails',
    // 技师 服务次数
    'single/service'    => 'admin/Shopview/TechSum',
    // 技师 礼品兑换
    'single/Gift'       => 'admin/Shopview/GiftSum',
    // 技师 服务奖励
    'single/TechPrice'  => 'admin/Shopview/TechPrice',
    // 技师 文章推荐奖励
    'single/Push'       => 'admin/Shopview/TechPush',
    // 授信物料
    'single/Credit'     => 'admin/Shopview/CreditSum',            // 暂无法测试
    // 授信物料 -- 期初配给
    'single/sou'        => 'admin/Shopview/Rationum',
    // 授信物料 -- 物料消耗
    'single/consume'        => 'admin/Shopview/Materielsume',
    // 授信物料 -- 物料补充
    'single/lement'         => 'admin/Shopview/MaterielSupp',   
    // 授信物料 -- 物料剩余
    'single/surplus'        => 'admin/Shopview/MaterialSurplus',  
    // 授信物料 -- 增加授信
    'single/Cadd'           => 'admin/Shopview/CreditAdd',     
    // 会员详情 -- 购卡一次
    'single/Cardone'        => 'admin/Shopview/ShopOne', 
    'single/shopFour'        => 'admin/Shopview/ShopOne', 
    // 会员详情 -- 参与次数
    'single/PartSum'        => 'admin/Shopview/PartSum',       
    // 会员详情 -- 剩余服务次数
    'single/OverSum'        => 'admin/Shopview/OverSum',     
    // 车主详情
    'single/UserInfo'       => 'admin/Shopview/UserInfo', 
    // 复购
    'single/ReSum'        => 'admin/Shopview/ReSum',
    // 复购详情
    'single/DetRes'   => 'admin/Shopview/ReSumDetails',
    // 柱形图 售卡总额
    'package/ToCardPrice'   => 'admin/Shopview/ToCardPrice',  
    // 柱形图 售卡总数
    'package/ToCardSum'   => 'admin/Shopview/ToCardSum',  
    // 柱形图 服务次数
    'package/ToServeSum'   => 'admin/Shopview/ToServeSum',  
    // 柱形图 好评次数
    'package/GoodSum'   => 'admin/Shopview/GoodSum',  
    // 柱形图 物料消耗
    'package/consume'       => 'admin/Shopview/StageSum',   
    // 柱形图 复购次数
    'package/ToRepu'       => 'admin/Shopview/ToRepu',     
    // 柱形图 售卡平均
    'package/ToCardFalt'       => 'admin/Shopview/ToCardFalt',  
    // 柱形图 金额平均
    'package/ToPricFalt'       => 'admin/Shopview/ToPricFalt',            
]);


/*
 *   服务经理
 * 
 */

Route::group('Smoc',[
   // 注册审核
   '/regAudit' => 'admin/Smoc/regAudit',
   // 列表详情
   '/regDetail' => 'admin/Smoc/regDetail',
   // 列表通过默认值
   '/checkREg' => 'admin/Smoc/checkREg',
   // 列表通过
   '/regPass' => 'admin/Smoc/regPass',
   // 列表驳回
   '/regDown' => 'admin/Smoc/regDown',
   // 增加地区审核列表
   '/addAudit' => 'admin/Smoc/addAudit',
   // 取消地区审核列表
   '/callAudit' => 'admin/Smoc/callAudit',
   // 取消地区理由详情
   '/callReason' => 'admin/Smoc/callReason',
   // 取消地区 驳回操作
   '/rejReason' => 'admin/Smoc/rejReason',
   // 取消地区审核通过
   '/callPass' => 'admin/Smoc/callPass',

   // 服务经理列表
   '/list'     => 'admin/SmocList/list',
   // 服务经理权限区域
   '/jurList'     => 'admin/SmocList/jurList',
   // 服务经理收益
   '/prcList'     => 'admin/SmocList/prcList',
   // 服务经理设置默认值 
   '/upCheck'    => 'admin/SmocList/upCheck',
   // 服务经理设置 
   '/upCent'    => 'admin/SmocList/upCent',
   // 服务经理取消区域默认值 
   '/calCent'    => 'admin/SmocList/calCent',
   // 服务经理取消区域 操作
   '/calSa'    => 'admin/SmocList/calSa',

   // 运营总监列表
   '/DicList'   => 'admin/SmocList/DicList',
   // 运营总监列表 查看团队
   '/itemDet'   => 'admin/SmocList/itemDet',
   // 运营总监列表 查看团队成员
   '/sonList'   => 'admin/SmocList/sonList',
   // 运营总监列表 投诉详情
   '/comList'   => 'admin/SmocList/comList',
   // 运营总监列表 投诉详情查看投诉内容
   '/clde'   => 'admin/SmocList/clde',
   // 运营总监列表 收益列表
   '/sPlist'   => 'admin/SmocList/sPlist',
   // 运营总监列表 分佣设置默认值
   '/pcheckSav'   => 'admin/SmocList/pcheckSav',
   // 运营总监列表 分佣设置操作
   '/priceSav'   => 'admin/SmocList/priceSav',
   // 运营总监列表 取消
   '/quxiao'   => 'admin/SmocList/quxiao',
   // 运营总监列表 投诉
   '/tousu'   => 'admin/SmocList/tousu',
   // 运营总监列表 列表
   '/xcl'   => 'admin/SmocList/clde',
   // 服务经理投诉 投诉详情
   '/tsDetail'=>'admin/SmocList/tsDetail',

  // 未知详情
   'xqs'  => 'admin/SmocList/xia',
  // 定时
  'dingshi' => 'admin/SmocList/dingshi',

  // 提现列表
  'putList' => 'admin/Smoc/putList',
  // 提现详情
  'dePut'  => 'admin/SmocList/dePut',
  // 提现驳回
  'turnPut' => 'admin/Smoc/turnPut',
  // 提现通过
  'passPut' => 'admin/Smoc/passPut',
  // 暂停分佣　默认值
  'checkPaus' => 'admin/SmocList/checkPaus',
  // 暂停分佣　单个设置
  'pause' => 'admin/SmocList/pause',
  // 暂停分佣　全部设置
  'Allpause' => 'admin/SmocList/Allpause',
  // 消息提示
  'msgALl' => 'admin/SmocMsg/msgall',
  'ddd'   => 'admin/SmocList/ddd',  // 设置
  'aaa'   => 'admin/SmocList/aaa',  // 默认
]);