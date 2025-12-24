package main

import (
	"flag"
	"fmt"
	"log"
	"net/http"
	"time"

	"github.com/anixops/v2board/internal/cache"
	"github.com/anixops/v2board/internal/config"
	"github.com/anixops/v2board/internal/database"
	"github.com/anixops/v2board/internal/router"
	"github.com/gin-gonic/gin"
)

var (
	configPath string
	version    = "2.0.0"
	buildTime  = "unknown"
)

func init() {
	flag.StringVar(&configPath, "config", "config/config.yaml", "配置文件路径")
}

func main() {
	flag.Parse()

	// 打印版本信息
	fmt.Printf("V2Board Go Backend v%s (build: %s)\n", version, buildTime)

	// 加载配置
	cfg, err := config.Load(configPath)
	if err != nil {
		log.Fatalf("Failed to load config: %v", err)
	}

	// 初始化数据库
	if err := database.Init(&cfg.Database); err != nil {
		log.Fatalf("Failed to init database: %v", err)
	}
	defer database.Close()

	// 初始化Redis
	if err := cache.Init(&cfg.Redis); err != nil {
		log.Fatalf("Failed to init redis: %v", err)
	}
	defer cache.Close()

	// 设置Gin模式
	gin.SetMode(cfg.Server.Mode)

	// 创建Gin引擎
	r := gin.New()

	// 设置路由
	router.Setup(r)

	// 创建HTTP服务器
	server := &http.Server{
		Addr:         fmt.Sprintf("%s:%d", cfg.Server.Host, cfg.Server.Port),
		Handler:      r,
		ReadTimeout:  time.Duration(cfg.Server.ReadTimeout) * time.Second,
		WriteTimeout: time.Duration(cfg.Server.WriteTimeout) * time.Second,
	}

	// 启动服务器
	log.Printf("Server starting on %s:%d", cfg.Server.Host, cfg.Server.Port)
	if err := server.ListenAndServe(); err != nil && err != http.ErrServerClosed {
		log.Fatalf("Failed to start server: %v", err)
	}
}
