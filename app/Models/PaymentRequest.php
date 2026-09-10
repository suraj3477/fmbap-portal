<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheme_id',
        'user_id',
        'state',
        'status',
        // Step 2
        'requested_amount_cr',
        'instalment_number',
        'bank_details',
        'state_remarks',
        // Step 3
        'physical_progress_pct',
        'physical_progress_description',
        'financial_progress_pct',
        'financial_progress_description',
        'narrative_progress_report',
        // Step 4
        'utilization_certificate_path',
        'voucher_doc_paths',
        'progress_report_doc_path',
        'additional_doc_paths',
        // BB
        'bb_decision',
        'bb_remarks',
        // MoJS
        'mojs_decision',
        'mojs_remarks',
        // Timestamps
        'submitted_at',
        'forwarded_to_mojs_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'voucher_doc_paths'     => 'array',
            'additional_doc_paths'  => 'array',
            'submitted_at'          => 'datetime',
            'forwarded_to_mojs_at'  => 'datetime',
            'approved_at'           => 'datetime',
            'requested_amount_cr'   => 'decimal:2',
            'physical_progress_pct' => 'decimal:2',
            'financial_progress_pct'=> 'decimal:2',
        ];
    }

    // ── Workflow status constants ─────────────────────────────────────────────
    const STATUS_DRAFT               = 'DRAFT';
    const STATUS_SUBMITTED_TO_BB     = 'SUBMITTED_TO_BB';
    const STATUS_BB_MONITORING       = 'BB_MONITORING_PENDING';
    const STATUS_FORWARDED_TO_MOJS   = 'FORWARDED_TO_MOJS';
    const STATUS_APPROVED            = 'APPROVED';
    const STATUS_REJECTED            = 'REJECTED';
    const STATUS_NEEDS_CORRECTION    = 'NEEDS_CORRECTION';

    // ── Relations ─────────────────────────────────────────────────────────────
    public function scheme()
    {
        return $this->belongsTo(Scheme::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bbMonitoringReport()
    {
        return $this->hasOne(BbMonitoringReport::class);
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }

    public function revisions()
    {
        return $this->hasMany(PaymentRequestRevision::class)->latest();
    }

    public function createRevisionSnapshot(?string $label = null, ?User $user = null): PaymentRequestRevision
    {
        $user = $user ?? auth()->user();
        $nextVersionCount = $this->revisions()->count() + 1;
        $versionLabel = $label ?? ('v' . $nextVersionCount . '.0');

        $stateGovtDoc = $this->scheme?->fmbapProject?->state_govt_doc_path;

        return $this->revisions()->create([
            'scheme_id'                      => $this->scheme_id,
            'user_id'                        => $user?->id,
            'version_label'                  => $versionLabel,
            'status_at_revision'             => $this->status,
            'requested_amount_cr'            => $this->requested_amount_cr,
            'instalment_number'              => $this->instalment_number,
            'bank_details'                   => $this->bank_details,
            'state_remarks'                  => $this->state_remarks,
            'physical_progress_pct'          => $this->physical_progress_pct,
            'physical_progress_description'  => $this->physical_progress_description,
            'financial_progress_pct'         => $this->financial_progress_pct,
            'financial_progress_description' => $this->financial_progress_description,
            'narrative_progress_report'      => $this->narrative_progress_report,
            'bb_decision'                    => $this->bb_decision,
            'bb_remarks'                     => $this->bb_remarks,
            'mojs_decision'                  => $this->mojs_decision,
            'mojs_remarks'                   => $this->mojs_remarks,
            'utilization_certificate_path'   => $this->utilization_certificate_path,
            'voucher_doc_paths'              => $this->voucher_doc_paths,
            'progress_report_doc_path'       => $this->progress_report_doc_path,
            'state_govt_doc_path'            => $stateGovtDoc,
            'additional_doc_paths'           => $this->additional_doc_paths,
            'author_name'                    => $user?->name ?? 'System',
            'author_role'                    => $user?->role ?? 'N/A',
        ]);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────
    /**
     * Role-aware scope: State sees own, BB/MoJS/Admin see all.
     */
    public function scopeForUser($query, User $user)
    {
        if ($user->isState()) {
            return $query->where('state', $user->state);
        }
        // BB, MoJS, Super Admin see everything
        return $query;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_NEEDS_CORRECTION]);
    }

    public function isAwaitingBb(): bool
    {
        return $this->status === self::STATUS_SUBMITTED_TO_BB;
    }

    public function isAwaitingMojs(): bool
    {
        return $this->status === self::STATUS_FORWARDED_TO_MOJS;
    }
}

