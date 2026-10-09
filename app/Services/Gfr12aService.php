<?php

namespace App\Services;

use App\Models\PaymentRequest;
use App\Models\Scheme;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class Gfr12aService
{
    /**
     * Get pre-filled statutory data for a scheme and optional payment request.
     */
    public function getPreFillData(Scheme $scheme, ?PaymentRequest $paymentRequest = null): array
    {
        $sanctioned = (float) $scheme->sanctioned_amount_cr;
        $centralPct = (float) ($scheme->central_share_pct ?: 90);
        $statePct   = (float) ($scheme->state_share_pct ?: 10);

        $effectiveCr = $paymentRequest 
            ? ((float) ($paymentRequest->approved_amount_cr ?: $paymentRequest->requested_amount_cr)) 
            : 0.00;
        if ($effectiveCr <= 0 && $sanctioned > 0) {
            // Default to instalment estimation or full sanctioned
            $effectiveCr = round(($sanctioned * ($centralPct / 100)) / 2, 2);
        }

        $centralShareCr = round($effectiveCr * ($centralPct / 100), 2);
        $stateShareCr   = round($effectiveCr * ($statePct / 100), 2);
        if ($centralPct >= 90) {
            // Under 90:10, requested central assistance is usually the central share directly
            $centralShareCr = $effectiveCr;
            $stateShareCr   = round($centralShareCr * ($statePct / $centralPct), 2);
        }
        $totalAvailableCr = round($centralShareCr + $stateShareCr, 2);

        $physProgress = $paymentRequest ? (float) $paymentRequest->physical_progress_pct : (float) $scheme->physical_progress_pct;
        $finProgress  = $paymentRequest ? (float) $paymentRequest->financial_progress_pct : min(100, (float) $scheme->physical_progress_pct);

        $utilizedCr = round($totalAvailableCr * ($finProgress / 100), 2);
        $unspentCr  = round(max(0, $totalAvailableCr - $utilizedCr), 2);

        $now = now();
        $fyStart = $now->month >= 4 ? $now->year : $now->year - 1;
        $financialYear = $fyStart . '-' . ($fyStart + 1);

        $sanctionRef = $paymentRequest?->sanction_order_no 
            ?: ($scheme->metadata['sanction_letter_no'] 
            ?? ('MoJS/FMBAP/' . ($scheme->state ? strtoupper(substr($scheme->state, 0, 3)) : 'NE') . '/' . ($scheme->scheme_code ?: 'SCH') . '/' . $financialYear));

        $sanctionDate = $paymentRequest?->sanction_order_date 
            ? \Carbon\Carbon::parse($paymentRequest->sanction_order_date)->format('d/m/Y')
            : $now->subMonths(2)->format('d/m/Y');

        return [
            'scheme_id'              => $scheme->id,
            'scheme_code'            => $scheme->scheme_code,
            'scheme_name'            => $scheme->scheme_name,
            'state'                  => $scheme->state,
            'river_basin'            => $scheme->river_basin,
            'district'               => $scheme->district,
            'financial_year'         => $financialYear,
            'instalment_number'      => $paymentRequest?->instalment_number ?: 1,
            'sanction_letter_no'     => $sanctionRef,
            'sanction_date'          => $sanctionDate,
            'central_amount_cr'      => $centralShareCr,
            'state_amount_cr'        => $stateShareCr,
            'total_amount_cr'        => $totalAvailableCr,
            'utilized_amount_cr'     => $utilizedCr,
            'unspent_balance_cr'     => $unspentCr,
            'interest_accrued_cr'    => 0.00,
            'physical_progress_pct'  => $physProgress,
            'financial_progress_pct' => $finProgress,
            'officer_name'           => auth()->user()?->name ?: 'Executive Engineer',
            'officer_designation'    => 'Executive Engineer, WRD Division',
        ];
    }

    /**
     * Build the DomPDF instance.
     */
    public function buildPdf(array $data, Scheme $scheme)
    {
        $totalAmount = (float) ($data['total_amount_cr'] ?? 0);
        $amountInWords = $this->numberToIndianWords($totalAmount);

        $payload = array_merge($data, [
            'scheme'          => $scheme,
            'amount_in_words' => $amountInWords,
            'generated_at'    => now()->format('d M Y, H:i \I\S\T'),
        ]);

        return Pdf::loadView('pdf.gfr12a', $payload)
            ->setPaper('a4', 'portrait')
            ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
    }

    /**
     * Generate the PDF and automatically attach it to a PaymentRequest's utilization_certificate_path.
     */
    public function generateAndAttach(array $data, PaymentRequest $paymentRequest): string
    {
        $scheme = $paymentRequest->scheme ?: Scheme::findOrFail($data['scheme_id']);
        $pdf = $this->buildPdf($data, $scheme);

        $fileName = 'GFR12A_' . ($scheme->scheme_code ?: 'SCH') . '_Instalment_' . ($paymentRequest->instalment_number ?: 1) . '_' . time() . '.pdf';
        $relativeDir = 'fund_release/uc';
        $fullPath = $relativeDir . '/' . $fileName;

        Storage::disk('public')->put($fullPath, $pdf->output());

        $storedPath = '/storage/' . $fullPath;
        $paymentRequest->update([
            'utilization_certificate_path' => $storedPath,
        ]);

        return $storedPath;
    }

    /**
     * Simple Indian numbering word generator for Crores & Lakhs.
     */
    public function numberToIndianWords(float $amountCr): string
    {
        if ($amountCr <= 0) {
            return 'Zero';
        }

        $wholeCrores = (int) floor($amountCr);
        $fraction = round(($amountCr - $wholeCrores) * 100); // in lakhs (approx 1 Cr = 100 Lakh)

        $words = [];
        if ($wholeCrores > 0) {
            $words[] = $this->smallNumToWords($wholeCrores) . ' Crore';
        }
        if ($fraction > 0) {
            $words[] = $this->smallNumToWords((int)$fraction) . ' Lakh';
        }

        return implode(' and ', $words) ?: 'Zero';
    }

    private function smallNumToWords(int $n): string
    {
        $ones = [
            0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
            15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
        ];

        $tens = [
            2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
        ];

        if ($n < 20) {
            return $ones[$n];
        }

        if ($n < 100) {
            return $tens[(int)($n / 10)] . ($n % 10 > 0 ? ' ' . $ones[$n % 10] : '');
        }

        if ($n < 1000) {
            return $ones[(int)($n / 100)] . ' Hundred' . ($n % 100 > 0 ? ' ' . $this->smallNumToWords($n % 100) : '');
        }

        return (string) $n;
    }
}
