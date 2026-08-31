<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * ECS磁盘类型配置模型
 * 表 fc_ecs_disk
 */
class EcsDiskModel extends Model
{
    protected $table = 'fc_ecs_disk';

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

        $zoneId = $filters['zone_id'] ?? '';
        if ($zoneId !== '') {
            $query->where('zone_id', (int) $zoneId);
        }

        $diskCategory = $filters['disk_category'] ?? '';
        if ($diskCategory !== '') {
            $query->where('disk_category', $diskCategory);
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
     * 获取指定地域下的所有启用磁盘类型
     */
    public static function getActiveByRegionId(int $regionId, int $zoneId = 0): array
    {
        $query = self::where('region_id', $regionId)->where('status', 1)->order('sort', 'asc')->order('id', 'asc');
        if ($zoneId > 0) {
            $query->where('zone_id', $zoneId);
        }
        return $query->select()->toArray();
    }
}