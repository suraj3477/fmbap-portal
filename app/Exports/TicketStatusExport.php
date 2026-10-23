<?php

namespace App\Exports;

use App\Models\PaymentRequest;
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

class TicketStatusExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected ?string $state;
    protected ?string $status;
    protected ?string $pendingWith;
    protected int $rowCount = 0;

    public function __construct(?string $state = null, ?string $status = null, ?string $pendingWith = null)
    {
        $user = auth()->user();
        if ($user && $user->isState() && !empty($user->state)) {
            $this->state = $user->state;
        } else {
            $this->state = ($state && $state !== 'ALL') ? $state : null;
        }

        $this->status = ($status && $status !== 'ALL') ? $status : null;
        $this->pendingWith = ($pendingWith && $pendingWith !== 'ALL') ? $pendingWith : null;
    }

    public function title(): string
    {
        return 'Ticket Status & Pendency';
    }

    public function headings(): array
    {
        return [
            'Sl No.',
            'Ticket / Claim ID',
            'State',
            'Project / Scheme Name',
            'Scheme Code',
            'Fund Component / Category',
            'Instalment',
            'Claimed Amount (₹ Cr)',
            'BB Recommended (₹ Cr)',
            'Approved / Released (₹ Cr)',
            'Ticket Status',
            'Pending With Whom',
            'Date Raised',
            'Pendency Duration',
            'Sanction Order No.',
            'Latest Official Remarks / Notes',
        ];
    }

    public function collection()
    {
        $user = auth()->user();
        $query = PaymentRequest::with('scheme')->forUser($user)->latest('id');

        if ($this->state) {
            $query->where('state', $this->state);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $tickets = $query->get();

        if ($this->pendingWith) {
            $pw = strtoupper($this->pendingWith);
            $tickets = $tickets->filter(function ($t) use ($pw) {
                $status = $t->status;
                if ($pw === 'BB') {
                    return in_array($status, [PaymentRequest::STATUS_SUBMITTED_TO_BB, PaymentRequest::STATUS_BB_MONITORING]);
                } elseif ($pw === 'MOJS') {
                    return $status === PaymentRequest::STATUS_FORWARDED_TO_MOJS;
                } elseif ($pw === 'STATE') {
                    return in_array($status, [PaymentRequest::STATUS_DRAFT, PaymentRequest::STATUS_NEEDS_CORRECTION]);
                } elseif ($pw === 'SETTLED' || $pw === 'APPROVED') {
                    return $status === PaymentRequest::STATUS_APPROVED;
                }
                return true;
            });
        }

        $this->rowCount = $tickets->count();
        return $tickets;
    }

    public function map($ticket): array
    {
        static $slNo = 0;
        $slNo++;

        $scheme = $ticket->scheme;
        $schemeName = $scheme?->scheme_name ?? 'N/A';
        $schemeCode = $scheme?->scheme_code ?? 'N/A';
        $centralPct = (int)($scheme?->central_share_pct ?: 90);
        $fundType = "FMBAP Central Share ({$centralPct}%) - " . ($scheme?->project_type ?: 'Flood Management & Erosion Protection');
        $instalment = $ticket->instalment_number ? "Instalment #{$ticket->instalment_number}" : '1st Instalment';

        $claimedCr = (float)($ticket->requested_amount_cr ?? 0);
        $bbRecCr = $ticket->bb_recommended_amount_cr !== null ? (float)$ticket->bb_recommended_amount_cr : null;
        $approvedCr = $ticket->approved_amount_cr !== null ? (float)$ticket->approved_amount_cr : ($ticket->status === PaymentRequest::STATUS_APPROVED ? $claimedCr : null);

        // Status description
        $statusLabels = [
            PaymentRequest::STATUS_DRAFT             => 'Draft (State)',
            PaymentRequest::STATUS_SUBMITTED_TO_BB   => 'Submitted to Brahmaputra Board',
            PaymentRequest::STATUS_BB_MONITORING     => 'BB Field Monitoring & Inspection',
            PaymentRequest::STATUS_FORWARDED_TO_MOJS => 'Recommended & Forwarded to Ministry (MoJS)',
            PaymentRequest::STATUS_APPROVED          => 'Approved & Disbursed (Completed)',
            PaymentRequest::STATUS_NEEDS_CORRECTION  => 'Clarification Required (Returned to State)',
            PaymentRequest::STATUS_REJECTED          => 'Rejected by Ministry',
        ];
        $statusLabel = $statusLabels[$ticket->status] ?? $ticket->status;

        // Pending with authority
        $stateName = $ticket->state ?: ($scheme?->state ?: 'State WRD');
        switch ($ticket->status) {
            case PaymentRequest::STATUS_DRAFT:
            case PaymentRequest::STATUS_NEEDS_CORRECTION:
                $pendingWith = "State Government ({$stateName})";
                break;
            case PaymentRequest::STATUS_SUBMITTED_TO_BB:
            case PaymentRequest::STATUS_BB_MONITORING:
                $pendingWith = "Brahmaputra Board (BB)";
                break;
            case PaymentRequest::STATUS_FORWARDED_TO_MOJS:
                $pendingWith = "Ministry of Jal Shakti (MoJS)";
                break;
            case PaymentRequest::STATUS_APPROVED:
                $pendingWith = "Settled / Disbursed (Completed)";
                break;
            case PaymentRequest::STATUS_REJECTED:
                $pendingWith = "Closed / Rejected (MoJS)";
                break;
            default:
                $pendingWith = "Pending Review";
                break;
        }

        // Dates & Pendency
        $raisedDate = $ticket->submitted_at ? Carbon::parse($ticket->submitted_at) : ($ticket->created_at ? Carbon::parse($ticket->created_at) : null);
        $raisedStr = $raisedDate ? $raisedDate->format('d/m/Y') : 'N/A';

        if ($ticket->status === PaymentRequest::STATUS_APPROVED) {
            $approvedDate = $ticket->approved_at ? Carbon::parse($ticket->approved_at) : Carbon::parse($ticket->updated_at);
            $diffDays = $raisedDate ? (int) abs($raisedDate->diffInDays($approvedDate)) : 0;
            $pendency = $diffDays === 0 ? 'Settled same day' : "Settled in {$diffDays} days";
        } elseif ($raisedDate) {
            $diffDays = (int) abs($raisedDate->diffInDays(now()));
            $pendency = $diffDays === 0 ? 'Today (0 days)' : "{$diffDays} days";
        } else {
            $pendency = '—';
        }

        // Sanction & Remarks
        $sanctionNo = $ticket->sanction_order_no ?: ($ticket->status === PaymentRequest::STATUS_APPROVED ? 'Sanctioned' : 'Pending');
        $remarks = $ticket->bb_remarks ?: ($ticket->mojs_remarks ?: ($ticket->state_remarks ?: '—'));

        return [
            $slNo,
            '#REQ-' . str_pad($ticket->id, 4, '0', STR_PAD_LEFT),
            $stateName,
            $schemeName,
            $schemeCode,
            $fundType,
            $instalment,
            number_format($claimedCr, 2),
            $bbRecCr !== null ? number_format($bbRecCr, 2) : '—',
            $approvedCr !== null ? number_format($approvedCr, 2) : '—',
            $statusLabel,
            $pendingWith,
            $raisedStr,
            $pendency,
            $sanctionNo,
            $remarks,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = max(2, $this->rowCount + 1);

        // Header styling
        $sheet->getStyle('A1:P1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0F4C9F'], // Indian Navy / FMBAP Blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(32);

        // Border styling for entire data matrix
        $sheet->getStyle("A1:P{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FFE2E8F0'],
                ],
            ],
        ]);

        // Alignment formatting for specific columns
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C2:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("G2:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("H2:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("L2:L{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("M2:N{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Color highlighting on 'Pending With Whom' column (L)
        for ($r = 2; $r <= $lastRow; $r++) {
            $cellValue = (string) $sheet->getCell("L{$r}")->getValue();
            if (str_contains($cellValue, 'Brahmaputra Board')) {
                $sheet->getStyle("L{$r}")->getFill()->applyFromArray([
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFEDE9FE'], // Light violet
                ]);
            } elseif (str_contains($cellValue, 'Ministry of Jal Shakti')) {
                $sheet->getStyle("L{$r}")->getFill()->applyFromArray([
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFDBEAFE'], // Light blue
                ]);
            } elseif (str_contains($cellValue, 'State Government')) {
                $sheet->getStyle("L{$r}")->getFill()->applyFromArray([
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFEF3C7'], // Light amber
                ]);
            } elseif (str_contains($cellValue, 'Settled')) {
                $sheet->getStyle("L{$r}")->getFill()->applyFromArray([
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFD1FAE5'], // Light emerald
                ]);
            }
        }

        return [];
    }
}
