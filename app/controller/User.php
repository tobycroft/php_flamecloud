<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\FcUserModel;
use app\model\AdminLogModel;
use think\facade\Session;
use think\facade\View;

/**
 * 前台用户管理控制器
 */
class User extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    public function index()
    {
        $keyword = trim((string) $this->request->get('keyword', ''));
        $page    = (int) $this->request->get('page', 1);
        $limit   = 15;

        $result = FcUserModel::getList($page, $limit, $keyword);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'keyword'       => $keyword,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/user/index');
    }

    public function status()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }
        $id     = (int) $this->request->post('id', 0);
        $status = (int) $this->request->post('status', 0);

        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $status = $status === 1 ? 1 : 0;
        $ret = FcUserModel::setStatus($id, $status);
        if ($ret) {
            AdminLogModel::record([
                'admin_id'    => (int) Session::get('admin_id', 0),
                'admin_name'  => (string) Session::get('admin_name', ''),
                'type_code'   => 'user_status',
                'action'      => '启用/禁用用户',
                'detail'      => ($status === 1 ? '启用' : '禁用') . '用户 ID=' . $id,
                'target_type' => 'user',
                'target_id'   => $id,
                'ip'          => $this->request->ip(),
                'user_agent'  => (string) $this->request->header('user-agent', ''),
            ]);
            return json(['code' => 0, 'msg' => '操作成功']);
        }
        return json(['code' => 1, 'msg' => '操作失败']);
    }
}