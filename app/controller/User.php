<?php
declare (strict_types = 1);

namespace app\controller;

use app\AdminBaseController;
use app\model\FcUserModel;
use app\model\AdminLogOperationModel;
use think\facade\Session;
use think\facade\View;

/**
 * 前台用户管理控制器
 */
class User extends AdminBaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    public function index()
    {
        $keyword = trim((string) $this->request->get('keyword', ''));
        $page    = (int) $this->request->get('page', 1);
        $limit   = 15;

        $result = FcUserModel::getList($page, $limit, $keyword);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = $keyword !== '' ? '?keyword=' . urlencode($keyword) . '&' : '?';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'keyword'       => $keyword,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/user/index');
    }

    public function edit()
    {
        if ($this->request->isPost()) {
            $id         = (int) $this->request->post('id', 0);
            $name       = trim((string) $this->request->post('name', ''));
            $company    = trim((string) $this->request->post('company', ''));
            $department = trim((string) $this->request->post('department', ''));
            $phone      = trim((string) $this->request->post('phone', ''));
            $email      = trim((string) $this->request->post('email', ''));
            $password   = (string) $this->request->post('password', '');

            if ($id <= 0) {
                return json(['code' => 1, 'msg' => '参数错误']);
            }

            $user = FcUserModel::findById($id);
            if (empty($user)) {
                return json(['code' => 1, 'msg' => '用户不存在']);
            }

            if ($password !== '' && strlen($password) < 6) {
                return json(['code' => 1, 'msg' => '密码长度不能少于6位']);
            }

            $data = [
                'name'       => $name,
                'company'    => $company,
                'department' => $department,
                'phone'      => $phone,
                'email'      => $email,
                'password'   => $password,
            ];

            $ret = FcUserModel::edit($id, $data);
            if ($ret) {
                $detail = '编辑用户 ID=' . $id;
                if ($name !== '') {
                    $detail .= ' 姓名=' . $name;
                }
                if ($password !== '') {
                    $detail .= ' 密码已重置';
                }
                AdminLogOperationModel::record([
                    'admin_id'    => (int) Session::get('admin_id', 0),
                    'admin_name'  => (string) Session::get('admin_name', ''),
                    'type_code'   => 'user_edit',
                    'action'      => '编辑用户',
                    'detail'      => $detail,
                    'target_type' => 'user',
                    'target_id'   => $id,
                    'ip'          => $this->request->ip(),
                    'user_agent'  => (string) $this->request->header('user-agent', ''),
                ]);
                return json(['code' => 0, 'msg' => '修改成功']);
            }
            return json(['code' => 1, 'msg' => '修改失败']);
        }

        // GET 请求返回用户数据
        $id = (int) $this->request->get('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }
        $user = FcUserModel::findById($id);
        if (empty($user)) {
            return json(['code' => 1, 'msg' => '用户不存在']);
        }

        $info = FcUserModel::getInfoByUid($id);
        return json(['code' => 0, 'data' => [
            'id'         => (int) $user->id,
            'username'   => (string) $user->username,
            'phone'      => (string) $user->phone,
            'email'      => (string) $user->email,
            'name'       => $info ? (string) ($info['name'] ?? '') : '',
            'company'    => $info ? (string) ($info['company'] ?? '') : '',
            'department' => $info ? (string) ($info['department'] ?? '') : '',
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

        $status = $status === 1 ? 1 : 0;
        $ret = FcUserModel::setStatus($id, $status);
        if ($ret) {
            AdminLogOperationModel::record([
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