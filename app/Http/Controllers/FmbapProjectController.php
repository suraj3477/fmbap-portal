<?php

namespace App\Http\Controllers;

use App\Models\FmbapProject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FmbapProjectController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = FmbapProject::query();

        if ($user->isState()) {
            $query->where('state', $user->state);
        } elseif ($user->isMoJS() || $user->isBB() || in_array($user->role, ['super_admin', 'admin', 'board_official', 'mojs_official', 'central_admin', 'bbrd_inspector'])) {
            // Can see all approved projects
        } else {
            $query->where('user_id', $user->id);
        }

        $projects = $query->with(['scheme.paymentRequests'])->latest()->get();

        // Fetch counts for new modules
        $moduleCounts = [
            'fund_release' => \App\Models\PaymentRequest::forUser($user)->count(),
            'monitoring_requests' => \App\Models\BbMonitoringReport::count(), // Adjust scope as needed, perhaps only for BB/MoJS
            'progress_reports' => \App\Models\ProgressReport::forUser($user)->count(),
        ];

        return Inertia::render('Fmbap/Dashboard', [
            'projects' => $projects,
            'userRole' => $user->role,
            'moduleCounts' => $moduleCounts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'scheme_code' => 'nullable|string|max:100',
            'scheme_name' => 'nullable|string',
            'project_code' => 'nullable|string|max:100',
            'title' => 'nullable|string',
            'estimated_cost_cr' => 'nullable|numeric|min:0',
            'sanctioned_cost_cr' => 'nullable|numeric|min:0',
            'executed_amount_cr' => 'nullable|numeric|min:0',
            'funding_pattern' => 'nullable|string',
            'central_share_cr' => 'nullable|numeric|min:0',
            'state_share_cr' => 'nullable|numeric|min:0',
            'released_central_share_cr' => 'nullable|numeric|min:0',
            'released_state_share_cr' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
            'state_govt_submission_date' => 'nullable|date',
            'state_govt_doc' => 'nullable|file|mimes:pdf',
            'state_govt_doc_note' => 'nullable|string',
            'brahmaputra_board_submission_date' => 'nullable|date',
            'brahmaputra_board_doc' => 'nullable|file|mimes:pdf',
            'brahmaputra_board_doc_note' => 'nullable|string',
            'mojs_submission_date' => 'nullable|date',
            'mojs_doc' => 'nullable|file|mimes:pdf',
            'mojs_doc_note' => 'nullable|string',
            'district' => 'nullable|string',
            'river_basin' => 'nullable|string',
        ]);

        if (empty($validated['scheme_code']) && !empty($validated['project_code'])) {
            $validated['scheme_code'] = $validated['project_code'];
        }
        if (empty($validated['scheme_name']) && !empty($validated['title'])) {
            $validated['scheme_name'] = $validated['title'];
        }
        if (empty($validated['estimated_cost_cr']) && !empty($validated['sanctioned_cost_cr'])) {
            $validated['estimated_cost_cr'] = $validated['sanctioned_cost_cr'];
        }

        $district = $validated['district'] ?? null;
        $riverBasin = $validated['river_basin'] ?? null;

        unset($validated['project_code'], $validated['title'], $validated['sanctioned_cost_cr'], $validated['district'], $validated['river_basin']);

        // Auto-calculate balances
        $centralShare = $validated['central_share_cr'] ?? 0;
        $stateShare = $validated['state_share_cr'] ?? 0;
        $releasedCentral = $validated['released_central_share_cr'] ?? 0;
        $releasedState = $validated['released_state_share_cr'] ?? 0;

        $validated['balance_central_share_cr'] = round($centralShare - $releasedCentral, 2);
        $validated['balance_state_share_cr'] = round($stateShare - $releasedState, 2);

        // Save PDF uploads
        if ($request->hasFile('state_govt_doc')) {
            $validated['state_govt_doc_path'] = '/storage/'.$request->file('state_govt_doc')->store('proposals/state_govt', 'public');
        }
        unset($validated['state_govt_doc']);

        if ($request->hasFile('brahmaputra_board_doc')) {
            $validated['brahmaputra_board_doc_path'] = '/storage/'.$request->file('brahmaputra_board_doc')->store('proposals/bbrd', 'public');
        }
        unset($validated['brahmaputra_board_doc']);

        if ($request->hasFile('mojs_doc')) {
            $validated['mojs_doc_path'] = '/storage/'.$request->file('mojs_doc')->store('proposals/mojs', 'public');
        }
        unset($validated['mojs_doc']);

        $validated['user_id'] = auth()->id();
        $validated['state'] = $request->state ?? auth()->user()->state ?? 'Assam';
        $validated['status'] = 'SUBMITTED_BY_STATE';

        $project = FmbapProject::create($validated);

        // Also create/update corresponding Scheme so it shows up in Scheme Master Catalogue (Dashboard)
        if (!empty($project->scheme_code)) {
            $scheme = \App\Models\Scheme::where('scheme_code', $project->scheme_code)->first();
            
            $fundingPattern = $project->funding_pattern ?? '90/10';
            $parts = explode('/', $fundingPattern);
            $centralPct = (isset($parts[0]) && is_numeric($parts[0])) ? (int) $parts[0] : 90;
            $statePct = (isset($parts[1]) && is_numeric($parts[1])) ? (int) $parts[1] : 10;

            if (!$scheme) {
                \App\Models\Scheme::create([
                    'scheme_code'          => $project->scheme_code,
                    'scheme_name'          => $project->scheme_name ?? 'Untitled Scheme',
                    'state'                => $project->state ?? 'Assam',
                    'district'             => $district ?? $request->district ?? null,
                    'river_basin'          => $riverBasin ?? $request->river_basin ?? 'Brahmaputra',
                    'sanctioned_amount_cr' => $project->estimated_cost_cr ?? 0,
                    'central_share_pct'    => $centralPct,
                    'state_share_pct'      => $statePct,
                    'project_type'         => $request->project_type ?? 'Flood Management',
                    'physical_status'      => 'Ongoing',
                    'physical_progress_pct' => 0.00,
                    'is_active'            => true,
                ]);
            } else {
                $scheme->update([
                    'scheme_name'          => $project->scheme_name ?? 'Untitled Scheme',
                    'state'                => $project->state ?? 'Assam',
                    'district'             => $district ?? $request->district ?? null,
                    'river_basin'          => $riverBasin ?? $request->river_basin ?? 'Brahmaputra',
                    'sanctioned_amount_cr' => $project->estimated_cost_cr ?? 0,
                    'central_share_pct'    => $centralPct,
                    'state_share_pct'      => $statePct,
                    'project_type'         => $request->project_type ?? 'Flood Management',
                ]);
            }
        }

        return redirect()->back()->with('success', 'FMBAP Proposal submitted successfully.');
    }

    public function update(Request $request, FmbapProject $fmbapProject)
    {
        // Check authorization (super admin, admin, or owner)
        $user = auth()->user();
        
        if ($user->isState()) {
            if ($fmbapProject->state_govt_doc_path) {
                abort(403, 'You have already added the details and PDF for this approved project. It cannot be edited again.');
            }
        } elseif (!$user->isBB() && !$user->isMoJS() && $user->role !== 'super_admin' && $user->role !== 'admin' && !$user->is_admin) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'scheme_code' => 'nullable|string|max:100',
            'scheme_name' => 'nullable|string',
            'project_code' => 'nullable|string|max:100',
            'title' => 'nullable|string',
            'estimated_cost_cr' => 'nullable|numeric|min:0',
            'sanctioned_cost_cr' => 'nullable|numeric|min:0',
            'executed_amount_cr' => 'nullable|numeric|min:0',
            'funding_pattern' => 'nullable|string',
            'central_share_cr' => 'nullable|numeric|min:0',
            'state_share_cr' => 'nullable|numeric|min:0',
            'released_central_share_cr' => 'nullable|numeric|min:0',
            'released_state_share_cr' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
            'state_govt_submission_date' => 'nullable|date',
            'state_govt_doc' => 'nullable|file|mimes:pdf',
            'state_govt_doc_note' => 'nullable|string',
            'brahmaputra_board_submission_date' => 'nullable|date',
            'brahmaputra_board_doc' => 'nullable|file|mimes:pdf',
            'brahmaputra_board_doc_note' => 'nullable|string',
            'mojs_submission_date' => 'nullable|date',
            'mojs_doc' => 'nullable|file|mimes:pdf',
            'mojs_doc_note' => 'nullable|string',
            'district' => 'nullable|string',
            'river_basin' => 'nullable|string',
        ]);

        if (empty($validated['scheme_code']) && !empty($validated['project_code'])) {
            $validated['scheme_code'] = $validated['project_code'];
        }
        if (empty($validated['scheme_name']) && !empty($validated['title'])) {
            $validated['scheme_name'] = $validated['title'];
        }
        if (empty($validated['estimated_cost_cr']) && !empty($validated['sanctioned_cost_cr'])) {
            $validated['estimated_cost_cr'] = $validated['sanctioned_cost_cr'];
        }

        $district = $validated['district'] ?? null;
        $riverBasin = $validated['river_basin'] ?? null;

        unset($validated['project_code'], $validated['title'], $validated['sanctioned_cost_cr'], $validated['district'], $validated['river_basin']);

        $centralShare = $validated['central_share_cr'] ?? 0;
        $stateShare = $validated['state_share_cr'] ?? 0;
        $releasedCentral = $validated['released_central_share_cr'] ?? 0;
        $releasedState = $validated['released_state_share_cr'] ?? 0;

        $validated['balance_central_share_cr'] = round($centralShare - $releasedCentral, 2);
        $validated['balance_state_share_cr'] = round($stateShare - $releasedState, 2);

        if ($request->hasFile('state_govt_doc')) {
            $validated['state_govt_doc_path'] = '/storage/'.$request->file('state_govt_doc')->store('proposals/state_govt', 'public');
        }
        unset($validated['state_govt_doc']);

        if ($request->hasFile('brahmaputra_board_doc')) {
            $validated['brahmaputra_board_doc_path'] = '/storage/'.$request->file('brahmaputra_board_doc')->store('proposals/bbrd', 'public');
        }
        unset($validated['brahmaputra_board_doc']);

        if ($request->hasFile('mojs_doc')) {
            $validated['mojs_doc_path'] = '/storage/'.$request->file('mojs_doc')->store('proposals/mojs', 'public');
        }
        unset($validated['mojs_doc']);

        $fmbapProject->update($validated);

        // Also create/update corresponding Scheme so it shows up in Scheme Master Catalogue (Dashboard)
        if (!empty($fmbapProject->scheme_code)) {
            $scheme = \App\Models\Scheme::where('scheme_code', $fmbapProject->scheme_code)->first();

            $fundingPattern = $fmbapProject->funding_pattern ?? '90/10';
            $parts = explode('/', $fundingPattern);
            $centralPct = (isset($parts[0]) && is_numeric($parts[0])) ? (int) $parts[0] : 90;
            $statePct = (isset($parts[1]) && is_numeric($parts[1])) ? (int) $parts[1] : 10;

            if (!$scheme) {
                \App\Models\Scheme::create([
                    'scheme_code'          => $fmbapProject->scheme_code,
                    'scheme_name'          => $fmbapProject->scheme_name ?? 'Untitled Scheme',
                    'state'                => $fmbapProject->state ?? 'Assam',
                    'district'             => $district ?? $request->district ?? null,
                    'river_basin'          => $riverBasin ?? $request->river_basin ?? 'Brahmaputra',
                    'sanctioned_amount_cr' => $fmbapProject->estimated_cost_cr ?? 0,
                    'central_share_pct'    => $centralPct,
                    'state_share_pct'      => $statePct,
                    'project_type'         => $request->project_type ?? 'Flood Management',
                    'physical_status'      => 'Ongoing',
                    'physical_progress_pct' => 0.00,
                    'is_active'            => true,
                ]);
            } else {
                $scheme->update([
                    'scheme_name'          => $fmbapProject->scheme_name ?? 'Untitled Scheme',
                    'state'                => $fmbapProject->state ?? 'Assam',
                    'district'             => $district ?? $request->district ?? null,
                    'river_basin'          => $riverBasin ?? $request->river_basin ?? 'Brahmaputra',
                    'sanctioned_amount_cr' => $fmbapProject->estimated_cost_cr ?? 0,
                    'central_share_pct'    => $centralPct,
                    'state_share_pct'      => $statePct,
                    'project_type'         => $request->project_type ?? 'Flood Management',
                ]);
            }
        }

        return redirect()->back()->with('success', 'FMBAP Proposal updated successfully.');
    }

    public function forwardToMojs(Request $request, FmbapProject $fmbapProject)
    {
        $role = auth()->user()->role;
        if (!in_array($role, ['board_official', 'bbrd_inspector', 'super_admin', 'admin'])) {
            abort(403, 'Only BB officials can forward to MoJS.');
        }
        
        $request->validate(['bb_remarks' => 'nullable|string']);
        $fmbapProject->update([
            'status' => 'FORWARDED_TO_MOJS',
            'bb_remarks' => $request->bb_remarks
        ]);
        return back()->with('success', 'Forwarded to MoJS successfully.');
    }

    public function addMojsDecision(Request $request, FmbapProject $fmbapProject)
    {
        $role = auth()->user()->role;
        if (!in_array($role, ['mojs_official', 'central_admin', 'super_admin', 'admin'])) {
            abort(403, 'Only MoJS officials can add a decision.');
        }
        
        $request->validate(['mojs_remarks' => 'nullable|string']);
        $fmbapProject->update([
            'status' => 'REVIEWED_BY_MOJS',
            'mojs_remarks' => $request->mojs_remarks
        ]);
        return back()->with('success', 'MoJS decision recorded and sent back to BB.');
    }

    public function forwardDecisionToState(Request $request, FmbapProject $fmbapProject)
    {
        $role = auth()->user()->role;
        if (!in_array($role, ['board_official', 'bbrd_inspector', 'super_admin', 'admin'])) {
            abort(403, 'Only BB officials can forward the decision to State.');
        }
        
        $fmbapProject->update([
            'status' => 'RETURNED_TO_STATE',
        ]);
        return back()->with('success', 'Decision forwarded to State successfully.');
    }

    public function requestRelease(Request $request, FmbapProject $fmbapProject)
    {
        $user = auth()->user();
        if (!$user->isState() && $user->role !== 'state') {
            abort(403, 'Only State Officials can request fund release.');
        }

        if (empty($fmbapProject->scheme_code)) {
            return back()->withErrors(['message' => 'Please configure a Scheme Code for this project before requesting fund release.']);
        }

        $scheme = \App\Models\Scheme::where('scheme_code', $fmbapProject->scheme_code)->first();
        if (!$scheme) {
            return back()->withErrors(['message' => 'Corresponding Scheme not found. Please edit this project to register it in the master catalogue.']);
        }

        $validated = $request->validate([
            'instalment_number' => 'required|integer|min:1',
            'requested_amount_cr' => 'required|numeric|min:0.01',
            'bank_details' => 'nullable|string',
            'physical_progress_pct' => 'nullable|numeric|min:0|max:100',
            'financial_progress_pct' => 'nullable|numeric|min:0|max:100',
            'utilization_certificate' => 'required|file|mimes:pdf|max:204800',
            'state_remarks' => 'nullable|string',
        ]);

        $ucPath = null;
        if ($request->hasFile('utilization_certificate')) {
            $ucPath = '/storage/' . $request->file('utilization_certificate')->store('fund_release/uc', 'public');
        }

        \App\Models\PaymentRequest::create([
            'scheme_id' => $scheme->id,
            'user_id' => $user->id,
            'state' => $user->state,
            'status' => \App\Models\PaymentRequest::STATUS_SUBMITTED_TO_BB,
            'requested_amount_cr' => $validated['requested_amount_cr'],
            'instalment_number' => $validated['instalment_number'],
            'bank_details' => $validated['bank_details'] ?? null,
            'state_remarks' => $validated['state_remarks'] ?? null,
            'physical_progress_pct' => $validated['physical_progress_pct'] ?? 0,
            'financial_progress_pct' => $validated['financial_progress_pct'] ?? 0,
            'utilization_certificate_path' => $ucPath,
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Fund Release Request submitted to Brahmaputra Board successfully.');
    }

    public function export()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\FmbapProjectExport(auth()->user()), 'fmbap_proposals.xlsx');
    }
}
