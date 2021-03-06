<?php
/**
 * Created by PhpStorm.
 * User: lihe
 * Date: 2017/6/13
 * Time: 下午6:41
 */
namespace Xt\Publisher\Providers;

use Xt\Publisher\DefaultException;

class Feiliu extends ProviderAbstract
{

    public function verifyToken($token = '', $option = [])
    {
        //md5 验证
        $data = [
            'timestamp' => $option['custom'],
            'uid'       => $option['uid'],
            'SECRETKEY' => $this->option['secret_key']
        ];
        $sign = implode('&', array_values($data));
        if ($token != md5($sign)) {
            throw new DefaultException('failed');
        }
        unset($data['SECRETKEY']);

        return [
            'uid'      => $option['uid'],
            'username' => '',
            'original' => $data
        ];
    }

    public function notify()
    {
        $request = file_get_contents("php://input");
        //删除多余的 =
        $request = trim($request, '=');
        $data = json_decode($request, true);

        if ($data['status'] !== '0') {
            throw new DefaultException('fail');
        }

        // 平台参数
        $param['amount'] = $data['amount'];                     // 总价.单位: 分
        $param['transaction'] = $data['cpOrderId'];             // 订单id
        $param['currency'] = 'CNY';                             // 货币类型

        // 第三方参数【可选,暂未使用】
        $param['reference'] = $data['cpOrderId'];               // 第三方订单ID
        $param['userId'] = $data['userId'];                     // 第三方账号ID

        // 检查签名
        $this->check_sign($data['sign']);

        //转化单位,并保留2位小数
        $param['amount'] = round($data['amount'] / 100, 2);

        return $param;
    }

    public function check_sign($sign = '')
    {
        $request = file_get_contents("php://input");
        //删除多余的 =
        $request = trim($request, '=');
        $req = json_decode($request, true);
        $req['goodsId'] = str_replace('_', '.', $req['goodsId']);
        unset($req['sign']);
        ksort($req);
        $str = '';
        foreach ($req as $k => $v) {
            $str .= "$k=$v&";
        }
        $str = trim($str, '&');
        $str .= $this->option['secret_key'];

        if (strtolower($sign) != strtolower(md5($str))) {
            throw new DefaultException('sign error');
        }
    }

    public function success()
    {
        echo json_encode(array('code' => 0, 'tips' => 'success'));
        exit;
    }
}
