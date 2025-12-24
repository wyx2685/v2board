package handler

import (
	"crypto/md5"
	"encoding/hex"
	"encoding/json"
	"net/http"
	"strconv"

	"github.com/anixops/v2board/internal/model"
	"github.com/anixops/v2board/internal/service"
	"github.com/gin-gonic/gin"
	"github.com/vmihailenco/msgpack/v5"
)

// UniProxyHandler UniProxy API处理器
type UniProxyHandler struct {
	serverService *service.ServerService
	userService   *service.UserService
}

// NewUniProxyHandler 创建UniProxy处理器
func NewUniProxyHandler() *UniProxyHandler {
	return &UniProxyHandler{
		serverService: service.NewServerService(),
		userService:   service.NewUserService(),
	}
}

// GetConfig 获取节点配置
// GET /api/v1/server/UniProxy/config
func (h *UniProxyHandler) GetConfig(c *gin.Context) {
	nodeType := c.Query("node_type")
	nodeIDStr := c.Query("node_id")

	nodeID, err := strconv.ParseUint(nodeIDStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid node_id"})
		return
	}

	serverType := model.ServerType(nodeType)

	// 构建节点配置
	config, err := h.serverService.BuildNodeConfig(serverType, uint(nodeID))
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "node not found"})
		return
	}

	// 生成ETag
	configJSON, _ := json.Marshal(config)
	etag := generateETag(configJSON)

	// 检查ETag
	ifNoneMatch := c.GetHeader("If-None-Match")
	if ifNoneMatch == etag {
		c.Status(http.StatusNotModified)
		return
	}

	c.Header("ETag", etag)
	c.JSON(http.StatusOK, config)
}

// GetUsers 获取用户列表
// GET /api/v1/server/UniProxy/user
func (h *UniProxyHandler) GetUsers(c *gin.Context) {
	nodeType := c.Query("node_type")
	nodeIDStr := c.Query("node_id")

	nodeID, err := strconv.ParseUint(nodeIDStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid node_id"})
		return
	}

	serverType := model.ServerType(nodeType)

	// 获取服务器用户
	users, err := h.serverService.GetServerUsers(serverType, uint(nodeID))
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to get users"})
		return
	}

	// 构建响应
	userList := make([]map[string]interface{}, 0, len(users))
	for _, user := range users {
		speedLimit := user.GetSpeedLimit()
		deviceLimit := user.GetDeviceLimit()

		// 如果用户没有设置，则从套餐获取
		if speedLimit == 0 && user.Plan != nil {
			speedLimit = user.Plan.GetSpeedLimit()
		}
		if deviceLimit == 0 && user.Plan != nil {
			deviceLimit = user.Plan.GetDeviceLimit()
		}

		userList = append(userList, map[string]interface{}{
			"id":           user.ID,
			"uuid":         user.UUID,
			"speed_limit":  speedLimit,
			"device_limit": deviceLimit,
		})
	}

	response := map[string]interface{}{
		"users": userList,
	}

	// 生成ETag
	responseJSON, _ := json.Marshal(response)
	etag := generateETag(responseJSON)

	// 检查ETag
	ifNoneMatch := c.GetHeader("If-None-Match")
	if ifNoneMatch == etag {
		c.Status(http.StatusNotModified)
		return
	}

	c.Header("ETag", etag)

	// 检查响应格式
	responseFormat := c.GetHeader("X-Response-Format")
	if responseFormat == "msgpack" {
		msgpackData, err := msgpack.Marshal(response)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to encode msgpack"})
			return
		}
		c.Data(http.StatusOK, "application/x-msgpack", msgpackData)
		return
	}

	c.JSON(http.StatusOK, response)
}

// GetAliveList 获取用户在线状态
// GET /api/v1/server/UniProxy/alivelist
func (h *UniProxyHandler) GetAliveList(c *gin.Context) {
	aliveMap, err := h.serverService.GetAllUsersOnlineCount()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to get alive list"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"alive": aliveMap,
	})
}

// PushTraffic 上报用户流量
// POST /api/v1/server/UniProxy/push
func (h *UniProxyHandler) PushTraffic(c *gin.Context) {
	nodeType := c.Query("node_type")
	nodeIDStr := c.Query("node_id")

	nodeID, err := strconv.ParseUint(nodeIDStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid node_id"})
		return
	}

	serverType := model.ServerType(nodeType)

	// 解析请求体
	var trafficData map[string]interface{}
	if err := c.ShouldBindJSON(&trafficData); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid request body"})
		return
	}

	// 解析流量数据
	traffics, err := service.ParseTrafficData(trafficData)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid traffic data"})
		return
	}

	if len(traffics) == 0 {
		c.JSON(http.StatusOK, gin.H{"status": "success"})
		return
	}

	// 获取服务器流量倍率
	rate := h.serverService.GetServerRate(serverType, uint(nodeID))

	// 记录流量日志
	if err := h.serverService.BatchRecordTrafficLog(serverType, uint(nodeID), traffics, rate); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to record traffic log"})
		return
	}

	// 更新用户流量（按倍率计算）
	userTraffics := make(map[uint][2]int64)
	for userID, traffic := range traffics {
		userTraffics[userID] = [2]int64{
			int64(float64(traffic[0]) * rate),
			int64(float64(traffic[1]) * rate),
		}
	}

	if err := h.userService.BatchUpdateTraffic(userTraffics); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to update user traffic"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"status": "success"})
}

// PushAlive 上报用户在线状态
// POST /api/v1/server/UniProxy/alive
func (h *UniProxyHandler) PushAlive(c *gin.Context) {
	nodeType := c.Query("node_type")
	nodeIDStr := c.Query("node_id")

	nodeID, err := strconv.ParseUint(nodeIDStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid node_id"})
		return
	}

	serverType := model.ServerType(nodeType)

	// 解析请求体
	var onlineData map[string]interface{}
	if err := c.ShouldBindJSON(&onlineData); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid request body"})
		return
	}

	// 解析在线数据
	userIPs, err := service.ParseOnlineData(onlineData)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid online data"})
		return
	}

	// 更新在线状态
	if err := h.serverService.UpdateOnlineStatus(serverType, uint(nodeID), userIPs); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to update online status"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"status": "success"})
}

// generateETag 生成ETag
func generateETag(data []byte) string {
	hash := md5.Sum(data)
	return hex.EncodeToString(hash[:])
}
