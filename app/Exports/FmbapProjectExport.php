<?php

namespace App\Exports;

use App\Models\FmbapProject;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FmbapProjectExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function collection()
    {
        $query = FmbapProject::query();

        if ($this->user->isState()) {
            $query->where('state', $this->user->state);
        } elseif ($this->user->isMoJS()) {
            $query->whereIn('status', ['FORWARDED_TO_MOJS', 'REVIEWED_BY_MOJS', 'RETURNED_TO_STATE']);
        } elseif ($this->user->isBB() || $this->user->role === 'super_admin' || $this->user->role === 'admin' || $this->user->is_admin) {
            // Can see all
        } else {
            $query->where('user_id', $this->user->id);
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Scheme Code',
            'Name of Scheme',
            'State',
            'Status',
            'Estimated Cost (Cr)',
            'Executed Amount (Cr)',
            'Funding Pattern',
            'Central Share (Cr)',
            'State Share (Cr)',
            'Released Central (Cr)',
            'Released State (Cr)',
            'Balance Central (Cr)',
            'Balance State (Cr)',
            'BB Remarks',
            'MoJS Remarks',
            'Created At'
        ];
    }

    public function map($project): array
    {
        return [
            $project->id,
            $project->scheme_code,
            $project->title ?? $project->scheme_name,
            $project->state,
            $project->status,
            $project->estimated_cost_cr,
            $project->executed_amount_cr,
            $project->funding_pattern,
            $project->central_share_cr,
            $project->state_share_cr,
            $project->released_central_share_cr,
            $project->released_state_share_cr,
            $project->balance_central_share_cr,
            $project->balance_state_share_cr,
            $project->bb_remarks,
            $project->mojs_remarks,
            $project->created_at->format('Y-m-d')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text with a dark blue background and white text
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1F497D'],
                ],
            ],
        ];
    }
}
