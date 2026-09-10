<?php

namespace App\Http\Controllers;

use App\Models\FmbapProposal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Models\FmbapProject;

class FmbapProposalController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = FmbapProposal::query();

        if ($user->isState() || $user->role === 'state') {
            $query->where('state', $user->state);
        }

        $proposals = $query->latest()->get();

        return Inertia::render('Fmbap/Proposals/Index', [
            'proposals' => $proposals,
            'userRole' => $user->role,
        ]);
    }

    public function create()
    {
        return Inertia::render('Fmbap/Proposals/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'scheme_name' => 'required|string|max:2000',
            'project_type' => 'nullable|string|max:255',
            'river_basin' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'latitude' => 'nullable|string|max:255',
            'longitude' => 'nullable|string|max:255',
            'estimated_cost_cr' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'videos.*' => 'nullable|mimes:mp4,mov,ogg|max:20480',
            'pdfs.*' => 'nullable|mimes:pdf|max:204800',
        ]);

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photoPaths[] = $photo->store('proposals/photos', 'public');
            }
        }

        $videoPaths = [];
        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $video) {
                $videoPaths[] = $video->store('proposals/videos', 'public');
            }
        }

        $pdfPaths = [];
        if ($request->hasFile('pdfs')) {
            foreach ($request->file('pdfs') as $pdf) {
                $pdfPaths[] = $pdf->store('proposals/pdfs', 'public');
            }
        }

        $proposal = FmbapProposal::create([
            'user_id' => $request->user()->id,
            'state' => $request->user()->state,
            'scheme_name' => $validated['scheme_name'],
            'project_type' => $validated['project_type'] ?? null,
            'river_basin' => $validated['river_basin'] ?? null,
            'district' => $validated['district'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'estimated_cost_cr' => $validated['estimated_cost_cr'],
            'description' => $validated['description'] ?? null,
            'photos' => $photoPaths,
            'videos' => $videoPaths,
            'pdfs' => $pdfPaths,
            'status' => 'SUBMITTED_BY_STATE',
        ]);

        return redirect()->route('fmbap.proposals.index')->with('success', 'Proposal submitted successfully.');
    }

    public function edit(Request $request, FmbapProposal $proposal)
    {
        $user = $request->user();
        if (!in_array($user->role, ['state', 'state_official']) || $proposal->state !== $user->state) {
            abort(403, 'Unauthorized.');
        }
        if ($proposal->status !== 'NEEDS_CORRECTION') {
            abort(403, 'Only proposals requiring correction can be edited.');
        }

        return Inertia::render('Fmbap/Proposals/Edit', [
            'proposal' => $proposal,
        ]);
    }

    public function update(Request $request, FmbapProposal $proposal)
    {
        $user = $request->user();
        if (!in_array($user->role, ['state', 'state_official']) || $proposal->state !== $user->state) {
            abort(403, 'Unauthorized.');
        }
        if ($proposal->status !== 'NEEDS_CORRECTION') {
            abort(403, 'Only proposals requiring correction can be edited.');
        }

        $validated = $request->validate([
            'scheme_name' => 'required|string|max:2000',
            'project_type' => 'nullable|string|max:255',
            'river_basin' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'latitude' => 'nullable|string|max:255',
            'longitude' => 'nullable|string|max:255',
            'estimated_cost_cr' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'videos.*' => 'nullable|mimes:mp4,mov,ogg|max:20480',
            'pdfs.*' => 'nullable|mimes:pdf|max:204800',
        ]);

        $photoPaths = $proposal->photos ?? [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photoPaths[] = $photo->store('proposals/photos', 'public');
            }
        }

        $videoPaths = $proposal->videos ?? [];
        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $video) {
                $videoPaths[] = $video->store('proposals/videos', 'public');
            }
        }

        $pdfPaths = $proposal->pdfs ?? [];
        if ($request->hasFile('pdfs')) {
            foreach ($request->file('pdfs') as $pdf) {
                $pdfPaths[] = $pdf->store('proposals/pdfs', 'public');
            }
        }

        $proposal->update([
            'scheme_name' => $validated['scheme_name'],
            'project_type' => $validated['project_type'] ?? null,
            'river_basin' => $validated['river_basin'] ?? null,
            'district' => $validated['district'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'estimated_cost_cr' => $validated['estimated_cost_cr'],
            'description' => $validated['description'] ?? null,
            'photos' => $photoPaths,
            'videos' => $videoPaths,
            'pdfs' => $pdfPaths,
            'status' => 'SUBMITTED_BY_STATE',
        ]);

        return redirect()->route('fmbap.proposals.index')->with('success', 'Proposal updated and resubmitted successfully.');
    }

    public function updateStatus(Request $request, FmbapProposal $proposal)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'status' => 'required|string',
            'remarks' => 'nullable|string',
        ]);

        if (in_array($user->role, ['super_admin', 'admin', 'board_official', 'bbrd_inspector'])) {
            $proposal->bb_remarks = $validated['remarks'];
        } elseif (in_array($user->role, ['mojs_official', 'central_admin'])) {
            $proposal->mojs_remarks = $validated['remarks'];
        }

        $proposal->status = $validated['status'];
        $proposal->save();

        if ($validated['status'] === 'APPROVED') {
            // Automatically convert to project
            FmbapProject::create([
                'user_id' => $proposal->user_id,
                'state' => $proposal->state,
                'status' => 'APPROVED',
                'scheme_name' => $proposal->scheme_name,
                'estimated_cost_cr' => $proposal->estimated_cost_cr,
            ]);
        }

        return redirect()->back()->with('success', 'Proposal status updated successfully.');
    }
}
