<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 管理员个人设置模型
 * 表 admin_user_setting
 */
class AdminUserSettingModel extends Model
{
    protected $name = 'user_setting';

    protected $autoWriteTimestamp = 'datetime';

    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    public static function getByAdminId(int $adminId): ?self
    {
        return self::where('admin_id', $adminId)->find();
    }

    public static function getIdleTimeout(int $adminId): int
    {
        $setting = self::getByAdminId($adminId);
        if ($setting && (int) $setting->idle_timeout > 0) {
            return (int) $setting->idle_timeout;
        }
        return 30;
    }

    public static function saveSetting(int $adminId, int $idleTimeout): bool
    {
        $setting = self::getByAdminId($adminId);
        if ($setting) {
            $setting->idle_timeout = $idleTimeout;
            return $setting->save();
        }
        $setting = new self();
        $setting->admin_id    = $adminId;
        $setting->idle_timeout = $idleTimeout;
        return (bool) $setting->save();
    }
}