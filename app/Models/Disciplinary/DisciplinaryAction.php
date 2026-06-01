<?php

namespace App\Models\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit log inmutable. NO debe editarse después de creado.
 */
class DisciplinaryAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'disciplinary_case_id',
        'informe_submission_id',
        'disciplinary_stage_id',
        'user_id',
        'action_type',
        'from_status',
        'to_status',
        'description',
        'metadata',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'action_type' => ActionType::class,
            'from_status' => CaseStatus::class,
            'to_status' => CaseStatus::class,
            'performed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryCase::class, 'disciplinary_case_id');
    }

    public function informeSubmission(): BelongsTo
    {
        return $this->belongsTo(InformeSubmission::class, 'informe_submission_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryStage::class, 'disciplinary_stage_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
