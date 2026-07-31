<?php
use think\facade\Route;

// 验证码（无需登录）
Route::get('captcha', 'auth/captcha');

// 登录 / 登出（无需登录）
Route::get('login', 'auth/login');
Route::post('login', 'auth/doLogin');
Route::get('logout', 'auth/logout');

// 后台首页（Index 控制器自带 AdminAuth 中间件保护）
Route::get('/', 'index/index');
