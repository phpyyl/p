<?php

namespace app\web\controller;

use app\admin\model\Athlete;
use think\Controller;
use think\Request;
use app\web\model\Match;
use think\Db;
use app\admin\model\BigSmallScore;
use app\admin\model\BanshuScore;
use app\admin\model\GameScore;
use app\admin\model\SkillScore;

class Index extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index(Request $request)
    {
        if ($request->isPost()) {
            return (new Match())->getList($request);
        }
        // 连表获取运动员名称数据
        $matchs = Db::name('match')->alias('a')
            ->where('state', '是')
            ->field('a.*, b.athlete_name as athlete_A, c.athlete_name as athlete_B')
            ->join('athlete b', 'a.athlete_id_a = b.id')
            ->join('athlete c', 'a.athlete_id_b = c.id')
            ->select();
        // 获取运动员表
        $athletes = Athlete::all();
        $this->assign('matchs', $matchs);
        $this->assign('athletes', $athletes);
        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        // 运动员A_id
        $athlete_id_a = Match::find($id)->athlete_id_a;
        // 运动员A姓名
        $athlete_name_a = Match::find($id)->athleteA->athlete_name;
        // 运动员A图片
        $athlete_img_a = Match::find($id)->athleteA->athlete_image;
        // 运动员B_id
        $athlete_id_b = Match::find($id)->athlete_id_b;
        // 运动员B姓名
        $athlete_name_b = Match::find($id)->athleteB->athlete_name;
        // 运动员B图片
        $athlete_img_b = Match::find($id)->athleteB->athlete_image;

        // 第一部分：失分 得分 指标 得分 失分
        // ------得失表 技术项------
        // 获得各技术项运动员A得失分
        $qa = SkillScore::where('match_id', $id)
            ->where('athlete_id', $athlete_id_a)
            ->buildSql();
        // 获得各技术项运动员B得失分
        $qb = SkillScore::where('match_id', $id)
            ->where('athlete_id', $athlete_id_b)
            ->buildSql();
        // 联表
        $shouduans = Db::table($qa)->alias('a')
            ->join("$qb b", 'a.data_shouduan=b.data_shouduan')
            ->field('a.allscore_lose, a.allscore_get, a.data_shouduan, b.allscore_get as allscore_get_b, b.allscore_lose as allscore_lose_b')
            ->select();

        // ------得失表 阶段项------
        // 获取各段得分 先根据板数得分表 连接A、B 得出一张表的数据
        // 获得各板数运动员A得失分
        $qa = BanshuScore::where('match_id', $id)
            ->where('athlete_id', $athlete_id_a)
            ->buildSql();
        // 获得各板数运动员B得失分
        $qb = BanshuScore::where('match_id', $id)
            ->where('athlete_id', $athlete_id_b)
            ->buildSql();
        // 联表
        $banshus = Db::table($qa)->alias('a')
            ->join("$qb b", 'a.data_banshu=b.data_banshu')
            ->field('a.allscore_lose, a.allscore_get, a.data_banshu, b.allscore_get as allscore_get_b, b.allscore_lose as allscore_lose_b')
            ->buildSql();

        // 1、发抢断（发球/第三板）
        $faqiang = Db::table($banshus)->alias('a')
            ->where('a.data_banshu', 'in', ['发球', '第三板'])
            ->field('sum(a.allscore_lose) as a, sum(a.allscore_get) as b, sum(a.allscore_get_b) as c, sum(a.allscore_lose_b) as d')
            ->select();
        // 2、接抢断（接发球/第四板）
        $jieqiang = Db::table($banshus)->alias('a')
            ->where('a.data_banshu', 'in', ['接发球', '第四板'])
            ->field('sum(a.allscore_lose) as a, sum(a.allscore_get) as b, sum(a.allscore_get_b) as c, sum(a.allscore_lose_b) as d')
            ->select();
        // 3、转换断（第五板/第六板）
        $zhuanhuan = Db::table($banshus)->alias('a')
            ->where('a.data_banshu', 'in', ['第五板', '第六板'])
            ->field('sum(a.allscore_lose) as a, sum(a.allscore_get) as b, sum(a.allscore_get_b) as c, sum(a.allscore_lose_b) as d')
            ->select();
        // 4、相持断（相持段）
        $xiangchi = Db::table($banshus)->alias('a')
            ->where('a.data_banshu', '相持')
            ->field('a.allscore_lose as a, a.allscore_get as b, a.allscore_get_b as c, a.allscore_lose_b as d')
            ->select();


        // 第二部分：各板得失分及得分率
        $gebanshu = Db::table($qa)->alias('a')
            ->join("$qb b", 'a.data_banshu=b.data_banshu')
            ->field('a.allscore_lose, a.allscore_get, a.data_banshu, b.allscore_get as allscore_get_b, b.allscore_lose as allscore_lose_b')
            ->select();
        // 第三部分：各段得分率和各技术项得分率返回以上数据可计算得出

        // 第四部分：单局数据返回 单独方法处理

        // 向视图赋值
        $this->assign([
            'athlete_img_a' => $athlete_img_a,
            'athlete_img_b' => $athlete_img_b,
            'match_id' => $id,
            'athlete_name_a' => $athlete_name_a,
            'athlete_name_b' => $athlete_name_b,
            'shouduans' => $shouduans,
            'banshus' => $banshus,
            'gebanshu' => $gebanshu,
            'faqiang' =>$faqiang,
            'jieqiang' => $jieqiang,
            'zhuanhuan' => $zhuanhuan,
            'xiangchi' => $xiangchi
        ]);
        return $this->fetch();
    }

    // 获取单局数据
    public function getone(Request $request) {
        $id = $request->param('id');
        $jushu = $request->param('jushu');
        // 运动员A_id
        $athlete_id_a = Match::find($id)->athlete_id_a;
        // 运动员A姓名
        $athlete_name_a = Match::find($id)->athleteA->athlete_name;
        // 运动员B_id
        $athlete_id_b = Match::find($id)->athlete_id_b;
        // 运动员B姓名
        $athlete_name_b = Match::find($id)->athleteB->athlete_name;

        $data = GameScore::field('data_get_score, data_lose_score')->where('match_id', $id)
            ->where('athlete_id', $athlete_id_a)
            ->where('data_game_no', $jushu)
            ->select();
//        dump($data);die;
        return $data;
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
