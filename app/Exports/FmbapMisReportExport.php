<?php

namespace App\Exports;

use App\Models\PaymentRequest;
use App\Models\Scheme;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FmbapMisReportExport implements WithMultipleSheets
{
    protected ?string $state;
    protected ?string $basin;
    protected ?string $status;

    public function __construct(?string $state = null, ?string $basin = null, ?string $status = null)
    {
        $user = auth()->user();
        if ($user && $user->isState() && !empty($user->state)) {
            $this->state = $user->state;
        } else {
            $this->state = $state;
        }
        $this->basin  = $basin;
        $this->status = $status;
    }

    public function sheets(): array
    {
        return [
            new ProjectMonitoringExport($this->state, $this->basin, $this->status),
            new MisMacroSummarySheet($this->state, $this->basin, $this->status),
            new MisStateScorecardSheet($this->state, $this->basin, $this->status),
            new MisParliamentaryRegisterSheet($this->state, $this->basin, $this->status),
        ];
    }
}

/**
 * Sheet 1: National Macro KPI & Strategic Executive Summary
 */
class MisMacroSummarySheet implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected ?string $state;
    protected ?string $basin;
    protected ?string $status;

    public function __construct(?string $state, ?string $basin, ?string $status)
    {
        $user = auth()->user();
        if ($user && $user->isState() && !empty($user->state)) {
            $this->state = $user->state;
        } else {
            $this->state = $state;
        }
        $this->basin  = $basin;
        $this->status = $status;
    }

    public function title(): string
    {
        return 'Executive Portfolio Summary';
    }

    public function headings(): array
    {
        return [
            'Indicator / Strategic Metric',
            'Portfolio Value',
            'Benchmark Unit / Details',
        ];
    }

    public function collection()
    {
        $user = auth()->user();
        $query = Scheme::forUser($user);
        if ($this->state && $this->state !== 'ALL') $query->where('state', $this->state);
        if ($this->basin && $this->basin !== 'ALL') $query->where('river_basin', $this->basin);
        $schemes = $query->get();

        $totalSchemes   = $schemes->count();
        $completedCount = $schemes->filter(fn($s) => $s->physical_status === 'Completed' || (float)$s->physical_progress_pct >= 100)->count();
        $ongoingCount   = max(0, $totalSchemes - $completedCount);

        $totalSanctionedCr = (float) $schemes->sum('sanctioned_amount_cr');
        $totalCentralShareCr = $schemes->reduce(function ($acc, $s) {
            $pct = (float) ($s->central_share_pct ?: 90);
            return $acc + ((float) $s->sanctioned_amount_cr * ($pct / 100));
        }, 0);
        $totalStateShareCr = max(0, $totalSanctionedCr - $totalCentralShareCr);

        $prQuery = PaymentRequest::forUser($user)->where('status', PaymentRequest::STATUS_APPROVED);
        if ($this->state && $this->state !== 'ALL') $prQuery->where('state', $this->state);
        $approvedPrs = $prQuery->get();

        $totalReleasedCr = (float) $approvedPrs->sum(function($r) {
            return $r->approved_amount_cr !== null ? (float)$r->approved_amount_cr : (float)$r->requested_amount_cr;
        });

        $disbursalVelocity = $totalCentralShareCr > 0 ? round(($totalReleasedCr / $totalCentralShareCr) * 100, 1) : 0;
        $avgPhysical = $totalSchemes > 0 ? round((float) $schemes->avg('physical_progress_pct'), 1) : 0;

        return collect([
            ['Programme Name', 'Flood Management and Border Areas Programme (FMBAP)', 'Ministry of Jal Shakti, GoI'],
            ['Monitoring Authority', 'Brahmaputra Board & Central Water Commission (CWC)', 'HQ Guwahati & New Delhi'],
            ['Total Statutory Outlay', number_format($totalSanctionedCr, 2), '₹ Crore (90:10 Funding Pattern)'],
            ['Central Assistance Entitlement (90%)', number_format($totalCentralShareCr, 2), '₹ Crore'],
            ['State Matching Share (10%)', number_format($totalStateShareCr, 2), '₹ Crore'],
            ['Central Assistance Disbursed', number_format($totalReleasedCr, 2), '₹ Crore (Approved Releases)'],
            ['Disbursal Velocity Ratio', $disbursalVelocity . '%', 'Percentage of Central Entitlement Drawn'],
            ['Total Schemes Monitored', $totalSchemes, 'Flood Mitigation & Anti-Erosion Schemes'],
            ['Completed Schemes', $completedCount, '100% Verified Completion'],
            ['Active Ongoing Schemes', $ongoingCount, 'Under Field Execution'],
            ['Average Physical Progress', $avgPhysical . '%', 'Brahmaputra Board Verified Baseline'],
            ['Reporting Timestamp', now()->format('d/m/Y H:i:s T'), 'Live Government Dataset'],
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF071F42'],
                ],
            ],
        ];
    }
}

