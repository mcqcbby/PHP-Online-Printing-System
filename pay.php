<?php
require_once __DIR__ . '/functions.php';
global $site_url, $epay_pid, $epay_key, $epay_gateway, $product_name;

$out_trade_no = $_GET['order'] ?? '';
$order = get_order_by_no($out_trade_no);
if (!$order) die('订单不存在');
if ($order['status'] === 'paid') die('该订单已支付');

$params = [
    'pid' => $epay_pid,
    'type' => 'alipay',
    'out_trade_no' => $order['out_trade_no'],
    'notify_url' => $site_url . '/notify.php',
    'return_url' => $site_url . '/return.php',
    'name' => $product_name . '-' . $order['out_trade_no'],
    'money' => number_format((float)$order['money'], 2, '.', ''),
    'sitename' => '在线打印',
];
$params['sign'] = epay_sign($params, $epay_key);
$params['sign_type'] = 'MD5';

$query = http_build_query($params);
header('Location: ' . $epay_gateway . '?' . $query);
exit;
