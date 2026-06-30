在线打印 PHP 系统使用说明

一、上传前先做这些
1. 在宝塔或虚拟主机后台创建 MySQL 数据库。
2. 导入 sql.sql。
3. 修改 config.php：
   - 数据库账号密码
   - site_url 改成你的域名，例如 https://Baidu.com
   - epay_pid、epay_key、epay_gateway 改成你的易支付信息
   - admin_password 改成你的后台密码

二、上传方法
把压缩包里面所有文件上传到网站根目录。

三、目录权限
确保 uploads_tmp 和 打印 这两个目录有写入权限。
宝塔一般设置为 755，不行就临时试 777。

四、访问地址
下单页面：你的域名/index.php
后台页面：你的域名/admin.php
异步回调：你的域名/notify.php
支付返回：你的域名/return.php

五、支付成功后的文件位置
打印/姓名_手机号后4位/订单号/
里面会有用户上传文件和 订单信息.txt。

六、易支付注意
大多数易支付的签名方式是 MD5，参数为 pid、type、out_trade_no、notify_url、return_url、name、money、sitename。
如果你的易支付平台参数名不一样，只需要改 pay.php、notify.php。
