<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;
use think\facade\Db;

/**
 * 前台工单模型
 * 表 fc_ticket（与 Go 后端共用，显式指定表名）
 *
 * 状态机：
 *  0 = 待回复（默认，刚创建）
 *  1 = 客户发送（客户追加回复后）
 *  2 = 客服答复（客服回复后）
 *  3 = 结案关闭
 */
class FcTicketModel extends Model
{
    protected $table = 'fc_ticket';

    protected $autoWriteTimestamp = 'timestamp';

    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /**
     * 后台分页列表（带搜索 / 状态 / 分类 / 日期范围）
     */
    public static function getList(int $page = 1, int $limit = 15, array $filters = []): array
    {
        $query = self::whereNull('deleted_at')->order('id', 'desc');

        $keyword = $filters['keyword'] ?? '';
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('description', 'like', '%' . $keyword . '%')
                  ->whereOr('id', $keyword)
                  ->whereOr('contact_phone', 'like', '%' . $keyword . '%');
            });
        }

        $status = $filters['status'] ?? '';
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $category = $filters['category'] ?? '';
        if ($category !== '') {
            $query->where('category', $category);
        }

        $urgency = $filters['urgency'] ?? '';
        if ($urgency !== '') {
            $query->where('urgency', $urgency);
        }

        $dateStart = $filters['date_start'] ?? '';
        if ($dateStart !== '') {
            $query->whereTime('created_at', '>=', $dateStart . ' 00:00:00');
        }

        $dateEnd = $filters['date_end'] ?? '';
        if ($dateEnd !== '') {
            $query->whereTime('created_at', '<=', $dateEnd . ' 23:59:59');
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        // 附带用户名 / 手机号（批量查询避免 N+1）
        if (!empty($list)) {
            $uidList = array_values(array_unique(array_filter(array_column($list, 'uid'))));
            if (!empty($uidList)) {
                $rows = Db::table('fc_user')
                    ->whereIn('id', $uidList)
                    ->field('id,username,phone,email')
                    ->select()
                    ->toArray();
                $users = [];
                foreach ($rows as $u) {
                    $users[(int) $u['id']] = $u;
                }
                foreach ($list as &$item) {
                    $uid = (int) $item['uid'];
                    $item['user_username'] = $users[$uid]['username'] ?? '';
                    $item['user_phone']    = $users[$uid]['phone'] ?? '';
                    $item['user_email']    = $users[$uid]['email'] ?? '';
                }
                unset($item);
            }
        }

        return ['total' => $total, 'list' => $list];
    }

    /**
     * 按 ID 查询工单（附带用户信息）
     */
    public static function findById(int $id): ?array
    {
        $ticket = self::whereNull('deleted_at')->find($id);
        if (empty($ticket)) {
            return null;
        }
        $data = $ticket->toArray();
        // 附带用户信息
        $user = Db::table('fc_user')->where('id', $data['uid'])->field('id,username,phone,email')->find();
        $data['user_username'] = $user['username'] ?? '';
        $data['user_phone']    = $user['phone'] ?? '';
        $data['user_email']    = $user['email'] ?? '';
        return $data;
    }

    /**
     * 更新工单状态
     */
    public static function updateStatus(int $id, int $status): bool
    {
        return self::update(['id' => $id, 'status' => $status]) !== false;
    }

    /**
     * 统计待客服回复的工单数（status=1 客户发送）
     * 用于 sidebar 红点提醒
     */
    public static function countPendingReply(): int
    {
        return (int) self::whereNull('deleted_at')->where('status', 1)->count();
    }

    /**
     * 各状态计数（用于列表页 Tab 统计）
     */
    public static function countByStatus(): array
    {
        $rows = self::whereNull('deleted_at')
            ->field('status, COUNT(*) as cnt')
            ->group('status')
            ->select()
            ->toArray();
        $ret = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
        foreach ($rows as $r) {
            $ret[(int) $r['status']] = (int) $r['cnt'];
        }
        return $ret;
    }

    /**
     * 更新最后回复时间
     */
    public static function updateLastReplyAt(int $id): bool
    {
        return self::update(['id' => $id, 'last_reply_at' => date('Y-m-d H:i:s')]) !== false;
    }

    /**
     * 聊天工单转标准工单
     */
    public static function convertToStandard(int $id, string $category, string $urgency): bool
    {
        return self::update([
            'id'          => $id,
            'ticket_type' => 'standard',
            'category'    => $category,
            'urgency'     => $urgency,
        ]) !== false;
    }

    /**
     * 统计聊天工单数
     */
    public static function countChat(): int
    {
        return (int) self::whereNull('deleted_at')
            ->where('ticket_type', 'chat')
            ->where('status', '<>', 3)
            ->count();
    }
}