<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheme_code',
        'scheme_name',
        'river_basin',
        'district',
        'division',
        'state',
        'plan_period',
        'sanctioned_amount_cr',
        'estimated_cost_lakh',
        'fund_utilised_cs_lakh',
        'fund_utilised_ss_lakh',
        'fund_utilised_total_lakh',
        'fund_req_cs_lakh',
        'fund_req_ss_lakh',
        'fund_req_total_lakh',
        'central_share_pct',
        'state_share_pct',
        'project_type',
        'physical_status',
        'physical_progress_pct',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sanctioned_amount_cr' => 'decimal:2',
            'estimated_cost_lakh' => 'decimal:2',
            'fund_utilised_cs_lakh' => 'decimal:2',
            'fund_utilised_ss_lakh' => 'decimal:2',
            'fund_utilised_total_lakh' => 'decimal:2',
            'fund_req_cs_lakh' => 'decimal:2',
            'fund_req_ss_lakh' => 'decimal:2',
            'fund_req_total_lakh' => 'decimal:2',
            'physical_progress_pct' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function paymentRequests()
    {
        return $this->hasMany(PaymentRequest::class);
    }

    public function progressReports()
    {
        return $this->hasMany(ProgressReport::class);
    }

    public function fmbapProject()
    {
        return $this->hasOne(FmbapProject::class, 'scheme_code', 'scheme_code');
    }

    /**
     * Scope to only active schemes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Role-aware scope: State users only see their own state's schemes,
     * while Brahmaputra Board, MoJS, and Super Admin can see all states.
     */
    public function scopeForUser($query, ?User $user = null)
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        if ($user->isState() && !empty($user->state)) {
            return $query->where('state', $user->state);
        }

        return $query;
    }

    /**
     * Computed central share amount.
     */
    public function getCentralShareAmountAttribute(): float
    {
        return round($this->sanctioned_amount_cr * $this->central_share_pct / 100, 2);
    }

    /**
     * Computed state share amount.
     */
    public function getStateShareAmountAttribute(): float
    {
        return round($this->sanctioned_amount_cr * $this->state_share_pct / 100, 2);
    }

    /**
     * Enrich scheme with comprehensive financial metrics, MoJS sanction ledger, and remaining balance.
     */
    public function enrichFinancialMetrics(): self
    {
        if ($this->relationLoaded('fmbapProject') && $this->fmbapProject && !empty($this->fmbapProject->funding_pattern)) {
            $parts = explode('/', $this->fmbapProject->funding_pattern);
            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                $this->central_share_pct = (int) $parts[0];
                $this->state_share_pct   = (int) $parts[1];
            }
        }

        $centralSharePct = (float) ($this->central_share_pct ?? 90);
        $stateSharePct   = (float) ($this->state_share_pct ?? (100 - $centralSharePct));
        $sanctionedCr    = (float) ($this->sanctioned_amount_cr ?? 0);
        if ($sanctionedCr <= 0 && !empty($this->estimated_cost_lakh)) {
            $sanctionedCr = round(((float) $this->estimated_cost_lakh) / 100, 2);
        }

        $centralEntitlementCr = round($sanctionedCr * ($centralSharePct / 100), 2);
        $stateEntitlementCr   = round($sanctionedCr * ($stateSharePct / 100), 2);

        $prs = $this->relationLoaded('paymentRequests')
            ? $this->paymentRequests
            : $this->paymentRequests()->orderBy('instalment_number', 'asc')->get();

        $approvedPrs = $prs->where('status', 'APPROVED');

        $approvedClaimsSum = round($approvedPrs->sum(function ($pr) {
            return (float) ($pr->approved_amount_cr ?? $pr->requested_amount_cr ?? 0);
        }), 2);

        $curtailedSum = round($approvedPrs->sum(function ($pr) {
            return (float) ($pr->deduction_amount_cr ?? 0);
        }), 2);

        $totalRequestedApprovedClaims = round($approvedPrs->sum(function ($pr) {
            return (float) ($pr->requested_amount_cr ?? 0);
        }), 2);

        $proj = $this->relationLoaded('fmbapProject') ? $this->fmbapProject : null;
        $fmbapReleased = (float) ($proj?->released_central_share_cr ?? 0);
        $utilisedCs = !empty($this->fund_utilised_cs_lakh) ? round(((float) $this->fund_utilised_cs_lakh) / 100, 2) : 0;

        $cumReleased = $approvedClaimsSum > 0 ? $approvedClaimsSum : max($fmbapReleased, $utilisedCs);
        $releasedDisplay = $approvedClaimsSum > 0 ? $approvedClaimsSum : $cumReleased;
        $balanceCr = max(0, round($centralEntitlementCr - $cumReleased, 2));

        $this->central_share_entitlement_cr = $centralEntitlementCr;
        $this->state_share_entitlement_cr   = $stateEntitlementCr;
        $this->approved_claims_release_cr   = $approvedClaimsSum;
        $this->curtailed_amount_cr          = $curtailedSum;
        $this->released_central_share_cr    = $releasedDisplay;
        $this->cumulative_released_cr       = $cumReleased;
        $this->balance_central_share_cr     = $balanceCr;
        $this->total_requested_approved_cr  = $totalRequestedApprovedClaims;

        $this->past_approved_claims = $approvedPrs->map(function ($pr) {
            return [
                'id'                       => $pr->id,
                'instalment_number'        => $pr->instalment_number,
                'requested_amount_cr'      => (float) $pr->requested_amount_cr,
                'approved_amount_cr'       => (float) ($pr->approved_amount_cr ?? $pr->requested_amount_cr),
                'deduction_amount_cr'      => (float) ($pr->deduction_amount_cr ?? 0),
                'curtailment_reason'       => $pr->curtailment_reason,
                'sanction_order_no'        => $pr->sanction_order_no,
                'sanction_order_date'      => $pr->sanction_order_date ? \Carbon\Carbon::parse($pr->sanction_order_date)->format('d M Y') : null,
                'sanction_order_doc_path'  => $pr->sanction_order_doc_path,
                'status'                   => $pr->status,
                'approved_at'              => $pr->approved_at ? \Carbon\Carbon::parse($pr->approved_at)->format('d M Y') : null,
            ];
        })->values()->toArray();

        $maxInstalment = $approvedPrs->max('instalment_number');
        $this->next_suggested_instalment = $maxInstalment ? ($maxInstalment + 1) : 1;

        // Check if there is an active, unfulfilled claim in progress (single claim check)
        $activeClaim = $prs->first(function ($pr) {
            return !in_array($pr->status, [PaymentRequest::STATUS_APPROVED, PaymentRequest::STATUS_REJECTED]);
        });
        $this->has_active_claim = (bool) $activeClaim;
        $this->active_claim = $activeClaim ? [
            'id'                  => $activeClaim->id,
            'instalment_number'   => $activeClaim->instalment_number,
            'status'              => $activeClaim->status,
            'status_label'        => ucwords(strtolower(str_replace('_', ' ', $activeClaim->status))),
            'requested_amount_cr' => (float) $activeClaim->requested_amount_cr,
            'submitted_at'        => $activeClaim->submitted_at ? \Carbon\Carbon::parse($activeClaim->submitted_at)->format('d M Y') : null,
        ] : null;

        return $this;
    }

    /**
     * Check if this scheme has an active, unfulfilled fund claim currently under processing.
     */
    public function hasActiveClaim(): bool
    {
        return $this->paymentRequests()
            ->whereNotIn('status', [PaymentRequest::STATUS_APPROVED, PaymentRequest::STATUS_REJECTED])
            ->exists();
    }

    /**
     * Get the active, unfulfilled fund claim if any.
     */
    public function getActiveClaim(): ?PaymentRequest
    {
        return $this->paymentRequests()
            ->whereNotIn('status', [PaymentRequest::STATUS_APPROVED, PaymentRequest::STATUS_REJECTED])
            ->latest('id')
            ->first();
    }
}

