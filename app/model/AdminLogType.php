<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 管理员操作日志类型模型
 * 表 admin_log_type（DB_PREFIX=admin_ + name=log_type）
 */
class AdminLogType extends Model
{
    protected $name = 'log_type';

    protected $autoWriteTimestamp = 'datetime';

    protected $updateTime = false;

    public static function getAll(): array
    {
        return self::order('id', 'asc')->select()->toArray();
    }

    public static function findByCode(string $code)
    {
        return self::where('code', $code)->find();
    }
}