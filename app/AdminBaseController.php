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
     * 控制器初始化
     * 权限校验已移至 AdminAuth 中间件，此处仅做视图变量注入
     */
    protected function initialize()
    {
        parent::initialize();

        $adminId = (int) Session::get('admin_id');
        if ($adminId <= 0) {
            return;
        }

        // 权限校验由 AdminAuth 中间件完成，这里只注入视图变量
        // 如果中间件已注入，Session 中已有缓存数据
        $isSuper = Session::get('admin_is_super');
        $permissions = Session::get('admin_permissions');

        if ($isSuper === null) {
            $isSuper = AdminUserModel::isSuper($adminId);
            Session::set('admin_is_super', $isSuper);
        }
        if ($permissions === null) {
            $permissions = AdminUserModel::getPermissions($adminId);
            Session::set('admin_permissions', $permissions);
        }

        // admin_id=1 无条件的全权限
        if ($adminId === 1) {
            $isSuper = true;
            $permissions = array_keys(self::$permissionMap);
            Session::set('admin_is_super', $isSuper);
            Session::set('admin_permissions', $permissions);
        }

        View::assign([
            'is_super'    => $isSuper,
            'permissions' => $permissions,
        ]);
    }
}