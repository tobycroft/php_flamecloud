<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\FcTicketModel;
use app\model\FcTicketReplyModel;
use app\model\FcTicketAttachmentModel;
use app\model\FcTicketLinkModel;
use app\model\FcTicketContactModel;
use app\model\FcUserNotificationModel;
use app\model\AdminLogOperationModel;
use think\facade\Session;
use think\facade\View;

/**
 * 后台工单管理控制器
 *
 * 状态机：
 *  0 = 待回复（默认）
 *  1 = 客户发送（客户追加回复）
 *  2 = 客服答复（客服回复）
 *  3 = 结案关闭
 */
class Ticket extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    // 状态映射
    const STATUS_MAP = [
        0 => ['text' => '待回复',   'color' => 'bg-yellow-50 text-yellow-600'],
        1 => ['text' => '客户发送', 'color' => 'bg-blue-50 text-blue-600'],
        2 => ['text' => '客服答复', 'color' => 'bg-orange-50 text-orange-600'],
        3 => ['text' => '结案关闭', 'color' => 'bg-gray-100 text-gray-500'],
    ];

    const URGENCY_MAP = [
        'fault'   => '产品故障',
        'usage'   => '产品使用问题',
        'consult' => '产品咨询',
    ];

    const CATEGORY_MAP = [
        'ecs'         => 'ECS',
        'oss'         => '文件存储',
        'rds'         => '云数据库MySQL',
        'waf'         => 'WAF',
        'vpc'         => 'VPC',
        'ddos'        => 'DDoS高防防护',
        'metal'       => '裸金属',
        'elastic_ip'  => '弹性IP',
        'auto_scale'  => '负载均衡',
        'other'       => '其他',
    ];

    private function getLogMeta(): array
    {
        return [
            'admin_id'   => (int) Session::get('admin_id', 0),
            'admin_name' => (string) Session::get('admin_name', ''),
            'ip'         => $this->request->ip(),
            'user_agent' => (string) $this->request->header('user-agent', ''),
        ];
    }

    /**
     * 工单列表（分页 + 搜索 + 状态/分类/紧急性/日期筛选）
     */
    public function index()
    {
        $keyword   = trim((string) $this->request->get('keyword', ''));
        $status    = trim((string) $this->request->get('status', ''));
        $category  = trim((string) $this->request->get('category', ''));
        $urgency   = trim((string) $this->request->get('urgency', ''));
        $dateStart = trim((string) $this->request->get('date_start', ''));
        $dateEnd   = trim((string) $this->request->get('date_end', ''));
        $page      = (int) $this->request->get('page', 1);
        $limit     = 15;

        $filters = [
            'keyword'    => $keyword,
            'status'     => $status,
            'category'   => $category,
            'urgency'    => $urgency,
            'date_start' => $dateStart,
            'date_end'   => $dateEnd,
        ];

        $result    = FcTicketModel::getList($page, $limit, $filters);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $statusCnt = FcTicketModel::countByStatus();

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'keyword'       => $keyword,
            'status'        => $status,
            'category'      => $category,
            'urgency'       => $urgency,
            'date_start'    => $dateStart,
            'date_end'      => $dateEnd,
            'status_map'    => self::STATUS_MAP,
            'urgency_map'   => self::URGENCY_MAP,
            'category_map'  => self::CATEGORY_MAP,
            'status_cnt'    => $statusCnt,
            'pending_reply' => FcTicketModel::countPendingReply(),
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/ticket/index');
    }

    /**
     * 工单详情（帖子式，含回复列表）
     */
    public function detail()
    {
        $id = (int) $this->request->get('id', 0);
        if ($id <= 0) {
            return redirect((string) url('ticket/index'));
        }

        $ticket = FcTicketModel::findById($id);
        if (empty($ticket)) {
            return redirect((string) url('ticket/index'));
        }

        $replies     = FcTicketReplyModel::listByTicketId($id);
        $attachments = FcTicketAttachmentModel::listByTicketId($id);
        $links       = FcTicketLinkModel::listByTicketId($id);
        $contacts    = FcTicketContactModel::listByTicketId($id);

        View::assign([
            'ticket'       => $ticket,
            'replies'      => $replies,
            'attachments'  => $attachments,
            'links'        => $links,
            'contacts'     => $contacts,
            'status_map'   => self::STATUS_MAP,
            'urgency_map'  => self::URGENCY_MAP,
            'category_map' => self::CATEGORY_MAP,
            'admin_name'   => Session::get('admin_name', '管理员'),
            'admin_id'     => (int) Session::get('admin_id', 0),
            'pending_reply'=> FcTicketModel::countPendingReply(),
        ]);
        return View::fetch('/ticket/detail');
    }

    /**
     * 客服回复工单
     * - 插入回复记录（is_admin=1）
     * - 工单状态置为"客服答复"(2)
     * - 向用户推送站内信
     */
    public function reply()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        $id      = (int) $this->request->post('id', 0);
        $content = trim((string) $this->request->post('content', ''));

        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }
        if ($content === '') {
            return json(['code' => 1, 'msg' => '请输入回复内容']);
        }

        $ticket = FcTicketModel::findById($id);
        if (empty($ticket)) {
            return json(['code' => 1, 'msg' => '工单不存在']);
        }

        $status = (int) $ticket['status'];
        if ($status === 3) {
            return json(['code' => 1, 'msg' => '工单已关闭，不可回复']);
        }

        $adminId = (int) Session::get('admin_id', 0);

        $replyId = FcTicketReplyModel::adminReply($id, $adminId, $content);
        if ($replyId <= 0) {
            return json(['code' => 1, 'msg' => '回复失败']);
        }

        // 工单状态置为"客服答复"(2)
        FcTicketModel::updateStatus($id, 2);

        // 向用户推送站内信
        $title   = '工单 #' . $id . ' 有新的回复';
        $notice  = '客服已回复您的工单，请查看详情。';
        FcUserNotificationModel::push((int) $ticket['uid'], $title, $notice, 'info');

        AdminLogOperationModel::record(array_merge($this->getLogMeta(), [
            'type_code'   => 'ticket_reply',
            'action'      => '回复工单',
            'detail'      => '回复工单 #' . $id . ' 内容: ' . mb_substr($content, 0, 80),
            'target_type' => 'ticket',
            'target_id'   => $id,
        ]));

        return json(['code' => 0, 'msg' => '回复成功']);
    }

    /**
     * 关闭工单（结案）
     */
    public function close()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        $id = (int) $this->request->post('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $ticket = FcTicketModel::findById($id);
        if (empty($ticket)) {
            return json(['code' => 1, 'msg' => '工单不存在']);
        }

        if ((int) $ticket['status'] === 3) {
            return json(['code' => 1, 'msg' => '工单已关闭']);
        }

        if (FcTicketModel::updateStatus($id, 3)) {
            // 通知用户工单已关闭
            FcUserNotificationModel::push(
                (int) $ticket['uid'],
                '工单 #' . $id . ' 已关闭',
                '您的工单已结案关闭，如有需要请重新提交。',
                'system'
            );

            AdminLogOperationModel::record(array_merge($this->getLogMeta(), [
                'type_code'   => 'ticket_close',
                'action'      => '关闭工单',
                'detail'      => '结案关闭工单 #' . $id,
                'target_type' => 'ticket',
                'target_id'   => $id,
            ]));
            return json(['code' => 0, 'msg' => '工单已关闭']);
        }
        return json(['code' => 1, 'msg' => '操作失败']);
    }

    /**
     * 待回复工单数量（供 sidebar 红点轮询）
     */
    public function pending_count()
    {
        return json([
            'code'   => 0,
            'count'  => FcTicketModel::countPendingReply(),
        ]);
    }
}