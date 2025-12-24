package model

import (
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
)

func TestUserIsValid(t *testing.T) {
	tests := []struct {
		name     string
		user     User
		expected bool
	}{
		{
			name: "valid user",
			user: User{
				Banned: 0,
			},
			expected: true,
		},
		{
			name: "banned user",
			user: User{
				Banned: 1,
			},
			expected: false,
		},
		{
			name: "expired user",
			user: User{
				Banned:    0,
				ExpiredAt: ptrInt64(time.Now().Unix() - 3600), // 1 hour ago
			},
			expected: false,
		},
		{
			name: "valid user with future expiry",
			user: User{
				Banned:    0,
				ExpiredAt: ptrInt64(time.Now().Unix() + 3600), // 1 hour later
			},
			expected: true,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			result := tt.user.IsValid()
			assert.Equal(t, tt.expected, result)
		})
	}
}

func TestUserHasTraffic(t *testing.T) {
	tests := []struct {
		name     string
		user     User
		expected bool
	}{
		{
			name: "has traffic",
			user: User{
				U:              1024,
				D:              2048,
				TransferEnable: 10000,
			},
			expected: true,
		},
		{
			name: "no traffic left",
			user: User{
				U:              5000,
				D:              5000,
				TransferEnable: 10000,
			},
			expected: false,
		},
		{
			name: "exceeded traffic",
			user: User{
				U:              6000,
				D:              6000,
				TransferEnable: 10000,
			},
			expected: false,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			result := tt.user.HasTraffic()
			assert.Equal(t, tt.expected, result)
		})
	}
}

func TestUserGetSpeedLimit(t *testing.T) {
	tests := []struct {
		name     string
		user     User
		expected int64
	}{
		{
			name:     "no limit",
			user:     User{},
			expected: 0,
		},
		{
			name: "with limit",
			user: User{
				SpeedLimit: ptrInt64(10485760), // 10 MB/s
			},
			expected: 10485760,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			result := tt.user.GetSpeedLimit()
			assert.Equal(t, tt.expected, result)
		})
	}
}

func TestUserGetDeviceLimit(t *testing.T) {
	tests := []struct {
		name     string
		user     User
		expected int
	}{
		{
			name:     "no limit",
			user:     User{},
			expected: 0,
		},
		{
			name: "with limit",
			user: User{
				DeviceLimit: ptrInt(5),
			},
			expected: 5,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			result := tt.user.GetDeviceLimit()
			assert.Equal(t, tt.expected, result)
		})
	}
}

func ptrInt64(i int64) *int64 {
	return &i
}

func ptrInt(i int) *int {
	return &i
}
