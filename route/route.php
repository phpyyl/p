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

Route::get('think', function () {
    return 'hello,ThinkPHP5!';
});

Route::get('hello/:name', 'index_controller/hello');

// 后台主页路由
Route::get('admin/index', 'admin/IndexController/index')->name('admin.index.index');

// 后台运动员管理
// 运动员列表
Route::get('admin/athlete/index', 'admin/Athlete/index')->name('admin.athlete.index');
// 运动员添加页面
Route::get('admin/athlete/create', 'admin/Athlete/create')->name('admin.athlete.create');
// 运动员添加提交
Route::post('admin/athlete/save', 'admin/Athlete/save')->name('admin.athlete.save');
// 运动员修改页面
Route::get('admin/athlete/edit/:id', 'admin/Athlete/edit')->name('admin.athlete.edit');
// 运动员修改提交
Route::post('admin/athlete/update/:id', 'admin/Athlete/update')->name('admin.athlete.update');
// 运动员删除
Route::get('admin/athlete/delete/:id', 'admin/Athlete/delete')->name('admin.athlete.delete');

// 后台比赛管理
// 比赛列表
Route::get('admin/match/index', 'admin/match/index')->name('admin.match.index');
// 比赛添加页面
Route::get('admin/match/create', 'admin/match/create')->name('admin.match.create');
// 比赛添加提交
Route::post('admin/match/save', 'admin/match/save')->name('admin.match.save');
// 比赛修改页面
Route::get('admin/match/edit/:id', 'admin/match/edit')->name('admin.match.edit');
// 比赛修改提交
Route::post('admin/match/update/:id', 'admin/match/update')->name('admin.match.update');
// 比赛删除
Route::get('admin/match/delete/:id', 'admin/match/delete')->name('admin.match.delete');
// 比赛上线
Route::post('admin/match/stateup/:id', 'admin/match/stateUp')->name('admin.match.stateup');
// 比赛下线
Route::get('admin/match/statedown/:id', 'admin/match/stateDown')->name('admin.match.statedown');

// 后台比赛数据管理
// 比赛数据列表
Route::get('admin/matchdata/index', 'admin/MatchData/index')->name('admin.matchdata.index');
// 比赛数据列表(ajax请求)
Route::post('admin/matchdata/index', 'admin/MatchData/index')->name('admin.matchdata.index');
// 比赛数据添加页面
Route::get('admin/matchdata/create', 'admin/MatchData/create')->name('admin.matchdata.create');
// 比赛数据添加提交
Route::post('admin/matchdata/save', 'admin/MatchData/save')->name('admin.matchdata.save');

// 比赛数据批量添加界面
Route::get('admin/matchdata/createbat', 'admin/MatchData/createBat')->name('admin.matchdata.createbat');
// 比赛数据批量添加提交
Route::post('admin/matchdata/savebat', 'admin/MatchData/saveBat')->name('admin.matchdata.savebat');

// excel上传
Route::post('admin/matchdata/upload', 'admin/MatchData/upload')->name('admin.matchdata.upload');
// excel下载
Route::get('admin/matchdata/download', 'admin/MatchData/download')->name('admin.matchdata.download');


// 比赛数据修改页面
Route::get('admin/matchdata/edit/:id', 'admin/MatchData/edit')->name('admin.matchdata.edit');
// 比赛数据修改提交
Route::post('admin/matchdata/update/:id', 'admin/MatchData/update')->name('admin.matchdata.update');
// 比赛数据删除
Route::get('admin/matchdata/delete/:id', 'admin/MatchData/delete')->name('admin.matchdata.delete');
// 二级联动查询某场比赛运动员
Route::get('admin/matchdata/join/:id', 'admin/MatchData/join')->name('admin.matchdata.join');


// 后台成绩管理
// 比分首页数据展示
Route::get('admin/matchscore/index', 'admin/MatchScore/index')->name('admin.matchscore.index');
Route::post('admin/matchscore/index', 'admin/MatchScore/index')->name('admin.matchscore.index');


// 前台首页显示
Route::get('web/index/index', 'web/Index/index')->name('web.index.index');
Route::post('web/index/index', 'web/Index/index')->name('web.index.index');

// 前台详情页显示
Route::get('web/index/read/:id', 'web/Index/read')->name('web.index.read');
// 前台获取单局数据
Route::post('web/index/getone', 'web/Index/getone')->name('web.index.getone');

return [

];
