<?php

namespace App\Services;

use App\Models\PaymentRequest;

class DossierService
{
    /**
     * Build a structured data array of all dossier documents for a payment request.
     * Used to power the consolidated dossier view (Show page) and PDF generation.
     */
    public function build(PaymentRequest $paymentRequest): array
    {
        $paymentRequest->load(['scheme.fmbapProject', 'user', 'bbMonitoringReport.bbUser', 'auditLogs.user']);

        $scheme  = $paymentRequest->scheme;
        $bbReport = $paymentRequest->bbMonitoringReport;

        return [
            'payment_request' => $paymentRequest,
            'scheme'          => $scheme,

            // ── State Documents ────────────────────────────────────────────
            'state_documents' => [
                'utilization_certificate' => $paymentRequest->utilization_certificate_path,
                'vouchers'                => $paymentRequest->voucher_doc_paths ?? [],
                'progress_report'         => $paymentRequest->progress_report_doc_path,
                'additional'              => $paymentRequest->additional_doc_paths ?? [],
            ],

            // ── BB Monitoring Report ───────────────────────────────────────
            'bb_monitoring_report' => $bbReport ? [
                'report'           => $bbReport,
                'official_pdf'     => $bbReport->bb_report_doc_path,
                'geo_tagged_files' => $bbReport->geo_tagged_files ?? [],
                'photos'           => $bbReport->photos,
                'videos'           => $bbReport->videos,
            ] : null,

            // ── Audit Trail ────────────────────────────────────────────────
            'audit_logs' => $paymentRequest->auditLogs,

            // ── Summary ────────────────────────────────────────────────────
            'summary' => [
                'requested_amount_cr'      => $paymentRequest->requested_amount_cr,
                'physical_progress_pct'    => $paymentRequest->physical_progress_pct,
                'financial_progress_pct'   => $paymentRequest->financial_progress_pct,
                'bb_physical_pct'          => $bbReport?->bb_physical_progress_pct,
                'bb_financial_pct'         => $bbReport?->bb_financial_progress_pct,
                'current_status'           => $paymentRequest->status,
                'bb_decision'              => $paymentRequest->bb_decision,
                'mojs_decision'            => $paymentRequest->mojs_decision,
            ],
        ];
    }

    /**
     * Returns a human-readable workflow step label array for the status bar.
     */
    public function getWorkflowSteps(string $currentStatus): array
    {
        $steps = [
            ['key' => 'DRAFT',                 'label' => 'Draft',              'icon' => '📝'],
            ['key' => 'SUBMITTED_TO_BB',        'label' => 'Submitted to BB',    'icon' => '📤'],
            ['key' => 'BB_MONITORING_PENDING',  'label' => 'BB Inspection',      'icon' => '🔍'],
            ['key' => 'FORWARDED_TO_MOJS',      'label' => 'Forwarded to MoJS',  'icon' => '🔀'],
            ['key' => 'APPROVED',               'label' => 'Approved & Released','icon' => '✅'],
        ];

        $activeIndex = 0;
        foreach ($steps as $i => $step) {
            if ($step['key'] === $currentStatus) {
                $activeIndex = $i;
                break;
            }
        }

        // Handle terminal states
        if ($currentStatus === 'REJECTED') {
            $activeIndex = -1;
        } elseif ($currentStatus === 'NEEDS_CORRECTION') {
            $activeIndex = 1; // back to BB step
        }

        foreach ($steps as $i => &$step) {
            $step['status'] = $i < $activeIndex ? 'completed' : ($i === $activeIndex ? 'active' : 'pending');
        }

        return $steps;
    }
}
