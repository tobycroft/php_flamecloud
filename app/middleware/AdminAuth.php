<?php
declare (strict_types = 1);

namespace app\middleware;

use think\facade\Session;
use app\model\AdminUserSettingModel;

/**
 * 后台登录状态校验中间件
 * 未登录跳转到登录页
 * 检测空闲超时自动退出
 */
class AdminAuth
{
    public function handle($request, \Closure $next)
    {
        $adminId = Session::get('admin_id');
        if (empty($adminId)) {
            return redirect((string) url('auth/login'));
        }

        $lastActivity = (int) Session::get('last_activity', 0);
        if ($lastActivity > 0) {
            $idleTimeout = AdminUserSettingModel::getIdleTimeout((int) $adminId);
            $idleSeconds = $idleTimeout * 60;
            if (time() - $lastActivity > $idleSeconds) {
                Session::clear();
                return redirect((string) url('auth/login'));
            }
        }

        Session::set('last_activity', time());

        // 注入当前登录管理员信息到请求
        $request->admin_id        = (int) $adminId;
        $request->admin_name      = (string) Session::get('admin_name', '');
        $request->admin_username  = (string) Session::get('admin_username', '');
        return $next($request);
    }
}