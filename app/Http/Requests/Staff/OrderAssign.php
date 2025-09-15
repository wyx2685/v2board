<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class OrderAssign extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'plan_id' => 'required|integer',
            'email' => 'required|email',
            'period' => 'required|in:month_price,quarter_price,half_year_price,year_price,two_year_price,three_year_price,onetime_price,reset_price'
        ];
    }

    public function messages()
    {
        return [
            'plan_id.required' => 'Plan ID cannot be empty',
            'plan_id.integer' => 'Plan ID must be a number',
            'email.required' => 'Email cannot be empty', 
            'email.email' => 'Email format is invalid',
            'period.required' => 'Period cannot be empty',
            'period.in' => 'Invalid period format'
        ];
    }
}
