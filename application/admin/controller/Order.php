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

use app\service\OrderService;
use app\service\PaymentService;
use app\service\ExpressService;
use think\Db;

/**
 * 订单管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Order extends Common
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
     * 订单列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     */
    public function Index()
    {
        // 参数
        $params = input();
        $params['admin'] = $this->admin;
        $params['user_type'] = 'admin';

        // 分页
        $number = MyC('admin_page_number', 10, true);

        // 条件
        $where = OrderService::OrderListWhere($params);

        // 获取总数
        $total = OrderService::OrderTotal($where);

        // 分页
        $page_params = array(
                'number'    =>  $number,
                'total'     =>  $total,
                'where'     =>  $params,
                'page'      =>  isset($params['page']) ? intval($params['page']) : 1,
                'url'       =>  MyUrl('admin/order/index'),
            );
        $page = new \base\Page($page_params);
        $this->assign('page_html', $page->GetPageHtml());

        // 获取列表
        $data_params = array(
            'm'         => $page->GetPageStarNumber(),
            'n'         => $number,
            'where'     => $where,
            'is_public' => 0,
        );
        $data = OrderService::OrderList($data_params);
        $this->assign('data_list', $data['data']);

        // 状态
        $this->assign('common_order_admin_status', lang('common_order_admin_status'));

        // 支付状态
        $this->assign('common_order_pay_status', lang('common_order_pay_status'));

        // 快递公司
        $this->assign('express_list', ExpressService::ExpressList());

        // 发起支付 - 支付方式
        $pay_where = [
            'where' => ['is_enable'=>1, 'is_open_user'=>1, 'payment'=>config('shopxo.under_line_list')],
        ];
        $this->assign('buy_payment_list', PaymentService::BuyPaymentList($pay_where));

        // 支付方式
        $this->assign('payment_list', PaymentService::PaymentList());

        // 评价状态
        $this->assign('common_comments_status_list', lang('common_comments_status_list'));

        // 平台
        $this->assign('common_platform_type', lang('common_platform_type'));

        // Excel地址
        $this->assign('excel_url', MyUrl('admin/order/excelexport', input()));

        // 参数
        $this->assign('params', $params);
        return $this->fetch();
    }

    /**
     * [ExcelExport excel文件导出]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-01-10T15:46:00+0800
     */
    public function ExcelExport()
    {
        // 参数
        $params = input();
        $params['admin'] = $this->admin;
        $params['user_type'] = 'admin';

        // 条件
        $where = OrderService::OrderListWhere($params);

        // 获取列表
        $data_params = array(
            'where'             => $where,
            'm'                 => 0,
            'n'                 => 100000,
            'is_excel_export'   => 1,
        );
        $data = OrderService::OrderList($data_params);

        // Excel驱动导出数据
        $excel = new \base\Excel(array('filename'=>'order', 'title'=>lang('excel_order_title_list'), 'data'=>$data['data'], 'msg'=>'没有相关数据'));
        return $excel->Export();
    }

    /**
     * [Delete 订单删除]
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

        // 删除操作
        $params = input();
        $params['user_id'] = $params['value'];
        $params['creator'] = $this->admin['id'];
        $params['creator_name'] = $this->admin['username'];
        $params['user_type'] = 'admin';
        return OrderService::OrderDelete($params);
    }

    /**
     * [Cancel 订单取消]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-15T11:03:30+0800
     */
    public function Cancel()
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 取消操作
        $params = input();
        $params['user_id'] = $params['value'];
        $params['creator'] = $this->admin['id'];
        $params['creator_name'] = $this->admin['username'];
        return OrderService::OrderCancel($params);
    }

    /**
     * [Delivery 订单发货]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-15T11:03:30+0800
     */
    public function Delivery()
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 发货操作
        $params = input();
        $params['creator'] = $this->admin['id'];
        $params['creator_name'] = $this->admin['username'];
        return OrderService::OrderDelivery($params);
    }

    /**
     * [Collect 订单收货]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-15T11:03:30+0800
     */
    public function Collect()
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 收货操作
        $params = input();
        $params['user_id'] = $params['value'];
        $params['creator'] = $this->admin['id'];
        $params['creator_name'] = $this->admin['username'];
        return OrderService::OrderCollect($params);
    }

    /**
     * [Confirm 订单确认]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-15T11:03:30+0800
     */
    public function Confirm()
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 订单确认
        $params = input();
        $params['user_id'] = $params['value'];
        $params['creator'] = $this->admin['id'];
        $params['creator_name'] = $this->admin['username'];
        return OrderService::OrderConfirm($params);
    }

    /**
     * 订单支付
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-28
     * @desc    description
     */
    public function Pay()
    {
        $params = input();
        $params['user'] = $this->admin;
        $params['user']['user_name_view'] = '管理员'.'-'.$this->admin['username'];
        return OrderService::AdminPay($params);
    }


    public function SubOrder()
    {
        // 参数
        $params = input();
        
        // 分页
        $number = 20;

        // 条件
        $where = [];
        if (isset($params['keywords']) && !empty($params['keywords'])) {
            $where['out_order_no'] = $params['keywords'];
        }

        // 获取总数
        $total = Db::name('order_sub')->where($where)->count();
        // 分页
        $page_params = array(
                'number'    =>  $number,
                'total'     =>  $total,
                'where'     =>  $params,
                'page'      =>  isset($params['page']) ? intval($params['page']) : 1,
                'url'       =>  MyUrl('admin/order/SubOrder'),
            );
        $page = new \base\Page($page_params);
        $this->assign('page_html', $page->GetPageHtml());

        // 获取列表
        $data_params = array(
            'm'         => $page->GetPageStarNumber(),
            'n'         => $number,
            'where'     => $where,
        );
        $data = Db::name('order_sub')->where($where)->limit($data_params['m'], $data_params['n'])->order('id desc')->select();
        $this->assign('data_list', $data);
        // 参数
        $this->assign('params', $params);
        return $this->fetch();
    }

    public function Report()
    {
        // 参数
        $params = input();
        
        // 分页
        $number = 20;

        // 条件
        $where = [];
        if (isset($params['keywords']) && !empty($params['keywords'])) {
            $where['repayment_time'] = $params['keywords'];
        }

        // 获取总数
        $total = Db::name('order_sub')->where($where)->group('repayment_time')->count();
        // 分页
        $page_params = array(
                'number'    =>  $number,
                'total'     =>  $total,
                'where'     =>  $params,
                'page'      =>  isset($params['page']) ? intval($params['page']) : 1,
                'url'       =>  MyUrl('admin/order/Report'),
            );
        $page = new \base\Page($page_params);
        $this->assign('page_html', $page->GetPageHtml());

        // 获取列表
        $data_params = array(
            'm'         => $page->GetPageStarNumber(),
            'n'         => $number,
            'where'     => $where,
        );
        $data = Db::name('order_sub')->field('repayment_time,COUNT(1) cnt')->where($where)->limit($data_params['m'], $data_params['n'])->group('repayment_time')->select();
        if (!empty($data)) {
            //
            foreach ($data as $k => $v) {
                $data[$k]['sum'] = Db::name('order_sub')->where('repayment_time',$data[$k]['repayment_time'])->sum('amount'); //已还款
                $data[$k]['repayment_cnt'] = Db::name('order_sub')->where('repayment_time',$data[$k]['repayment_time'])->where('status',1)->count(); //已还款
                $data[$k]['repayment_sum'] = Db::name('order_sub')->where('repayment_time',$data[$k]['repayment_time'])->where('status',1)->sum('amount'); //已还款
                $data[$k]['no_repayment_cnt'] = Db::name('order_sub')->where('repayment_time',$data[$k]['repayment_time'])->where('status',0)->count(); //已还款
                 $data[$k]['no_repayment_sum'] = Db::name('order_sub')->where('repayment_time',$data[$k]['repayment_time'])->where('status',0)->sum('amount'); //已还款
            }
        }
        $sub_total_fee = Db::name('order_sub')->sum('amount');
        $repayment_total_fee = Db::name('order_sub')->where('status',1)->sum('amount');
        $no_repayment_total_fee = Db::name('order_sub')->where('status',0)->sum('amount');
        $this->assign('sub_total_fee', $sub_total_fee);
        $this->assign('repayment_total_fee', $repayment_total_fee);
        $this->assign('no_repayment_total_fee', $no_repayment_total_fee);

        $this->assign('data_list', $data);
        // 参数
        $this->assign('params', $params);
        return $this->fetch();
    }

    //代扣
    public function Withhold()
    {   
        $params = input();
        $params['sub_order_id'] = $params['id'];  //子订单id
        $sub_info = Db::name('order_sub')->where('id',$params['id'])->find();
        if ($sub_info['repayment_time'] > date('Y-m-d')) {
            return DataReturn('该笔订单只能'.$sub_info['repayment_time'].'进行代扣', -1);
        }
        if ($sub_info['status'] == 1) {
            return DataReturn('订单已经扣款，请不要重复扣款', -1);
        }
        $order = Db::name('order')->where('order_no',$sub_info['out_order_no'])->find();
        $user = Db::name('user')->where('id',$order['user_id'])->find();
        if (empty($user['alipay_openid'])) {
            return DataReturn('用户信息异常', -1);
        }
        
        $data = [
            'out_trade_no' => date('YmdHis').rand(10000,99999).'w',
            'buyer_id' => $user['alipay_openid'],
            'total_amount' => $sub_info['amount'],
            'auth_no'     => $sub_info['auth_no'],
            'title'       => '订单【'.$sub_info['out_order_no'].'】信用租借第'.$sub_info['nums'].'期自动扣租，扣租时间:'.$sub_info['repayment_time'],
        ];
        $payment = PaymentService::PaymentList(['where'=>['id'=>$order['payment_id']]]);
        $pay_name = 'payment\\'.$payment[0]['payment'];
        $ret = (new $pay_name($payment[0]['config']))->Withhold($data);
        if (isset($ret['alipay_trade_pay_response']) && $ret['alipay_trade_pay_response']['code'] == '10000') { //扣款成功
            Db::name('order_sub')->where('id',$params['id'])->update([
                'pay_nums' => ($sub_info['pay_nums']+1),
                'order_no' => $ret['alipay_trade_pay_response']['out_trade_no'],
                'status'   => 1,
                'pay_time' => date('Y-m-d H:i:s'),
                'pay_type' => 2,
                'return_json' => json_encode($ret)
            ]);
            return DataReturn('手动扣款完成', 0);
        }else{
            Db::name('order_sub')->where('id',$params['id'])->update([
                'pay_nums' => ($sub_info['pay_nums']+1),
                'return_json' => json_encode($ret)
            ]);
            return DataReturn($ret['alipay_trade_pay_response']['msg'].'-'.$ret['alipay_trade_pay_response']['sub_msg'], -1);
        }
    }

    /**
     * 取消授权冻结
     */
    public function Auth()
    {
        $params = input();
        $order_id = $params['id'] ; //订单主键id
        $order = Db::name('order')->where('id',$order_id)->find();
        if (empty($order)) {
            return DataReturn('订单不存在', -1);
        }
        if ($order['status'] != 2) {
            return DataReturn('订单状态错误', -1);
        }
        $sub_order = Db::name('order_sub')->where('out_order_no',$order['order_no'])->where('status',1)->count();
        if ($sub_order > 0) {
            return DataReturn('账单已开始扣款，不支持解除授权', -1);
        }
        //进行授权解除
       
        $data = [
            'out_request_no' => date('YmdHis').rand(10000,99999).'a',
            'amount'         => Db::name('order_sub')->where('out_order_no',$order['order_no'])->where('status',0)->sum('amount'),
            'auth_no'        => $order['auth_no'],
            'title'       => '订单【'.$order['order_no'].'】内金额已经解除冻结，详情请查询账单。',
        ];
        $payment = PaymentService::PaymentList(['where'=>['id'=>$order['payment_id']]]);
        $pay_name = 'payment\\'.$payment[0]['payment'];
        $ret = (new $pay_name($payment[0]['config']))->unfreeze($data);
        if (isset($ret['alipay_fund_auth_order_unfreeze_response']) && $ret['alipay_fund_auth_order_unfreeze_response']['code'] == '10000') { //扣款成功
            //更新订单信息

            Db::name('order')->where('id',$params['id'])->update([
                'status'        => 5,
                'cancel_time'   => time(),
                'upd_time'      => time(),
            ]);
            $params['creator'] = $this->admin['id'];
            $params['creator_name'] = $this->admin['username'];
            OrderService::OrderHistoryAdd($order['id'], 5, $order['status'], '授信订单取消解冻', $params['creator'], $params['creator_name']);
            //删除子账单
            Db::name('order_sub')->where('out_order_no',$order['order_no'])->delete();
            return DataReturn('订单授信解冻成功', 0);
        }else{
            return DataReturn($ret['alipay_fund_auth_order_unfreeze_response']['msg'].'-'.$ret['alipay_fund_auth_order_unfreeze_response']['sub_msg'], -1);
        }
    }

}
?>