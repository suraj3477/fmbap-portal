<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>FMBAP Project Monitoring Report</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.4; margin: 15px; }
        .header { text-align: center; border-bottom: 2px solid #0F4C9F; padding-bottom: 8px; margin-bottom: 15px; }
        .header h1 { margin: 0; font-size: 16px; color: #0F4C9F; text-transform: uppercase; letter-spacing: 0.5px; }
        .header h2 { margin: 2px 0; font-size: 12px; color: #334155; font-weight: 600; }
        .header p { margin: 2px 0; font-size: 10px; color: #64748b; }
        .title-badge { display: inline-block; background-color: #0F4C9F; color: #fff; padding: 3px 10px; border-radius: 3px; font-weight: bold; font-size: 11px; margin-top: 5px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 12px; }
        .table th, .table td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; vertical-align: top; }
        .table th { background-color: #f1f5f9; color: #334155; font-weight: 600; font-size: 10.5px; }
        .badge { background: #dbeafe; color: #1e40af; padding: 2px 6px; border-radius: 3px; font-weight: bold; font-size: 10px; }
        .section-title { font-size: 12px; font-weight: bold; color: #0F4C9F; margin-top: 14px; margin-bottom: 4px; border-left: 3px solid #0F4C9F; padding-left: 6px; }
        .notes-box { background: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 4px; min-height: 40px; font-size: 10.5px; color: #334155; }
        .footer { margin-top: 30px; }
        .footer table { width: 100%; border: none; }
        .footer td { border: none; vertical-align: bottom; }
        .watermark { position: fixed; bottom: 10px; left: 0; right: 0; text-align: center; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <p><strong>GOVERNMENT OF INDIA &bull; MINISTRY OF JAL SHAKTI</strong></p>
        <h2>BRAHMAPUTRA BOARD / CENTRAL WATER COMMISSION</h2>
        <h1>Flood Management and Border Areas Programme (FMBAP)</h1>
        <div class="title-badge">PROJECT SCRUTINY &amp; MONITORING SUMMARY</div>
    </div>

    <div class="section-title">1. Scheme Identification &amp; Statutory Metadata</div>
    <table class="table">
        <tr>
            <th style="width: 20%;">Scheme Code</th>
            <td style="width: 30%;"><strong>{{ $project->scheme_code ?: ($project->project_code ?: 'FMBAP-'.$project->id) }}</strong></td>
            <th style="width: 20%;">Workflow Status</th>
            <td style="width: 30%;"><span class="badge">{{ str_replace('_', ' ', $project->status) }}</span></td>
        </tr>
        <tr>
            <th>Scheme Title</th>
            <td colspan="3"><strong>{{ $project->scheme_name ?: ($project->title ?: 'Flood Mitigation & Anti-Erosion Scheme') }}</strong></td>
        </tr>
        <tr>
            <th>State</th>
            <td>{{ $project->state ?? ($project->scheme?->state ?? 'N/A') }}</td>
            <th>River Basin / District</th>
            <td>{{ $project->scheme?->river_basin ?? 'Brahmaputra Basin' }} @if($project->scheme?->district) ({{ $project->scheme->district }}) @endif</td>
        </tr>
        <tr>
            <th>Funding Ratio</th>
            <td>{{ $project->funding_pattern ?: '90% Central : 10% State' }}</td>
            <th>Physical Progress</th>
            <td><strong>{{ $project->scheme?->physical_progress_pct ?? ($project->physical_progress_pct ?? 0) }}%</strong></td>
        </tr>
    </table>

    <div class="section-title">2. Financial Allocation &amp; Assistance Disbursal (₹ in Crore)</div>
    <table class="table">
        <tr>
            <th style="width: 25%;">Estimated / Sanctioned Cost</th>
            <th style="width: 25%;">Central Assistance Outlay</th>
            <th style="width: 25%;">State Matching Share</th>
            <th style="width: 25%;">Central Share Released</th>
        </tr>
        <tr>
            <td><strong>₹{{ number_format($project->estimated_cost_cr ?: ($project->sanctioned_cost_cr ?: 0), 2) }} Cr</strong></td>
            <td>₹{{ number_format($project->central_share_cr ?: (($project->estimated_cost_cr ?: 0) * 0.9), 2) }} Cr</td>
            <td>₹{{ number_format($project->state_share_cr ?: (($project->estimated_cost_cr ?: 0) * 0.1), 2) }} Cr</td>
            <td style="color: #047857; font-weight: bold;">₹{{ number_format($project->released_central_share_cr ?: ($project->funds_released_cr ?: 0), 2) }} Cr</td>
        </tr>
        <tr>
            <th>Balance Central Share</th>
            <td colspan="3">₹{{ number_format($project->balance_central_share_cr ?: max(0, ($project->central_share_cr ?: 0) - ($project->released_central_share_cr ?: 0)), 2) }} Cr</td>
        </tr>
    </table>

    <div class="section-title">3. Departmental Remarks &amp; Inspection Findings</div>
    <table class="table">
        <tr>
            <th style="width: 25%;">State Govt Submission</th>
            <td>{{ $project->state_govt_doc_note ?: ($project->remarks ?: 'Submitted through online FMBAP portal.') }}</td>
        </tr>
        <tr>
            <th>Brahmaputra Board Scrutiny</th>
            <td>{{ $project->brahmaputra_board_doc_note ?: ($project->bb_remarks ?: 'Scrutinized according to FMBAP technical guidelines.') }}</td>
        </tr>
        <tr>
            <th>MoJS Decision Notes</th>
            <td>{{ $project->mojs_doc_note ?: ($project->mojs_remarks ?: 'Under central assistance review.') }}</td>
        </tr>
    </table>

    <div class="footer">
        <table>
            <tr>
                <td style="width: 50%;">
                    <p style="margin: 0; font-size: 10px; color: #64748b;">Generated from National FMBAP Monitoring Portal</p>
                    <p style="margin: 2px 0 0 0; font-size: 10px;"><strong>Date:</strong> {{ $generated_at }}</p>
                </td>
                <td style="width: 50%; text-align: right;">
                    <p style="margin: 0; font-size: 11px;"><strong>Brahmaputra Board / Ministry of Jal Shakti</strong></p>
                    <p style="margin-top: 30px; font-size: 10px; color: #475569;">Authorized Technical Verification Officer</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="watermark">Official Government Document &bull; Validated under Ministry of Jal Shakti Guidelines</div>
</body>
</html>