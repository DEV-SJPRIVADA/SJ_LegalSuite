<?php

namespace App\Models;

use App\Enums\EmployeeContractType;
use App\Enums\EmployeeDocumentType;
use App\Enums\EmployeeGender;
use App\Enums\EmployeeScope;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Support\Disciplinary\WorkerLegalPhrasing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'document_type',
        'document_number',
        'birth_date',
        'gender',
        'address',
        'residence_municipality_code',
        'residence_department_code',
        'municipality_code',
        'work_department_code',
        'phone',
        'email',
        'hired_at',
        'contract_type',
        'job_title',
        'employee_job_position_id',
        'department_area',
        'employee_scope',
        'base_salary',
        'termination_at',
        'emergency_contact_name',
        'emergency_contact_phone',
        'is_active',
        'external_id',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => EmployeeDocumentType::class,
            'gender' => EmployeeGender::class,
            'contract_type' => EmployeeContractType::class,
            'employee_scope' => EmployeeScope::class,
            'birth_date' => 'date',
            'hired_at' => 'date',
            'termination_at' => 'date',
            'base_salary' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(ColombianMunicipality::class, 'municipality_code', 'municipality_code');
    }

    public function residenceMunicipality(): BelongsTo
    {
        return $this->belongsTo(ColombianMunicipality::class, 'residence_municipality_code', 'municipality_code');
    }

    public function employeeJobPosition(): BelongsTo
    {
        return $this->belongsTo(EmployeeJobPosition::class);
    }

    public function isGuardaCargo(): bool
    {
        $this->loadMissing('employeeJobPosition');

        return (bool) $this->employeeJobPosition?->is_guarda;
    }

    public function syncJobTitleFromPosition(): void
    {
        $this->loadMissing('employeeJobPosition');

        if ($this->employeeJobPosition?->name) {
            $this->job_title = $this->employeeJobPosition->name;
        }
    }

    public function disciplinaryCases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function displayName(): string
    {
        $name = trim($this->full_name);
        if ($name === '' || $name === '-') {
            return '—';
        }

        if (mb_strtoupper($name, 'UTF-8') === $name) {
            return mb_convert_case(mb_strtolower($name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        return $name;
    }

    public function legalPhrasing(): WorkerLegalPhrasing
    {
        return WorkerLegalPhrasing::fromGender($this->gender);
    }

    public function initials(): string
    {
        $first = mb_substr(trim((string) $this->first_name), 0, 1);
        $last = mb_substr(trim((string) $this->last_name), 0, 1);

        if ($first === '' && $last === '') {
            return '?';
        }

        if ($last === '' || $last === '-') {
            $parts = preg_split('/\s+/u', trim((string) $this->first_name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            return mb_strtoupper(mb_substr($parts[0] ?? '?', 0, 1).mb_substr($parts[1] ?? '', 0, 1), 'UTF-8');
        }

        return mb_strtoupper($first.$last, 'UTF-8');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return list<string>
     */
    public function profileCompletionIssues(): array
    {
        $issues = [];

        if ($this->employee_job_position_id === null) {
            $issues[] = 'Cargo';
        }

        if ($this->employee_scope === null) {
            $issues[] = 'Rol empleado';
        }

        if ($this->hired_at === null) {
            $issues[] = 'Fecha de ingreso';
        }

        if ($this->contract_type === null) {
            $issues[] = 'Tipo de contrato';
        }

        if (! $this->hasResidenceTerritory()) {
            $issues[] = 'Territorio de residencia';
        }

        if (! $this->hasWorkTerritory()) {
            $issues[] = 'Territorio de labor';
        }

        if ($this->isGuardaCargo() && ! filled($this->municipality_code)) {
            $issues[] = 'Municipio de labor (cargo guarda)';
        }

        return $issues;
    }

    public function isProfileComplete(): bool
    {
        return $this->profileCompletionIssues() === [];
    }

    public function workTerritoryLabel(): string
    {
        if ($this->relationLoaded('municipality') && $this->municipality) {
            return (string) $this->municipality->municipality_name;
        }

        if (filled($this->municipality_code)) {
            $this->loadMissing('municipality');

            return (string) ($this->municipality?->municipality_name ?? $this->municipality_code);
        }

        if (filled($this->work_department_code)) {
            return (string) (ColombianMunicipality::departmentName($this->work_department_code) ?? $this->work_department_code);
        }

        return '—';
    }

    public function residenceTerritoryLabel(): string
    {
        if ($this->relationLoaded('residenceMunicipality') && $this->residenceMunicipality) {
            return (string) $this->residenceMunicipality->municipality_name;
        }

        if (filled($this->residence_municipality_code)) {
            $this->loadMissing('residenceMunicipality');

            return (string) ($this->residenceMunicipality?->municipality_name ?? $this->residence_municipality_code);
        }

        if (filled($this->residence_department_code)) {
            return (string) (ColombianMunicipality::departmentName($this->residence_department_code) ?? $this->residence_department_code);
        }

        return '—';
    }

    public function hasResidenceTerritory(): bool
    {
        return filled($this->residence_municipality_code) || filled($this->residence_department_code);
    }

    public function hasWorkTerritory(): bool
    {
        return filled($this->municipality_code) || filled($this->work_department_code);
    }

    public function scopeProfileComplete(Builder $query): Builder
    {
        return $query
            ->whereNotNull('employee_job_position_id')
            ->whereNotNull('employee_scope')
            ->whereNotNull('hired_at')
            ->whereNotNull('contract_type')
            ->where(function (Builder $inner): void {
                $inner->where(function (Builder $residence): void {
                    $residence->whereNotNull('residence_municipality_code')
                        ->where('residence_municipality_code', '!=', '')
                        ->orWhereNotNull('residence_department_code')
                        ->where('residence_department_code', '!=', '');
                })->where(function (Builder $work): void {
                    $work->whereNotNull('municipality_code')
                        ->where('municipality_code', '!=', '')
                        ->orWhereNotNull('work_department_code')
                        ->where('work_department_code', '!=', '');
                });
            })
            ->where(function (Builder $inner): void {
                $inner->whereDoesntHave('employeeJobPosition', fn (Builder $position) => $position->where('is_guarda', true))
                    ->orWhere(function (Builder $municipality): void {
                        $municipality->whereNotNull('municipality_code')
                            ->where('municipality_code', '!=', '');
                    });
            });
    }

    public function scopeProfileIncomplete(Builder $query): Builder
    {
        return $query->where(function (Builder $inner): void {
            $inner->whereNull('employee_job_position_id')
                ->orWhereNull('employee_scope')
                ->orWhereNull('hired_at')
                ->orWhereNull('contract_type')
                ->orWhere(function (Builder $residence): void {
                    $residence->where(function (Builder $empty): void {
                        $empty->whereNull('residence_municipality_code')
                            ->orWhere('residence_municipality_code', '=', '');
                    })->where(function (Builder $empty): void {
                        $empty->whereNull('residence_department_code')
                            ->orWhere('residence_department_code', '=', '');
                    });
                })
                ->orWhere(function (Builder $work): void {
                    $work->where(function (Builder $empty): void {
                        $empty->whereNull('municipality_code')
                            ->orWhere('municipality_code', '=', '');
                    })->where(function (Builder $empty): void {
                        $empty->whereNull('work_department_code')
                            ->orWhere('work_department_code', '=', '');
                    });
                })
                ->orWhere(function (Builder $guarda): void {
                    $guarda->whereHas('employeeJobPosition', fn (Builder $position) => $position->where('is_guarda', true))
                        ->where(function (Builder $empty): void {
                            $empty->whereNull('municipality_code')
                                ->orWhere('municipality_code', '=', '');
                        });
                });
        });
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.str_replace(' ', '%', trim($term)).'%';

        return $query->where(function (Builder $q) use ($like, $term) {
            $q->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('document_number', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('job_title', 'like', $like);

            $digits = preg_replace('/\D+/', '', $term) ?? '';
            if ($digits !== '') {
                $q->orWhere('document_number', 'like', '%'.$digits.'%');
            }
        });
    }

    /** Solo dígitos: sin puntos, espacios ni caracteres especiales. */
    public static function normalizeDocumentNumber(string $documentNumber): string
    {
        return preg_replace('/\D+/', '', trim($documentNumber)) ?? '';
    }

    /** @return list<string> */
    public static function documentNumberRules(bool $required = true): array
    {
        $rules = ['string', 'regex:/^\d+$/', 'min:5', 'max:15'];
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * @return array{0: string, 1: string} [nombres, apellidos]
     */
    public static function splitFullName(string $fullName): array
    {
        $name = trim(preg_replace('/\s+/u', ' ', $fullName) ?? '');
        if ($name === '') {
            throw new \InvalidArgumentException('El nombre completo es obligatorio.');
        }

        $tokens = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($tokens === []) {
            throw new \InvalidArgumentException('El nombre completo no es válido.');
        }

        if (count($tokens) === 1) {
            return [$tokens[0], '-'];
        }

        $lastName = array_pop($tokens);

        return [implode(' ', $tokens), $lastName];
    }
}
