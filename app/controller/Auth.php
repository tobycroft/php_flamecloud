<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\AdminUser;
use app\model\AdminLoginLog;
use think\App;
use think\facade\Session;
use think\facade\View;

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

        $captchaCode = (string) Session::get('admin_captcha_code');
        Session::delete('admin_captcha_code');

        if (empty($captchaCode) || strtolower($code) !== strtolower($captchaCode)) {
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
     * 普通静态验证码
     * 输出 PNG 图片流，验证码存 Session 校验
     */
    public function captcha()
    {
        $width = 120;
        $height = 40;
        $length = 4;
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        Session::set('admin_captcha_code', $code);

        $image = imagecreatetruecolor($width, $height);
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $bgColor);

        for ($i = 0; $i < 5; $i++) {
            $lineColor = imagecolorallocate($image, mt_rand(100, 200), mt_rand(100, 200), mt_rand(100, 200));
            imageline($image, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $lineColor);
        }
        for ($i = 0; $i < 50; $i++) {
            $pixelColor = imagecolorallocate($image, mt_rand(150, 255), mt_rand(150, 255), mt_rand(150, 255));
            imagesetpixel($image, mt_rand(0, $width), mt_rand(0, $height), $pixelColor);
        }

        $font = 5;
        $fontWidth = imagefontwidth($font);
        $fontHeight = imagefontheight($font);
        $x = ($width - $fontWidth * $length) / 2;
        $y = ($height - $fontHeight) / 2;
        for ($i = 0; $i < $length; $i++) {
            $charColor = imagecolorallocate($image, mt_rand(0, 100), mt_rand(20, 120), mt_rand(40, 140));
            imagestring($image, $font, (int)$x + $i * $fontWidth + mt_rand(-2, 2), (int)$y + mt_rand(-2, 2), $code[$i], $charColor);
        }

        ob_end_clean();
        header('Content-Type: image/png');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        imagepng($image);
        exit;
    }
}