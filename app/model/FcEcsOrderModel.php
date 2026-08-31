<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * ECS订单模型
 * 表 fc_ecs_order（与 Go 后端共用）
 *
 * type: 1=云服务器, 2=网络, 3=存储, 4=资源管理, 5=其他
 * status: 0=待支付, 1=已支付, 2=已取消
 */
class FcEcsOrderModel extends Model
{
    protected $table = 'fc_ecs_order';

    protected $autoWriteTimestamp = false;

    const TYPE_MAP = [
        1 => '云服务器',
        2 => '网络',
        3 => '存储',
        4 => '资源管理',
        5 => '其他',
    ];

    const STATUS_MAP = [
        0 => ['text' => '待支付', 'color' => 'bg-yellow-100 text-yellow-700'],
        1 => ['text' => '已完成', 'color' => 'bg-green-100 text-green-700'],
        2 => ['text' => '已取消', 'color' => 'bg-gray-100 text-gray-500'],
    ];

    /**
     * 分页列表
     */
    public static function getList(int $page = 1, int $limit = 15, array $filters = []): array
    {
        $query = self::order('id', 'desc');

        $type = $filters['type'] ?? '';
        if ($type !== '') {
            $query->where('type', (int) $type);
        }

        $status = $filters['status'] ?? '';
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $keyword = $filters['keyword'] ?? '';
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('order_no', 'like', '%' . $keyword . '%')
                  ->whereOr('uid', $keyword);
            });
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

    public static function countByStatus(int $status): int
    {
        return self::where('status', $status)->count();
    }
}