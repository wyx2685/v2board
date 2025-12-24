package service

import (
	"github.com/anixops/v2board/internal/database"
	"github.com/anixops/v2board/internal/model"
	"gorm.io/gorm"
)

// UserService 用户服务
type UserService struct {
	db *gorm.DB
}

// NewUserService 创建用户服务
func NewUserService() *UserService {
	return &UserService{
		db: database.Get(),
	}
}

// GetByID 根据ID获取用户
func (s *UserService) GetByID(id uint) (*model.User, error) {
	var user model.User
	err := s.db.Preload("Plan").First(&user, id).Error
	if err != nil {
		return nil, err
	}
	return &user, nil
}

// GetByUUID 根据UUID获取用户
func (s *UserService) GetByUUID(uuid string) (*model.User, error) {
	var user model.User
	err := s.db.Preload("Plan").Where("uuid = ?", uuid).First(&user).Error
	if err != nil {
		return nil, err
	}
	return &user, nil
}

// GetByToken 根据Token获取用户
func (s *UserService) GetByToken(token string) (*model.User, error) {
	var user model.User
	err := s.db.Preload("Plan").Where("token = ?", token).First(&user).Error
	if err != nil {
		return nil, err
	}
	return &user, nil
}

// GetByEmail 根据Email获取用户
func (s *UserService) GetByEmail(email string) (*model.User, error) {
	var user model.User
	err := s.db.Preload("Plan").Where("email = ?", email).First(&user).Error
	if err != nil {
		return nil, err
	}
	return &user, nil
}

// GetActiveUsersByGroupID 获取指定分组的有效用户
func (s *UserService) GetActiveUsersByGroupID(groupID uint) ([]model.User, error) {
	var users []model.User
	err := s.db.Preload("Plan").
		Where("group_id = ?", groupID).
		Where("banned = 0").
		Where("(expired_at IS NULL OR expired_at > UNIX_TIMESTAMP())").
		Where("(u + d) < transfer_enable").
		Find(&users).Error
	return users, err
}

// GetActiveUsers 获取所有有效用户
func (s *UserService) GetActiveUsers() ([]model.User, error) {
	var users []model.User
	err := s.db.Preload("Plan").
		Where("banned = 0").
		Where("(expired_at IS NULL OR expired_at > UNIX_TIMESTAMP())").
		Where("(u + d) < transfer_enable").
		Find(&users).Error
	return users, err
}

// UpdateTraffic 更新用户流量
func (s *UserService) UpdateTraffic(userID uint, upload, download int64) error {
	return s.db.Model(&model.User{}).
		Where("id = ?", userID).
		Updates(map[string]interface{}{
			"u": gorm.Expr("u + ?", upload),
			"d": gorm.Expr("d + ?", download),
		}).Error
}

// BatchUpdateTraffic 批量更新用户流量
func (s *UserService) BatchUpdateTraffic(traffics map[uint][2]int64) error {
	return s.db.Transaction(func(tx *gorm.DB) error {
		for userID, traffic := range traffics {
			if err := tx.Model(&model.User{}).
				Where("id = ?", userID).
				Updates(map[string]interface{}{
					"u": gorm.Expr("u + ?", traffic[0]),
					"d": gorm.Expr("d + ?", traffic[1]),
				}).Error; err != nil {
				return err
			}
		}
		return nil
	})
}
