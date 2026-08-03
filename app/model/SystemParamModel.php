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
}