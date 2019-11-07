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
namespace app\api\controller;

use app\service\GoodsService;
use app\service\UserService;
use app\service\PaymentService;
use app\service\BuyService;
use think\Db;
/**
 * 购买
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Buy extends Common
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

        // 是否登录
        $this->IsLogin();
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
        // 获取商品列表
        $params = $this->data_post;
        $params['user'] = $this->user;
        $ret = BuyService::BuyTypeGoodsList($params);

        // 商品校验
        if(isset($ret['code']) && $ret['code'] == 0)
        {
            // 支付方式
            $payment_list = PaymentService::BuyPaymentList(['is_enable'=>1, 'is_open_user'=>1]);

            // 数据返回组装
            $result = [
                'goods_list'                => $ret['data']['goods'],
                'payment_list'              => $payment_list,
                'base'                      => $ret['data']['base'],
                'extension_data'            => $ret['data']['extension_data'],
                'common_order_is_booking'   => (int) MyC('common_order_is_booking', 0),
            ];
            return DataReturn('操作成功', 0, $result);
        }
        return $ret;
    }

    /**
     * 订单添加
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-25
     * @desc    description
     */
    public function Add()
    {
        $params = $this->data_post;
        $params['user'] = $this->user;
        return BuyService::OrderAdd($params);
    }

    public function UserIdData()
    {   
        $user = $this->user;
        $params = $this->data_post;
        if (empty($params['id_card']) || empty($params['id_name'])) {
            //先去查询有咩有
            $find = Db::name('user')->where('id',$user['id'])->find();
            if (empty($find['id_card']) || empty($find['id_name'])) {
                return DataReturn('请输入身份证号或姓名', -1, []);
            }
            $params['id_name'] = $find['id_name'];
            $params['id_card'] = $find['id_card'];
        }
        return DataReturn('成功', 0, 'ok');
        $payment = PaymentService::PaymentList(['where'=>['id'=>4]]);
        $pay_name = 'payment\\'.$payment[0]['payment'];
        $data = [
            'id_name'=>$params['id_name'],
            'id_card'=>$params['id_card'],
            'outer_order_no'=>date('YmdHis').rand(10000,99999)
        ];
        $ret = (new $pay_name($payment[0]['config']))->face($data);
        if ($ret['code'] == 0) {
            Db::name('user')->where('id',$user['id'])->update([
                'id_name'=>$params['id_name'],
                'id_card'=>$params['id_card']
            ]);
            return DataReturn('成功', 0, $ret['msg']);
        }
        return DataReturn('失败', -1, $ret['msg']);
    }

    public function faceAuth()
    {   
        return DataReturn('成功', 0, []);
        $params = $this->data_post;
        if (empty($params['id_card']) || empty($params['id_name'])) {
            return DataReturn('请输入身份证号或姓名', -1, []);
        }

        $payment = PaymentService::PaymentList(['where'=>['id'=>4]]);
        $pay_name = 'payment\\'.$payment[0]['payment'];
        $data = [
            'id_name'=>$params['id_name'],
            'id_card'=>$params['id_card'],
            'transaction_id'=>date('YmdHis').rand(10000,99999)
        ];
        $ret = (new $pay_name($payment[0]['config']))->certification($data);
        dump($ret);
    }
}
?>