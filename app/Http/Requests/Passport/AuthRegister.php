<?php

namespace App\Http\Requests\Passport;

use Illuminate\Foundation\Http\FormRequest;

class AuthRegister extends FormRequest
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
            'password' => 'required|min:8',
            'captcha' => 'required|string|size:4',
            'captcha_key' => 'required|string'
        ];
    }

    public function messages()
    {
        return [
            'email.required' => __('Email can not be empty'),
            'email.email' => __('Email format is incorrect'),
            'password.required' => __('Password can not be empty'),
            'password.min' => __('Password must be greater than 8 digits'),
            'captcha.required' => __('Captcha can not be empty'),
            'captcha.size' => __('Captcha must be 4 digits'),
            'captcha_key.required' => __('Captcha key can not be empty')
        ];
    }
}
