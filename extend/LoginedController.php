<?php
declare (strict_types = 1);

use think\App;
use think\facade\Session;
use think\Response;

class LoginedController extends CommonController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->checkLogin();
    }

    protected function checkLogin(): void
    {
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            $this->unauthorized();
        }
    }

    protected function unauthorized(): void
    {
        if ($this->request->isAjax()) {
            json(['code' => 401, 'msg' => '未登录或登录已过期'])->send();
        } else {
            redirect((string) url('auth/login'))->send();
        }
        exit;
    }

    protected function getAdminId(): int
    {
        return (int) Session::get('admin_id', 0);
    }

    protected function getAdminName(): string
    {
        return (string) Session::get('admin_name', '');
    }

    protected function getAdminUsername(): string
    {
        return (string) Session::get('admin_username', '');
    }
}