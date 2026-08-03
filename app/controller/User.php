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

        $query = FcUser::order('id', 'desc');
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('username', 'like', '%' . $keyword . '%')
                  ->whereOr('phone', 'like', '%' . $keyword . '%')
                  ->whereOr('email', 'like', '%' . $keyword . '%');
            });
        }

        $list = $query->paginate([
            'list_rows' => $limit,
            'page'      => $page,
            'query'     => ['keyword' => $keyword],
        ]);

        View::assign([
            'list'          => $list,
            'total'         => $list->total(),
            'page'          => $list->currentPage(),
            'totalPage'     => $list->lastPage(),
            'keyword'       => $keyword,
            'pages'         => $list->render(),
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/user/index');
    }
}