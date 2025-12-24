package model

import "time"

// Plan 订阅计划模型
type Plan struct {
	ID                 uint    `gorm:"primaryKey" json:"id"`
	GroupID            uint    `gorm:"index" json:"group_id"`
	TransferEnable     int64   `json:"transfer_enable"` // GB
	SpeedLimit         *int64  `json:"speed_limit"`
	DeviceLimit        *int    `json:"device_limit"`
	Name               string  `gorm:"size:255" json:"name"`
	Content            *string `gorm:"type:text" json:"content"`
	Show               int     `gorm:"default:0" json:"show"`
	Sort               *int    `json:"sort"`
	Renew              int     `gorm:"default:1" json:"renew"`
	ResetPrice         *int64  `json:"reset_price"`
	ResetTrafficMethod *int    `json:"reset_traffic_method"`
	CapacityLimit      *int    `json:"capacity_limit"`

	// Prices
	MonthPrice     *int64 `json:"month_price"`
	QuarterPrice   *int64 `json:"quarter_price"`
	HalfYearPrice  *int64 `json:"half_year_price"`
	YearPrice      *int64 `json:"year_price"`
	TwoYearPrice   *int64 `json:"two_year_price"`
	ThreeYearPrice *int64 `json:"three_year_price"`
	OnetimePrice   *int64 `json:"onetime_price"`

	CreatedAt time.Time `json:"created_at"`
	UpdatedAt time.Time `json:"updated_at"`
}

func (Plan) TableName() string {
	return "v2_plan"
}

// GetSpeedLimit 获取速度限制
func (p *Plan) GetSpeedLimit() int64 {
	if p.SpeedLimit != nil {
		return *p.SpeedLimit
	}
	return 0
}

// GetDeviceLimit 获取设备限制
func (p *Plan) GetDeviceLimit() int {
	if p.DeviceLimit != nil {
		return *p.DeviceLimit
	}
	return 0
}
