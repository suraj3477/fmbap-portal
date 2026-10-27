<?php

namespace App\Exports;

use App\Models\PaymentRequest;
use App\Models\ProgressReport;
use App\Models\Scheme;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectMonitoringExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected ?string $state;
    protected ?string $basin;
    protected ?string $status;
    protected ?string $pendingWith;
    protected int $rowCount = 0;

    public function __construct(?string $state = null, ?string $basin = null, ?string $status = null, ?string $pendingWith = null)
    {
        $user = auth()->user();
        if ($user && $user->isState() && !empty($user->state)) {
            $this->state = $user->state;
        } else {
            $this->state = ($state && $state !== 'ALL') ? $state : null;
        }

        $this->basin       = ($basin && $basin !== 'ALL') ? $basin : null;
        $this->status      = ($status && $status !== 'ALL') ? $status : null;
        $this->pendingWith = ($pendingWith && $pendingWith !== 'ALL') ? $pendingWith : null;
    }

    public function title(): string
    {
        return 'Project Status & Pendency';
    }

    public function headings(): array
    {
        return [
            'Sl. No.',
            'Scheme Code',
            'Project / Scheme Title',
            'State / UT',
            'District / Division',
            'River Basin',
            'Sanctioned Cost (₹ Cr)',
            'Central Entitlement (₹ Cr)',
            'Central Released (₹ Cr)',
            'Central Balance (₹ Cr)',
            'Physical Progress (%)',
            'Financial Progress (%)',
            'Physical Status',
            'Pending At What Level (BB / MoJS / State)',
            'Pendency Type (Fund vs Project)',
            'Details',
            'Days Pending (From How Long)',
            'Aging Category',
            'Delay Status (>30 Days)',
            'Next Action Required',
            'Active Ticket / Requisition ID',
            'Active Claim Amount (₹ Cr)',
            'Pending Since Date',
            'Latest Official Remarks / Notes',
        ];
    }

    public function collection()
    {
        $user = auth()->user();
        $query = Scheme::forUser($user)->with([
            'paymentRequests',
            'progressReports' => fn($q) => $q->latest()->limit(3),
            'fmbapProject',
        ])->orderBy('state')->orderBy('scheme_code');

        if ($this->state) {
            $query->where('state', $this->state);
        }
        if ($this->basin) {
            $query->where('river_basin', $this->basin);
        }
        if ($this->status === 'COMPLETED') {
            $query->where(function ($q) {
                $q->where('physical_status', 'Completed')->orWhere('physical_progress_pct', '>=', 100);
            });
        } elseif ($this->status === 'ONGOING') {
            $query->where('physical_status', '!=', 'Completed')->where('physical_progress_pct', '<', 100);
        }

        $schemes = $query->get();

        if ($this->pendingWith) {
            $pw = strtoupper($this->pendingWith);
            $schemes = $schemes->filter(function ($s) use ($pw) {
                $activePr = $s->paymentRequests->filter(fn($r) => !in_array($r->status, [PaymentRequest::STATUS_APPROVED, PaymentRequest::STATUS_REJECTED]))->sortByDesc('id')->first();
                $party = 'STATE';
                if ($activePr) {
                    if (in_array($activePr->status, [PaymentRequest::STATUS_SUBMITTED_TO_BB, PaymentRequest::STATUS_BB_MONITORING])) {
                        $party = 'BB';
                    } elseif ($activePr->status === PaymentRequest::STATUS_FORWARDED_TO_MOJS) {
                        $party = 'MOJS';
                    } else {
                        $party = 'STATE';
                    }
                } elseif ($s->physical_status === 'Completed' || (float)$s->physical_progress_pct >= 100) {
                    $party = 'SETTLED';
                }

                if ($pw === 'BB') return $party === 'BB';
                if ($pw === 'MOJS') return $party === 'MOJS';
                if ($pw === 'STATE') return $party === 'STATE';
                if ($pw === 'SETTLED' || $pw === 'COMPLETED') return $party === 'SETTLED';
                return true;
            });
        }

        $this->rowCount = $schemes->count();
        return $schemes;
    }

    public function map($scheme): array
    {
        static $slNo = 0;
        $slNo++;

        $sanctioned = (float) $scheme->sanctioned_amount_cr;
        $centralPct = (float) ($scheme->central_share_pct ?: 90);
        $centralEntitlement = round($sanctioned * ($centralPct / 100), 2);

        $released = (float) $scheme->paymentRequests
            ->where('status', PaymentRequest::STATUS_APPROVED)
            ->sum(fn($r) => $r->approved_amount_cr !== null ? (float)$r->approved_amount_cr : (float)$r->requested_amount_cr);

        if ($released <= 0 && $scheme->fmbapProject) {
            $released = (float) $scheme->fmbapProject->released_central_share_cr;
        }

        $balanceCentral = round(max(0, $centralEntitlement - $released), 2);

        $activePr = $scheme->paymentRequests
            ->filter(fn($r) => !in_array($r->status, [PaymentRequest::STATUS_APPROVED, PaymentRequest::STATUS_REJECTED]))
            ->sortByDesc('id')
            ->first();

        $activeReport = $scheme->progressReports
            ->filter(fn($r) => !in_array($r->status, [ProgressReport::STATUS_APPROVED]))
            ->sortByDesc('id')
            ->first();

        $latestReport = $scheme->progressReports->sortByDesc('id')->first();
        $isCompleted = ($scheme->physical_status === 'Completed' || (float)$scheme->physical_progress_pct >= 100);

        if ($activePr) {
            $claimId = '#REQ-' . str_pad($activePr->id, 4, '0', STR_PAD_LEFT);
            $claimCr = (float)($activePr->requested_amount_cr ?? 0);
            $fromDate = $activePr->submitted_at ? Carbon::parse($activePr->submitted_at) : ($activePr->created_at ? Carbon::parse($activePr->created_at) : null);
            $daysCount = $fromDate ? (int) abs($fromDate->diffInDays(now())) : 0;
            $sinceStr = $fromDate ? $fromDate->format('d/m/Y') : 'N/A';
            $remarks = $activePr->bb_remarks ?: ($activePr->mojs_remarks ?: ($activePr->state_remarks ?: 'Under active statutory review'));

            switch ($activePr->status) {
                case PaymentRequest::STATUS_SUBMITTED_TO_BB:
                case PaymentRequest::STATUS_BB_MONITORING:
                    $stage = 'BB Field Monitoring & Inspection';
                    $pendingWith = 'Brahmaputra Board (BB)';
                    $action = 'Field verification & appraisal recommendation';
                    break;
                case PaymentRequest::STATUS_FORWARDED_TO_MOJS:
                    $stage = 'Ministry Sanction & Release Desk';
                    $pendingWith = 'Ministry of Jal Shakti (MoJS)';
                    $action = 'Central financial concurrence & release order';
                    break;
                case PaymentRequest::STATUS_NEEDS_CORRECTION:
                    $stage = 'Clarification Required (Returned to State)';
                    $pendingWith = "State Government ({$scheme->state})";
                    $action = 'Submit rectified documents & compliance reply';
                    break;
                default:
                    $stage = 'Draft Claim (State)';
                    $pendingWith = "State Government ({$scheme->state})";
                    $action = 'Final submission to Brahmaputra Board';
                    break;
            }
        } elseif ($isCompleted) {
            $claimId = 'COMPLETED';
            $claimCr = 0.0;
            $stage = 'Project 100% Completed';
            $pendingWith = 'Settled / Fully Handed Over';
            $action = 'Project completion documentation filed';
            $daysCount = 0;
            $sinceStr = 'Completed';
            $remarks = 'All works completed and verified.';
        } elseif ($activeReport) {
            $claimId = '#REP-' . str_pad($activeReport->id, 4, '0', STR_PAD_LEFT);
            $claimCr = 0.0;
            $fromDate = $activeReport->created_at ? Carbon::parse($activeReport->created_at) : null;
            $daysCount = $fromDate ? (int) abs($fromDate->diffInDays(now())) : 0;
            $sinceStr = $fromDate ? $fromDate->format('d/m/Y') : 'N/A';
            $remarks = $activeReport->bb_remarks ?: ($activeReport->mojs_remarks ?: 'Progress report under review');

            switch ($activeReport->status) {
                case ProgressReport::STATUS_SUBMITTED:
                    $stage = 'BB Monthly Progress Verification';
                    $pendingWith = 'Brahmaputra Board (BB)';
                    $action = 'Verification of physical milestones on ground';
                    break;
                case ProgressReport::STATUS_REVIEWED_BY_BB:
                    $stage = 'Ministry Quarterly Oversight';
                    $pendingWith = 'Ministry of Jal Shakti (MoJS)';
                    $action = 'Quarterly progress sign-off';
                    break;
                default:
                    $stage = 'Progress Report Rectification';
                    $pendingWith = "State Government ({$scheme->state})";
                    $action = 'Update physical measurements & re-submit';
                    break;
            }
        } else {
            $claimId = 'EXECUTION';
            $claimCr = 0.0;
            $stage = (float)$scheme->physical_progress_pct < 20 ? 'Lagging Execution (<20%)' : 'On Track (Civil Works)';
            $pendingWith = "State Government ({$scheme->state})";
            $action = 'Execute next ground milestone & submit claim / UC';
            $fromDate = $scheme->updated_at ? Carbon::parse($scheme->updated_at) : null;
            $daysCount = $fromDate ? (int) min(90, abs($fromDate->diffInDays(now()))) : 10;
            $sinceStr = $fromDate ? $fromDate->format('d/m/Y') : 'Ongoing';
            $remarks = 'Field execution in progress under State Executive Engineer.';
        }

        // Aging
        if ($isCompleted) {
            $agingCategory = 'Completed';
            $slaStatus = 'Completed (N/A)';
            $pendencyStr = 'Completed';
        } else {
            $pendencyStr = "{$daysCount} days";
            $slaStatus = $daysCount > 30 ? "Delayed ({$daysCount} days - Exceeds 30d)" : "On Schedule ({$daysCount}/30d)";

            if ($daysCount <= 15) {
                $agingCategory = '< 15 Days (Normal)';
            } elseif ($daysCount <= 30) {
                $agingCategory = '16 - 30 Days (Standard Window)';
            } elseif ($daysCount <= 60) {
                $agingCategory = '31 - 60 Days (Delayed)';
            } else {
                $agingCategory = '> 60 Days (Critical Delay)';
            }
        }

        // Category & What is Pending
        if ($activePr) {
            $pendencyType = 'Fund Release';
            $pendingWhat = "{$claimId} (" . ($activePr->instalment_number ? "Instalment #{$activePr->instalment_number}" : "Claim") . ") — ₹" . number_format($claimCr, 2) . " Cr";
        } elseif ($activeReport) {
            $pendencyType = 'Project Report';
            $pendingWhat = "{$claimId} Progress Report & Geo-tag Validation";
        } elseif ($isCompleted) {
            $pendencyType = 'None (Settled)';
            $pendingWhat = 'Project Completed (100%) & Commissioned';
        } else {
            $pendencyType = 'Project Execution';
            $pct = (float)$scheme->physical_progress_pct;
            $pendingWhat = $pct <= 0 ? 'Ground Mobilization & Tender Award' : "Civil Works Execution ({$pct}%)";
        }

        return [
            $slNo,
            $scheme->scheme_code,
            $scheme->scheme_name,
            $scheme->state,
            $scheme->district ?: ($scheme->division ?: 'State WRD'),
            $scheme->river_basin ?: 'Brahmaputra',
            number_format($sanctioned, 2),
            number_format($centralEntitlement, 2),
            number_format($released, 2),
            number_format($balanceCentral, 2),
            ($scheme->physical_progress_pct ?: 0) . '%',
            ($latestReport?->financial_progress_pct ?: ($centralEntitlement > 0 ? round(($released / $centralEntitlement) * 100, 1) : 0)) . '%',
            $scheme->physical_status ?: ($isCompleted ? 'Completed' : 'Ongoing'),
            $pendingWith,
            $pendencyType,
            $pendingWhat,
            $pendencyStr,
            $agingCategory,
            $slaStatus,
            $action,
            $claimId,
            $claimCr > 0 ? number_format($claimCr, 2) : '—',
            $sinceStr,
            $remarks,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // 1. Header Styling
        $sheet->getStyle('A1:X1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size'  => 10,
                'name'  => 'Segoe UI',
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0A2540'], // MoJS Deep Navy
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // 2. Data Rows Styling
        $lastRow = max(2, $this->rowCount + 1);

        $sheet->getStyle("A2:W{$lastRow}")->applyFromArray([
            'font' => [
                'name' => 'Segoe UI',
                'size' => 9.5,
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FFE2E8F0'],
                ],
            ],
        ]);

        // Center alignments
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("K2:N{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("O2:R{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("T2:V{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Right alignment for financial columns
        $sheet->getStyle("G2:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }
}
