<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>FMBAP Executive MIS Briefing Note</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10px; color: #1e293b; line-height: 1.35; margin: 15px 20px; }
        .header { text-align: center; border-bottom: 2px solid #0F4C9F; padding-bottom: 8px; margin-bottom: 12px; }
        .header h1 { margin: 0; font-size: 15px; color: #0F4C9F; text-transform: uppercase; letter-spacing: 0.5px; }
        .header h2 { margin: 2px 0; font-size: 11px; color: #334155; }
        .header p { margin: 1px 0; font-size: 9px; color: #64748b; }
        .title-badge { display: inline-block; background-color: #0F4C9F; color: #fff; padding: 2px 8px; border-radius: 3px; font-weight: bold; font-size: 9.5px; margin-top: 4px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 6px; margin-bottom: 10px; font-size: 9.5px; }
        .table th, .table td { border: 1px solid #cbd5e1; padding: 5px 6px; text-align: left; vertical-align: top; }
        .table th { background-color: #f1f5f9; color: #334155; font-weight: bold; }
        .badge { background: #dbeafe; color: #1e40af; padding: 1.5px 5px; border-radius: 3px; font-weight: bold; font-size: 8.5px; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .section-title { font-size: 11px; font-weight: bold; color: #0F4C9F; margin-top: 10px; margin-bottom: 4px; border-left: 3px solid #0F4C9F; padding-left: 5px; text-transform: uppercase; }
        .grid-2 { width: 100%; margin-bottom: 8px; }
        .grid-2 td { width: 50%; vertical-align: top; padding: 0 4px; }
        .kpi-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px 8px; margin-bottom: 6px; }
        .kpi-title { font-size: 8.5px; text-transform: uppercase; color: #64748b; font-weight: bold; }
        .kpi-val { font-size: 14px; font-weight: 800; color: #0F4C9F; margin-top: 2px; }
        .footer { margin-top: 25px; }
        .footer table { width: 100%; border: none; }
        .footer td { border: none; vertical-align: bottom; }
        .watermark { position: fixed; bottom: 8px; left: 0; right: 0; text-align: center; font-size: 8px; color: #94a3b8; }
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
                    <div class="kpi-title">Total Sanctioned Outlay (90:10 Pattern)</div>
                    <div class="kpi-val">₹{{ number_format($macro['total_sanctioned_cr'], 2) }} Cr</div>
                    <div style="font-size: 8.5px; color: #64748b; margin-top: 2px;">
                        Central Assistance: ₹{{ number_format($macro['total_central_share_cr'], 2) }} Cr | State Share: ₹{{ number_format($macro['total_state_share_cr'], 2) }} Cr
                    </div>
                </div>
            </td>
            <td>
                <div class="kpi-card">
                    <div class="kpi-title">Central Assistance Disbursed</div>
                    <div class="kpi-val" style="color: #047857;">₹{{ number_format($macro['total_released_cr'], 2) }} Cr</div>
                    <div style="font-size: 8.5px; color: #047857; margin-top: 2px;">
                        Disbursal Velocity: {{ $macro['disbursal_velocity_pct'] }}% of Central Outlay
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="kpi-card">
                    <div class="kpi-title">Execution Portfolio &amp; Schemes Count</div>
                    <div class="kpi-val" style="color: #334155;">{{ $macro['total_schemes'] }} Schemes</div>
                    <div style="font-size: 8.5px; color: #64748b; margin-top: 2px;">
                        Completed: {{ $macro['completed_schemes'] }} | Active / Ongoing: {{ $macro['ongoing_schemes'] }}
                    </div>
                </div>
            </td>
            <td>
                <div class="kpi-card">
                    <div class="kpi-title">Average Physical Completion</div>
                    <div class="kpi-val" style="color: #2563eb;">{{ $macro['avg_physical_progress'] }}%</div>
                    <div style="font-size: 8.5px; color: #64748b; margin-top: 2px;">
                        Verified against Brahmaputra Board field inspections
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">2. State-Wise Performance &amp; Disbursal Scorecard</div>
    <table class="table">
        <thead>
            <tr>
                <th style="width: 20%;">State / WRD</th>
                <th style="width: 12%; text-align: center;">Total Schemes</th>
                <th style="width: 16%; text-align: right;">Sanctioned (₹ Cr)</th>
                <th style="width: 16%; text-align: right;">Released (₹ Cr)</th>
                <th style="width: 12%; text-align: center;">Disbursal %</th>
                <th style="width: 12%; text-align: center;">Avg Physical %</th>
                <th style="width: 12%; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($state_scorecard as $s)
                <tr>
                    <td><strong>{{ $s['state_name'] }}</strong></td>
                    <td style="text-align: center;">{{ $s['schemes_count'] }}</td>
                    <td style="text-align: right;">₹{{ number_format($s['sanctioned_cr'], 2) }}</td>
                    <td style="text-align: right; color: #047857; font-weight: bold;">₹{{ number_format($s['released_cr'], 2) }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ $s['disbursal_pct'] }}%</td>
                    <td style="text-align: center; font-weight: bold; color: #0F4C9F;">{{ $s['avg_physical_pct'] }}%</td>
                    <td style="text-align: center;">
                        <span class="badge {{ $s['completed_count'] > 0 ? 'badge-success' : 'badge-warning' }}">
                            {{ $s['completed_count'] }}/{{ $s['schemes_count'] }} Done
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">3. River Basin Distribution (Brahmaputra &amp; North East)</div>
    <table class="table">
        <thead>
            <tr>
                <th style="width: 30%;">River Basin</th>
                <th style="width: 15%; text-align: center;">Schemes Count</th>
                <th style="width: 25%; text-align: right;">Sanctioned Outlay (₹ Cr)</th>
                <th style="width: 15%; text-align: center;">Avg Progress</th>
                <th style="width: 15%; text-align: center;">Active Projects</th>
            </tr>
        </thead>
        <tbody>
            @foreach($basin_matrix as $b)
                <tr>
                    <td><strong>{{ $b['basin_name'] }}</strong></td>
                    <td style="text-align: center;">{{ $b['schemes_count'] }}</td>
                    <td style="text-align: right;">₹{{ number_format($b['sanctioned_cr'], 2) }} Cr</td>
                    <td style="text-align: center; font-weight: bold; color: #0F4C9F;">{{ $b['avg_physical_pct'] }}%</td>
                    <td style="text-align: center;">{{ $b['schemes_count'] }} Schemes</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">4. Workflow Turnaround Time (TAT) &amp; Bottlenecks</div>
    <table class="table">
        <tr>
            <th style="width: 25%;">Pending BB Scrutiny / Inspection</th>
            <td style="width: 25%;"><strong>{{ $bottlenecks['submitted_to_bb'] }} claims</strong> in queue</td>
            <th style="width: 25%;">Pending MoJS Final Release</th>
            <td style="width: 25%;"><strong>{{ $bottlenecks['forwarded_to_mojs'] }} claims</strong> pending</td>
        </tr>
        <tr>
            <th>Claims Returned for Correction</th>
            <td><strong style="color: #b45309;">{{ $bottlenecks['needs_correction'] }} claims</strong> with State</td>
            <th>Total Approved &amp; Released</th>
            <td><strong style="color: #047857;">{{ $bottlenecks['approved'] }} claims</strong> completed</td>
        </tr>
        <tr>
            <th>Average Processing Turnaround</th>
            <td colspan="3">
                <strong>~{{ $bottlenecks['avg_turnaround_days'] }} Days</strong> (from initial State submission to MoJS financial release sanction order)
            </td>
        </tr>
    </table>

    <div class="footer">
        <table>
            <tr>
                <td style="width: 50%;">
                    <p style="margin: 0; font-size: 8.5px; color: #64748b;">Prepared for MoJS &amp; Brahmaputra Board Parliamentary Oversight</p>
                    <p style="margin: 2px 0 0 0; font-size: 8.5px;"><strong>Briefing Generated:</strong> {{ $generated_at }}</p>
                </td>
                <td style="width: 50%; text-align: right;">
                    <p style="margin: 0; font-size: 9.5px;"><strong>Ministry of Jal Shakti / Brahmaputra Board</strong></p>
                    <p style="margin-top: 25px; font-size: 8.5px; color: #475569;">Director / Member (Technical)</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="watermark">Government of India &bull; Ministry of Jal Shakti &bull; Confidential &amp; Official Use</div>
</body>
</html>
