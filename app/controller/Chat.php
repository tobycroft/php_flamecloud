<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\ChatModel;
use app\model\AdminLogOperationModel;
use think\facade\Db;
use think\facade\Session;
use think\facade\View;

class Chat extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    public function index()
    {
        $unreadUids = ChatModel::getUnreadUidList();
        $unreadUidSet = [];
        foreach ($unreadUids as $row) {
            $unreadUidSet[] = $row['uid'];
        }

        $allUids = Db::table('fc_chat')
            ->distinct(true)
            ->field('uid')
            ->order('uid', 'asc')
            ->select()
            ->toArray();

        $users = [];
        if (!empty($allUids)) {
            $uidList = array_column($allUids, 'uid');
            $userList = Db::table('fc_user')
                ->whereIn('id', $uidList)
                ->field('id, username, phone')
                ->select()
                ->toArray();
            foreach ($userList as $u) {
                $users[$u['id']] = $u;
            }
        }

        $chatUsers = [];
        foreach ($allUids as $row) {
            $uid = $row['uid'];
            $lastMsg = Db::table('fc_chat')
                ->where('uid', $uid)
                ->order('id', 'desc')
                ->find();
            $userInfo = $users[$uid] ?? null;
            $chatUsers[] = [
                'uid'         => $uid,
                'username'    => $userInfo['username'] ?? ('用户' . $uid),
                'phone'       => $userInfo['phone'] ?? '',
                'last_msg'    => $lastMsg ? mb_substr((string)$lastMsg['content'], 0, 50) : '',
                'last_time'   => $lastMsg['created_at'] ?? '',
                'has_unread'  => in_array($uid, $unreadUidSet),
            ];
        }

        usort($chatUsers, function ($a, $b) {
            return strcmp($b['last_time'] ?? '', $a['last_time'] ?? '');
        });

        View::assign('chatUsers', $chatUsers);
        return View::fetch();
    }

    public function detail()
    {
        $uid = (int) input('get.uid');
        if ($uid <= 0) {
            return '缺少参数';
        }

        ChatModel::markRead($uid);

        $userInfo = Db::table('fc_user')
            ->where('id', $uid)
            ->field('id, username, phone')
            ->find();

        $messages = ChatModel::getListByUid($uid);

        View::assign('uid', $uid);
        View::assign('username', $userInfo['username'] ?? ('用户' . $uid));
        View::assign('messages', $messages);
        return View::fetch();
    }

    public function reply()
    {
        $uid = (int) input('post.uid');
        $content = trim((string) input('post.content'));
        if ($uid <= 0 || $content === '') {
            return json(['code' => 400, 'echo' => '缺少参数']);
        }

        $id = ChatModel::insertMessage($uid, $content, 1);
        if ($id <= 0) {
            return json(['code' => 500, 'echo' => '发送失败']);
        }

        AdminLogOperationModel::log(
            Session::get('admin_id'),
            'chat_reply',
            '回复聊天 uid=' . $uid . ' id=' . $id
        );

        return json(['code' => 0, 'data' => ['id' => $id], 'echo' => '发送成功']);
    }

    public function pending_count()
    {
        $count = ChatModel::getUnreadCount();
        return json(['code' => 0, 'count' => $count]);
    }
}