<?php
declare (strict_types = 1);

namespace app\controller;

use app\AdminBaseController;
use app\model\FcNewsModel;
use app\model\AdminLogOperationModel;
use think\facade\Session;
use think\facade\View;

/**
 * 新闻公告管理控制器
 * 后台配置新闻公告内容，公开读接口由 go_flamecloud 提供（/v1/news/list、/v1/news/detail）。
 */
class News extends AdminBaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 列表首页
     */
    public function index()
    {
        $keyword = trim((string) $this->request->get('keyword', ''));
        $page    = max(1, (int) $this->request->get('page', 1));
        $limit   = 15;

        $filters = [];
        if ($keyword !== '') {
            $filters['keyword'] = $keyword;
        }

        $result    = FcNewsModel::getList($page, $limit, $filters);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = $keyword !== '' ? '?keyword=' . urlencode($keyword) . '&' : '?';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'keyword'       => $keyword,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/news/index');
    }

    /**
     * 新增
     */
    public function add()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }

        $title = trim((string) $this->request->post('title', ''));
        if ($title === '') {
            return json(['code' => 1, 'msg' => '标题不能为空']);
        }

        $data = $this->collectForm();
        $row  = FcNewsModel::create($data);

        $this->logAction('news', '新增新闻公告', '新增 ID=' . $row->id . ' 标题=' . $title, 'news', (int) $row->id);

        return json(['code' => 0, 'msg' => '添加成功']);
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $id = (int) $this->request->get('id', 0);
        if ($id <= 0) {
            $id = (int) $this->request->post('id', 0);
        }

        $row = FcNewsModel::getById($id);
        if (!$row) {
            return json(['code' => 1, 'msg' => '数据不存在']);
        }

        if (!$this->request->isPost()) {
            return json(['code' => 0, 'data' => $row]);
        }

        $title = trim((string) $this->request->post('title', ''));
        if ($title === '') {
            return json(['code' => 1, 'msg' => '标题不能为空']);
        }

        $data = $this->collectForm();
        FcNewsModel::where('id', $id)->update($data);

        $this->logAction('news', '编辑新闻公告', '编辑 ID=' . $id . ' 标题=' . $title, 'news', $id);

        return json(['code' => 0, 'msg' => '修改成功']);
    }

    /**
     * 切换状态
     */
    public function status()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }

        $id     = (int) $this->request->post('id', 0);
        $status = (int) $this->request->post('status', 0);

        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        FcNewsModel::where('id', $id)->update(['status' => $status]);

        $this->logAction('news', '切换新闻公告状态', 'ID=' . $id . ' status=' . $status, 'news', $id);

        return json(['code' => 0, 'msg' => $status ? '已显示' : '已隐藏']);
    }

    /**
     * 删除
     */
    public function delete()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }

        $id = (int) $this->request->post('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        FcNewsModel::where('id', $id)->delete();

        $this->logAction('news', '删除新闻公告', '删除 ID=' . $id, 'news', $id);

        return json(['code' => 0, 'msg' => '删除成功']);
    }

    /**
     * 批量删除
     */
    public function batchDelete()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }

        $ids = array_filter(array_map('intval', (array) $this->request->post('ids/a', [])));
        if (empty($ids)) {
            return json(['code' => 1, 'msg' => '请选择要删除的公告']);
        }

        FcNewsModel::whereIn('id', $ids)->delete();

        $this->logAction('news', '批量删除新闻公告', '批量删除 ID=' . implode(',', $ids), 'news', 0);

        return json(['code' => 0, 'msg' => '删除成功']);
    }

    /**
     * 收集表单字段（新增/编辑共用）
     */
    private function collectForm(): array
    {
        $publishTime = trim((string) $this->request->post('publish_time', ''));
        if ($publishTime === '') {
            $publishTime = date('Y-m-d H:i:s');
        } else {
            // datetime-local 形如 2025-09-10T14:30，统一转成 Y-m-d H:i:s
            $publishTime = str_replace('T', ' ', $publishTime);
        }

        return [
            'title'       => trim((string) $this->request->post('title', '')),
            'summary'     => trim((string) $this->request->post('summary', '')),
            'cover_image' => trim((string) $this->request->post('cover_image', '')),
            'content'     => (string) $this->request->post('content', ''),
            'source'      => trim((string) $this->request->post('source', '')),
            'author'      => trim((string) $this->request->post('author', '')),
            'status'      => (int) $this->request->post('status', 1),
            'sort'        => (int) $this->request->post('sort', 0),
            'publish_time'=> $publishTime,
        ];
    }

    /**
     * 后台操作日志元数据
     */
    private function getLogMeta(): array
    {
        return [
            'admin_id'   => (int) Session::get('admin_id', 0),
            'admin_name' => (string) Session::get('admin_name', ''),
            'ip'         => $this->request->ip(),
            'user_agent' => (string) $this->request->header('user-agent', ''),
        ];
    }

    /**
     * 记录后台操作日志到 admin_log_operation（系统 ActionLog）
     */
    private function logAction(string $typeCode, string $action, string $detail, string $targetType = 'news', int $targetId = 0): void
    {
        AdminLogOperationModel::record(array_merge($this->getLogMeta(), [
            'type_code'   => $typeCode,
            'action'      => $action,
            'detail'      => $detail,
            'target_type' => $targetType,
            'target_id'   => $targetId,
        ]));
    }
}
