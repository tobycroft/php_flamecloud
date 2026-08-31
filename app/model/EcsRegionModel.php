<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * ECS地域配置模型
 * 表 fc_ecs_region
 */
class EcsRegionModel extends Model
{
    protected $table = 'fc_ecs_region';

    protected $autoWriteTimestamp = false;

    /**
     * 分页列表
     */
    public static function getList(int $page = 1, int $limit = 15, array $filters = []): array
    {
        $query = self::order('sort', 'asc')->order('id', 'asc');

        $keyword = $filters['keyword'] ?? '';
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                  ->whereOr('value', 'like', '%' . $keyword . '%');
            });
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        return ['list' => $list, 'total' => $total];
    }

    public static function getById(int $id): ?array
    {
        $row = self::find($id);
        return $row ? $row->toArray() : null;
    }

    /**
     * 获取所有启用的地域
     */
    public static function getActiveAll(): array
    {
        return self::where('status', 1)->order('sort', 'asc')->order('id', 'asc')->select()->toArray();
    }
}