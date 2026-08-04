<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 余额流水模型
 * 表 fc_balance_record（与 Go 后端共用）
 *
 * type: 1=充值, 2=消费, 3=退款, 4=系统调整
 */
class FcBalanceRecordModel extends Model
{
    protected $table = 'fc_balance_record';

    protected $autoWriteTimestamp = false;

    public static function insertRecord(array $data): bool
    {
        $record = new self();
        $record->save($data);
        return (bool) $record->id;
    }
}