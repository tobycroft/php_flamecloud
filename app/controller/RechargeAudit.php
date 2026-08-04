<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\FcRechargeOrderModel;
use app\model\FcUserBalanceModel;
use app\model\FcBalanceRecordModel;
use app\model\AdminLogOperationModel;
use think\facade\Db;
use think\facade\Session;
use think\facade\View;

/**
 * 充值审核控制器
 * - 审核列表（status=3 审核中）
 * - 审核详情（查看订单信息、凭证图片）
 * - 通过 / 拒绝
 */
class RechargeAudit extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 订单列表
     */
    public function index()
    {
        $page  = max(1, (int) $this->request->get('page', 1));
        $limit = 15;
        $status  = trim((string) $this->request->get('status', ''));
        $type    = trim((string) $this->request->get('type', ''));
        $keyword = trim((string) $this->request->get('keyword', ''));
        $dateStart = trim((string) $this->request->get('date_start', ''));
        $dateEnd   = trim((string) $this->request->get('date_end', ''));

        $filters = [];
        if ($status !== '') $filters['status'] = $status;
        if ($type !== '')   $filters['type']   = $type;
        if ($keyword !== '') $filters['keyword'] = $keyword;
        if ($dateStart !== '') $filters['date_start'] = $dateStart;
        if ($dateEnd !== '') $filters['date_end'] = $dateEnd;

        $result = FcRechargeOrderModel::getList($page, $limit, $filters);
        $totalPage = max(1, (int) ceil($result['total'] / $limit));

        // 各状态计数
        $statusCnt = [];
        foreach (FcRechargeOrderModel::STATUS_MAP as $k => $v) {
            $statusCnt[$k] = FcRechargeOrderModel::countByStatus($k);
        }

        $pendingAudit = FcRechargeOrderModel::countByStatus(3);

        // 分页参数
        $params = [];
        if ($status !== '') $params[] = 'status=' . urlencode($status);
        if ($type !== '') $params[] = 'type=' . urlencode($type);
        if ($keyword !== '') $params[] = 'keyword=' . urlencode($keyword);
        if ($dateStart !== '') $params[] = 'date_start=' . urlencode($dateStart);
        if ($dateEnd !== '') $params[] = 'date_end=' . urlencode($dateEnd);
        $pQuery = !empty($params) ? '?' . implode('&', $params) . '&' : '?';
        $pStart = max(1, $page - 2);
        $pEnd   = min($totalPage, $page + 2);

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'keyword'       => $keyword,
            'status'        => $status,
            'type'          => $type,
            'date_start'    => $dateStart,
            'date_end'      => $dateEnd,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'status_map'    => FcRechargeOrderModel::STATUS_MAP,
            'type_map'      => FcRechargeOrderModel::TYPE_MAP,
            'pay_method_map'=> FcRechargeOrderModel::PAY_METHOD_MAP,
            'status_cnt'    => $statusCnt,
            'pending_audit' => $pendingAudit,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/recharge_audit/index');
    }

    /**
     * 订单详情
     */
    public function detail()
    {
        $id = (int) $this->request->get('id', 0);
        if ($id <= 0) {
            return redirect((string) url('recharge_audit/index'));
        }

        $order = FcRechargeOrderModel::findById($id);
        if (empty($order)) {
            return redirect((string) url('recharge_audit/index'));
        }

        // 解析凭证 JSON
        $vouchers = [];
        $voucherRaw = $order['voucher'] ?? '';
        if ($voucherRaw !== '' && $voucherRaw !== null) {
            $decoded = json_decode($voucherRaw, true);
            if (is_array($decoded)) {
                $vouchers = $decoded;
            }
        }

        View::assign([
            'order'         => $order,
            'vouchers'      => $vouchers,
            'status_map'    => FcRechargeOrderModel::STATUS_MAP,
            'type_map'      => FcRechargeOrderModel::TYPE_MAP,
            'pay_method_map'=> FcRechargeOrderModel::PAY_METHOD_MAP,
            'pending_audit' => FcRechargeOrderModel::countByStatus(3),
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/recharge_audit/detail');
    }

    /**
     * 审核通过
     */
    public function approve()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        $id = (int) $this->request->post('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $order = FcRechargeOrderModel::findById($id);
        if (empty($order)) {
            return json(['code' => 1, 'msg' => '订单不存在']);
        }

        if ((int) $order['status'] !== 3) {
            return json(['code' => 1, 'msg' => '该订单不是审核中状态']);
        }

        $adminId = (int) Session::get('admin_id', 0);
        $uid     = (int) $order['uid'];
        $amount  = (string) $order['amount'];

        // 事务处理
        Db::startTrans();
        try {
            // 1. 更新订单状态为已完成
            FcRechargeOrderModel::updateStatus($id, 1);

            // 2. 增加用户余额
            $balanceBefore = '0.00';
            $balanceRow = FcUserBalanceModel::findByUid($uid);
            if ($balanceRow) {
                $balanceBefore = (string) $balanceRow['balance'];
            }
            $newBalance = FcUserBalanceModel::addBalance($uid, $amount);

            // 3. 写入余额流水
            FcBalanceRecordModel::insertRecord([
                'uid'            => $uid,
                'type'           => 1,
                'order_id'       => $id,
                'order_no'       => $order['order_no'],
                'balance_before' => $balanceBefore,
                'amount'         => $amount,
                'balance_after'  => $newBalance,
                'description'    => '线下充值审核通过',
                'remark'         => '管理员审核通过',
            ]);

            Db::commit();

            AdminLogOperationModel::record([
                'admin_id'    => $adminId,
                'admin_name'  => Session::get('admin_name', ''),
                'type_code'   => 'recharge_approve',
                'action'      => '充值审核通过',
                'detail'      => '订单#' . $order['order_no'] . ' 金额' . $amount . ' 审核通过',
                'target_type' => 'recharge_order',
                'target_id'   => $id,
                'ip'          => $this->request->ip(),
                'user_agent'  => (string) $this->request->header('user-agent', ''),
            ]);

            return json(['code' => 0, 'msg' => '审核通过，已充值到账']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => '操作失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 审核拒绝
     */
    public function reject()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        $id = (int) $this->request->post('id', 0);
        $reason = trim((string) $this->request->post('reason', ''));

        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $order = FcRechargeOrderModel::findById($id);
        if (empty($order)) {
            return json(['code' => 1, 'msg' => '订单不存在']);
        }

        if ((int) $order['status'] !== 3) {
            return json(['code' => 1, 'msg' => '该订单不是审核中状态']);
        }

        $adminId = (int) Session::get('admin_id', 0);

        FcRechargeOrderModel::updateStatus($id, 4);

        AdminLogOperationModel::record([
            'admin_id'    => $adminId,
            'admin_name'  => Session::get('admin_name', ''),
            'type_code'   => 'recharge_reject',
            'action'      => '充值审核拒绝',
            'detail'      => '订单#' . $order['order_no'] . ' 金额' . $order['amount'] . ' 审核拒绝' . ($reason !== '' ? ' 原因:' . $reason : ''),
            'target_type' => 'recharge_order',
            'target_id'   => $id,
            'ip'          => $this->request->ip(),
            'user_agent'  => (string) $this->request->header('user-agent', ''),
        ]);

        return json(['code' => 0, 'msg' => '已拒绝该充值申请']);
    }
}