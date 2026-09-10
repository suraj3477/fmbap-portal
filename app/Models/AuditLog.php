<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'user_id',
        'user_name',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'remarks',
        'ip_address',
        'user_agent',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────
    public function auditable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Action label helpers ──────────────────────────────────────────────────
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created'           => 'Request Created',
            'submitted'         => 'Submitted to BB',
            'status_changed'    => 'Status Changed',
            'document_uploaded' => 'Document Uploaded',
            'forwarded_to_mojs' => 'Forwarded to MoJS',
            'bb_decision'       => 'BB Decision',
            'mojs_decision'     => 'MoJS Decision',
            'needs_correction'  => 'Sent for Correction',
            'approved'          => 'Approved & Released',
            'rejected'          => 'Rejected',
            'report_submitted'  => 'BB Report Submitted',
            default             => ucwords(str_replace('_', ' ', $this->action)),
        };
    }

    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            'created'           => '📝',
            'submitted'         => '📤',
            'forwarded_to_mojs' => '🔀',
            'bb_decision'       => '🏛',
            'mojs_decision'     => '⚖️',
            'approved'          => '✅',
            'rejected'          => '❌',
            'needs_correction'  => '🔄',
            'document_uploaded' => '📎',
            'report_submitted'  => '📋',
            default             => '📌',
        };
    }
}
