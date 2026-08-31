<?php
declare (strict_types = 1);

namespace app;

use think\App;
use think\facade\Session;
use think\facade\View;
use app\model\AdminUserModel;

/**
 * 后台管理控制器基类
 * 包含管理员权限校验，在 initialize() 中自动执行
 */
abstract class AdminBaseController extends BaseController
{
    /**
     * 所有后台权限映射
     * key = 控制器名(小写), value = 中文名称
     */
    public static array $permissionMap = [
        'index'            => '首页',
        'user'             => '用户管理',
        'recharge_audit'   => '充值审核',
        'user_balance'     => '用户余额',
        'balance_record'   => '交易流水',
        'user_verification'=> '实名认证',
        'ticket'           => '工单系统',
        'ecs_instance'     => 'ECS实例管理',
        'ecs_order'        => 'ECS订单管理',
        'chat'             => '在线客服',
        'admin'            => '管理员管理',
        'admin_log'        => '操作日志',
        'admin_log_login'  => '登录日志',
        'system_param'     => '参数配置',
        'admin_setting'    => '个人设置',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * 控制器初始化 - 权限校验
     * 超级管理员拥有所有权限，非超级管理员仅可访问被授权的控制器
     */
    protected function initialize()
    {
        parent::initialize();

        $adminId = (int) Session::get('admin_id');
        if ($adminId <= 0) {
            return;
        }

        // admin_id=1 始终为超级管理员，跳过权限校验
        if ($adminId === 1) {
            $isSuper = true;
            Session::set('admin_is_super', $isSuper);
            View::assign([
                'is_super'    => $isSuper,
                'permissions' => array_keys(self::$permissionMap),
            ]);
            return;
        }

        // 超级管理员跳过权限校验
        $isSuper = Session::get('admin_is_super');
        if ($isSuper === null) {
            $isSuper = AdminUserModel::isSuper((int) $adminId);
            Session::set('admin_is_super', $isSuper);
        }

        // 注入权限数据到视图（供侧边栏渲染使用）
        View::assign([
            'is_super'    => $isSuper,
            'permissions' => Session::get('admin_permissions', AdminUserModel::getPermissions((int) $adminId)),
        ]);

        if ($isSuper) {
            return;
        }

        // 获取当前控制器名
        $controller = strtolower($this->request->controller());

        // 允许访问的控制器（无需权限）
        $allowControllers = ['auth'];

        // 不在白名单中的控制器需要校验权限
        if (in_array($controller, $allowControllers)) {
            return;
        }

        // 获取管理员权限
        $permissions = Session::get('admin_permissions');
        if ($permissions === null) {
            $permissions = AdminUserModel::getPermissions((int) $adminId);
            Session::set('admin_permissions', $permissions);
        }

        if (!in_array($controller, $permissions)) {
            // 判断请求类型
            if ($this->request->isAjax() || $this->request->isPost() || $this->request->isJson()) {
                exit(json_encode(['code' => 403, 'msg' => '无权限访问'], JSON_UNESCAPED_UNICODE));
            }
            // 页面请求直接输出无权限提示
            exit('<div style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f5f5f5;font-family:sans-serif;">
                <div style="text-align:center;">
                    <div style="font-size:72px;font-weight:bold;color:#e74c3c;margin-bottom:8px;">403</div>
                    <div style="font-size:18px;color:#333;margin-bottom:4px;">无权限访问</div>
                    <div style="font-size:14px;color:#999;">请联系超级管理员开通权限</div>
                    <a href="javascript:history.back()" style="display:inline-block;margin-top:20px;padding:10px 24px;background:#e74c3c;color:#fff;text-decoration:none;border-radius:6px;font-size:14px;">返回上一页</a>
                </div>
            </div>');
        }
    }
}