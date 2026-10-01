<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use App\Models\Scheme;
use App\Services\DossierService;
use App\Services\Gfr12aService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentRequestController extends Controller
{
    /**
     * Dashboard List
     */
    public function index()
    {
        $user = auth()->user();
        $requests = PaymentRequest::with('scheme')->forUser($user)->latest()->get();

        return Inertia::render('FundRelease/Index', [
            'requests' => $requests,
            'userRole' => $user->role,
        ]);
    }

    /**
     * Launch 4-step wizard
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        if (!$user->isState() && !$user->isSuperAdmin()) {
            abort(403, 'Only State officials or Super Admins can initiate fund release claims.');
        }

        $availableSchemes = Scheme::active()
            ->with('fmbapProject')
            ->orderByDesc('id')
            ->take(50)
            ->get();

        $preselectedSchemeId = $request->query('scheme_id');

        return Inertia::render('FundRelease/Create', [
            'userRole'            => auth()->user()->role,
            'availableSchemes'    => $availableSchemes,
            'preselectedSchemeId' => $preselectedSchemeId ? (int)$preselectedSchemeId : null,
        ]);
    }

    /**
     * Save step data (used progressively for each step of wizard)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'step'                           => 'required|integer|min:1|max:4',
            'payment_request_id'             => 'nullable|exists:payment_requests,id',
            'is_final_submit'                => 'boolean',
            // Step 1
            'scheme_id'                      => 'nullable|exists:schemes,id',
            // Step 2
            'requested_amount_cr'            => 'nullable|numeric|min:0',
            'instalment_number'              => 'nullable|integer',
            'bank_details'                   => 'nullable|string',
            'state_remarks'                  => 'nullable|string',
            'estimated_cost_cr'              => 'nullable|numeric|min:0',
            'executed_amount_cr'             => 'nullable|numeric|min:0',
            'funding_pattern'                => 'nullable|string',
            'central_share_cr'               => 'nullable|numeric|min:0',
            'state_share_cr'                 => 'nullable|numeric|min:0',
            'released_central_share_cr'      => 'nullable|numeric|min:0',
            'released_state_share_cr'        => 'nullable|numeric|min:0',
            'balance_central_share_cr'       => 'nullable|numeric|min:0',
            'balance_state_share_cr'         => 'nullable|numeric|min:0',
            'state_govt_doc'                 => 'nullable|file|mimes:pdf|max:204800',
            // Step 3
            'physical_progress_pct'          => 'nullable|numeric|min:0|max:100',
            'physical_progress_description'  => 'nullable|string',
            'financial_progress_pct'         => 'nullable|numeric|min:0|max:100',
            'financial_progress_description' => 'nullable|string',
            'narrative_progress_report'      => 'nullable|string',
            // Step 4
            'utilization_certificate'        => 'nullable|file|mimes:pdf|max:204800',
            'vouchers.*'                     => 'nullable|file|mimes:pdf|max:204800',
            'progress_report_pdf'            => 'nullable|file|mimes:pdf|max:204800',
        ]);

        $user = auth()->user();
        if (!$user->isState() && !$user->isSuperAdmin()) {
            abort(403, 'Only State officials or Super Admins can initiate fund release claims.');
        }

        // Find or create Draft
        if ($validated['payment_request_id'] ?? false) {
            $query = PaymentRequest::where('id', $validated['payment_request_id']);
            if ($user->isState()) {
                $query->where('state', $user->state);
            }
            $paymentRequest = $query->firstOrFail();
        } else {
            // Require scheme for new request
            $request->validate(['scheme_id' => 'required|exists:schemes,id']);
            
            // Check if an existing blank DRAFT already exists for this user and scheme before creating a new one
            $existingDraft = PaymentRequest::where('user_id', $user->id)
                ->where('scheme_id', $validated['scheme_id'])
                ->where('status', PaymentRequest::STATUS_DRAFT)
                ->where(function ($q) {
                    $q->whereNull('requested_amount_cr')->orWhere('requested_amount_cr', 0);
                })
                ->latest()
                ->first();

            if ($existingDraft) {
                $paymentRequest = $existingDraft;
            } else {
                $paymentRequest = PaymentRequest::create([
                    'user_id'   => $user->id,
                    'state'     => $user->state,
                    'scheme_id' => $validated['scheme_id'],
                    'status'    => PaymentRequest::STATUS_DRAFT,
                ]);
            }
        }

        // Apply fields based on step
        if ($validated['step'] == 1 && isset($validated['scheme_id'])) {
            $paymentRequest->scheme_id = $validated['scheme_id'];
        }

        if ($validated['step'] == 2) {
            $paymentRequest->requested_amount_cr = $validated['requested_amount_cr'] ?? 0;
            $paymentRequest->instalment_number   = $validated['instalment_number'];
            $paymentRequest->bank_details        = $validated['bank_details'];
            $paymentRequest->state_remarks       = $validated['state_remarks'];

            // Sync baseline project financial metrics with FmbapProject for this scheme
            if ($paymentRequest->scheme) {
                $fmbapProject = \App\Models\FmbapProject::firstOrNew([
                    'scheme_code' => $paymentRequest->scheme->scheme_code,
                ]);

                if (!$fmbapProject->exists) {
                    $fmbapProject->user_id     = $user->id;
                    $fmbapProject->state       = $user->state;
                    $fmbapProject->scheme_name = $paymentRequest->scheme->scheme_name;
                    $fmbapProject->status      = 'SUBMITTED_BY_STATE';
                }

                if (isset($validated['estimated_cost_cr'])) {
                    $fmbapProject->estimated_cost_cr = $validated['estimated_cost_cr'];
                    if ($paymentRequest->scheme) {
                        $paymentRequest->scheme->sanctioned_amount_cr = $validated['estimated_cost_cr'];
                        $paymentRequest->scheme->estimated_cost_lakh = round($validated['estimated_cost_cr'] * 100, 2);
                    }
                }
                if (isset($validated['executed_amount_cr'])) $fmbapProject->executed_amount_cr = $validated['executed_amount_cr'];
                if (isset($validated['funding_pattern'])) {
                    $fmbapProject->funding_pattern = $validated['funding_pattern'];
                    $parts = explode('/', $validated['funding_pattern']);
                    if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1]) && $paymentRequest->scheme) {
                        $paymentRequest->scheme->central_share_pct = (int) $parts[0];
                        $paymentRequest->scheme->state_share_pct = (int) $parts[1];
                    }
                }
                if (isset($validated['central_share_cr'])) $fmbapProject->central_share_cr = $validated['central_share_cr'];
                if (isset($validated['state_share_cr'])) $fmbapProject->state_share_cr = $validated['state_share_cr'];
                if (isset($validated['released_central_share_cr'])) $fmbapProject->released_central_share_cr = $validated['released_central_share_cr'];
                if (isset($validated['released_state_share_cr'])) $fmbapProject->released_state_share_cr = $validated['released_state_share_cr'];
                if (isset($validated['balance_central_share_cr'])) $fmbapProject->balance_central_share_cr = $validated['balance_central_share_cr'];
                if (isset($validated['balance_state_share_cr'])) $fmbapProject->balance_state_share_cr = $validated['balance_state_share_cr'];
                if (isset($validated['state_remarks'])) $fmbapProject->remarks = $validated['state_remarks'];

                if ($request->hasFile('state_govt_doc')) {
                    $fmbapProject->state_govt_doc_path = '/storage/'.$request->file('state_govt_doc')->store('fmbap_docs', 'public');
                    $fmbapProject->state_govt_submission_date = now()->toDateString();
                }

                $fmbapProject->save();

                if ($paymentRequest->scheme) {
                    $paymentRequest->scheme->save();
                }
            }
        }

        if ($validated['step'] == 3) {
            $paymentRequest->physical_progress_pct          = $validated['physical_progress_pct'] ?? 0;
            $paymentRequest->physical_progress_description  = $validated['physical_progress_description'];
            $paymentRequest->financial_progress_pct         = $validated['financial_progress_pct'] ?? 0;
            $paymentRequest->financial_progress_description = $validated['financial_progress_description'];
            $paymentRequest->narrative_progress_report      = $validated['narrative_progress_report'];
        }

        if ($validated['step'] == 4) {
            if ($request->hasFile('utilization_certificate')) {
                $paymentRequest->utilization_certificate_path = '/storage/'.$request->file('utilization_certificate')->store('fund_release/uc', 'public');
            }
            if ($request->hasFile('vouchers')) {
                $vPaths = $paymentRequest->voucher_doc_paths ?? [];
                foreach ($request->file('vouchers') as $v) {
                    $vPaths[] = '/storage/'.$v->store('fund_release/vouchers', 'public');
                }
                $paymentRequest->voucher_doc_paths = $vPaths;
            }
            if ($request->hasFile('progress_report_pdf')) {
                $paymentRequest->progress_report_doc_path = '/storage/'.$request->file('progress_report_pdf')->store('fund_release/progress', 'public');
            }

            if ($validated['is_final_submit'] ?? false) {
                // Ensure UC is present on final submit
                if (!$paymentRequest->utilization_certificate_path && !$request->hasFile('utilization_certificate')) {
                    return response()->json(['message' => 'Utilization Certificate is required for final submission.'], 422);
                }
                $paymentRequest->status = PaymentRequest::STATUS_SUBMITTED_TO_BB;
                $paymentRequest->submitted_at = now();

                // If a BB monitoring report already exists, reset its status to DRAFT so BB can re-verify and edit it
                if ($paymentRequest->bbMonitoringReport) {
                    $paymentRequest->bbMonitoringReport->update(['status' => 'DRAFT']);
                }
            }
        }

        $paymentRequest->save();

        if ($validated['is_final_submit'] ?? false) {
            $versionCount = $paymentRequest->revisions()->count() + 1;
            $paymentRequest->createRevisionSnapshot("Submission v{$versionCount}.0");
        }

        return response()->json([
            'message' => 'Saved successfully',
            'payment_request_id' => $paymentRequest->id,
            'status' => $paymentRequest->status,
        ]);
    }

    /**
     * Show full consolidated dossier view
     */
    public function show(PaymentRequest $fund_release, DossierService $dossierService)
    {
        $fund_release->load(['revisions.user', 'scheme.fmbapProject']);
        $dossier = $dossierService->build($fund_release);
        $steps = $dossierService->getWorkflowSteps($fund_release->status);

        return Inertia::render('FundRelease/Show', [
            'dossier'   => $dossier,
            'steps'     => $steps,
            'revisions' => $fund_release->revisions,
            'userRole'  => auth()->user()->role,
        ]);
    }

    /**
     * Re-open wizard for correction
     */
    public function edit(PaymentRequest $fund_release)
    {
        if (!$fund_release->isEditable()) {
            abort(403, 'This request cannot be edited.');
        }

        if (auth()->user()->isState() && $fund_release->state !== auth()->user()->state && $fund_release->user_id !== auth()->id()) {
            abort(403, 'Unauthorized.');
        }

        return Inertia::render('FundRelease/Edit', [
            'paymentRequest' => $fund_release->load(['scheme.fmbapProject', 'revisions']),
            'userRole'       => auth()->user()->role,
        ]);
    }

    /**
     * Save corrected step data (similar to store, just updates)
     */
    public function update(Request $request, PaymentRequest $fund_release)
    {
        $request->merge(['payment_request_id' => $fund_release->id]);
        return $this->store($request);
    }

    /**
     * BB Decision (Approve/Reject/Send back/Forward)
     */
    public function bbDecision(Request $request, PaymentRequest $fund_release)
    {
        if (!auth()->user()->isBB() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'decision' => 'required|in:FORWARDED_TO_MOJS,NEEDS_CORRECTION,REJECTED',
            'remarks'  => 'required|string',
        ]);

        $fund_release->bb_decision = $validated['decision'];
        $fund_release->bb_remarks  = $validated['remarks'];
        $fund_release->status      = $validated['decision'];

        if ($validated['decision'] === 'FORWARDED_TO_MOJS') {
            $fund_release->forwarded_to_mojs_at = now();
        }

        $fund_release->save();

        $actionLabel = $validated['decision'] === 'NEEDS_CORRECTION' ? 'BB Sent Back for Correction' : 'BB Decision: ' . str_replace('_', ' ', $validated['decision']);
        $fund_release->createRevisionSnapshot($actionLabel);

        return redirect()->back()->with('success', 'BB decision recorded.');
    }

    /**
     * MoJS Final Decision
     */
    public function mojsDecision(Request $request, PaymentRequest $fund_release)
    {
        if (!auth()->user()->isMoJS() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'decision' => 'required|in:APPROVED,NEEDS_CORRECTION,REJECTED',
            'remarks'  => 'required|string',
        ]);

        $fund_release->mojs_decision = $validated['decision'];
        $fund_release->mojs_remarks  = $validated['remarks'];
        $fund_release->status        = $validated['decision'];

        if ($validated['decision'] === 'APPROVED') {
            $fund_release->approved_at = now();

            // Automatically sync the scheme physical status & progress if claim is approved
            if ($fund_release->scheme && $fund_release->physical_progress_pct !== null) {
                $progressPct = (float) $fund_release->physical_progress_pct;
                $schemeData = ['physical_progress_pct' => $progressPct];
                if ($progressPct >= 100) {
                    $schemeData['physical_status'] = 'Completed';
                }
                $fund_release->scheme->update($schemeData);
            }
        }

        $fund_release->save();

        $actionLabel = $validated['decision'] === 'NEEDS_CORRECTION' ? 'MoJS Sent Back for Correction' : 'MoJS Decision: ' . str_replace('_', ' ', $validated['decision']);
        $fund_release->createRevisionSnapshot($actionLabel);

        return redirect()->back()->with('success', 'MoJS decision recorded.');
    }

    /**
     * Download Consolidated Official Dossier PDF
     */
    public function downloadDossier(PaymentRequest $fund_release, DossierService $dossierService)
    {
        $dossier = $dossierService->build($fund_release);
        $steps = $dossierService->getWorkflowSteps($fund_release->status);

        $pdf = Pdf::loadView('pdf.dossier', [
            'dossier'        => $dossier,
            'steps'          => $steps,
            'paymentRequest' => $fund_release,
            'generated_at'   => now()->format('d M Y, H:i \I\S\T'),
        ])->setPaper('a4', 'portrait')
          ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        $code = $fund_release->scheme?->scheme_code ?: ('PR-' . $fund_release->id);
        return $pdf->download("FMBAP_Dossier_{$code}_Instalment_{$fund_release->instalment_number}.pdf");
    }

    /**
     * Fetch pre-filled GFR-12A data for a scheme / payment request.
     */
    public function gfr12aData(Request $request, Gfr12aService $gfrService)
    {
        $request->validate(['scheme_id' => 'required|exists:schemes,id']);
        $scheme = Scheme::findOrFail($request->scheme_id);
        $paymentRequest = $request->payment_request_id ? PaymentRequest::find($request->payment_request_id) : null;

        $data = $gfrService->getPreFillData($scheme, $paymentRequest);
        return response()->json($data);
    }

    /**
     * Generate GFR-12A PDF (download or attach directly to claim).
     */
    public function generateGfr12a(Request $request, Gfr12aService $gfrService)
    {
        $validated = $request->validate([
            'scheme_id'              => 'required|exists:schemes,id',
            'payment_request_id'     => 'nullable|exists:payment_requests,id',
            'financial_year'         => 'required|string',
            'instalment_number'      => 'required|integer|min:1',
            'sanction_letter_no'     => 'required|string',
            'sanction_date'          => 'required|string',
            'central_amount_cr'      => 'required|numeric|min:0',
            'state_amount_cr'        => 'required|numeric|min:0',
            'total_amount_cr'        => 'required|numeric|min:0',
            'utilized_amount_cr'     => 'required|numeric|min:0',
            'unspent_balance_cr'     => 'required|numeric|min:0',
            'interest_accrued_cr'    => 'nullable|numeric|min:0',
            'physical_progress_pct'  => 'required|numeric|min:0|max:100',
            'financial_progress_pct' => 'required|numeric|min:0|max:100',
            'officer_name'           => 'nullable|string',
            'officer_designation'    => 'nullable|string',
            'action'                 => 'required|in:download,attach',
        ]);

        $scheme = Scheme::findOrFail($validated['scheme_id']);

        if ($validated['action'] === 'attach' && !empty($validated['payment_request_id'])) {
            $paymentRequest = PaymentRequest::findOrFail($validated['payment_request_id']);
            $filePath = $gfrService->generateAndAttach($validated, $paymentRequest);

            return response()->json([
                'message'   => 'Statutory Form GFR-12A generated and attached to claim successfully.',
                'file_path' => $filePath,
            ]);
        }

        // Direct PDF download
        $pdf = $gfrService->buildPdf($validated, $scheme);
        $filename = 'Form_GFR12A_' . ($scheme->scheme_code ?: 'Scheme') . '_Instalment_' . $validated['instalment_number'] . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Delete a Draft Payment Request
     */
    public function destroy(PaymentRequest $fund_release)
    {
        $user = auth()->user();
        if ($fund_release->status !== PaymentRequest::STATUS_DRAFT && !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Only unsubmitted drafts can be deleted.');
        }

        $fund_release->delete();
        return redirect()->back()->with('success', 'Draft deleted successfully.');
    }
}
