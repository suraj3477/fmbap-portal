<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use App\Models\ProgressReport;
use App\Models\Scheme;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgressReportController extends Controller
{
    /**
     * List all progress reports with summary KPIs & scheme timeline filtering.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = ProgressReport::with(['scheme', 'user', 'lastPaymentRequest'])
            ->forUser($user);

        if ($request->filled('scheme_id')) {
            $query->where('scheme_id', $request->scheme_id);
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        if ($request->filled('year')) {
            $query->where('reporting_year', $request->year);
        }

        $reports = $query->latest('reporting_year')
            ->latest('reporting_month')
            ->latest('id')
            ->get();

        $allUserReports = ProgressReport::forUser($user)->get();

        $stats = [
            'total_reports'          => $allUserReports->count(),
            'approved_count'         => $allUserReports->whereIn('status', [ProgressReport::STATUS_APPROVED, ProgressReport::STATUS_REVIEWED_BY_MOJS, ProgressReport::STATUS_REVIEWED_BY_BB])->count(),
            'under_review_count'     => $allUserReports->where('status', ProgressReport::STATUS_SUBMITTED)->count(),
            'needs_correction_count' => $allUserReports->where('status', ProgressReport::STATUS_NEEDS_CORRECTION)->count(),
            'avg_physical_progress'  => $allUserReports->count() > 0 ? round($allUserReports->avg('physical_progress_pct'), 1) : 0,
        ];

        $schemesQuery = Scheme::active();
        if ($user->isState()) {
            $schemesQuery->where('state', $user->state);
        }
        $schemes = $schemesQuery->select('id', 'scheme_code', 'scheme_name', 'state', 'physical_progress_pct')
            ->orderBy('scheme_code')
            ->get();

        return Inertia::render('ProgressReports/Index', [
            'reports'  => $reports,
            'stats'    => $stats,
            'schemes'  => $schemes,
            'userRole' => $user->role,
            'filters'  => [
                'scheme_id' => $request->scheme_id,
                'status'    => $request->status ?? 'ALL',
                'year'      => $request->year,
            ],
        ]);
    }

    /**
     * Show create form with available schemes and last reported fund release baseline.
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        if (!$user->isState() && !$user->isSuperAdmin()) {
            abort(403, 'Only State officials or Super Admins can submit monthly progress reports.');
        }

        $schemesQuery = Scheme::active()->with([
            'fmbapProject',
            'paymentRequests' => fn($q) => $q->latest(),
            'progressReports' => fn($q) => $q->latest(),
        ]);

        if ($user->isState()) {
            $schemesQuery->where('state', $user->state);
        }

        $availableSchemes = $schemesQuery->orderByDesc('id')->get();
        $preselectedSchemeId = $request->query('scheme_id');

        return Inertia::render('ProgressReports/Create', [
            'userRole'            => $user->role,
            'availableSchemes'    => $availableSchemes,
            'preselectedSchemeId' => $preselectedSchemeId ? (int) $preselectedSchemeId : null,
        ]);
    }

    /**
     * API: Get baseline data and progress timeline for a specific scheme.
     * Returns the latest fund release claim, previous monthly report, and historical progression curve.
     */
    public function schemeBaseline(Scheme $scheme)
    {
        $user = auth()->user();
        if ($user && $user->isState() && $scheme->state !== $user->state) {
            abort(403, 'Unauthorized.');
        }

        // 1. Last reported Fund Release claim
        $lastFundRelease = PaymentRequest::where('scheme_id', $scheme->id)
            ->latest('id')
            ->first();

        // 2. Last submitted / approved Monthly Progress Report
        $lastMonthlyReport = ProgressReport::where('scheme_id', $scheme->id)
            ->latest('reporting_year')
            ->latest('reporting_month')
            ->latest('id')
            ->first();

        // 3. Chronological progression history (for timeline chart)
        $history = ProgressReport::where('scheme_id', $scheme->id)
            ->orderBy('reporting_year', 'asc')
            ->orderBy('reporting_month', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'reporting_period', 'reporting_month', 'reporting_year', 'physical_progress_pct', 'financial_progress_pct', 'status', 'created_at']);

        return response()->json([
            'scheme' => [
                'id'                    => $scheme->id,
                'scheme_code'           => $scheme->scheme_code,
                'scheme_name'           => $scheme->scheme_name,
                'state'                 => $scheme->state,
                'sanctioned_amount_cr'  => $scheme->sanctioned_amount_cr,
                'physical_progress_pct' => $scheme->physical_progress_pct,
                'physical_status'       => $scheme->physical_status,
            ],
            'last_fund_release' => $lastFundRelease ? [
                'id'                             => $lastFundRelease->id,
                'instalment_number'              => $lastFundRelease->instalment_number,
                'requested_amount_cr'            => $lastFundRelease->requested_amount_cr,
                'approved_amount_cr'             => $lastFundRelease->approved_amount_cr,
                'physical_progress_pct'          => $lastFundRelease->physical_progress_pct,
                'financial_progress_pct'         => $lastFundRelease->financial_progress_pct,
                'physical_progress_description'  => $lastFundRelease->physical_progress_description,
                'financial_progress_description' => $lastFundRelease->financial_progress_description,
                'narrative_progress_report'      => $lastFundRelease->narrative_progress_report,
                'status'                         => $lastFundRelease->status,
                'created_at'                     => $lastFundRelease->created_at->format('d M Y'),
            ] : null,
            'last_monthly_report' => $lastMonthlyReport ? [
                'id'                             => $lastMonthlyReport->id,
                'reporting_period'               => $lastMonthlyReport->reporting_period,
                'reporting_month'                => $lastMonthlyReport->reporting_month,
                'reporting_year'                 => $lastMonthlyReport->reporting_year,
                'physical_progress_pct'          => $lastMonthlyReport->physical_progress_pct,
                'financial_progress_pct'         => $lastMonthlyReport->financial_progress_pct,
                'physical_progress_description'  => $lastMonthlyReport->physical_progress_description,
                'financial_progress_description' => $lastMonthlyReport->financial_progress_description,
                'physical_breakup'               => $lastMonthlyReport->physical_breakup,
                'geo_tagged_files'               => $lastMonthlyReport->geo_tagged_files,
                'video_url'                      => $lastMonthlyReport->video_url,
                'status'                         => $lastMonthlyReport->status,
                'created_at'                     => $lastMonthlyReport->created_at->format('d M Y'),
            ] : null,
            'history' => $history,
        ]);
    }

    /**
     * Store new monthly progress report with break-up and media.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'scheme_id'                      => 'required|exists:schemes,id',
            'reporting_month'                => 'required|integer|min:1|max:12',
            'reporting_year'                 => 'required|integer|min:2020|max:2035',
            'reporting_period'               => 'nullable|string|max:100',
            'physical_progress_pct'          => 'required|numeric|min:0|max:100',
            'physical_progress_description'  => 'nullable|string',
            'physical_breakup'               => 'nullable',
            'financial_progress_pct'         => 'required|numeric|min:0|max:100',
            'financial_progress_description' => 'nullable|string',
            'narrative_report'               => 'nullable|string',
            'video_url'                      => 'nullable|string|max:500',
            'progress_report_pdf'            => 'nullable|file|mimes:pdf|max:204800',
            'media_files.*'                  => 'nullable|file|mimes:jpg,jpeg,png,mp4,webm|max:20480',
        ]);

        $user = auth()->user();
        $scheme = Scheme::forUser($user)->findOrFail($validated['scheme_id']);

        // Check if report already exists for this scheme and month/year
        $existing = ProgressReport::where('scheme_id', $scheme->id)
            ->where('reporting_month', $validated['reporting_month'])
            ->where('reporting_year', $validated['reporting_year'])
            ->first();

        if ($existing) {
            return back()->withErrors([
                'reporting_month' => "A progress report for {$scheme->scheme_code} for this month ({$validated['reporting_month']}/{$validated['reporting_year']}) already exists.",
            ]);
        }

        // Link latest PaymentRequest (Fund Release claim)
        $latestClaim = PaymentRequest::where('scheme_id', $scheme->id)->latest('id')->first();

        $monthName = date('F', mktime(0, 0, 0, (int) $validated['reporting_month'], 10));
        $periodString = $validated['reporting_period'] ?: "{$monthName} {$validated['reporting_year']}";

        // Parse physical break-up
        $physicalBreakup = null;
        if (!empty($validated['physical_breakup'])) {
            $physicalBreakup = is_string($validated['physical_breakup'])
                ? json_decode($validated['physical_breakup'], true)
                : $validated['physical_breakup'];
        }

        $report = new ProgressReport();
        $report->scheme_id                      = $scheme->id;
        $report->user_id                        = $user->id;
        $report->state                          = $scheme->state ?? $user->state;
        $report->reporting_period               = $periodString;
        $report->reporting_month                = (int) $validated['reporting_month'];
        $report->reporting_year                 = (int) $validated['reporting_year'];
        $report->physical_progress_pct          = $validated['physical_progress_pct'];
        $report->physical_progress_description  = $validated['physical_progress_description'] ?? null;
        $report->physical_breakup               = $physicalBreakup;
        $report->financial_progress_pct         = $validated['financial_progress_pct'];
        $report->financial_progress_description = $validated['financial_progress_description'] ?? null;
        $report->narrative_report               = $validated['narrative_report'] ?? null;
        $report->video_url                      = $validated['video_url'] ?? null;
        $report->last_payment_request_id        = $latestClaim?->id;
        $report->status                         = ProgressReport::STATUS_SUBMITTED;

        if ($request->hasFile('progress_report_pdf')) {
            $report->progress_report_doc_path = '/storage/' . $request->file('progress_report_pdf')->store('progress_reports', 'public');
        }

        // Process uploaded geo-tagged photos and video files
        $mediaList = [];
        if ($request->hasFile('media_files')) {
            $captions = $request->input('media_captions', []);
            $latitudes = $request->input('media_latitudes', []);
            $longitudes = $request->input('media_longitudes', []);

            foreach ($request->file('media_files') as $idx => $file) {
                if ($file->isValid()) {
                    $mime = $file->getMimeType();
                    $type = str_starts_with($mime, 'video') ? 'video' : 'photo';
                    $path = '/storage/' . $file->store('progress_media', 'public');

                    $mediaList[] = [
                        'path'         => $path,
                        'type'         => $type,
                        'name'         => $file->getClientOriginalName(),
                        'caption'      => $captions[$idx] ?? 'Site work progress',
                        'lat'          => $latitudes[$idx] ?? null,
                        'lng'          => $longitudes[$idx] ?? null,
                        'capture_date' => now()->toDateString(),
                    ];
                }
            }
        }
        $report->geo_tagged_files = $mediaList;

        $report->save();

        // Update Scheme current physical progress indicator
        $progressPct = (float) $validated['physical_progress_pct'];
        $schemeData = ['physical_progress_pct' => $progressPct];
        if ($progressPct >= 100) {
            $schemeData['physical_status'] = 'Completed';
        }
        $scheme->update($schemeData);

        return redirect()->route('progress-reports.index')->with('success', "Monthly Progress Report for {$scheme->scheme_code} ({$periodString}) submitted successfully.");
    }

    /**
     * View full report details, component breakdown, photo gallery & progression curve.
     */
    public function show(ProgressReport $progress_report)
    {
        $user = auth()->user();
        if ($user && $user->isState() && $progress_report->scheme && $progress_report->scheme->state !== $user->state && $progress_report->user_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $progress_report->load(['scheme.fmbapProject', 'user', 'lastPaymentRequest', 'auditLogs.user']);

        // Historical progression reports for the same scheme
        $timeline = ProgressReport::where('scheme_id', $progress_report->scheme_id)
            ->orderBy('reporting_year', 'asc')
            ->orderBy('reporting_month', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'reporting_period', 'reporting_month', 'reporting_year', 'physical_progress_pct', 'financial_progress_pct', 'status', 'created_at']);

        return Inertia::render('ProgressReports/Show', [
            'report'   => $progress_report,
            'timeline' => $timeline,
            'userRole' => auth()->user()->role,
        ]);
    }

    /**
     * Show edit form for correction.
     */
    public function edit(ProgressReport $progress_report)
    {
        if (!$progress_report->isEditable() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'This report cannot be edited currently.');
        }

        if (auth()->user()->isState() && $progress_report->user_id !== auth()->id()) {
            abort(403, 'Unauthorized.');
        }

        $progress_report->load(['scheme', 'lastPaymentRequest']);

        return Inertia::render('ProgressReports/Edit', [
            'report'   => $progress_report,
            'userRole' => auth()->user()->role,
        ]);
    }

    /**
     * Re-submit corrected report.
     */
    public function update(Request $request, ProgressReport $progress_report)
    {
        if (!$progress_report->isEditable() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized edit action.');
        }

        $validated = $request->validate([
            'reporting_month'                => 'required|integer|min:1|max:12',
            'reporting_year'                 => 'required|integer|min:2020|max:2035',
            'reporting_period'               => 'nullable|string|max:100',
            'physical_progress_pct'          => 'required|numeric|min:0|max:100',
            'physical_progress_description'  => 'nullable|string',
            'physical_breakup'               => 'nullable',
            'financial_progress_pct'         => 'required|numeric|min:0|max:100',
            'financial_progress_description' => 'nullable|string',
            'narrative_report'               => 'nullable|string',
            'video_url'                      => 'nullable|string|max:500',
            'progress_report_pdf'            => 'nullable|file|mimes:pdf|max:204800',
            'media_files.*'                  => 'nullable|file|mimes:jpg,jpeg,png,mp4,webm|max:20480',
        ]);

        $monthName = date('F', mktime(0, 0, 0, (int) $validated['reporting_month'], 10));
        $periodString = $validated['reporting_period'] ?: "{$monthName} {$validated['reporting_year']}";

        $physicalBreakup = null;
        if (!empty($validated['physical_breakup'])) {
            $physicalBreakup = is_string($validated['physical_breakup'])
                ? json_decode($validated['physical_breakup'], true)
                : $validated['physical_breakup'];
        }

        $progress_report->reporting_period               = $periodString;
        $progress_report->reporting_month                = (int) $validated['reporting_month'];
        $progress_report->reporting_year                 = (int) $validated['reporting_year'];
        $progress_report->physical_progress_pct          = $validated['physical_progress_pct'];
        $progress_report->physical_progress_description  = $validated['physical_progress_description'] ?? null;
        $progress_report->physical_breakup               = $physicalBreakup;
        $progress_report->financial_progress_pct         = $validated['financial_progress_pct'];
        $progress_report->financial_progress_description = $validated['financial_progress_description'] ?? null;
        $progress_report->narrative_report               = $validated['narrative_report'] ?? null;
        $progress_report->video_url                      = $validated['video_url'] ?? null;
        $progress_report->status                         = ProgressReport::STATUS_SUBMITTED;

        if ($request->hasFile('progress_report_pdf')) {
            $progress_report->progress_report_doc_path = '/storage/' . $request->file('progress_report_pdf')->store('progress_reports', 'public');
        }

        // Retain existing media or append new
        $existingMedia = $progress_report->geo_tagged_files ?? [];
        if ($request->hasFile('media_files')) {
            $captions = $request->input('media_captions', []);
            $latitudes = $request->input('media_latitudes', []);
            $longitudes = $request->input('media_longitudes', []);

            foreach ($request->file('media_files') as $idx => $file) {
                if ($file->isValid()) {
                    $mime = $file->getMimeType();
                    $type = str_starts_with($mime, 'video') ? 'video' : 'photo';
                    $path = '/storage/' . $file->store('progress_media', 'public');

                    $existingMedia[] = [
                        'path'         => $path,
                        'type'         => $type,
                        'name'         => $file->getClientOriginalName(),
                        'caption'      => $captions[$idx] ?? 'Site work progress',
                        'lat'          => $latitudes[$idx] ?? null,
                        'lng'          => $longitudes[$idx] ?? null,
                        'capture_date' => now()->toDateString(),
                    ];
                }
            }
        }
        $progress_report->geo_tagged_files = $existingMedia;

        $progress_report->save();

        if ($progress_report->scheme) {
            $progressPct = (float) $validated['physical_progress_pct'];
            $schemeData = ['physical_progress_pct' => $progressPct];
            if ($progressPct >= 100) {
                $schemeData['physical_status'] = 'Completed';
            }
            $progress_report->scheme->update($schemeData);
        }

        return redirect()->route('progress-reports.index')->with('success', 'Progress report re-submitted successfully.');
    }

    /**
     * Delete report (State owner if draft/correction, or Super Admin).
     */
    public function destroy(ProgressReport $progress_report)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && (!$user->isState() || $progress_report->user_id !== $user->id)) {
            abort(403);
        }

        $progress_report->delete();

        return redirect()->route('progress-reports.index')->with('success', 'Progress report deleted successfully.');
    }

    /**
     * Brahmaputra Board Technical Inspection & Submission to MoJS.
     */
    public function bbReview(Request $request, ProgressReport $progress_report)
    {
        if (!auth()->user()->isBB() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'decision'       => 'required|in:REVIEWED_BY_BB,NEEDS_CORRECTION',
            'remarks'        => 'required|string',
            'inspection_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
        ]);

        $progress_report->status = $validated['decision'];
        $progress_report->bb_remarks = $validated['remarks'];
        $progress_report->bb_reviewed_at = now();

        if ($request->hasFile('inspection_doc')) {
            $progress_report->bb_inspection_doc_path = '/storage/' . $request->file('inspection_doc')->store('bb_inspections', 'public');
        }

        if ($validated['decision'] === 'REVIEWED_BY_BB' && $progress_report->scheme) {
            $progressPct = (float) $progress_report->physical_progress_pct;
            $schemeData = ['physical_progress_pct' => $progressPct];
            if ($progressPct >= 100) {
                $schemeData['physical_status'] = 'Completed';
            }
            $progress_report->scheme->update($schemeData);
        }

        $progress_report->save();

        return redirect()->back()->with('success', 'Brahmaputra Board Inspection Report submitted to MoJS successfully.');
    }

    /**
     * Ministry (MoJS) Final Review & Scheme Physical Progress Affirmation.
     */
    public function mojsReview(Request $request, ProgressReport $progress_report)
    {
        if (!auth()->user()->isMoJS() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'decision' => 'required|in:APPROVED,NEEDS_CORRECTION',
            'remarks'  => 'required|string',
        ]);

        $progress_report->status = $validated['decision'];
        $progress_report->mojs_remarks = $validated['remarks'];
        $progress_report->mojs_reviewed_at = now();

        if ($validated['decision'] === 'APPROVED' && $progress_report->scheme) {
            $progressPct = (float) $progress_report->physical_progress_pct;
            $schemeData = ['physical_progress_pct' => $progressPct];
            if ($progressPct >= 100) {
                $schemeData['physical_status'] = 'Completed';
            }
            $progress_report->scheme->update($schemeData);
        }

        $progress_report->save();

        return redirect()->back()->with('success', 'MoJS statutory review decision recorded.');
    }
}
