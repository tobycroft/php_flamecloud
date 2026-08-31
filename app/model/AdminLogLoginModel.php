<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 后台登录日志模型
 * 表 admin_log_login（DB_PREFIX=admin_ + name=log_login）
 */
class AdminLogLoginModel extends Model
{
    protected $name = 'log_login';

    protected $autoWriteTimestamp = 'datetime';

    protected $updateTime = false;

    public static function record(int $adminId, string $username, string $ip, string $ua, bool $ok, string $reason = ''): void
    {
        self::create([
            'admin_id'   => $adminId,
            'username'   => $username,
            'ip'         => $ip,
            'user_agent' => mb_substr($ua, 0, 255),
            'status'     => $ok ? 1 : 0,
            'reason'     => mb_substr($reason, 0, 255),
        ]);
    }

    public static function getList(int $page = 1, int $limit = 15, string $keyword = '', string $status = ''): array
    {
        $query = self::order('id', 'desc');

        if ($keyword !== '') {
            $query->where('username', 'like', '%' . $keyword . '%');
        }

        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();
        return ['total' => $total, 'list' => $list];
    }
}