<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserRedeemGiftCard extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'giftcard' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'giftcard.required' => __('Giftcard cannot be empty')
        ];
    }
}
