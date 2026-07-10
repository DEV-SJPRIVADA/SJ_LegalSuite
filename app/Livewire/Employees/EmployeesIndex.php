<?php

namespace App\Livewire\Employees;

use App\Enums\EmployeeContractType;
use App\Enums\EmployeeScope;
use App\Enums\EmployeeDocumentType;
use App\Enums\EmployeeGender;
use App\Models\ColombianMunicipality;
use App\Models\Employee;
use App\Models\EmployeeJobPosition;
use App\Services\Employees\EmployeeBulkImportService;
use App\Support\Employees\EmployeeBulkImportStore;
use App\Support\Employees\EmployeeImportValueNormalizer;
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

    #[Url(as: 'rol')]
    public string $scopeFilter = '';

    #[Url(as: 'contrato')]
    public string $contractFilter = '';

    #[Url(as: 'pp')]
    public int $perPage = 20;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $fullName = '';

    public string $documentType = 'CC';

    public string $documentNumber = '';

    public ?string $birthDate = null;

    public string $gender = '';

    public string $address = '';

    public string $residenceMunicipalityCode = '';

    public string $residenceDepartmentCode = '';

    public string $municipalityCode = '';

    public string $workDepartmentCode = '';

    public string $phone = '';

    public string $email = '';

    public ?string $hiredAt = null;

    public string $contractType = '';

    public ?int $employeeJobPositionId = null;

    public string $employeeScope = '';

    public string $departmentArea = '';

    public string $baseSalary = '';

    public string $emergencyContactName = '';

    public string $emergencyContactPhone = '';

    public bool $isActive = true;

    public bool $showBulkModal = false;

    /** @var mixed */
    public $bulkFile = null;

    /** @var array<int, string> */
    public array $bulkImportErrors = [];

    public ?string $bulkImportToken = null;

    public bool $bulkImportRunning = false;

    /** @var array<string, mixed> */
    public array $bulkImportProgress = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', Employee::class);
    }

    public function updating($prop): void
    {
        if (in_array($prop, ['search', 'status', 'perPage', 'scopeFilter', 'contractFilter'], true)) {
            $this->resetPage();
        }
    }

    public function updatedPerPage(mixed $value): void
    {
        $allowed = [20, 50, 100];
        $this->perPage = in_array((int) $value, $allowed, true) ? (int) $value : 20;
    }

    public function setStatusFilter(string $value): void
    {
        $this->status = $value;
        $this->resetPage();
    }

    public function applyKpiFilter(string $type): void
    {
        match ($type) {
            'total' => $this->reset(['status', 'scopeFilter', 'contractFilter']),
            'activos' => $this->fill(['status' => 'activos', 'scopeFilter' => '', 'contractFilter' => '']),
            'incompletos' => $this->fill(['status' => 'incompletos', 'scopeFilter' => '', 'contractFilter' => '']),
            'operativos' => $this->fill(['status' => '', 'scopeFilter' => EmployeeScope::Operativo->value, 'contractFilter' => '']),
            'administrativos' => $this->fill(['status' => '', 'scopeFilter' => EmployeeScope::Administrativo->value, 'contractFilter' => '']),
            default => null,
        };

        $this->resetPage();
    }

    public function updatedResidenceMunicipalityCode(string $value): void
    {
        if ($value === '') {
            return;
        }

        $departmentCode = ColombianMunicipality::query()
            ->where('municipality_code', $value)
            ->value('department_code');

        if ($departmentCode) {
            $this->residenceDepartmentCode = (string) $departmentCode;
        }
    }

    public function updatedMunicipalityCode(string $value): void
    {
        if ($value === '') {
            return;
        }

        $departmentCode = ColombianMunicipality::query()
            ->where('municipality_code', $value)
            ->value('department_code');

        if ($departmentCode) {
            $this->workDepartmentCode = (string) $departmentCode;
        }
    }

    public function updatedResidenceDepartmentCode(): void
    {
        if ($this->residenceMunicipalityCode === '') {
            return;
        }

        $departmentCode = ColombianMunicipality::query()
            ->where('municipality_code', $this->residenceMunicipalityCode)
            ->value('department_code');

        if ($departmentCode !== $this->residenceDepartmentCode) {
            $this->residenceMunicipalityCode = '';
        }
    }

    public function updatedWorkDepartmentCode(): void
    {
        if ($this->municipalityCode === '') {
            return;
        }

        $departmentCode = ColombianMunicipality::query()
            ->where('municipality_code', $this->municipalityCode)
            ->value('department_code');

        if ($departmentCode !== $this->workDepartmentCode) {
            $this->municipalityCode = '';
        }
    }

    #[Computed]
    public function departments()
    {
        return ColombianMunicipality::departmentsForSelect();
    }

    #[Computed]
    public function employeeJobPositions()
    {
        $query = EmployeeJobPosition::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($this->employeeScope !== '' && EmployeeScope::tryFrom($this->employeeScope)) {
            $query->where('employee_scope', $this->employeeScope);
        }

        return $query->get(['id', 'name', 'is_guarda', 'employee_scope']);
    }

    #[Computed]
    public function selectedJobPosition(): ?EmployeeJobPosition
    {
        if ($this->employeeJobPositionId === null) {
            return null;
        }

        return EmployeeJobPosition::query()
            ->whereKey($this->employeeJobPositionId)
            ->first(['id', 'name', 'is_guarda', 'employee_scope']);
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function formProfileIssues(): array
    {
        $employee = new Employee([
            'employee_job_position_id' => $this->employeeJobPositionId,
            'employee_scope' => $this->employeeScope !== '' ? $this->employeeScope : null,
            'hired_at' => $this->hiredAt,
            'contract_type' => $this->contractType !== '' ? $this->contractType : null,
            'residence_municipality_code' => $this->residenceMunicipalityCode !== '' ? $this->residenceMunicipalityCode : null,
            'residence_department_code' => $this->residenceDepartmentCode !== '' ? $this->residenceDepartmentCode : null,
            'municipality_code' => $this->municipalityCode !== '' ? $this->municipalityCode : null,
            'work_department_code' => $this->workDepartmentCode !== '' ? $this->workDepartmentCode : null,
        ]);

        $position = $this->selectedJobPosition;
        if ($position instanceof EmployeeJobPosition) {
            $employee->setRelation('employeeJobPosition', $position);
        }

        return $employee->profileCompletionIssues();
    }

    #[Computed]
    public function formProfileComplete(): bool
    {
        return $this->formProfileIssues === [];
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
        $this->residenceMunicipalityCode = (string) ($employee->residence_municipality_code ?? '');
        $this->residenceDepartmentCode = (string) ($employee->residence_department_code ?? '');
        $this->municipalityCode = (string) ($employee->municipality_code ?? '');
        $this->workDepartmentCode = (string) ($employee->work_department_code ?? '');
        $this->phone = (string) ($employee->phone ?? '');
        $this->email = (string) ($employee->email ?? '');
        $this->hiredAt = $employee->hired_at?->format('Y-m-d');
        $this->contractType = $employee->contract_type?->value ?? '';
        $this->employeeJobPositionId = $employee->employee_job_position_id;
        $this->employeeScope = (string) ($employee->employee_scope?->value ?? '');
        $this->departmentArea = (string) ($employee->department_area ?? '');
        $this->baseSalary = $employee->base_salary !== null ? (string) $employee->base_salary : '';
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
        $this->resetBulkImportState();
        $this->resetErrorBag();
        $this->showBulkModal = true;
    }

    public function closeBulk(): void
    {
        if ($this->bulkImportRunning) {
            return;
        }

        $this->showBulkModal = false;
        $this->bulkFile = null;
        $this->bulkImportErrors = [];
        $this->resetBulkImportState();
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

        $sourcePath = (string) $this->bulkFile->getRealPath();
        if ($sourcePath === '' || ! is_readable($sourcePath)) {
            $this->addError('bulkFile', 'No se pudo leer el archivo subido. Intente seleccionarlo de nuevo.');

            return;
        }

        try {
            $token = EmployeeBulkImportStore::createFromUploadedFile(
                (int) auth()->id(),
                $sourcePath,
            );
        } catch (\Throwable $e) {
            $this->addError('bulkFile', $e->getMessage());

            return;
        }

        $this->bulkImportToken = $token;
        $this->bulkImportRunning = true;
        $this->bulkImportProgress = [
            'status' => EmployeeBulkImportStore::STATUS_PENDING,
            'percent' => 0,
            'processed_rows' => 0,
            'total_rows' => 0,
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors_count' => 0,
            'eta_seconds' => null,
            'eta_label' => 'Preparando importación…',
            'phase_label' => 'Validando archivo…',
        ];
        $this->bulkFile = null;
        $this->resetErrorBag();
    }

    public function advanceBulkImport(EmployeeBulkImportService $importer): void
    {
        if (! $this->bulkImportRunning || $this->bulkImportToken === null) {
            return;
        }

        if (! EmployeeBulkImportStore::belongsToUser($this->bulkImportToken, (int) auth()->id())) {
            $this->resetBulkImportState();

            return;
        }

        @set_time_limit(120);

        $lock = EmployeeBulkImportStore::acquireAdvanceLock($this->bulkImportToken);
        if ($lock === null) {
            return;
        }

        try {
            $importer->advanceImport(
                $this->bulkImportToken,
                EmployeeBulkImportService::BATCH_SIZE,
                1,
            );
        } catch (\Throwable $e) {
            EmployeeBulkImportStore::markFailed($this->bulkImportToken, $e->getMessage());
            $this->addError('bulkFile', $e->getMessage());
            EmployeeBulkImportStore::delete($this->bulkImportToken);
            $this->resetBulkImportState();

            return;
        } finally {
            $lock->release();
        }

        $this->bulkImportProgress = $importer->progressSnapshot($this->bulkImportToken);
        $status = (string) ($this->bulkImportProgress['status'] ?? '');

        if ($status === EmployeeBulkImportStore::STATUS_COMPLETED) {
            $this->finishBulkImport();
        }

        if ($status === EmployeeBulkImportStore::STATUS_FAILED) {
            $meta = EmployeeBulkImportStore::meta($this->bulkImportToken);
            $this->addError('bulkFile', (string) ($meta['error'] ?? 'La importación falló.'));
            EmployeeBulkImportStore::delete($this->bulkImportToken);
            $this->resetBulkImportState();
        }
    }

    private function finishBulkImport(): void
    {
        $token = $this->bulkImportToken;
        if ($token === null) {
            $this->resetBulkImportState();

            return;
        }

        $importer = app(EmployeeBulkImportService::class);
        $result = $importer->resultFromToken($token);
        $this->bulkImportErrors = $result['errors'];
        $this->bulkImportProgress = $importer->progressSnapshot($token);

        EmployeeBulkImportStore::delete($token);
        $this->dispatch('bulk-import-finished');
        $this->resetBulkImportState();

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

    private function resetBulkImportState(): void
    {
        $this->bulkImportToken = null;
        $this->bulkImportRunning = false;
        $this->bulkImportProgress = [];
    }

    public function updatedDocumentNumber(string $value): void
    {
        $this->documentNumber = Employee::normalizeDocumentNumber($value);
    }

    public function save(): void
    {
        $this->documentNumber = Employee::normalizeDocumentNumber($this->documentNumber);
        $this->phone = EmployeeImportValueNormalizer::nullableContact($this->phone) ?? '';
        $this->email = EmployeeImportValueNormalizer::nullableContact($this->email) ?? '';
        $this->emergencyContactName = EmployeeImportValueNormalizer::nullableContact($this->emergencyContactName) ?? '';
        $this->emergencyContactPhone = EmployeeImportValueNormalizer::nullableContact($this->emergencyContactPhone) ?? '';

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
            'residenceMunicipalityCode' => ['nullable', 'string', 'size:5', 'exists:colombian_municipalities,municipality_code'],
            'residenceDepartmentCode' => ['nullable', 'string', 'size:2'],
            'municipalityCode' => ['nullable', 'string', 'size:5', 'exists:colombian_municipalities,municipality_code'],
            'workDepartmentCode' => ['nullable', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:150'],
            'hiredAt' => ['required', 'date'],
            'contractType' => ['required', Rule::enum(EmployeeContractType::class)],
            'employeeJobPositionId' => ['required', 'integer', 'exists:employee_job_positions,id'],
            'employeeScope' => ['required', Rule::enum(EmployeeScope::class)],
            'departmentArea' => ['nullable', 'string', 'max:120'],
            'baseSalary' => ['nullable', 'numeric', 'min:0'],
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

        if (! filled($this->residenceMunicipalityCode) && ! filled($this->residenceDepartmentCode)) {
            $this->addError('residenceDepartmentCode', 'Indique departamento o municipio de residencia.');

            return;
        }

        if (! filled($this->municipalityCode) && ! filled($this->workDepartmentCode)) {
            $this->addError('workDepartmentCode', 'Indique departamento o municipio de labor.');

            return;
        }

        $position = EmployeeJobPosition::query()->findOrFail($this->employeeJobPositionId);

        if ($position->is_guarda && ! filled($this->municipalityCode)) {
            $this->addError('municipalityCode', 'Los cargos de guarda requieren municipio de labor.');

            return;
        }

        [$firstName, $lastName] = Employee::splitFullName($this->fullName);

        if ($this->employeeScope !== $position->employee_scope?->value) {
            $this->addError('employeeScope', 'El rol empleado no coincide con el cargo seleccionado.');

            return;
        }

        $payload = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'document_type' => $this->documentType,
            'document_number' => $this->documentNumber,
            'birth_date' => $this->birthDate ?: null,
            'gender' => $this->gender ?: null,
            'address' => $this->address !== '' ? $this->address : null,
            'residence_municipality_code' => $this->residenceMunicipalityCode !== '' ? $this->residenceMunicipalityCode : null,
            'residence_department_code' => $this->residenceDepartmentCode !== '' ? $this->residenceDepartmentCode : null,
            'municipality_code' => $this->municipalityCode !== '' ? $this->municipalityCode : null,
            'work_department_code' => $this->workDepartmentCode !== '' ? $this->workDepartmentCode : null,
            'phone' => $this->phone !== '' ? $this->phone : null,
            'email' => $this->email !== '' ? $this->email : null,
            'hired_at' => $this->hiredAt,
            'contract_type' => $this->contractType,
            'employee_job_position_id' => $position->id,
            'job_title' => $position->name,
            'employee_scope' => $this->employeeScope,
            'department_area' => EmployeeScope::from($this->employeeScope)->label(),
            'base_salary' => $this->baseSalary !== '' ? $this->baseSalary : null,
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

    #[Computed]
    public function incompleteProfilesCount(): int
    {
        return Employee::query()->profileIncomplete()->count();
    }

    #[Computed]
    public function kpiTotal(): int
    {
        return Employee::query()->count();
    }

    #[Computed]
    public function kpiActive(): int
    {
        return Employee::query()->where('is_active', true)->count();
    }

    #[Computed]
    public function kpiOperativo(): int
    {
        return Employee::query()->where('employee_scope', EmployeeScope::Operativo)->count();
    }

    #[Computed]
    public function kpiAdministrativo(): int
    {
        return Employee::query()->where('employee_scope', EmployeeScope::Administrativo)->count();
    }

    public function filterIncompleteProfiles(): void
    {
        $this->status = 'incompletos';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'scopeFilter', 'contractFilter']);
        $this->resetPage();
    }

    public function updatedEmployeeJobPositionId(?int $value): void
    {
        if (! $value) {
            return;
        }

        $position = EmployeeJobPosition::query()->find($value, ['id', 'employee_scope']);
        if ($position?->employee_scope) {
            $this->employeeScope = (string) $position->employee_scope->value;
        }
    }

    public function updatedEmployeeScope(string $value): void
    {
        if ($this->employeeJobPositionId === null || $value === '') {
            return;
        }

        $position = EmployeeJobPosition::query()->find($this->employeeJobPositionId, ['id', 'employee_scope']);
        if ($position?->employee_scope?->value !== $value) {
            $this->employeeJobPositionId = null;
        }
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'fullName', 'documentType', 'documentNumber',
            'birthDate', 'gender', 'address', 'residenceMunicipalityCode', 'residenceDepartmentCode',
            'municipalityCode', 'workDepartmentCode', 'phone', 'email',
            'hiredAt', 'contractType', 'employeeJobPositionId', 'employeeScope', 'departmentArea', 'baseSalary',
            'emergencyContactName', 'emergencyContactPhone',
        ]);
        $this->documentType = 'CC';
        $this->isActive = true;
    }

    public function render()
    {
        $query = Employee::query()->with([
            'municipality:municipality_code,municipality_name,department_name',
            'residenceMunicipality:municipality_code,municipality_name,department_name',
            'employeeJobPosition:id,name,is_guarda,employee_scope',
        ]);

        if ($this->search !== '') {
            $query->search($this->search);
        }

        if ($this->status === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->status === 'inactivos') {
            $query->where('is_active', false);
        } elseif ($this->status === 'incompletos') {
            $query->profileIncomplete();
        }

        if ($this->scopeFilter !== '' && EmployeeScope::tryFrom($this->scopeFilter)) {
            $query->where('employee_scope', $this->scopeFilter);
        }

        if ($this->contractFilter !== '' && EmployeeContractType::tryFrom($this->contractFilter)) {
            $query->where('contract_type', $this->contractFilter);
        }

        $employees = $query->orderBy('last_name')->orderBy('first_name')->paginate($this->perPage);

        return view('livewire.employees.index', [
            'employees' => $employees,
            'incompleteProfilesCount' => $this->incompleteProfilesCount,
            'kpiTotal' => $this->kpiTotal,
            'kpiActive' => $this->kpiActive,
            'kpiOperativo' => $this->kpiOperativo,
            'kpiAdministrativo' => $this->kpiAdministrativo,
            'documentTypes' => EmployeeDocumentType::options(),
            'genders' => EmployeeGender::options(),
            'contractTypes' => EmployeeContractType::options(),
            'employeeScopes' => EmployeeScope::options(),
        ]);
    }
}
