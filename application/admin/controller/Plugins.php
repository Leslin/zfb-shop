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

use app\service\PluginsService;

/**
 * 应用调用入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Plugins extends Common
{
    /**
     * 构造方法
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-11-30
     * @desc    description
     */
    public function __construct()
    {
        parent::__construct();

        // 登录校验
        $this->IsLogin();

        // 权限校验
        $this->IsPower();
    }
    
    /**
     * [Index 首页]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-02-22T16:50:32+0800
     */
    public function Index()
    {
        // 参数
        $params = input();

        // 请求参数校验
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'pluginsname',
                'error_msg'         => '应用名称有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'pluginscontrol',
                'error_msg'         => '应用控制器有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'pluginsaction',
                'error_msg'         => '应用操作方法有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            if(IS_AJAX)
            {
                return DataReturn($ret, -5000);
            } else {
                $this->assign('msg', $ret);
                return $this->fetch('public/tips_error');
            }
        }

        // 应用名称/控制器/方法
        $pluginsname = $params['pluginsname'];
        $pluginscontrol = strtolower($params['pluginscontrol']);
        $pluginsaction = strtolower($params['pluginsaction']);

        // 应用校验
        $ret = $this->PluginsCheck($pluginsname, $pluginscontrol, $pluginsaction);
        if($ret['code'] != 0)
        {
            if(IS_AJAX)
            {
                return $ret;
            } else {
                $this->assign('msg', $ret['msg']);
                return $this->fetch('public/tips_error');
            }
        }

        // 视图初始化
        $this->PluginsViewInit($pluginsname, $pluginscontrol, $pluginsaction);

        // 编辑器文件存放地址定义
        $this->assign('editor_path_type', 'plugins_'.$pluginsname);

        // 应用控制器
        $plugins = '\app\plugins\\'.$pluginsname.'\admin\\'.ucfirst($pluginscontrol);
        if(!class_exists($plugins))
        {
            if(IS_AJAX)
            {
                return DataReturn(ucfirst($pluginscontrol).' 应用控制器未定义', -1);
            } else {
                $this->assign('msg', ucfirst($pluginscontrol).' 应用控制器未定义');
                return $this->fetch('public/tips_error');
            }
        }

        // 调用方法
        $obj = new $plugins();
        if(!method_exists($obj, $pluginsaction))
        {
            if(IS_AJAX)
            {
                return DataReturn(ucfirst($pluginsaction).' 应用方法未定义', -1);
            } else {
                $this->assign('msg', ucfirst($pluginsaction).' 应用方法未定义');
                return $this->fetch('public/tips_error');
            }
        }
        return $obj->$pluginsaction($params);
    }

    /**
     * 应用校验
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-05-27T00:13:50+0800
     * @param    [string]                   $plugins_name       [应用名称]
     * @param    [string]                   $plugins_control    [控制器名称]
     * @param    [string]                   $plugins_action     [方法]
     */
    private function PluginsCheck($pluginsname, $pluginscontrol, $pluginsaction)
    {
        $ret = PluginsService::PluginsField($pluginsname, 'id');
        if(empty($ret['data']))
        {
            return DataReturn('应用不存在', -10);
        }
        return DataReturn('验证成功', 0);
    }

    /**
     * 视图初始化
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T22:46:29+0800
     * @param    [string]                   $plugins_name       [应用名称]
     * @param    [string]                   $plugins_control    [控制器名称]
     * @param    [string]                   $plugins_action     [方法]
     */
    public function PluginsViewInit($plugins_name, $plugins_control, $plugins_action)
    {
        // 应用名称/控制器/方法
        $this->assign('plugins_name', $plugins_name);
        $this->assign('plugins_control', $plugins_control);
        $this->assign('plugins_action', $plugins_action);
        
        // 当前操作名称
        $module_name = 'plugins';

        // 模块组
        $group = 'admin';

        // 控制器静态文件状态css,js
        $module_css = $module_name.DS.'css'.DS.$plugins_name.DS.$group.DS.$plugins_control;
        $module_css .= file_exists(ROOT_PATH.'static'.DS.$module_css.'.'.$plugins_action.'.css') ? '.'.$plugins_action.'.css' : '.css';
        $this->assign('module_css', file_exists(ROOT_PATH.'static'.DS.$module_css) ? $module_css : '');

        $module_js = $module_name.DS.'js'.DS.$plugins_name.DS.$group.DS.$plugins_control;
        $module_js .= file_exists(ROOT_PATH.'static'.DS.$module_js.'.'.$plugins_action.'.js') ? '.'.$plugins_action.'.js' : '.js';
        $this->assign('module_js', file_exists(ROOT_PATH.'static'.DS.$module_js) ? $module_js : '');

        // 应用公共css,js
        $plugins_css = $module_name.DS.'css'.DS.$plugins_name.DS.$group.DS.'common.css';
        $this->assign('plugins_css', file_exists(ROOT_PATH.'static'.DS.$plugins_css) ? $plugins_css : '');
        $plugins_js = $module_name.DS.'js'.DS.$plugins_name.DS.$group.DS.'common.js';
        $this->assign('plugins_js', file_exists(ROOT_PATH.'static'.DS.$plugins_js) ? $plugins_js : '');
    }
}
?>