<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Form GFR 12-A - Statutory Utilization Certificate</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; color: #000; line-height: 1.4; margin: 25px 30px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .header { margin-bottom: 20px; }
        .header h1 { font-size: 13pt; margin: 0; }
        .header h2 { font-size: 11pt; margin: 3px 0; font-weight: normal; }
        .header h3 { font-size: 12pt; margin: 5px 0; }
        .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table th, .table td { border: 1px solid #000; padding: 6px 8px; text-align: left; vertical-align: top; font-size: 10pt; }
        .table th { background-color: #f2f2f2; text-align: center; font-weight: bold; }
        .cert-para { text-align: justify; margin: 12px 0; text-indent: 30px; line-height: 1.5; font-size: 10.5pt; }
        .check-list { margin: 8px 0 15px 25px; font-size: 10pt; }
        .check-list li { margin-bottom: 4px; }
        .signatures { margin-top: 40px; width: 100%; }
        .signatures table { width: 100%; border: none; }
        .signatures td { border: none; vertical-align: top; font-size: 10pt; }
        .seal-box { border: 1px dashed #666; height: 50px; margin-top: 10px; text-align: center; padding-top: 15px; font-size: 9pt; color: #555; }
        .footer-note { margin-top: 30px; border-top: 1px solid #999; padding-top: 5px; font-size: 8.5pt; color: #444; }
    </style>
</head>
<body>
    <div class="header center">
        <p style="margin: 0; font-size: 10pt;"><strong>GOVERNMENT OF INDIA &bull; MINISTRY OF FINANCE</strong></p>
        <h1>FORM GFR 12 - A</h1>
        <h2>[(See Rule 238 (1))]</h2>
        <h3>FORM OF UTILIZATION CERTIFICATE FOR STATE GOVERNMENTS</h3>
        <p style="margin: 3px 0; font-size: 10pt;">
            <strong>(Central Assistance Grants-in-Aid under Flood Management and Border Areas Programme - FMBAP)</strong>
        </p>
        <p style="margin: 2px 0; font-size: 10.5pt;"><strong>FINANCIAL YEAR: {{ $financial_year }}</strong></p>
    </div>

    <table style="width: 100%; margin-bottom: 12px; font-size: 10.5pt;">
        <tr>
            <td style="width: 25%; font-weight: bold;">1. Name of Scheme:</td>
            <td><strong>{{ $scheme->scheme_code }}</strong> &mdash; {{ $scheme->scheme_name }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">2. State / River Basin:</td>
            <td>{{ $scheme->state }} / {{ $scheme->river_basin ?? 'Brahmaputra' }} Basin @if($scheme->district) (District: {{ $scheme->district }}) @endif</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">3. Funding Pattern:</td>
            <td>{{ $scheme->central_share_pct ?? 90 }}% Central Assistance : {{ $scheme->state_share_pct ?? 10 }}% State Matching Share</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">4. Nature of Grant:</td>
            <td>Non-recurring (Capital Outlay for Flood Management / Anti-Erosion Works)</td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 8%;">Sl. No.</th>
                <th style="width: 32%;">Letter No. &amp; Date of MoJS Sanction Order</th>
                <th style="width: 20%;">Central Share Released (₹ in Cr)</th>
                <th style="width: 20%;">State Matching Released (₹ in Cr)</th>
                <th style="width: 20%;">Total Amount Disbursed (₹ in Cr)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="center">1.</td>
                <td>
                    Sanction Ref: {{ $sanction_letter_no }}<br>
                    <small>Dated: {{ $sanction_date }}</small><br>
                    <small>Instalment No.: #{{ $instalment_number }}</small>
                </td>
                <td style="text-align: right; font-weight: bold;">₹{{ number_format($central_amount_cr, 2) }}</td>
                <td style="text-align: right;">₹{{ number_format($state_amount_cr, 2) }}</td>
                <td style="text-align: right; font-weight: bold;">₹{{ number_format($total_amount_cr, 2) }}</td>
            </tr>
            <tr style="background-color: #fafafa;">
                <td colspan="2" style="font-weight: bold; text-align: right;">Total Funds Available:</td>
                <td colspan="3" style="font-weight: bold; text-align: right;">₹{{ number_format($total_amount_cr, 2) }} Cr</td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: right;">Expenditure Incurred &amp; Utilized for Works:</td>
                <td colspan="3" style="text-align: right; font-weight: bold; color: #004d00;">₹{{ number_format($utilized_amount_cr, 2) }} Cr ({{ $financial_progress_pct }}%)</td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: right;">Unspent Closing Balance (carried to next period):</td>
                <td colspan="3" style="text-align: right; font-weight: bold;">₹{{ number_format($unspent_balance_cr, 2) }} Cr</td>
            </tr>
            @if($interest_accrued_cr > 0)
            <tr>
                <td colspan="2" style="text-align: right;">Interest accrued in SNA Account:</td>
                <td colspan="3" style="text-align: right;">₹{{ number_format($interest_accrued_cr, 2) }} Cr</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="bold" style="margin-top: 15px; font-size: 11pt;">STATUTORY CERTIFICATE</div>
    <p class="cert-para">
        1. Certified that out of <strong>₹{{ number_format($total_amount_cr, 2) }} Crore</strong> (Rupees {{ $amount_in_words }} only) of grants-in-aid sanctioned during the year <strong>{{ $financial_year }}</strong> in favour of <strong>State Government of {{ $scheme->state }}</strong> under the Ministry of Jal Shakti Sanction Order details indicated in the margin, a sum of <strong>₹{{ number_format($utilized_amount_cr, 2) }} Crore</strong> has been utilized for the approved execution of <em>{{ $scheme->scheme_name }}</em> for which it was sanctioned, and that the balance of <strong>₹{{ number_format($unspent_balance_cr, 2) }} Crore</strong> remaining unutilized at the end of the year has been carried forward / will be adjusted towards the grants-in-aid payable during the next instalment.
    </p>

    <p class="cert-para">
        2. Certified that I have satisfied myself that the conditions on which the grants-in-aid was sanctioned have been duly fulfilled / are being fulfilled and that I have exercised the following checks to see that the money was actually utilized for the purpose for which it was sanctioned:
    </p>

    <ol class="check-list" type="i">
        <li>Physical progress achieved on ground corresponds to <strong>{{ $physical_progress_pct }}%</strong> as recorded in the Measurement Book (MB) and verified by departmental engineers.</li>
        <li>Relevant invoices, contractor bills, vouchers, and muster rolls have been scrutinized by the Drawing and Disbursing Officer (DDO).</li>
        <li>No portion of the Central Assistance or matching State Share has been diverted to any other project or expenditure head.</li>
        <li>Interest accrued in the Single Nodal Agency (SNA) Account, if any, is duly accounted for as per Ministry of Finance guidelines.</li>
    </ol>

    <div class="signatures">
        <table>
            <tr>
                <td style="width: 50%;">
                    <p><strong>Prepared &amp; Verified By:</strong></p>
                    <p style="margin-top: 35px;">___________________________________<br>
                    <strong>{{ $officer_name ?: 'Executive Engineer' }}</strong><br>
                    {{ $officer_designation ?: 'Division Executive Engineer' }}<br>
                    Water Resources Department<br>
                    Government of {{ $scheme->state }}
                    </p>
                </td>
                <td style="width: 50%; text-align: right;">
                    <p><strong>Counter-Signed &amp; Approved By:</strong></p>
                    <p style="margin-top: 35px;">___________________________________<br>
                    <strong>Chief Engineer / Nodal Officer</strong><br>
                    Flood Management Directorate (WRD)<br>
                    Government of {{ $scheme->state }}<br>
                    (Official State Seal)
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer-note">
        <strong>Digital Record Reference:</strong> FMBAP-GFR12A-{{ $scheme->scheme_code }}-{{ date('YmdHis') }} &bull; Generated via Brahmaputra Board FMBAP National Monitoring Portal on {{ $generated_at }}
    </div>
</body>
</html>
