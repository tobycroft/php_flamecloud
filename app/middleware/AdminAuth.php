<?php
declare (strict_types = 1);

namespace app\middleware;

use think\facade\Session;
use think\facade\View;
use app\model\AdminUserSettingModel;
use app\model\AdminUserModel;
use app\AdminBaseController;

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

        // ========== 权限校验 ==========
        $adminIdInt = (int) $adminId;

        // admin_id=1 始终为超级管理员，全部放行
        if ($adminIdInt === 1) {
            // 注入全权限数据到视图
            View::assign([
                'is_super'    => true,
                'permissions' => array_keys(AdminBaseController::$permissionMap),
            ]);
            return $next($request);
        }

        // 从 session 获取是否超级管理员
        $isSuper = Session::get('admin_is_super');
        if ($isSuper === null) {
            $isSuper = AdminUserModel::isSuper($adminIdInt);
            Session::set('admin_is_super', $isSuper);
        }

        // 获取管理员权限
        $permissions = Session::get('admin_permissions');
        if ($permissions === null) {
            $permissions = AdminUserModel::getPermissions($adminIdInt);
            Session::set('admin_permissions', $permissions);
        }

        // 注入权限数据到视图（供侧边栏渲染）
        View::assign([
            'is_super'    => $isSuper,
            'permissions' => $permissions,
        ]);

        // 超级管理员全部放行
        if ($isSuper) {
            return $next($request);
        }

        // 获取当前控制器名
        $controller = strtolower(request()->controller());

        // 允许访问的控制器（无需权限）
        $allowControllers = ['auth'];
        if (in_array($controller, $allowControllers)) {
            return $next($request);
        }

        // 检查是否有权限
        if (!in_array($controller, $permissions)) {
            if (request()->isAjax() || request()->isPost() || request()->isJson()) {
                exit(json_encode(['code' => 403, 'msg' => '无权限访问'], JSON_UNESCAPED_UNICODE));
            }
            exit('<div style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f5f5f5;font-family:sans-serif;">
                <div style="text-align:center;">
                    <div style="font-size:72px;font-weight:bold;color:#e74c3c;margin-bottom:8px;">403</div>
                    <div style="font-size:18px;color:#333;margin-bottom:4px;">无权限访问</div>
                    <div style="font-size:14px;color:#999;">请联系超级管理员开通权限</div>
                    <a href="javascript:history.back()" style="display:inline-block;margin-top:20px;padding:10px 24px;background:#e74c3c;color:#fff;text-decoration:none;border-radius:6px;font-size:14px;">返回上一页</a>
                </div>
            </div>');
        }

        return $next($request);
    }
}