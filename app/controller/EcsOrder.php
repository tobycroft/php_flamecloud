<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\FcEcsOrderModel;
use think\facade\Session;
use think\facade\View;

/**
 * ECS订单管理控制器
 */
class EcsOrder extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 订单列表
     */
    public function index()
    {
        $keyword = trim((string) $this->request->get('keyword', ''));
        $type    = trim((string) $this->request->get('type', ''));
        $status  = trim((string) $this->request->get('status', ''));
        $page    = max(1, (int) $this->request->get('page', 1));
        $limit   = 15;

        $filters = [];
        if ($keyword !== '') $filters['keyword'] = $keyword;
        if ($type !== '') $filters['type'] = $type;
        if ($status !== '') $filters['status'] = $status;

        $result = FcEcsOrderModel::getList($page, $limit, $filters);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = $keyword !== '' ? '?keyword=' . urlencode($keyword) . '&' : '?';
        if ($type !== '') $pQuery .= 'type=' . urlencode($type) . '&';
        if ($status !== '') $pQuery .= 'status=' . urlencode($status) . '&';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'keyword'       => $keyword,
            'type'          => $type,
            'status'        => $status,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'type_map'      => FcEcsOrderModel::TYPE_MAP,
            'status_map'    => FcEcsOrderModel::STATUS_MAP,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/ecs_order/index');
    }
}