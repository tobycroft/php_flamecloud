<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * ECS购买周期与折扣配置模型
 * 表 fc_ecs_period
 */
class EcsPeriodModel extends Model
{
    protected $table = 'fc_ecs_period';

    protected $autoWriteTimestamp = false;

    /**
     * 分页列表
     */
    public static function getList(int $page = 1, int $limit = 15, array $filters = []): array
    {
        $query = self::order('sort', 'asc');
        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        return ['list' => $list, 'total' => $total];
    }

    public static function getById(int $id): ?array
    {
        $row = self::find($id);
        return $row ? $row->toArray() : null;
    }
}
