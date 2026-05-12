<?php
require_once __DIR__ . '/config.php';

function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function log_epay($msg, $data = null) {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    if ($data !== null) {
        $line .= ' ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    @file_put_contents(__DIR__ . '/epay_notify.log', $line . PHP_EOL, FILE_APPEND);
}

function make_order_no() {
    return date('YmdHis') . mt_rand(1000, 9999);
}

function clean_name($name) {
    $name = trim((string)$name);
    // 不用正则，避免部分 PHP 环境 preg_replace 分隔符报错
    $bad = array('\\', '/', ':', '*', '?', '"', '<', '>', '|', "\r", "\n", "\t", ' ');
    $name = str_replace($bad, '_', $name);
    $name = trim($name, '._-');
    return $name !== '' ? $name : '未知姓名';
}

function phone_tail($phone) {
    $digits = preg_replace('/\D+/', '', $phone);
    if (strlen($digits) >= 4) return substr($digits, -4);
    return $digits ?: '0000';
}

function allowed_upload_ext($filename) {
    global $allowed_ext;
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, $allowed_ext, true);
}

function safe_filename($filename) {
    $filename = basename((string)$filename);
    // 不用正则，避免 Warning: preg_replace(): Unknown modifier '\\'
    $bad = array('\\', '/', ':', '*', '?', '"', '<', '>', '|', "\r", "\n", "\t");
    $filename = str_replace($bad, '_', $filename);
    $filename = trim($filename, '._-');
    return $filename !== '' ? $filename : ('file_' . time());
}

function epay_sign($params, $key) {
    ksort($params);
    $sign_str = '';
    foreach ($params as $k => $v) {
        if ($k === 'sign' || $k === 'sign_type' || $v === '' || $v === null) continue;
        $sign_str .= $k . '=' . $v . '&';
    }
    $sign_str = rtrim($sign_str, '&');
    return md5($sign_str . $key);
}

function epay_verify($params, $key) {
    if (!isset($params['sign'])) return false;
    return strtolower($params['sign']) === strtolower(epay_sign($params, $key));
}

function write_order_info_file($dir, $order) {
    $content = "订单号：{$order['out_trade_no']}\n";
    $content .= "易支付订单号：" . ($order['trade_no'] ?: '无') . "\n";
    $content .= "姓名：{$order['name']}\n";
    $content .= "手机：{$order['phone']}\n";
    $content .= "地址：{$order['address']}\n";
    $content .= "打印页数：{$order['print_pages']}\n";
    $content .= "打印类型：{$order['print_type']}\n";
    $content .= "份数：{$order['copies']}\n";
    $content .= "金额：{$order['money']} 元\n";
    $content .= "状态：{$order['status']}\n";
    $content .= "备注：{$order['remark']}\n";
    $content .= "下单时间：{$order['created_at']}\n";
    $content .= "支付时间：{$order['paid_at']}\n";
    @file_put_contents($dir . '/订单信息.txt', $content);
}

function finalize_order_files($order) {
    global $print_root_dir;
    $name_dir = clean_name($order['name']) . '_' . phone_tail($order['phone']);
    $final_dir = $print_root_dir . '/' . $name_dir . '/' . $order['out_trade_no'];
    if (!is_dir($final_dir)) {
        @mkdir($final_dir, 0755, true);
    }

    $src = $order['tmp_file_path'];
    $dst = $final_dir . '/' . basename($order['file_name']);

    if (is_dir($final_dir) && file_exists($src) && !file_exists($dst)) {
        if (!@rename($src, $dst)) {
            // 有些主机跨目录 rename 会失败，改用 copy + unlink 兜底
            if (@copy($src, $dst)) {
                @unlink($src);
            } else {
                log_epay('文件归档失败', ['src' => $src, 'dst' => $dst]);
                return $src;
            }
        }
    }

    $order['final_file_path'] = file_exists($dst) ? $dst : $src;
    write_order_info_file($final_dir, $order);
    return $order['final_file_path'];
}

function get_order_by_no($out_trade_no) {
    $mysqli = db();
    $stmt = $mysqli->prepare("SELECT * FROM print_orders WHERE out_trade_no = ? LIMIT 1");
    $stmt->bind_param('s', $out_trade_no);
    $stmt->execute();
    $res = $stmt->get_result();
    $order = $res->fetch_assoc();
    $stmt->close();
    $mysqli->close();
    return $order;
}