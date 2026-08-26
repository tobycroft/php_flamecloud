<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\AdminUserModel;
use app\model\AdminUserSettingModel;
use app\model\AdminLogLoginModel;
use app\model\SystemParamModel;
use think\App;
use think\facade\Session;
use think\facade\View;
use Tobycroft\AossSdk\Captcha;

/**
 * 后台认证控制器
 * 登录 / 登出 / 验证码
 */
class Auth extends BaseController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
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

        $captchaIdent = (string) Session::get('admin_captcha_ident');
        Session::delete('admin_captcha_ident');

        if (empty($captchaIdent)) {
            return json(['code' => 1, 'msg' => '验证码已过期，请刷新']);
        }

        $token = SystemParamModel::getVal('captcha_token');
        if (empty($token)) {
            return json(['code' => 1, 'msg' => '系统配置错误']);
        }

        $captcha = new Captcha($token);
        $ret = $captcha->check($captchaIdent, $code);

        if (!$ret->isSuccess()) {
            return json(['code' => 1, 'msg' => '验证码错误']);
        }

        $admin = AdminUserModel::findByUsername($username);
        if (empty($admin)) {
            AdminLogLoginModel::record(0, $username, $ip, $ua, false);
            return json(['code' => 1, 'msg' => '用户不存在或已禁用']);
        }

        if ((int) $admin->status !== 1) {
            AdminLogLoginModel::record((int) $admin->id, $username, $ip, $ua, false);
            return json(['code' => 1, 'msg' => '账号已禁用']);
        }

        if (md5($password) !== $admin->password) {
            AdminLogLoginModel::record((int) $admin->id, $username, $ip, $ua, false);
            return json(['code' => 1, 'msg' => '密码错误']);
        }

        AdminUserModel::updateLastLogin((int) $admin->id, $ip);
        AdminLogLoginModel::record((int) $admin->id, $username, $ip, $ua, true);

        Session::set('admin_id', (int) $admin->id);
        Session::set('admin_name', (string) $admin->nickname ?: $admin->username);
        Session::set('admin_username', (string) $admin->username);
        Session::set('last_activity', time());

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
     * 心跳保活
     * 前端定时请求，更新 last_activity 并返回空闲超时配置
     */
    public function heartbeat()
    {
        $adminId = Session::get('admin_id');
        if (empty($adminId)) {
            return json(['code' => 401, 'msg' => '未登录']);
        }

        Session::set('last_activity', time());

        $adminName = (string) Session::get('admin_name', '管理员');
        $now = time();

        // 更新客服在线时间戳（供前端判断客服是否在线）
        SystemParamModel::setValByKey('kf_last_ping', (string) $now);

        $idleTimeout = AdminUserSettingModel::getIdleTimeout((int) $adminId);

        return json([
            'code' => 0,
            'data' => [
                'idle_timeout' => $idleTimeout,
                'server_time'  => $now,
            ],
        ]);
    }

    /**
     * AOSS 动态 GIF 数字验证码
     * 输出 GIF 图片流，验证码标识存 Session 校验
     */
    public function captcha()
    {
        $token = SystemParamModel::getVal('captcha_token');
        if (empty($token)) {
            exit('Captcha token not configured');
        }

        $captcha = new Captcha($token);
        $ident = md5(uniqid('captcha_', true));

        $gif = $captcha->gif_number($ident);
        if ($gif === false) {
            exit('Failed to generate captcha');
        }

        Session::set('admin_captcha_ident', $ident);
        Session::save();

        ob_end_clean();
        header('Content-Type: image/gif');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        echo $gif;
        exit;
    }
}