<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\FmbapProjectController;
use App\Http\Controllers\FmbapProposalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchemeController;
use App\Http\Controllers\SchemeAdminController;
use App\Http\Controllers\PaymentRequestController;
use App\Http\Controllers\BbMonitoringReportController;
use App\Http\Controllers\ProgressReportController;
use App\Http\Controllers\AuditLogController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Logo Asset Route
Route::get('/logo.png', function () {
    $path = public_path('logo.png');
    if (file_exists($path)) {
        return response()->file($path, ['Content-Type' => 'image/png']);
    }
    abort(404);
});

// Public Entry - Redirect directly to login page
Route::get('/', function () {
    return redirect()->route('login');
});

// Authenticated & Approved User Routes
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    
    // Primary Dashboards
    Route::get('/dashboard', function () {
        $user = auth()->user();

        // KPI summary from new modules
        $userRequests = \App\Models\PaymentRequest::forUser($user)->with('scheme');
        $approvedRequests = (clone $userRequests)->where('status', \App\Models\PaymentRequest::STATUS_APPROVED)->get();

        $totalSanctioned = \App\Models\Scheme::sum('sanctioned_amount_cr');
        $totalReleased = $approvedRequests->sum('requested_amount_cr');

        $totalSchemes = \App\Models\Scheme::count();
        $completedSchemes = \App\Models\Scheme::where('physical_status', 'Completed')->count();
        $ongoingSchemes = $totalSchemes - $completedSchemes;
        $avgPhysical = $totalSchemes > 0
            ? round(\App\Models\Scheme::avg('physical_progress_pct'), 1)
            : 0;

        $summary = [
            'total_projects'        => $totalSchemes,
            'completed_schemes'     => $completedSchemes,
            'ongoing_schemes'       => $ongoingSchemes,
            'total_sanctioned_cr'   => number_format($totalSanctioned, 2),
            'total_released_cr'     => number_format($totalReleased, 2),
            'avg_physical_progress' => $avgPhysical,
        ];

        $moduleCounts = [
            'fund_release' => \App\Models\PaymentRequest::forUser($user)->count(),
            'monitoring_requests' => \App\Models\PaymentRequest::whereIn('status', [
                \App\Models\PaymentRequest::STATUS_SUBMITTED_TO_BB,
                \App\Models\PaymentRequest::STATUS_BB_MONITORING,
                \App\Models\PaymentRequest::STATUS_FORWARDED_TO_MOJS,
                \App\Models\PaymentRequest::STATUS_NEEDS_CORRECTION,
            ])->count(),
            'progress_reports' => \App\Models\ProgressReport::forUser($user)->count(),
        ];

        // Scheme catalogue with related project data
        $schemes = \App\Models\Scheme::with([
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
                $q->latest()->select('id','scheme_id','status','physical_progress_pct','financial_progress_pct','reporting_period','created_at');
            },
            'fmbapProject',
        ])->orderByDesc('id')->get();

        return Inertia::render('Dashboard', [
            'userRole'     => $user->role,
            'moduleCounts' => $moduleCounts,
            'summary'      => $summary,
            'schemes'      => $schemes,
            'projects'     => [],
        ]);
    })->name('dashboard');
    Route::get('/fmbap', [FmbapProjectController::class, 'index'])->name('fmbap.dashboard');

    // Proposals (Step 1)
    Route::get('/fmbap/proposals', [FmbapProposalController::class, 'index'])->name('fmbap.proposals.index');
    Route::get('/fmbap/proposals/create', [FmbapProposalController::class, 'create'])->name('fmbap.proposals.create');
    Route::post('/fmbap/proposals', [FmbapProposalController::class, 'store'])->name('fmbap.proposals.store');
    Route::get('/fmbap/proposals/{proposal}/edit', [FmbapProposalController::class, 'edit'])->name('fmbap.proposals.edit');
    Route::post('/fmbap/proposals/{proposal}/update', [FmbapProposalController::class, 'update'])->name('fmbap.proposals.update');
    Route::patch('/fmbap/proposals/{proposal}/status', [FmbapProposalController::class, 'updateStatus'])->name('fmbap.proposals.status');

    // Project Operations
    Route::post('/fmbap/projects', [FmbapProjectController::class, 'store'])->name('fmbap.store');
    Route::get('/fmbap/projects/{fmbapProject}', [FmbapProjectController::class, 'show'])->name('fmbap.show');
    Route::patch('/fmbap/projects/{fmbapProject}', [FmbapProjectController::class, 'update'])->name('fmbap.update');
    Route::patch('/fmbap/projects/{fmbapProject}/status', [FmbapProjectController::class, 'updateStatus'])->name('fmbap.projects.status');
    Route::post('/fmbap/projects/{fmbapProject}/request-release', [FmbapProjectController::class, 'requestRelease'])->name('fmbap.projects.requestRelease');
    
    // Workflow Routes
    Route::post('/fmbap/projects/{fmbapProject}/forward-mojs', [FmbapProjectController::class, 'forwardToMojs'])->name('fmbap.projects.forwardMojs');
    Route::post('/fmbap/projects/{fmbapProject}/mojs-decision', [FmbapProjectController::class, 'addMojsDecision'])->name('fmbap.projects.mojsDecision');
    Route::post('/fmbap/projects/{fmbapProject}/forward-state', [FmbapProjectController::class, 'forwardDecisionToState'])->name('fmbap.projects.forwardState');
    
    // Export
    Route::get('/fmbap/export', [FmbapProjectController::class, 'export'])->name('fmbap.export');
    
    // Documents & Reports
    Route::post('/fmbap/projects/{fmbapProject}/upload', [FmbapProjectController::class, 'uploadDocument'])->name('fmbap.documents.upload');
    Route::get('/fmbap/projects/{fmbapProject}/pdf', [FmbapProjectController::class, 'downloadPdf'])->name('fmbap.projects.pdf');

    // --- NEW MODULE ROUTES ---

    // Scheme Catalogue & Excel Import/Export
    Route::get('/schemes', [SchemeController::class, 'index'])->name('schemes.index');
    Route::post('/schemes', [SchemeController::class, 'store'])->name('schemes.store');
    Route::get('/schemes/download-template', [SchemeController::class, 'downloadTemplate'])->name('schemes.download-template');
    Route::post('/schemes/parse-excel', [SchemeController::class, 'parseExcel'])->name('schemes.parse-excel');
    Route::post('/schemes/import-excel', [SchemeController::class, 'importExcel'])->name('schemes.import-excel');

    // Scheme Catalogue API
    Route::get('/api/schemes/search', [SchemeController::class, 'apiSearch'])->name('schemes.search');

    // MODULE 1: Fund Release (Payment Requests)
    Route::prefix('fund-release')->name('fund-release.')->group(function () {
        Route::get('/', [PaymentRequestController::class, 'index'])->name('index');
        Route::get('/create', [PaymentRequestController::class, 'create'])->name('create');
        Route::post('/', [PaymentRequestController::class, 'store'])->name('store');
        Route::get('/{fund_release}', [PaymentRequestController::class, 'show'])->name('show');
        Route::get('/{fund_release}/edit', [PaymentRequestController::class, 'edit'])->name('edit');
        Route::put('/{fund_release}', [PaymentRequestController::class, 'update'])->name('update');
        Route::post('/{fund_release}/bb-decision', [PaymentRequestController::class, 'bbDecision'])->name('bbDecision');
        Route::post('/{fund_release}/mojs-decision', [PaymentRequestController::class, 'mojsDecision'])->name('mojsDecision');
        Route::get('/{fund_release}/dossier', [PaymentRequestController::class, 'downloadDossier'])->name('dossier');
        Route::delete('/{fund_release}', [PaymentRequestController::class, 'destroy'])->name('destroy');
    });

    // MODULE 2: Monitoring Requests (BB)
    Route::prefix('monitoring-requests')->name('monitoring-requests.')->group(function () {
        Route::get('/', [BbMonitoringReportController::class, 'index'])->name('index');
        Route::get('/{id}/report/create', [BbMonitoringReportController::class, 'create'])->name('report.create');
        Route::post('/{id}/report', [BbMonitoringReportController::class, 'store'])->name('report.store');
        Route::get('/{id}/report/edit', [BbMonitoringReportController::class, 'edit'])->name('report.edit');
        Route::put('/{id}/report', [BbMonitoringReportController::class, 'update'])->name('report.update');
        Route::post('/{id}/report/submit', [BbMonitoringReportController::class, 'submit'])->name('report.submit');
    });

    // MODULE 3: Progress Report Monitoring (Disabled as requested - progress is tracked inside Fund Release)
    Route::get('/progress-reports', function () {
        return redirect()->route('fund-release.index');
    })->name('progress-reports.index');

    // Audit Trail
    Route::get('/{module}/{id}/audit', [AuditLogController::class, 'index'])->name('audit');

    // -------------------------

    // Super Admin Management Panel (Protected via inline role check)
    Route::group([
        'prefix' => 'admin',
        'middleware' => function ($request, $next) {
            if ($request->user() && $request->user()->isSuperAdmin()) {
                return $next($request);
            }
            abort(403, 'Unauthorized access to Super Admin Panel.');
        }
    ], function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::patch('/users/{user}/approve', [AdminUserController::class, 'toggleApprove'])->name('admin.users.approve');
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('admin.users.role');
        
        // Scheme Management
        Route::prefix('schemes')->name('admin.schemes.')->group(function () {
            Route::get('/', [SchemeAdminController::class, 'index'])->name('index');
            Route::post('/', [SchemeAdminController::class, 'store'])->name('store');
            Route::put('/{scheme}', [SchemeAdminController::class, 'update'])->name('update');
            Route::delete('/{scheme}', [SchemeAdminController::class, 'destroy'])->name('destroy');
        });
    });
});

// Profile Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';