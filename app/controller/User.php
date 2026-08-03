<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\FcUser;
use think\facade\Session;
use think\facade\View;

/**
 * 前台用户管理控制器
 */
class User extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    public function index()
    {
        $keyword = trim((string) $this->request->get('keyword', ''));
        $page    = (int) $this->request->get('page', 1);
        $limit   = 15;

        $result = FcUser::getList($page, $limit, $keyword);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'keyword'       => $keyword,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/user/index');
    }
}