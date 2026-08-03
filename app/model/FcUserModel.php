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
}