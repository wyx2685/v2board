# V2Board Go Backend

基于 Go 语言重构的 V2Board 面板后端，提供高性能的代理节点管理服务。

## 特性

- 🚀 高性能: 使用 Go 语言和 Gin 框架，性能大幅提升
- 🔧 多协议支持: VMess, VLESS, Trojan, Shadowsocks, Hysteria, Hysteria2, TUIC, AnyTLS
- 📦 简单部署: 单二进制文件，开箱即用
- 🔒 安全可靠: 原生支持 TLS、Reality 等安全特性
- 📊 流量统计: 完整的流量监控和统计功能
- 🌐 节点管理: 支持多节点集群管理

## 项目结构

```
.
├── cmd/
│   └── server/
│       └── main.go          # 程序入口
├── config/
│   └── config.yaml          # 配置文件
├── internal/
│   ├── cache/               # Redis 缓存
│   ├── config/              # 配置加载
│   ├── database/            # 数据库连接
│   ├── handler/             # HTTP 处理器
│   ├── middleware/          # 中间件
│   ├── model/               # 数据模型
│   ├── router/              # 路由设置
│   └── service/             # 业务逻辑
├── go.mod
├── go.sum
└── README_GO.md
```

## 快速开始

### 环境要求

- Go 1.22+
- MySQL 5.7+ / MariaDB 10.3+
- Redis 6.0+

### 编译

```bash
# 安装依赖
go mod tidy

# 编译
go build -o v2board ./cmd/server

# 或使用 make
make build
```

### 配置

复制配置文件并修改：

```bash
cp config/config.yaml.example config/config.yaml
```

编辑 `config/config.yaml`：

```yaml
server:
  host: "0.0.0.0"
  port: 8080
  mode: "release"

database:
  host: "127.0.0.1"
  port: 3306
  database: "v2board"
  username: "root"
  password: "your_password"

redis:
  host: "127.0.0.1"
  port: 6379

app:
  api_token: "your_node_communication_token"
```

### 运行

```bash
./v2board -config config/config.yaml
```

## API 接口

### UniProxy API (节点通信)

所有节点通信接口都需要携带以下 Query 参数：

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `node_type` | string | 是 | 节点类型 |
| `node_id` | int | 是 | 节点ID |
| `token` | string | 是 | API令牌 |

#### 获取节点配置

```
GET /api/v1/server/UniProxy/config
```

#### 获取用户列表

```
GET /api/v1/server/UniProxy/user
```

支持 `msgpack` 响应格式（通过 `X-Response-Format: msgpack` 请求头）

#### 获取在线状态

```
GET /api/v1/server/UniProxy/alivelist
```

#### 上报流量

```
POST /api/v1/server/UniProxy/push
```

请求体：
```json
{
  "1": [1024000, 2048000],
  "2": [512000, 1024000]
}
```

#### 上报在线状态

```
POST /api/v1/server/UniProxy/alive
```

请求体：
```json
{
  "1": ["192.168.1.100", "10.0.0.50"],
  "2": ["172.16.0.1"]
}
```

## 数据库迁移

项目兼容 V2Board PHP 版本的数据库结构，可以直接使用现有数据库。

## 开发

### 目录说明

- `cmd/`: 程序入口
- `internal/`: 内部包（不对外暴露）
  - `cache/`: Redis 缓存封装
  - `config/`: 配置文件加载
  - `database/`: 数据库连接管理
  - `handler/`: HTTP 请求处理
  - `middleware/`: Gin 中间件
  - `model/`: GORM 数据模型
  - `router/`: 路由配置
  - `service/`: 业务逻辑层

### 添加新的协议支持

1. 在 `internal/model/server.go` 中添加新的服务器模型
2. 在 `internal/service/server_service.go` 中添加配置构建逻辑
3. 在数据库中添加对应的表

## 部署

### Systemd

创建 `/etc/systemd/system/v2board.service`：

```ini
[Unit]
Description=V2Board Go Backend
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/opt/v2board
ExecStart=/opt/v2board/v2board -config /opt/v2board/config/config.yaml
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable v2board
sudo systemctl start v2board
```

### Docker

```dockerfile
FROM golang:1.22-alpine AS builder

WORKDIR /app
COPY . .
RUN go mod tidy && go build -o v2board ./cmd/server

FROM alpine:latest
WORKDIR /app
COPY --from=builder /app/v2board .
COPY config/config.yaml ./config/

EXPOSE 8080
CMD ["./v2board", "-config", "config/config.yaml"]
```

## 许可证

MIT License
