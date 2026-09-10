<?php

namespace App\Http\Controllers;

use App\Models\ProgressReport;
use App\Models\Scheme;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgressReportController extends Controller
{
    /**
     * List all progress reports
     */
    public function index()
    {
        $user = auth()->user();
        $reports = ProgressReport::with('scheme')->forUser($user)->latest()->get();

        return Inertia::render('ProgressReports/Index', [
            'reports'  => $reports,
            'userRole' => $user->role,
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        return Inertia::render('ProgressReports/Create', [
            'userRole' => auth()->user()->role,
        ]);
    }

    /**
     * Store new progress report
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'scheme_id'                      => 'required|exists:schemes,id',
            'reporting_period'               => 'required|string|max:100',
            'physical_progress_pct'          => 'required|numeric|min:0|max:100',
            'physical_progress_description'  => 'nullable|string',
            'financial_progress_pct'         => 'required|numeric|min:0|max:100',
            'financial_progress_description' => 'nullable|string',
            'narrative_report'               => 'nullable|string',
            'progress_report_pdf'            => 'nullable|file|mimes:pdf|max:204800',
        ]);

        $user = auth()->user();

        $report = new ProgressReport();
        $report->scheme_id = $validated['scheme_id'];
        $report->user_id = $user->id;
        $report->state = $user->state;
        $report->reporting_period = $validated['reporting_period'];
        $report->physical_progress_pct = $validated['physical_progress_pct'];
        $report->physical_progress_description = $validated['physical_progress_description'];
        $report->financial_progress_pct = $validated['financial_progress_pct'];
        $report->financial_progress_description = $validated['financial_progress_description'];
        $report->narrative_report = $validated['narrative_report'];
        $report->status = ProgressReport::STATUS_SUBMITTED;

        if ($request->hasFile('progress_report_pdf')) {
            $report->progress_report_doc_path = '/storage/'.$request->file('progress_report_pdf')->store('progress_reports', 'public');
        }

        $report->save();

        return redirect()->route('progress-reports.index')->with('success', 'Progress report submitted successfully.');
    }

    /**
     * View report
     */
    public function show(ProgressReport $progress_report)
    {
        $progress_report->load(['scheme', 'user', 'auditLogs.user']);

        return Inertia::render('ProgressReports/Show', [
            'report'   => $progress_report,
            'userRole' => auth()->user()->role,
        ]);
    }

    /**
     * Show edit form for correction
     */
    public function edit(ProgressReport $progress_report)
    {
        if (!$progress_report->isEditable()) {
            abort(403, 'This report cannot be edited.');
        }

        if (auth()->user()->isState() && $progress_report->user_id !== auth()->id()) {
            abort(403, 'Unauthorized.');
        }

        return Inertia::render('ProgressReports/Edit', [
            'report'   => $progress_report->load('scheme'),
            'userRole' => auth()->user()->role,
        ]);
    }

    /**
     * Re-submit corrected report
     */
    public function update(Request $request, ProgressReport $progress_report)
    {
        if (!$progress_report->isEditable()) {
            abort(403);
        }

        $validated = $request->validate([
            'reporting_period'               => 'required|string|max:100',
            'physical_progress_pct'          => 'required|numeric|min:0|max:100',
            'physical_progress_description'  => 'nullable|string',
            'financial_progress_pct'         => 'required|numeric|min:0|max:100',
            'financial_progress_description' => 'nullable|string',
            'narrative_report'               => 'nullable|string',
            'progress_report_pdf'            => 'nullable|file|mimes:pdf|max:204800',
        ]);

        $progress_report->reporting_period = $validated['reporting_period'];
        $progress_report->physical_progress_pct = $validated['physical_progress_pct'];
        $progress_report->physical_progress_description = $validated['physical_progress_description'];
        $progress_report->financial_progress_pct = $validated['financial_progress_pct'];
        $progress_report->financial_progress_description = $validated['financial_progress_description'];
        $progress_report->narrative_report = $validated['narrative_report'];
        
        // Reset status
        $progress_report->status = ProgressReport::STATUS_SUBMITTED;

        if ($request->hasFile('progress_report_pdf')) {
            $progress_report->progress_report_doc_path = '/storage/'.$request->file('progress_report_pdf')->store('progress_reports', 'public');
        }

        $progress_report->save();

        return redirect()->route('progress-reports.index')->with('success', 'Progress report re-submitted successfully.');
    }

    /**
     * BB Review Decision
     */
    public function bbReview(Request $request, ProgressReport $progress_report)
    {
        if (!auth()->user()->isBB() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'decision' => 'required|in:REVIEWED_BY_BB,NEEDS_CORRECTION',
            'remarks'  => 'required|string',
        ]);

        $progress_report->status = $validated['decision'];
        $progress_report->bb_remarks = $validated['remarks'];
        $progress_report->bb_reviewed_at = now();
        $progress_report->save();

        return redirect()->back()->with('success', 'BB review recorded.');
    }

    /**
     * MoJS Review Decision
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

        if ($validated['decision'] === 'APPROVED') {
            if ($progress_report->scheme && $progress_report->physical_progress_pct !== null) {
                $progressPct = (float) $progress_report->physical_progress_pct;
                $schemeData = ['physical_progress_pct' => $progressPct];
                if ($progressPct >= 100) {
                    $schemeData['physical_status'] = 'Completed';
                }
                $progress_report->scheme->update($schemeData);
            }
        }

        $progress_report->save();

        return redirect()->back()->with('success', 'MoJS review recorded.');
    }
}
