<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FmbapProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'state',
        'status',
        'scheme_code',
        'scheme_name',
        'estimated_cost_cr',
        'executed_amount_cr',
        'funding_pattern',
        'central_share_cr',
        'state_share_cr',
        'released_central_share_cr',
        'released_state_share_cr',
        'balance_central_share_cr',
        'balance_state_share_cr',
        'remarks',
        'bb_remarks',
        'mojs_remarks',
        'state_govt_doc_path',
        'state_govt_submission_date',
        'state_govt_doc_note',
        'brahmaputra_board_doc_path',
        'brahmaputra_board_submission_date',
        'brahmaputra_board_doc_note',
        'mojs_doc_path',
        'mojs_submission_date',
        'mojs_doc_note',
    ];

    public function scheme()
    {
        return $this->belongsTo(Scheme::class, 'scheme_code', 'scheme_code');
    }
}