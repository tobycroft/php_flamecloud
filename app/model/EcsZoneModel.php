<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * ECS可用区配置模型
 * 表 fc_ecs_zone
 */
class EcsZoneModel extends Model
{
    protected $table = 'fc_ecs_zone';

    protected $autoWriteTimestamp = false;

    /**
     * 分页列表
     */
    public static function getList(int $page = 1, int $limit = 15, array $filters = []): array
    {
        $query = self::order('sort', 'asc')->order('id', 'asc');

        $regionId = $filters['region_id'] ?? '';
        if ($regionId !== '') {
            $query->where('region_id', (int) $regionId);
        }

        $keyword = $filters['keyword'] ?? '';
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%');
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
     * 获取指定地域下的所有启用可用区
     */
    public static function getActiveByRegionId(int $regionId): array
    {
        return self::where('region_id', $regionId)->where('status', 1)->order('sort', 'asc')->order('id', 'asc')->select()->toArray();
    }
}