<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 用户站内信模型
 * 表 fc_user_notification（与 Go 后端共用）
 *
 * 用于客服回复工单后向用户推送站内信
 */
class FcUserNotificationModel extends Model
{
    protected $table = 'fc_user_notification';

    protected $autoWriteTimestamp = 'timestamp';

    protected $createTime = 'created_at';
    protected $updateTime = false;

    /**
     * 推送站内信
     * @param int    $uid     接收用户ID
     * @param string $title   标题
     * @param string $content 内容
     * @param string $type    类型 info/success/warning/system
     * @return int 通知ID，0=失败
     */
    public static function push(int $uid, string $title, string $content = '', string $type = 'info'): int
    {
        $n = new self();
        $n->uid     = $uid;
        $n->title   = $title;
        $n->content = $content;
        $n->type    = $type;
        $n->is_read = 0;
        return $n->save() ? (int) $n->id : 0;
    }
}
