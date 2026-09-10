<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>FMBAP Project Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; color: #1e40af; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .table th { background-color: #f3f4f6; }
        .badge { background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>MINISTRY OF JAL SHAKTI / BRAHMAPUTRA BOARD</h2>
        <p>Flood Management and Border Areas Programme (FMBAP)</p>
        <p><strong>Project Monitoring & Verification Report</strong></p>
    </div>

    <table class="table">
        <tr>
            <th>Project Code</th>
            <td>{{ $project->project_code }}</td>
            <th>Status</th>
            <td><span class="badge">{{ $project->status }}</span></td>
        </tr>
        <tr>
            <th>Project Title</th>
            <td colspan="3">{{ $project->title }}</td>
        </tr>
        <tr>
            <th>State & District</th>
            <td>{{ $project->state }} ({{ $project->district }})</td>
            <th>River Basin</th>
            <td>{{ $project->river_basin }}</td>
        </tr>
        <tr>
            <th>Sanctioned Cost</th>
            <td>₹{{ $project->sanctioned_cost_cr }} Cr</td>
            <th>Physical Progress</th>
            <td><strong>{{ $project->physical_progress_pct }}%</strong></td>
        </tr>
        <tr>
            <th>Funds Released</th>
            <td>₹{{ $project->funds_released_cr }} Cr</td>
            <th>Funds Utilized</th>
            <td>₹{{ $project->funds_utilized_cr }} Cr</td>
        </tr>
    </table>

    <h3 style="margin-top:20px;">Field Inspection Notes</h3>
    <p style="background: #f9fafb; padding: 10px; border: 1px solid #e5e7eb;">
        {{ $project->inspection_notes ?? 'No technical notes recorded yet.' }}
    </p>

    <div style="margin-top: 40px; text-align: right;">
        <p><strong>Generated On:</strong> {{ $generated_at }}</p>
        <p style="margin-top: 30px;">___________________________<br>Authorized Signatory / BBRD Inspector</p>
    </div>
</body>
</html>