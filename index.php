<?php
require_once __DIR__ . '/functions.php';
global $price_rules;
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>在线打印下单</title>
<style>
body{margin:0;background:#f6f7fb;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,"Microsoft YaHei",sans-serif;color:#1f2937}.wrap{max-width:760px;margin:0 auto;padding:22px}.card{background:#fff;border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,.06);padding:22px}.top{text-align:center;margin-bottom:18px}.top h1{font-size:26px;margin:0 0 8px}.top p{color:#6b7280;margin:0}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.full{grid-column:1/-1}label{font-weight:700;font-size:14px;margin-bottom:8px;display:block}input,select,textarea{width:100%;box-sizing:border-box;border:1px solid #d1d5db;border-radius:12px;padding:12px;font-size:15px;background:#fff}textarea{min-height:84px;resize:vertical}.price{background:#f3f4f6;border-radius:14px;padding:14px;margin-top:10px}.btn{border:0;border-radius:14px;background:#111827;color:#fff;font-weight:800;font-size:16px;padding:14px 18px;width:100%;cursor:pointer}.btn:hover{opacity:.92}.hint{font-size:13px;color:#6b7280;margin-top:8px}.money{font-size:22px;font-weight:900;color:#dc2626}.notice{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:12px;border-radius:14px;margin-bottom:14px;font-size:14px}@media(max-width:640px){.grid{grid-template-columns:1fr}.wrap{padding:14px}}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <h1>在线打印下单</h1>
    <p>上传文件，填写信息，支付成功后店主会尽快打印</p>
  </div>
  <div class="notice">请确认文件、页数、份数无误后再下单。支付成功后文件会自动进入打印队列。</div>
  <div class="card">
    <form action="submit.php" method="post" enctype="multipart/form-data" id="orderForm">
      <div class="grid">
        <div>
          <label>姓名</label>
          <input name="name" required placeholder="例如：张三">
        </div>
        <div>
          <label>手机号</label>
          <input name="phone" required placeholder="用于联系你">
        </div>
        <div class="full">
          <label>地址 / 宿舍 / 班级</label>
          <input name="address" required placeholder="例如：3栋505 / 教学楼门口">
        </div>
        <div class="full">
          <label>上传文件</label>
          <input type="file" name="print_file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip,.rar">
          <div class="hint">支持 PDF、Word、Excel、PPT、图片、压缩包等，最大 <?php echo h($max_upload_mb); ?>MB。</div>
        </div>
        <div>
          <label>打印类型</label>
          <select name="print_type_key" id="printType">
            <?php foreach ($price_rules as $key => $rule): ?>
              <option value="<?php echo h($key); ?>" data-price="<?php echo h($rule['price']); ?>"><?php echo h($rule['name']); ?> - <?php echo h($rule['price']); ?>元/张</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>张数 / 页数数量</label>
          <input type="number" min="1" value="1" name="page_count" id="pageCount" required>
        </div>
        <div>
          <label>份数</label>
          <input type="number" min="1" value="1" name="copies" id="copies" required>
        </div>
        <div>
          <label>打印页码范围</label>
          <input name="print_pages" placeholder="例如：1-5，全部可填 全部">
        </div>
        <div class="full">
          <label>备注</label>
          <textarea name="remark" placeholder="例如：双面、装订、彩印第几页、加急等"></textarea>
        </div>
        <div class="full price">
          <div>预估金额：<span class="money" id="moneyText">0.35</span> 元</div>
          <div class="hint">金额 = 单价 × 张数/页数数量 × 份数。特殊需求请在备注写清楚。</div>
        </div>
        <div class="full">
          <button class="btn" type="submit">提交订单并去支付</button>
        </div>
      </div>
    </form>
  </div>
</div>
<script>
function calc(){
  const sel=document.getElementById('printType');
  const price=parseFloat(sel.options[sel.selectedIndex].dataset.price||0);
  const pages=Math.max(1,parseInt(document.getElementById('pageCount').value||1));
  const copies=Math.max(1,parseInt(document.getElementById('copies').value||1));
  document.getElementById('moneyText').innerText=(price*pages*copies).toFixed(2);
}
document.getElementById('printType').addEventListener('change',calc);
document.getElementById('pageCount').addEventListener('input',calc);
document.getElementById('copies').addEventListener('input',calc);
calc();
</script>
</body>
</html>
