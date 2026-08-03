<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 管理员操作日志模型
 * 表 admin_log（DB_PREFIX=admin_ + name=log）
 */
class AdminLog extends Model
{
    protected $name = 'log';

    protected $autoWriteTimestamp = 'datetime';

    protected $updateTime = false;

    public static function record(array $data): void
    {
        self::create([
            'admin_id'   => $data['admin_id'] ?? 0,
            'admin_name' => $data['admin_name'] ?? '',
            'type_code'  => $data['type_code'] ?? '',
            'action'     => $data['action'] ?? '',
            'detail'     => $data['detail'] ?? '',
            'target_type' => $data['target_type'] ?? '',
            'target_id'  => $data['target_id'] ?? 0,
            'ip'         => $data['ip'] ?? '',
            'user_agent' => mb_substr($data['user_agent'] ?? '', 0, 255),
        ]);
    }

    public static function getList(int $page = 1, int $limit = 15, string $typeCode = '', string $keyword = ''): array
    {
        $query = self::order('id', 'desc');

        if ($typeCode !== '') {
            $query->where('type_code', $typeCode);
        }

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('admin_name', 'like', '%' . $keyword . '%')
                  ->whereOr('action', 'like', '%' . $keyword . '%')
                  ->whereOr('detail', 'like', '%' . $keyword . '%');
            });
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();
        return ['total' => $total, 'list' => $list];
    }
}