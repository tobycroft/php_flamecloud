<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * ECS实例模型
 * 表 fc_ecs_instance（与 Go 后端共用）
 */
class FcEcsInstanceModel extends Model
{
    protected $table = 'fc_ecs_instance';

    protected $autoWriteTimestamp = false;

    /**
     * 分页列表
     */
    public static function getList(int $page = 1, int $limit = 15, array $filters = []): array
    {
        $query = self::order('id', 'desc');

        $keyword = $filters['keyword'] ?? '';
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('instance_id', 'like', '%' . $keyword . '%')
                  ->whereOr('instance_name', 'like', '%' . $keyword . '%')
                  ->whereOr('uid', $keyword)
                  ->whereOr('remark', 'like', '%' . $keyword . '%');
            });
        }

        $status = $filters['status'] ?? '';
        if ($status !== '') {
            $query->where('status', $status);
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        return ['list' => $list, 'total' => $total];
    }

    public static function findById(int $id): ?array
    {
        $row = self::find($id);
        return $row ? $row->toArray() : null;
    }

    public static function countByStatus(string $status): int
    {
        return self::where('status', $status)->count();
    }
}