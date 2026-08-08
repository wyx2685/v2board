<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderFetch extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'filter.*.key' => 'required|in:email,invite_user_email,trade_no,status,commission_status,user_id,invite_user_id,plan_id,callback_no,commission_balance',
            'filter.*.condition' => ['required', Rule::in(AdminFilter::allowedConditions())],
            'filter.*.value' => ''
        ];
    }

    public function messages()
    {
        return [
            'filter.*.key.required' => __('Filter field cannot be empty'),
            'filter.*.key.in' => __('Invalid filter field'),
            'filter.*.condition.required' => __('Filter condition cannot be empty'),
            'filter.*.condition.in' => __('Invalid filter condition'),
        ];
    }
}
