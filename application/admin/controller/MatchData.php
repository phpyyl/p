<?php

namespace app\admin\controller;

use think\Controller;
use think\Exception;
use think\Request;
use think\Db;
use app\admin\model\Match;

use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Cell;

class MatchData extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index(Request $request)
    {
        if ($request->isPost()) {
            return (new \app\admin\model\MatchData())->getList($request);
        }

        // 连表获取运动员名称数据
        $matchs = Db::name('match')->alias('a')
            ->field('a.*, b.athlete_name as athlete_A, c.athlete_name as athlete_B')
            ->join('athlete b', 'a.athlete_id_a = b.id')
            ->join('athlete c', 'a.athlete_id_b = c.id')
            ->select();
        $this->assign('matchs', $matchs);
        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $matchs = Match::all();
        $this->assign('matchs', $matchs);
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
        $res = \app\admin\model\MatchData::create($data);
        if ($res) {
            $this->success('新增成功', 'admin/matchdata/index', 3);
        } else {
            $this->error('新增失败');
        }
    }

    /**
     * 显示批量上传的表单页
     *
     * @return \think\Response
     */
    public function createBat()
    {
        $matchs = Match::all();
        $this->assign('matchs', $matchs);
        return $this->fetch();
    }

    /**
     * 保存批量上传的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function saveBat(Request $request)
    {
        // excel文件服务器物理路径
        $file_path = $request->param('excel_url');
        // 获取比赛场次和运动员
        $match_id = $request->param('match_id');
        $athlete_id = $request->param('athlete_id');

        // 判断是否选择了要上传的表格
        if (!$file_path) {
            $this->error('请选择要上传的文件', 'admin/matchdata/createbat', 3);
        }

        // 判断文件大小是否符合…… 判断文件类型是否正确……
        // ……

        // use excel2007 for 2007 format
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        // 加载文件
        $objPHPExcel = $objReader->load($file_path);
        // 指定sheet
        $sheet = $objPHPExcel->getSheet(0);
        // 取得总行数
        $highestRow = $sheet->getHighestRow();
        // 取得总列数
        // $highestColumn = $sheet->getHighestColumn();

        try {
            // 循环读取excel表格,读取一条,插入一条
            // j表示从哪一行开始读取  从第二行开始读取，因为第一行是标题不保存
            // $a表示列号
            for($j=2;$j<=$highestRow;$j++) {
                $data['match_id'] = $match_id;
                $data['athlete_id'] = $athlete_id;
                $data['data_game_no'] = $objPHPExcel->getActiveSheet()->getCell("A".$j)->getValue();// 获取A(局数)列的值
                $data['data_get_score'] = $objPHPExcel->getActiveSheet()->getCell("B".$j)->getValue();// 获取B(总得)列的值
                $data['data_lose_score'] = $objPHPExcel->getActiveSheet()->getCell("C".$j)->getValue();// 获取C(总失)列的值
                $data['data_fajielunci'] = $objPHPExcel->getActiveSheet()->getCell("D".$j)->getValue();// 获取D(发接轮次)列的值
                $data['data_banshu'] = $objPHPExcel->getActiveSheet()->getCell("E".$j)->getValue();// 获取E(拍数)列的值
                $data['data_shouduan'] = $objPHPExcel->getActiveSheet()->getCell("F".$j)->getValue();// 获取F(手段)列的值
                $data['data_deshi'] = $objPHPExcel->getActiveSheet()->getCell("G".$j)->getValue();// 获取G(得失分)列的值
                // 入库
                $res = \app\admin\model\MatchData::create($data);
            }
        } catch (Exception $exp) {
            $this->error('新增失败');
        }
        $this->success('新增成功', 'admin/matchdata/index', 3);

    }

    // excel上传
    public function upload(Request $request)
    {
        // 获取表单上传文件
        if ($file = request()->file('excel_bat')) {
            // 移动到框架应用根目录/uploads/excel/ 目录下
            $info = $file->move( './uploads/excel/');
            if($info){
                // 成功上传后 获取上传信息
                $lujing = './uploads/excel/' . $info->getSaveName();
                return $lujing;
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }
    // excel模板文件下载
    public function download()
    {
        $file_dir = './uploads/excel/20181025\ef5523e9ccc3091a69b73b3baa31c1bf.xls';

        // 检查文件是否存在
        if (! file_exists($file_dir) ) {
            $this->error('文件未找到');
        }else{
            // 打开文件
            $file1 = fopen($file_dir, "r");
            // 输入文件标签
            Header("Content-type: application/octet-stream");
            Header("Accept-Ranges: bytes");
            Header("Accept-Length:".filesize($file_dir));
            Header("Content-Disposition: attachment;filename=" . $file_dir);
            ob_clean();     // 重点
            flush();        // 可以清除文件中多余的路径名以及解决乱码的问题
            // 输出文件内容
            // 读取文件内容并直接输出到浏览器
            echo fread($file1, filesize($file_dir));
            fclose($file1);
            exit();
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
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        // 获取单条数据 连表查询 获得双方运动员（供首次进入修改页面中运动员select选项使用）
        $data = Db::name('match_data')->alias('a')
            ->field('a.*, b.athlete_id_a as id_a, b.athlete_id_b as id_b, c.athlete_name as name_a, d.athlete_name as name_b')
            ->join('match b', 'a.match_id=b.id')
            ->join('athlete c', 'b.athlete_id_a=c.id')
            ->join('athlete d', 'b.athlete_id_b=d.id')
            ->where('a.id', $id)
            ->find();

        // 获取比赛表
        $matchs = Match::all();

        // 模板赋值
        $this->assign([
            'data' => $data,
            'matchs' => $matchs
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
        $res = \app\admin\model\MatchData::where('id', $id)->update($data);
        if ($res) {
            $this->success('更新成功', 'admin/matchdata/index', 3);
        } else {
            $this->error('更新失败');
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
        $res = \app\admin\model\MatchData::where('id', $id)->delete();
        if ($res) {
            $this->success('删除成功', 'admin/matchdata/index', 3);
        } else {
            $this->error('删除失败');
        }
    }

    // 二级联动查询所选场次得对阵运动员
    public function join(int $id) {
        // 连表获取运动员
        $data = Db::name('match')->alias('a')
            ->field('b.id as id_a, b.athlete_name as name_a, c.id as id_b, c.athlete_name as name_b')
            ->join('athlete b', 'a.athlete_id_a = b.id')
            ->join('athlete c', 'a.athlete_id_b = c.id')
            ->where('a.id', $id)
            ->find();
        return json_encode($data);
    }
}
