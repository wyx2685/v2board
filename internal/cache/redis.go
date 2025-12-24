package cache

import (
	"context"
	"fmt"
	"time"

	"github.com/anixops/v2board/internal/config"
	"github.com/go-redis/redis/v8"
)

var rdb *redis.Client
var ctx = context.Background()

// Init 初始化Redis连接
func Init(cfg *config.RedisConfig) error {
	rdb = redis.NewClient(&redis.Options{
		Addr:     fmt.Sprintf("%s:%d", cfg.Host, cfg.Port),
		Password: cfg.Password,
		DB:       cfg.DB,
		PoolSize: cfg.PoolSize,
	})

	// 测试连接
	_, err := rdb.Ping(ctx).Result()
	if err != nil {
		return fmt.Errorf("failed to connect redis: %w", err)
	}

	return nil
}

// Get 获取Redis实例
func Get() *redis.Client {
	return rdb
}

// Close 关闭Redis连接
func Close() error {
	return rdb.Close()
}

// Set 设置缓存
func Set(key string, value interface{}, expiration time.Duration) error {
	return rdb.Set(ctx, key, value, expiration).Err()
}

// GetString 获取字符串缓存
func GetString(key string) (string, error) {
	return rdb.Get(ctx, key).Result()
}

// Del 删除缓存
func Del(keys ...string) error {
	return rdb.Del(ctx, keys...).Err()
}

// HSet 设置Hash
func HSet(key string, field string, value interface{}) error {
	return rdb.HSet(ctx, key, field, value).Err()
}

// HGet 获取Hash值
func HGet(key string, field string) (string, error) {
	return rdb.HGet(ctx, key, field).Result()
}

// HGetAll 获取所有Hash值
func HGetAll(key string) (map[string]string, error) {
	return rdb.HGetAll(ctx, key).Result()
}

// HDel 删除Hash字段
func HDel(key string, fields ...string) error {
	return rdb.HDel(ctx, key, fields...).Err()
}

// SAdd 集合添加
func SAdd(key string, members ...interface{}) error {
	return rdb.SAdd(ctx, key, members...).Err()
}

// SMembers 获取集合成员
func SMembers(key string) ([]string, error) {
	return rdb.SMembers(ctx, key).Result()
}

// SCard 获取集合大小
func SCard(key string) (int64, error) {
	return rdb.SCard(ctx, key).Result()
}

// Expire 设置过期时间
func Expire(key string, expiration time.Duration) error {
	return rdb.Expire(ctx, key, expiration).Err()
}

// Keys 获取匹配的键
func Keys(pattern string) ([]string, error) {
	return rdb.Keys(ctx, pattern).Result()
}
