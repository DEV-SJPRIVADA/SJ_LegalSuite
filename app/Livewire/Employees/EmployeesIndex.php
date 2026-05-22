<?php

namespace App\Livewire\Employees;

use App\Enums\EmployeeContractType;
use App\Enums\EmployeeDocumentType;
use App\Enums\EmployeeGender;
use App\Models\ColombianMunicipality;
use App\Models\Employee;
use App\Services\Employees\EmployeeBulkImportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Empleados · SJ LegalSuite')]
class EmployeesIndex extends Component
{
    use WithFileUploads, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'estado')]
    public string $status = '';

    public int $perPage = 20;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $fullName = '';

    public string $documentType = 'CC';

    public string $documentNumber = '';

    public ?string $birthDate = null;

    public string $gender = '';

    public string $address = '';

    public string $municipalityCode = '';

    public string $phone = '';

    public string $email = '';

    public ?string $hiredAt = null;

    public string $contractType = '';

    public string $jobTitle = '';

    public string $departmentArea = '';

    public string $baseSalary = '';

    public ?string $terminationAt = null;

    public string $emergencyContactName = '';

    public string $emergencyContactPhone = '';

    public bool $isActive = true;

    public bool $showBulkModal = false;

    /** @var mixed */
    public $bulkFile = null;

    /** @var array<int, string> */
    public array $bulkImportErrors = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', Employee::class);
    }

    public function updating($prop): void
    {
        if (in_array($prop, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function updatedContractType(): void
    {
        if ($this->contractType !== EmployeeContractType::TerminoFijo->value) {
            $this->terminationAt = null;
        }
    }

    #[Computed]
    public function municipalitiesGrouped()
    {
        return ColombianMunicipality::groupedByDepartmentForSelect();
    }

    public function openCreate(): void
    {
        Gate::authorize('create', Employee::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $employee = Employee::findOrFail($id);
        Gate::authorize('update', $employee);

        $this->editingId = $employee->id;
        $this->fullName = $employee->full_name;
        $this->documentType = $employee->document_type?->value ?? 'CC';
        $this->documentNumber = $employee->document_number;
        $this->birthDate = $employee->birth_date?->format('Y-m-d');
        $this->gender = $employee->gender?->value ?? '';
        $this->address = (string) ($employee->address ?? '');
        $this->municipalityCode = (string) ($employee->municipality_code ?? '');
        $this->phone = (string) ($employee->phone ?? '');
        $this->email = (string) ($employee->email ?? '');
        $this->hiredAt = $employee->hired_at?->format('Y-m-d');
        $this->contractType = $employee->contract_type?->value ?? '';
        $this->jobTitle = (string) ($employee->job_title ?? '');
        $this->departmentArea = (string) ($employee->department_area ?? '');
        $this->baseSalary = $employee->base_salary !== null ? (string) $employee->base_salary : '';
        $this->terminationAt = $employee->termination_at?->format('Y-m-d');
        $this->emergencyContactName = (string) ($employee->emergency_contact_name ?? '');
        $this->emergencyContactPhone = (string) ($employee->emergency_contact_phone ?? '');
        $this->isActive = (bool) $employee->is_active;
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function openBulk(): void
    {
        Gate::authorize('import', Employee::class);
        $this->bulkFile = null;
        $this->bulkImportErrors = [];
        $this->resetErrorBag();
        $this->showBulkModal = true;
    }

    public function closeBulk(): void
    {
        $this->showBulkModal = false;
        $this->bulkFile = null;
        $this->bulkImportErrors = [];
    }

    public function importBulk(EmployeeBulkImportService $importer): void
    {
        Gate::authorize('import', Employee::class);

        $this->validate([
            'bulkFile' => ['required', 'file', 'max:20480', 'mimes:xlsx'],
        ], [
            'bulkFile.required' => 'Seleccione un archivo Excel (.xlsx).',
            'bulkFile.mimes' => 'Solo se admite formato .xlsx.',
        ]);

        $result = $importer->importFromPath((string) $this->bulkFile->getRealPath());
        $this->bulkImportErrors = $result['errors'];
        $this->bulkFile = null;

        if ($result['inserted'] === 0 && $result['updated'] === 0 && $result['errors'] !== []) {
            $this->addError('bulkFile', 'No se importó ningún registro válido. Revise la plantilla y los errores por fila.');

            return;
        }

        $this->showBulkModal = false;
        session()->flash(
            'success',
            "Carga masiva: {$result['inserted']} nuevos, {$result['updated']} actualizados."
            .(count($result['errors']) > 0 ? ' Algunas filas tuvieron errores.' : '')
        );
        $this->resetPage();
    }

    public function updatedDocumentNumber(string $value): void
    {
        $this->documentNumber = Employee::normalizeDocumentNumber($value);
    }

    public function save(): void
    {
        $this->documentNumber = Employee::normalizeDocumentNumber($this->documentNumber);
        $isFixedTerm = $this->contractType === EmployeeContractType::TerminoFijo->value;

        $rules = [
            'fullName' => ['required', 'string', 'max:200'],
            'documentType' => ['required', Rule::enum(EmployeeDocumentType::class)],
            'documentNumber' => [
                ...Employee::documentNumberRules(),
                Rule::unique('employees', 'document_number')->ignore($this->editingId),
            ],
            'birthDate' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::enum(EmployeeGender::class)],
            'address' => ['nullable', 'string', 'max:255'],
            'municipalityCode' => ['nullable', 'string', 'size:5', 'exists:colombian_municipalities,municipality_code'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:150'],
            'hiredAt' => ['nullable', 'date'],
            'contractType' => ['nullable', Rule::enum(EmployeeContractType::class)],
            'jobTitle' => ['nullable', 'string', 'max:120'],
            'departmentArea' => ['nullable', 'string', 'max:120'],
            'baseSalary' => ['nullable', 'numeric', 'min:0'],
            'terminationAt' => [$isFixedTerm ? 'nullable' : 'prohibited', 'nullable', 'date'],
            'emergencyContactName' => ['nullable', 'string', 'max:150'],
            'emergencyContactPhone' => ['nullable', 'string', 'max:32'],
            'isActive' => ['boolean'],
        ];

        if ($this->editingId) {
            Gate::authorize('update', Employee::findOrFail($this->editingId));
        } else {
            Gate::authorize('create', Employee::class);
        }

        $this->validate($rules);

        [$firstName, $lastName] = Employee::splitFullName($this->fullName);

        $payload = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'document_type' => $this->documentType,
            'document_number' => $this->documentNumber,
            'birth_date' => $this->birthDate ?: null,
            'gender' => $this->gender ?: null,
            'address' => $this->address !== '' ? $this->address : null,
            'municipality_code' => $this->municipalityCode !== '' ? $this->municipalityCode : null,
            'phone' => $this->phone !== '' ? $this->phone : null,
            'email' => $this->email !== '' ? $this->email : null,
            'hired_at' => $this->hiredAt ?: null,
            'contract_type' => $this->contractType ?: null,
            'job_title' => $this->jobTitle !== '' ? $this->jobTitle : null,
            'department_area' => $this->departmentArea !== '' ? $this->departmentArea : null,
            'base_salary' => $this->baseSalary !== '' ? $this->baseSalary : null,
            'termination_at' => $isFixedTerm ? ($this->terminationAt ?: null) : null,
            'emergency_contact_name' => $this->emergencyContactName !== '' ? $this->emergencyContactName : null,
            'emergency_contact_phone' => $this->emergencyContactPhone !== '' ? $this->emergencyContactPhone : null,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            Employee::findOrFail($this->editingId)->update($payload);
            session()->flash('success', 'Empleado actualizado.');
        } else {
            Employee::create($payload);
            session()->flash('success', 'Empleado registrado.');
        }

        $this->closeForm();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status']);
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'fullName', 'documentType', 'documentNumber',
            'birthDate', 'gender', 'address', 'municipalityCode', 'phone', 'email',
            'hiredAt', 'contractType', 'jobTitle', 'departmentArea', 'baseSalary',
            'terminationAt', 'emergencyContactName', 'emergencyContactPhone',
        ]);
        $this->documentType = 'CC';
        $this->isActive = true;
    }

    public function render()
    {
        $query = Employee::query()->with('municipality:municipality_code,municipality_name,department_name');

        if ($this->search !== '') {
            $query->search($this->search);
        }

        if ($this->status === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->status === 'inactivos') {
            $query->where('is_active', false);
        }

        return view('livewire.employees.index', [
            'employees' => $query->orderBy('last_name')->orderBy('first_name')->paginate($this->perPage),
            'documentTypes' => EmployeeDocumentType::options(),
            'genders' => EmployeeGender::options(),
            'contractTypes' => EmployeeContractType::options(),
        ]);
    }
}
