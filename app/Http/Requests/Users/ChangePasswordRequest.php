<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('changePassword', $this->route('user'));
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ];
    }
}
