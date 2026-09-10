<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRequestRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_request_id',
        'scheme_id',
        'user_id',
        'version_label',
        'status_at_revision',
        'requested_amount_cr',
        'instalment_number',
        'bank_details',
        'state_remarks',
        'physical_progress_pct',
        'physical_progress_description',
        'financial_progress_pct',
        'financial_progress_description',
        'narrative_progress_report',
        'bb_decision',
        'bb_remarks',
        'mojs_decision',
        'mojs_remarks',
        'utilization_certificate_path',
        'voucher_doc_paths',
        'progress_report_doc_path',
        'state_govt_doc_path',
        'additional_doc_paths',
        'author_name',
        'author_role',
    ];

    protected function casts(): array
    {
        return [
            'voucher_doc_paths'     => 'array',
            'additional_doc_paths'  => 'array',
            'requested_amount_cr'   => 'decimal:2',
            'physical_progress_pct' => 'decimal:2',
            'financial_progress_pct'=> 'decimal:2',
        ];
    }

    public function paymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    public function scheme()
    {
        return $this->belongsTo(Scheme::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
