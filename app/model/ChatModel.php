<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;
use think\facade\Db;

class ChatModel extends Model
{
    protected $table = 'fc_chat';

    protected $autoWriteTimestamp = false;

    public static function getUnreadUidList(): array
    {
        return Db::table('fc_chat')
            ->where('is_admin', 0)
            ->where('is_read', 0)
            ->distinct(true)
            ->field('uid')
            ->select()
            ->toArray();
    }

    public static function getUnreadCount(): int
    {
        $list = Db::table('fc_chat')
            ->where('is_admin', 0)
            ->where('is_read', 0)
            ->distinct(true)
            ->field('uid')
            ->select()
            ->toArray();
        return count($list);
    }

    public static function getListByUid(int $uid): array
    {
        return Db::table('fc_chat')
            ->where('uid', $uid)
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    public static function insertMessage(int $uid, string $content, int $is_admin): int
    {
        return Db::table('fc_chat')
            ->insertGetId([
                'uid'      => $uid,
                'content'  => $content,
                'is_admin' => $is_admin,
            ]);
    }

    public static function markRead(int $uid): bool
    {
        return Db::table('fc_chat')
            ->where('uid', $uid)
            ->where('is_admin', 0)
            ->where('is_read', 0)
            ->update(['is_read' => 1]) !== false;
    }
}