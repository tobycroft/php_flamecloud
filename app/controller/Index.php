<?php
declare (strict_types = 1);

namespace app\controller;

use app\AdminBaseController;
use think\facade\View;

/**
 * 后台首页控制器
 * 访问需要登录（见 $middleware 与路由分组）
 */
class Index extends AdminBaseController
{
    // 该控制器所有方法都需要登录
    protected $middleware = [\app\middleware\AdminAuth::class];

    public function index()
    {
        View::assign([
            'admin_name'     => $this->request->admin_name ?? '管理员',
            'admin_username' => $this->request->admin_username ?? '',
            'admin_id'       => $this->request->admin_id ?? 0,
        ]);
        return View::fetch('/index');
    }
}