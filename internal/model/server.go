package model

import (
	"encoding/json"
	"time"
)

// ServerType 服务器类型
type ServerType string

const (
	ServerTypeVMess       ServerType = "vmess"
	ServerTypeVLESS       ServerType = "vless"
	ServerTypeTrojan      ServerType = "trojan"
	ServerTypeShadowsocks ServerType = "shadowsocks"
	ServerTypeHysteria    ServerType = "hysteria"
	ServerTypeHysteria2   ServerType = "hysteria2"
	ServerTypeTUIC        ServerType = "tuic"
	ServerTypeAnyTLS      ServerType = "anytls"
)

// BaseServer 服务器基础模型
type BaseServer struct {
	ID          uint      `gorm:"primaryKey" json:"id"`
	GroupID     string    `gorm:"type:varchar(255)" json:"group_id"` // JSON array of group IDs
	RouteID     string    `gorm:"type:varchar(255)" json:"route_id"` // JSON array of route IDs
	ParentID    *uint     `json:"parent_id"`
	Tags        *string   `gorm:"type:varchar(255)" json:"tags"`
	Name        string    `gorm:"size:255" json:"name"`
	Rate        float64   `gorm:"default:1" json:"rate"`
	Host        string    `gorm:"size:255" json:"host"`
	Port        int       `json:"port"`
	ServerPort  int       `json:"server_port"`
	TLS         int       `gorm:"default:0" json:"tls"` // 0: None, 1: TLS, 2: Reality
	TLSSettings *string   `gorm:"type:text" json:"tls_settings"`
	Show        int       `gorm:"default:0" json:"show"`
	Sort        *int      `json:"sort"`
	CreatedAt   time.Time `json:"created_at"`
	UpdatedAt   time.Time `json:"updated_at"`
}

// ServerVMess VMess服务器
type ServerVMess struct {
	BaseServer
	Network         string  `gorm:"size:20" json:"network"` // tcp, ws, grpc, http, quic
	NetworkSettings *string `gorm:"type:text" json:"network_settings"`
}

func (ServerVMess) TableName() string {
	return "v2_server_vmess"
}

// ServerVLESS VLESS服务器
type ServerVLESS struct {
	BaseServer
	Network            string  `gorm:"size:20" json:"network"`
	NetworkSettings    *string `gorm:"type:text" json:"network_settings"`
	Flow               string  `gorm:"size:50" json:"flow"` // xtls-rprx-vision
	Encryption         *string `gorm:"size:50" json:"encryption"`
	EncryptionSettings *string `gorm:"type:text" json:"encryption_settings"`
	RealitySettings    *string `gorm:"type:text" json:"reality_settings"`
}

func (ServerVLESS) TableName() string {
	return "v2_server_vless"
}

// ServerTrojan Trojan服务器
type ServerTrojan struct {
	BaseServer
	Network         string  `gorm:"size:20" json:"network"`
	NetworkSettings *string `gorm:"type:text" json:"network_settings"`
	ServerName      *string `gorm:"size:255" json:"server_name"`
	AllowInsecure   int     `gorm:"default:0" json:"allow_insecure"`
}

func (ServerTrojan) TableName() string {
	return "v2_server_trojan"
}

// ServerShadowsocks Shadowsocks服务器
type ServerShadowsocks struct {
	BaseServer
	Cipher       string  `gorm:"size:50" json:"cipher"`
	ServerKey    *string `gorm:"size:255" json:"server_key"`
	Obfs         *string `gorm:"size:20" json:"obfs"`
	ObfsSettings *string `gorm:"type:text" json:"obfs_settings"`
}

func (ServerShadowsocks) TableName() string {
	return "v2_server_shadowsocks"
}

// ServerHysteria Hysteria服务器
type ServerHysteria struct {
	BaseServer
	Version               int     `gorm:"default:2" json:"version"` // 1 or 2
	UpMbps                int     `json:"up_mbps"`
	DownMbps              int     `json:"down_mbps"`
	Obfs                  string  `gorm:"size:50" json:"obfs"`
	ObfsPassword          *string `gorm:"size:255" json:"obfs_password"`
	ServerName            *string `gorm:"size:255" json:"server_name"`
	IgnoreClientBandwidth int     `gorm:"default:0" json:"ignore_client_bandwidth"`
}

func (ServerHysteria) TableName() string {
	return "v2_server_hysteria"
}

// ServerTUIC TUIC服务器
type ServerTUIC struct {
	BaseServer
	CongestionControl string  `gorm:"size:20;default:bbr" json:"congestion_control"` // bbr, cubic, new_reno
	ZeroRTTHandshake  int     `gorm:"default:0" json:"zero_rtt_handshake"`
	ServerName        *string `gorm:"size:255" json:"server_name"`
}

