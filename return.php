<?php
require_once __DIR__ . '/functions.php';

global $epay_key;

$params = $_GET + $_POST;
$ok = epay_verify($params, $epay_key);

$out_trade_no = $params['out_trade_no'] ?? '';
$trade_no = $params['trade_no'] ?? '';
$trade_status = $params['trade_status'] ?? '';
$money = floatval($params['money'] ?? 0);

$message = '支付结果无法确认，请联系店主核对订单。';
$success = false;

if ($ok && $out_trade_no && ($trade_status === 'TRADE_SUCCESS' || $trade_status === 'TRADE_FINISHED')) {
    $mysqli = db();

    $stmt = $mysqli->prepare("SELECT * FROM print_orders WHERE out_trade_no = ? LIMIT 1");
    $stmt->bind_param('s', $out_trade_no);
    $stmt->execute();
    $res = $stmt->get_result();
    $order = $res->fetch_assoc();
    $stmt->close();

    if ($order) {
        if (abs(floatval($order['money']) - $money) <= 0.01) {
            if ($order['status'] !== 'paid') {
                $order['trade_no'] = $trade_no;
                $order['status'] = 'paid';
                $order['paid_at'] = date('Y-m-d H:i:s');

                $final_path = finalize_order_files($order);

                $stmt = $mysqli->prepare("UPDATE print_orders SET trade_no=?, status='paid', paid_at=NOW(), final_file_path=? WHERE out_trade_no=?");
                $stmt->bind_param('sss', $trade_no, $final_path, $out_trade_no);
                $stmt->execute();
                $stmt->close();
            }

            $success = true;
            $message = '支付成功，订单已提交，店主会尽快处理打印。';
        } else {
            $message = '金额校验失败，请联系店主处理。';
        }
    } else {
        $message = '没有找到对应订单，请联系店主处理。';
    }

    $mysqli->close();
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>支付结果</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: Arial, "Microsoft YaHei", sans-serif;
            background: linear-gradient(135deg, #eef5ff, #f7f8fb);
            min-height: 100vh;
            padding: 24px;
        }
        .box {
            max-width: 560px;
            margin: 70px auto;
            background: #ffffff;
            border-radius: 18px;
            padding: 32px 24px;
            box-shadow: 0 12px 35px rgba(0,0,0,.08);
            text-align: center;
        }
        .icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: <?php echo $success ? '#16a34a' : '#dc2626'; ?>;
            color: #fff;
            font-size: 42px;
            line-height: 72px;
            font-weight: bold;
        }
        h1 {
            margin: 0 0 12px;
            color: <?php echo $success ? '#16a34a' : '#dc2626'; ?>;
            font-size: 28px;
        }
        .desc {
            color: #555;
            line-height: 1.8;
            margin-bottom: 20px;
        }
        .order {
            background: #f1f5f9;
            border-radius: 14px;
            padding: 16px;
            text-align: left;
            word-break: break-all;
            margin-top: 18px;
        }
        .order p {
            margin: 8px 0;
            color: #333;
            font-size: 15px;
        }
        .btns {
            margin-top: 24px;
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        a {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
        }
        .home {
            background: #2563eb;
            color: #fff;
        }
        .admin {
            background: #f1f5f9;
            color: #111827;
        }
    </style>
</head>
<body>
<div class="box">
    <div class="icon"><?php echo $success ? '✓' : '!'; ?></div>
    <h1><?php echo $success ? '支付成功' : '支付异常'; ?></h1>

    <div class="desc">
        <?php echo htmlspecialchars($message); ?>
    </div>

    <div class="order">
        <p><strong>商户订单号：</strong><?php echo htmlspecialchars($out_trade_no); ?></p>
        <p><strong>易支付订单号：</strong><?php echo htmlspecialchars($trade_no); ?></p>
        <p><strong>支付金额：</strong><?php echo htmlspecialchars($money); ?> 元</p>
        <p><strong>支付状态：</strong><?php echo htmlspecialchars($trade_status); ?></p>
    </div>

    <div class="btns">
        <a class="home" href="index.php">返回首页</a>
        <a class="admin" href="admin.php">查看订单</a>
    </div>
</div>
</body>
</html>