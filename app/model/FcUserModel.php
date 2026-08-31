<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 前台用户模型
 * 表 fc_user（无前缀，显式指定表名）
 */
class FcUserModel extends Model
{
    protected $table = 'fc_user';

    protected $autoWriteTimestamp = 'timestamp';

    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $hidden = ['password'];

    public static function findById(int $id)
    {
        return self::find($id);
    }

    public static function getList(int $page = 1, int $limit = 15, string $keyword = ''): array
    {
        $query = self::order('id', 'desc');
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('username', 'like', '%' . $keyword . '%')
                  ->whereOr('phone', 'like', '%' . $keyword . '%')
                  ->whereOr('email', 'like', '%' . $keyword . '%');
            });
        }
        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();
        return ['total' => $total, 'list' => $list];
    }

    public static function setStatus(int $id, int $status): bool
    {
        $user = self::find($id);
        if (empty($user)) {
            return false;
        }
        $user->status = $status;
        return $user->save();
    }

    public static function edit(int $id, array $data): bool
    {
        $user = self::find($id);
        if (empty($user)) {
            return false;
        }

        // 更新 fc_user 表字段
        $userData = [];
        if (isset($data['phone'])) {
            $userData['phone'] = $data['phone'];
        }
        if (isset($data['email'])) {
            $userData['email'] = $data['email'];
        }
        if (isset($data['password']) && $data['password'] !== '') {
            $userData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (!empty($userData)) {
            self::where('id', $id)->update($userData);
        }

        // 更新 fc_user_info 表字段
        $infoData = [];
        if (isset($data['name'])) {
            $infoData['name'] = $data['name'];
        }
        if (isset($data['company'])) {
            $infoData['company'] = $data['company'];
        }
        if (isset($data['department'])) {
            $infoData['department'] = $data['department'];
        }

        if (!empty($infoData)) {
            $info = self::where('id', $id)->find();
            if ($info) {
                // 使用 Db 类直接操作 fc_user_info 表
                $db = \think\facade\Db::table('fc_user_info');
                $exists = $db->where('uid', $id)->find();
                if ($exists) {
                    $db->where('uid', $id)->update($infoData);
                } else {
                    $infoData['uid'] = $id;
                    $db->insert($infoData);
                }
            }
        }

        return true;
    }

    public static function getInfoByUid(int $uid): ?array
    {
        $info = \think\facade\Db::table('fc_user_info')->where('uid', $uid)->find();
        return $info ?: null;
    }
}