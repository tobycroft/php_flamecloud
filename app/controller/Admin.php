<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\AdminUser;
use think\facade\Session;
use think\facade\View;

/**
 * 管理员管理控制器
 */
class Admin extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    public function index()
    {
        $keyword = trim((string) $this->request->get('keyword', ''));
        $page    = (int) $this->request->get('page', 1);
        $limit   = 15;

        $result = AdminUser::getList($page, $limit, $keyword);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;

        View::assign([
            'list'        => $result['list'],
            'total'       => $result['total'],
            'page'        => $page,
            'totalPage'   => $totalPage,
            'keyword'     => $keyword,
            'admin_name'  => Session::get('admin_name', '管理员'),
            'admin_username' => Session::get('admin_username', ''),
            'admin_id'    => (int) Session::get('admin_id', 0),
            'current_admin_id' => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/admin/index');
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $username = trim((string) $this->request->post('username', ''));
            $password = (string) $this->request->post('password', '');
            $nickname = trim((string) $this->request->post('nickname', ''));
            $status   = (int) $this->request->post('status', 1);

            if ($username === '') {
                return json(['code' => 1, 'msg' => '请输入用户名']);
            }
            if ($password === '') {
                return json(['code' => 1, 'msg' => '请输入密码']);
            }
            if (strlen($password) < 6) {
                return json(['code' => 1, 'msg' => '密码长度不能少于6位']);
            }

            $exists = AdminUser::findByUsername($username);
            if ($exists) {
                return json(['code' => 1, 'msg' => '用户名已存在']);
            }

            $ret = AdminUser::add([
                'username' => $username,
                'password' => $password,
                'nickname' => $nickname,
                'status'   => $status,
            ]);

            if ($ret) {
                return json(['code' => 0, 'msg' => '添加成功']);
            }
            return json(['code' => 1, 'msg' => '添加失败']);
        }
        return json(['code' => 1, 'msg' => '非法请求']);
    }

    public function edit()
    {
        if ($this->request->isPost()) {
            $id       = (int) $this->request->post('id', 0);
            $nickname = trim((string) $this->request->post('nickname', ''));
            $password = (string) $this->request->post('password', '');
            $status   = (int) $this->request->post('status', 1);

            if ($id <= 0) {
                return json(['code' => 1, 'msg' => '参数错误']);
            }

            $currentId = (int) Session::get('admin_id', 0);
            if ($id === $currentId && $status !== 1) {
                return json(['code' => 1, 'msg' => '不能禁用当前登录账号']);
            }

            $data = [
                'nickname' => $nickname,
                'status'   => $status,
            ];
            if ($password !== '') {
                if (strlen($password) < 6) {
                    return json(['code' => 1, 'msg' => '密码长度不能少于6位']);
                }
                $data['password'] = $password;
            }

            $ret = AdminUser::edit($id, $data);
            if ($ret) {
                return json(['code' => 0, 'msg' => '修改成功']);
            }
            return json(['code' => 1, 'msg' => '修改失败']);
        }

        $id = (int) $this->request->get('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }
        $admin = AdminUser::findById($id);
        if (empty($admin)) {
            return json(['code' => 1, 'msg' => '管理员不存在']);
        }
        return json(['code' => 0, 'data' => [
            'id'       => (int) $admin->id,
            'username' => (string) $admin->username,
            'nickname' => (string) $admin->nickname,
            'status'   => (int) $admin->status,
        ]]);
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

        $currentId = (int) Session::get('admin_id', 0);
        if ($id === $currentId) {
            return json(['code' => 1, 'msg' => '不能操作当前登录账号']);
        }

        $status = $status === 1 ? 1 : 0;
        $ret = AdminUser::setStatus($id, $status);
        if ($ret) {
            return json(['code' => 0, 'msg' => '操作成功']);
        }
        return json(['code' => 1, 'msg' => '操作失败']);
    }

    public function delete()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }
        $id = (int) $this->request->post('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $currentId = (int) Session::get('admin_id', 0);
        if ($id === $currentId) {
            return json(['code' => 1, 'msg' => '不能删除当前登录账号']);
        }

        $ret = AdminUser::remove($id);
        if ($ret) {
            return json(['code' => 0, 'msg' => '删除成功']);
        }
        return json(['code' => 1, 'msg' => '删除失败']);
    }
}