(function (window) {
    'use strict';

    // Strings extracted from the compiled administration application. Keeping the
    // source catalogue separate makes it possible to replace the old bundle later
    // without coupling the runtime to React or webpack internals.
    var chineseSource = String.raw`
组ID
组名称
用户数量
节点数量
操作
编辑
删除
权限组管理
 添加权限组
国
系统配置
站点
站点名称
用于显示需要站点名称的地方。
请输入站点名称
站点描述
用于显示需要站点描述的地方。
请输入站点描述
站点网址
当前网站最新网址，将会在邮件等需要用于网址处体现。
请输入站点URL，末尾不要/
强制HTTPS
当站点没有使用HTTPS，CDN或反代开启强制HTTPS时需要开启。
用于显示需要LOGO的地方。
请输入LOGO URL，末尾不要/
订阅URL
用于订阅所使用，留空则为站点URL。如需多个订阅URL随机获取请使用逗号进行分割。
请输入订阅URL，末尾不要/。逗号分割支持多域名
订阅路径
用于订阅所使用，留空则为/api/v1/client/subscribe。如需更换不同的订阅路径请设置。
用户条款(TOS)URL
用于跳转到用户条款(TOS)
请输入用户条款URL，末尾不要/
停止新用户注册
开启后任何人都将无法进行注册。
注册试用
选择需要试用的订阅，如果没有选项请先前往订阅管理添加。
请选择试用订阅
关闭
试用时间(小时)
请输入
货币单位
仅用于展示使用，更改后系统中所有的货币单位都将发生变更。
货币符号
安全
邮箱验证
开启后将会强制要求用户进行邮箱验证。
禁止使用Gmail多别名
开启后Gmail多别名将无法注册。
安全模式
开启后除了站点URL以外的绑定本站点的域名访问都将会被403。
后台路径
后台管理路径，修改后将会改变原有的admin路径
邮箱后缀白名单
开启后在名单中的邮箱后缀才允许进行注册。
白名单后缀
请使用逗号进行分割，如：qq.com,gmail.com。
请输入后缀域名，逗号分割 如：qq.com,gmail.com
防机器人
开启后将会使用Google reCAPTCHA防止机器人。
密钥
在Google reCAPTCHA申请的密钥。
网站密钥
在Google reCAPTCH申请的网站密钥。
IP注册限制
开启后如果IP注册账户达到规则要求将会被限制注册，请注意IP判断可能因为CDN或前置代理导致问题。
次数
达到注册次数后开启惩罚。
惩罚时间(分钟)
需要等待惩罚时间过后才可以再次注册。
防爆破限制
开启后如果该账户尝试登陆失败次数过多将会被限制。
达到失败次数后开启惩罚。
需要等待惩罚时间过后才可以再次登陆。
订阅
允许用户更改订阅
开启后用户将会可以对订阅计划进行变更。
月流量重置方式
全局流量重置方式，默认每月1号。可以在订阅管理为订阅单独设置。
请选择订阅重置方式
每月1号
按月重置
不重置
每年1月1日
按年重置
开启折抵方案
开启后用户更换订阅将会由系统对原有订阅进行折抵，方案参考文档。
允许提前开启流量周期
开启后用户流量用尽时可以选择扣除订阅时长为代价重置流量，按月重置时扣除本周期剩余订阅时长，每月1号重置时扣除整月时间30天。
当订阅新购时触发事件
新购订阅完成时将触发该任务。
请选择事件
不执行任何动作
重置用户流量
当订阅续费时触发事件
续费订阅完成时将触发该任务。
当订阅变更时触发事件
变更订阅完成时将触发该任务。
在订阅中展示订阅信息
开启后将会在用户订阅节点时输出订阅信息。
订阅链接生效模式
用户获取订阅链接后的有效期。
请选择
永久有效
一次性有效
限时有效
订阅链接有效时间(分钟)
订阅链接获取后经过该时间将失效。
充值
充值奖励
充值一定金额可以获得的奖励。
请输入 充值金额:奖励金额,逗号分割\n如 50:18,100:38, 200:88
工单
工单设置
请选择工单的状态。
完全开放工单
仅限有付费订单用户
完全禁止工单
邀请&佣金
开启强制邀请
开启后只有被邀请的用户才可以进行注册。
邀请佣金百分比
默认全局的佣金分配比例，你可以在用户管理单独配置单个比例。
用户可创建邀请码上限
邀请码永不失效
开启后邀请码被使用后将不会失效，否则使用过后即失效。
佣金仅首次发放
开启后被邀请人首次支付时才会产生佣金，可以在用户管理对用户进行单独配置。
佣金自动确认
开启后佣金将会在订单完成3日后自动进行确认。
提现单申请门槛(元)
小于门槛金额的提现单将不会被提交。
提现方式
可以支持的提现方式。
请输入后缀域名，逗号分割 如：支付宝,USDT,贝宝
关闭提现
关闭后将禁止用户申请提现，且邀请佣金将会直接进入用户余额。
三级分销
开启后将佣金将按照设置的3成比例进行分成，三成比例合计请不要>100%。
一级邀请人比例
请输入比例如：50
二级邀请人比例
请输入比例如：30
三级邀请人比例
请输入比例如：20
个性化
如果你采用前后分离的方式部署V2board管理端，那么本页配置将不会生效。了解
前后分离
边栏风格
亮
暗
头部风格
主题色
默认
黑色
暗蓝色
奶绿色
背景
将会在后台登录页面进行展示。
节点
节点对接API地址
v2node节点一键对接专用地址。
通讯密钥
V2board与节点通讯的密钥，以便数据不会被他人获取。
节点拉取动作轮询间隔
节点从面板获取数据的间隔频率。
秒
节点推送动作轮询间隔
节点推送数据到面板的间隔频率。
节点用户流量上报最低阈值
每次推送动作仅累计使用流量高于阈值的用户信息会被上报，未上报流量会累计
节点用户设备数统计最低阈值
每次推送动作仅上报流量高于阈值的在线设备IP地址会被节点统计
全局设备数限制采用宽松模式
开启后同一IP地址使用多个节点只统计为一个设备
邮件
如果你更改了本页配置，需要对队列服务进行重启。另外本页配置优先级高于.env中邮件配置。
SMTP服务器地址
由邮件服务商提供的服务地址
SMTP服务端口
常见的端口有25, 465, 587
SMTP加密方式
465端口加密方式一般为SSL，587端口加密方式一般为TLS
SMTP账号
由邮件服务商提供的账号
SMTP密码
由邮件服务商提供的密码
发件地址
由邮件服务商提供的发件地址
邮件模板
你可以在文档查看如何自定义邮件模板
发送测试邮件
邮件将会发送到当前登陆用户邮箱
机器人Token
请输入由Botfather提供的token。
设置Webhook
对机器人进行Webhook设置，不设置将无法收到Telegram通知。
一键设置
开启机器人通知
开启后bot将会对绑定了telegram的管理员和用户进行基础通知。
群组地址
填写后将会在用户端展示，或者被用于需要的地方。
用于自有客户端(APP)的版本管理及更新
Windows端版本号及下载地址
macOS端版本号及下载地址
Android端版本号及下载地址
用户管理
TA的流量记录
输入内容回复工单...
路径
编辑节点
新建节点
节点名称
请输入节点名称
倍率
请输入节点倍率
节点标签
输入后回车添加标签
权限组
添加权限组
请选择权限组
节点地址
地址或IP
连接端口
用户连接端口
服务端口
服务端开放端口
加密算法
混淆
无
父节点
https://docs.v2board.com/use/node.html#父节点与子节点关系
更多解答
路由组
请选择路由组
取消
提交
显示
标题
创建时间
公告管理
 添加公告
编辑公告
新建公告
请输入公告标题
公告内容
请输入公告内容
公告标签
图片URL
请输入图片URL
队列监控
总览
当前作业量
近一小时处理量
7日内报错数量
状态
运行中
未启动
当前作业详情
队列名称
订单队列
邮件队列
邮件群发队列
Telegram消息队列
统计队列
流量消费队列
作业量
任务量
占用时间
今天
此刻
返回今天
确定
选择时间
选择日期
选择周
清除
月
年
上个月 (翻页上键)
下个月 (翻页下键)
选择月份
选择年份
选择年代
YYYY年
D日
YYYY年M月D日
YYYY年M月D日 HH时mm分ss秒
上一年 (Control键加左方向键)
下一年 (Control键加右方向键)
上一年代
下一年代
上一世纪
下一世纪
条/页
跳至
页
上一页
下一页
向前 5 页
向后 5 页
向前 3 页
向后 3 页
启用
券名称
类型
金额
比例
券码
复制成功
剩余次数
无限
有效期
警告
确定要删除该条项目吗？
优惠券管理
 添加优惠券
编辑优惠券
新建优惠券
名称
请输入优惠券名称
自定义优惠券码
自定义优惠券码(留空随机生成)
优惠信息
按金额优惠
按比例优惠
请输入值
优惠券有效期
最大使用次数
限制最大使用次数，用完则无法使用(为空则不限制)
每个用户可使用次数
限制每个用户可使用次数(为空则不限制)
指定订阅
限制指定订阅可以使用优惠(为空则不限制)
指定周期
限制指定周期可以使用优惠(为空则不限制)
生成数量
输入数量批量生成
创建用户
生成
邮箱
账号（批量生成请留空）
域
密码
留空则密码与邮箱相同
到期时间
请选择用户到期日期，为空则不限制到期时间
订阅计划
请选择用户订阅计划
如果为批量生成请输入生成数量
低
中
高
主题
工单级别
工单状态
已回复
待回复
已关闭
最后回复
查看
工单管理
已开启
输入邮箱搜索
登录到管理中心
登入
忘记密码
在站点目录下执行命令找回密码
php artisan reset:password 管理员邮箱
我知道了
日期
上行
下行
流量记录
提醒
确定要进行封禁吗？
确定要进行删除吗？
重置安全信息
确定要重置
的安全信息吗？
删除用户
确定要删除
的用户信息吗？
最后在线
从未在线
封禁
正常
权限组
已用(G)
流量(G)
设备数
长期有效
余额
佣金
加入时间
 编辑
 分配订单
 复制订阅URL
 重置UUID及订阅URL
 TA的订单
 TA的邀请
 TA的流量记录
 删除用户
操作
Tips：可以使用过滤器过滤后再使用操作对过滤的用户进行操作。
模糊
用户ID
无订阅
流量
账号状态
邀请人邮箱
邀请人ID
备注
管理员
是
否
 过滤器
 导出CSV
 发送邮件
 批量封禁
 批量删除
发送中
编辑支付方式
添加支付方式
保存
添加
显示名称
用于前端显示使用
图标URL(选填)
用于前端显示使用(https://x.com/icon.svg)
自定义通知域名(选填)
网关的通知将会发送到该域名(https://x.com)
百分比手续费(选填)
在订单金额基础上附加手续费
固定手续费(选填)
接口文件
支付接口
通知地址
支付网关将会把数据通知到本地址，请通过防火墙放行本地址。
支付配置
 添加支付方式
值不能为空
过滤器
欲检索内容不能为空
条件
字段名
欲检索内容
请选择值
值
 添加条件
重置
检索
导出中
已加入队列执行
重置成功
删除成功
编辑订阅
新建订阅
套餐名称
请输入套餐名称
套餐描述
请输入套餐描述，支持HTML
售价设置
将金额留空则不会进行出售
月付
季付
半年
年付
两年付
三年付
一次性
重置包
套餐流量
请输入套餐流量
设备数限制
留空则不限制
流量重置方式
跟随系统设置
最大容纳用户量
限速
勾选后变更的流量、限速、权限组将应用到该套餐下的用户
强制更新到用户
排序
销售状态
续费
在订阅停止销售时，已购用户是否可以续费
统计
半年付
 删除
订阅管理
 添加订阅
保存成功
编辑知识
新增知识
请输入知识标题
分类
请输入分类，分类将会自动归集
语言
请选择知识语言
内容
文章ID
更新时间
知识库管理
新增
订单分配
用户邮箱
请输入用户邮箱
请选择订阅
请选择周期
支付金额
请输入需要支付的金额
发送邮件
收件人
过滤用户
全部用户
请输入邮件主题
发送内容
请输入邮件内容
请选择时间
请选择日期
开始日期
结束日期
确 定
筛选
全选当页
反选当页
展开行
关闭行
知道了
请输入搜索内容
项
文件上传中
删除文件
上传错误
预览文件
下载文件
暂无数据
图标
复制
展开
返回
您确定要清空所有内容吗？
清空
加粗
斜体
下划线
删除线
无序列表
有序列表
引用
换行
行内代码
代码块
表格
图片
链接
撤销
重做
全屏
退出全屏
仅显示编辑器
仅显示预览
显示编辑器与预览
按下 Tab 键时实际的输入
制表符
空格
# 订单号
127.0.0.1(单一匹配)\n10.0.0.0/8(范围匹配)\ngeoip:cn(预定义列表匹配)
DNS服务器
DNS服务器地址
DNS服务器表
DNS申请
DNS解析提供商Provider
ECH Config (客户端配置)
ECH Key (服务端私钥)
ECH Server Name (伪装域名/外层SNI)
HTTP伪装
HTTP申请
HYSTERIA版本
REALITY必填，与后端保持一致
REALITY目标地址,默认使用SNI
REALITY目标端口,默认443
TLS指纹默认Chrome
XTLS流控算法
Xray出站配置
example.com(关键字匹配)\ndomain:example.com(子域名匹配)\ngeosite:netflix(预定义域名列表)
webhook 设置成功
✓ Cloudflare 托管 ECH，密钥由 Cloudflare 自动管理，客户端从 DNS 自动获取配置，服务端无需配置
一次性套餐输入0
一键安装指令
上月佣金支出
上月收入
上行带宽
下行带宽
不使用请留空
不支持
专享折扣比例
主题设置
主题配置
书写格式CF_DNS_API_TOKEN=xxxxxxx如有多条使用逗号,分隔
书写格式cloudflare
人数
今日收入
今日用户流量排行
今日节点流量排行
仪表盘
优惠金额
传输协议
传输协议配置格式有误
余额支付
佣金状态
佣金状态
佣金金额
使用的流量将乘以倍率进行扣除
使用自签名证书需要允许不安全，用户才可以连接
保存排序
信任的XFF头部(获取真实IP)
倍率
允许不安全
允许不安全
兑换订阅套餐
创建组
创建路由
动作
加密方式
匹配
匹配值
匹配数量
协议详细配置
协议过滤器
卡密
参考
发信加密方式:
发信服务器:
发信用户名:
发信端口:
发放中
发送失败
发送成功
变更
周期
回调单号
在线人数
地址
地址或IP默认为0.0.0.0
域名
域名列表，此列表包含的域名，将优先使用此服务器进行查询。一行一条
域名过滤器
填写参考
增加套餐流量
增加订阅时长
增加账户余额
 复制
 天
天
失败原因:
套餐
如果你采用前后分离的方式部署V2board，那么主题配置将不会生效。了解
如需修改密码请输入
安全性
实时注册
实际发放
客户端启用 0-RTT
已发放
已取消
已完成
已折抵
已支付
已用上行
已用下行
已驳回
常见头部:X-Forwarded-For CF-Connecting-IP X-Real-IP
开启
开通中
当前主题
当前队列服务运行异常，可能会导致业务无法使用。
当节点地址与证书不一致时用于证书验证
待支付
待确认
待答复
循环返利
必填
标记为
标记为[已支付]后将会由系统进行开通后并完成
标记为[有效]后将会由系统处理后发放到用户并完成
折抵金额
拖动排序
拥塞控制算法
指定DNS服务器进行解析
指定出站服务器(IP目标)
指定出站服务器(域名目标)
指标
推广佣金
推荐返利比例
推荐返利类型
搜索
支持
收信地址:
数值
数据包中继模式
新建礼品卡
新购
时长
 无人使用或服务端上报异常
无效
无规则时默认
无证书(关闭TLS)
昨日用户流量排行
昨日节点流量排行
是否员工
是否支持TLS
是否管理员
显隐
最长允许时间
有
有效
服务器
服务器名称指示(sni)
服务器组
服务端发送带宽,留空或填0使用BBR
服务端接收带宽,留空或填0使用BBR
未支付
 未运行
本月收入
本月新增用户
 条工单等待处理
 条规则
查看TA邀请的人
流量包
流量重置包
混淆密码obfsParam
混淆密码obfs_password
混淆方式obfs
添加礼品卡
 添加订单
 添加路由
根据服务端上报频率而定
激活主题
用户
留空使用默认值100-111-1111.75-0-111.50-0-3333
留空在/etc/v2node/目录自动生成
留空自动生成
留空自动生成，需抗量子加密请自行替换
登出
监听地址
礼品卡有效期
礼品卡管理
礼品卡类型
禁止访问(IP目标)
禁止访问(协议)
禁止访问(域名目标)
禁止访问(端口目标)
禁用SNI
立即处理
端口
 笔佣金等待确认
简体中文
系统设置
组名
续费
编辑TLS配置
编辑加密配置
编辑协议配置
编辑填充方案
编辑安全性配置
编辑排序
编辑礼品卡
编辑组
编辑路由
编辑配置
自定义 SNI
自定义礼品卡卡密
自定义礼品卡卡密(留空随机生成)
自定义默认出站
自签名
节点
节点ID
节点协议
节点排序还没有保存，是否离开
节点管理
订单信息
订单号
订单周期
订单状态
订单状态
订单管理
设置
设置后该用户购买任何订阅将始终享受该折扣
证书公钥文件地址Cert File Path
证书模式Cert Mode
证书私钥文件地址Key File Path
请在这里记录..
请求失败
请输入DNS服务器地址
请输入专享折扣比例
请输入备注
请输入推荐返利比例(为空则跟随站点设置返利比例)
请输入流量
请输入用于解析的DNS服务器地址
请输入礼品卡名称
请输入组名
请输入连接地址
请输入邀请人邮箱
请输入邮箱
请选择动作
财务
账户状态
路由器
路由管理
输入任意关键字搜索
 运行正常
连接地址
退回金额
选择 ECH 模式
选择XTLS流控算法
选择传输协议
选择加密方式
邀请人
配置
重置套餐流量
非NAT同连接端口
首次返利
`.slice(1, -1).split('\n').map(function (value) { return value.replace(/\\n/g, '\n'); });

    // English strings are already present in widgets/editor controls. They are
    // catalogued too so switching away from English can translate the whole UI.
    var englishSource = String.raw`
/api/v1/client/subscribe
0000000000:xxxxxxxxx_xxxxxxxxxxxxxxx
Add cursor above
Add cursor above (skip current)
Add cursor below
Add cursor below (skip current)
Add new line after the current line
Add new line before the current line
Align cursors
All
Android
Auto Indent
Backspace
Block indent
Block outdent
CNY
Cancel
Center selection
Change language mode...
Copy
Copy lines down
Copy lines up
Cut
Cut or delete
Delete
Duplicate selection
Editor
Expand to line
Expand to matching
Find
Find all
Find next
Find previous
Fold To Level
Fold all
Fold all comments
Fold other
Go line down
Go line up
Go to end
Go to left
Go to line end
Go to line start
Go to line...
Go to next error
Go to page down
Go to page up
Go to previous error
Go to right
Go to start
Go to word left
Go to word right
Host
ID
Indent
Insert string
Insert text
Invert selection
Join lines
Jump to matching
LOGO
Modify number down
Modify number up
Move lines down
Move lines up
No Data
OK
Open command palette
Outdent
Overwrite
Page down
Page up
Pass keys to browser
Paste
Please select
Preview
Redo
Remove line
Remove to line end
Remove to line end hard
Remove to line start
Remove to line start hard
Remove word left
Remove word right
Replace
Replay macro
Scroll down
Scroll up
Select all
Select date
Select down
Select left
Select line end
Select line start
Select more after
Select more before
Select next after
Select next before
Select or find next
Select or find previous
Select page down
Select page up
Select right
Select time
Select to end
Select to line end
Select to line start
Select to matching
Select to start
Select up
Select word left
Select word right
Show settings menu
Single selection
Sort lines
Split into lines
Split line
TOKEN
To lowercase
To uppercase
Toggle block comment
Toggle comment
Toggle fold widget
Toggle parent fold widget
Toggle recording
Transpose letters
UUID
Undo
Unfold all
V2Board
Windows
admin
https://t.me/xxxxxx
https://xxxx.com/xxx.apk
https://xxxx.com/xxx.dmg
https://xxxx.com/xxx.exe
https://xxxxx.com/wallpaper.png
macOS
Loading...
New
Enabled
Disabled
HTTP
HTTPS
TCP
UDP
TLS
WebSocket
gRPC
Hysteria
TUIC
Shadowsocks
Trojan
VLESS
VMess
AnyTLS
V2Node
Telegram
Email
Password
Username
Server
Port
Protocol
Status
Action
Search
Save
Submit
Reset
Close
Edit
Create
Update
Download
Upload
Language
`.slice(1, -1).split('\n');

    var sourceKeys = chineseSource.concat(englishSource);
    var unique = Object.create(null);
    sourceKeys = sourceKeys.filter(function (key) {
        if (Object.prototype.hasOwnProperty.call(unique, key)) {
            return false;
        }
        unique[key] = true;
        return true;
    });

    window.V2BoardAdminI18nDictionaries = window.V2BoardAdminI18nDictionaries || {};
    window.V2BoardAdminI18nSourceKeys = Object.freeze(sourceKeys.slice());
    window.V2BoardAdminI18nCatalog = Object.freeze(unique);
    window.V2BoardAdminI18nRegister = function (locale, translations) {
        var dictionary = Object.create(null);
        sourceKeys.forEach(function (source) {
            var translated = Object.prototype.hasOwnProperty.call(translations || {}, source)
                ? translations[source]
                : source;
            dictionary[source] = typeof translated === 'string' ? translated : source;
        });
        // React frequently renders surrounding spaces as separate text-node
        // whitespace. Add non-enumerable trimmed aliases so sources such as
        // "通知地址 " still match without changing catalogue parity counts.
        sourceKeys.forEach(function (source) {
            var alias = source.trim();
            if (alias === source || Object.prototype.hasOwnProperty.call(dictionary, alias)) {
                return;
            }
            Object.defineProperty(dictionary, alias, {
                configurable: false,
                enumerable: false,
                value: dictionary[source].trim(),
                writable: false
            });
        });
        window.V2BoardAdminI18nDictionaries[locale] = Object.freeze(dictionary);
        return dictionary;
    };
    window.V2BoardAdminI18nRegisterChinese = function (locale, values, englishTranslations) {
        if (!Array.isArray(values) || values.length !== chineseSource.length) {
            throw new Error('[V2Board admin i18n] ' + locale + ' must provide all 822 Chinese translations.');
        }
        var translations = Object.create(null);
        chineseSource.forEach(function (source, index) {
            translations[source] = values[index];
        });
        Object.keys(englishTranslations || {}).forEach(function (source) {
            translations[source] = englishTranslations[source];
        });
        return window.V2BoardAdminI18nRegister(locale, translations);
    };

    if (chineseSource.length !== 822 || englishSource.length !== 177 || sourceKeys.length !== 999) {
        window.console && window.console.warn && window.console.warn(
            '[V2Board admin i18n] Unexpected catalogue size:',
            chineseSource.length,
            englishSource.length,
            sourceKeys.length
        );
    }
})(window);
