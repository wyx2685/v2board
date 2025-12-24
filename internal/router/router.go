package router

import (
	"github.com/anixops/v2board/internal/handler"
	"github.com/anixops/v2board/internal/middleware"
	"github.com/gin-gonic/gin"
)

// Setup 设置路由
func Setup(r *gin.Engine) {
	// 全局中间件
	r.Use(middleware.CORS())
	r.Use(middleware.Logger())
	r.Use(middleware.Recovery())

	// 健康检查
	r.GET("/health", func(c *gin.Context) {
		c.JSON(200, gin.H{"status": "ok"})
	})

	// API v1
	v1 := r.Group("/api/v1")
	{
		// UniProxy API (节点通信接口)
		uniproxy := v1.Group("/server/UniProxy")
		uniproxy.Use(middleware.NodeAuth())
		{
			h := handler.NewUniProxyHandler()
			uniproxy.GET("/config", h.GetConfig)
			uniproxy.GET("/user", h.GetUsers)
			uniproxy.GET("/alivelist", h.GetAliveList)
			uniproxy.POST("/push", h.PushTraffic)
			uniproxy.POST("/alive", h.PushAlive)
		}

		// 其他API路由将在后续添加
		// - 用户认证
		// - 订阅管理
		// - 后台管理
	}
}
