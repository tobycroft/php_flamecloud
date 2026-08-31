<?php
declare (strict_types = 1);

namespace app\controller;

use app\AdminBaseController;
use app\model\UserBalanceModel;
use think\facade\Session;
use think\facade\View;

/**
 * 用户余额控制器
 */
class UserBalance extends AdminBaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    public function index()
    {
        $keyword = trim((string) $this->request->get('keyword', ''));
        $page    = (int) $this->request->get('page', 1);
        $limit   = 15;

        $result    = FcUserBalanceModel::getList($page, $limit, $keyword);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = $keyword !== '' ? '?keyword=' . urlencode($keyword) . '&' : '?';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'keyword'       => $keyword,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/user_balance/index');
    }
}