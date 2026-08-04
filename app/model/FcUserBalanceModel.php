<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 用户余额模型
 * 表 fc_user_balance（与 Go 后端共用）
 */
class FcUserBalanceModel extends Model
{
    protected $table = 'fc_user_balance';

    protected $autoWriteTimestamp = false;

    public static function findByUid(int $uid): ?array
    {
        $row = self::where('uid', $uid)->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 初始化余额记录
     */
    public static function initBalance(int $uid): void
    {
        $exists = self::where('uid', $uid)->find();
        if (!$exists) {
            self::create([
                'uid'     => $uid,
                'balance' => '0.00',
                'frozen'  => '0.00',
            ]);
        }
    }

    /**
     * 增加余额，返回变动后的余额
     */
    public static function addBalance(int $uid, string $amount): ?string
    {
        self::initBalance($uid);
        $row = self::where('uid', $uid)->find();
        if (!$row) {
            return null;
        }
        $newBalance = bcadd((string) $row->balance, $amount, 2);
        self::where('uid', $uid)->update(['balance' => $newBalance]);
        return $newBalance;
    }

    /**
     * 分页列表（支持 UID 搜索）
     */
    public static function getList(int $page = 1, int $limit = 15, string $keyword = ''): array
    {
        $query = self::order('uid', 'asc');

        if ($keyword !== '') {
            if (is_numeric($keyword)) {
                $query->where('uid', (int) $keyword);
            } else {
                $query->where('uid', 'like', '%' . $keyword . '%');
            }
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        return ['list' => $list, 'total' => $total];
    }
}