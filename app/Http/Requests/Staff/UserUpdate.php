<?php

namespace App\Http\Requests\Staff;

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
            'id' => 'required|integer|min:1',
            'password' => 'nullable|string|min:8|max:72',
            'banned' => 'required|in:0,1',
        ];
    }

    public function messages()
    {
        return [
            'banned.required' => __('Ban status cannot be empty'),
            'banned.in' => __('Ban status format is invalid'),
        ];
    }
}
