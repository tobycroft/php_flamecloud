<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

class SystemParamModel extends Model
{
    protected $table = 'system_param';

    public static function getVal(string $key, string $default = ''): string
    {
        $row = self::where('key', $key)->find();
        return $row ? (string) $row->value : $default;
    }

    public static function getAll(): array
    {
        return self::order('id', 'asc')->select()->toArray();
    }

    public static function setVal(int $id, string $value): bool
    {
        $row = self::find($id);
        if (!$row) {
            return false;
        }
        $row->value = $value;
        return (bool) $row->save();
    }

    /**
     * 按 key 设置值，不存在则创建
     */
    public static function setValByKey(string $key, string $value): bool
    {
        $row = self::where('key', $key)->find();
        if ($row) {
            $row->value = $value;
            return (bool) $row->save();
        } else {
            $self = new self();
            $self->key = $key;
            $self->value = $value;
            return (bool) $self->save();
        }
    }
}