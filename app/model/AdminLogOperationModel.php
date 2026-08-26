<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 管理员操作日志模型
 * 表 admin_log_operation（DB_PREFIX=admin_ + name=log_operation）
 */
class AdminLogOperationModel extends Model
{
    protected $name = 'log_operation';

    protected $autoWriteTimestamp = 'datetime';

    protected $updateTime = false;

    /**
     * 日志快捷方法
     * 支持两种调用方式：
     *   AdminLogOperationModel::log($adminId, $typeCode, $detail)
     *   AdminLogOperationModel::log($metaArray, $typeCode, $targetId, $detail)
     */
    public static function log($meta, string $typeCode, $targetOrDetail = '', string $detail = ''): void
    {
        if (is_array($meta)) {
            // 方式二：传入元数据数组
            self::record(array_merge($meta, [
                'type_code'   => $typeCode,
                'action'      => $typeCode,
                'target_id'   => is_numeric($targetOrDetail) ? (int) $targetOrDetail : 0,
                'detail'      => $detail ?: (string) $targetOrDetail,
            ]));
        } else {
            // 方式一：直接传入 admin_id
            self::record([
                'admin_id'   => (int) $meta,
                'admin_name' => (string) session('admin_name', ''),
                'type_code'  => $typeCode,
                'action'     => $typeCode,
                'detail'     => $targetOrDetail ?: $detail,
                'ip'         => request()->ip(),
                'user_agent' => mb_substr((string) request()->header('user-agent', ''), 0, 255),
            ]);
        }
    }

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