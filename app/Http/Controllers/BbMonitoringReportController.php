<?php

namespace App\Http\Controllers;

use App\Models\BbMonitoringReport;
use App\Models\PaymentRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BbMonitoringReportController extends Controller
{
    /**
     * BB queue of pending inspections.
     */
    public function index()
    {
        if (!auth()->user()->isBB() && !auth()->user()->isSuperAdmin() && !auth()->user()->isMoJS()) {
            abort(403);
        }

        // Show requests that are submitted to BB or already have a draft/submitted report
        $requests = PaymentRequest::with(['scheme', 'bbMonitoringReport'])
            ->whereIn('status', [
                PaymentRequest::STATUS_SUBMITTED_TO_BB,
                PaymentRequest::STATUS_BB_MONITORING,
                PaymentRequest::STATUS_FORWARDED_TO_MOJS,
                PaymentRequest::STATUS_NEEDS_CORRECTION,
            ])
            ->latest()
            ->get();

        return Inertia::render('MonitoringRequests/Index', [
            'requests' => $requests,
        ]);
    }

    /**
     * Show form to create/edit report
     */
    public function create(Request $request, $id)
    {
        if (!auth()->user()->isBB() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }
        $paymentRequest = PaymentRequest::with(['scheme.fmbapProject', 'user', 'bbMonitoringReport'])->findOrFail($id);

        return Inertia::render('MonitoringRequests/ReportForm', [
            'paymentRequest' => $paymentRequest,
            'report'         => $paymentRequest->bbMonitoringReport,
        ]);
    }

    /**
     * Save draft report (AJAX/progressive save)
     */
    public function store(Request $request, $id)
    {
        if (!auth()->user()->isBB() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }
        $paymentRequest = PaymentRequest::findOrFail($id);
        
        $validated = $request->validate([
            'inspection_date'                  => 'nullable|date',
            'site_description'                 => 'nullable|string',
            'bb_physical_progress_pct'         => 'nullable|numeric|min:0|max:100',
            'bb_physical_progress_description' => 'nullable|string',
            'bb_financial_progress_pct'        => 'nullable|numeric|min:0|max:100',
            'bb_financial_progress_description'=> 'nullable|string',
            'bb_report_doc'                    => 'nullable|file|mimes:pdf|max:204800',
            'geo_files.*.file'                 => 'nullable|file|mimes:jpeg,png,jpg,mp4|max:20480',
            'geo_files.*.lat'                  => 'nullable|string',
            'geo_files.*.lng'                  => 'nullable|string',
            'geo_files.*.caption'              => 'nullable|string',
            'is_final_submit'                  => 'boolean',
            'decision'                         => 'nullable|in:FORWARDED_TO_MOJS,NEEDS_CORRECTION,REJECTED',
            'bb_remarks'                        => 'nullable|string',
        ]);

        $report = BbMonitoringReport::firstOrNew([
            'payment_request_id' => $paymentRequest->id,
        ]);

        if (!$report->exists) {
            $report->bb_user_id = auth()->id();
            $report->status = 'DRAFT';
        }

        $report->fill([
            'inspection_date'                  => $validated['inspection_date'] ?? $report->inspection_date,
            'site_description'                 => $validated['site_description'] ?? $report->site_description,
            'bb_physical_progress_pct'         => $validated['bb_physical_progress_pct'] ?? $report->bb_physical_progress_pct,
            'bb_physical_progress_description' => $validated['bb_physical_progress_description'] ?? $report->bb_physical_progress_description,
            'bb_financial_progress_pct'        => $validated['bb_financial_progress_pct'] ?? $report->bb_financial_progress_pct,
            'bb_financial_progress_description'=> $validated['bb_financial_progress_description'] ?? $report->bb_financial_progress_description,
        ]);

        if ($request->hasFile('bb_report_doc')) {
            $report->bb_report_doc_path = '/storage/'.$request->file('bb_report_doc')->store('bb_reports', 'public');
        }

        // Process geo-tagged files
        if (isset($validated['geo_files']) && is_array($validated['geo_files'])) {
            $existingGeoFiles = $report->geo_tagged_files ?? [];
            foreach ($request->file('geo_files') ?? [] as $index => $fileData) {
                if (isset($fileData['file'])) {
                    $file = $fileData['file'];
                    $mime = $file->getMimeType();
                    $type = str_starts_with($mime, 'video') ? 'video' : 'photo';
                    $path = '/storage/'.$file->store('bb_evidence', 'public');
                    
                    $existingGeoFiles[] = [
                        'path'    => $path,
                        'type'    => $type,
                        'lat'     => $validated['geo_files'][$index]['lat'] ?? null,
                        'lng'     => $validated['geo_files'][$index]['lng'] ?? null,
                        'caption' => $validated['geo_files'][$index]['caption'] ?? null,
                    ];
                }
            }
            $report->geo_tagged_files = $existingGeoFiles;
        }

        if ($validated['is_final_submit'] ?? false) {
            $decision = $validated['decision'] ?? 'FORWARDED_TO_MOJS';
            $report->status = 'SUBMITTED';
            $report->submitted_at = now();
            
            $paymentRequest->status = $decision;
            $paymentRequest->bb_decision = $decision;
            $paymentRequest->bb_remarks = $validated['bb_remarks'] 
                ?? $report->bb_physical_progress_description 
                ?: ($decision === 'FORWARDED_TO_MOJS' 
                    ? 'BB Monitoring Report completed and request forwarded to MoJS.' 
                    : ($decision === 'NEEDS_CORRECTION' ? 'BB sent request back to State for correction.' : 'BB rejected request.'));

            if ($decision === 'FORWARDED_TO_MOJS') {
                $paymentRequest->forwarded_to_mojs_at = now();
            }
            $paymentRequest->save();
        }

        $report->save();

        return response()->json([
            'message' => 'Saved successfully',
            'report_id' => $report->id,
            'status' => $report->status,
        ]);
    }

    public function edit(Request $request, $id)
    {
        return $this->create($request, $id);
    }

    public function update(Request $request, $id)
    {
        return $this->store($request, $id);
    }

    public function submit(Request $request, $id)
    {
        $request->merge(['is_final_submit' => true]);
        return $this->store($request, $id);
    }
}
