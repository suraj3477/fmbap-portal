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
        'reporting_month',
        'reporting_year',
        'physical_progress_pct',
        'physical_progress_description',
        'physical_breakup',
        'financial_progress_pct',
        'financial_progress_description',
        'narrative_report',
        'progress_report_doc_path',
        'geo_tagged_files',
        'video_url',
        'last_payment_request_id',
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
            'reporting_month'        => 'integer',
            'reporting_year'         => 'integer',
            'physical_breakup'       => 'array',
            'geo_tagged_files'       => 'array',
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

    public function lastPaymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class, 'last_payment_request_id');
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

    public function getPhotosAttribute(): array
    {
        return collect($this->geo_tagged_files ?? [])
            ->filter(fn($f) => ($f['type'] ?? '') === 'photo')
            ->values()
            ->toArray();
    }

    public function getVideosAttribute(): array
    {
        return collect($this->geo_tagged_files ?? [])
            ->filter(fn($f) => ($f['type'] ?? '') === 'video')
            ->values()
            ->toArray();
    }

    public function getReportingMonthNameAttribute(): string
    {
        if ($this->reporting_month && $this->reporting_month >= 1 && $this->reporting_month <= 12) {
            $monthName = date('F', mktime(0, 0, 0, $this->reporting_month, 10));
            return $this->reporting_year ? "{$monthName} {$this->reporting_year}" : $monthName;
        }
        return $this->reporting_period ?? '';
    }
}
