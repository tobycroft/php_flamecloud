<?php
use think\facade\Route;

Route::get('captcha', 'auth/captcha');
Route::get('login', 'auth/login');
Route::post('login', 'auth/doLogin');
Route::get('logout', 'auth/logout');

Route::get('/', 'index/index');

Route::group(function () {
    Route::get('admin', 'admin/index');
    Route::get('admin/index', 'admin/index');
    Route::post('admin/add', 'admin/add');
    Route::get('admin/edit', 'admin/edit');
    Route::post('admin/edit', 'admin/edit');
    Route::post('admin/status', 'admin/status');
    Route::post('admin/delete', 'admin/delete');

    Route::get('user', 'user/index');
    Route::get('user/index', 'user/index');
    Route::post('user/status', 'user/status');

    Route::get('admin_log', 'adminLog/index');
    Route::get('admin_log/index', 'adminLog/index');

    Route::get('admin_log_login', 'adminLogLogin/index');
    Route::get('admin_log_login/index', 'adminLogLogin/index');

    Route::get('system_param', 'systemParam/index');
    Route::get('system_param/index', 'systemParam/index');
    Route::post('system_param/save', 'systemParam/save');

    // 工单管理
    Route::get('ticket', 'ticket/index');
    Route::get('ticket/index', 'ticket/index');
    Route::get('ticket/detail', 'ticket/detail');
    Route::post('ticket/reply', 'ticket/reply');
    Route::post('ticket/close', 'ticket/close');
    Route::post('ticket/reopen', 'ticket/reopen');
    Route::get('ticket/pending_count', 'ticket/pending_count');

    // 个人设置
    Route::get('admin_setting', 'adminSetting/index');
    Route::get('admin_setting/index', 'adminSetting/index');
    Route::post('admin_setting/save', 'adminSetting/save');

    // 文件上传
    Route::get('upload/token', 'upload/token');
    Route::post('upload/resolve', 'upload/resolve');

    // 充值审核
    Route::get('recharge_audit', 'recharge_audit/index');
    Route::get('recharge_audit/index', 'recharge_audit/index');
    Route::get('recharge_audit/detail', 'recharge_audit/detail');
    Route::post('recharge_audit/approve', 'recharge_audit/approve');
    Route::post('recharge_audit/reject', 'recharge_audit/reject');
    Route::get('recharge_audit/pending_count', 'recharge_audit/pending_count');

    // 用户余额
    Route::get('user_balance', 'user_balance/index');
    Route::get('user_balance/index', 'user_balance/index');

    // 心跳保活
    Route::get('heartbeat', 'auth/heartbeat');
});