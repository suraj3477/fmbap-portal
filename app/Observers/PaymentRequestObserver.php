<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\PaymentRequest;
use Illuminate\Support\Facades\Request;

class PaymentRequestObserver
{
    /**
     * Fields to track for field-level delta logging.
     */
    protected array $trackedFields = [
        'status',
        'bb_decision',
        'mojs_decision',
        'requested_amount_cr',
        'physical_progress_pct',
        'financial_progress_pct',
        'state_remarks',
        'bb_remarks',
        'mojs_remarks',
        'utilization_certificate_path',
        'progress_report_doc_path',
    ];

    public function created(PaymentRequest $request): void
    {
        $this->log($request, 'created', null, null, null, 'Payment request created as draft.');
    }

    public function updated(PaymentRequest $request): void
    {
        $dirty = $request->getDirty();

        foreach ($dirty as $field => $newValue) {
            if (!in_array($field, $this->trackedFields)) {
                continue;
            }

            $oldValue = $request->getOriginal($field);

            // Determine action label from status changes
            $action = match (true) {
                $field === 'status' => $this->statusAction($newValue),
                $field === 'bb_decision' => 'bb_decision',
                $field === 'mojs_decision' => 'mojs_decision',
                str_contains($field, '_path') => 'document_uploaded',
                default => 'updated',
            };

            $this->log($request, $action, $field, (string)$oldValue, (string)$newValue);
        }
    }

    protected function statusAction(string $newStatus): string
    {
        return match ($newStatus) {
            'SUBMITTED_TO_BB'       => 'submitted',
            'FORWARDED_TO_MOJS'     => 'forwarded_to_mojs',
            'APPROVED'              => 'approved',
            'REJECTED'              => 'rejected',
            'NEEDS_CORRECTION'      => 'needs_correction',
            default                 => 'status_changed',
        };
    }

    protected function log(
        PaymentRequest $request,
        string $action,
        ?string $fieldName,
        ?string $oldValue,
        ?string $newValue,
        ?string $remarks = null
    ): void {
        $user = auth()->user();

        AuditLog::create([
            'auditable_type' => PaymentRequest::class,
            'auditable_id'   => $request->id,
            'user_id'        => $user?->id,
            'user_name'      => $user?->name ?? 'System',
            'action'         => $action,
            'field_name'     => $fieldName,
            'old_value'      => $oldValue,
            'new_value'      => $newValue,
            'remarks'        => $remarks,
            'ip_address'     => Request::ip(),
            'user_agent'     => Request::userAgent(),
        ]);
    }
}
