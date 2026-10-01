<?php

namespace App\Http\Controllers;

use App\Exports\FmbapMisReportExport;
use App\Models\PaymentRequest;
use App\Models\Scheme;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class MisAnalyticsController extends Controller
{
    /**
     * Executive MIS Analytics Cockpit
     */
    public function index(Request $request)
    {
        $selectedState = $request->query('state', 'ALL');
        $selectedBasin = $request->query('basin', 'ALL');
        $selectedFY    = $request->query('fy', 'ALL');

        $analytics = $this->computeAnalytics($selectedState, $selectedBasin, $selectedFY);

        return Inertia::render('Analytics/Index', [
            'analytics'     => $analytics,
            'filters'       => [
                'state' => $selectedState,
                'basin' => $selectedBasin,
                'fy'    => $selectedFY,
            ],
            'availableStates' => Scheme::whereNotNull('state')->distinct()->pluck('state')->values(),
            'availableBasins' => Scheme::whereNotNull('river_basin')->distinct()->pluck('river_basin')->values(),
            'userRole'        => auth()->user()?->role,
        ]);
    }

    /**
     * Download Parliamentary / Executive Excel Spreadsheet
     */
    public function exportExcel(Request $request)
    {
        $state  = $request->query('state');
        $basin  = $request->query('basin');
        $status = $request->query('status');

        $fileName = 'FMBAP_Parliamentary_MIS_Report_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new FmbapMisReportExport($state, $basin, $status), $fileName);
    }

    /**
     * Download Official Executive Briefing PDF Note
     */
    public function exportPdf(Request $request)
    {
        $state = $request->query('state', 'ALL');
        $basin = $request->query('basin', 'ALL');
        $fy    = $request->query('fy', 'ALL');

        $analytics = $this->computeAnalytics($state, $basin, $fy);

        $pdf = Pdf::loadView('pdf.mis_report', [
            'macro'           => $analytics['macro'],
            'state_scorecard' => $analytics['state_scorecard'],
            'basin_matrix'    => $analytics['basin_matrix'],
            'bottlenecks'     => $analytics['bottlenecks'],
            'generated_at'    => now()->format('d M Y, H:i \I\S\T'),
        ])->setPaper('a4', 'portrait')
          ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->download('FMBAP_Executive_Briefing_Note_' . date('Ymd') . '.pdf');
    }

    /**
     * Aggregate portfolio metrics
     */
    protected function computeAnalytics(string $selectedState = 'ALL', string $selectedBasin = 'ALL', string $selectedFY = 'ALL'): array
    {
        $query = Scheme::with(['paymentRequests', 'fmbapProject']);

        if ($selectedState !== 'ALL') {
            $query->where('state', $selectedState);
        }
        if ($selectedBasin !== 'ALL') {
            $query->where('river_basin', $selectedBasin);
        }

        $schemes = $query->get();

        // 1. Macro KPIs
        $totalSchemes   = $schemes->count();
        $completedCount = $schemes->filter(fn($s) => $s->physical_status === 'Completed' || (float)$s->physical_progress_pct >= 100)->count();
        $ongoingCount   = $totalSchemes - $completedCount;

        $totalSanctionedCr = (float) $schemes->sum('sanctioned_amount_cr');
        $totalCentralShareCr = $schemes->reduce(function ($acc, $s) {
            $pct = (float) ($s->central_share_pct ?: 90);
            return $acc + ((float) $s->sanctioned_amount_cr * ($pct / 100));
        }, 0);
        $totalStateShareCr = max(0, $totalSanctionedCr - $totalCentralShareCr);

        // Released funds from approved payment requests
        $approvedRequests = PaymentRequest::where('status', PaymentRequest::STATUS_APPROVED)->get();
        $totalReleasedCr = (float) $approvedRequests->sum('requested_amount_cr');

        // Fallback for legacy seeded projects
        if ($totalReleasedCr <= 0) {
            $totalReleasedCr = (float) \App\Models\FmbapProject::sum('released_central_share_cr');
        }

        $disbursalVelocity = $totalCentralShareCr > 0 ? round(($totalReleasedCr / $totalCentralShareCr) * 100, 1) : 0;
        $avgPhysicalProgress = $totalSchemes > 0 ? round((float) $schemes->avg('physical_progress_pct'), 1) : 0;

        // 2. State-Wise Performance Scorecard
        $allStates = Scheme::whereNotNull('state')->distinct()->pluck('state');
        $stateScorecard = [];

        foreach ($allStates as $st) {
            $stSchemes = $schemes->where('state', $st);
            if ($stSchemes->isEmpty() && $selectedState !== 'ALL' && $selectedState !== $st) {
                continue;
            }

            $stCount = $stSchemes->count();
            if ($stCount === 0) continue;

            $stSanctioned = (float) $stSchemes->sum('sanctioned_amount_cr');
            $stCentralOutlay = $stSchemes->reduce(function ($acc, $s) {
                $pct = (float) ($s->central_share_pct ?: 90);
                return $acc + ((float) $s->sanctioned_amount_cr * ($pct / 100));
            }, 0);

            $stApprovedReqs = PaymentRequest::where('state', $st)->where('status', PaymentRequest::STATUS_APPROVED)->sum('requested_amount_cr');
            if ($stApprovedReqs <= 0) {
                $stApprovedReqs = (float) \App\Models\FmbapProject::where('state', $st)->sum('released_central_share_cr');
            }

            $stDisbursalPct = $stCentralOutlay > 0 ? round(($stApprovedReqs / $stCentralOutlay) * 100, 1) : 0;
            $stAvgPhysical = round((float) $stSchemes->avg('physical_progress_pct'), 1);
            $stCompleted = $stSchemes->filter(fn($s) => $s->physical_status === 'Completed' || (float)$s->physical_progress_pct >= 100)->count();
            $stOngoing = $stCount - $stCompleted;

            $pendingClaims = PaymentRequest::where('state', $st)
                ->whereIn('status', [PaymentRequest::STATUS_SUBMITTED_TO_BB, PaymentRequest::STATUS_BB_MONITORING, PaymentRequest::STATUS_FORWARDED_TO_MOJS, PaymentRequest::STATUS_NEEDS_CORRECTION])
                ->count();

            // Rank tier calculation
            $tier = 'Tier A (Leader)';
            if ($stAvgPhysical < 50 || $stDisbursalPct < 40) {
                $tier = 'Tier C (Needs Acceleration)';
            } elseif ($stAvgPhysical < 75) {
                $tier = 'Tier B (On Track)';
            }

            $stateScorecard[] = [
                'state_name'            => $st,
                'schemes_count'         => $stCount,
                'sanctioned_cr'         => round($stSanctioned, 2),
                'central_outlay_cr'     => round($stCentralOutlay, 2),
                'released_cr'           => round($stApprovedReqs, 2),
                'disbursal_pct'         => $stDisbursalPct,
                'avg_physical_pct'      => $stAvgPhysical,
                'completed_count'       => $stCompleted,
                'ongoing_count'         => $stOngoing,
                'pending_claims_count'  => $pendingClaims,
                'tier'                  => $tier,
            ];
        }

        // Sort states by highest physical progress first
        usort($stateScorecard, fn($a, $b) => $b['avg_physical_pct'] <=> $a['avg_physical_pct']);

        // 3. River Basin Matrix
        $basinMatrix = [];
        $allBasins = Scheme::whereNotNull('river_basin')->distinct()->pluck('river_basin');

        foreach ($allBasins as $basin) {
            $bSchemes = $schemes->where('river_basin', $basin);
            if ($bSchemes->isEmpty()) continue;

            $basinMatrix[] = [
                'basin_name'        => $basin,
                'schemes_count'     => $bSchemes->count(),
                'sanctioned_cr'     => round((float) $bSchemes->sum('sanctioned_amount_cr'), 2),
                'avg_physical_pct'  => round((float) $bSchemes->avg('physical_progress_pct'), 1),
            ];
        }
        usort($basinMatrix, fn($a, $b) => $b['sanctioned_cr'] <=> $a['sanctioned_cr']);

        // 4. Bottleneck & Workflow Turnaround Time (TAT) Radar
        $submittedToBbCount  = PaymentRequest::where('status', PaymentRequest::STATUS_SUBMITTED_TO_BB)->count();
        $bbMonitoringCount   = PaymentRequest::where('status', PaymentRequest::STATUS_BB_MONITORING)->count();
        $forwardedToMojsCount = PaymentRequest::where('status', PaymentRequest::STATUS_FORWARDED_TO_MOJS)->count();
        $needsCorrectionCount = PaymentRequest::where('status', PaymentRequest::STATUS_NEEDS_CORRECTION)->count();
        $approvedCount       = PaymentRequest::where('status', PaymentRequest::STATUS_APPROVED)->count();
        $rejectedCount       = PaymentRequest::where('status', PaymentRequest::STATUS_REJECTED)->count();

        // Calculate average days from submission to approval safely
        $approvedReqsWithDates = PaymentRequest::whereNotNull('submitted_at')->whereNotNull('approved_at')->get();
        $totalDays = 0;
        $validDateCount = 0;
        foreach ($approvedReqsWithDates as $ar) {
            try {
                if ($ar->submitted_at && $ar->approved_at) {
                    $sub = \Carbon\Carbon::parse($ar->submitted_at);
                    $app = \Carbon\Carbon::parse($ar->approved_at);
                    $totalDays += abs($sub->diffInDays($app));
                    $validDateCount++;
                }
            } catch (\Throwable $e) {
                // Safeguard against any malformed legacy dates
            }
        }
        $avgTurnaroundDays = $validDateCount > 0 ? round($totalDays / $validDateCount, 1) : 14;

        $bottlenecks = [
            'submitted_to_bb'      => $submittedToBbCount,
            'bb_monitoring'        => $bbMonitoringCount,
            'forwarded_to_mojs'    => $forwardedToMojsCount,
            'needs_correction'     => $needsCorrectionCount,
            'approved'             => $approvedCount,
            'rejected'             => $rejectedCount,
            'avg_turnaround_days'  => $avgTurnaroundDays,
            'stalled_claims_count' => $needsCorrectionCount + $submittedToBbCount,
        ];

        // 5. Parliamentary Register Table
        $parliamentaryList = $schemes->take(30)->map(function ($s) {
            return [
                'id'                   => $s->id,
                'scheme_code'          => $s->scheme_code,
                'scheme_name'          => $s->scheme_name,
                'state'                => $s->state,
                'district'             => $s->district ?: 'N/A',
                'river_basin'          => $s->river_basin ?: 'Brahmaputra',
                'sanctioned_amount_cr' => number_format((float) $s->sanctioned_amount_cr, 2),
                'physical_progress_pct'=> $s->physical_progress_pct ?: 0,
                'physical_status'      => $s->physical_status ?: 'Ongoing',
            ];
        });

        return [
            'macro'                => [
                'total_schemes'          => $totalSchemes,
                'completed_schemes'      => $completedCount,
                'ongoing_schemes'        => $ongoingCount,
                'total_sanctioned_cr'    => round($totalSanctionedCr, 2),
                'total_central_share_cr' => round($totalCentralShareCr, 2),
                'total_state_share_cr'   => round($totalStateShareCr, 2),
                'total_released_cr'      => round($totalReleasedCr, 2),
                'disbursal_velocity_pct' => $disbursalVelocity,
                'avg_physical_progress'  => $avgPhysicalProgress,
            ],
            'state_scorecard'      => $stateScorecard,
            'basin_matrix'         => $basinMatrix,
            'bottlenecks'          => $bottlenecks,
            'parliamentary_list'   => $parliamentaryList,
        ];
    }
}
