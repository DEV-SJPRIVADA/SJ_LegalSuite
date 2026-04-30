<?php

namespace App\Http\Requests\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class TransitionStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transition', $this->route('case'));
    }

    public function rules(): array
    {
        return [
            'to' => ['required', new Enum(CaseStatus::class)],
            'note' => ['nullable', 'string', 'max:2000'],
            'scheduled_at' => ['nullable', 'date'],
            'deadline_at' => ['nullable', 'date'],
            'context' => ['nullable', 'array'],
        ];
    }
}
