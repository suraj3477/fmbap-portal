<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\ProgressReport;
use Illuminate\Support\Facades\Request;

class ProgressReportObserver
{
    protected array $trackedFields = [
        'status',
        'physical_progress_pct',
        'financial_progress_pct',
        'bb_remarks',
        'mojs_remarks',
        'progress_report_doc_path',
    ];

    public function created(ProgressReport $report): void
    {
        $this->log($report, 'created', null, null, null, 'Progress report submitted.');
    }

    public function updated(ProgressReport $report): void
    {
        $dirty = $report->getDirty();

        foreach ($dirty as $field => $newValue) {
            if (!in_array($field, $this->trackedFields)) {
                continue;
            }
            $oldValue = $report->getOriginal($field);
            $action = ($field === 'status') ? $this->statusAction($newValue) : 'updated';
            $this->log($report, $action, $field, (string)$oldValue, (string)$newValue);
        }
    }

    protected function statusAction(string $status): string
    {
        return match ($status) {
            'REVIEWED_BY_BB'   => 'bb_decision',
            'REVIEWED_BY_MOJS' => 'mojs_decision',
            'NEEDS_CORRECTION' => 'needs_correction',
            'APPROVED'         => 'approved',
            default            => 'status_changed',
        };
    }

    protected function log(ProgressReport $report, string $action, ?string $fieldName, ?string $oldValue, ?string $newValue, ?string $remarks = null): void
    {
        $user = auth()->user();
        AuditLog::create([
            'auditable_type' => ProgressReport::class,
            'auditable_id'   => $report->id,
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
