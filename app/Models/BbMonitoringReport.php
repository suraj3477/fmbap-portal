<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BbMonitoringReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_request_id',
        'bb_user_id',
        'inspection_date',
        'site_description',
        'bb_physical_progress_pct',
        'bb_physical_progress_description',
        'bb_financial_progress_pct',
        'bb_financial_progress_description',
        'bb_report_doc_path',
        'geo_tagged_files',
        'status',
        'remarks',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'geo_tagged_files'            => 'array',
            'inspection_date'             => 'date',
            'submitted_at'                => 'datetime',
            'bb_physical_progress_pct'    => 'decimal:2',
            'bb_financial_progress_pct'   => 'decimal:2',
        ];
    }

    // ── Relations ─────────────────────────────────────────────────────────────
    public function paymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    public function bbUser()
    {
        return $this->belongsTo(User::class, 'bb_user_id');
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    public function isDraft(): bool
    {
        return $this->status === 'DRAFT';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'SUBMITTED';
    }

    /**
     * Returns only photo-type geo-tagged files.
     */
    public function getPhotosAttribute(): array
    {
        return collect($this->geo_tagged_files ?? [])
            ->filter(fn($f) => ($f['type'] ?? '') === 'photo')
            ->values()
            ->toArray();
    }

    /**
     * Returns only video-type geo-tagged files.
     */
    public function getVideosAttribute(): array
    {
        return collect($this->geo_tagged_files ?? [])
            ->filter(fn($f) => ($f['type'] ?? '') === 'video')
            ->values()
            ->toArray();
    }
}
