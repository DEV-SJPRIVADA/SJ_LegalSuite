<?php

namespace App\Models\Disciplinary;

use App\Enums\Disciplinary\InformeSubmissionStatus;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InformeSubmission extends Model
{
    use SoftDeletes;

    protected $table = 'disciplinary_informe_submissions';

    protected $fillable = [
        'submitted_by',
        'personnel_id',
        'status',
        'storage_disk',
        'storage_path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'checksum_sha256',
        'form_snapshot',
        'summary',
        'reviewed_by',
        'reviewed_at',
        'reviewer_notes',
        'disciplinary_case_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => InformeSubmissionStatus::class,
            'form_snapshot' => 'array',
            'reviewed_at' => 'datetime',
            'size_bytes' => 'integer',
        ];
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function disciplinaryCase(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryCase::class, 'disciplinary_case_id');
    }

    public function scopePendingReview($query)
    {
        return $query->where('status', InformeSubmissionStatus::PENDIENTE_REVISION);
    }
}
