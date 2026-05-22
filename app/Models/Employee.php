<?php

namespace App\Models;

use App\Enums\EmployeeContractType;
use App\Enums\EmployeeDocumentType;
use App\Enums\EmployeeGender;
use App\Models\Disciplinary\DisciplinaryCase;
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
        'municipality_code',
        'phone',
        'email',
        'hired_at',
        'contract_type',
        'job_title',
        'department_area',
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

    public function disciplinaryCases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
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
