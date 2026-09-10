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
}
