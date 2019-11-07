<?php
namespace app\admin\controller;

use app\service\OrderService;
use app\service\PaymentService;
use app\service\ExpressService;
use think\Db;
use think\Controller;

/**
 * 订单管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Withhold extends Controller
{
	public function deduction()
	{	
		$limit = input('limit') ?? 10;
        trace('执行扣款开始：'.date('Y-m-d H:i:s'));
        $list = Db::name('order_sub')->where('repayment_time',date('Y-m-d'))->where('status',0)->where('nums','<>',1)->limit($limit)->select(); //过滤首次还租必须手动
        if (!empty($list)) {
            trace('获取扣款条数：'.count($list));
            foreach ($list as $k => $v) {
                trace('子订单id'.$list[$k]['id'].'开始扣款');
                $is_first = Db::name('order_sub')->where('out_order_no',$list[$k]['out_order_no'])->where('nums',1)->where('status',1)->find(); //查看第一期是否还款了
                if (!empty($is_first)) {
                    $res = self::Withhold($list[$k]['id']);
                    trace('子订单id'.$list[$k]['id'].'扣款返回：'. json_encode($res));
                }else{
                    trace('子订单id'.$list[$k]['id'].'第一还未开始扣款，不进行自动扣款操作');
                }
            }
            //unset($list);
            trace('处理任务完成'.date('Y-m-d H:i:s'));
            return '处理条数:'.count($list);
        }else{
            trace('当前没有扣租订单：'.date('Y-m-d H:i:s'));
            return '暂无订单处理';
        }
	}

	//发起扣款
	static function Withhold($id)
    {   
    	$params['id'] = $id;
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
                'pay_type' => 1, //自动扣款
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
}