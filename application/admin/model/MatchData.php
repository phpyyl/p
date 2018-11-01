<?php

namespace app\admin\model;

use think\Model;
use think\Request;

class MatchData extends Model
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

        // 按条件查询数据
        $shuju = $this->querySearch($request)
            ->with('match')->with('athlete')
            ->order($field, $orderWay)
            ->limit($offset, $recodes)
            ->select();
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

        // 获取运动员id
        $athlete_id = $request->param('athlete_id');


        if ($match_id) {
            $query->where('match_id', $match_id);
        }

        if ($athlete_id) {
            $query->where('athlete_id', $athlete_id);
        }
        return $query;
    }

    // 关联比赛表
    public function match() {
        return $this->belongsTo('match', 'match_id', 'id');
    }

    // 关联运动员
    public function athlete() {
        return $this->belongsTo('athlete', 'athlete_id', 'id');
    }
}
