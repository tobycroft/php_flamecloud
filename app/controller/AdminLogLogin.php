<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\AdminLogLoginModel;
use think\facade\Session;
use think\facade\View;

/**
 * 管理员登录日志控制器
 */
class AdminLogLogin extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    public function index()
    {
        $keyword = trim((string) $this->request->get('keyword', ''));
        $status  = (string) $this->request->get('status', '');
        $page    = (int) $this->request->get('page', 1);
        $limit   = 15;

        $result    = AdminLogLoginModel::getList($page, $limit, $keyword, $status);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;

        $params = [];
        if ($status !== '') $params[] = 'status=' . urlencode($status);
        if ($keyword !== '') $params[] = 'keyword=' . urlencode($keyword);
        $pQuery = !empty($params) ? '?' . implode('&', $params) . '&' : '?';
        $pStart = max(1, $page - 2);
        $pEnd   = min($totalPage, $page + 2);

        View::assign([
            'list'            => $result['list'],
            'total'           => $result['total'],
            'page'            => $page,
            'totalPage'       => $totalPage,
            'keyword'         => $keyword,
            'status'          => $status,
            'p_query'         => $pQuery,
            'p_start'         => $pStart,
            'p_end'           => $pEnd,
            'admin_name'      => Session::get('admin_name', '管理员'),
            'admin_username'  => Session::get('admin_username', ''),
            'admin_id'        => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/admin_log_login/index');
    }
}