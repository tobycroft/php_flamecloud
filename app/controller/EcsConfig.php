<?php
declare (strict_types = 1);

namespace app\controller;

use app\AdminBaseController;
use app\model\EcsRegionModel;
use app\model\EcsZoneModel;
use app\model\EcsSpecModel;
use app\model\EcsImageModel;
use app\model\EcsDiskModel;
use app\model\EcsLineModel;
use app\model\EcsBandwidthModel;
use app\model\EcsVpcModel;
use think\facade\Session;
use think\facade\View;

/**
 * ECS配置管理控制器
 * 管理地域、可用区、规格、镜像、磁盘、线路、带宽、VPC等配置
 */
class EcsConfig extends AdminBaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    // ==================== 地域管理 ====================

    /**
     * 地域列表首页
     */
    public function index()
    {
        $keyword = trim((string) $this->request->get('keyword', ''));
        $page    = max(1, (int) $this->request->get('page', 1));
        $limit   = 15;

        $filters = [];
        if ($keyword !== '') $filters['keyword'] = $keyword;

        $result    = EcsRegionModel::getList($page, $limit, $filters);
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
        return View::fetch('/ecs_config/index');
    }

    /**
     * 新增地域
     */
    public function add()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }

        $name  = trim((string) $this->request->post('name', ''));
        $value = trim((string) $this->request->post('value', ''));
        $sort  = (int) $this->request->post('sort', 0);

        if ($name === '' || $value === '') {
            return json(['code' => 1, 'msg' => '名称和标识不能为空']);
        }

        // 检查标识是否重复
        $exists = EcsRegionModel::where('value', $value)->find();
        if ($exists) {
            return json(['code' => 1, 'msg' => '地域标识已存在']);
        }

        $region = new EcsRegionModel();
        $region->save([
            'name'  => $name,
            'value' => $value,
            'sort'  => $sort,
        ]);

        return json(['code' => 0, 'msg' => '添加成功']);
    }

    /**
     * 编辑地域
     */
    public function edit()
    {
        $id = (int) $this->request->get('id', 0);
        if ($id <= 0) {
            // POST 提交
            $id = (int) $this->request->post('id', 0);
        }

        $row = EcsRegionModel::getById($id);
        if (!$row) {
            if ($this->request->isPost()) {
                return json(['code' => 1, 'msg' => '数据不存在']);
            }
            return json(['code' => 1, 'msg' => '数据不存在']);
        }

        if (!$this->request->isPost()) {
            return json(['code' => 0, 'data' => $row]);
        }

        $name  = trim((string) $this->request->post('name', ''));
        $value = trim((string) $this->request->post('value', ''));
        $sort  = (int) $this->request->post('sort', 0);

        if ($name === '' || $value === '') {
            return json(['code' => 1, 'msg' => '名称和标识不能为空']);
        }

        // 检查标识是否与其他记录重复
        $exists = EcsRegionModel::where('value', $value)->where('id', '<>', $id)->find();
        if ($exists) {
            return json(['code' => 1, 'msg' => '地域标识已存在']);
        }

        EcsRegionModel::where('id', $id)->update([
            'name'  => $name,
            'value' => $value,
            'sort'  => $sort,
        ]);

        return json(['code' => 0, 'msg' => '修改成功']);
    }

    /**
     * 切换地域状态
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

        EcsRegionModel::where('id', $id)->update(['status' => $status]);

        return json(['code' => 0, 'msg' => $status ? '已启用' : '已禁用']);
    }

    /**
     * 删除地域
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

        // 检查是否有子配置
        $zoneCount = EcsZoneModel::where('region_id', $id)->count();
        if ($zoneCount > 0) {
            return json(['code' => 1, 'msg' => '该地域下存在可用区，请先删除可用区']);
        }

        EcsRegionModel::where('id', $id)->delete();

        return json(['code' => 0, 'msg' => '删除成功']);
    }

    // ==================== 可用区管理 ====================

    /**
     * 可用区列表
     */
    public function zone()
    {
        $regionId = (int) $this->request->get('region_id', 0);
        $keyword  = trim((string) $this->request->get('keyword', ''));
        $page     = max(1, (int) $this->request->get('page', 1));
        $limit    = 15;

        $region = EcsRegionModel::getById($regionId);
        if (!$region) {
            return redirect('/ecs_config/index');
        }

        $filters = ['region_id' => $regionId];
        if ($keyword !== '') $filters['keyword'] = $keyword;

        $result    = EcsZoneModel::getList($page, $limit, $filters);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = '?region_id=' . $regionId . '&';
        if ($keyword !== '') $pQuery .= 'keyword=' . urlencode($keyword) . '&';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'keyword'       => $keyword,
            'region'        => $region,
            'region_id'     => $regionId,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/ecs_config/zone');
    }

    // ==================== 规格管理 ====================

    /**
     * 规格列表
     */
    public function spec()
    {
        $regionId = (int) $this->request->get('region_id', 0);
        $zoneId   = (int) $this->request->get('zone_id', 0);
        $keyword  = trim((string) $this->request->get('keyword', ''));
        $page     = max(1, (int) $this->request->get('page', 1));
        $limit    = 15;

        $region = EcsRegionModel::getById($regionId);
        if (!$region) {
            return redirect('/ecs_config/index');
        }

        $filters = ['region_id' => $regionId];
        if ($zoneId > 0) $filters['zone_id'] = $zoneId;
        if ($keyword !== '') $filters['keyword'] = $keyword;

        $result    = EcsSpecModel::getList($page, $limit, $filters);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = '?region_id=' . $regionId . '&';
        if ($zoneId > 0) $pQuery .= 'zone_id=' . $zoneId . '&';
        if ($keyword !== '') $pQuery .= 'keyword=' . urlencode($keyword) . '&';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        $zones = EcsZoneModel::getActiveByRegionId($regionId);

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'keyword'       => $keyword,
            'region'        => $region,
            'region_id'     => $regionId,
            'zone_id'       => $zoneId,
            'zones'         => $zones,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/ecs_config/spec');
    }

    // ==================== 镜像管理 ====================

    /**
     * 镜像列表
     */
    public function image()
    {
        $regionId  = (int) $this->request->get('region_id', 0);
        $zoneId    = (int) $this->request->get('zone_id', 0);
        $imageType = trim((string) $this->request->get('image_type', ''));
        $keyword   = trim((string) $this->request->get('keyword', ''));
        $page      = max(1, (int) $this->request->get('page', 1));
        $limit     = 15;

        $region = EcsRegionModel::getById($regionId);
        if (!$region) {
            return redirect('/ecs_config/index');
        }

        $filters = ['region_id' => $regionId];
        if ($zoneId > 0) $filters['zone_id'] = $zoneId;
        if ($imageType !== '') $filters['image_type'] = $imageType;
        if ($keyword !== '') $filters['keyword'] = $keyword;

        $result    = EcsImageModel::getList($page, $limit, $filters);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = '?region_id=' . $regionId . '&';
        if ($zoneId > 0) $pQuery .= 'zone_id=' . $zoneId . '&';
        if ($imageType !== '') $pQuery .= 'image_type=' . urlencode($imageType) . '&';
        if ($keyword !== '') $pQuery .= 'keyword=' . urlencode($keyword) . '&';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        $zones = EcsZoneModel::getActiveByRegionId($regionId);

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'keyword'       => $keyword,
            'region'        => $region,
            'region_id'     => $regionId,
            'zone_id'       => $zoneId,
            'image_type'    => $imageType,
            'zones'         => $zones,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/ecs_config/image');
    }

    // ==================== 磁盘管理 ====================

    /**
     * 磁盘列表
     */
    public function disk()
    {
        $regionId     = (int) $this->request->get('region_id', 0);
        $zoneId       = (int) $this->request->get('zone_id', 0);
        $diskCategory = trim((string) $this->request->get('disk_category', ''));
        $page         = max(1, (int) $this->request->get('page', 1));
        $limit        = 15;

        $region = EcsRegionModel::getById($regionId);
        if (!$region) {
            return redirect('/ecs_config/index');
        }

        $filters = ['region_id' => $regionId];
        if ($zoneId > 0) $filters['zone_id'] = $zoneId;
        if ($diskCategory !== '') $filters['disk_category'] = $diskCategory;

        $result    = EcsDiskModel::getList($page, $limit, $filters);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = '?region_id=' . $regionId . '&';
        if ($zoneId > 0) $pQuery .= 'zone_id=' . $zoneId . '&';
        if ($diskCategory !== '') $pQuery .= 'disk_category=' . urlencode($diskCategory) . '&';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        $zones = EcsZoneModel::getActiveByRegionId($regionId);

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'region'        => $region,
            'region_id'     => $regionId,
            'zone_id'       => $zoneId,
            'disk_category' => $diskCategory,
            'zones'         => $zones,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/ecs_config/disk');
    }

    // ==================== 线路管理 ====================

    /**
     * 线路列表
     */
    public function line()
    {
        $regionId = (int) $this->request->get('region_id', 0);
        $zoneId   = (int) $this->request->get('zone_id', 0);
        $page     = max(1, (int) $this->request->get('page', 1));
        $limit    = 15;

        $region = EcsRegionModel::getById($regionId);
        if (!$region) {
            return redirect('/ecs_config/index');
        }

        $filters = ['region_id' => $regionId];
        if ($zoneId > 0) $filters['zone_id'] = $zoneId;

        $result    = EcsLineModel::getList($page, $limit, $filters);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = '?region_id=' . $regionId . '&';
        if ($zoneId > 0) $pQuery .= 'zone_id=' . $zoneId . '&';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        $zones = EcsZoneModel::getActiveByRegionId($regionId);

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'region'        => $region,
            'region_id'     => $regionId,
            'zone_id'       => $zoneId,
            'zones'         => $zones,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/ecs_config/line');
    }

    // ==================== 带宽管理 ====================

    /**
     * 带宽列表
     */
    public function bandwidth()
    {
        $regionId = (int) $this->request->get('region_id', 0);
        $zoneId   = (int) $this->request->get('zone_id', 0);
        $page     = max(1, (int) $this->request->get('page', 1));
        $limit    = 15;

        $region = EcsRegionModel::getById($regionId);
        if (!$region) {
            return redirect('/ecs_config/index');
        }

        $filters = ['region_id' => $regionId];
        if ($zoneId > 0) $filters['zone_id'] = $zoneId;

        $result    = EcsBandwidthModel::getList($page, $limit, $filters);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = '?region_id=' . $regionId . '&';
        if ($zoneId > 0) $pQuery .= 'zone_id=' . $zoneId . '&';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        $zones = EcsZoneModel::getActiveByRegionId($regionId);

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'region'        => $region,
            'region_id'     => $regionId,
            'zone_id'       => $zoneId,
            'zones'         => $zones,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/ecs_config/bandwidth');
    }

    // ==================== VPC管理 ====================

    /**
     * VPC列表
     */
    public function vpc()
    {
        $regionId = (int) $this->request->get('region_id', 0);
        $zoneId   = (int) $this->request->get('zone_id', 0);
        $keyword  = trim((string) $this->request->get('keyword', ''));
        $page     = max(1, (int) $this->request->get('page', 1));
        $limit    = 15;

        $region = EcsRegionModel::getById($regionId);
        if (!$region) {
            return redirect('/ecs_config/index');
        }

        $filters = ['region_id' => $regionId];
        if ($zoneId > 0) $filters['zone_id'] = $zoneId;
        if ($keyword !== '') $filters['keyword'] = $keyword;

        $result    = EcsVpcModel::getList($page, $limit, $filters);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = '?region_id=' . $regionId . '&';
        if ($zoneId > 0) $pQuery .= 'zone_id=' . $zoneId . '&';
        if ($keyword !== '') $pQuery .= 'keyword=' . urlencode($keyword) . '&';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        $zones = EcsZoneModel::getActiveByRegionId($regionId);

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'keyword'       => $keyword,
            'region'        => $region,
            'region_id'     => $regionId,
            'zone_id'       => $zoneId,
            'zones'         => $zones,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/ecs_config/vpc');
    }

    // ==================== 全局列表（所有地域） ====================

    /**
     * 获取地域名称映射
     */
    private function getRegionMap(): array
    {
        $regions = EcsRegionModel::getActiveAll();
        $map = [];
        foreach ($regions as $r) {
            $map[$r['id']] = $r['name'];
        }
        return $map;
    }

    /**
     * 所有可用区列表（全局）
     */
    public function zoneList()
    {
        $keyword = trim((string) $this->request->get('keyword', ''));
        $page    = max(1, (int) $this->request->get('page', 1));
        $limit   = 15;

        $filters = [];
        if ($keyword !== '') $filters['keyword'] = $keyword;

        $result    = EcsZoneModel::getList($page, $limit, $filters);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = $keyword !== '' ? '?keyword=' . urlencode($keyword) . '&' : '?';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        $regionMap = $this->getRegionMap();
        foreach ($result['list'] as &$v) {
            $v['region_name'] = $regionMap[$v['region_id']] ?? '-';
        }
        unset($v);

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
        return View::fetch('/ecs_config/zone_list');
    }

    /**
     * 所有规格列表（全局）
     */
    public function specList()
    {
        $keyword = trim((string) $this->request->get('keyword', ''));
        $page    = max(1, (int) $this->request->get('page', 1));
        $limit   = 15;

        $filters = [];
        if ($keyword !== '') $filters['keyword'] = $keyword;

        $result    = EcsSpecModel::getList($page, $limit, $filters);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = $keyword !== '' ? '?keyword=' . urlencode($keyword) . '&' : '?';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        $regionMap = $this->getRegionMap();
        foreach ($result['list'] as &$v) {
            $v['region_name'] = $regionMap[$v['region_id']] ?? '-';
        }
        unset($v);

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
        return View::fetch('/ecs_config/spec_list');
    }

    /**
     * 所有镜像列表（全局）
     */
    public function imageList()
    {
        $imageType = trim((string) $this->request->get('image_type', ''));
        $keyword   = trim((string) $this->request->get('keyword', ''));
        $page      = max(1, (int) $this->request->get('page', 1));
        $limit     = 15;

        $filters = [];
        if ($imageType !== '') $filters['image_type'] = $imageType;
        if ($keyword !== '') $filters['keyword'] = $keyword;

        $result    = EcsImageModel::getList($page, $limit, $filters);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = '?';
        if ($keyword !== '') $pQuery .= 'keyword=' . urlencode($keyword) . '&';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        $regionMap = $this->getRegionMap();
        foreach ($result['list'] as &$v) {
            $v['region_name'] = $regionMap[$v['region_id']] ?? '-';
        }
        unset($v);

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'keyword'       => $keyword,
            'image_type'    => $imageType,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/ecs_config/image_list');
    }

    /**
     * 所有磁盘列表（全局）
     */
    public function diskList()
    {
        $diskCategory = trim((string) $this->request->get('disk_category', ''));
        $page         = max(1, (int) $this->request->get('page', 1));
        $limit        = 15;

        $filters = [];
        if ($diskCategory !== '') $filters['disk_category'] = $diskCategory;

        $result    = EcsDiskModel::getList($page, $limit, $filters);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = '?';
        if ($diskCategory !== '') $pQuery .= 'disk_category=' . urlencode($diskCategory) . '&';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        $regionMap = $this->getRegionMap();
        foreach ($result['list'] as &$v) {
            $v['region_name'] = $regionMap[$v['region_id']] ?? '-';
        }
        unset($v);

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'disk_category' => $diskCategory,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/ecs_config/disk_list');
    }

    /**
     * 所有线路列表（全局）
     */
    public function lineList()
    {
        $page  = max(1, (int) $this->request->get('page', 1));
        $limit = 15;

        $result    = EcsLineModel::getList($page, $limit, []);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = '?';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        $regionMap = $this->getRegionMap();
        foreach ($result['list'] as &$v) {
            $v['region_name'] = $regionMap[$v['region_id']] ?? '-';
        }
        unset($v);

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/ecs_config/line_list');
    }

    /**
     * 所有带宽列表（全局）
     */
    public function bandwidthList()
    {
        $page  = max(1, (int) $this->request->get('page', 1));
        $limit = 15;

        $result    = EcsBandwidthModel::getList($page, $limit, []);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = '?';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        $regionMap = $this->getRegionMap();
        foreach ($result['list'] as &$v) {
            $v['region_name'] = $regionMap[$v['region_id']] ?? '-';
        }
        unset($v);

        View::assign([
            'list'          => $result['list'],
            'total'         => $result['total'],
            'page'          => $page,
            'totalPage'     => $totalPage,
            'p_query'       => $pQuery,
            'p_start'       => $pStart,
            'p_end'         => $pEnd,
            'admin_name'    => Session::get('admin_name', '管理员'),
            'admin_username'=> Session::get('admin_username', ''),
            'admin_id'      => (int) Session::get('admin_id', 0),
        ]);
        return View::fetch('/ecs_config/bandwidth_list');
    }

    /**
     * 所有VPC列表（全局）
     */
    public function vpcList()
    {
        $keyword = trim((string) $this->request->get('keyword', ''));
        $page    = max(1, (int) $this->request->get('page', 1));
        $limit   = 15;

        $filters = [];
        if ($keyword !== '') $filters['keyword'] = $keyword;

        $result    = EcsVpcModel::getList($page, $limit, $filters);
        $totalPage = $result['total'] > 0 ? (int) ceil($result['total'] / $limit) : 1;
        $pQuery    = $keyword !== '' ? '?keyword=' . urlencode($keyword) . '&' : '?';
        $pStart    = max(1, $page - 2);
        $pEnd      = min($totalPage, $page + 2);

        $regionMap = $this->getRegionMap();
        foreach ($result['list'] as &$v) {
            $v['region_name'] = $regionMap[$v['region_id']] ?? '-';
        }
        unset($v);

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
        return View::fetch('/ecs_config/vpc_list');
    }

    // ==================== 通用CRUD操作 ====================

    /**
     * 通用新增
     */
    public function itemAdd()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }

        $type = trim((string) $this->request->post('type', ''));
        $data = $this->request->post();

        switch ($type) {
            case 'zone':
                $regionId = (int) ($data['region_id'] ?? 0);
                $name     = trim((string) ($data['name'] ?? ''));
                $sort     = (int) ($data['sort'] ?? 0);
                if ($regionId <= 0 || $name === '') {
                    return json(['code' => 1, 'msg' => '参数不完整']);
                }
                EcsZoneModel::create([
                    'region_id' => $regionId,
                    'name'      => $name,
                    'sort'      => $sort,
                ]);
                return json(['code' => 0, 'msg' => '添加成功']);

            case 'spec':
                $regionId = (int) ($data['region_id'] ?? 0);
                $zoneId   = (int) ($data['zone_id'] ?? 0);
                $cpu      = trim((string) ($data['cpu'] ?? ''));
                $memory   = trim((string) ($data['memory'] ?? ''));
                $price    = (float) ($data['price'] ?? 0);
                $sort     = (int) ($data['sort'] ?? 0);
                if ($regionId <= 0 || $cpu === '' || $memory === '') {
                    return json(['code' => 1, 'msg' => '参数不完整']);
                }
                EcsSpecModel::create([
                    'region_id' => $regionId,
                    'zone_id'   => $zoneId,
                    'cpu'       => $cpu,
                    'memory'    => $memory,
                    'price'     => $price,
                    'sort'      => $sort,
                ]);
                return json(['code' => 0, 'msg' => '添加成功']);

            case 'image':
                $regionId  = (int) ($data['region_id'] ?? 0);
                $zoneId    = (int) ($data['zone_id'] ?? 0);
                $imageType = trim((string) ($data['image_type'] ?? '系统镜像'));
                $os        = trim((string) ($data['os'] ?? ''));
                $version   = trim((string) ($data['version'] ?? ''));
                $sort      = (int) ($data['sort'] ?? 0);
                if ($regionId <= 0 || $os === '' || $version === '') {
                    return json(['code' => 1, 'msg' => '参数不完整']);
                }
                EcsImageModel::create([
                    'region_id'  => $regionId,
                    'zone_id'    => $zoneId,
                    'image_type' => $imageType,
                    'os'         => $os,
                    'version'    => $version,
                    'sort'       => $sort,
                ]);
                return json(['code' => 0, 'msg' => '添加成功']);

            case 'disk':
                $regionId     = (int) ($data['region_id'] ?? 0);
                $zoneId       = (int) ($data['zone_id'] ?? 0);
                $diskCategory = trim((string) ($data['disk_category'] ?? '数据盘'));
                $name         = trim((string) ($data['name'] ?? ''));
                $minSize      = (int) ($data['min_size'] ?? 10);
                $maxSize      = (int) ($data['max_size'] ?? 2000);
                $pricePerGb   = (float) ($data['price_per_gb'] ?? 0);
                $sort         = (int) ($data['sort'] ?? 0);
                if ($regionId <= 0 || $name === '') {
                    return json(['code' => 1, 'msg' => '参数不完整']);
                }
                EcsDiskModel::create([
                    'region_id'     => $regionId,
                    'zone_id'       => $zoneId,
                    'disk_category' => $diskCategory,
                    'name'          => $name,
                    'min_size'      => $minSize,
                    'max_size'      => $maxSize,
                    'price_per_gb'  => $pricePerGb,
                    'sort'          => $sort,
                ]);
                return json(['code' => 0, 'msg' => '添加成功']);

            case 'line':
                $regionId = (int) ($data['region_id'] ?? 0);
                $zoneId   = (int) ($data['zone_id'] ?? 0);
                $name     = trim((string) ($data['name'] ?? ''));
                $sort     = (int) ($data['sort'] ?? 0);
                if ($regionId <= 0 || $name === '') {
                    return json(['code' => 1, 'msg' => '参数不完整']);
                }
                EcsLineModel::create([
                    'region_id' => $regionId,
                    'zone_id'   => $zoneId,
                    'name'      => $name,
                    'sort'      => $sort,
                ]);
                return json(['code' => 0, 'msg' => '添加成功']);

            case 'bandwidth':
                $regionId    = (int) ($data['region_id'] ?? 0);
                $zoneId      = (int) ($data['zone_id'] ?? 0);
                $minBw       = (int) ($data['min_bandwidth'] ?? 1);
                $maxBw       = (int) ($data['max_bandwidth'] ?? 600);
                $pricePerMbps = (float) ($data['price_per_mbps'] ?? 0);
                if ($regionId <= 0) {
                    return json(['code' => 1, 'msg' => '参数不完整']);
                }
                EcsBandwidthModel::create([
                    'region_id'      => $regionId,
                    'zone_id'        => $zoneId,
                    'min_bandwidth'  => $minBw,
                    'max_bandwidth'  => $maxBw,
                    'price_per_mbps' => $pricePerMbps,
                ]);
                return json(['code' => 0, 'msg' => '添加成功']);

            case 'vpc':
                $regionId = (int) ($data['region_id'] ?? 0);
                $zoneId   = (int) ($data['zone_id'] ?? 0);
                $name     = trim((string) ($data['name'] ?? ''));
                $cidr     = trim((string) ($data['cidr'] ?? ''));
                $sort     = (int) ($data['sort'] ?? 0);
                if ($regionId <= 0 || $name === '' || $cidr === '') {
                    return json(['code' => 1, 'msg' => '参数不完整']);
                }
                EcsVpcModel::create([
                    'region_id' => $regionId,
                    'zone_id'   => $zoneId,
                    'name'      => $name,
                    'cidr'      => $cidr,
                    'sort'      => $sort,
                ]);
                return json(['code' => 0, 'msg' => '添加成功']);

            default:
                return json(['code' => 1, 'msg' => '未知类型']);
        }
    }

    /**
     * 通用编辑
     */
    public function itemEdit()
    {
        $id = (int) $this->request->get('id', 0);
        if ($id <= 0) {
            $id = (int) $this->request->post('id', 0);
        }

        $type = trim((string) $this->request->get('type', ''));
        if ($type === '') {
            $type = trim((string) $this->request->post('type', ''));
        }

        $model = $this->getModelByType($type);
        if (!$model) {
            return json(['code' => 1, 'msg' => '未知类型']);
        }

        $row = $model::getById($id);
        if (!$row) {
            if ($this->request->isPost()) {
                return json(['code' => 1, 'msg' => '数据不存在']);
            }
            return json(['code' => 1, 'msg' => '数据不存在']);
        }

        if (!$this->request->isPost()) {
            return json(['code' => 0, 'data' => $row]);
        }

        $data = $this->request->post();
        $updateData = [];

        switch ($type) {
            case 'zone':
                $name = trim((string) ($data['name'] ?? ''));
                $sort = (int) ($data['sort'] ?? 0);
                if ($name === '') return json(['code' => 1, 'msg' => '名称不能为空']);
                $updateData = ['name' => $name, 'sort' => $sort];
                break;

            case 'spec':
                $cpu    = trim((string) ($data['cpu'] ?? ''));
                $memory = trim((string) ($data['memory'] ?? ''));
                $price  = (float) ($data['price'] ?? 0);
                $sort   = (int) ($data['sort'] ?? 0);
                $zoneId = (int) ($data['zone_id'] ?? 0);
                if ($cpu === '' || $memory === '') return json(['code' => 1, 'msg' => '参数不完整']);
                $updateData = ['zone_id' => $zoneId, 'cpu' => $cpu, 'memory' => $memory, 'price' => $price, 'sort' => $sort];
                break;

            case 'image':
                $imageType = trim((string) ($data['image_type'] ?? '系统镜像'));
                $os        = trim((string) ($data['os'] ?? ''));
                $version   = trim((string) ($data['version'] ?? ''));
                $sort      = (int) ($data['sort'] ?? 0);
                $zoneId    = (int) ($data['zone_id'] ?? 0);
                if ($os === '' || $version === '') return json(['code' => 1, 'msg' => '参数不完整']);
                $updateData = ['zone_id' => $zoneId, 'image_type' => $imageType, 'os' => $os, 'version' => $version, 'sort' => $sort];
                break;

            case 'disk':
                $diskCategory = trim((string) ($data['disk_category'] ?? '数据盘'));
                $name         = trim((string) ($data['name'] ?? ''));
                $minSize      = (int) ($data['min_size'] ?? 10);
                $maxSize      = (int) ($data['max_size'] ?? 2000);
                $pricePerGb   = (float) ($data['price_per_gb'] ?? 0);
                $sort         = (int) ($data['sort'] ?? 0);
                $zoneId       = (int) ($data['zone_id'] ?? 0);
                if ($name === '') return json(['code' => 1, 'msg' => '名称不能为空']);
                $updateData = ['zone_id' => $zoneId, 'disk_category' => $diskCategory, 'name' => $name, 'min_size' => $minSize, 'max_size' => $maxSize, 'price_per_gb' => $pricePerGb, 'sort' => $sort];
                break;

            case 'line':
                $name = trim((string) ($data['name'] ?? ''));
                $sort = (int) ($data['sort'] ?? 0);
                $zoneId = (int) ($data['zone_id'] ?? 0);
                if ($name === '') return json(['code' => 1, 'msg' => '名称不能为空']);
                $updateData = ['zone_id' => $zoneId, 'name' => $name, 'sort' => $sort];
                break;

            case 'bandwidth':
                $minBw        = (int) ($data['min_bandwidth'] ?? 1);
                $maxBw        = (int) ($data['max_bandwidth'] ?? 600);
                $pricePerMbps = (float) ($data['price_per_mbps'] ?? 0);
                $zoneId       = (int) ($data['zone_id'] ?? 0);
                $updateData = ['zone_id' => $zoneId, 'min_bandwidth' => $minBw, 'max_bandwidth' => $maxBw, 'price_per_mbps' => $pricePerMbps];
                break;

            case 'vpc':
                $name   = trim((string) ($data['name'] ?? ''));
                $cidr   = trim((string) ($data['cidr'] ?? ''));
                $sort   = (int) ($data['sort'] ?? 0);
                $zoneId = (int) ($data['zone_id'] ?? 0);
                if ($name === '' || $cidr === '') return json(['code' => 1, 'msg' => '参数不完整']);
                $updateData = ['zone_id' => $zoneId, 'name' => $name, 'cidr' => $cidr, 'sort' => $sort];
                break;

            default:
                return json(['code' => 1, 'msg' => '未知类型']);
        }

        $model::where('id', $id)->update($updateData);
        return json(['code' => 0, 'msg' => '修改成功']);
    }

    /**
     * 通用切换状态
     */
    public function itemStatus()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }

        $type   = trim((string) $this->request->post('type', ''));
        $id     = (int) $this->request->post('id', 0);
        $status = (int) $this->request->post('status', 0);

        if ($id <= 0 || $type === '') {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $model = $this->getModelByType($type);
        if (!$model) {
            return json(['code' => 1, 'msg' => '未知类型']);
        }

        $model::where('id', $id)->update(['status' => $status]);
        return json(['code' => 0, 'msg' => $status ? '已启用' : '已禁用']);
    }

    /**
     * 通用删除
     */
    public function itemDelete()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }

        $type = trim((string) $this->request->post('type', ''));
        $id   = (int) $this->request->post('id', 0);

        if ($id <= 0 || $type === '') {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $model = $this->getModelByType($type);
        if (!$model) {
            return json(['code' => 1, 'msg' => '未知类型']);
        }

        $model::where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    /**
     * 根据类型获取模型类
     */
    private function getModelByType(string $type): ?string
    {
        $map = [
            'zone'      => EcsZoneModel::class,
            'spec'      => EcsSpecModel::class,
            'image'     => EcsImageModel::class,
            'disk'      => EcsDiskModel::class,
            'line'      => EcsLineModel::class,
            'bandwidth' => EcsBandwidthModel::class,
            'vpc'       => EcsVpcModel::class,
        ];
        return $map[$type] ?? null;
    }
}