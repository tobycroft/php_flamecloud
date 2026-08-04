<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 余额流水模型
 * 表 fc_balance_record（与 Go 后端共用）
 *
 * type: 1=充值, 2=消费, 3=退款, 4=系统调整
 */
class FcBalanceRecordModel extends Model
{
    protected $table = 'fc_balance_record';

    protected $autoWriteTimestamp = false;

    const TYPE_MAP = [
        1 => '充值',
        2 => '消费',
        3 => '退款',
        4 => '系统调整',
    ];

    public static function insertRecord(array $data): bool
    {
        $record = new self();
        $record->save($data);
        return (bool) $record->id;
    }

    /**
     * 分页列表（支持类型、UID、关键词、日期筛选）
     */
    public static function getList(int $page = 1, int $limit = 15, array $filters = []): array
    {
        $query = self::order('id', 'desc');

        $type = $filters['type'] ?? '';
        if ($type !== '') {
            $query->where('type', (int) $type);
        }

        $uid = $filters['uid'] ?? '';
        if ($uid !== '') {
            $query->where('uid', (int) $uid);
        }

        $keyword = $filters['keyword'] ?? '';
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('order_no', 'like', '%' . $keyword . '%')
                  ->whereOr('description', 'like', '%' . $keyword . '%');
            });
        }

        $dateStart = $filters['date_start'] ?? '';
        if ($dateStart !== '') {
            $query->where('created_at', '>=', $dateStart . ' 00:00:00');
        }
        $dateEnd = $filters['date_end'] ?? '';
        if ($dateEnd !== '') {
            $query->where('created_at', '<=', $dateEnd . ' 23:59:59');
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        return ['list' => $list, 'total' => $total];
    }
}