<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\AdminUserSettingModel;
use think\facade\Session;
use think\facade\View;

/**
 * 管理员个人设置控制器
 */
class AdminSetting extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    public function index()
    {
        $adminId  = (int) Session::get('admin_id', 0);
        $setting  = AdminUserSettingModel::getByAdminId($adminId);
        $idleTimeout = $setting ? (int) $setting->idle_timeout : 30;

        View::assign([
            'idle_timeout'    => $idleTimeout,
            'admin_name'      => Session::get('admin_name', '管理员'),
            'admin_username'  => Session::get('admin_username', ''),
            'admin_id'        => $adminId,
        ]);
        return View::fetch('/admin_setting/index');
    }

    public function save()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        $adminId     = (int) Session::get('admin_id', 0);
        $idleTimeout = (int) $this->request->post('idle_timeout', 30);

        if ($idleTimeout < 15) {
            return json(['code' => 1, 'msg' => '空闲超时时间不能少于15分钟']);
        }
        if ($idleTimeout > 1440) {
            return json(['code' => 1, 'msg' => '空闲超时时间不能超过24小时']);
        }

        $ret = AdminUserSettingModel::saveSetting($adminId, $idleTimeout);
        if ($ret) {
            return json(['code' => 0, 'msg' => '保存成功']);
        }
        return json(['code' => 1, 'msg' => '保存失败']);
    }
}