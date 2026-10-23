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
     * Executive MIS Analytics & Strategic Intelligence Cockpit
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if ($user && $user->isState() && !empty($user->state)) {
            $selectedState   = $user->state;
            $availableStates = collect([$user->state]);
            $availableBasins = Scheme::forUser($user)->whereNotNull('river_basin')->distinct()->pluck('river_basin')->filter()->sort()->values();
        } else {
            $selectedState   = $request->query('state', 'ALL');
            $availableStates = Scheme::whereNotNull('state')->distinct()->pluck('state')->filter()->sort()->values();
            $availableBasins = Scheme::whereNotNull('river_basin')->distinct()->pluck('river_basin')->filter()->sort()->values();
        }

        $selectedBasin  = $request->query('basin', 'ALL');
        $selectedStatus = $request->query('status', 'ALL');
        $selectedFY     = $request->query('fy', 'ALL');

        $analytics = $this->computeAnalytics($selectedState, $selectedBasin, $selectedStatus, $selectedFY);

        return Inertia::render('Analytics/Index', [
            'analytics'         => $analytics,
            'filters'           => [
                'state'  => $selectedState,
                'basin'  => $selectedBasin,
                'status' => $selectedStatus,
                'fy'     => $selectedFY,
            ],
            'availableStates'   => $availableStates,
            'availableBasins'   => $availableBasins,
            'userRole'          => $user?->role,
        ]);
    }

    /**
     * Download Parliamentary / Executive Excel Spreadsheet
     */
    public function exportExcel(Request $request)
    {
        $user   = auth()->user();
        $state  = ($user && $user->isState() && !empty($user->state)) ? $user->state : $request->query('state');
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
        $user   = auth()->user();
        $state  = ($user && $user->isState() && !empty($user->state)) ? $user->state : $request->query('state', 'ALL');
        $basin  = $request->query('basin', 'ALL');
        $status = $request->query('status', 'ALL');
        $fy     = $request->query('fy', 'ALL');

        $analytics = $this->computeAnalytics($state, $basin, $status, $fy);

        $pdf = Pdf::loadView('pdf.mis_report', [
            'macro'            => $analytics['macro'],
            'state_scorecard'  => $analytics['state_scorecard'],
            'basin_matrix'     => $analytics['basin_matrix'],
            'bottlenecks'      => $analytics['bottlenecks'],
            'discrepancies'    => $analytics['discrepancies'],
            'category_matrix'  => $analytics['category_matrix'],
            'filters'          => [
                'state' => $state,
                'basin' => $basin,
            ],
            'generated_at'     => now()->format('d M Y, H:i \I\S\T'),
        ])->setPaper('a4', 'portrait')
          ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->download('FMBAP_Executive_Briefing_Note_' . date('Ymd') . '.pdf');
    }

    /**
     * Aggregate portfolio metrics with multi-dimensional intelligence
     */
    protected function computeAnalytics(string $selectedState = 'ALL', string $selectedBasin = 'ALL', string $selectedStatus = 'ALL', string $selectedFY = 'ALL'): array
    {
        $user = auth()->user();
        if ($user && $user->isState() && !empty($user->state)) {
            $selectedState = $user->state;
        }

        $query = Scheme::forUser($user)->with([
            'paymentRequests.bbMonitoringReport',
            'progressReports' => fn($q) => $q->latest()->limit(3),
            'fmbapProject',
        ]);

        if ($selectedState !== 'ALL') {
            $query->where('state', $selectedState);
        }
        if ($selectedBasin !== 'ALL') {
            $query->where('river_basin', $selectedBasin);
        }
        if ($selectedStatus === 'COMPLETED') {
            $query->where(function ($q) {
                $q->where('physical_status', 'Completed')->orWhere('physical_progress_pct', '>=', 100);
            });
        } elseif ($selectedStatus === 'ONGOING') {
            $query->where('physical_status', '!=', 'Completed')->where('physical_progress_pct', '<', 100);
        }

        $schemes = $query->get();

        // ── 1. Macro KPIs ────────────────────────────────────────────────────────
        $totalSchemes   = $schemes->count();
        $completedCount = $schemes->filter(fn($s) => $s->physical_status === 'Completed' || (float)$s->physical_progress_pct >= 100)->count();
        $stalledCount   = $schemes->filter(fn($s) => (float)$s->physical_progress_pct < 20 && stripos($s->physical_status ?? '', 'Stalled') !== false)->count();
        $ongoingCount   = max(0, $totalSchemes - $completedCount);

        $totalSanctionedCr = (float) $schemes->sum('sanctioned_amount_cr');
        $totalCentralShareCr = $schemes->reduce(function ($acc, $s) {
            $pct = (float) ($s->central_share_pct ?: 90);
            return $acc + ((float) $s->sanctioned_amount_cr * ($pct / 100));
        }, 0);
        $totalStateShareCr = max(0, $totalSanctionedCr - $totalCentralShareCr);

        // Filter payment requests aligned with selected state
        $prQuery = PaymentRequest::forUser($user)->where('status', PaymentRequest::STATUS_APPROVED);
        if ($selectedState !== 'ALL') {
            $prQuery->where('state', $selectedState);
        }
        $approvedRequests = $prQuery->get();

        $totalReleasedCr = (float) $approvedRequests->sum(function($r) {
            return $r->approved_amount_cr !== null ? (float)$r->approved_amount_cr : (float)$r->requested_amount_cr;
        });

        // Fallback for legacy seeded projects if payment request ledger is blank
        if ($totalReleasedCr <= 0) {
            $fmbapQuery = \App\Models\FmbapProject::query();
            if ($selectedState !== 'ALL') {
                $fmbapQuery->where('state', $selectedState);
            }
            $totalReleasedCr = (float) $fmbapQuery->sum('released_central_share_cr');
        }

        $totalCurtailmentCr = (float) $approvedRequests->sum(fn($r) => (float)($r->deduction_amount_cr ?? 0));
        $disbursalVelocity  = $totalCentralShareCr > 0 ? round(($totalReleasedCr / $totalCentralShareCr) * 100, 1) : 0;
        $avgPhysicalProgress = $totalSchemes > 0 ? round((float) $schemes->avg('physical_progress_pct'), 1) : 0;

        // Estimated unspent balance at State SNA
        $totalUtilizedCentral = $schemes->sum(fn($s) => ((float)($s->fund_utilised_cs_lakh ?? 0)) / 100);
        $unspentBalanceCr = max(0, round($totalReleasedCr - $totalUtilizedCentral, 2));

        // UC compliance: count of approved claims with UC uploaded
        $approvedWithUcCount = $approvedRequests->filter(fn($r) => !empty($r->utilization_certificate_path))->count();
        $ucCompliancePct = $approvedRequests->count() > 0 ? round(($approvedWithUcCount / $approvedRequests->count()) * 100, 1) : 100;

        // ── 2. State-Wise Performance Scorecard & Composite Index (CPI) ──────────
        $allStates = Scheme::forUser($user)->whereNotNull('state')->distinct()->pluck('state')->filter()->sort();
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
            $stStateOutlay = max(0, $stSanctioned - $stCentralOutlay);

            $stApprovedReqs = (float) PaymentRequest::where('state', $st)
                ->where('status', PaymentRequest::STATUS_APPROVED)
                ->get()
                ->sum(function($r) {
                    return $r->approved_amount_cr !== null ? (float)$r->approved_amount_cr : (float)$r->requested_amount_cr;
                });
            if ($stApprovedReqs <= 0) {
                $stApprovedReqs = (float) \App\Models\FmbapProject::where('state', $st)->sum('released_central_share_cr');
            }

            $stDisbursalPct = $stCentralOutlay > 0 ? round(($stApprovedReqs / $stCentralOutlay) * 100, 1) : 0;
            $stAvgPhysical  = round((float) $stSchemes->avg('physical_progress_pct'), 1);
            $stCompleted    = $stSchemes->filter(fn($s) => $s->physical_status === 'Completed' || (float)$s->physical_progress_pct >= 100)->count();
            $stOngoing      = max(0, $stCount - $stCompleted);
            $stCompletionRatio = $stCount > 0 ? round(($stCompleted / $stCount) * 100, 1) : 0;

            $pendingClaims = PaymentRequest::where('state', $st)
                ->whereIn('status', [
                    PaymentRequest::STATUS_SUBMITTED_TO_BB,
                    PaymentRequest::STATUS_BB_MONITORING,
                    PaymentRequest::STATUS_FORWARDED_TO_MOJS,
                    PaymentRequest::STATUS_NEEDS_CORRECTION,
                ])
                ->count();

            $totalClaims = PaymentRequest::where('state', $st)->count();
            $claimClearanceRate = $totalClaims > 0 ? round((($totalClaims - $pendingClaims) / $totalClaims) * 100, 1) : 100;

            // Composite Performance Index (CPI) Formulation:
            // 40% Physical Progress + 35% Disbursal Velocity + 15% Completion Ratio + 10% Claim Clearance
            $cpiScore = round(
                (0.40 * min(100, $stAvgPhysical)) +
                (0.35 * min(100, $stDisbursalPct)) +
                (0.15 * min(100, $stCompletionRatio)) +
                (0.10 * min(100, $claimClearanceRate)),
                1
            );

            // Tier Classification based on CPI
            if ($cpiScore >= 75) {
                $tier = 'Tier 1 (Leader / Benchmark)';
            } elseif ($cpiScore >= 50) {
                $tier = 'Tier 2 (Progressive / On Track)';
            } else {
                $tier = 'Tier 3 (Critical Attention Required)';
            }

            // Districts and Divisions breakdown
            $divisions = $stSchemes->pluck('division')->filter()->unique()->values()->all();
            $districts = $stSchemes->pluck('district')->filter()->unique()->values()->all();

            $stateScorecard[] = [
                'state_name'           => $st,
                'schemes_count'        => $stCount,
                'sanctioned_cr'        => round($stSanctioned, 2),
                'central_outlay_cr'    => round($stCentralOutlay, 2),
                'state_outlay_cr'      => round($stStateOutlay, 2),
                'released_cr'          => round($stApprovedReqs, 2),
                'disbursal_pct'        => $stDisbursalPct,
                'avg_physical_pct'     => $stAvgPhysical,
                'completed_count'      => $stCompleted,
                'ongoing_count'        => $stOngoing,
                'pending_claims_count' => $pendingClaims,
                'cpi_score'            => $cpiScore,
                'tier'                 => $tier,
                'divisions_count'      => count($divisions),
                'districts_count'      => count($districts),
            ];
        }

        // Sort states by highest Composite Performance Index (CPI) first
        usort($stateScorecard, fn($a, $b) => $b['cpi_score'] <=> $a['cpi_score']);

        // ── 3. River Basin Matrix ────────────────────────────────────────────────
        $basinMatrix = [];
        $allBasins = Scheme::forUser($user)->whereNotNull('river_basin')->distinct()->pluck('river_basin')->filter()->sort();

        foreach ($allBasins as $basin) {
            $bSchemes = $schemes->where('river_basin', $basin);
            if ($bSchemes->isEmpty()) continue;

            $bSanctioned = (float) $bSchemes->sum('sanctioned_amount_cr');
            $bCompleted  = $bSchemes->filter(fn($s) => $s->physical_status === 'Completed' || (float)$s->physical_progress_pct >= 100)->count();

            $basinMatrix[] = [
                'basin_name'        => $basin,
                'schemes_count'     => $bSchemes->count(),
                'completed_count'   => $bCompleted,
                'sanctioned_cr'     => round($bSanctioned, 2),
                'avg_physical_pct'  => round((float) $bSchemes->avg('physical_progress_pct'), 1),
            ];
        }
        usort($basinMatrix, fn($a, $b) => $b['sanctioned_cr'] <=> $a['sanctioned_cr']);

        // ── 4. Physical Progress Donut Distribution ──────────────────────────────
        $p100 = $schemes->filter(fn($s) => (float)$s->physical_progress_pct >= 100)->count();
        $p75  = $schemes->filter(fn($s) => (float)$s->physical_progress_pct >= 75 && (float)$s->physical_progress_pct < 100)->count();
        $p50  = $schemes->filter(fn($s) => (float)$s->physical_progress_pct >= 50 && (float)$s->physical_progress_pct < 75)->count();
        $p25  = $schemes->filter(fn($s) => (float)$s->physical_progress_pct >= 25 && (float)$s->physical_progress_pct < 50)->count();
        $p0   = $schemes->filter(fn($s) => (float)$s->physical_progress_pct < 25)->count();

        $progressDistribution = [
            'completed_100'        => ['label' => '100% Completed', 'count' => $p100, 'pct' => $totalSchemes > 0 ? round(($p100 / $totalSchemes) * 100, 1) : 0, 'color' => '#10B981'],
            'near_completion_75_99'=> ['label' => '75% - 99% Near Completion', 'count' => $p75, 'pct' => $totalSchemes > 0 ? round(($p75 / $totalSchemes) * 100, 1) : 0, 'color' => '#0F4C9F'],
            'advance_50_74'        => ['label' => '50% - 74% Advance Stage', 'count' => $p50, 'pct' => $totalSchemes > 0 ? round(($p50 / $totalSchemes) * 100, 1) : 0, 'color' => '#3B82F6'],
            'intermediate_25_49'   => ['label' => '25% - 49% Intermediate Stage', 'count' => $p25, 'pct' => $totalSchemes > 0 ? round(($p25 / $totalSchemes) * 100, 1) : 0, 'color' => '#F59E0B'],
            'initial_under_25'     => ['label' => '< 25% Initial / Lagging Stage', 'count' => $p0, 'pct' => $totalSchemes > 0 ? round(($p0 / $totalSchemes) * 100, 1) : 0, 'color' => '#EF4444'],
        ];

        // ── 5. Work Category / Component Classification ──────────────────────────
        $categories = [
            'Embankments & Dykes' => ['keywords' => ['embankment', 'dyke', 'bund', 'raising', 'strengthening', 'r/s'], 'count' => 0, 'outlay' => 0.0, 'progress' => []],
            'Anti-Erosion & Bank Protection' => ['keywords' => ['erosion', 'protection', 'spur', 'revetment', 'porcupine', 'boulder', 'geo-bag'], 'count' => 0, 'outlay' => 0.0, 'progress' => []],
            'Drainage Congestion & Outfall' => ['keywords' => ['drainage', 'congestion', 'channel', 'malugram', 'silchar', 'desiltation'], 'count' => 0, 'outlay' => 0.0, 'progress' => []],
            'Sluices & Water Control' => ['keywords' => ['sluice', 'culvert', 'regulator', 'gate'], 'count' => 0, 'outlay' => 0.0, 'progress' => []],
            'Comprehensive Flood Management' => ['keywords' => [], 'count' => 0, 'outlay' => 0.0, 'progress' => []],
        ];

        foreach ($schemes as $s) {
            $name = strtolower($s->scheme_name . ' ' . ($s->project_type ?? ''));
            $matched = false;
            foreach ($categories as $catKey => &$catData) {
                if ($catKey === 'Comprehensive Flood Management') continue;
                foreach ($catData['keywords'] as $kw) {
                    if (str_contains($name, $kw)) {
                        $catData['count']++;
                        $catData['outlay'] += (float)$s->sanctioned_amount_cr;
                        $catData['progress'][] = (float)$s->physical_progress_pct;
                        $matched = true;
                        break 2;
                    }
                }
            }
            if (!$matched) {
                $categories['Comprehensive Flood Management']['count']++;
                $categories['Comprehensive Flood Management']['outlay'] += (float)$s->sanctioned_amount_cr;
                $categories['Comprehensive Flood Management']['progress'][] = (float)$s->physical_progress_pct;
            }
        }

        $categoryMatrix = [];
        foreach ($categories as $k => $c) {
            if ($c['count'] > 0) {
                $avgProg = count($c['progress']) > 0 ? round(array_sum($c['progress']) / count($c['progress']), 1) : 0;
                $categoryMatrix[] = [
                    'category'         => $k,
                    'schemes_count'    => $c['count'],
                    'sanctioned_cr'    => round($c['outlay'], 2),
                    'avg_physical_pct' => $avgProg,
                ];
            }
        }
        usort($categoryMatrix, fn($a, $b) => $b['sanctioned_cr'] <=> $a['sanctioned_cr']);

        // ── 6. Physical-Financial Discrepancy Radar (Audit Early Warning) ────────
        $discrepancies = [];
        foreach ($schemes as $s) {
            $sanctioned = (float)$s->sanctioned_amount_cr;
            if ($sanctioned <= 0) continue;

            $centralPct = (float)($s->central_share_pct ?: 90);
            $centralEntitlement = $sanctioned * ($centralPct / 100);

            $released = (float)$s->paymentRequests->where('status', PaymentRequest::STATUS_APPROVED)->sum(function($r) {
                return $r->approved_amount_cr !== null ? (float)$r->approved_amount_cr : (float)$r->requested_amount_cr;
            });
            if ($released <= 0 && $s->fmbapProject) {
                $released = (float)$s->fmbapProject->released_central_share_cr;
            }

            $disbursalPct = $centralEntitlement > 0 ? round(($released / $centralEntitlement) * 100, 1) : 0;
            $physicalPct  = (float)$s->physical_progress_pct;
            $variance     = round($disbursalPct - $physicalPct, 1);

            // Flag conditions:
            // 1. High Disbursal (>50%) but Low Physical (<35%) => Delta >= 25%
            // 2. Physical stagnant under 20% despite fund releases
            $isHighRisk = ($variance >= 25 && $disbursalPct >= 50);
            $isModerateRisk = ($variance >= 15 && $disbursalPct >= 40) || ($physicalPct < 25 && $released > 0);

            if ($isHighRisk || $isModerateRisk) {
                $discrepancies[] = [
                    'scheme_code'          => $s->scheme_code,
                    'scheme_name'          => $s->scheme_name,
                    'state'                => $s->state,
                    'district'             => $s->district ?: $s->division ?: 'N/A',
                    'sanctioned_amount_cr' => round($sanctioned, 2),
                    'released_cr'          => round($released, 2),
                    'disbursal_pct'        => $disbursalPct,
                    'physical_progress_pct'=> $physicalPct,
                    'variance_delta'       => $variance,
                    'risk_level'           => $isHighRisk ? 'HIGH AUDIT RISK' : 'MODERATE WATCH',
                    'observation'          => $isHighRisk
                        ? "Disbursal velocity ({$disbursalPct}%) significantly precedes physical ground realization ({$physicalPct}%)."
                        : "Progress lag detected: Disbursal {$disbursalPct}% vs Physical {$physicalPct}%.",
                ];
            }
        }
        usort($discrepancies, fn($a, $b) => $b['variance_delta'] <=> $a['variance_delta']);
        $discrepancies = array_slice($discrepancies, 0, 12);

        // ── 7. Bottleneck & Workflow Turnaround Time (TAT) ───────────────────────
        $tatQuery = PaymentRequest::forUser($user);
        if ($selectedState !== 'ALL') {
            $tatQuery->where('state', $selectedState);
        }
        $submittedToBbCount   = (clone $tatQuery)->where('status', PaymentRequest::STATUS_SUBMITTED_TO_BB)->count();
        $bbMonitoringCount    = (clone $tatQuery)->where('status', PaymentRequest::STATUS_BB_MONITORING)->count();
        $forwardedToMojsCount = (clone $tatQuery)->where('status', PaymentRequest::STATUS_FORWARDED_TO_MOJS)->count();
        $needsCorrectionCount = (clone $tatQuery)->where('status', PaymentRequest::STATUS_NEEDS_CORRECTION)->count();
        $approvedCount        = (clone $tatQuery)->where('status', PaymentRequest::STATUS_APPROVED)->count();
        $rejectedCount        = (clone $tatQuery)->where('status', PaymentRequest::STATUS_REJECTED)->count();

        // Calculate average turnaround days
        $approvedReqsWithDates = (clone $tatQuery)->whereNotNull('submitted_at')->whereNotNull('approved_at')->get();
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
            } catch (\Throwable $e) {}
        }
        $avgTurnaroundDays = $validDateCount > 0 ? round($totalDays / $validDateCount, 1) : 14;

        $overdueClaimsCount = (clone $tatQuery)->whereIn('status', [
            PaymentRequest::STATUS_SUBMITTED_TO_BB,
            PaymentRequest::STATUS_BB_MONITORING,
            PaymentRequest::STATUS_FORWARDED_TO_MOJS,
        ])->where('submitted_at', '<', now()->subDays(30))->count();

        $bottlenecks = [
            'submitted_to_bb'      => $submittedToBbCount,
            'bb_monitoring'        => $bbMonitoringCount,
            'forwarded_to_mojs'    => $forwardedToMojsCount,
            'needs_correction'     => $needsCorrectionCount,
            'approved'             => $approvedCount,
            'rejected'             => $rejectedCount,
            'avg_turnaround_days'  => $avgTurnaroundDays,
            'sla_target_days'      => 30,
            'stalled_claims_count' => $needsCorrectionCount + $submittedToBbCount,
            'overdue_claims_count' => $overdueClaimsCount,
        ];

        // ── 8. Parliamentary Register Table ──────────────────────────────────────
        $parliamentaryList = $schemes->map(function ($s) {
            $sanctioned = (float)$s->sanctioned_amount_cr;
            $centralPct = (float)($s->central_share_pct ?: 90);
            $centralEntitlement = $sanctioned * ($centralPct / 100);

            $released = (float)$s->paymentRequests->where('status', PaymentRequest::STATUS_APPROVED)->sum(function($r) {
                return $r->approved_amount_cr !== null ? (float)$r->approved_amount_cr : (float)$r->requested_amount_cr;
            });
            if ($released <= 0 && $s->fmbapProject) {
                $released = (float)$s->fmbapProject->released_central_share_cr;
            }

            $balanceCentral = max(0, round($centralEntitlement - $released, 2));

            // Latest payment request
            $latestPr = $s->paymentRequests->sortByDesc('id')->first();
            $latestReport = $s->progressReports->sortByDesc('id')->first();

            return [
                'id'                     => $s->id,
                'scheme_code'            => $s->scheme_code,
                'scheme_name'            => $s->scheme_name,
                'state'                  => $s->state,
                'district'               => $s->district ?: $s->division ?: 'State WRD',
                'division'               => $s->division ?: 'WRD Division',
                'river_basin'            => $s->river_basin ?: 'Brahmaputra',
                'plan_period'            => $s->plan_period ?: '15th Finance Commission',
                'sanctioned_amount_cr'   => number_format($sanctioned, 2),
                'central_share_pct'      => (int)$centralPct,
                'state_share_pct'        => (int)(100 - $centralPct),
                'central_share_cr'       => number_format($centralEntitlement, 2),
                'released_central_cr'    => number_format($released, 2),
                'balance_central_cr'     => number_format($balanceCentral, 2),
                'physical_progress_pct'  => (float)($s->physical_progress_pct ?: 0),
                'financial_progress_pct' => (float)($latestReport?->financial_progress_pct ?: ($centralEntitlement > 0 ? round(($released / $centralEntitlement) * 100, 1) : 0)),
                'physical_status'        => $s->physical_status ?: 'Ongoing',
                'payment_requests_count' => $s->paymentRequests->count(),
                'latest_sanction_no'     => $latestPr?->sanction_order_no ?: 'Pending Sanction',
                'latest_sanction_date'   => $latestPr?->sanction_order_date?->format('d/m/Y') ?: 'N/A',
                'latest_inspection_date' => $latestReport?->bb_reviewed_at?->format('d/m/Y') ?: 'Routine Verification',
                'active_claim_status'    => $latestPr?->status ?: 'NO_CLAIMS',
            ];
        });

        return [
            'macro'                 => [
                'total_schemes'          => $totalSchemes,
                'completed_schemes'      => $completedCount,
                'ongoing_schemes'        => $ongoingCount,
                'stalled_schemes'        => $stalledCount,
                'total_sanctioned_cr'    => round($totalSanctionedCr, 2),
                'total_central_share_cr' => round($totalCentralShareCr, 2),
                'total_state_share_cr'   => round($totalStateShareCr, 2),
                'total_released_cr'      => round($totalReleasedCr, 2),
                'total_curtailment_cr'   => round($totalCurtailmentCr, 2),
                'unspent_balance_cr'     => $unspentBalanceCr,
                'uc_compliance_pct'      => $ucCompliancePct,
                'disbursal_velocity_pct' => $disbursalVelocity,
                'avg_physical_progress'  => $avgPhysicalProgress,
            ],
            'state_scorecard'       => $stateScorecard,
            'basin_matrix'          => $basinMatrix,
            'progress_distribution' => $progressDistribution,
            'category_matrix'       => $categoryMatrix,
            'discrepancies'         => $discrepancies,
            'bottlenecks'           => $bottlenecks,
            'parliamentary_list'    => $parliamentaryList,
        ];
    }
}
