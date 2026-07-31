<?php
declare (strict_types = 1);

namespace app\middleware;

use think\facade\Session;

/**
 * 后台登录状态校验中间件
 * 未登录跳转到登录页
 */
class AdminAuth
{
    public function handle($request, \Closure $next)
    {
        $adminId = Session::get('admin_id');
        if (empty($adminId)) {
            return redirect((string) url('auth/login'));
        }
        // 注入当前登录管理员信息到请求
        $request->admin_id   = (int) $adminId;
        $request->admin_name = (string) Session::get('admin_name', '');
        return $next($request);
    }
}
