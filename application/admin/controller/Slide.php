<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2019 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Devil
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\service\SlideService;

/**
 * 轮播图片管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Slide extends Common
{
    /**
     * 构造方法
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-03T12:39:08+0800
     */
    public function __construct()
    {
        // 调用父类前置方法
        parent::__construct();

        // 登录校验
        $this->IsLogin();

        // 权限校验
        $this->IsPower();
    }

    /**
     * [Index 轮播图片列表]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     */
    public function Index()
    {
        // 参数
        $params = input();

        // 分页
        $number = MyC('admin_page_number', 10, true);

        // 条件
        $where = SlideService::SlideListWhere($params);

        // 获取总数
        $total = SlideService::SlideTotal($where);

        // 分页
        $page_params = array(
                'number'    =>  $number,
                'total'     =>  $total,
                'where'     =>  $params,
                'page'      =>  isset($params['page']) ? intval($params['page']) : 1,
                'url'       =>  MyUrl('admin/slide/index'),
            );
        $page = new \base\Page($page_params);
        $this->assign('page_html', $page->GetPageHtml());

        // 获取列表
        $data_params = array(
            'm'         => $page->GetPageStarNumber(),
            'n'         => $number,
            'where'     => $where,
            'field'     => '*',
        );
        $data = SlideService::SlideList($data_params);
        $this->assign('data_list', $data['data']);

        // 是否启用
        $this->assign('common_is_enable_list', lang('common_is_enable_list'));

        // 所属平台
        $this->assign('common_platform_type', lang('common_platform_type'));

        // 事件类型
        $this->assign('common_app_event_type', lang('common_app_event_type'));

        // 参数
        $this->assign('params', $params);
        return $this->fetch();
    }

    /**
     * [SaveInfo 添加/编辑页面]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-14T21:37:02+0800
     */
    public function SaveInfo()
    {
        // 参数
        $params = input();

        // 数据
        $data = [];
        if(!empty($params['id']))
        {
            // 获取列表
            $data_params = array(
                'm'         => 0,
                'n'         => 1,
                'where'     => ['id'=>intval($params['id'])],
                'field'     => '*',
            );
            $ret = SlideService::SlideList($data_params);
            $data = empty($ret['data'][0]) ? [] : $ret['data'][0];
        }
        $this->assign('data', $data);

        // 是否启用
        $this->assign('common_is_enable_list', lang('common_is_enable_list'));

        // 所属平台
        $this->assign('common_platform_type', lang('common_platform_type'));

        // 事件类型
        $this->assign('common_app_event_type', lang('common_app_event_type'));

        // 编辑器文件存放地址
        $this->assign('editor_path_type', 'slide');

        return $this->fetch();
    }

    /**
     * [Save 轮播图片添加/编辑]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-14T21:37:02+0800
     */
    public function Save()
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params = input();
        return SlideService::SlideSave($params);
    }

    /**
     * [Delete 轮播图片删除]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-15T11:03:30+0800
     */
    public function Delete()
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params = input();
        return SlideService::SlideDelete($params);
    }

    /**
     * [StatusUpdate 状态更新]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-01-12T22:23:06+0800
     */
    public function StatusUpdate()
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params = input();
        return SlideService::SlideStatusUpdate($params);
    }
}
?>