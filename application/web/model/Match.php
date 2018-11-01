<?php

namespace app\web\model;

use think\Model;
use think\Request;
use app\admin\model\MatchData;

class Match extends Model
{
    // 查询用户列表
    public function getList(Request $request)
    {
        // 起始位置
        $offset = $request->param('start');
        // 需要获取条数
        $recodes = $request->param('length');

        // 排序
        // 排序的字段的索引
        $index = $request->param('order')[0]['column'];
        // 排序的字段
        $field = $request->param('columns')[$index]['data'];
        //dump($field);

        // 排序的规则
        $orderWay = $request->param('order')[0]['dir'];

        // 获取所有上线比赛的列表
        $shuju = $this->querySearch($request)
            ->with('athleteA')->with('athleteB')
            ->with('bigSmallScore')
            ->order('match_date', 'desc')
            ->limit($offset, $recodes)
            ->select();

        // 返回数据
        $data = [
            'draw' => $request->param('draw'),
            'recordsTotal' => $this->getTotal($request),
            'recordsFiltered' => $this->getTotal($request),
            'data' => $shuju
        ];
        return $data;
    }

    // 获取查询到的总条数
    public function getTotal(Request $request)
    {
        return $this->querySearch($request)->count();
    }

    // 查询条件
    private function querySearch(Request $request)
    {
        // 初始化查询条件
        $query = $this->where('state', '是');

        // 获取比赛id
        $match_id = $request->param('match_id');
        // 获取运动员_id_A
        $athlete_id_a = $request->param('athlete_id_a');
        // 获取运动员_id_B
        $athlete_id_b = $request->param('athlete_id_b');
        // 获取比赛城市
        $match_nation = $request->param('match_nation');
        // 获取比赛阶段
        $match_phase = $request->param('match_phase');
        // 获取比赛项目
        $match_item = $request->param('match_item');

        if ($match_id) {
            $query->where('id', $match_id);
        }
        if ($athlete_id_a) {
            $query->where('athlete_id_a', $athlete_id_a);
        }
        if ($athlete_id_b) {
            $query->where('$athlete_id_b', $athlete_id_b);
        }
        if ($match_nation) {
            $query->where('match_nation', $match_nation);
        }
        if ($match_phase) {
            $query->where('match_phase', $match_phase);
        }
        if ($match_item) {
            $query->where('match_item', $match_item);
        }
        return $query;
    }

    // 关联运动员A
    public function athleteA() {
        return $this->belongsTo('app\admin\model\Athlete', 'athlete_id_a', 'id');
    }

    // 关联运动员B
    public function athleteB() {
        return $this->belongsTo('app\admin\model\Athlete', 'athlete_id_b', 'id');
    }

    // 关联大小比分表
    public function bigSmallScore() {
        return $this->hasOne('app\admin\model\BigSmallScore', 'match_id', 'id');
    }
}
