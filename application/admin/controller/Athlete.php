<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Db;
use app\admin\model\Athlete as Ath;

class Athlete extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        // 获取数据
        $data = Db::name('athlete')->paginate(5);
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

        // 获取表单上传文件
        if ($file = request()->file('athlete_image')) {
            // 移动到网站根目录/uploads/ 目录下
            $info = $file->move( './uploads');
            if($info){
                // 成功上传后 获取上传信息
                $data['athlete_image'] = '/uploads/' . $info->getSaveName();
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }


        // 入库
        $res = Ath::create($data);
        if ($res) {
            $this->success('新增成功', 'admin/athlete/index', 3);
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
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        $data = Ath::get($id);
        $this->assign('data', $data);
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


        // 获取表单上传文件 例如上传了001.jpg
        if ($file = request()->file('athlete_image')) {
            // 移动到框架应用根目录/uploads/ 目录下
            $info = $file->move( './uploads');
            if($info){
                // 成功上传后 获取上传信息
                $data['athlete_image'] = '/uploads/' . $info->getSaveName();
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }

        // 入库
        $res = Ath::where('id', $id)->update($data);
        if ($res) {
            $this->success('修改成功', 'admin/athlete/index', 3);
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
        $res = Ath::where('id', $id)->delete();
        if ($res) {
            $this->success('删除成功', 'admin/athlete/index', 3);
        } else {
            $this->error('删除失败');
        }
    }
}
