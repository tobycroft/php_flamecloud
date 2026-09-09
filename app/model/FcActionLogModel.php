<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 前台用户行为日志模型
 * 表 fc_action_log（无前缀，显式指定表名）
 * log_type_id: 1=用户 2=系统 3=AK 4=登录 5=安全
 */
class FcActionLogModel extends Model
{
    protected $table = 'fc_action_log';

    protected $autoWriteTimestamp = false;

    /** 登录类型日志 */
    const TYPE_LOGIN = 4;

    /**
     * 获取指定用户的登录记录
     */
    public static function getLoginList(int $uid, int $page = 1, int $limit = 15): array
    {
        $query = self::where('uid', $uid)
            ->where('log_type_id', self::TYPE_LOGIN)
            ->order('id', 'desc');

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();
        return ['total' => $total, 'list' => $list];
    }

    /**
     * 获取指定用户最后一次登录记录
     */
    public static function getLastLogin(int $uid): array
    {
        $row = self::where('uid', $uid)
            ->where('log_type_id', self::TYPE_LOGIN)
            ->order('id', 'desc')
            ->find();
        return $row ? $row->toArray() : [];
    }
}
