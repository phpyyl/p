<?php

namespace app\admin\controller;

use think\Controller;
use think\Exception;
use think\Request;
use app\admin\model\Match as Mat;
use think\Db;
use app\admin\model\Athlete;
use app\admin\model\BigSmallScore;
use app\admin\model\BanshuScore;
use app\admin\model\GameScore;
use app\admin\model\SkillScore;
use app\admin\model\MatchData;

class Match extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        // 连表获取运动员名称数据
        $data = Db::name('match')->alias('a')
            ->field('a.*, b.athlete_name as athlete_A, c.athlete_name as athlete_B')
            ->join('athlete b', 'a.athlete_id_a = b.id')
            ->join('athlete c', 'a.athlete_id_b = c.id')
            ->paginate(5);

        // 模板赋值
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $athletes = Athlete::all();
        $this->assign('athletes', $athletes);
        return $this->fetch();
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        // 获取表单数据
        $data = $request->post();
        // 入库
        $res = Mat::create($data);
        if ($res) {
            $this->success('新增成功', 'admin/match/index', 3);
        } else {
            $this->error('新增失败');
        }
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        // 获取单条数据
        $data = Mat::find($id);
        // 获取运动员表
        $athletes = Athlete::all();

        // 模板赋值
        $this->assign([
            'data' => $data,
            'athletes' => $athletes
        ]);
        return $this->fetch();
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        // 获取表单数据
        $data = $request->post();
        // 入库
        $res = Mat::where('id', $id)->update($data);
        if ($res) {
            $this->success('修改成功', 'admin/match/index', 3);
        } else {
            $this->error('修改失败');
        }
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        $res = Mat::where('id', $id)->delete();
        if ($res) {
            $this->success('删除成功', 'admin/match/index', 3);
        } else {
            $this->error('删除失败');
        }
    }

    // 比赛上线
    public function stateUp(Request $request, int $id)
    {
        // 启动事务
         Db::startTrans();
         try {
            // 运动员A_id
            $athlete_id_a = Mat::find($id)->athlete_id_a;
            // 运动员B_id
            $athlete_id_b = Mat::find($id)->athlete_id_b;

            // 更改为上线状态
            Mat::where('id', $id)->update(['state' => '是']);

            // 前台表写入数据
            // 1、大小比分表
            // 获取大小比分数据（下标已定义好 同数据库字段）
            $data = $request->post();
            // 比赛id
            $data['match_id'] = $id;
            // 使用Db类入库
            BigSmallScore::create($data);

            // 2、各局得分表
            $datas = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->field('match_id, athlete_id, data_game_no, data_get_score, data_lose_score')
                ->order(['data_game_no'=>'asc', 'data_get_score'=>'asc'])
                ->select()->toArray();
            // 入库
            foreach ($datas as $data) {
                GameScore::create($data);
            }

            // 3、各技术项得分表
            // 运动员A各技术项得失分
            $data = [
                ['match_id' => $id, 'athlete_id' => $athlete_id_a, 'data_shouduan' => '正手', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_a, 'data_shouduan' => '反手', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_a, 'data_shouduan' => '控制', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_a, 'data_shouduan' => '发球', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_a, 'data_shouduan' => '侧身', 'allscore_get' => 0, 'allscore_lose' => 0],
            ];
            Db::name('skill_score')->insertAll($data);

            // 正手得分
            $zhengshou_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_shouduan', '正手')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($zhengshou_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_shouduan', '正手')
                    ->update(['allscore_get' => $zhengshou_score]);
            }

            // 反手得分
            $fanshou_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_shouduan', '反手')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($fanshou_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_shouduan', '反手')
                    ->update(['allscore_get' => $fanshou_score]);
            }

            // 控制得分
            $kongzhi_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_shouduan', '控制')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($kongzhi_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_shouduan', '控制')
                    ->update(['allscore_get' => $kongzhi_score]);
            }

            // 发球得分
            $faqiu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_shouduan', '发球')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($faqiu_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_shouduan', '发球')
                    ->update(['allscore_get' => $faqiu_score]);
            }

            // 侧身得分
            $ceshen_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_shouduan', '侧身')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($ceshen_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_shouduan', '侧身')
                    ->update(['allscore_get' => $ceshen_score]);
            }

            // 正手失分
            $zhengshou_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_shouduan', '正手')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($zhengshou_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_shouduan', '正手')
                    ->update(['allscore_lose' => $zhengshou_score]);
            }

            // 反手失分
            $fanshou_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_shouduan', '反手')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($fanshou_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_shouduan', '反手')
                    ->update(['allscore_lose' => $fanshou_score]);
            }

            // 控制失分
            $kongzhi_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_shouduan', '控制')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($kongzhi_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_shouduan', '控制')
                    ->update(['allscore_lose' => $kongzhi_score]);
            }

            // 发球失分
            $faqiu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_shouduan', '发球')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($faqiu_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_shouduan', '发球')
                    ->update(['allscore_lose' => $faqiu_score]);
            }

            // 侧身失分
            $ceshen_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_shouduan', '侧身')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($ceshen_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_shouduan', '侧身')
                    ->update(['allscore_lose' => $ceshen_score]);
            }

            // 运动员B各技术项得失分
            $data = [
                ['match_id' => $id, 'athlete_id' => $athlete_id_b, 'data_shouduan' => '正手', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_b, 'data_shouduan' => '反手', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_b, 'data_shouduan' => '控制', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_b, 'data_shouduan' => '发球', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_b, 'data_shouduan' => '侧身', 'allscore_get' => 0, 'allscore_lose' => 0],
            ];
            Db::name('skill_score')->insertAll($data);

            // 正手得分
            $zhengshou_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_shouduan', '正手')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($zhengshou_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_shouduan', '正手')
                    ->update(['allscore_get' => $zhengshou_score]);
            }

            // 反手得分
            $fanshou_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_shouduan', '反手')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($fanshou_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_shouduan', '反手')
                    ->update(['allscore_get' => $fanshou_score]);
            }

            // 控制得分
            $kongzhi_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_shouduan', '控制')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($kongzhi_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_shouduan', '控制')
                    ->update(['allscore_get' => $kongzhi_score]);
            }

            // 发球得分
            $faqiu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_shouduan', '发球')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($faqiu_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_shouduan', '发球')
                    ->update(['allscore_get' => $faqiu_score]);
            }

            // 侧身得分
            $ceshen_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_shouduan', '侧身')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($ceshen_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_shouduan', '侧身')
                    ->update(['allscore_get' => $ceshen_score]);
            }

            // 正手失分
            $zhengshou_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_shouduan', '正手')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($zhengshou_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_shouduan', '正手')
                    ->update(['allscore_lose' => $zhengshou_score]);
            }

            // 反手失分
            $fanshou_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_shouduan', '反手')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($fanshou_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_shouduan', '反手')
                    ->update(['allscore_lose' => $fanshou_score]);
            }

            // 控制失分
            $kongzhi_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_shouduan', '控制')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($kongzhi_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_shouduan', '控制')
                    ->update(['allscore_lose' => $kongzhi_score]);
            }

            // 发球失分
            $faqiu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_shouduan', '发球')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($faqiu_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_shouduan', '发球')
                    ->update(['allscore_lose' => $faqiu_score]);
            }

            // 侧身失分
            $ceshen_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_shouduan', '侧身')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($ceshen_score) {
                Db::name('skill_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_shouduan', '侧身')
                    ->update(['allscore_lose' => $ceshen_score]);
            }

            // 4、各板数得分表
            // 各板数得分——运动员A
            $data = [
                ['match_id' => $id, 'athlete_id' => $athlete_id_a, 'data_banshu' => '发球', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_a, 'data_banshu' => '接发球', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_a, 'data_banshu' => '第三板', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_a, 'data_banshu' => '第四板', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_a, 'data_banshu' => '第五板', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_a, 'data_banshu' => '第六板', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_a, 'data_banshu' => '相持', 'allscore_get' => 0, 'allscore_lose' => 0],
            ];
            Db::name('banshu_score')->insertAll($data);

            // 发球得分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_banshu', '发球')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_banshu', '发球')
                    ->update(['allscore_get' => $banshu_score]);
            }

            // 发球失分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_banshu', '发球')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_banshu', '发球')
                    ->update(['allscore_lose' => $banshu_score]);
            }

            // 接发球得分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_banshu', '接发球')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_banshu', '接发球')
                    ->update(['allscore_get' => $banshu_score]);
            }

            // 接发球失分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_banshu', '接发球')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_banshu', '接发球')
                    ->update(['allscore_lose' => $banshu_score]);
            }

            // 第三板得分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_banshu', '第三板')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_banshu', '第三板')
                    ->update(['allscore_get' => $banshu_score]);
            }

            // 第三板失分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_banshu', '第三板')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_banshu', '第三板')
                    ->update(['allscore_lose' => $banshu_score]);
            }

            // 第四板得分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_banshu', '第四板')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_banshu', '第四板')
                    ->update(['allscore_get' => $banshu_score]);
            }

            // 第四板失分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_banshu', '第四板')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_banshu', '第四板')
                    ->update(['allscore_lose' => $banshu_score]);
            }

            // 第五板得分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_banshu', '第五板')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_banshu', '第五板')
                    ->update(['allscore_get' => $banshu_score]);
            }

            // 第五板失分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_banshu', '第五板')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_banshu', '第五板')
                    ->update(['allscore_lose' => $banshu_score]);
            }

            // 第六板得分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_banshu', '第六板')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_banshu', '第六板')
                    ->update(['allscore_get' => $banshu_score]);
            }

            // 第六板失分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_banshu', '第六板')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_banshu', '第六板')
                    ->update(['allscore_lose' => $banshu_score]);
            }

            // 相持得分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_banshu', '相持')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_banshu', '相持')
                    ->update(['allscore_get' => $banshu_score]);
            }

            // 相持失分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_a)
                ->where('data_banshu', '相持')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_a)
                    ->where('data_banshu', '相持')
                    ->update(['allscore_lose' => $banshu_score]);
            }

            // 各板数得分——运动员B
            $data = [
                ['match_id' => $id, 'athlete_id' => $athlete_id_b, 'data_banshu' => '发球', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_b, 'data_banshu' => '接发球', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_b, 'data_banshu' => '第三板', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_b, 'data_banshu' => '第四板', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_b, 'data_banshu' => '第五板', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_b, 'data_banshu' => '第六板', 'allscore_get' => 0, 'allscore_lose' => 0],
                ['match_id' => $id, 'athlete_id' => $athlete_id_b, 'data_banshu' => '相持', 'allscore_get' => 0, 'allscore_lose' => 0],
            ];
            Db::name('banshu_score')->insertAll($data);

            // 发球得分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_banshu', '发球')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_banshu', '发球')
                    ->update(['allscore_get' => $banshu_score]);
            }

            // 发球失分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_banshu', '发球')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_banshu', '发球')
                    ->update(['allscore_lose' => $banshu_score]);
            }

            // 接发球得分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_banshu', '接发球')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_banshu', '接发球')
                    ->update(['allscore_get' => $banshu_score]);
            }

            // 接发球失分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_banshu', '接发球')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_banshu', '接发球')
                    ->update(['allscore_lose' => $banshu_score]);
            }

            // 第三板得分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_banshu', '第三板')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_banshu', '第三板')
                    ->update(['allscore_get' => $banshu_score]);
            }

            // 第三板失分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_banshu', '第三板')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_banshu', '第三板')
                    ->update(['allscore_lose' => $banshu_score]);
            }

            // 第四板得分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_banshu', '第四板')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_banshu', '第四板')
                    ->update(['allscore_get' => $banshu_score]);
            }

            // 第四板失分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_banshu', '第四板')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_banshu', '第四板')
                    ->update(['allscore_lose' => $banshu_score]);
            }

            // 第五板得分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_banshu', '第五板')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_banshu', '第五板')
                    ->update(['allscore_get' => $banshu_score]);
            }

            // 第五板失分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_banshu', '第五板')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_banshu', '第五板')
                    ->update(['allscore_lose' => $banshu_score]);
            }

            // 第六板得分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_banshu', '第六板')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_banshu', '第六板')
                    ->update(['allscore_get' => $banshu_score]);
            }

            // 第六板失分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_banshu', '第六板')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_banshu', '第六板')
                    ->update(['allscore_lose' => $banshu_score]);
            }

            // 相持得分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_banshu', '相持')
                ->where('data_deshi', '得')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_banshu', '相持')
                    ->update(['allscore_get' => $banshu_score]);
            }

            // 相持失分
            $banshu_score = MatchData::where('match_id', $id)
                ->where('athlete_id', $athlete_id_b)
                ->where('data_banshu', '相持')
                ->where('data_deshi', '失')
                ->select()
                ->count();
            if ($banshu_score) {
                Db::name('banshu_score')->where('match_id', $id)
                    ->where('athlete_id', $athlete_id_b)
                    ->where('data_banshu', '相持')
                    ->update(['allscore_lose' => $banshu_score]);
            }

             // 提交事务
             Db::commit();
             return ['state' => 1];

        } catch (Exception $exp) {

             // 回滚事务
             Db::rollback();
             return ['state' => 0];
        }
    }

    // 比赛下线
    public function stateDown(int $id)
    {
        // 启动事务
         Db::startTrans();
         try {
             // 更改为下线状态
             $res = Mat::where('id', $id)->update(['state' => '否']);

             // 删除相关数据表中的数据
             BigSmallScore::where('match_id', $id)->delete();
             BanshuScore::where('match_id', $id)->delete();
             SkillScore::where('match_id', $id)->delete();
             GameScore::where('match_id', $id)->delete();

             // 提交事务
             Db::commit();
             return ['state' => 1];

         } catch (Exception $exp) {
             // 回滚事务
             Db::rollback();
             return ['state' => 0];
         }
    }
}