/**
 * Sheet 2: Inter-State Benchmark & Composite Performance Index (CPI)
 */
class MisStateScorecardSheet implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected ?string $state;
    protected ?string $basin;
    protected ?string $status;

    public function __construct(?string $state, ?string $basin, ?string $status)
    {
        $user = auth()->user();
        if ($user && $user->isState() && !empty($user->state)) {
            $this->state = $user->state;
        } else {
            $this->state = $state;
        }
        $this->basin  = $basin;
        $this->status = $status;
    }

    public function title(): string
    {
        return 'Inter-State Scorecard (CPI)';
    }

    public function headings(): array
    {
        return [
            'National Rank',
            'State / WRD Name',
            'Total Schemes',
            'Completed',
            'Ongoing',
            'Sanctioned Outlay (₹ Cr)',
            'Central Entitlement (₹ Cr)',
            'State Matching (₹ Cr)',
            'Central Released (₹ Cr)',
            'Disbursal Velocity (%)',
            'Average Physical (%)',
            'Pending Claims in Review',
            'Composite Performance Index (CPI)',
            'Performance Classification Tier',
        ];
    }

    public function collection()
    {
        $user = auth()->user();
        $allStates = Scheme::forUser($user)->whereNotNull('state')->distinct()->pluck('state')->filter()->sort();
        $rows = [];

        foreach ($allStates as $st) {
            if ($this->state && $this->state !== 'ALL' && $this->state !== $st) continue;

            $stSchemes = Scheme::forUser($user)->where('state', $st)->get();
            $count = $stSchemes->count();
            if ($count === 0) continue;

            $sanctioned = (float) $stSchemes->sum('sanctioned_amount_cr');
            $centralOutlay = $stSchemes->reduce(function ($acc, $s) {
                $pct = (float) ($s->central_share_pct ?: 90);
                return $acc + ((float) $s->sanctioned_amount_cr * ($pct / 100));
            }, 0);
            $stateOutlay = max(0, $sanctioned - $centralOutlay);

            $released = (float) PaymentRequest::forUser($user)->where('state', $st)
                ->where('status', PaymentRequest::STATUS_APPROVED)
                ->get()
                ->sum(fn($r) => $r->approved_amount_cr !== null ? (float)$r->approved_amount_cr : (float)$r->requested_amount_cr);

            $disbursalPct = $centralOutlay > 0 ? round(($released / $centralOutlay) * 100, 1) : 0;
            $avgPhysical  = round((float) $stSchemes->avg('physical_progress_pct'), 1);
            $completed    = $stSchemes->filter(fn($s) => $s->physical_status === 'Completed' || (float)$s->physical_progress_pct >= 100)->count();
            $ongoing      = max(0, $count - $completed);
            $completionRate = round(($completed / $count) * 100, 1);

            $pendingClaims = PaymentRequest::forUser($user)->where('state', $st)
                ->whereIn('status', [
                    PaymentRequest::STATUS_SUBMITTED_TO_BB,
                    PaymentRequest::STATUS_BB_MONITORING,
                    PaymentRequest::STATUS_FORWARDED_TO_MOJS,
                    PaymentRequest::STATUS_NEEDS_CORRECTION,
                ])
                ->count();

            $totalClaims = PaymentRequest::forUser($user)->where('state', $st)->count();
            $clearanceRate = $totalClaims > 0 ? round((($totalClaims - $pendingClaims) / $totalClaims) * 100, 1) : 100;

            $cpiScore = round(
                (0.40 * min(100, $avgPhysical)) +
                (0.35 * min(100, $disbursalPct)) +
                (0.15 * min(100, $completionRate)) +
                (0.10 * min(100, $clearanceRate)),
                1
            );

            $tier = $cpiScore >= 75 ? 'Tier 1 (Leader / Benchmark)' : ($cpiScore >= 50 ? 'Tier 2 (Progressive / On Track)' : 'Tier 3 (Critical Attention Required)');

            $rows[] = [
                'state'         => $st,
                'total'         => $count,
                'completed'     => $completed,
                'ongoing'       => $ongoing,
                'sanctioned'    => number_format($sanctioned, 2),
                'central'       => number_format($centralOutlay, 2),
                'state_share'   => number_format($stateOutlay, 2),
                'released'      => number_format($released, 2),
                'disbursal_pct' => $disbursalPct . '%',
                'avg_physical'  => $avgPhysical . '%',
                'pending'       => $pendingClaims,
                'cpi'           => $cpiScore,
                'tier'          => $tier,
            ];
        }

        usort($rows, fn($a, $b) => $b['cpi'] <=> $a['cpi']);

        return collect($rows)->map(function ($r, $idx) {
            return [
                $idx + 1,
                $r['state'],
                $r['total'],
                $r['completed'],
                $r['ongoing'],
                $r['sanctioned'],
                $r['central'],
                $r['state_share'],
                $r['released'],
                $r['disbursal_pct'],
                $r['avg_physical'],
                $r['pending'],
                $r['cpi'],
                $r['tier'],
            ];
        });
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0F4C9F'],
                ],
            ],
        ];
    }
}

