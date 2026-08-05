<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

class FcUserVerificationModel extends Model
{
    protected $table = 'fc_user_verification';

    protected $autoWriteTimestamp = false;

    const TYPE_MAP = [
        1 => '个人认证',
        2 => '企业认证',
    ];

    const STATUS_MAP = [
        0 => ['text' => '待审核', 'color' => 'bg-blue-100 text-blue-700'],
        1 => ['text' => '已通过', 'color' => 'bg-green-100 text-green-700'],
        2 => ['text' => '已拒绝', 'color' => 'bg-red-100 text-red-700'],
    ];

    public static function findById(int $id): ?array
    {
        $row = self::find($id);
        return $row ? $row->toArray() : null;
    }

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
                $q->where('real_name', 'like', '%' . $keyword . '%')
                  ->whereOr('id_card', 'like', '%' . $keyword . '%')
                  ->whereOr('company_name', 'like', '%' . $keyword . '%')
                  ->whereOr('uid', $keyword);
            });
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();
        return ['total' => $total, 'list' => $list];
    }

    public static function countByStatus(int $status): int
    {
        return (int) self::where('status', $status)->count();
    }

    public static function updateStatus(int $id, int $status, string $remark = ''): bool
    {
        $row = self::find($id);
        if (empty($row)) {
            return false;
        }
        $row->status = $status;
        if ($remark !== '') {
            $row->remark = $remark;
        }
        return $row->save();
    }

    public static function updateData(int $id, array $data): bool
    {
        $row = self::find($id);
        if (empty($row)) {
            return false;
        }
        return $row->save($data);
    }
}