<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PlanSave extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'content' => '',
            'group_id' => 'required',
            'transfer_enable' => 'required',
            'device_limit' => 'nullable|integer',
            'month_price' => 'nullable|integer',
            'quarter_price' => 'nullable|integer',
            'half_year_price' => 'nullable|integer',
            'year_price' => 'nullable|integer',
            'two_year_price' => 'nullable|integer',
            'three_year_price' => 'nullable|integer',
            'onetime_price' => 'nullable|integer',
            'reset_price' => 'nullable|integer',
            'reset_traffic_method' => 'nullable|integer|in:0,1,2,3,4',
            'capacity_limit' => 'nullable|integer',
            'speed_limit' => 'nullable|integer'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('Subscription plan name cannot be empty'),
            'type.required' => __('Subscription plan type cannot be empty'),
            'type.in' => __('Subscription plan type format is invalid'),
            'group_id.required' => __('Permission group cannot be empty'),
            'transfer_enable.required' => __('Traffic allowance cannot be empty'),
            'device_limit.integer' => __('Device limit format is invalid'),
            'month_price.integer' => __('Monthly price format is invalid'),
            'quarter_price.integer' => __('Quarterly price format is invalid'),
            'half_year_price.integer' => __('Semiannual price format is invalid'),
            'year_price.integer' => __('Annual price format is invalid'),
            'two_year_price.integer' => __('Two-year price format is invalid'),
            'three_year_price.integer' => __('Three-year price format is invalid'),
            'onetime_price.integer' => __('One-time price format is invalid'),
            'reset_price.integer' => __('Data reset package price format is invalid'),
            'reset_traffic_method.integer' => __('Traffic reset method format is invalid'),
            'reset_traffic_method.in' => __('Traffic reset method format is invalid'),
            'capacity_limit.integer' => __('User capacity limit format is invalid'),
            'speed_limit.integer' => __('Speed limit format is invalid')
        ];
    }
}
