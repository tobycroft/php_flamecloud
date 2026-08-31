<?php
declare (strict_types = 1);

namespace app\controller;

use app\AdminBaseController;
use app\model\AdminUserModel;
use app\model\AdminLogOperationModel;
use think\facade\Session;
use think\facade\View;

/**
 * 管理员管理控制器
 */
class Admin extends AdminBaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    private function getLogMeta(): array
    {
        return [
            'admin_id'   => (int) Session::get('admin_id', 0),
            'admin_name' => (string) Session::get('admin_name', ''),
            'ip'         => $this->request->ip(),
            'user_agent' => (string) $this->request->header('user-agent', ''),
        ];
    }

    public function index()
    {
        $keyword = trim((string) $this->request->get('keyword', ''));
        $page    = (int) $this->request->get('page', 1);
        $limit   = 15;

        $result = AdminUserModel::getList($page, $limit, $keyword);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = $keyword !== '' ? '?keyword=' . urlencode($keyword) . '&' : '?';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        View::assign([
            'list'        => $result['list'],
            'total'       => $result['total'],
            'page'        => $page,
            'totalPage'   => $totalPage,
            'keyword'     => $keyword,
            'p_query'     => $pQuery,
            'p_start'     => $pStart,
            'p_end'       => $pEnd,
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

            $exists = AdminUserModel::findByUsername($username);
            if ($exists) {
                return json(['code' => 1, 'msg' => '用户名已存在']);
            }

            $ret = AdminUserModel::add([
                'username' => $username,
                'password' => $password,
                'nickname' => $nickname,
                'status'   => $status,
            ]);

            if ($ret) {
                AdminLogOperationModel::record(array_merge($this->getLogMeta(), [
                    'type_code'   => 'admin_add',
                    'action'      => '添加管理员',
                    'detail'      => '添加管理员 ' . $username . ($nickname ? ' (' . $nickname . ')' : ''),
                    'target_type' => 'admin',
                    'target_id'   => (int) $ret->id,
                ]));
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

            $ret = AdminUserModel::edit($id, $data);
            if ($ret) {
                $detail = '修改管理员 ' . $id;
                if ($nickname !== '') {
                    $detail .= ' 昵称=' . $nickname;
                }
                if ($password !== '') {
                    $detail .= ' 密码已重置';
                }
                $detail .= ' 状态=' . ($status === 1 ? '启用' : '禁用');
                AdminLogOperationModel::record(array_merge($this->getLogMeta(), [
                    'type_code'   => 'admin_edit',
                    'action'      => '编辑管理员',
                    'detail'      => $detail,
                    'target_type' => 'admin',
                    'target_id'   => $id,
                ]));
                return json(['code' => 0, 'msg' => '修改成功']);
            }
            return json(['code' => 1, 'msg' => '修改失败']);
        }

        $id = (int) $this->request->get('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }
        $admin = AdminUserModel::findById($id);
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
        $ret = AdminUserModel::setStatus($id, $status);
        if ($ret) {
            AdminLogOperationModel::record(array_merge($this->getLogMeta(), [
                'type_code'   => 'admin_status',
                'action'      => '启用/禁用管理员',
                'detail'      => ($status === 1 ? '启用' : '禁用') . '管理员 ID=' . $id,
                'target_type' => 'admin',
                'target_id'   => $id,
            ]));
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

        $ret = AdminUserModel::remove($id);
        if ($ret) {
            AdminLogOperationModel::record(array_merge($this->getLogMeta(), [
                'type_code'   => 'admin_delete',
                'action'      => '删除管理员',
                'detail'      => '删除管理员 ID=' . $id,
                'target_type' => 'admin',
                'target_id'   => $id,
            ]));
            return json(['code' => 0, 'msg' => '删除成功']);
        }
        return json(['code' => 1, 'msg' => '删除失败']);
    }

    /**
     * 权限列表页 - 显示所有管理员并可配置权限
     */
    public function permissionList()
    {
        $keyword = trim((string) $this->request->get('keyword', ''));
        $page    = (int) $this->request->get('page', 1);
        $limit   = 15;

        $result = AdminUserModel::getList($page, $limit, $keyword);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = $keyword !== '' ? '?keyword=' . urlencode($keyword) . '&' : '?';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        // 计算每个管理员的权限数量
        $list = [];
        foreach ($result['list'] as $item) {
            $item['perm_count'] = 0;
            if (!empty($item['permissions'])) {
                $perms = json_decode($item['permissions'], true);
                $item['perm_count'] = is_array($perms) ? count($perms) : 0;
            }
            $list[] = $item;
        }

        View::assign([
            'list'        => $list,
            'total'       => $result['total'],
            'page'        => $page,
            'totalPage'   => $totalPage,
            'keyword'     => $keyword,
            'p_query'     => $pQuery,
            'p_start'     => $pStart,
            'p_end'       => $pEnd,
            'admin_name'  => Session::get('admin_name', '管理员'),
            'admin_username' => Session::get('admin_username', ''),
            'permission_map' => AdminBaseController::$permissionMap,
        ]);
        return View::fetch('/admin/permission_list');
    }

    /**
     * 权限编辑页面
     */
    public function permission()
    {
        $id = (int) $this->request->get('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $admin = AdminUserModel::findById($id);
        if (empty($admin)) {
            return json(['code' => 1, 'msg' => '管理员不存在']);
        }

        $permissions = empty($admin->permissions) ? [] : json_decode($admin->permissions, true);
        if (!is_array($permissions)) {
            $permissions = [];
        }

        // 预计算每组的选中状态，避免模板中写 PHP 逻辑
        $groupsData = [];
        foreach (AdminBaseController::$permissionGroups as $groupName => $children) {
            $allChildrenChecked = true;
            $anyChildChecked = false;
            $childrenData = [];
            foreach ($children as $childKey) {
                $childName = AdminBaseController::$permissionMap[$childKey] ?? $childKey;
                $childChecked = in_array($childKey, $permissions) || in_array($groupName, $permissions);
                if ($childChecked) {
                    $anyChildChecked = true;
                } else {
                    $allChildrenChecked = false;
                }
                $childrenData[] = [
                    'key'     => $childKey,
                    'name'    => $childName,
                    'checked' => $childChecked,
                ];
            }
            $groupsData[] = [
                'name'       => $groupName,
                'allChecked' => $allChildrenChecked,
                'anyChecked' => $anyChildChecked,
                'children'   => $childrenData,
            ];
        }

        View::assign([
            'id'            => $id,
            'username'      => $admin->username,
            'nickname'      => $admin->nickname,
            'is_super'      => (int) $admin->is_super,
            'permissions'   => $permissions,
            'groups_data'   => $groupsData,
            'admin_name'    => Session::get('admin_name', '管理员'),
        ]);
        return View::fetch('/admin/permission');
    }

    /**
     * 保存权限修改
     */
    public function savePermission()
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
            return json(['code' => 1, 'msg' => '不能修改自己的权限']);
        }

        $admin = AdminUserModel::findById($id);
        if (empty($admin)) {
            return json(['code' => 1, 'msg' => '管理员不存在']);
        }

        $isSuper = (bool) $this->request->post('is_super', 0);
        $permissions = (array) $this->request->post('permissions', []);

        AdminUserModel::updateIsSuper($id, $isSuper);
        if (!$isSuper) {
            AdminUserModel::updatePermissions($id, $permissions);
        }

        AdminLogOperationModel::record(array_merge($this->getLogMeta(), [
            'type_code'   => 'admin_permission',
            'action'      => '修改管理员权限',
            'detail'      => sprintf('修改管理员 ID=%s 是否超级管理员=%s 权限数量=%d', $id, $isSuper ? '是' : '否', count($permissions)),
            'target_type' => 'admin',
            'target_id'   => $id,
        ]));

        return json(['code' => 0, 'msg' => '权限保存成功']);
    }

    /**
     * 获取权限详情（AJAX）
     */
    public function getPermission()
    {
        $id = (int) $this->request->get('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $admin = AdminUserModel::findById($id);
        if (empty($admin)) {
            return json(['code' => 1, 'msg' => '管理员不存在']);
        }

        $permissions = AdminUserModel::getPermissions($id);

        return json([
            'code' => 0,
            'data' => [
                'id'             => (int) $admin->id,
                'username'       => (string) $admin->username,
                'nickname'       => (string) $admin->nickname,
                'is_super'       => (int) $admin->is_super,
                'permissions'    => $permissions,
                'permission_map' => AdminBaseController::$permissionMap,
            ],
        ]);
    }
}