<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)],
            'document_number' => ['nullable', 'string', 'max:32'],
            'phone' => ['nullable', 'string', 'max:32'],
            'organizational_area_id' => ['nullable', 'integer', 'exists:organizational_areas,id'],
            'job_position_id' => ['nullable', 'integer', 'exists:job_positions,id'],
            'position' => ['nullable', 'string', 'max:120'],
            'area' => ['nullable', 'string', 'max:64'],
            'is_active' => ['boolean'],
            'read_only' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ];
    }
}
