<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\FcUserVerificationModel;
use app\model\AdminLogOperationModel;
use think\facade\Session;
use think\facade\View;

class UserVerification extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    private function getLogMeta(): array
    {
        return [
            'admin_id'   => (int) Session::get('admin_id', 0),
            'admin_name' => (string) Session::get('admin_name', ''),
            'ip'         => $this->request->ip(),
            'user_agent' => (string) $this->request->header('user-agent', ''),
        ];
    }

    public function index()
    {
        $page    = max(1, (int) $this->request->get('page', 1));
        $limit   = 15;
        $status  = trim((string) $this->request->get('status', ''));
        $type    = trim((string) $this->request->get('type', ''));
        $keyword = trim((string) $this->request->get('keyword', ''));

        $filters = [];
        if ($status !== '') $filters['status'] = $status;
        if ($type !== '') $filters['type'] = $type;
        if ($keyword !== '') $filters['keyword'] = $keyword;

        $result    = FcUserVerificationModel::getList($page, $limit, $filters);
        $totalPage = max(1, (int) ceil($result['total'] / $limit));

        $statusCnt = [];
        foreach (FcUserVerificationModel::STATUS_MAP as $k => $v) {
            $statusCnt[$k] = FcUserVerificationModel::countByStatus($k);
        }

        $pendingCount = FcUserVerificationModel::countByStatus(0);

        $params = [];
        if ($status !== '') $params[] = 'status=' . urlencode($status);
        if ($type !== '') $params[] = 'type=' . urlencode($type);
        if ($keyword !== '') $params[] = 'keyword=' . urlencode($keyword);
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
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'status_map'    => FcUserVerificationModel::STATUS_MAP,
            'type_map'      => FcUserVerificationModel::TYPE_MAP,
            'status_cnt'    => $statusCnt,
            'pending_audit' => $pendingCount,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/user_verification/index');
    }

    public function detail()
    {
        $id = (int) $this->request->get('id', 0);
        if ($id <= 0) {
            return redirect((string) url('user_verification/index'));
        }

        $record = FcUserVerificationModel::findById($id);
        if (empty($record)) {
            return redirect((string) url('user_verification/index'));
        }

        View::assign([
            'record'        => $record,
            'status_map'    => FcUserVerificationModel::STATUS_MAP,
            'type_map'      => FcUserVerificationModel::TYPE_MAP,
            'pending_audit' => FcUserVerificationModel::countByStatus(0),
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/user_verification/detail');
    }

    public function approve()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        $id = (int) $this->request->post('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $record = FcUserVerificationModel::findById($id);
        if (empty($record)) {
            return json(['code' => 1, 'msg' => '认证记录不存在']);
        }

        if ((int) $record['status'] !== 0) {
            return json(['code' => 1, 'msg' => '仅待审核状态可以审核通过']);
        }

        FcUserVerificationModel::updateStatus($id, 1);

        AdminLogOperationModel::record(array_merge($this->getLogMeta(), [
            'type_code'   => 'verification_approve',
            'action'      => '实名认证审核通过',
            'detail'      => '认证记录#' . $id . ' UID=' . $record['uid'] . ' 审核通过',
            'target_type' => 'user_verification',
            'target_id'   => $id,
        ]));

        return json(['code' => 0, 'msg' => '审核通过']);
    }

    public function reject()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        $id     = (int) $this->request->post('id', 0);
        $reason = trim((string) $this->request->post('reason', ''));

        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $record = FcUserVerificationModel::findById($id);
        if (empty($record)) {
            return json(['code' => 1, 'msg' => '认证记录不存在']);
        }

        if ((int) $record['status'] !== 0) {
            return json(['code' => 1, 'msg' => '仅待审核状态可以拒绝']);
        }

        FcUserVerificationModel::updateStatus($id, 2, $reason);

        AdminLogOperationModel::record(array_merge($this->getLogMeta(), [
            'type_code'   => 'verification_reject',
            'action'      => '实名认证审核拒绝',
            'detail'      => '认证记录#' . $id . ' UID=' . $record['uid'] . ' 审核拒绝' . ($reason !== '' ? ' 原因:' . $reason : ''),
            'target_type' => 'user_verification',
            'target_id'   => $id,
        ]));

        return json(['code' => 0, 'msg' => '已拒绝']);
    }

    public function edit()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        $id = (int) $this->request->post('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $record = FcUserVerificationModel::findById($id);
        if (empty($record)) {
            return json(['code' => 1, 'msg' => '认证记录不存在']);
        }

        if ((int) $record['status'] !== 0) {
            return json(['code' => 1, 'msg' => '仅待审核状态可以修改']);
        }

        $data = [];
        $fields = ['real_name', 'id_card', 'id_card_front', 'id_card_back', 'phone', 'company_name', 'business_license', 'legal_person', 'credit_code'];
        foreach ($fields as $field) {
            $val = $this->request->post($field, null);
            if ($val !== null) {
                $data[$field] = trim((string) $val);
            }
        }

        if (empty($data)) {
            return json(['code' => 1, 'msg' => '没有需要修改的数据']);
        }

        FcUserVerificationModel::updateData($id, $data);

        AdminLogOperationModel::record(array_merge($this->getLogMeta(), [
            'type_code'   => 'verification_edit',
            'action'      => '修改实名认证数据',
            'detail'      => '认证记录#' . $id . ' UID=' . $record['uid'] . ' 修改了认证数据',
            'target_type' => 'user_verification',
            'target_id'   => $id,
        ]));

        return json(['code' => 0, 'msg' => '修改成功']);
    }

    public function pending_count()
    {
        return json([
            'code'  => 0,
            'count' => FcUserVerificationModel::countByStatus(0),
        ]);
    }
}