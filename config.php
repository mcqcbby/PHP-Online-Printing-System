<?php
// =============================
// 在线打印系统配置文件
// 上传到网站根目录后，先修改这里
// =============================

// 数据库配置
$db_host = 'localhost';
$db_user = '数据库账号';
$db_pass = '数据库密码';
$db_name = '数据库名';
$db_port = 3306;

// 网站域名，不要以 / 结尾
$site_url = 'https://www.baidu.com/';

// 易支付配置：不同易支付平台字段基本类似
$epay_pid = 'ID';
$epay_key = '密钥';
$epay_gateway = '接口地址/submit.php';

// 收款名称
$product_name = '在线打印订单';

// 上传与归档目录
$tmp_upload_dir = __DIR__ . '/uploads_tmp';
$print_root_dir = __DIR__ . '/打印';

// 允许上传的文件类型
$allowed_ext = ['pdf','doc','docx','xls','xlsx','ppt','pptx','jpg','jpeg','png','zip','rar'];

// 最大上传大小：单位 MB
$max_upload_mb = 80;

// 管理后台密码：访问 /admin.php 时使用，建议改复杂一点
$admin_password = '123456';

// 价格设置：你可以按实际价格改
$price_rules = [
    'bw_single'   => ['name' => '黑白单面打印/复印', 'price' => 0.35],
    'bw_double'   => ['name' => '黑白双面打印/复印', 'price' => 0.40],
    'color_single'=> ['name' => '彩色单面打印/复印', 'price' => 0.55],
    'color_double'=> ['name' => '彩色双面打印/复印', 'price' => 0.80],
    'photo_id'    => ['name' => '证件照', 'price' => 6.00],
    'photo_6'     => ['name' => '6寸及以下相纸', 'price' => 4.00],
    'laminate'    => ['name' => '相纸塑封', 'price' => 1.50],
    'a4_coated'   => ['name' => 'A4铜版纸', 'price' => 6.00],
];

function db() {
    global $db_host, $db_user, $db_pass, $db_name, $db_port;
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    if ($mysqli->connect_error) {
        die('数据库连接失败：' . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}
