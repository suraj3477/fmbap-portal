<?php

namespace App\Http\Controllers;

use App\Models\Scheme;
use App\Services\SchemeExcelService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SchemeController extends Controller
{
    protected SchemeExcelService $excelService;

    public function __construct(SchemeExcelService $excelService)
    {
        $this->excelService = $excelService;
    }

    /**
     * Full Scheme Master Catalogue page.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $schemes = Scheme::with([
            'paymentRequests' => function ($q) use ($user) {
                if ($user->role === 'state_official') {
                    $q->where('user_id', $user->id);
                }
                $q->latest();
            },
            'progressReports' => function ($q) use ($user) {
                if ($user->role === 'state_official') {
                    $q->where('user_id', $user->id);
                }
                $q->latest()->select('id', 'scheme_id', 'status', 'physical_progress_pct', 'financial_progress_pct', 'reporting_period', 'created_at');
            },
            'fmbapProject',
        ])->orderByDesc('id')->get();

        $totalSanctioned = Scheme::sum('sanctioned_amount_cr');
        $completedCount  = Scheme::where('physical_status', 'Completed')->count();
        $totalCount      = Scheme::count();
        $avgProgress     = $totalCount > 0 ? round(Scheme::avg('physical_progress_pct'), 1) : 0;

        return Inertia::render('Schemes/Index', [
            'schemes' => $schemes,
            'stats' => [
                'total'            => $totalCount,
                'completed'        => $completedCount,
                'ongoing'          => $totalCount - $completedCount,
                'total_sanctioned' => number_format($totalSanctioned, 2),
                'avg_progress'     => $avgProgress,
            ],
        ]);
    }

    /**
     * Download the official Excel template.
     */
    public function downloadTemplate()
    {
        $filePath = $this->excelService->generateTemplate();

        return response()->download($filePath, 'FMBAP_Scheme_Master_Template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Parse uploaded Excel file and return preview rows for the modal.
     */
    public function parseExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|max:20480', // max 20MB
        ]);

        $file = $request->file('excel_file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
            return response()->json(['error' => 'Please upload a valid .xlsx, .xls, or .csv Excel spreadsheet.'], 422);
        }

        try {
            $parsed = $this->excelService->parseExcelFile($file->getRealPath());
            return response()->json([
                'success' => true,
                'rows'    => $parsed,
                'count'   => count($parsed),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Could not parse Excel file: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Import parsed rows or direct Excel file into Schemes catalogue.
     */
    public function importExcel(Request $request)
    {
        if ($request->has('rows') && is_array($request->rows)) {
            $result = $this->excelService->importRows($request->rows);
            return redirect()->back()->with('success', $this->formatImportMessage($result));
        }

        $request->validate([
            'excel_file' => 'required|file|max:20480',
        ]);

        $file = $request->file('excel_file');
        $parsed = $this->excelService->parseExcelFile($file->getRealPath());
        $result = $this->excelService->importRows($parsed);

        return redirect()->back()->with('success', $this->formatImportMessage($result));
    }

    /**
     * Helper to generate user-friendly summary of import operations.
     */
    private function formatImportMessage(array $result): string
    {
        $parts = [];
        if (($result['created'] ?? 0) > 0) {
            $parts[] = "Entered {$result['created']} new scheme(s) into the portal";
        }
        if (($result['updated'] ?? 0) > 0) {
            $parts[] = "updated {$result['updated']} existing scheme(s) (prevented duplicate insertion)";
        }
        if (($result['skipped'] ?? 0) > 0) {
            $parts[] = "skipped {$result['skipped']} duplicate row(s) in spreadsheet";
        }

        return !empty($parts)
            ? implode(', ', $parts) . '.'
            : 'Excel processed successfully. No new schemes were added as they already exist in the portal.';
    }

    /**
     * Store a single new Scheme (manual entry from modal).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'scheme_code'              => 'required|string|max:100|unique:schemes,scheme_code',
            'scheme_name'              => 'required|string',
            'division'                 => 'nullable|string|max:150',
            'district'                 => 'nullable|string|max:150',
            'state'                    => 'nullable|string|max:100',
            'river_basin'              => 'nullable|string|max:100',
            'plan_period'              => 'nullable|string|max:100',
            'estimated_cost_lakh'      => 'nullable|numeric|min:0',
            'sanctioned_amount_cr'     => 'nullable|numeric|min:0',
            'fund_utilised_cs_lakh'    => 'nullable|numeric|min:0',
            'fund_utilised_ss_lakh'    => 'nullable|numeric|min:0',
            'fund_utilised_total_lakh' => 'nullable|numeric|min:0',
            'fund_req_cs_lakh'         => 'nullable|numeric|min:0',
            'fund_req_ss_lakh'         => 'nullable|numeric|min:0',
            'fund_req_total_lakh'      => 'nullable|numeric|min:0',
            'central_share_pct'        => 'nullable|integer|min:0|max:100',
            'state_share_pct'          => 'nullable|integer|min:0|max:100',
            'physical_status'          => 'nullable|string',
            'physical_progress_pct'    => 'nullable|numeric|min:0|max:100',
        ]);

        // Prevent duplicate scheme code insertion
        $cleanCode = strtolower(trim($validated['scheme_code']));
        $existing = Scheme::whereRaw('LOWER(TRIM(scheme_code)) = ?', [$cleanCode])->first();

        if ($existing) {
            return redirect()->back()->withErrors([
                'scheme_code' => "A scheme with code '{$validated['scheme_code']}' already exists in the portal ({$existing->scheme_name}). Duplicate scheme codes are not allowed.",
            ]);
        }

        // Auto-calculate missing values
        if (empty($validated['district']) && !empty($validated['division'])) {
            $validated['district'] = $validated['division'];
        }
        if (empty($validated['sanctioned_amount_cr']) && !empty($validated['estimated_cost_lakh'])) {
            $validated['sanctioned_amount_cr'] = round($validated['estimated_cost_lakh'] / 100, 2);
        } elseif (empty($validated['estimated_cost_lakh']) && !empty($validated['sanctioned_amount_cr'])) {
            $validated['estimated_cost_lakh'] = round($validated['sanctioned_amount_cr'] * 100, 2);
        }

        $validated['central_share_pct'] = $validated['central_share_pct'] ?? 90;
        $validated['state_share_pct'] = $validated['state_share_pct'] ?? 10;
        $validated['state'] = $validated['state'] ?? 'Assam';
        $validated['river_basin'] = $validated['river_basin'] ?? 'Brahmaputra';
        $validated['plan_period'] = $validated['plan_period'] ?? 'XI Plan';
        $validated['physical_status'] = $validated['physical_status'] ?? 'Ongoing';
        $validated['physical_progress_pct'] = $validated['physical_progress_pct'] ?? 0;
        $validated['is_active'] = true;

        Scheme::create($validated);

        return redirect()->back()->with('success', 'Scheme created successfully and added to the Master Catalogue.');
    }

    /**
     * Search API for auto-populating scheme dropdown.
     */
    public function apiSearch(Request $request)
    {
        $query = Scheme::active();

        if ($request->filled('q')) {
            $searchTerm = '%' . trim($request->q) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('scheme_code', 'like', $searchTerm)
                  ->orWhere('scheme_name', 'like', $searchTerm)
                  ->orWhere('division', 'like', $searchTerm)
                  ->orWhere('district', 'like', $searchTerm);
            });
        }

        // Limit to 30 for dropdown performance, strictly newest first
        $schemes = $query->with('fmbapProject')->orderByDesc('id')->take(30)->get();

        return response()->json($schemes);
    }
}
