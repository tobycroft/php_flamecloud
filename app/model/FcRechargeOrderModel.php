<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 充值订单模型
 * 表 fc_recharge_order（与 Go 后端共用）
 *
 * status: 0=待支付, 1=已完成, 2=已取消, 3=审核中, 4=已失败
 * type:   1=在线充值, 2=线下充值
 */
class FcRechargeOrderModel extends Model
{
    protected $table = 'fc_recharge_order';

    protected $autoWriteTimestamp = false;

    const STATUS_MAP = [
        0 => ['text' => '待支付',  'color' => 'bg-yellow-100 text-yellow-700'],
        1 => ['text' => '已完成',  'color' => 'bg-green-100 text-green-700'],
        2 => ['text' => '已取消',  'color' => 'bg-gray-100 text-gray-500'],
        3 => ['text' => '审核中',  'color' => 'bg-blue-100 text-blue-700'],
        4 => ['text' => '已失败',  'color' => 'bg-red-100 text-red-700'],
    ];

    const TYPE_MAP = [
        1 => '在线充值',
        2 => '线下充值',
    ];

    const PAY_METHOD_MAP = [
        'alipay'          => '支付宝',
        'wechat'          => '微信支付',
        'bank_transfer'   => '银行转账',
        'alipay_transfer' => '支付宝转账',
        'wechat_transfer' => '微信转账',
    ];

    /**
     * 分页列表
     */
    public static function getList(int $page = 1, int $limit = 15, array $filters = []): array
    {
        $query = self::order('id', 'desc');

        $status = $filters['status'] ?? '';
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $type = $filters['type'] ?? '';
        if ($type !== '') {
            $query->where('type', (int) $type);
        }

        $keyword = $filters['keyword'] ?? '';
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('order_no', 'like', '%' . $keyword . '%')
                  ->whereOr('uid', $keyword)
                  ->whereOr('transaction_id', 'like', '%' . $keyword . '%');
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

    public static function findById(int $id): ?array
    {
        $row = self::find($id);
        return $row ? $row->toArray() : null;
    }

    public static function updateStatus(int $id, int $status): bool
    {
        return (bool) self::where('id', $id)->update(['status' => $status]);
    }

    public static function countByStatus(int $status): int
    {
        return self::where('status', $status)->count();
    }
}