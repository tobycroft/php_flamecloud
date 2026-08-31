<?php
declare (strict_types = 1);

namespace app\controller;

use app\AdminBaseController;
use app\model\FcEcsInstanceModel;
use think\facade\Session;
use think\facade\View;

/**
 * ECS实例管理控制器
 */
class EcsInstance extends AdminBaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 实例列表
     */
    public function index()
    {
        $keyword = trim((string) $this->request->get('keyword', ''));
        $status  = trim((string) $this->request->get('status', ''));
        $page    = max(1, (int) $this->request->get('page', 1));
        $limit   = 15;

        $filters = [];
        if ($keyword !== '') $filters['keyword'] = $keyword;
        if ($status !== '') $filters['status'] = $status;

        $result = FcEcsInstanceModel::getList($page, $limit, $filters);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = $keyword !== '' ? '?keyword=' . urlencode($keyword) . '&' : '?';
        if ($status !== '') $pQuery .= 'status=' . urlencode($status) . '&';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        // 各状态计数
        $statusCnt = [
            'running' => FcEcsInstanceModel::countByStatus('running'),
            'stopped' => FcEcsInstanceModel::countByStatus('stopped'),
        ];

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'keyword'       => $keyword,
            'status'        => $status,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'status_cnt'    => $statusCnt,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/ecs_instance/index');
    }
}