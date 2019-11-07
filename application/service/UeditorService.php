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
namespace app\service;

use app\service\ResourcesService;

/**
 * 百度编辑器附件服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class UeditorService
{
    private static $current_action;
    private static $current_config;
    private static $current_result;
    private static $params;
    private static $path_type;

    /**
     * 请求入口
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function Run($params = [])
    {
        // 配置信息
        self::$params = $params;
        self::$current_config = config('ueditor.');
        self::$current_action = isset($params['action']) ? $params['action'] : '';
        self::$path_type = isset($params['path_type']) ? $params['path_type'] : PathToParams('path_type', 'other');

        // action
        switch(self::$current_action)
        {
            // 配置信息
            case 'config':
                self::$current_result = self::$current_config;
                break;

            /* 上传图片 */
            case 'uploadimage':
            /* 上传涂鸦 */
            case 'uploadscrawl':
            /* 上传视频 */
            case 'uploadvideo':
            /* 上传文件 */
            case 'uploadfile':
                self::ActionUpload();
                break;

            /* 列出图片 */
            case 'listimage':
            /* 列出文件 */
            case 'listfile':
            /* 列出视频 */
            case 'listvideo':
                self::ActionList();
                break;

            /* 抓取远程文件 */
            case 'catchimage':
                self::ActionCrawler();
                break;

            /* 删除文件 */
            case 'deletefile':
                self::DeleteFile();
                break;

            default:
                self::$current_result = [
                    'state'=> '请求地址出错'
                ];
        }

        // 输出结果
        if(!empty($params['callback']))
        {
            if(preg_match("/^[\w_]+$/", $params['callback']))
            {
                return DataReturn(htmlspecialchars($params['callback']) . '(' . self::$current_result . ')', -1);
            } else {
                return DataReturn('callback参数不合法', -1);
            }
        }
        return DataReturn('操作成功', 0, self::$current_result);
    }

    /**
     * 文件删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-10
     * @desc    description
     */
    private static function DeleteFile()
    {
        $ret = ResourcesService::AttachmentDelete(input());
        if($ret['code'] == 0)
        {
            self::$current_result = [
                'state'=> 'SUCCESS'
            ];
        } else {
            self::$current_result = [
                'state'=> $ret['msg']
            ];
        }
    }

    /**
     * [ActionUpload 上传配置]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-01-17T22:45:06+0800
     */
    private static function ActionUpload()
    {
        $attachment_type = "file";
        switch(htmlspecialchars(self::$current_action))
        {
            case 'uploadimage':
                $temp_config = array(
                        "pathFormat" => self::$current_config['imagePathFormat'],
                        "maxSize" => self::$current_config['imageMaxSize'],
                        "allowFiles" => self::$current_config['imageAllowFiles']
                    );
                $field_name = self::$current_config['imageFieldName'];
                $attachment_type = "image";
                break;

            case 'uploadscrawl':
                $temp_config = array(
                        "pathFormat" => self::$current_config['scrawlPathFormat'],
                        "maxSize" => self::$current_config['scrawlMaxSize'],
                        "allowFiles" => self::$current_config['scrawlAllowFiles'],
                        "oriName" => "scrawl.png"
                    );
                $field_name = self::$current_config['scrawlFieldName'];
                $attachment_type = "scrawl";
                break;

            case 'uploadvideo':
                $temp_config = array(
                        "pathFormat" => self::$current_config['videoPathFormat'],
                        "maxSize" => self::$current_config['videoMaxSize'],
                        "allowFiles" => self::$current_config['videoAllowFiles']
                    );
                $field_name = self::$current_config['videoFieldName'];
                $attachment_type = "video";
                break;

            case 'uploadfile':
            default:
                $temp_config = array(
                        "pathFormat" => self::$current_config['filePathFormat'],
                        "maxSize" => self::$current_config['fileMaxSize'],
                        "allowFiles" => self::$current_config['fileAllowFiles']
                    );
                $field_name = self::$current_config['fileFieldName'];
                $attachment_type = "file";
        }

        /* 生成上传实例对象并完成上传 */
        $up = new \base\Uploader($field_name, $temp_config, $attachment_type);

        /**
         * 得到上传文件所对应的各个参数,数组结构
         * array(
         *     "state" => "",          //上传状态，上传成功时必须返回"SUCCESS"
         *     "url" => "",            //返回的地址
         *     "path" => "",           //绝对地址
         *     "title" => "",          //新文件名
         *     "original" => "",       //原始文件名
         *     "type" => ""            //文件类型
         *     "size" => "",           //文件大小
         *     "hash" => "",           //sha256值
         * )
         */
        $data = $up->getFileInfo();
        if(isset($data['state']) && $data['state'] == 'SUCCESS')
        {
            $data['type'] = $attachment_type;
            $data['path_type'] = self::$path_type;
            $ret = ResourcesService::AttachmentAdd($data);
            if($ret['code'] == 0)
            {
                self::$current_result = $ret['data'];
            } else {
                self::$current_result = ['state'=>$ret['msg']];
            }
        } else {
            self::$current_result = $data;
        }
    }

    /**
     * [ActionList 文件列表]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-01-17T22:55:16+0800
     */
    private static function ActionList()
    {
        /* 判断类型 */
        switch(self::$current_action)
        {
            /* 列出视频 */
            case 'listvideo':
                $allow_files = self::$current_config['videoManagerAllowFiles'];
                $list_size = self::$current_config['videoManagerListSize'];
                $path = self::$current_config['videoManagerListPath'];
                break;
            /* 列出文件 */
            case 'listfile':
                $allow_files = self::$current_config['fileManagerAllowFiles'];
                $list_size = self::$current_config['fileManagerListSize'];
                $path = self::$current_config['fileManagerListPath'];
                break;

            /* 列出图片 */
            case 'listimage':
            default:
                $allow_files = self::$current_config['imageManagerAllowFiles'];
                $list_size = self::$current_config['imageManagerListSize'];
                $path = self::$current_config['imageManagerListPath'];
        }
        $allow_files = substr(str_replace(".", "|", join("", $allow_files)), 1);

        /* 获取参数 */
        $size = isset(self::$params['size']) ? htmlspecialchars(self::$params['size']) : $list_size;
        $start = isset(self::$params['start']) ? htmlspecialchars(self::$params['start']) : 0;
        $end = $start + $size;

        // 参数
        $params = [
            'm'         => $start,
            'n'         => $size,
            'where'     => [
                ['type', '=', substr(self::$current_action, 4)],
                ['path_type', '=', self::$path_type]
            ],
        ];

        // 搜索关键字
        if(!empty(self::$params['keywords']))
        {
            $params['where'][] = ['original', 'like', '%'.self::$params['keywords'].'%'];
        }

        // 数据初始化
        $data = array(
            'state'     => "没有相关数据",
            'list'      => [],
            'start'     => $start,
            'total'     => ResourcesService::AttachmentTotal($params['where']),
        );

        // 获取数据
        $ret = ResourcesService::AttachmentList($params);
        if(!empty($ret['data']))
        {
            $data['state'] = 'SUCCESS';
            $data['list'] = $ret['data'];
        }
        
        self::$current_result = $data;
    }

    /**
     * [ActionCrawler 抓取远程文件]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-01-17T23:08:29+0800
     */
    private static function ActionCrawler()
    {
        $temp_config = array(
                "pathFormat" => self::$current_config['catcherPathFormat'],
                "maxSize" => self::$current_config['catcherMaxSize'],
                "allowFiles" => self::$current_config['catcherAllowFiles'],
                "oriName" => "remote.png"
            );
        $field_name = self::$current_config['catcherFieldName'];

        /* 抓取远程图片 */
        $list = array();
        $source = isset(self::$params[$field_name]) ? self::$params[$field_name] : self::$params[$field_name];
        foreach($source as $imgUrl)
        {
            $item = new \base\Uploader($imgUrl, $temp_config, "remote");
            /**
             * 得到上传文件所对应的各个参数,数组结构
             * array(
             *     "state" => "",          //上传状态，上传成功时必须返回"SUCCESS"
             *     "url" => "",            //返回的地址
             *     "path" => "",           //绝对地址
             *     "title" => "",          //新文件名
             *     "original" => "",       //原始文件名
             *     "type" => ""            //文件类型
             *     "size" => "",           //文件大小
             *     "hash" => "",           //sha256值
             * )
             */
            $data = $up->getFileInfo();
            if(isset($data['state']) && $data['state'] == 'SUCCESS')
            {
                $data['type'] = 'remote';
                $data['path_type'] = self::$path_type;
                $ret = ResourcesService::AttachmentAdd($data);
                if($ret['code'] != 0)
                {
                    $data['state'] = $ret['msg'];
                }
            }
            array_push($list, array(
                "state" => $data["state"],
                "url" => $data["url"],
                "size" => $data["size"],
                "title" => htmlspecialchars($data["title"]),
                "original" => htmlspecialchars($data["original"]),
                "source" => htmlspecialchars($imgUrl)
            ));
        }

        /* 返回抓取数据 */
        self::$current_result = [
            'state'     => count($list) ? 'SUCCESS':'ERROR',
            'list'      => $list,
        ];
    }
}
?>