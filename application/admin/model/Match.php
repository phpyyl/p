<?php

namespace app\admin\model;

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


        // 先获取所有比赛
        $shuju = $this->querySearch($request)
            ->with('athleteA')->with('athleteB')
            ->order($field, $orderWay)
            ->limit($offset, $recodes)
            ->select();


        // 查询大小比分
        foreach ($shuju as $sj) {
            // 本场比赛的id
            $m_id = $sj->id;
            // 本场比赛运动员A的id
            $a_id = $sj->athlete_id_a;

            // 查询大比分
            // 本场比赛总局数
            $zongjushu = MatchData::where('match_id', $m_id)
                ->where('athlete_id', $a_id)
                ->group('data_game_no')
                ->select()
                ->count();
            // 本场比赛运动员A胜利局数
            $a_sheng = MatchData::where('match_id', $m_id)
                ->where('athlete_id', $a_id)
                ->where('data_get_score>=11 and data_get_score-data_lose_score>=2')
                ->select()
                ->count();
            // 本场比赛运动员B胜利局数
            $b_sheng = $zongjushu - $a_sheng;
            // 本场比赛大比分写入查询结果集$sj
            $sj->big_score = "$a_sheng - $b_sheng";

            // 查询小比分
            $small_score = MatchData::where('match_id', $m_id)
                ->where('athlete_id', $a_id)
                ->where('(data_get_score>=11 and data_get_score-data_lose_score>=2)
                or (data_lose_score>=11 and data_lose_score-data_get_score>=2)')
                ->field('concat(data_get_score,"-",data_lose_score) as small_score')
                ->select();

            $xbf = '';
            foreach ($small_score as $key => $sc) {
                $xbf .= $sc;
            }

            $xbf = str_replace('{"small_score":"', '/', $xbf);
            $xbf = str_replace('"}/', '/', $xbf);
            $xbf = rtrim($xbf, '"}');
            $xbf = ltrim($xbf, '/');
            // 本场比赛小比分（字符串形式）写入查询结果集$sj
            $sj->small_score = $xbf;
        }

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
        $query = $this->where('id', '>', 0);

        // 获取比赛id
        $match_id = $request->param('match_id');

        if ($match_id) {
            $query->where('id', $match_id);
        }
        return $query;
    }

    // 关联运动员A
    public function athleteA() {
        return $this->belongsTo('athlete', 'athlete_id_a', 'id');
    }

    // 关联运动员B
    public function athleteB() {
        return $this->belongsTo('athlete', 'athlete_id_b', 'id');
    }
}
