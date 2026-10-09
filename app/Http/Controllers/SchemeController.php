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
            'paymentRequests' => function ($q) {
                $q->with('bbMonitoringReport')->latest();
            },
            'progressReports' => function ($q) {
                $q->latest()->select('id', 'scheme_id', 'status', 'physical_progress_pct', 'financial_progress_pct', 'reporting_period', 'created_at');
            },
            'fmbapProject',
        ])->orderByDesc('id')->get();

        $totalReleasedCr = 0.0;
        $totalCurtailedCr = 0.0;

        foreach ($schemes as $scheme) {
            if ($scheme->fmbapProject && !empty($scheme->fmbapProject->funding_pattern)) {
                $parts = explode('/', $scheme->fmbapProject->funding_pattern);
                if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                    $cPct = (int) $parts[0];
                    $sPct = (int) $parts[1];
                    if ($scheme->central_share_pct != $cPct || $scheme->state_share_pct != $sPct) {
                        $scheme->update([
                            'central_share_pct' => $cPct,
                            'state_share_pct'   => $sPct,
                        ]);
                    }
                    $scheme->central_share_pct = $cPct;
                    $scheme->state_share_pct = $sPct;
                }
            }

            $centralSharePct = (float) ($scheme->central_share_pct ?? 90);
            $stateSharePct = (float) ($scheme->state_share_pct ?? 10);
            $sanctionedCr = (float) ($scheme->sanctioned_amount_cr ?? 0);
            $centralEntitlementCr = round($sanctionedCr * ($centralSharePct / 100), 2);
            $scheme->central_share_entitlement_cr = $centralEntitlementCr;

            $approvedPrs = $scheme->paymentRequests ? $scheme->paymentRequests->where('status', 'APPROVED') : collect();
            $approvedClaimsSum = round($approvedPrs->sum(function ($pr) {
                return (float) ($pr->approved_amount_cr ?? $pr->requested_amount_cr ?? 0);
            }), 2);
            $curtailedSum = round($approvedPrs->sum(function ($pr) {
                return (float) ($pr->deduction_amount_cr ?? 0);
            }), 2);

            $fmbapReleased = (float) ($scheme->fmbapProject?->released_central_share_cr ?? 0);
            $utilisedCs = !empty($scheme->fund_utilised_cs_lakh) ? round(((float) $scheme->fund_utilised_cs_lakh) / 100, 2) : 0;
            $cumReleased = max($approvedClaimsSum, $fmbapReleased, $utilisedCs);

            $releasedDisplay = $approvedClaimsSum > 0 ? $approvedClaimsSum : $cumReleased;
            $balanceCr = max(0, round($centralEntitlementCr - $cumReleased, 2));

            $scheme->approved_claims_release_cr = $approvedClaimsSum;
            $scheme->curtailed_amount_cr        = $curtailedSum;
            $scheme->released_central_share_cr  = $releasedDisplay;
            $scheme->cumulative_released_cr     = $cumReleased;
            $scheme->balance_central_share_cr   = $balanceCr;

            // Curtailment reason and sanction order details
            $curtailedPr = $approvedPrs->where('deduction_amount_cr', '>', 0)->first();
            $scheme->latest_curtailment_reason = $curtailedPr?->curtailment_reason;

            $latestSanctionedPr = $approvedPrs->whereNotNull('sanction_order_no')->first();
            $scheme->latest_sanction_order_no = $latestSanctionedPr?->sanction_order_no;
            $scheme->latest_sanction_order_date = $latestSanctionedPr?->sanction_order_date;
            $scheme->latest_sanction_order_doc_path = $latestSanctionedPr?->sanction_order_doc_path;

            $totalReleasedCr += $releasedDisplay;
            $totalCurtailedCr += $curtailedSum;
        }

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
                'total_released'   => number_format($totalReleasedCr, 2),
                'total_curtailed'  => number_format($totalCurtailedCr, 2),
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
        $messages = [
            'scheme_code.required' => 'Scheme Code is required (e.g. AS-19).',
            'scheme_code.unique'   => "Scheme Code '{$request->scheme_code}' is already registered in the system. Please enter a unique code.",
            'scheme_name.required' => 'Please enter the Scheme Title / Project Description.',
            'estimated_cost_lakh.numeric' => 'Estimated Cost must be a valid number in Lakhs.',
        ];

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
        ], $messages);

        // Prevent duplicate scheme code insertion (case-insensitive check)
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
        $validated['state'] = $validated['state'] ?? (auth()->user()->state ?? 'Assam');
        $validated['river_basin'] = $validated['river_basin'] ?? 'Brahmaputra';
        $validated['plan_period'] = $validated['plan_period'] ?? 'XI Plan';
        $validated['physical_status'] = $validated['physical_status'] ?? 'Ongoing';
        $validated['physical_progress_pct'] = $validated['physical_progress_pct'] ?? 0;
        $validated['is_active'] = true;

        $scheme = Scheme::create($validated);

        return redirect()->back()->with('success', "Scheme {$scheme->scheme_code} created successfully and added to the Master Catalogue.");
    }

    /**
     * API to generate next available unique scheme code.
     */
    public function nextCode(Request $request)
    {
        $state = $request->query('state', auth()->user()->state ?? 'Assam');
        $prefix = match (strtolower(trim($state))) {
            'assam'             => 'AS',
            'arunachal pradesh' => 'AR',
            'meghalaya'         => 'ML',
            'manipur'           => 'MN',
            'mizoram'           => 'MZ',
            'nagaland'          => 'NL',
            'tripura'           => 'TR',
            'sikkim'            => 'SK',
            'west bengal'       => 'WB',
            default             => 'SCH',
        };

        $codes = Scheme::where('scheme_code', 'like', "{$prefix}-%")->pluck('scheme_code');
        $max = 0;
        foreach ($codes as $c) {
            if (preg_match('/' . preg_quote($prefix, '/') . '-(\d+)/i', $c, $matches)) {
                $num = intval($matches[1]);
                if ($num > $max && $num < 1000) {
                    $max = $num;
                }
            }
        }

        $nextNum = max($max + 1, 1);
        $nextCode = sprintf("%s-%02d", $prefix, $nextNum);

        while (Scheme::where('scheme_code', $nextCode)->exists()) {
            $nextNum++;
            $nextCode = sprintf("%s-%02d", $prefix, $nextNum);
        }

        return response()->json([
            'prefix'    => $prefix,
            'next_code' => $nextCode,
        ]);
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
        $schemes = $query->with([
            'fmbapProject',
            'paymentRequests' => function ($q) {
                $q->orderBy('instalment_number', 'asc')->orderBy('id', 'asc');
            }
        ])->orderByDesc('id')->take(30)->get();

        $schemes->each(function ($scheme) {
            $scheme->enrichFinancialMetrics();
        });

        return response()->json($schemes);
    }
}
