<?php
declare (strict_types = 1);

namespace app\controller;

use app\AdminBaseController;
use app\model\SystemParamModel;
use think\facade\Session;
use think\facade\View;

/**
 * 系统参数配置控制器
 */
class SystemParam extends AdminBaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    public function index()
    {
        View::assign([
            'list'            => SystemParamModel::getAll(),
            'admin_name'      => Session::get('admin_name', '管理员'),
            'admin_username'  => Session::get('admin_username', ''),
            'admin_id'        => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/system_param/index');
    }

    public function save()
    {
        $id    = (int) $this->request->post('id', 0);
        $value = (string) $this->request->post('value', '');

        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数不存在']);
        }

        $ret = SystemParamModel::setVal($id, $value);
        if ($ret) {
            AdminLogOperationModel::record([
                'admin_id'    => (int) Session::get('admin_id', 0),
                'admin_name'  => (string) Session::get('admin_name', ''),
                'type_code'   => 'system',
                'action'      => '修改系统参数',
                'detail'      => '修改系统参数 ID=' . $id . ' value=' . $value,
                'target_type' => 'system_param',
                'target_id'   => $id,
                'ip'          => $this->request->ip(),
                'user_agent'  => (string) $this->request->header('user-agent', ''),
            ]);
            return json(['code' => 0, 'msg' => '修改成功']);
        }

        return json(['code' => 1, 'msg' => '修改失败']);
    }
}