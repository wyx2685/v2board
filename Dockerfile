FROM golang:1.22-alpine AS builder

# 安装必要的构建工具
RUN apk add --no-cache git make

WORKDIR /app

# 复制依赖文件
COPY go.mod go.sum ./
RUN go mod download

# 复制源代码
COPY . .

# 编译
RUN CGO_ENABLED=0 GOOS=linux go build -ldflags="-s -w" -o v2board ./cmd/server

# 运行镜像
FROM alpine:latest

RUN apk --no-cache add ca-certificates tzdata

WORKDIR /app

# 复制编译产物
COPY --from=builder /app/v2board .
COPY --from=builder /app/config/config.yaml ./config/

# 设置时区
ENV TZ=Asia/Shanghai

EXPOSE 8080

CMD ["./v2board", "-config", "config/config.yaml"]
