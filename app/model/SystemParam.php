<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

class SystemParam extends Model
{
    protected $name = 'system_param';

    public static function getVal(string $key, string $default = ''): string
    {
        $row = self::where('key', $key)->find();
        return $row ? (string) $row->val : $default;
    }
}
EOF 