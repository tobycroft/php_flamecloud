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
     * key = 控制器名(小写), value = 中文名称（保留兼容）
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

    /**
     * 权限菜单层级结构（匹配 sidebar）
     * 父级组名 → [子控制器列表]
     * 勾选父级自动继承所有子权限
     */
    public static array $permissionGroups = [
        '首页'     => ['index'],
        '用户管理' => ['user'],
        '财务管理' => ['recharge_audit', 'user_balance', 'balance_record'],
        '实名认证' => ['user_verification'],
        '工单系统' => ['ticket'],
        'ECS管理'  => ['ecs_instance', 'ecs_order'],
        '在线客服' => ['chat'],
        '管理员管理' => ['admin', 'admin_log', 'admin_log_login'],
        '系统设置' => ['system_param', 'admin_setting'],
    ];

    /**
     * 控制器名 → 所属父级组名
     */
    public static array $controllerParent = [
        'index'            => '首页',
        'user'             => '用户管理',
        'recharge_audit'   => '财务管理',
        'user_balance'     => '财务管理',
        'balance_record'   => '财务管理',
        'user_verification'=> '实名认证',
        'ticket'           => '工单系统',
        'ecs_instance'     => 'ECS管理',
        'ecs_order'        => 'ECS管理',
        'chat'             => '在线客服',
        'admin'            => '管理员管理',
        'admin_log'        => '管理员管理',
        'admin_log_login'  => '管理员管理',
        'system_param'     => '系统设置',
        'admin_setting'    => '系统设置',
    ];

    /**
     * 检查控制器是否有权限
     * 允许直接匹配 或 父级组名匹配
     */
    public static function checkPermission(string $controller, array $permissions): bool
    {
        // 直接匹配
        if (in_array($controller, $permissions)) {
            return true;
        }
        // 父级组匹配
        $parent = self::$controllerParent[$controller] ?? null;
        if ($parent && in_array($parent, $permissions)) {
            return true;
        }
        return false;
    }

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
        // 每次都从数据库拉取，避免 session 缓存过时数据

        // admin_id=1 无条件的全权限
        if ($adminId === 1) {
            $isSuper = true;
            $permissions = array_keys(self::$permissionMap);
        } else {
            $isSuper = AdminUserModel::isSuper($adminId);
            $permissions = $isSuper ? array_keys(self::$permissionMap) : AdminUserModel::getPermissions($adminId);
        }

        // 同步到 session（供中间件或后续使用，但不再依赖 session 缓存）
        Session::set('admin_is_super', $isSuper);
        Session::set('admin_permissions', $permissions);

        View::assign([
            'is_super'          => $isSuper,
            'permissions'       => $permissions,
            'admin_name'        => Session::get('admin_name', ''),
            'admin_username'    => Session::get('admin_username', ''),
            'admin_id'          => $adminId,
            'controller_parent' => self::$controllerParent,
        ]);
    }
}