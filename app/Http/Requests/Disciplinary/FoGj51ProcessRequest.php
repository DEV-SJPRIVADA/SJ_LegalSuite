<?php

namespace App\Http\Requests\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\InformeSubmission;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FoGj51ProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if ((string) $this->input('fo51_action') === 'pdf') {
            return $user->can('disciplinary.download-pdf')
                || $user->can('create', DisciplinaryCase::class)
                || $user->can('generateFo51Inform', DisciplinaryCase::class);
        }

        return $user->can('submit', InformeSubmission::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(StoreFoGj51InformePdfRequest::fieldRules(), [
            'fo51_action' => ['required', Rule::in(['pdf', 'enviar', 'cargar'])],

            /* Informe digitado desde pantalla: identidad debe coincidir con el PDF generado */
            'fo51_worker_name' => [
                Rule::requiredIf(fn () => (string) $this->input('fo51_action') === 'enviar'),
                'nullable',
                'string',
                'max:500',
            ],
            'fo51_worker_document' => [
                Rule::requiredIf(fn () => (string) $this->input('fo51_action') === 'enviar'),
                'nullable',
                'string',
                'max:32',
            ],

            /* PDF externo: el sistema no extrae texto; debe capturarse a mano */
            'informe_worker_name' => [
                Rule::requiredIf(fn () => (string) $this->input('fo51_action') === 'cargar'),
                'nullable',
                'string',
                'max:500',
            ],
            'informe_worker_document' => [
                Rule::requiredIf(fn () => (string) $this->input('fo51_action') === 'cargar'),
                'nullable',
                'string',
                'max:32',
            ],

            'informe_file' => [
                Rule::requiredIf(fn () => (string) $this->input('fo51_action') === 'cargar'),
                'nullable',
                'file',
                'mimetypes:application/pdf',
                'max:15360',
            ],

            'evidence_images' => ['nullable', 'array', 'max:10'],
            'evidence_images.*' => ['nullable', 'image', 'max:5120'],
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'fo51_fault_other_chk' => $this->boolean('fo51_fault_other_chk'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes(
            'fo51_municipality_code',
            ['required', 'string', 'size:5', Rule::exists('colombian_municipalities', 'municipality_code')],
            fn () => in_array((string) $this->input('fo51_action'), ['pdf', 'enviar'], true)
        );
    }

    protected function failedValidation(Validator $validator): void
    {
        $action = (string) $this->input('fo51_action');
        $params = ['informe_modal' => 1];

        if ($action === 'cargar') {
            $params['cargar_pdf'] = 1;
        }

        throw (new ValidationException($validator))
            ->redirectTo(route('disciplinary.cases.index', $params));
    }
}
