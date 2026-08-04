<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 工单附件模型
 * 表 fc_ticket_attachment（与 Go 后端共用）
 */
class FcTicketAttachmentModel extends Model
{
    protected $table = 'fc_ticket_attachment';

    protected $autoWriteTimestamp = false;

    public static function listByTicketId(int $ticketId): array
    {
        return self::where('ticket_id', $ticketId)
            ->where('reply_id', 0)
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    public static function listByReplyId(int $replyId): array
    {
        return self::where('reply_id', $replyId)
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    public static function insertBatchReply(int $ticketId, int $replyId, int $uid, array $files): bool
    {
        $data = [];
        $now  = date('Y-m-d H:i:s');
        foreach ($files as $f) {
            $data[] = [
                'ticket_id'  => $ticketId,
                'reply_id'   => $replyId,
                'uid'        => $uid,
                'name'       => $f['name'] ?? '',
                'url'        => $f['url'] ?? '',
                'hash'       => $f['hash'] ?? '',
                'created_at' => $now,
            ];
        }
        return !empty($data) && (bool) self::insertAll($data);
    }
}