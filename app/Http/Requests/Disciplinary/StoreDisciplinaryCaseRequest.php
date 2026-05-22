<?php

namespace App\Http\Requests\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use Illuminate\Foundation\Http\FormRequest;

class StoreDisciplinaryCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', DisciplinaryCase::class);
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'assigned_lawyer_id' => ['nullable', 'integer', 'exists:users,id'],
            'city' => ['nullable', 'string', 'max:100'],
            'municipality_code' => ['nullable', 'string', 'size:5', 'exists:colombian_municipalities,municipality_code'],
            'sede' => ['nullable', 'string', 'max:120'],
            'opened_at' => ['nullable', 'date'],
            'summary' => ['nullable', 'string', 'max:5000'],

            'faults' => ['required', 'array', 'min:1'],
            'faults.*.fault_id' => ['required', 'integer', 'exists:faults,id'],
            'faults.*.extra_info' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
