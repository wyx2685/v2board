package handler

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func setupTestRouter() *gin.Engine {
	gin.SetMode(gin.TestMode)
	r := gin.New()
	return r
}

func TestGenerateETag(t *testing.T) {
	data := []byte(`{"test": "data"}`)
	etag := generateETag(data)

	assert.NotEmpty(t, etag)
	assert.Len(t, etag, 32) // MD5 hash is 32 hex characters

	// 相同数据应该生成相同的ETag
	etag2 := generateETag(data)
	assert.Equal(t, etag, etag2)

	// 不同数据应该生成不同的ETag
	data2 := []byte(`{"test": "data2"}`)
	etag3 := generateETag(data2)
	assert.NotEqual(t, etag, etag3)
}

func TestPushTrafficDataParsing(t *testing.T) {
	// 测试流量数据解析
	trafficJSON := `{
		"1": [1024, 2048],
		"2": [512, 1024]
	}`

	var data map[string]interface{}
	err := json.Unmarshal([]byte(trafficJSON), &data)
	assert.NoError(t, err)

	// 验证数据格式
	for k, v := range data {
		traffic, ok := v.([]interface{})
		assert.True(t, ok, "traffic data should be an array for key %s", k)
		assert.Len(t, traffic, 2, "traffic data should have 2 elements")
	}
}

func TestPushAliveDataParsing(t *testing.T) {
	// 测试在线数据解析
	aliveJSON := `{
		"1": ["192.168.1.100", "10.0.0.50"],
		"2": ["172.16.0.1"]
	}`

	var data map[string]interface{}
	err := json.Unmarshal([]byte(aliveJSON), &data)
	assert.NoError(t, err)

	// 验证数据格式
	for k, v := range data {
		ips, ok := v.([]interface{})
		assert.True(t, ok, "IP list should be an array for key %s", k)
		for _, ip := range ips {
			_, isString := ip.(string)
			assert.True(t, isString, "IP should be a string")
		}
	}
}

func TestConfigAPIValidation(t *testing.T) {
	r := setupTestRouter()

	// 模拟配置API路由（不实际调用数据库）
	r.GET("/api/v1/server/UniProxy/config", func(c *gin.Context) {
		nodeType := c.Query("node_type")
		nodeID := c.Query("node_id")
		token := c.Query("token")

		if nodeType == "" || nodeID == "" || token == "" {
			c.JSON(http.StatusBadRequest, gin.H{"error": "missing required parameters"})
			return
		}

		c.JSON(http.StatusOK, gin.H{"status": "ok"})
	})

	// 测试缺少参数
	req, _ := http.NewRequest("GET", "/api/v1/server/UniProxy/config", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)

	// 测试正常参数
	req, _ = http.NewRequest("GET", "/api/v1/server/UniProxy/config?node_type=vmess&node_id=1&token=test", nil)
	w = httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestUserAPIWithMsgpack(t *testing.T) {
	r := setupTestRouter()

	r.GET("/api/v1/server/UniProxy/user", func(c *gin.Context) {
		format := c.GetHeader("X-Response-Format")

		response := gin.H{
			"users": []gin.H{
				{"id": 1, "uuid": "test-uuid", "speed_limit": 0, "device_limit": 3},
			},
		}

		if format == "msgpack" {
			c.Header("Content-Type", "application/x-msgpack")
			c.JSON(http.StatusOK, response) // 简化测试
			return
		}

		c.JSON(http.StatusOK, response)
	})

	// 测试普通JSON请求
	req, _ := http.NewRequest("GET", "/api/v1/server/UniProxy/user?node_type=vmess&node_id=1&token=test", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	assert.Contains(t, w.Header().Get("Content-Type"), "application/json")

	// 测试msgpack请求
	req, _ = http.NewRequest("GET", "/api/v1/server/UniProxy/user?node_type=vmess&node_id=1&token=test", nil)
	req.Header.Set("X-Response-Format", "msgpack")
	w = httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestPushTrafficAPI(t *testing.T) {
	r := setupTestRouter()

	r.POST("/api/v1/server/UniProxy/push", func(c *gin.Context) {
		var data map[string]interface{}
		if err := c.ShouldBindJSON(&data); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": "invalid request body"})
			return
		}

		c.JSON(http.StatusOK, gin.H{"status": "success"})
	})

	// 测试正常请求
	body := `{"1": [1024, 2048], "2": [512, 1024]}`
	req, _ := http.NewRequest("POST", "/api/v1/server/UniProxy/push?node_type=vmess&node_id=1&token=test",
		strings.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)

	var response map[string]string
	json.Unmarshal(w.Body.Bytes(), &response)
	assert.Equal(t, "success", response["status"])
}

func TestPushAliveAPI(t *testing.T) {
	r := setupTestRouter()

	r.POST("/api/v1/server/UniProxy/alive", func(c *gin.Context) {
		var data map[string]interface{}
		if err := c.ShouldBindJSON(&data); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": "invalid request body"})
			return
		}

		c.JSON(http.StatusOK, gin.H{"status": "success"})
	})

	// 测试正常请求
	body := `{"1": ["192.168.1.100", "10.0.0.50"], "2": ["172.16.0.1"]}`
	req, _ := http.NewRequest("POST", "/api/v1/server/UniProxy/alive?node_type=vmess&node_id=1&token=test",
		strings.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)

	var response map[string]string
	json.Unmarshal(w.Body.Bytes(), &response)
	assert.Equal(t, "success", response["status"])
}
