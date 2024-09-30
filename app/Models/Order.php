<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Order extends Model
{
    protected $table = 'v2_order';
    protected $dateFormat = 'U';
    protected $guarded = ['id'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'surplus_order_ids' => 'array'
    ];

    // رابطه با مدل User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // فعال‌سازی لاگ‌گیری حرفه‌ای برای رویدادهای Eloquent
    protected static function boot()
    {
        parent::boot();

        // هنگام ایجاد یک سفارش جدید
        static::creating(function ($order) {
            Log::info('Creating new order.', ['order' => $order]);
        });

        // هنگام به‌روزرسانی یک سفارش
        static::updating(function ($order) {
            Log::info('Updating order.', ['order' => $order]);
        });

        // هنگام حذف یک سفارش
        static::deleting(function ($order) {
            Log::warning('Deleting order.', ['order' => $order]);
        });

        // هنگام بازیابی یک سفارش حذف شده
        static::restoring(function ($order) {
            Log::info('Restoring order.', ['order' => $order]);
        });
    }

    // نمونه‌سازی از خطاهای احتمالی
    public function save(array $options = [])
    {
        try {
            parent::save($options);
            Log::info('Order saved successfully.', ['order_id' => $this->id]);
        } catch (\Exception $e) {
            Log::error('Error while saving order.', ['error' => $e->getMessage()]);
            throw $e; // برای هندل کردن خطا در جاهای دیگر
        }
    }
}
