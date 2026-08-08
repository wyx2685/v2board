<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdate extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|email:strict',
            'password' => 'nullable|min:8',
            'transfer_enable' => 'numeric',
            'device_limit' => 'nullable|integer',
            'expired_at' => 'nullable|integer',
            'banned' => 'required|in:0,1',
            'plan_id' => 'nullable|integer',
            'commission_rate' => 'nullable|integer|min:0|max:100',
            'discount' => 'nullable|integer|min:0|max:100',
            'is_admin' => 'required|in:0,1',
            'is_staff' => 'required|in:0,1',
            'u' => 'integer',
            'd' => 'integer',
            'balance' => 'integer',
            'commission_type' => 'integer',
            'commission_balance' => 'integer',
            'remarks' => 'nullable',
            'speed_limit' => 'nullable|integer'
        ];
    }

    public function messages()
    {
        return [
            'email.required' => __('Email can not be empty'),
            'email.email' => __('Email format is incorrect'),
            'transfer_enable.numeric' => __('Traffic allowance format is invalid'),
            'device_limit.integer' => __('Device limit format is invalid'),
            'expired_at.integer' => __('Expiration time format is invalid'),
            'banned.required' => __('Ban status cannot be empty'),
            'banned.in' => __('Ban status format is invalid'),
            'is_admin.required' => __('Administrator status cannot be empty'),
            'is_admin.in' => __('Administrator status format is invalid'),
            'is_staff.required' => __('Staff status cannot be empty'),
            'is_staff.in' => __('Staff status format is invalid'),
            'plan_id.integer' => __('Subscription plan format is invalid'),
            'commission_rate.integer' => __('Referral commission rate format is invalid'),
            'commission_rate.nullable' => __('Referral commission rate format is invalid'),
            'commission_rate.min' => __('Referral commission rate cannot be less than 0'),
            'commission_rate.max' => __('Referral commission rate cannot exceed 100'),
            'discount.integer' => __('Custom discount rate format is invalid'),
            'discount.nullable' => __('Custom discount rate format is invalid'),
            'discount.min' => __('Custom discount rate cannot be less than 0'),
            'discount.max' => __('Custom discount rate cannot exceed 100'),
            'u.integer' => __('Upload traffic format is invalid'),
            'd.integer' => __('Download traffic format is invalid'),
            'balance.integer' => __('Balance format is invalid'),
            'commission_balance.integer' => __('Commission balance format is invalid'),
            'password.min' => __('Password must contain at least 8 characters'),
            'speed_limit.integer' => __('Speed limit format is invalid')
        ];
    }
}
