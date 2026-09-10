<?php

namespace App\Http\Controllers;

use App\Models\Scheme;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SchemeAdminController extends Controller
{
    /**
     * Display a listing of schemes.
     */
    public function index()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $schemes = Scheme::latest()->get();

        return Inertia::render('Admin/Schemes/Index', [
            'schemes' => $schemes,
        ]);
    }

    /**
     * Store a newly created scheme.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'scheme_code'           => 'required|string|unique:schemes',
            'scheme_name'           => 'required|string',
            'river_basin'           => 'nullable|string',
            'district'              => 'nullable|string',
            'state'                 => 'nullable|string',
            'sanctioned_amount_cr'  => 'required|numeric|min:0',
            'central_share_pct'     => 'required|integer|min:0|max:100',
            'state_share_pct'       => 'required|integer|min:0|max:100',
            'project_type'          => 'nullable|string',
            'physical_status'       => 'nullable|string',
            'physical_progress_pct' => 'nullable|numeric|min:0|max:100',
            'is_active'             => 'boolean',
        ]);

        Scheme::create($validated);

        return redirect()->back()->with('success', 'Scheme added successfully.');
    }

    /**
     * Update the specified scheme.
     */
    public function update(Request $request, Scheme $scheme)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'scheme_code'           => 'required|string|unique:schemes,scheme_code,' . $scheme->id,
            'scheme_name'           => 'required|string',
            'river_basin'           => 'nullable|string',
            'district'              => 'nullable|string',
            'state'                 => 'nullable|string',
            'sanctioned_amount_cr'  => 'required|numeric|min:0',
            'central_share_pct'     => 'required|integer|min:0|max:100',
            'state_share_pct'       => 'required|integer|min:0|max:100',
            'project_type'          => 'nullable|string',
            'physical_status'       => 'nullable|string',
            'physical_progress_pct' => 'nullable|numeric|min:0|max:100',
            'is_active'             => 'boolean',
        ]);

        $scheme->update($validated);

        return redirect()->back()->with('success', 'Scheme updated successfully.');
    }

    /**
     * Remove the specified scheme.
     */
    public function destroy(Scheme $scheme)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $scheme->delete();

        return redirect()->back()->with('success', 'Scheme deleted successfully.');
    }
}
