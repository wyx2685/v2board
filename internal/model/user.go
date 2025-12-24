package model

import (
	"time"
)

// User 用户模型
type User struct {
	ID                uint      `gorm:"primaryKey" json:"id"`
	InviteUserID      *uint     `gorm:"index" json:"invite_user_id"`
	TelegramID        *int64    `json:"telegram_id"`
	Email             string    `gorm:"size:255;uniqueIndex" json:"email"`
	Password          string    `gorm:"size:255" json:"-"`
	PasswordAlgo      *string   `gorm:"size:20" json:"-"`
	PasswordSalt      *string   `gorm:"size:20" json:"-"`
	Balance           int64     `gorm:"default:0" json:"balance"`
	Discount          *int      `json:"discount"`
	CommissionType    int       `gorm:"default:0" json:"commission_type"`
	CommissionRate    *int      `json:"commission_rate"`
	CommissionBalance int64     `gorm:"default:0" json:"commission_balance"`
	Token             string    `gorm:"size:36;uniqueIndex" json:"token"`
	UUID              string    `gorm:"size:36;uniqueIndex" json:"uuid"`
	DeviceLimit       *int      `json:"device_limit"`
	SpeedLimit        *int64    `json:"speed_limit"`
	TransferEnable    int64     `gorm:"default:0" json:"transfer_enable"` // bytes
	U                 int64     `gorm:"default:0" json:"u"`               // upload bytes
	D                 int64     `gorm:"default:0" json:"d"`               // download bytes
	PlanID            *uint     `gorm:"index" json:"plan_id"`
	GroupID           *uint     `gorm:"index" json:"group_id"`
	ExpiredAt         *int64    `json:"expired_at"`
	Banned            int       `gorm:"default:0" json:"banned"`
	RemarkContent     *string   `gorm:"type:text" json:"remark_content"`
	IsAdmin           int       `gorm:"default:0" json:"is_admin"`
	IsStaff           int       `gorm:"default:0" json:"is_staff"`
	LastLoginAt       *int64    `json:"last_login_at"`
	CreatedAt         time.Time `json:"created_at"`
	UpdatedAt         time.Time `json:"updated_at"`

	// Relations
	Plan *Plan `gorm:"foreignKey:PlanID" json:"plan,omitempty"`
}

func (User) TableName() string {
	return "v2_user"
}

// IsValid 检查用户是否有效
func (u *User) IsValid() bool {
	if u.Banned == 1 {
		return false
	}
	if u.ExpiredAt != nil && *u.ExpiredAt < time.Now().Unix() {
		return false
	}
	return true
}

// HasTraffic 检查用户是否有剩余流量
func (u *User) HasTraffic() bool {
	used := u.U + u.D
	return used < u.TransferEnable
}

// GetSpeedLimit 获取速度限制 (bytes/s)
func (u *User) GetSpeedLimit() int64 {
	if u.SpeedLimit != nil && *u.SpeedLimit > 0 {
		return *u.SpeedLimit
	}
	return 0
}

// GetDeviceLimit 获取设备限制
func (u *User) GetDeviceLimit() int {
	if u.DeviceLimit != nil && *u.DeviceLimit > 0 {
		return *u.DeviceLimit
	}
	return 0
}
