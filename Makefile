.PHONY: build run clean test

# 版本信息
VERSION := 2.0.0
BUILD_TIME := $(shell date +%Y-%m-%d_%H:%M:%S)
LDFLAGS := -X main.version=$(VERSION) -X main.buildTime=$(BUILD_TIME)

# 编译
build:
	go build -ldflags "$(LDFLAGS)" -o v2board ./cmd/server

# 运行
run:
	go run ./cmd/server -config config/config.yaml

# 清理
clean:
	rm -f v2board
	go clean

# 测试
test:
	go test -v ./...

# 安装依赖
deps:
	go mod tidy

# 格式化代码
fmt:
	go fmt ./...

# 代码检查
lint:
	golangci-lint run

# 交叉编译
build-linux:
	GOOS=linux GOARCH=amd64 go build -ldflags "$(LDFLAGS)" -o v2board-linux-amd64 ./cmd/server

build-windows:
	GOOS=windows GOARCH=amd64 go build -ldflags "$(LDFLAGS)" -o v2board-windows-amd64.exe ./cmd/server

build-darwin:
	GOOS=darwin GOARCH=amd64 go build -ldflags "$(LDFLAGS)" -o v2board-darwin-amd64 ./cmd/server

build-all: build-linux build-windows build-darwin
