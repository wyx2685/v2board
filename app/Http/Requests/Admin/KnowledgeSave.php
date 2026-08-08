<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class KnowledgeSave extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'category' => 'required',
            'language' => 'required',
            'title' => 'required',
            'body' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'title.required' => __('Title cannot be empty'),
            'category.required' => __('Category cannot be empty'),
            'body.required' => __('Content cannot be empty'),
            'language.required' => __('Language cannot be empty')
        ];
    }
}
