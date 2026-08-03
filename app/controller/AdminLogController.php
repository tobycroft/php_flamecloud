<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\AdminLog;
use app\model\AdminLogType;
use think\facade\Session;
use think\facade\View;

/**
 * 管理员操作日志控制器
 */
class AdminLogController extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    public function index()
    {
        $typeCode = trim((string) $this->request->get('type_code', ''));
        $keyword  = trim((string) $this->request->get('keyword', ''));
        $page     = (int) $this->request->get('page', 1);
        $limit    = 15;

        $result    = AdminLog::getList($page, $limit, $typeCode, $keyword);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $types     = AdminLogType::getAll();

        View::assign([
            'list'            => $result['list'],
            'total'           => $result['total'],
            'page'            => $page,
            'totalPage'       => $totalPage,
            'type_code'       => $typeCode,
            'keyword'         => $keyword,
            'types'           => $types,
            'admin_name'      => Session::get('admin_name', '管理员'),
            'admin_username'  => Session::get('admin_username', ''),
            'admin_id'        => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/admin_log/index');
    }
}