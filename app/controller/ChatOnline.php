<?php
declare (strict_types = 1);

namespace app\controller;

use think\facade\Db;

/**
 * 客服在线状态（公开接口，无需登录，含 CORS 跨域头）
 */
class ChatOnline extends \CommonController
{
    /**
     * 检查客服是否在线
     * 60 秒内有心跳记录即视为在线
     */
    public function online_status()
    {
        $threshold = time() - 60;

        $online = Db::table('fc_admin_online')
            ->where('last_heartbeat', '>=', $threshold)
            ->find();

        return json([
            'code'   => 0,
            'data'   => [
                'online' => !empty($online),
            ],
        ]);
    }
}