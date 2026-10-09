<?php

namespace App\Exports;

use App\Models\Scheme;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FmbapMisReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected ?string $state;
    protected ?string $basin;
    protected ?string $status;

    public function __construct(?string $state = null, ?string $basin = null, ?string $status = null)
    {
        $this->state = $state;
        $this->basin = $basin;
        $this->status = $status;
    }

    public function collection()
    {
        $query = Scheme::with(['paymentRequests', 'fmbapProject'])->orderBy('state')->orderBy('scheme_code');

        if ($this->state && $this->state !== 'ALL') {
            $query->where('state', $this->state);
        }
        if ($this->basin && $this->basin !== 'ALL') {
            $query->where('river_basin', $this->basin);
        }
        if ($this->status === 'COMPLETED') {
            $query->where(function ($q) {
                $q->where('physical_status', 'Completed')->orWhere('physical_progress_pct', '>=', 100);
            });
        } elseif ($this->status === 'ONGOING') {
            $query->where('physical_status', '!=', 'Completed')->where('physical_progress_pct', '<', 100);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Sl. No.',
            'Scheme Code',
            'Scheme Name',
            'State',
            'District',
            'River Basin',
            'Plan Period',
            'Sanctioned Cost (₹ Cr)',
            'Central Share %',
            'State Share %',
            'Central Assistance Outlay (₹ Cr)',
            'State Matching Outlay (₹ Cr)',
            'Total Central Released (₹ Cr)',
            'Balance Central Share (₹ Cr)',
            'Physical Progress (%)',
            'Physical Status',
            'Active Release Claims Count',
            'Latest Brahmaputra Board Inspection Date',
        ];
    }

    /**
     * @param Scheme $scheme
     */
    public function map($scheme): array
    {
        static $slNo = 0;
        $slNo++;

        $sanctioned = (float) $scheme->sanctioned_amount_cr;
        $centralPct = (float) ($scheme->central_share_pct ?: 90);
        $statePct   = (float) ($scheme->state_share_pct ?: 10);

        $centralOutlay = round($sanctioned * ($centralPct / 100), 2);
        $stateOutlay   = round($sanctioned * ($statePct / 100), 2);

        $releasedCentral = (float) $scheme->paymentRequests()
            ->where('status', \App\Models\PaymentRequest::STATUS_APPROVED)
            ->get()
            ->sum(function($r) {
                return $r->approved_amount_cr !== null ? (float)$r->approved_amount_cr : (float)$r->requested_amount_cr;
            });

        if ($releasedCentral <= 0 && $scheme->fmbapProject) {
            $releasedCentral = (float) $scheme->fmbapProject->released_central_share_cr;
        }

        $balanceCentral = round(max(0, $centralOutlay - $releasedCentral), 2);
        $activeClaimsCount = $scheme->paymentRequests()
            ->whereNotIn('status', [\App\Models\PaymentRequest::STATUS_APPROVED, \App\Models\PaymentRequest::STATUS_REJECTED])
            ->count();

        $latestInspection = $scheme->metadata['bb_inspection_date'] ?? null;
        if (!$latestInspection && $scheme->paymentRequests()->exists()) {
            $latestReport = $scheme->paymentRequests()->with('bbMonitoringReport')->latest()->first()?->bbMonitoringReport;
            $latestInspection = $latestReport?->inspection_date?->format('Y-m-d');
        }

        return [
            $slNo,
            $scheme->scheme_code,
            $scheme->scheme_name,
            $scheme->state,
            $scheme->district ?: 'N/A',
            $scheme->river_basin ?: 'Brahmaputra',
            $scheme->plan_period ?: '15th Finance Commission (2021-26)',
            number_format($sanctioned, 2),
            $centralPct . '%',
            $statePct . '%',
            number_format($centralOutlay, 2),
            number_format($stateOutlay, 2),
            number_format($releasedCentral, 2),
            number_format($balanceCentral, 2),
            ($scheme->physical_progress_pct ?: 0) . '%',
            $scheme->physical_status ?: ($scheme->physical_progress_pct >= 100 ? 'Completed' : 'Ongoing'),
            $activeClaimsCount,
            $latestInspection ?: 'Pending Inspection',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0F4C9F'],
                ],
            ],
        ];
    }
}
