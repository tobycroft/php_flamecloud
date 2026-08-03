<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 前台用户模型
 * 表 fc_user（无前缀，显式指定表名）
 */
class FcUser extends Model
{
    protected $table = 'fc_user';

    protected $autoWriteTimestamp = 'timestamp';

    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $hidden = ['password'];
}