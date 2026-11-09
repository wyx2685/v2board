# 管理员权限

沿用 `is_admin` 身份，通过 `v2_user.admin_permissions` 分配权限，不启用员工模块。

当前实现范围是网页管理后台及其接口。Telegram 工单回复仍只检查 `is_admin` / `is_staff` 身份，尚未校验 `ticket.reply`；已绑定 Telegram 的受限管理员可能绕过网页的工单回复限制。部署前需要补齐该入口的授权检查。角色／权限组管理及员工后台接入也未包含在本次改动中。

- 数据库 SQL NULL：完整权限管理员，可以分配其他管理员权限。
- JSON 数组：受限管理员，只能使用数组中明确授权的操作；空数组没有业务权限。
- 原有管理员升级后保留完整权限；通过用户管理新提拔的管理员默认使用空数组。
- 操作权限自动包含对应查看权限，调整余额和佣金还会包含修改用户权限。用户、订单、优惠券、礼品卡页面会补齐套餐查询，用户和套餐页面会补齐节点权限组查询，不会因此授予节点连接凭证的查看权限。
- 无法撤销自己的完整权限。删除、封禁或撤销完整管理员身份前，需要另一位完整管理员先将其改为受限管理员。批量封禁和删除只作用于普通用户。

## 升级

先备份数据库，在发布新 PHP 代码前执行以下增量 SQL 一次：

```sql
ALTER TABLE `v2_user`
ADD `admin_permissions` text DEFAULT NULL
COMMENT 'NULL: full admin, JSON array: restricted admin' AFTER `is_admin`;
```

此 SQL 已加入 `database/update.sql`，新安装的结构也已同步。已执行项目更新流程的实例无需重复执行。由于现有更新命令会忽略 SQL 错误，请确认列实际创建成功。

发布后刷新配置与视图缓存，按部署方式重启常驻 PHP 进程：

```sh
php artisan config:clear
php artisan config:cache
php artisan view:clear
```

后台模板加载新增的 `permissions.js`、`permissions.css`，并更新原有后台脚本的缓存标记。仓库只包含后台构建产物，现有菜单与登录流程的接入修改位于 `public/assets/admin/umi.js`；以后替换该文件时需保留这些接入修改。

## 使用

1. 完整权限管理员在「用户管理」将目标账号设置为管理员。
2. 打开侧栏「管理员权限」，选择账号。
3. 取消「完整管理权限」，勾选所需功能后保存。需要完整权限时可以显式勾选。
4. 目标账号重新进入后台后，菜单和默认页面按权限显示。

每次后台请求都会重新校验签名、会话、封禁状态、管理员身份和数据库权限，不受原身份缓存的一小时有效期影响。权限保存后，下一个请求立即生效；已打开页面的菜单在重新进入后台时刷新。没有功能权限的管理员会看到等待授权页面。拒绝操作返回 HTTP 403 和 `permission_denied: true`，前端显示错误且保留登录状态。

受限管理员的用户查询和修改仅针对普通用户，排除管理员及员工。用户查看接口不返回密码哈希，受限管理员也不获取订阅凭证；用户导出是独立权限，其导出内容包含普通用户订阅地址。修改余额、佣金、折扣和封禁状态另行检查权限。系统配置、支付配置、节点配置、日志及内容发布等权限涉及敏感数据或站点内容，按实际职责授予。

## 接口和扩展

- `GET /api/v1/{secure_path}/permission/current`：当前管理员的权限与权限目录。
- `GET /api/v1/{secure_path}/permission/fetch`：完整管理员查看管理员账号。
- `POST /api/v1/{secure_path}/permission/save`：完整管理员分配权限，请求包含 `id`、布尔值 `is_super_admin`、数组 `permissions`。

权限目录和明确的控制器动作映射位于 `config/admin_permissions.php`。新增后台动作在未映射时默认拒绝受限管理员。Horizon 自带的其他监控和管理接口保留仅完整管理员可访问；后台已有的队列状态接口按目录授权。未授予系统配置查看权限时，`config/fetch?key=site` 只返回页面所需的币种及币种符号，不能读取其他配置。权限变更日志记录操作人、目标账号和变更前后的授权。

## 验证

```sh
vendor/bin/phpunit tests/Feature/AdminPermissionTest.php
node --check public/assets/admin/permissions.js
node --check public/assets/admin/umi.js
```

权限接口测试使用 SQLite 内存数据库，不连接业务数据库。数据库行锁在 MySQL 上串行处理权限分配；SQLite 测试不验证并发锁行为。
