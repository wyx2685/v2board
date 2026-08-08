<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GiftcardGenerate extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'generate_count' => 'nullable|integer|max:500',
            'name' => 'required',
            'type' => 'required|in:1,2,3,4,5',
            'value' => ['required_if:type,1,2,3,5', 'nullable', 'integer'],
            'plan_id' => ['required_if:type,5', 'nullable','integer'],
            'started_at' => 'required|integer',
            'ended_at' => 'required|integer',
            'limit_use' => 'nullable|integer',
            'code' => ''
        ];
    }

    public function messages()
    {
        return [
            'generate_count.integer' => __('Generation count must be an integer'),
            'generate_count.max' => __('Generation count cannot exceed 500'),
            'name.required' => __('Name cannot be empty'),
            'type.required' => __('Type cannot be empty'),
            'type.in' => __('Invalid type'),
            'value.required' => __('Value cannot be empty'),
            'value.integer' => __('Value format is invalid'),
            'plan_id.required' => __('Subscription cannot be empty'),
            'started_at.required' => __('Start time cannot be empty'),
            'started_at.integer' => __('Start time format is invalid'),
            'ended_at.required' => __('End time cannot be empty'),
            'ended_at.integer' => __('End time format is invalid'),
            'limit_use.integer' => __('Usage limit format is invalid')
        ];
    }
}
