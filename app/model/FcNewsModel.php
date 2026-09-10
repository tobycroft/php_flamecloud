<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 新闻公告模型
 * 表 fc_news
 */
class FcNewsModel extends Model
{
    protected $table = 'fc_news';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /**
     * 分页列表（后台管理用，含隐藏项）
     */
    public static function getList(int $page = 1, int $limit = 15, array $filters = []): array
    {
        $query = self::order('publish_time', 'desc')->order('id', 'desc');

        $keyword = $filters['keyword'] ?? '';
        if ($keyword !== '') {
            $query->where('title', 'like', '%' . $keyword . '%');
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 按 id 获取单条
     */
    public static function getById(int $id): ?array
    {
        $row = self::find($id);
        return $row ? $row->toArray() : null;
    }
}
