<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class WebconSave extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required',
            'domain' => 'required',
            'plan_id' => 'nullable',
            'plan_ids' => 'nullable',
            'title' => 'nullable',
            'description' => 'nullable',
            'logo' => 'nullable|url',
            'background_url' => 'nullable|url',
            'custom_html' => 'nullable'
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'Email admin không được bỏ trống',
            'domain.required' => 'domain không được bỏ trống',
            'logo.url' => 'Logo phải là url',
            'background_url.url' => 'Hình nền phải là Url'
        ];
    }
}
