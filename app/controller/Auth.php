<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\AdminUser;
use app\model\AdminLoginLog;
use app\model\SystemParam;
use think\facade\Session;
use think\facade\View;
use Tobycroft\AossSdk\Captcha;

/**
 * 后台认证控制器
 * 登录 / 登出 / 验证码
 */
class Auth extends BaseController
{
    private Captcha $captcha;

    public function __construct($app)
    {
        parent::__construct($app);
        $token = SystemParam::getVal('aoss');
        $this->captcha = new Captcha($token);
    }

    /**
     * 登录页
     */
    public function login()
    {
        if (Session::get('admin_id')) {
            return redirect((string) url('index/index'));
        }
        return View::fetch('login');
    }

    /**
     * 登录提交
     */
    public function doLogin()
    {
        $username = trim((string) $this->request->post('username', ''));
        $password = (string) $this->request->post('password', '');
        $code     = trim((string) $this->request->post('code', ''));
        $ip       = $this->request->ip();
        $ua       = (string) $this->request->header('user-agent', '');

        if ($username === '' || $password === '') {
            return json(['code' => 1, 'msg' => '请输入用户名和密码']);
        }

        $ident = (string) Session::get('admin_captcha_ident');
        Session::delete('admin_captcha_ident');

        $ret = $this->captcha->check_in_time($ident, $code, 300);
        if (!$ret->isSuccess()) {
            return json(['code' => 1, 'msg' => '验证码错误']);
        }

        $admin = AdminUser::findByUsername($username);
        if (empty($admin)) {
            AdminLoginLog::record(0, $username, $ip, $ua, false);
            return json(['code' => 1, 'msg' => '用户不存在或已禁用']);
        }

        if ((int) $admin->status !== 1) {
            AdminLoginLog::record((int) $admin->id, $username, $ip, $ua, false);
            return json(['code' => 1, 'msg' => '账号已禁用']);
        }

        if (md5($password) !== $admin->password) {
            AdminLoginLog::record((int) $admin->id, $username, $ip, $ua, false);
            return json(['code' => 1, 'msg' => '密码错误']);
        }

        AdminUser::updateLastLogin((int) $admin->id, $ip);
        AdminLoginLog::record((int) $admin->id, $username, $ip, $ua, true);

        Session::set('admin_id', (int) $admin->id);
        Session::set('admin_name', (string) $admin->nickname ?: $admin->username);
        Session::set('admin_username', (string) $admin->username);

        return json(['code' => 0, 'msg' => '登录成功', 'url' => (string) url('index/index')]);
    }

    /**
     * 登出
     */
    public function logout()
    {
        Session::clear();
        return redirect((string) url('auth/login'));
    }

    /**
     * GIF 动态验证码（aoss-sdk）
     * 输出 GIF 图片流，验证码通过 API 校验
     */
    public function captcha()
    {
        $ident = 'captcha_' . uniqid() . '_' . mt_rand();
        Session::set('admin_captcha_ident', $ident);

        $gif = $this->captcha->gif_number_fast($ident);

        if ($gif === false) {
            http_response_code(500);
            exit('验证码生成失败');
        }

        ob_end_clean();
        header('Content-Type: image/gif');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        echo $gif;
        exit;
    }
}