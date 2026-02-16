<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShiprocketCredentialRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $user_id = auth()->id();
        return [
            'SHIPROCKET_EMAIL' => 'required|email|unique:shiprocket_credentials,email,' . $user_id . ',user_id',
            'SHIPROCKET_PASSWORD' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'SHIPROCKET_EMAIL.required' => translate('Shiprocket email is required.'),
            'SHIPROCKET_EMAIL.email'    => translate('Please enter a valid Shiprocket email address.'),
            'SHIPROCKET_EMAIL.unique'   => translate('This Shiprocket email is already in use.'),
            'SHIPROCKET_PASSWORD.required' => translate('Shiprocket password is required.'),
        ];
    }
}
