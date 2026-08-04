<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 工单额外联系方式模型
 * 表 fc_ticket_contact（与 Go 后端共用）
 */
class FcTicketContactModel extends Model
{
    protected $table = 'fc_ticket_contact';

    protected $autoWriteTimestamp = false;

    public static function listByTicketId(int $ticketId): array
    {
        return self::where('ticket_id', $ticketId)
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }
}
