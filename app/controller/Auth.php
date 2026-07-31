<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\AdminUser;
use app\model\AdminLoginLog;
use think\facade\Session;
use think\facade\View;

/**
 * 后台认证控制器
 * 登录 / 登出 / 验证码
 */
class Auth extends BaseController
{
    /**
     * 登录页
     */
    public function login()
    {
        // 已登录直接进首页
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

        // 验证码校验
        $sessCode = (string) Session::get('admin_captcha');
        Session::delete('admin_captcha');
        if (strtolower($code) !== strtolower($sessCode)) {
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

        // 登录成功
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
     * 图形验证码（GD 零依赖）
     * 输出 PNG 图片流，验证码内容存 session: admin_captcha
     */
    public function captcha()
    {
        $code   = '';
        $chars  = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        for ($i = 0; $i < 4; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        Session::set('admin_captcha', $code);

        $w = 120;
        $h = 40;
        $img = imagecreatetruecolor($w, $h);

        // 背景色
        $bg = imagecolorallocate($img, 245, 247, 250);
        imagefilledrectangle($img, 0, 0, $w, $h, $bg);

        // 干扰点
        for ($i = 0; $i < 80; $i++) {
            $c = imagecolorallocate($img, random_int(180, 230), random_int(180, 230), random_int(180, 230));
            imagesetpixel($img, random_int(0, $w - 1), random_int(0, $h - 1), $c);
        }
        // 干扰线
        for ($i = 0; $i < 3; $i++) {
            $c = imagecolorallocate($img, random_int(150, 200), random_int(150, 200), random_int(150, 200));
            imageline($img, random_int(0, $w), random_int(0, $h), random_int(0, $w), random_int(0, $h), $c);
        }

        // 文字
        $colors = [
            imagecolorallocate($img, 22, 119, 255),
            imagecolorallocate($img, 255, 107, 53),
            imagecolorallocate($img, 34, 197, 94),
            imagecolorallocate($img, 139, 92, 246),
        ];
        for ($i = 0; $i < 4; $i++) {
            imagestring($img, 5, 12 + $i * 26, 10 + random_int(-2, 4), $code[$i], $colors[$i % count($colors)]);
        }

        ob_end_clean();
        header('Content-Type: image/png');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        imagepng($img);
        imagedestroy($img);
        exit;
    }
}
