<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;
use think\facade\Db;

/**
 * 后台管理员模型
 * 表 admin_user（DB_PREFIX=admin_ + name=user）
 */
class AdminUser extends Model
{
    // 去掉前缀的表名（实际表名 = admin_user）
    protected $name = 'user';

    // 自动时间戳（datetime 格式）
    protected $autoWriteTimestamp = 'datetime';

    // 密码字段不在查询时自动输出由控制器控制
    protected $hidden = ['password'];

    /**
     * 根据用户名查询管理员
     */
    public static function findByUsername(string $username)
    {
        return self::where('username', $username)->find();
    }

    /**
     * 根据 ID 查询
     */
    public static function findById(int $id)
    {
        return self::find($id);
    }

    /**
     * 更新最后登录信息
     */
    public static function updateLastLogin(int $id, string $ip): void
    {
        self::update([
            'id'              => $id,
            'last_login_ip'   => $ip,
            'last_login_time' => date('Y-m-d H:i:s'),
        ]);
    }
}
