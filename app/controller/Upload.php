<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\SystemParamModel;
use Tobycroft\AossSdk\File;

/**
 * 文件上传控制器
 * 提供 AOSS 上传 Token 和 Hash 解析接口
 */
class Upload extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取上传地址（Hash 模式）
     */
    public function token()
    {
        $token = SystemParamModel::getVal('aoss');
        if (empty($token)) {
            return json(['code' => 1, 'msg' => '上传服务未配置']);
        }

        $file = new File($token);
        $ret = $file->getUploadHashUrl();

        if (!$ret->isSuccess()) {
            return json(['code' => 1, 'msg' => '获取上传地址失败']);
        }

        return json([
            'code' => 0,
            'msg'  => 'ok',
            'data' => [
                'upload_url' => $ret->upload_url,
            ],
        ]);
    }

    /**
     * 通过 Hash 换取文件实际地址
     */
    public function resolve()
    {
        $hash = trim((string) $this->request->post('hash', ''));
        if ($hash === '') {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $token = SystemParamModel::getVal('aoss');
        if (empty($token)) {
            return json(['code' => 1, 'msg' => '上传服务未配置']);
        }

        $file = new File($token);
        $ret = $file->getUploadedFileUrlByHash($hash);

        if (!$ret->isSuccess()) {
            return json(['code' => 1, 'msg' => '获取文件地址失败']);
        }

        return json([
            'code' => 0,
            'msg'  => 'ok',
            'data' => [
                'hash' => $hash,
                'url'  => $ret->url,
            ],
        ]);
    }
}