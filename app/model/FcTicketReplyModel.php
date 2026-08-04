<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 工单回复模型
 * 表 fc_ticket_reply（与 Go 后端共用，显式指定表名）
 *
 * is_admin: 0=用户回复, 1=客服回复
 * uid: 用户回复时为用户ID，客服回复时为管理员ID
 */
class FcTicketReplyModel extends Model
{
    protected $table = 'fc_ticket_reply';

    protected $autoWriteTimestamp = 'timestamp';

    protected $createTime = 'created_at';
    protected $updateTime = false;

    /**
     * 按工单ID获取全部回复（正序，盖楼展示）
     */
    public static function listByTicketId(int $ticketId): array
    {
        return self::where('ticket_id', $ticketId)
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 客服回复
     * @param int    $ticketId  工单ID
     * @param int    $adminId   管理员ID
     * @param string $content   回复内容
     * @return int 回复ID，0=失败
     */
    public static function adminReply(int $ticketId, int $adminId, string $content): int
    {
        $reply = new self();
        $reply->ticket_id = $ticketId;
        $reply->uid        = $adminId;
        $reply->content    = $content;
        $reply->is_admin   = 1;
        return $reply->save() ? (int) $reply->id : 0;
    }
}
