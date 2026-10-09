<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>FMBAP Consolidated Claim Dossier - {{ $dossier['scheme']?->scheme_code }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10px; color: #1e293b; line-height: 1.35; margin: 15px; }
        .header { text-align: center; border-bottom: 2px solid #0F4C9F; padding-bottom: 6px; margin-bottom: 12px; }
        .header h1 { margin: 0; font-size: 14px; color: #0F4C9F; text-transform: uppercase; letter-spacing: 0.5px; }
        .header h2 { margin: 1px 0; font-size: 11px; color: #334155; }
        .header p { margin: 1px 0; font-size: 9px; color: #64748b; }
        .dossier-pill { display: inline-block; background-color: #0F4C9F; color: #fff; padding: 2px 8px; border-radius: 3px; font-weight: bold; font-size: 10px; margin-top: 4px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 6px; margin-bottom: 10px; }
        .table th, .table td { border: 1px solid #cbd5e1; padding: 5px 6px; text-align: left; vertical-align: top; }
        .table th { background-color: #f1f5f9; color: #334155; font-weight: 600; font-size: 9.5px; }
        .badge { background: #dbeafe; color: #1e40af; padding: 2px 5px; border-radius: 3px; font-weight: bold; font-size: 9px; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .section-title { font-size: 11px; font-weight: bold; color: #0F4C9F; margin-top: 10px; margin-bottom: 3px; border-left: 3px solid #0F4C9F; padding-left: 5px; text-transform: uppercase; }
        .footer { margin-top: 25px; }
        .footer table { width: 100%; border: none; }
        .footer td { border: none; vertical-align: bottom; }
        .status-tracker { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
        .status-tracker td { border: 1px solid #e2e8f0; text-align: center; padding: 4px 2px; font-size: 8.5px; }
        .step-active { background-color: #0F4C9F; color: #ffffff; font-weight: bold; }
        .step-completed { background-color: #e0f2fe; color: #0369a1; font-weight: bold; }
        .step-pending { background-color: #f8fafc; color: #94a3b8; }
        .watermark { position: fixed; bottom: 8px; left: 0; right: 0; text-align: center; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <p><strong>GOVERNMENT OF INDIA &bull; MINISTRY OF JAL SHAKTI</strong></p>
        <h2>BRAHMAPUTRA BOARD / CENTRAL WATER COMMISSION</h2>
        <h1>Consolidated Statutory Fund Release Dossier</h1>
        <div class="dossier-pill">CLAIM DOSSIER &bull; INSTALMENT #{{ $paymentRequest->instalment_number }} &bull; {{ $paymentRequest->scheme?->scheme_code }}</div>
    </div>

    <!-- Workflow Progress Bar -->
    <table class="status-tracker">
        <tr>
            @foreach($steps as $step)
                <td class="{{ $step['status'] === 'active' ? 'step-active' : ($step['status'] === 'completed' ? 'step-completed' : 'step-pending') }}">
                    {{ $step['label'] }}
                </td>
            @endforeach
        </tr>
    </table>

    <div class="section-title">1. Scheme Baseline Information</div>
    <table class="table">
        <tr>
            <th style="width: 20%;">Scheme Code</th>
            <td style="width: 30%;"><strong>{{ $dossier['scheme']?->scheme_code }}</strong></td>
            <th style="width: 20%;">Sanctioned Outlay</th>
            <td style="width: 30%;"><strong>₹{{ number_format($dossier['scheme']?->sanctioned_amount_cr ?? 0, 2) }} Cr</strong></td>
        </tr>
        <tr>
            <th>Scheme Title</th>
            <td colspan="3">{{ $dossier['scheme']?->scheme_name }}</td>
        </tr>
        <tr>
            <th>State</th>
            <td>{{ $dossier['scheme']?->state }}</td>
            <th>River Basin / District</th>
            <td>{{ $dossier['scheme']?->river_basin }} @if($dossier['scheme']?->district) ({{ $dossier['scheme']->district }}) @endif</td>
        </tr>
        <tr>
            <th>Funding Ratio</th>
            <td>{{ $dossier['scheme']?->central_share_pct ?? 90 }}% Central : {{ $dossier['scheme']?->state_share_pct ?? 10 }}% State</td>
            <th>Overall Physical Status</th>
            <td><strong>{{ $dossier['scheme']?->physical_status ?? 'Ongoing' }} ({{ $dossier['scheme']?->physical_progress_pct ?? 0 }}%)</strong></td>
        </tr>
    </table>

    <div class="section-title">2. State Government Claim Summary</div>
    <table class="table">
        <tr>
            <th style="width: 25%;">Instalment Number</th>
            <td style="width: 25%;"><strong>Instalment #{{ $paymentRequest->instalment_number }}</strong></td>
            <th style="width: 25%;">Requested Central Assistance</th>
            <td style="width: 25%; color: #0F4C9F; font-weight: bold;">₹{{ number_format($paymentRequest->requested_amount_cr, 2) }} Cr</td>
        </tr>
        <tr>
            <th>Claimed Physical Progress</th>
            <td><strong>{{ $paymentRequest->physical_progress_pct }}%</strong></td>
            <th>Claimed Financial Progress</th>
            <td><strong>{{ $paymentRequest->financial_progress_pct }}%</strong></td>
        </tr>
        <tr>
            <th>State Single Nodal A/C</th>
            <td colspan="3">{{ $paymentRequest->bank_details ?: 'State Treasury / SNA Account Configured' }}</td>
        </tr>
        <tr>
            <th>State Official Remarks</th>
            <td colspan="3">{{ $paymentRequest->state_remarks ?: 'Submitted along with GFR-12A Utilization Certificate and progress vouchers.' }}</td>
        </tr>
    </table>

    <div class="section-title">3. Brahmaputra Board Site Inspection &amp; Monitoring Report</div>
    @if($dossier['bb_monitoring_report'])
        <table class="table">
            <tr>
                <th style="width: 25%;">Inspection Date</th>
                <td style="width: 25%;">{{ $dossier['bb_monitoring_report']['report']->inspection_date ? \Carbon\Carbon::parse($dossier['bb_monitoring_report']['report']->inspection_date)->format('d M Y') : 'N/A' }}</td>
                <th style="width: 25%;">BB Inspected Officer</th>
                <td style="width: 25%;">{{ $dossier['bb_monitoring_report']['report']->bbUser?->name ?? 'Brahmaputra Board Inspector' }}</td>
            </tr>
            <tr>
                <th>Verified Physical Progress</th>
                <td><strong>{{ $dossier['bb_monitoring_report']['report']->bb_physical_progress_pct ?? 'N/A' }}%</strong></td>
                <th>Verified Financial Progress</th>
                <td><strong>{{ $dossier['bb_monitoring_report']['report']->bb_financial_progress_pct ?? 'N/A' }}%</strong></td>
            </tr>
            <tr>
                <th>BB Inspection Findings</th>
                <td colspan="3">{{ $dossier['bb_monitoring_report']['report']->site_description ?: $dossier['bb_monitoring_report']['report']->bb_physical_progress_description ?: 'Site inspection completed and found satisfactory.' }}</td>
            </tr>
            <tr>
                <th>BB Recommendation</th>
                <td colspan="3">
                    <span class="badge {{ $paymentRequest->bb_decision === 'FORWARDED_TO_MOJS' ? 'badge-success' : 'badge-warning' }}">
                        {{ str_replace('_', ' ', $paymentRequest->bb_decision ?? 'PENDING') }}
                    </span>
                    &mdash; {{ $paymentRequest->bb_remarks ?: 'Inspection report verified and submitted to MoJS.' }}
                </td>
            </tr>
            @if($paymentRequest->bb_recommended_amount_cr || ($dossier['bb_monitoring_report']['report']->bb_recommended_amount_cr ?? null))
                <tr>
                    <th>BB Recommended Release</th>
                    <td colspan="3" style="color: #4338ca; font-weight: bold;">
                        ₹{{ number_format($paymentRequest->bb_recommended_amount_cr ?? $dossier['bb_monitoring_report']['report']->bb_recommended_amount_cr, 2) }} Cr
                        @if($paymentRequest->requested_amount_cr > ($paymentRequest->bb_recommended_amount_cr ?? $dossier['bb_monitoring_report']['report']->bb_recommended_amount_cr))
                            <span style="color: #b45309; font-size: 8.5px; font-weight: normal; margin-left: 8px;">
                                (Curtailed by ₹{{ number_format($paymentRequest->requested_amount_cr - ($paymentRequest->bb_recommended_amount_cr ?? $dossier['bb_monitoring_report']['report']->bb_recommended_amount_cr), 2) }} Cr based on site verification)
                            </span>
                        @endif
                    </td>
                </tr>
            @endif
            @if(!empty($dossier['bb_monitoring_report']['geo_tagged_files']))
                <tr>
                    <th>Geo-Tagged Evidence</th>
                    <td colspan="3">
                        Total {{ count($dossier['bb_monitoring_report']['geo_tagged_files']) }} geo-tagged media recorded with GPS coordinates.
                    </td>
                </tr>
            @endif
        </table>
    @else
        <p style="background: #f8fafc; padding: 6px 10px; border: 1px solid #e2e8f0; font-size: 9px; color: #64748b;">
            Brahmaputra Board field inspection is currently in queue.
        </p>
    @endif

    <div class="section-title">4. Ministry of Jal Shakti (MoJS) Final Decision &amp; Central Allocation</div>
    <table class="table">
        <tr>
            <th style="width: 25%;">Final Status</th>
            <td style="width: 25%;">
                <span class="badge {{ $paymentRequest->status === 'APPROVED' ? 'badge-success' : ($paymentRequest->status === 'REJECTED' ? 'badge-danger' : 'badge-warning') }}">
                    {{ str_replace('_', ' ', $paymentRequest->status) }}
                </span>
            </td>
            <th style="width: 25%;">Decision Date</th>
            <td style="width: 25%;">{{ $paymentRequest->approved_at ? \Carbon\Carbon::parse($paymentRequest->approved_at)->format('d M Y') : 'Pending Final Release' }}</td>
        </tr>
        @if($paymentRequest->status === 'APPROVED')
            <tr>
                <th>State Claimed Amount</th>
                <td>₹{{ number_format($paymentRequest->requested_amount_cr, 2) }} Cr</td>
                <th>Sanctioned Central Release</th>
                <td style="color: #047857; font-weight: bold; font-size: 10.5px;">
                    ₹{{ number_format($paymentRequest->approved_amount_cr ?? $paymentRequest->requested_amount_cr, 2) }} Cr
                </td>
            </tr>
            @if(($paymentRequest->deduction_amount_cr ?? 0) > 0)
                <tr style="background-color: #fffbeb;">
                    <th>Curtailment / Deduction</th>
                    <td style="color: #b45309; font-weight: bold;">
                        - ₹{{ number_format($paymentRequest->deduction_amount_cr, 2) }} Cr
                    </td>
                    <th>Curtailment Reason</th>
                    <td style="color: #92400e; font-weight: 600;">
                        {{ $paymentRequest->curtailment_reason ?: 'SNA Unspent Balance / Disallowed Expenditure' }}
                    </td>
                </tr>
            @endif
            @if($paymentRequest->sanction_order_no)
                <tr>
                    <th>Central Sanction Order</th>
                    <td><strong>{{ $paymentRequest->sanction_order_no }}</strong></td>
                    <th>Sanction Date</th>
                    <td>{{ $paymentRequest->sanction_order_date ? \Carbon\Carbon::parse($paymentRequest->sanction_order_date)->format('d M Y') : 'N/A' }}</td>
                </tr>
            @endif
        @endif
        <tr>
            <th>MoJS Remarks</th>
            <td colspan="3">{{ $paymentRequest->mojs_remarks ?: 'Under central assistance review.' }}</td>
        </tr>
    </table>

    <div class="section-title">5. Digital Audit Trail &amp; Verification History</div>
    <table class="table">
        <thead>
            <tr>
                <th style="width: 20%;">Timestamp</th>
                <th style="width: 25%;">Officer</th>
                <th style="width: 25%;">Action</th>
                <th style="width: 30%;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($paymentRequest->auditLogs->take(5) as $log)
                <tr>
                    <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->user_name }}</td>
                    <td><span class="badge">{{ strtoupper($log->action) }}</span></td>
                    <td>{{ $log->remarks ?: ($log->field_name ? "Changed {$log->field_name}" : 'Action recorded') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #94a3b8;">No previous audit entries.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <table>
            <tr>
                <td style="width: 50%;">
                    <p style="margin: 0; font-size: 9px; color: #64748b;">Generated from National FMBAP Monitoring Portal</p>
                    <p style="margin: 2px 0 0 0; font-size: 9px;"><strong>Generated On:</strong> {{ $generated_at }}</p>
                </td>
                <td style="width: 50%; text-align: right;">
                    <p style="margin: 0; font-size: 10px;"><strong>Ministry of Jal Shakti / Brahmaputra Board</strong></p>
                    <p style="margin-top: 25px; font-size: 9px; color: #475569;">Authorized Central Disbursal Officer</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="watermark">Official Dossier &bull; Ministry of Jal Shakti &bull; Brahmaputra Board HQ</div>
</body>
</html>
