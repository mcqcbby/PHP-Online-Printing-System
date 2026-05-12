<?php
require_once __DIR__ . '/functions.php';
global $epay_key;

$params = array_merge($_GET, $_POST);
log_epay('收到异步回调', $params);

if (empty($params)) {
    log_epay('回调参数为空');
    echo 'fail';
    exit;
}

if (!epay_verify($params, $epay_key)) {
    log_epay('签名验证失败', ['calc_sign' => epay_sign($params, $epay_key), 'got_sign' => $params['sign'] ?? '']);
    echo 'fail';
    exit;
}

$out_trade_no = $params['out_trade_no'] ?? '';
$trade_no = $params['trade_no'] ?? '';
$trade_status = $params['trade_status'] ?? ($params['status'] ?? '');
$money = isset($params['money']) ? floatval($params['money']) : floatval($params['total_amount'] ?? 0);

if ($out_trade_no === '') {
    log_epay('缺少 out_trade_no');
    echo 'fail';
    exit;
}

if ($trade_status !== 'TRADE_SUCCESS' && $trade_status !== 'TRADE_FINISHED') {
    log_epay('支付状态不是成功', ['trade_status' => $trade_status]);
    echo 'fail';
    exit;
}

$mysqli = db();
$stmt = $mysqli->prepare("SELECT * FROM print_orders WHERE out_trade_no = ? LIMIT 1");
$stmt->bind_param('s', $out_trade_no);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
$stmt->close();

if (!$order) {
    $mysqli->close();
    log_epay('订单不存在', ['out_trade_no' => $out_trade_no]);
    echo 'fail';
    exit;
}

if (abs(floatval($order['money']) - $money) > 0.01) {
    $mysqli->close();
    log_epay('金额校验失败', ['order_money' => $order['money'], 'callback_money' => $money]);
    echo 'fail';
    exit;
}

if ($order['status'] !== 'paid') {
    $order['trade_no'] = $trade_no;
    $order['status'] = 'paid';
    $order['paid_at'] = date('Y-m-d H:i:s');
    $final_path = finalize_order_files($order);

    $stmt = $mysqli->prepare("UPDATE print_orders SET trade_no=?, status='paid', paid_at=NOW(), final_file_path=? WHERE out_trade_no=?");
    $stmt->bind_param('sss', $trade_no, $final_path, $out_trade_no);
    if (!$stmt->execute()) {
        log_epay('数据库更新失败', ['error' => $stmt->error]);
        $stmt->close();
        $mysqli->close();
        echo 'fail';
        exit;
    }
    $stmt->close();
}

$mysqli->close();
log_epay('订单更新成功', ['out_trade_no' => $out_trade_no, 'trade_no' => $trade_no]);
echo 'success';
