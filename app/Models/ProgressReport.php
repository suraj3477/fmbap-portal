<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgressReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheme_id',
        'user_id',
        'state',
        'reporting_period',
        'physical_progress_pct',
        'physical_progress_description',
        'financial_progress_pct',
        'financial_progress_description',
        'narrative_report',
        'progress_report_doc_path',
        'status',
        'bb_remarks',
        'bb_reviewed_at',
        'mojs_remarks',
        'mojs_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'physical_progress_pct'  => 'decimal:2',
            'financial_progress_pct' => 'decimal:2',
            'bb_reviewed_at'         => 'datetime',
            'mojs_reviewed_at'       => 'datetime',
        ];
    }

    // ── Status constants ──────────────────────────────────────────────────────
    const STATUS_SUBMITTED         = 'SUBMITTED';
    const STATUS_REVIEWED_BY_BB    = 'REVIEWED_BY_BB';
    const STATUS_REVIEWED_BY_MOJS  = 'REVIEWED_BY_MOJS';
    const STATUS_NEEDS_CORRECTION  = 'NEEDS_CORRECTION';
    const STATUS_APPROVED          = 'APPROVED';

    // ── Relations ─────────────────────────────────────────────────────────────
    public function scheme()
    {
        return $this->belongsTo(Scheme::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }

    // ── Scopes ────────────────────────────────────────────────────────────────
    public function scopeForUser($query, User $user)
    {
        if ($user->isState()) {
            return $query->where('state', $user->state);
        }
        return $query;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_NEEDS_CORRECTION]);
    }
}
