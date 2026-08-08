<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserFetch extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'filter.*.key' => 'required|in:id,email,transfer_enable,device_limit,d,expired_at,uuid,token,invite_by_email,invite_user_id,plan_id,banned,remarks,is_admin',
            'filter.*.condition' => ['required', Rule::in(AdminFilter::allowedConditions())],
            'filter.*.value' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'filter.*.key.required' => __('Filter field cannot be empty'),
            'filter.*.key.in' => __('Invalid filter field'),
            'filter.*.condition.required' => __('Filter condition cannot be empty'),
            'filter.*.condition.in' => __('Invalid filter condition'),
            'filter.*.value.required' => __('Filter value cannot be empty')
        ];
    }
}
