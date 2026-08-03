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
});