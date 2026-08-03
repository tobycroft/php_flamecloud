<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 后台登录日志模型
 * 表 admin_login_log（DB_PREFIX=admin_ + name=login_log）
 */
class AdminLoginLogModel extends Model
{
    protected $name = 'login_log';

    protected $autoWriteTimestamp = 'datetime';

    // 该表只有 create_time，无 update_time
    protected $updateTime = false;

    /**
     * 记录一次登录尝试
     */
    public static function record(int $adminId, string $username, string $ip, string $ua, bool $ok): void
    {
        self::create([
            'admin_id'   => $adminId,
            'username'   => $username,
            'ip'         => $ip,
            'user_agent' => mb_substr($ua, 0, 255),
            'status'     => $ok ? 1 : 0,
        ]);
    }
}