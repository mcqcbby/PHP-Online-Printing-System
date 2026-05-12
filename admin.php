<?php
require_once __DIR__ . '/functions.php';
global $admin_password;
session_start();

if (isset($_POST['password'])) {
    if ($_POST['password'] === $admin_password) $_SESSION['admin_ok'] = true;
    else $err = '密码错误';
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }

if (empty($_SESSION['admin_ok'])):
?>
<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>后台登录</title><style>body{font-family:Arial,"Microsoft YaHei";background:#f6f7fb}.box{max-width:360px;margin:90px auto;background:#fff;padding:24px;border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,.08)}input,button{width:100%;box-sizing:border-box;padding:12px;border-radius:12px;border:1px solid #ddd;margin-top:10px}button{background:#111827;color:#fff;font-weight:700}</style></head><body><div class="box"><h2>打印后台</h2><?php if(!empty($err)) echo '<p style="color:red">'.h($err).'</p>'; ?><form method="post"><input type="password" name="password" placeholder="后台密码"><button>登录</button></form></div></body></html>
<?php exit; endif;

$mysqli = db();
if (isset($_GET['printed'])) {
    $no = $_GET['printed'];
    $stmt = $mysqli->prepare("UPDATE print_orders SET status='printed' WHERE out_trade_no=?");
    $stmt->bind_param('s', $no);
    $stmt->execute();
    $stmt->close();
    header('Location: admin.php'); exit;
}
$status = $_GET['status'] ?? '';
if ($status) {
    $stmt = $mysqli->prepare("SELECT * FROM print_orders WHERE status=? ORDER BY id DESC LIMIT 200");
    $stmt->bind_param('s', $status);
    $stmt->execute();
    $orders = $stmt->get_result();
} else {
    $orders = $mysqli->query("SELECT * FROM print_orders ORDER BY id DESC LIMIT 200");
}
?>
<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>打印订单后台</title><style>body{font-family:Arial,"Microsoft YaHei";background:#f6f7fb;margin:0;color:#111827}.wrap{max-width:1200px;margin:0 auto;padding:22px}.top{display:flex;justify-content:space-between;align-items:center}.card{background:#fff;border-radius:18px;padding:18px;box-shadow:0 10px 30px rgba(0,0,0,.06);overflow:auto}table{border-collapse:collapse;width:100%;font-size:14px}th,td{border-bottom:1px solid #eee;padding:10px;text-align:left;vertical-align:top}th{background:#f9fafb}.paid{color:#16a34a;font-weight:700}.unpaid{color:#dc2626;font-weight:700}.printed{color:#2563eb;font-weight:700}.btn{display:inline-block;padding:7px 10px;border-radius:9px;background:#111827;color:#fff;text-decoration:none;font-size:13px}.filter a{margin-right:8px}.small{color:#6b7280;font-size:12px}</style></head><body><div class="wrap"><div class="top"><h1>打印订单后台</h1><a href="?logout=1">退出</a></div><p class="filter"><a href="admin.php">全部</a><a href="?status=paid">已支付</a><a href="?status=unpaid">未支付</a><a href="?status=printed">已打印</a></p><div class="card"><table><thead><tr><th>订单</th><th>用户</th><th>打印信息</th><th>金额</th><th>状态</th><th>文件路径</th><th>时间</th><th>操作</th></tr></thead><tbody>
<?php while($o = $orders->fetch_assoc()): ?>
<tr>
<td><b><?php echo h($o['out_trade_no']); ?></b><div class="small">易支付：<?php echo h($o['trade_no']); ?></div></td>
<td><?php echo h($o['name']); ?><br><?php echo h($o['phone']); ?><br><span class="small"><?php echo h($o['address']); ?></span></td>
<td><?php echo h($o['print_type']); ?><br>页码：<?php echo h($o['print_pages']); ?><br>数量：<?php echo h($o['page_count']); ?> × <?php echo h($o['copies']); ?><br><span class="small"><?php echo nl2br(h($o['remark'])); ?></span></td>
<td>￥<?php echo h($o['money']); ?></td>
<td class="<?php echo h($o['status']); ?>"><?php echo h($o['status']); ?></td>
<td><span class="small"><?php echo h($o['final_file_path'] ?: $o['tmp_file_path']); ?></span></td>
<td><span class="small">下单：<?php echo h($o['created_at']); ?><br>支付：<?php echo h($o['paid_at']); ?></span></td>
<td><?php if($o['status']==='paid'): ?><a class="btn" href="?printed=<?php echo urlencode($o['out_trade_no']); ?>" onclick="return confirm('确认已打印？')">标记已打印</a><?php endif; ?></td>
</tr>
<?php endwhile; ?>
</tbody></table></div></div></body></html>