/**
 * Sheet 3: Complete Scheme-by-Scheme Parliamentary Master Register
 */
class MisParliamentaryRegisterSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected ?string $state;
    protected ?string $basin;
    protected ?string $status;

    public function __construct(?string $state, ?string $basin, ?string $status)
    {
        $user = auth()->user();
        if ($user && $user->isState() && !empty($user->state)) {
            $this->state = $user->state;
        } else {
            $this->state = $state;
        }
        $this->basin  = $basin;
        $this->status = $status;
    }

    public function title(): string
    {
        return 'Parliamentary Master Register';
    }

    public function collection()
    {
        $user = auth()->user();
        $query = Scheme::forUser($user)->with(['paymentRequests', 'progressReports', 'fmbapProject'])
            ->orderBy('state')
            ->orderBy('scheme_code');

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
            'Scheme Name & Scope',
            'State / UT',
            'District',
            'Executive Division',
            'River Basin / Catchment',
            'Plan Period',
            'Sanctioned Cost (₹ Cr)',
            'Central Share %',
            'State Share %',
            'Central Assistance Outlay (₹ Cr)',
            'State Matching Outlay (₹ Cr)',
            'Central Released (₹ Cr)',
            'Balance Central Share (₹ Cr)',
            'Physical Progress (%)',
            'Financial Progress (%)',
            'Physical Execution Status',
            'Active Release Claims Count',
            'Latest Sanction Order No.',
            'Latest Sanction Date',
            'Latest Monitoring / Inspection Date',
        ];
    }

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
            ->where('status', PaymentRequest::STATUS_APPROVED)
            ->get()
            ->sum(fn($r) => $r->approved_amount_cr !== null ? (float)$r->approved_amount_cr : (float)$r->requested_amount_cr);

        if ($releasedCentral <= 0 && $scheme->fmbapProject) {
            $releasedCentral = (float) $scheme->fmbapProject->released_central_share_cr;
        }

        $balanceCentral = round(max(0, $centralOutlay - $releasedCentral), 2);
        $activeClaimsCount = $scheme->paymentRequests()
            ->whereNotIn('status', [PaymentRequest::STATUS_APPROVED, PaymentRequest::STATUS_REJECTED])
            ->count();

        $latestPr = $scheme->paymentRequests->sortByDesc('id')->first();
        $latestReport = $scheme->progressReports->sortByDesc('id')->first();

        return [
            $slNo,
            $scheme->scheme_code,
            $scheme->scheme_name,
            $scheme->state,
            $scheme->district ?: ($scheme->division ?: 'State WRD'),
            $scheme->division ?: 'WRD Division',
            $scheme->river_basin ?: 'Brahmaputra',
            $scheme->plan_period ?: '15th Finance Commission',
            number_format($sanctioned, 2),
            $centralPct . '%',
            $statePct . '%',
            number_format($centralOutlay, 2),
            number_format($stateOutlay, 2),
            number_format($releasedCentral, 2),
            number_format($balanceCentral, 2),
            ($scheme->physical_progress_pct ?: 0) . '%',
            ($latestReport?->financial_progress_pct ?: ($centralOutlay > 0 ? round(($releasedCentral / $centralOutlay) * 100, 1) : 0)) . '%',
            $scheme->physical_status ?: ($scheme->physical_progress_pct >= 100 ? 'Completed' : 'Ongoing'),
            $activeClaimsCount,
            $latestPr?->sanction_order_no ?: 'Pending MoJS Sanction',
            $latestPr?->sanction_order_date?->format('d/m/Y') ?: 'N/A',
            $latestReport?->bb_reviewed_at?->format('d/m/Y') ?: 'Routine Verification',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF047857'],
                ],
            ],
        ];
    }
}
