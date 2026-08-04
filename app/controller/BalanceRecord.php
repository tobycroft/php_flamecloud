<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\FcBalanceRecordModel;
use think\facade\Session;
use think\facade\View;

/**
 * 交易流水控制器
 */
class BalanceRecord extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    public function index()
    {
        $type      = trim((string) $this->request->get('type', ''));
        $uid       = trim((string) $this->request->get('uid', ''));
        $keyword   = trim((string) $this->request->get('keyword', ''));
        $dateStart = trim((string) $this->request->get('date_start', ''));
        $dateEnd   = trim((string) $this->request->get('date_end', ''));
        $page      = (int) $this->request->get('page', 1);
        $limit     = 15;

        $filters = [
            'type'       => $type,
            'uid'        => $uid,
            'keyword'    => $keyword,
            'date_start' => $dateStart,
            'date_end'   => $dateEnd,
        ];

        $result    = FcBalanceRecordModel::getList($page, $limit, $filters);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;

        $params = [];
        if ($type !== '') $params[] = 'type=' . urlencode($type);
        if ($uid !== '') $params[] = 'uid=' . urlencode($uid);
        if ($keyword !== '') $params[] = 'keyword=' . urlencode($keyword);
        if ($dateStart !== '') $params[] = 'date_start=' . urlencode($dateStart);
        if ($dateEnd !== '') $params[] = 'date_end=' . urlencode($dateEnd);
        $pQuery = '?' . implode('&', $params) . (count($params) > 0 ? '&' : '');
        $pStart = max(1, $page - 2);
        $pEnd   = min($totalPage, $page + 2);

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'type'          => $type,
            'uid'           => $uid,
            'keyword'       => $keyword,
            'date_start'    => $dateStart,
            'date_end'      => $dateEnd,
            'type_map'      => FcBalanceRecordModel::TYPE_MAP,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/balance_record/index');
    }
}