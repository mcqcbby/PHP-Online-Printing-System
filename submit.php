<?php
require_once __DIR__ . '/functions.php';
global $price_rules, $tmp_upload_dir, $max_upload_mb;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('非法请求');

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$print_type_key = $_POST['print_type_key'] ?? '';
$page_count = max(1, intval($_POST['page_count'] ?? 1));
$copies = max(1, intval($_POST['copies'] ?? 1));
$print_pages = trim($_POST['print_pages'] ?? '全部');
$remark = trim($_POST['remark'] ?? '');

if ($name === '' || $phone === '' || $address === '') die('请填写完整信息');
if (!isset($price_rules[$print_type_key])) die('打印类型错误');
if (!isset($_FILES['print_file']) || $_FILES['print_file']['error'] !== UPLOAD_ERR_OK) die('文件上传失败');

$file = $_FILES['print_file'];
if ($file['size'] > $max_upload_mb * 1024 * 1024) die('文件太大，最大允许 ' . $max_upload_mb . 'MB');

// 兼容微信/手机上传：有些文件上传后原文件名可能没有后缀，这里用 MIME 自动补后缀
$origin_name = safe_filename($file['name'] ?? '');
$ext = strtolower(pathinfo($origin_name, PATHINFO_EXTENSION));

if ($ext === '') {
    $mime_ext_map = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/zip' => 'zip',
        'application/x-zip-compressed' => 'zip',
        'application/x-rar-compressed' => 'rar',
        'application/vnd.rar' => 'rar',
    ];

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
    }

    if ($mime !== '' && isset($mime_ext_map[$mime])) {
        $ext = $mime_ext_map[$mime];
    }
}

if (!in_array($ext, $allowed_ext, true)) die('不允许上传这种文件类型');

$out_trade_no = make_order_no();
$base_name = pathinfo($origin_name, PATHINFO_FILENAME);
$base_name = $base_name !== '' ? $base_name : '上传文件';
$saved_name = $out_trade_no . '_' . $base_name . '.' . $ext;
if (!is_dir($tmp_upload_dir)) mkdir($tmp_upload_dir, 0755, true);
$tmp_path = $tmp_upload_dir . '/' . $saved_name;

if (!move_uploaded_file($file['tmp_name'], $tmp_path)) die('保存文件失败，请检查目录权限');

$rule = $price_rules[$print_type_key];
$print_type = $rule['name'];
$money = round(floatval($rule['price']) * $page_count * $copies, 2);
if ($money <= 0) die('金额错误');

$mysqli = db();
$stmt = $mysqli->prepare("INSERT INTO print_orders (out_trade_no,name,phone,address,print_pages,print_type,page_count,copies,file_name,tmp_file_path,money,remark,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?, 'unpaid')");
$stmt->bind_param('ssssssisssds', $out_trade_no, $name, $phone, $address, $print_pages, $print_type, $page_count, $copies, $saved_name, $tmp_path, $money, $remark);
if (!$stmt->execute()) {
    @unlink($tmp_path);
    die('订单创建失败：' . $stmt->error);
}
$stmt->close();
$mysqli->close();

header('Location: pay.php?order=' . urlencode($out_trade_no));
exit;