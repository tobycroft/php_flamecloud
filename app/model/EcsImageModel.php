<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * ECS镜像配置模型
 * 表 fc_ecs_image
 */
class EcsImageModel extends Model
{
    protected $table = 'fc_ecs_image';

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

        $imageType = $filters['image_type'] ?? '';
        if ($imageType !== '') {
            $query->where('image_type', $imageType);
        }

        $keyword = $filters['keyword'] ?? '';
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('os', 'like', '%' . $keyword . '%')
                  ->whereOr('version', 'like', '%' . $keyword . '%');
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
     * 获取指定地域下的所有启用镜像
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