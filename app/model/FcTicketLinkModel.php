<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 工单链接模型
 * 表 fc_ticket_link（与 Go 后端共用）
 */
class FcTicketLinkModel extends Model
{
    protected $table = 'fc_ticket_link';

    protected $autoWriteTimestamp = false;

    public static function listByTicketId(int $ticketId): array
    {
        return self::where('ticket_id', $ticketId)
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }
}
