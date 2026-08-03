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
    protected $name = 'user';

    protected $autoWriteTimestamp = 'datetime';

    protected $hidden = ['password'];

    public static function findByUsername(string $username)
    {
        return self::where('username', $username)->find();
    }

    public static function findById(int $id)
    {
        return self::find($id);
    }

    public static function updateLastLogin(int $id, string $ip): void
    {
        self::update([
            'id'              => $id,
            'last_login_ip'   => $ip,
            'last_login_time' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function getList(int $page = 1, int $limit = 15, string $keyword = ''): array
    {
        $query = self::order('id', 'desc');
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('username', 'like', '%' . $keyword . '%')
                  ->whereOr('nickname', 'like', '%' . $keyword . '%');
            });
        }
        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();
        return ['total' => $total, 'list' => $list];
    }

    public static function add(array $data): bool
    {
        $admin = new self();
        $admin->username = $data['username'];
        $admin->password = md5($data['password']);
        $admin->nickname = $data['nickname'] ?? '';
        $admin->avatar   = $data['avatar'] ?? '';
        $admin->status   = $data['status'] ?? 1;
        return $admin->save();
    }

    public static function edit(int $id, array $data): bool
    {
        $admin = self::find($id);
        if (empty($admin)) {
            return false;
        }
        if (!empty($data['nickname'])) {
            $admin->nickname = $data['nickname'];
        }
        if (isset($data['avatar'])) {
            $admin->avatar = $data['avatar'];
        }
        if (isset($data['status'])) {
            $admin->status = (int) $data['status'];
        }
        if (!empty($data['password'])) {
            $admin->password = md5($data['password']);
        }
        return $admin->save();
    }

    public static function setStatus(int $id, int $status): bool
    {
        $admin = self::find($id);
        if (empty($admin)) {
            return false;
        }
        $admin->status = $status;
        return $admin->save();
    }

    public static function remove(int $id): bool
    {
        $admin = self::find($id);
        if (empty($admin)) {
            return false;
        }
        return $admin->delete();
    }
}