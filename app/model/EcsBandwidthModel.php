<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * ECS带宽配置模型
 * 表 fc_ecs_bandwidth
 */
class EcsBandwidthModel extends Model
{
    protected $table = 'fc_ecs_bandwidth';

    protected $autoWriteTimestamp = false;

    /**
     * 分页列表
     */
    public static function getList(int $page = 1, int $limit = 15, array $filters = []): array
    {
        $query = self::order('id', 'asc');

        $regionId = $filters['region_id'] ?? '';
        if ($regionId !== '') {
            $query->where('region_id', (int) $regionId);
        }

        $zoneId = $filters['zone_id'] ?? '';
        if ($zoneId !== '') {
            $query->where('zone_id', (int) $zoneId);
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
     * 获取指定地域下的带宽配置
     */
    public static function getActiveByRegionId(int $regionId, int $zoneId = 0): ?array
    {
        $query = self::where('region_id', $regionId)->where('status', 1);
        if ($zoneId > 0) {
            $query->where('zone_id', $zoneId);
        }
        $row = $query->find();
        return $row ? $row->toArray() : null;
    }
}