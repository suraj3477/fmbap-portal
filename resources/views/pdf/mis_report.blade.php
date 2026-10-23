<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>FMBAP Executive MIS Briefing Note</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9.5px; color: #1e293b; line-height: 1.35; margin: 12px 18px; }
        .header { text-align: center; border-bottom: 2px solid #0F4C9F; padding-bottom: 6px; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 14px; color: #0F4C9F; text-transform: uppercase; letter-spacing: 0.5px; }
        .header h2 { margin: 2px 0; font-size: 10.5px; color: #334155; }
        .header p { margin: 1px 0; font-size: 8.5px; color: #64748b; }
        .title-badge { display: inline-block; background-color: #0F4C9F; color: #fff; padding: 2px 8px; border-radius: 3px; font-weight: bold; font-size: 9px; margin-top: 3px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 5px; margin-bottom: 8px; font-size: 8.5px; }
        .table th, .table td { border: 1px solid #cbd5e1; padding: 4px 5px; text-align: left; vertical-align: top; }
        .table th { background-color: #f1f5f9; color: #1e293b; font-weight: bold; font-size: 8.5px; }
        .badge { background: #dbeafe; color: #1e40af; padding: 1.5px 4px; border-radius: 3px; font-weight: bold; font-size: 8px; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .section-title { font-size: 10px; font-weight: bold; color: #0F4C9F; margin-top: 8px; margin-bottom: 3px; border-left: 3px solid #0F4C9F; padding-left: 5px; text-transform: uppercase; }
        .grid-2 { width: 100%; margin-bottom: 6px; }
        .grid-2 td { width: 50%; vertical-align: top; padding: 0 3px; }
        .kpi-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 5px 7px; margin-bottom: 4px; }
        .kpi-title { font-size: 8px; text-transform: uppercase; color: #64748b; font-weight: bold; }
        .kpi-val { font-size: 13px; font-weight: 800; color: #0F4C9F; margin-top: 1px; }
        .footer { margin-top: 18px; border-top: 1px solid #cbd5e1; padding-top: 8px; }
        .footer table { width: 100%; border: none; }
        .footer td { border: none; vertical-align: bottom; }
        .watermark { position: fixed; bottom: 6px; left: 0; right: 0; text-align: center; font-size: 7.5px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <p><strong>GOVERNMENT OF INDIA &bull; MINISTRY OF JAL SHAKTI</strong></p>
        <h2>BRAHMAPUTRA BOARD / CENTRAL WATER COMMISSION</h2>
        <h1>Executive MIS Briefing &amp; Portfolio Analytics</h1>
        <div class="title-badge">FLOOD MANAGEMENT AND BORDER AREAS PROGRAMME (FMBAP)</div>
    </div>

    <div class="section-title">1. National Portfolio Macro Executive Summary</div>
    <table class="grid-2">
        <tr>
            <td>
                <div class="kpi-card">
                    <div class="kpi-title">Total Statutory Sanctioned Outlay</div>
                    <div class="kpi-val">₹{{ number_format($macro['total_sanctioned_cr'], 2) }} Cr</div>
                    <div style="font-size: 8px; color: #64748b; margin-top: 1px;">
                        Central Entitlement: ₹{{ number_format($macro['total_central_share_cr'], 2) }} Cr | State Matching: ₹{{ number_format($macro['total_state_share_cr'], 2) }} Cr
                    </div>
                </div>
            </td>
            <td>
                <div class="kpi-card">
                    <div class="kpi-title">Central Assistance Disbursed</div>
                    <div class="kpi-val" style="color: #047857;">₹{{ number_format($macro['total_released_cr'], 2) }} Cr</div>
                    <div style="font-size: 8px; color: #047857; margin-top: 1px;">
                        Disbursal Velocity: {{ $macro['disbursal_velocity_pct'] }}% | Unspent Balance: ₹{{ number_format($macro['unspent_balance_cr'] ?? 0, 2) }} Cr
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="kpi-card">
                    <div class="kpi-title">Schemes Execution Health</div>
                    <div class="kpi-val" style="color: #334155;">{{ $macro['total_schemes'] }} Schemes</div>
                    <div style="font-size: 8px; color: #64748b; margin-top: 1px;">
                        Completed: {{ $macro['completed_schemes'] }} | Active Ongoing: {{ $macro['ongoing_schemes'] }} | Stalled: {{ $macro['stalled_schemes'] ?? 0 }}
                    </div>
                </div>
            </td>
            <td>
                <div class="kpi-card">
                    <div class="kpi-title">Average Physical Completion</div>
                    <div class="kpi-val" style="color: #2563eb;">{{ $macro['avg_physical_progress'] }}%</div>
                    <div style="font-size: 8px; color: #64748b; margin-top: 1px;">
                        GFR 12-A Compliance: {{ $macro['uc_compliance_pct'] ?? 100 }}% | Verified against BB Inspection Reports
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">2. State-Wise Performance &amp; Composite Benchmark Scorecard (CPI)</div>
    <table class="table">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 18%;">State / WRD</th>
                <th style="width: 10%; text-align: center;">Schemes (Done)</th>
                <th style="width: 15%; text-align: right;">Sanctioned (₹ Cr)</th>
                <th style="width: 15%; text-align: right;">Released (₹ Cr)</th>
                <th style="width: 10%; text-align: center;">Disbursal %</th>
                <th style="width: 10%; text-align: center;">Physical %</th>
                <th style="width: 8%; text-align: center;">CPI Index</th>
                <th style="width: 10%; text-align: center;">Tier</th>
            </tr>
        </thead>
        <tbody>
            @foreach($state_scorecard as $idx => $s)
                <tr>
                    <td style="text-align: center; font-weight: bold;">{{ $idx + 1 }}</td>
                    <td><strong>{{ $s['state_name'] }}</strong></td>
                    <td style="text-align: center;">{{ $s['schemes_count'] }} ({{ $s['completed_count'] }})</td>
                    <td style="text-align: right;">₹{{ number_format($s['sanctioned_cr'], 2) }}</td>
                    <td style="text-align: right; color: #047857; font-weight: bold;">₹{{ number_format($s['released_cr'], 2) }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ $s['disbursal_pct'] }}%</td>
                    <td style="text-align: center; font-weight: bold; color: #0F4C9F;">{{ $s['avg_physical_pct'] }}%</td>
                    <td style="text-align: center; font-weight: bold; color: #4338ca;">{{ $s['cpi_score'] ?? 'N/A' }}</td>
                    <td style="text-align: center;">
                        <span class="badge {{ str_contains($s['tier'], 'Tier 1') ? 'badge-success' : (str_contains($s['tier'], 'Tier 2') ? 'badge-warning' : 'badge-danger') }}">
                            {{ str_contains($s['tier'], 'Tier 1') ? 'Tier 1' : (str_contains($s['tier'], 'Tier 2') ? 'Tier 2' : 'Tier 3') }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">3. River Basin Allocation Matrix</div>
    <table class="table">
        <thead>
            <tr>
                <th style="width: 32%;">River Basin Catchment</th>
                <th style="width: 18%; text-align: center;">Schemes (Completed)</th>
                <th style="width: 25%; text-align: right;">Sanctioned Outlay (₹ Cr)</th>
                <th style="width: 25%; text-align: center;">Avg Physical Progress</th>
            </tr>
        </thead>
        <tbody>
            @foreach($basin_matrix as $b)
                <tr>
                    <td><strong>{{ $b['basin_name'] }}</strong></td>
                    <td style="text-align: center;">{{ $b['schemes_count'] }} ({{ $b['completed_count'] ?? 0 }} done)</td>
                    <td style="text-align: right; font-weight: bold;">₹{{ number_format($b['sanctioned_cr'], 2) }} Cr</td>
                    <td style="text-align: center; font-weight: bold; color: #0F4C9F;">{{ $b['avg_physical_pct'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">4. Workflow Turnaround Time (TAT) &amp; Operational Bottlenecks</div>
    <table class="table">
        <tr>
            <th style="width: 25%;">Pending BB Scrutiny / Inspection</th>
            <td style="width: 25%;"><strong>{{ $bottlenecks['submitted_to_bb'] }} claims</strong> in queue</td>
            <th style="width: 25%;">Pending MoJS Sanction Order</th>
            <td style="width: 25%;"><strong>{{ $bottlenecks['forwarded_to_mojs'] }} claims</strong> pending</td>
        </tr>
        <tr>
            <th>Claims Returned for Correction</th>
            <td><strong style="color: #b45309;">{{ $bottlenecks['needs_correction'] }} claims</strong> with State WRD</td>
            <th>Total Approved &amp; Released</th>
            <td><strong style="color: #047857;">{{ $bottlenecks['approved'] }} claims</strong> disbursed</td>
        </tr>
        <tr>
            <th>Average Processing Turnaround</th>
            <td colspan="3">
                <strong>~{{ $bottlenecks['avg_turnaround_days'] }} Days</strong> (Standard Target SLA: {{ $bottlenecks['sla_target_days'] ?? 30 }} Days)
                @if(($bottlenecks['overdue_claims_count'] ?? 0) > 0)
                    | <span style="color: #b91c1c; font-weight: bold;">{{ $bottlenecks['overdue_claims_count'] }} claims exceeding 30-day SLA</span>
                @endif
            </td>
        </tr>
    </table>

    <div class="footer">
        <table>
            <tr>
                <td style="width: 50%;">
                    <p style="margin: 0; font-size: 8px; color: #64748b;">Prepared for MoJS &amp; Brahmaputra Board Parliamentary Oversight</p>
                    <p style="margin: 2px 0 0 0; font-size: 8px;"><strong>Briefing Generated:</strong> {{ $generated_at }}</p>
                </td>
                <td style="width: 50%; text-align: right;">
                    <p style="margin: 0; font-size: 9px; font-weight: bold;">Ministry of Jal Shakti / Brahmaputra Board</p>
                    <p style="margin-top: 20px; font-size: 8px; color: #475569;">Director / Member (Technical)</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="watermark">Government of India &bull; Ministry of Jal Shakti &bull; Confidential &amp; Official Use Only</div>
</body>
</html>

