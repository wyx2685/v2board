package service

import (
	"encoding/json"
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestParseTrafficData(t *testing.T) {
	tests := []struct {
		name     string
		input    string
		expected map[uint][2]int64
	}{
		{
			name:  "normal traffic data",
			input: `{"1": [1024, 2048], "2": [512, 1024]}`,
			expected: map[uint][2]int64{
				1: {1024, 2048},
				2: {512, 1024},
			},
		},
		{
			name:     "empty data",
			input:    `{}`,
			expected: map[uint][2]int64{},
		},
		{
			name:  "large numbers",
			input: `{"1": [1073741824, 2147483648]}`,
			expected: map[uint][2]int64{
				1: {1073741824, 2147483648},
			},
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			var data map[string]interface{}
			err := json.Unmarshal([]byte(tt.input), &data)
			assert.NoError(t, err)

			result, err := ParseTrafficData(data)
			assert.NoError(t, err)
			assert.Equal(t, len(tt.expected), len(result))

			for k, v := range tt.expected {
				assert.Equal(t, v, result[k])
			}
		})
	}
}

func TestParseOnlineData(t *testing.T) {
	tests := []struct {
		name     string
		input    string
		expected map[uint][]string
	}{
		{
			name:  "normal online data",
			input: `{"1": ["192.168.1.100", "10.0.0.50"], "2": ["172.16.0.1"]}`,
			expected: map[uint][]string{
				1: {"192.168.1.100", "10.0.0.50"},
				2: {"172.16.0.1"},
			},
		},
		{
			name:     "empty data",
			input:    `{}`,
			expected: map[uint][]string{},
		},
		{
			name:  "single user single ip",
			input: `{"1": ["192.168.1.1"]}`,
			expected: map[uint][]string{
				1: {"192.168.1.1"},
			},
		},
		{
			name:  "ipv6 addresses",
			input: `{"1": ["::1", "2001:db8::1"]}`,
			expected: map[uint][]string{
				1: {"::1", "2001:db8::1"},
			},
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			var data map[string]interface{}
			err := json.Unmarshal([]byte(tt.input), &data)
			assert.NoError(t, err)

			result, err := ParseOnlineData(data)
			assert.NoError(t, err)
			assert.Equal(t, len(tt.expected), len(result))

			for k, v := range tt.expected {
				assert.Equal(t, v, result[k])
			}
		})
	}
}

func TestToInt64(t *testing.T) {
	tests := []struct {
		name     string
		input    interface{}
		expected int64
		ok       bool
	}{
		{
			name:     "float64",
			input:    float64(1024),
			expected: 1024,
			ok:       true,
		},
		{
			name:     "int64",
			input:    int64(2048),
			expected: 2048,
			ok:       true,
		},
		{
			name:     "int",
			input:    int(512),
			expected: 512,
			ok:       true,
		},
		{
			name:     "json.Number",
			input:    json.Number("4096"),
			expected: 4096,
			ok:       true,
		},
		{
			name:     "string (unsupported)",
			input:    "1024",
			expected: 0,
			ok:       false,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			result, ok := toInt64(tt.input)
			assert.Equal(t, tt.expected, result)
			assert.Equal(t, tt.ok, ok)
		})
	}
}

func TestIpsToInterface(t *testing.T) {
	ips := []string{"192.168.1.1", "10.0.0.1"}
	result := ipsToInterface(ips)

	assert.Len(t, result, 2)
	assert.Equal(t, "192.168.1.1", result[0])
	assert.Equal(t, "10.0.0.1", result[1])
}