func (ServerTUIC) TableName() string {
	return "v2_server_tuic"
}

// ServerAnyTLS AnyTLS服务器
type ServerAnyTLS struct {
	BaseServer
	PaddingScheme *string `gorm:"type:text" json:"padding_scheme"` // JSON array
	ServerName    *string `gorm:"size:255" json:"server_name"`
}

func (ServerAnyTLS) TableName() string {
	return "v2_server_anytls"
}

// ServerGroup 服务器分组
type ServerGroup struct {
	ID        uint      `gorm:"primaryKey" json:"id"`
	Name      string    `gorm:"size:255" json:"name"`
	CreatedAt time.Time `json:"created_at"`
	UpdatedAt time.Time `json:"updated_at"`
}

func (ServerGroup) TableName() string {
	return "v2_server_group"
}

// ServerRoute 服务器路由
type ServerRoute struct {
	ID          uint      `gorm:"primaryKey" json:"id"`
	Remarks     string    `gorm:"size:255" json:"remarks"`
	Match       string    `gorm:"type:text" json:"match"`
	Action      string    `gorm:"size:20" json:"action"` // block, dns
	ActionValue *string   `gorm:"type:text" json:"action_value"`
	CreatedAt   time.Time `json:"created_at"`
	UpdatedAt   time.Time `json:"updated_at"`
}

func (ServerRoute) TableName() string {
	return "v2_server_route"
}

// GetGroupIDs 获取分组ID列表
func (s *BaseServer) GetGroupIDs() []uint {
	var ids []uint
	if s.GroupID != "" {
		json.Unmarshal([]byte(s.GroupID), &ids)
	}
	return ids
}

// GetRouteIDs 获取路由ID列表
func (s *BaseServer) GetRouteIDs() []uint {
	var ids []uint
	if s.RouteID != "" {
		json.Unmarshal([]byte(s.RouteID), &ids)
	}
	return ids
}

// ServerInfo 统一服务器信息接口
type ServerInfo interface {
	GetID() uint
	GetType() ServerType
	GetHost() string
	GetPort() int
	GetServerPort() int
	GetGroupIDs() []uint
	GetRouteIDs() []uint
}

func (s *ServerVMess) GetID() uint         { return s.ID }
func (s *ServerVMess) GetType() ServerType { return ServerTypeVMess }
func (s *ServerVMess) GetHost() string     { return s.Host }
func (s *ServerVMess) GetPort() int        { return s.Port }
func (s *ServerVMess) GetServerPort() int  { return s.ServerPort }

func (s *ServerVLESS) GetID() uint         { return s.ID }
func (s *ServerVLESS) GetType() ServerType { return ServerTypeVLESS }
func (s *ServerVLESS) GetHost() string     { return s.Host }
func (s *ServerVLESS) GetPort() int        { return s.Port }
func (s *ServerVLESS) GetServerPort() int  { return s.ServerPort }

func (s *ServerTrojan) GetID() uint         { return s.ID }
func (s *ServerTrojan) GetType() ServerType { return ServerTypeTrojan }
func (s *ServerTrojan) GetHost() string     { return s.Host }
func (s *ServerTrojan) GetPort() int        { return s.Port }
func (s *ServerTrojan) GetServerPort() int  { return s.ServerPort }

func (s *ServerShadowsocks) GetID() uint         { return s.ID }
func (s *ServerShadowsocks) GetType() ServerType { return ServerTypeShadowsocks }
func (s *ServerShadowsocks) GetHost() string     { return s.Host }
func (s *ServerShadowsocks) GetPort() int        { return s.Port }
func (s *ServerShadowsocks) GetServerPort() int  { return s.ServerPort }

func (s *ServerHysteria) GetID() uint         { return s.ID }
func (s *ServerHysteria) GetType() ServerType { return ServerTypeHysteria }
func (s *ServerHysteria) GetHost() string     { return s.Host }
func (s *ServerHysteria) GetPort() int        { return s.Port }
func (s *ServerHysteria) GetServerPort() int  { return s.ServerPort }

func (s *ServerTUIC) GetID() uint         { return s.ID }
func (s *ServerTUIC) GetType() ServerType { return ServerTypeTUIC }
func (s *ServerTUIC) GetHost() string     { return s.Host }
func (s *ServerTUIC) GetPort() int        { return s.Port }
func (s *ServerTUIC) GetServerPort() int  { return s.ServerPort }

func (s *ServerAnyTLS) GetID() uint         { return s.ID }
func (s *ServerAnyTLS) GetType() ServerType { return ServerTypeAnyTLS }
func (s *ServerAnyTLS) GetHost() string     { return s.Host }
func (s *ServerAnyTLS) GetPort() int        { return s.Port }
func (s *ServerAnyTLS) GetServerPort() int  { return s.ServerPort }
