<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({
            total_projects: 0,
            total_sanctioned_cr: '0.00',
            total_released_cr: '0.00',
            avg_physical_progress: 0,
            completed_schemes: 0,
            ongoing_schemes: 0,
        }),
    },
    projects: { type: Array, default: () => [] },
    moduleCounts: {
        type: Object,
        default: () => ({ fund_release: 0, monitoring_requests: 0, progress_reports: 0 })
    },
    schemes: { type: Array, default: () => [] },
});

// Search & filter & pagination
const schemeSearch = ref('');
const activeStatusFilter = ref('ALL');
const sortBy = ref('ONGOING_FIRST'); // ONGOING_FIRST | PROGRESS_DESC | SANCTIONED_DESC | NEWEST
const activeSchemeId = ref(null);
const currentPage = ref(1);
const PER_PAGE = 6;

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Role helpers
const isBB = computed(() => user.value?.role === 'board_official');
const isMoJS = computed(() => user.value?.role === 'mojs_official');
const isStateOfficial = computed(() => ['state_official', 'state'].includes(user.value?.role));
const canSubmitDpr = computed(() => ['super_admin', 'board_official', 'state_official', 'state'].includes(user.value?.role));

// Role display
const roleLabel = computed(() => {
    const map = {
        board_official: 'Brahmaputra Board',
        mojs_official: 'Ministry of Jal Shakti',
        state_official: 'State Water Resources Dept.',
        state: 'State Official',
        super_admin: 'Super Admin',
        viewer: 'Viewer',
    };
    return map[user.value?.role] || user.value?.role || 'User';
});
const roleBadgeClass = computed(() => {
    const map = {
        board_official: 'badge-bb',
        mojs_official: 'badge-mojs',
        state_official: 'badge-state',
        state: 'badge-state',
        super_admin: 'badge-admin',
    };
    return map[user.value?.role] || 'badge-viewer';
});

// Helper for ongoing status
const isSchemeOngoing = (s) => {
    return s.physical_status !== 'Completed' && Number(s.physical_progress_pct) < 100;
};

// Scheme counts
const completedCount = computed(() =>
    props.schemes.filter(s => s.physical_status === 'Completed' || Number(s.physical_progress_pct) >= 100).length
);
const ongoingCount = computed(() =>
    props.schemes.filter(s => isSchemeOngoing(s)).length
);
const completionPct = computed(() =>
    Math.round((completedCount.value / (props.schemes.length || 1)) * 100)
);

// State-specific pending requests requiring correction
const stateNeedsCorrectionRequests = computed(() => {
    return props.schemes.flatMap(s => s.payment_requests || []).filter(r => r.status === 'NEEDS_CORRECTION');
});

// Sorted schemes with ONGOING FIRST by default
const sortedSchemes = computed(() => {
    return [...props.schemes].sort((a, b) => {
        if (sortBy.value === 'ONGOING_FIRST') {
            const aOngoing = isSchemeOngoing(a) ? 1 : 0;
            const bOngoing = isSchemeOngoing(b) ? 1 : 0;
            if (aOngoing !== bOngoing) {
                return bOngoing - aOngoing; // Ongoing (1) comes before Completed (0)
            }
            return (b.id || 0) - (a.id || 0);
        }
        if (sortBy.value === 'PROGRESS_DESC') {
            return (Number(b.physical_progress_pct) || 0) - (Number(a.physical_progress_pct) || 0);
        }
        if (sortBy.value === 'SANCTIONED_DESC') {
            return (Number(b.sanctioned_amount_cr) || 0) - (Number(a.sanctioned_amount_cr) || 0);
        }
        if (sortBy.value === 'NEWEST') {
            return (b.id || 0) - (a.id || 0);
        }
        return (b.id || 0) - (a.id || 0);
    });
});

// Filtered list
const filteredSchemes = computed(() => {
    const q = schemeSearch.value.trim().toLowerCase();
    return sortedSchemes.value.filter(s => {
        const matchesQuery = !q ||
            s.scheme_code?.toLowerCase().includes(q) ||
            s.scheme_name?.toLowerCase().includes(q) ||
            s.river_basin?.toLowerCase().includes(q) ||
            (s.district && s.district.toLowerCase().includes(q)) ||
            (s.state && s.state.toLowerCase().includes(q));
        if (!matchesQuery) return false;
        if (activeStatusFilter.value === 'COMPLETED')
            return s.physical_status === 'Completed' || Number(s.physical_progress_pct) >= 100;
        if (activeStatusFilter.value === 'ONGOING')
            return isSchemeOngoing(s);
        return true;
    });
});

// Pagination
const totalPages = computed(() => Math.max(1, Math.ceil(filteredSchemes.value.length / PER_PAGE)));
const paginatedSchemes = computed(() => {
    const start = (currentPage.value - 1) * PER_PAGE;
    return filteredSchemes.value.slice(start, start + PER_PAGE);
});
const pageNumbers = computed(() => Array.from({ length: totalPages.value }, (_, i) => i + 1));

// Reset page on filter change
watch([schemeSearch, activeStatusFilter, sortBy], () => {
    currentPage.value = 1;
    activeSchemeId.value = null;
});

const goToPage = (n) => {
    if (n >= 1 && n <= totalPages.value) {
        currentPage.value = n;
        activeSchemeId.value = null;
    }
};

const toggleScheme = (id) => {
    activeSchemeId.value = activeSchemeId.value === id ? null : id;
};

// ─── Date Helpers ───
const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    try {
        if (typeof dateStr === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateStr.trim())) {
            const [y, m, d] = dateStr.trim().split('-');
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const monthName = months[parseInt(m, 10) - 1] || m;
            return `${d.padStart(2, '0')} ${monthName} ${y}`;
        }
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    } catch (e) {
        return dateStr;
    }
};

const getSchemeDate = (scheme) => {
    if (scheme.fmbap_project?.state_govt_submission_date) {
        return {
            label: 'Submission Date',
            date: scheme.fmbap_project.state_govt_submission_date,
        };
    }
    if (scheme.created_at) {
        return {
            label: 'Sanction / Reg. Date',
            date: scheme.created_at,
        };
    }
    if (scheme.payment_requests?.[0]?.submitted_at) {
        return {
            label: 'Claim Date',
            date: scheme.payment_requests[0].submitted_at,
        };
    }
    return {
        label: 'Date',
        date: null,
    };
};

// ─── SVG Chart helpers ───────────────────────────────────────────────
const DONUT_R = 40;
const DONUT_CIRC = computed(() => 2 * Math.PI * DONUT_R);
const donutDash = computed(() => (completionPct.value / 100) * DONUT_CIRC.value);
const donutGap = computed(() => DONUT_CIRC.value - donutDash.value);

// Bar chart for fund release statuses
const fundStatusData = computed(() => {
    const all = props.schemes.flatMap(s => s.payment_requests || []);
    const statuses = ['APPROVED', 'SUBMITTED_TO_BB', 'FORWARDED_TO_MOJS', 'NEEDS_CORRECTION', 'DRAFT'];
    const labels = ['Approved', 'At BB', 'At MoJS', 'Correction', 'Draft'];
    const colors = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#9ca3af'];
    const counts = statuses.map(s => all.filter(r => r.status === s).length);
    const max = Math.max(...counts, 1);
    return statuses.map((s, i) => ({
        label: labels[i],
        count: counts[i],
        color: colors[i],
        pct: Math.round((counts[i] / max) * 100),
    }));
});

// Modal State: Excel Ingestion & Manual Entry
const isModalOpen = ref(false);
const modalMode = ref('excel'); // 'excel' | 'manual'
const fileInput = ref(null);
const excelFile = ref(null);
const isParsing = ref(false);
const parseError = ref('');
const parsedRows = ref([]);
const isImporting = ref(false);

const newSchemesCount = computed(() => parsedRows.value.filter(r => !r.is_duplicate).length);
const dupSchemesCount = computed(() => parsedRows.value.filter(r => r.is_duplicate).length);

const manualForm = useForm({
    scheme_code: '',
    scheme_name: '',
    division: '',
    district: '',
    plan_period: 'XI Plan',
    estimated_cost_lakh: '',
    sanctioned_amount_cr: '',
    state: user.value?.state || 'Assam',
    river_basin: 'Brahmaputra',
    physical_status: 'Ongoing',
    physical_progress_pct: 0,
});

watch(() => manualForm.estimated_cost_lakh, (val) => {
    if (val && !isNaN(val)) {
        manualForm.sanctioned_amount_cr = (parseFloat(val) / 100).toFixed(2);
    }
});

const onFileSelected = async (e) => {
    const file = e.target.files?.[0] || e.dataTransfer?.files?.[0];
    if (!file) return;
    excelFile.value = file;
    parseError.value = '';
    isParsing.value = true;
    parsedRows.value = [];

    const formData = new FormData();
    formData.append('excel_file', file);

    try {
        const res = await window.axios.post(route('schemes.parse-excel'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        if (res.data?.rows) {
            parsedRows.value = res.data.rows;
        }
    } catch (err) {
        parseError.value = err.response?.data?.error || err.message || 'Failed to parse Excel file.';
    } finally {
        isParsing.value = false;
    }
};

const commitImport = () => {
    if (!parsedRows.value.length) return;
    isImporting.value = true;
    router.post(route('schemes.import-excel'), { rows: parsedRows.value }, {
        onSuccess: () => {
            isModalOpen.value = false;
            parsedRows.value = [];
            excelFile.value = null;
            isImporting.value = false;
            schemeSearch.value = '';
            activeStatusFilter.value = 'ALL';
            currentPage.value = 1;
            activeSchemeId.value = null;
        },
        onError: () => {
            isImporting.value = false;
        },
    });
};

const submitManualScheme = () => {
    manualForm.post(route('schemes.store'), {
        onSuccess: () => {
            isModalOpen.value = false;
            manualForm.reset();
            schemeSearch.value = '';
            activeStatusFilter.value = 'ALL';
            currentPage.value = 1;
            activeSchemeId.value = null;
        },
    });
};
</script>

<template>
    <Head title="FMBAP Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-700 via-indigo-700 to-indigo-800 flex items-center justify-center shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h2 class="font-extrabold text-xl sm:text-2xl text-slate-900 tracking-tight leading-tight">
                                {{ isStateOfficial ? `${user?.state || 'State'} Water Resources Portal` : 'FMBAP Monitoring Dashboard' }}
                            </h2>
                            <span :class="['db-role-badge', roleBadgeClass]">{{ roleLabel }}</span>
                            <span v-if="isStateOfficial && user?.state" class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-900 border border-blue-200">
                                {{ user.state }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 font-medium mt-0.5">
                            Flood Management and Border Areas Programme &bull; Implementation &amp; Financial Tracking
                        </p>
                    </div>
                </div>

                <!-- Quick Action Buttons for Header -->
                <div class="flex items-center gap-2 flex-wrap">
                    <button v-if="canSubmitDpr" @click="isModalOpen = true"
                        class="inline-flex items-center gap-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 px-4 py-2.5 rounded-xl shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        + New Scheme (DPR)
                    </button>
                    <Link :href="route('schemes.index')"
                        class="inline-flex items-center gap-2 text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 px-4 py-2.5 rounded-xl shadow-sm transition-all">
                        Scheme Catalogue &rarr;
                    </Link>
                </div>
            </div>
        </template>

        <div class="db-page-wrap">

            <!-- ─── STATE OFFICIAL PRIORITY BANNER: If payment request needs correction ─── -->
            <div v-if="isStateOfficial && stateNeedsCorrectionRequests.length > 0"
                class="bg-amber-50 border-2 border-amber-400 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center text-xl shrink-0">
                        ⚠️
                    </div>
                    <div>
                        <h4 class="text-base font-extrabold text-amber-950">Action Required: Revisions Returned from Board</h4>
                        <p class="text-sm text-amber-900 mt-0.5">
                            You have <strong>{{ stateNeedsCorrectionRequests.length }}</strong> payment release request(s) requiring corrections or clarifications from Brahmaputra Board.
                        </p>
                    </div>
                </div>
                <Link :href="route('fund-release.index')"
                    class="shrink-0 inline-flex items-center gap-2 text-sm font-bold text-white bg-amber-600 hover:bg-amber-700 px-4 py-2.5 rounded-xl shadow-sm transition-all">
                    Review &amp; Update Now &rarr;
                </Link>
            </div>

            <!-- ─── HERO ALERT: Pending Actions for BB / MoJS ─── -->
            <div v-if="(isBB || isMoJS) && moduleCounts.monitoring_requests > 0"
                class="db-alert-banner">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">⚡</span>
                    <div>
                        <p class="font-bold text-amber-900 text-base">Action Required</p>
                        <p class="text-amber-800 text-sm">
                            <strong>{{ moduleCounts.monitoring_requests }}</strong> fund release request(s) are awaiting your review and monitoring decision.
                        </p>
                    </div>
                </div>
                <Link :href="route('fund-release.index')" class="db-alert-cta">Review Now →</Link>
            </div>

            <!-- ─── KPI STRIP (ONGOING SCHEMES FIRST) ─── -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 sm:gap-4">
                
                <!-- 1. ONGOING SCHEMES (FIRST & HIGHLIGHTED) -->
                <button @click="activeStatusFilter = activeStatusFilter === 'ONGOING' ? 'ALL' : 'ONGOING'"
                    type="button"
                    class="stat-kpi-card text-left transition-all group"
                    :class="activeStatusFilter === 'ONGOING' ? 'ring-2 ring-amber-500 bg-amber-50/80 border-amber-300 shadow-md' : 'bg-white border-slate-200 hover:border-amber-300 hover:shadow-sm'">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-amber-800 flex items-center gap-1.5">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                            </span>
                            Ongoing Schemes
                        </span>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-amber-100 text-amber-900 border border-amber-200">
                            Active
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl sm:text-4xl font-black text-amber-900 leading-none">{{ ongoingCount }}</span>
                        <span class="text-xs sm:text-sm font-medium text-amber-800">in execution</span>
                    </div>
                    <p class="text-xs text-amber-800/80 mt-1.5 font-medium">Click to filter ongoing</p>
                </button>

                <!-- 2. TOTAL SCHEMES -->
                <button @click="activeStatusFilter = 'ALL'"
                    type="button"
                    class="stat-kpi-card text-left transition-all group"
                    :class="activeStatusFilter === 'ALL' ? 'ring-2 ring-blue-500 bg-blue-50/70 border-blue-300 shadow-md' : 'bg-white border-slate-200 hover:border-blue-300 hover:shadow-sm'">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-blue-800">Total Schemes</span>
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl sm:text-4xl font-black text-blue-900 leading-none">{{ summary?.total_projects || schemes.length }}</span>
                        <span class="text-xs sm:text-sm font-medium text-slate-500">catalogue</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1.5 font-medium">All recorded works</p>
                </button>

                <!-- 3. COMPLETED SCHEMES -->
                <button @click="activeStatusFilter = activeStatusFilter === 'COMPLETED' ? 'ALL' : 'COMPLETED'"
                    type="button"
                    class="stat-kpi-card text-left transition-all group"
                    :class="activeStatusFilter === 'COMPLETED' ? 'ring-2 ring-emerald-500 bg-emerald-50/80 border-emerald-300 shadow-md' : 'bg-white border-slate-200 hover:border-emerald-300 hover:shadow-sm'">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-emerald-800">Completed</span>
                        <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl sm:text-4xl font-black text-emerald-800 leading-none">{{ completedCount }}</span>
                        <span class="text-xs sm:text-sm font-medium text-emerald-800">delivered</span>
                    </div>
                    <p class="text-xs text-emerald-800/80 mt-1.5 font-medium">{{ completionPct }}% total completion</p>
                </button>

                <!-- 4. TOTAL SANCTIONED OUTLAY -->
                <div class="stat-kpi-card bg-white border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-violet-800">Sanctioned Outlay</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded bg-violet-100 text-violet-800">Cr</span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-2xl sm:text-3xl font-black text-violet-900 leading-none">₹{{ summary?.total_sanctioned_cr || '0.00' }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1.5 font-medium">Central 90% &bull; State 10%</p>
                </div>

                <!-- 5. AVERAGE PROGRESS -->
                <div class="stat-kpi-card bg-white border-slate-200 col-span-2 sm:col-span-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-orange-800">Avg. Progress</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded bg-orange-100 text-orange-800">%</span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-3xl sm:text-4xl font-black text-orange-900 leading-none">{{ summary?.avg_physical_progress || 0 }}%</span>
                    </div>
                    <div class="w-full bg-orange-100 rounded-full h-2 mt-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-orange-500 transition-all duration-700" :style="{ width: `${summary?.avg_physical_progress || 0}%` }"></div>
                    </div>
                </div>

            </div>

            <!-- ─── QUICK ACTION MODULES (TAILORED FOR STATE & CENTRAL) ─── -->
            <div>
                <div class="flex items-center justify-between mb-3.5">
                    <h3 class="text-sm sm:text-base font-extrabold uppercase tracking-wider text-slate-700">
                        {{ isStateOfficial ? 'State Official Action Hub' : 'Programme Operations Hub' }}
                    </h3>
                    <span v-if="isStateOfficial" class="text-xs text-blue-700 font-bold bg-blue-50 border border-blue-200 px-3 py-1 rounded-lg">
                        State: {{ user?.state || 'Assam' }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    
                    <!-- 1. Fund Release -->
                    <Link :href="route('fund-release.index')" class="action-card module-blue group">
                        <div class="action-icon bg-blue-100 text-blue-700 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-extrabold text-base text-slate-900 group-hover:text-blue-700 transition-colors">Fund Release</h4>
                            <p class="text-xs text-slate-500 mt-0.5">Submit &amp; track payment instalments</p>
                        </div>
                        <div class="text-xs font-black px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                            {{ moduleCounts.fund_release }} Claims
                        </div>
                    </Link>

                    <!-- 2. If State Official: Direct New Scheme DPR Trigger; If BB/MoJS: Monitoring Requests -->
                    <div v-if="isStateOfficial" @click="isModalOpen = true" class="action-card module-amber group cursor-pointer">
                        <div class="action-icon bg-amber-100 text-amber-700 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-extrabold text-base text-slate-900 group-hover:text-amber-700 transition-colors">+ Submit Scheme (DPR)</h4>
                            <p class="text-xs text-slate-500 mt-0.5">Register new scheme or upload Excel</p>
                        </div>
                        <div class="text-xs font-black px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200">
                            DPR Entry
                        </div>
                    </div>

                    <Link v-else :href="route('monitoring-requests.index')" class="action-card module-indigo group">
                        <div class="action-icon bg-indigo-100 text-indigo-700 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-extrabold text-base text-slate-900 group-hover:text-indigo-700 transition-colors">Monitoring Requests</h4>
                            <p class="text-xs text-slate-500 mt-0.5">Field inspections &amp; BB reviews</p>
                        </div>
                        <div class="text-xs font-black px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                            {{ moduleCounts.monitoring_requests }} Pending
                        </div>
                    </Link>

                    <!-- 3. Official Template & Master Catalogue -->
                    <a :href="route('schemes.download-template')" class="action-card module-violet group">
                        <div class="action-icon bg-violet-100 text-violet-700 group-hover:bg-violet-600 group-hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-extrabold text-base text-slate-900 group-hover:text-violet-700 transition-colors">Official Template</h4>
                            <p class="text-xs text-slate-500 mt-0.5">Download FMBAP master spreadsheet</p>
                        </div>
                        <div class="text-xs font-black px-2.5 py-1 rounded-full bg-violet-50 text-violet-700 border border-violet-200">
                            .xlsx
                        </div>
                    </a>

                </div>
            </div>

            <!-- ─── ANALYTICS ROW: Donut + Bar ─── -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Donut: Completion ring -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6 flex items-center gap-6">
                    <div class="relative shrink-0">
                        <svg width="110" height="110" viewBox="0 0 110 110">
                            <circle cx="55" cy="55" r="40" fill="none" stroke="#f1f5f9" stroke-width="14"/>
                            <circle cx="55" cy="55" r="40" fill="none" stroke="#f59e0b" stroke-width="14"
                                stroke-dasharray="251.3 0"
                                stroke-linecap="round"
                                transform="rotate(-90 55 55)"/>
                            <circle cx="55" cy="55" r="40" fill="none" stroke="#10b981" stroke-width="14"
                                :stroke-dasharray="`${donutDash} ${donutGap}`"
                                stroke-linecap="round"
                                transform="rotate(-90 55 55)"/>
                            <text x="55" y="50" text-anchor="middle" class="font-black" fill="#0f172a" font-size="19" font-weight="900">{{ completionPct }}%</text>
                            <text x="55" y="66" text-anchor="middle" fill="#64748b" font-size="10" font-weight="700">Done</text>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-extrabold text-slate-900 text-base mb-3">Scheme Implementation Status</h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm font-semibold">
                                <span class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span>
                                    Ongoing Projects (Active)
                                </span>
                                <span class="font-black text-amber-800">{{ ongoingCount }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm font-semibold">
                                <span class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
                                    Completed Projects
                                </span>
                                <span class="font-black text-emerald-700">{{ completedCount }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm border-t border-slate-200 pt-2 mt-2 font-bold">
                                <span class="flex items-center gap-2 text-slate-600">
                                    <span class="w-3 h-3 rounded-full bg-slate-300 inline-block"></span>
                                    Total Master Registry
                                </span>
                                <span class="font-black text-slate-900">{{ schemes.length }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bar: Fund Release Status Distribution -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6">
                    <h3 class="font-extrabold text-slate-900 text-base mb-4">Fund Release Pipeline Status</h3>
                    <div class="space-y-3">
                        <div v-for="item in fundStatusData" :key="item.label" class="flex items-center gap-3">
                            <span class="text-xs font-bold text-slate-600 w-24 shrink-0 text-right">{{ item.label }}</span>
                            <div class="flex-1 bg-slate-100 rounded-full h-5 overflow-hidden relative">
                                <div class="h-5 rounded-full transition-all duration-700 flex items-center justify-end pr-2 font-bold text-[10px] text-white"
                                    :style="{ width: `${item.pct || 0}%`, backgroundColor: item.color }">
                                </div>
                            </div>
                            <span class="text-xs font-black w-6 text-slate-800 shrink-0 text-right">{{ item.count }}</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 mt-4 font-medium">Instalment release status across all recorded projects</p>
                </div>
            </div>

            <!-- ─── SCHEME CATALOGUE (ONGOING FIRST WITH LARGER TEXT) ─── -->
            <div class="cat-card">
                
                <!-- Catalogue Toolbar (Ongoing Filter First) -->
                <div class="cat-toolbar">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        
                        <!-- Search input (Large & Readable) -->
                        <div class="relative flex-1 max-w-lg">
                            <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
                                </svg>
                            </div>
                            <input v-model="schemeSearch" type="text"
                                placeholder="Search by scheme code, district, basin or name..."
                                class="w-full h-11 pl-11 pr-10 text-sm sm:text-base text-slate-800 placeholder-slate-400 bg-white border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm" />
                            <button v-if="schemeSearch" @click="schemeSearch = ''"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 font-bold p-1">✕</button>
                        </div>

                        <!-- Filter Tabs (Ongoing First) & Sort Selector -->
                        <div class="flex flex-wrap items-center gap-2">
                            
                            <!-- 1. Ongoing Pill (First) -->
                            <button @click="activeStatusFilter = 'ONGOING'"
                                :class="activeStatusFilter === 'ONGOING' ? 'bg-amber-600 text-white shadow-sm ring-2 ring-amber-300' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                class="inline-flex items-center gap-2 text-xs sm:text-sm font-bold px-3.5 py-2 rounded-xl transition-all">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-200 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2" :class="activeStatusFilter === 'ONGOING' ? 'bg-white' : 'bg-amber-600'"></span>
                                </span>
                                Ongoing Schemes ({{ ongoingCount }})
                            </button>

                            <!-- 2. All Pill -->
                            <button @click="activeStatusFilter = 'ALL'"
                                :class="activeStatusFilter === 'ALL' ? 'bg-slate-900 text-white shadow-sm ring-2 ring-slate-400' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold px-3.5 py-2 rounded-xl transition-all">
                                All Schemes ({{ schemes.length }})
                            </button>

                            <!-- 3. Completed Pill -->
                            <button @click="activeStatusFilter = 'COMPLETED'"
                                :class="activeStatusFilter === 'COMPLETED' ? 'bg-emerald-600 text-white shadow-sm ring-2 ring-emerald-300' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold px-3.5 py-2 rounded-xl transition-all">
                                ✓ Done ({{ completedCount }})
                            </button>

                            <!-- Sort -->
                            <div class="flex items-center gap-1 ml-auto">
                                <select v-model="sortBy"
                                    class="h-9 text-xs sm:text-sm font-semibold text-slate-800 bg-white border border-slate-300 rounded-xl px-2.5 py-1 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    <option value="ONGOING_FIRST">⚡ Ongoing First</option>
                                    <option value="PROGRESS_DESC">Highest Progress %</option>
                                    <option value="SANCTIONED_DESC">Sanctioned Outlay</option>
                                    <option value="NEWEST">Newest First</option>
                                </select>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Empty states -->
                <div v-if="schemes.length === 0" class="py-16 text-center">
                    <div class="text-5xl mb-3">📂</div>
                    <p class="text-slate-600 font-bold text-base">No schemes in catalogue yet.</p>
                    <p v-if="canSubmitDpr" class="text-sm text-slate-400 mt-1">Click the <strong>New Scheme</strong> button to submit a DPR.</p>
                </div>
                <div v-else-if="filteredSchemes.length === 0" class="py-12 text-center">
                    <div class="text-4xl mb-2">🔍</div>
                    <p class="text-slate-600 font-bold text-base">No schemes match your criteria.</p>
                    <button @click="activeStatusFilter = 'ALL'; schemeSearch = ''; sortBy = 'ONGOING_FIRST'"
                        class="mt-2 text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 px-4 py-2 rounded-lg transition">
                        Clear Filters
                    </button>
                </div>

                <!-- Scheme Rows with Ongoing Priority and Increased Text Size -->
                <div v-else class="divide-y divide-slate-200">
                    <div v-for="scheme in paginatedSchemes" :key="scheme.id"
                        class="transition-colors"
                        :class="[
                            activeSchemeId === scheme.id ? 'bg-blue-50/40' : '',
                            isSchemeOngoing(scheme) ? 'border-l-4 border-l-amber-500 bg-amber-50/10 hover:bg-amber-50/25' : 'border-l-4 border-l-emerald-500 hover:bg-slate-50/80'
                        ]">

                        <div @click="toggleScheme(scheme.id)"
                            role="button"
                            tabindex="0"
                            class="w-full px-5 sm:px-6 py-4 sm:py-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 cursor-pointer text-left select-none">

                            <!-- Left: Code + Date + Title + Chips -->
                            <div class="min-w-0 flex-1 space-y-2">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <span class="font-mono font-black text-blue-900 text-xs sm:text-sm bg-blue-50 border border-blue-200 px-3 py-1 rounded-md tracking-wider shadow-sm">
                                        {{ scheme.scheme_code }}
                                    </span>

                                    <span v-if="scheme.plan_period" class="text-xs font-bold px-2.5 py-1 rounded-md bg-slate-100 text-slate-800 border border-slate-200">
                                        {{ scheme.plan_period }}
                                    </span>

                                    <!-- Status Pill -->
                                    <div v-if="isSchemeOngoing(scheme)" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs sm:text-sm font-bold bg-amber-100 text-amber-900 border border-amber-300 shadow-sm">
                                        <span class="relative flex h-2.5 w-2.5">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-500 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-600"></span>
                                        </span>
                                        Ongoing &bull; {{ Number(scheme.physical_progress_pct || 0) }}% Completed
                                    </div>
                                    <div v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs sm:text-sm font-bold bg-emerald-100 text-emerald-900 border border-emerald-300 shadow-sm">
                                        <svg class="w-4 h-4 text-emerald-700" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Completed &bull; 100%
                                    </div>
                                </div>

                                <!-- Large Scheme Title (No Truncate) -->
                                <h3 class="font-extrabold text-base sm:text-lg text-slate-900 leading-snug break-words">
                                    {{ scheme.scheme_name }}
                                </h3>

                                <!-- MANDATORY OFFICIAL DATES STRIP IN ACCORDION TITLE -->
                                <div class="flex items-center flex-wrap gap-2.5 pt-0.5">
                                    <!-- Official State Submission Date -->
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs sm:text-sm font-bold bg-blue-50 text-blue-900 border border-blue-200 shadow-sm"
                                        title="Official State Government Submission Date">
                                        <svg class="w-4 h-4 text-blue-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="text-blue-700 font-semibold">State Submission:</span>
                                        <strong class="font-extrabold font-mono text-blue-950">{{ formatDate(scheme.fmbap_project?.state_govt_submission_date || scheme.created_at) }}</strong>
                                    </span>

                                    <!-- Sanction / Registration Date -->
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs sm:text-sm font-bold bg-emerald-50 text-emerald-900 border border-emerald-200 shadow-sm"
                                        title="Scheme Sanction / Entry Date in Portal">
                                        <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="text-emerald-700 font-semibold">Sanction / Entry:</span>
                                        <strong class="font-extrabold font-mono text-emerald-950">{{ formatDate(scheme.created_at) }}</strong>
                                    </span>

                                    <!-- Last System Update -->
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs sm:text-sm font-semibold bg-slate-100 text-slate-700 border border-slate-200"
                                        title="Last Portal System Update">
                                        <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="text-slate-600">Updated:</span>
                                        <strong class="font-mono text-slate-900">{{ formatDate(scheme.updated_at || scheme.created_at) }}</strong>
                                    </span>
                                </div>

                                <!-- Location, River Chips -->
                                <div class="flex items-center flex-wrap gap-2 text-xs sm:text-sm text-slate-600 font-medium">
                                    <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-800 font-semibold px-2.5 py-0.5 rounded-md border border-slate-200">
                                        📍 {{ scheme.district || 'District N/A' }}, {{ scheme.state }}
                                    </span>
                                    <span v-if="scheme.river_basin" class="inline-flex items-center gap-1 bg-teal-50 text-teal-800 font-semibold px-2.5 py-0.5 rounded-md border border-teal-200">
                                        Basin: {{ scheme.river_basin }}
                                    </span>
                                    <span v-if="scheme.division" class="inline-flex items-center gap-1 bg-purple-50 text-purple-800 font-semibold px-2.5 py-0.5 rounded-md border border-purple-200">
                                        Division: {{ scheme.division }}
                                    </span>
                                </div>
                            </div>

                            <!-- Right: Date Box + Outlay + Releases & Reports + Details -->
                            <div class="flex items-center gap-3 flex-wrap lg:flex-nowrap justify-between lg:justify-end shrink-0 pt-2 lg:pt-0 border-t lg:border-t-0 border-slate-100">
                                
                                <!-- Mandatory Date Box in right stats bar -->
                                <div class="px-3.5 py-2 rounded-xl bg-blue-50/80 border border-blue-200 text-blue-900 flex flex-col items-end shadow-sm shrink-0">
                                    <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-blue-700 flex items-center gap-1">
                                        <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Submission Date
                                    </span>
                                    <span class="text-xs sm:text-sm font-black text-blue-950 leading-tight font-mono whitespace-nowrap">
                                        {{ formatDate(scheme.fmbap_project?.state_govt_submission_date || scheme.created_at) }}
                                    </span>
                                </div>

                                <!-- Sanctioned Outlay -->
                                <div class="px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-200 text-slate-800 flex flex-col items-end">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Sanctioned</span>
                                    <span class="text-sm sm:text-base font-black text-slate-900 leading-tight">
                                        ₹{{ scheme.sanctioned_amount_cr }} Cr
                                    </span>
                                </div>

                                <!-- Releases Badge -->
                                <div class="px-3 py-2 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 flex flex-col items-center">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-blue-600">Releases</span>
                                    <span class="text-sm sm:text-base font-black leading-tight">
                                        {{ scheme.payment_requests?.length || 0 }}
                                    </span>
                                </div>

                                <!-- Reports Badge -->
                                <div class="px-3 py-2 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex flex-col items-center">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">Reports</span>
                                    <span class="text-sm sm:text-base font-black leading-tight">
                                        {{ scheme.progress_reports?.length || 0 }}
                                    </span>
                                </div>

                                <!-- Details CTA with Arrow -->
                                <div class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-slate-300 text-slate-700 font-bold text-xs sm:text-sm hover:bg-slate-50 transition-all shadow-sm">
                                    <span>{{ activeSchemeId === scheme.id ? 'Hide' : 'Details' }}</span>
                                    <svg class="w-4 h-4 text-slate-600 transition-transform duration-200"
                                        :class="activeSchemeId === scheme.id ? 'rotate-180' : ''"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>

                            </div>
                        </div>

                        <!-- Expanded Detail Panel with High-Visibility Data -->
                        <div v-show="activeSchemeId === scheme.id" class="bg-slate-50 border-t border-slate-200 px-5 sm:px-7 py-6">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                                
                                <!-- Left Column: Physical Progress & Financial Sharing (7 cols) -->
                                <div class="lg:col-span-7 space-y-4">
                                    
                                    <!-- Physical Progress Banner -->
                                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="text-base font-extrabold text-slate-900">Physical Implementation Progress</h4>
                                            <span :class="isSchemeOngoing(scheme)
                                                ? 'bg-amber-100 text-amber-900 border-amber-300'
                                                : 'bg-emerald-100 text-emerald-900 border-emerald-300'"
                                                class="text-xs sm:text-sm font-black px-3 py-1 rounded-full border shadow-sm">
                                                {{ isSchemeOngoing(scheme) ? `Ongoing — ${Number(scheme.physical_progress_pct || 0)}% Done` : '✓ Completed — 100%' }}
                                            </span>
                                        </div>
                                        <div class="w-full bg-slate-200 rounded-full h-4 overflow-hidden mt-2">
                                            <div class="h-4 rounded-full transition-all duration-700 font-bold text-[10px] text-white flex items-center justify-end pr-2"
                                                :class="isSchemeOngoing(scheme) ? 'bg-gradient-to-r from-amber-500 to-amber-600' : 'bg-gradient-to-r from-emerald-500 to-emerald-600'"
                                                :style="{ width: `${Math.min(100, Math.max(8, Number(scheme.physical_progress_pct || 0)))}%` }">
                                                {{ Number(scheme.physical_progress_pct || 0) }}%
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Official Scheme Metadata Card -->
                                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
                                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                            <h4 class="text-base font-extrabold text-slate-900">Official Scheme Metadata</h4>
                                            <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-blue-50 text-blue-800 border border-blue-200">
                                                Plan: {{ scheme.plan_period || 'XI / XII Plan' }}
                                            </span>
                                        </div>

                                        <dl class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
                                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Division</dt>
                                                <dd class="font-extrabold text-slate-900 text-sm sm:text-base mt-0.5">{{ scheme.division || scheme.district || 'WRD Division' }}</dd>
                                            </div>
                                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">River Basin</dt>
                                                <dd class="font-extrabold text-slate-900 text-sm sm:text-base mt-0.5">{{ scheme.river_basin || 'Brahmaputra' }}</dd>
                                            </div>
                                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">State</dt>
                                                <dd class="font-extrabold text-slate-900 text-sm sm:text-base mt-0.5">{{ scheme.state }}</dd>
                                            </div>
                                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Sanction / Entry Date</dt>
                                                <dd class="font-extrabold text-slate-900 text-sm sm:text-base mt-0.5 font-mono">{{ formatDate(scheme.created_at) }}</dd>
                                            </div>
                                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Last System Update</dt>
                                                <dd class="font-extrabold text-slate-900 text-sm sm:text-base mt-0.5 font-mono">{{ formatDate(scheme.updated_at || scheme.created_at) }}</dd>
                                            </div>
                                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Official Submission</dt>
                                                <dd class="font-extrabold text-slate-900 text-sm sm:text-base mt-0.5 font-mono">{{ formatDate(scheme.fmbap_project?.state_govt_submission_date || scheme.created_at) }}</dd>
                                            </div>
                                        </dl>

                                        <!-- Outlay & Financial Sharing Box -->
                                        <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-200 space-y-3">
                                            <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-2">
                                                <div>
                                                    <span class="text-xs font-extrabold uppercase tracking-wider text-blue-900">Estimated Outlay &amp; Sanction</span>
                                                    <div class="flex items-baseline gap-2 mt-1">
                                                        <span class="text-2xl font-black text-blue-950">
                                                            ₹{{ scheme.estimated_cost_lakh ? Number(scheme.estimated_cost_lakh).toFixed(2) : ((scheme.sanctioned_amount_cr || 0) * 100).toFixed(2) }} Lakh
                                                        </span>
                                                        <span class="text-sm font-bold text-blue-800 font-mono">
                                                            (₹{{ scheme.sanctioned_amount_cr }} Cr)
                                                        </span>
                                                    </div>
                                                </div>
                                                <span class="text-xs font-bold px-2.5 py-1 rounded bg-white text-blue-800 border border-blue-200 self-start">
                                                    Central {{ scheme.central_share_pct || 90 }}% &bull; State {{ scheme.state_share_pct || 10 }}%
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Government Fund Position Table -->
                                        <div v-if="scheme.fund_utilised_total_lakh || scheme.fund_req_total_lakh" class="pt-2">
                                            <h5 class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Government Fund Position (Rs. in Lakh)</h5>
                                            <div class="overflow-x-auto rounded-xl border border-slate-200">
                                                <table class="w-full text-sm text-left">
                                                    <thead class="bg-slate-100 text-xs text-slate-600 font-bold uppercase">
                                                        <tr>
                                                            <th class="px-3 py-2">Component</th>
                                                            <th class="px-3 py-2 text-right font-bold text-blue-800">CS (Central)</th>
                                                            <th class="px-3 py-2 text-right font-bold text-slate-700">SS (State)</th>
                                                            <th class="px-3 py-2 text-right font-black text-slate-900">Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-200 font-mono text-xs sm:text-sm">
                                                        <tr class="hover:bg-blue-50/30">
                                                            <td class="px-3 py-2 font-sans text-slate-800 font-bold">Fund Utilised</td>
                                                            <td class="px-3 py-2 text-right font-bold text-blue-700">₹{{ scheme.fund_utilised_cs_lakh || '0.00' }}</td>
                                                            <td class="px-3 py-2 text-right font-semibold text-slate-700">₹{{ scheme.fund_utilised_ss_lakh || '0.00' }}</td>
                                                            <td class="px-3 py-2 text-right font-black text-slate-900">₹{{ scheme.fund_utilised_total_lakh || '0.00' }}</td>
                                                        </tr>
                                                        <tr class="hover:bg-amber-50/30 bg-amber-50/20">
                                                            <td class="px-3 py-2 font-sans text-amber-900 font-bold">Fund Requirement</td>
                                                            <td class="px-3 py-2 text-right font-bold text-amber-800">₹{{ scheme.fund_req_cs_lakh || '0.00' }}</td>
                                                            <td class="px-3 py-2 text-right font-semibold text-slate-700">₹{{ scheme.fund_req_ss_lakh || '0.00' }}</td>
                                                            <td class="px-3 py-2 text-right font-black text-amber-950">₹{{ scheme.fund_req_total_lakh || '0.00' }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <!-- Right Column: Payment Releases & Reports (5 cols) -->
                                <div class="lg:col-span-5 space-y-4">
                                    
                                    <!-- Fund Releases Card -->
                                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-3">
                                        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                                            <h4 class="text-base font-extrabold text-slate-900">Fund Releases</h4>
                                            <Link :href="route('fund-release.index')" class="text-xs sm:text-sm text-blue-600 hover:text-blue-800 font-bold underline">
                                                All Releases &rarr;
                                            </Link>
                                        </div>

                                        <div v-if="!scheme.payment_requests?.length" class="text-sm text-slate-500 italic bg-slate-50 border border-dashed border-slate-200 rounded-xl p-4 text-center">
                                            No payment release requests recorded yet.
                                        </div>

                                        <div v-for="req in scheme.payment_requests" :key="req.id" class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2">
                                            <div class="flex items-center justify-between gap-2">
                                                <div>
                                                    <span class="text-xs text-slate-500 font-semibold">Instalment #{{ req.instalment_number || '1' }}</span>
                                                    <p class="font-black text-base text-slate-900">₹{{ req.requested_amount_cr }} Cr</p>
                                                </div>
                                                <div class="flex items-center gap-1.5 flex-wrap justify-end">
                                                    <span class="req-status-badge" :class="{
                                                        'status-approved': req.status === 'APPROVED',
                                                        'status-correction': req.status === 'NEEDS_CORRECTION',
                                                        'status-submitted': req.status === 'SUBMITTED_TO_BB',
                                                        'status-forwarded': req.status === 'FORWARDED_TO_MOJS',
                                                        'status-draft': req.status === 'DRAFT',
                                                        'status-rejected': req.status === 'REJECTED',
                                                    }">{{ req.status.replace(/_/g, ' ') }}</span>
                                                    <Link :href="route('fund-release.show', req.id)" class="text-xs sm:text-sm text-blue-600 hover:text-blue-800 font-bold underline px-1">
                                                        View Dossier &rarr;
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ─── PAGINATION ─── -->
                <div v-if="totalPages > 1" class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-slate-600 font-medium">
                        Showing <strong class="text-slate-900 font-bold">{{ (currentPage - 1) * PER_PAGE + 1 }}–{{ Math.min(currentPage * PER_PAGE, filteredSchemes.length) }}</strong>
                        of <strong class="text-slate-900 font-bold">{{ filteredSchemes.length }}</strong> schemes
                    </p>
                    <div class="flex items-center gap-1.5">
                        <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                            class="pg-btn" :class="currentPage === 1 ? 'opacity-40 cursor-not-allowed' : ''">
                            &larr; Prev
                        </button>
                        <button v-for="n in pageNumbers" :key="n" @click="goToPage(n)"
                            :class="n === currentPage ? 'pg-active' : 'pg-btn'"
                            class="pg-num">
                            {{ n }}
                        </button>
                        <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages"
                            class="pg-btn" :class="currentPage === totalPages ? 'opacity-40 cursor-not-allowed' : ''">
                            Next &rarr;
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- ─── MODAL: EXCEL UPLOAD & MANUAL ENTRY ─── -->
        <Transition name="modal-fade">
            <div v-if="isModalOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50 overflow-y-auto"
                @click.self="isModalOpen = false">
                <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-6 space-y-5 animate-modal-in my-8 max-h-[90vh] flex flex-col">
                    
                    <!-- Modal Header -->
                    <div class="flex justify-between items-start shrink-0">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h3 class="text-xl font-extrabold text-slate-900">Register / Import Schemes</h3>
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800">
                                    FMBAP DPR Registry
                                </span>
                            </div>
                            <p class="text-sm text-slate-500 mt-1">
                                Add a single scheme manually or upload the official FMBAP Excel DPR master sheet
                            </p>
                        </div>
                        <button @click="isModalOpen = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 hover:text-slate-800 font-bold transition text-lg leading-none">&times;</button>
                    </div>

                    <!-- Mode Tabs -->
                    <div class="flex border-b border-slate-200 shrink-0">
                        <button
                            type="button"
                            @click="modalMode = 'excel'"
                            :class="modalMode === 'excel' ? 'border-blue-600 text-blue-700 font-extrabold' : 'border-transparent text-slate-500 hover:text-slate-800'"
                            class="py-2.5 px-4 text-sm border-b-2 flex items-center gap-2 transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Upload Official Excel (.xlsx)
                        </button>
                        <button
                            type="button"
                            @click="modalMode = 'manual'"
                            :class="modalMode === 'manual' ? 'border-blue-600 text-blue-700 font-extrabold' : 'border-transparent text-slate-500 hover:text-slate-800'"
                            class="py-2.5 px-4 text-sm border-b-2 flex items-center gap-2 transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Manual Entry
                        </button>
                    </div>

                    <!-- ─── TAB 1: EXCEL UPLOAD ─── -->
                    <div v-if="modalMode === 'excel'" class="space-y-4 overflow-y-auto flex-1 pr-1">
                        <div class="bg-blue-50/80 border border-blue-200 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="text-sm text-blue-950">
                                <p class="font-bold text-blue-900">Official Spreadsheet Format Template</p>
                                <p class="text-blue-700 text-xs mt-0.5">
                                    Preconfigured for XI Plan, XII Plan &amp; Beyond XII Plan (Rs. in Lakh)
                                </p>
                            </div>
                            <a
                                :href="route('schemes.download-template')"
                                class="inline-flex items-center gap-1.5 bg-white hover:bg-blue-100 text-blue-800 border border-blue-300 font-bold text-xs px-3 py-2 rounded-lg shadow-sm transition shrink-0 self-start sm:self-auto"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download Template (.xlsx)
                            </a>
                        </div>

                        <!-- Dropzone -->
                        <div
                            @click="$refs.fileInput.click()"
                            class="border-2 border-dashed border-slate-300 hover:border-blue-500 hover:bg-blue-50/30 rounded-2xl p-6 text-center cursor-pointer transition flex flex-col items-center justify-center gap-2.5 group"
                        >
                            <input
                                ref="fileInput"
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                @change="onFileSelected"
                                class="hidden"
                            />
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 group-hover:bg-blue-100 text-slate-500 group-hover:text-blue-600 flex items-center justify-center transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-700">
                                    <span class="text-blue-600 hover:underline">Click to browse</span> or drag and drop Excel master sheet
                                </p>
                                <p class="text-xs text-slate-400 mt-0.5">Supports .xlsx, .xls, or .csv up to 20MB</p>
                            </div>
                            <span v-if="excelFile" class="inline-flex items-center gap-1 font-mono text-xs font-bold bg-blue-100 text-blue-800 px-3 py-1 rounded-md mt-1">
                                📄 {{ excelFile.name }}
                            </span>
                        </div>

                        <!-- Parsing Indicator -->
                        <div v-if="isParsing" class="py-4 text-center text-sm text-slate-600 flex items-center justify-center gap-2">
                            <svg class="animate-spin w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Parsing official spreadsheet columns &amp; rows...
                        </div>

                        <!-- Error Alert -->
                        <div v-if="parseError" class="p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 flex items-center gap-2">
                            <span>⚠️</span>
                            <span>{{ parseError }}</span>
                        </div>

                        <!-- Parsed Preview -->
                        <div v-if="parsedRows.length > 0" class="space-y-2">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-slate-50 p-3 rounded-xl border border-slate-200">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-bold text-emerald-700 flex items-center gap-1.5 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        {{ newSchemesCount }} New Scheme(s) detected
                                    </span>
                                    <span v-if="dupSchemesCount > 0" class="text-xs font-bold text-amber-800 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-lg">
                                        🛡️ {{ dupSchemesCount }} Existing schemes
                                    </span>
                                </div>
                                <span class="text-xs text-slate-400">Previewing first 5 entries</span>
                            </div>

                            <div class="border border-slate-200 rounded-xl overflow-hidden overflow-x-auto max-h-48">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 text-xs text-slate-600 uppercase font-bold">
                                        <tr>
                                            <th class="px-3 py-2">Code</th>
                                            <th class="px-3 py-2">Division</th>
                                            <th class="px-3 py-2">Scheme Name</th>
                                            <th class="px-3 py-2 text-right">Estimated (Lakh)</th>
                                            <th class="px-3 py-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-700">
                                        <tr v-for="(row, idx) in parsedRows.slice(0, 5)" :key="idx" :class="row.is_duplicate ? 'bg-amber-50/40' : 'hover:bg-slate-50/50'">
                                            <td class="px-3 py-1.5 font-mono font-bold text-blue-700">{{ row.scheme_code }}</td>
                                            <td class="px-3 py-1.5">{{ row.division || '—' }}</td>
                                            <td class="px-3 py-1.5 truncate max-w-xs" :title="row.scheme_name">{{ row.scheme_name }}</td>
                                            <td class="px-3 py-1.5 font-mono text-right font-bold">₹{{ row.estimated_cost_lakh }}</td>
                                            <td class="px-3 py-1.5 text-xs">
                                                <span v-if="row.is_duplicate" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                                    🛡️ {{ row.duplicate_reason || 'Already in Portal' }}
                                                </span>
                                                <span v-else class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                    ✓ New
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <button
                                type="button"
                                @click="isModalOpen = false"
                                class="px-4 py-2 text-xs font-semibold text-slate-600 border border-slate-300 rounded-xl hover:bg-slate-50 transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                @click="commitImport"
                                :disabled="parsedRows.length === 0 || isImporting"
                                class="px-5 py-2.5 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 disabled:opacity-50 transition flex items-center gap-2 shadow-sm"
                            >
                                <span v-if="isImporting" class="animate-spin w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full"></span>
                                {{ isImporting ? 'Importing...' : (newSchemesCount > 0 ? `Import ${newSchemesCount} New Scheme(s) →` : 'Update Existing Schemes →') }}
                            </button>
                        </div>
                    </div>

                    <!-- ─── TAB 2: MANUAL ENTRY ─── -->
                    <form v-else @submit.prevent="submitManualScheme" class="space-y-4 overflow-y-auto flex-1 pr-1">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Scheme Code No. *</label>
                                <input
                                    v-model="manualForm.scheme_code"
                                    type="text"
                                    placeholder="e.g. AS-21"
                                    class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Name of Division *</label>
                                <input
                                    v-model="manualForm.division"
                                    type="text"
                                    placeholder="e.g. North Lakhimpur, Silchar, Nagaon"
                                    class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Name of Scheme &amp; Technical Scope *</label>
                            <textarea
                                v-model="manualForm.scheme_name"
                                rows="2"
                                placeholder="e.g. Anti-erosion work to protect Rohmoria area..."
                                class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required
                            ></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Plan Period</label>
                                <select
                                    v-model="manualForm.plan_period"
                                    class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                >
                                    <option>XI Plan</option>
                                    <option>XII Plan</option>
                                    <option>Beyond XII Plan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Estimated Outlay (Rs. in Lakh) *</label>
                                <input
                                    v-model="manualForm.estimated_cost_lakh"
                                    type="number"
                                    step="0.01"
                                    placeholder="e.g. 299.93"
                                    class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
                                    required
                                />
                                <span v-if="manualForm.sanctioned_amount_cr" class="text-[10px] text-slate-400 mt-0.5 block font-mono">
                                    ≈ ₹{{ manualForm.sanctioned_amount_cr }} Cr
                                </span>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Physical Status</label>
                                <select
                                    v-model="manualForm.physical_status"
                                    class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                >
                                    <option>Ongoing</option>
                                    <option>Completed</option>
                                    <option>Foreclosed / Ongoing</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">State</label>
                                <select
                                    v-model="manualForm.state"
                                    class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                >
                                    <option>Assam</option>
                                    <option>Arunachal Pradesh</option>
                                    <option>Manipur</option>
                                    <option>Meghalaya</option>
                                    <option>Mizoram</option>
                                    <option>Nagaland</option>
                                    <option>Tripura</option>
                                    <option>Sikkim</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">River Basin</label>
                                <input
                                    v-model="manualForm.river_basin"
                                    type="text"
                                    placeholder="e.g. Brahmaputra / Barak"
                                    class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <button
                                type="button"
                                @click="isModalOpen = false"
                                class="px-4 py-2 text-xs font-semibold text-slate-600 border border-slate-300 rounded-xl hover:bg-slate-50 transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="manualForm.processing"
                                class="px-5 py-2.5 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 disabled:opacity-50 transition shadow-sm"
                            >
                                {{ manualForm.processing ? 'Saving...' : 'Create Scheme' }}
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>

<style scoped>
/* ─── Page Layout ─── */
.db-page-wrap {
    @apply py-6 px-4 sm:px-6 lg:px-8 space-y-6 max-w-screen-2xl mx-auto;
}

/* ─── Role Badges ─── */
.db-role-badge { @apply text-xs font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider; }
.badge-bb      { @apply bg-blue-100 text-blue-800 border border-blue-200; }
.badge-mojs    { @apply bg-violet-100 text-violet-800 border border-violet-200; }
.badge-state   { @apply bg-teal-100 text-teal-800 border border-teal-200; }
.badge-admin   { @apply bg-rose-100 text-rose-800 border border-rose-200; }
.badge-viewer  { @apply bg-slate-100 text-slate-700 border border-slate-200; }

/* ─── Hero Alert ─── */
.db-alert-banner {
    @apply flex items-center justify-between gap-4 bg-amber-50 border border-amber-300 rounded-2xl px-5 py-4;
}
.db-alert-cta {
    @apply shrink-0 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-colors shadow-sm;
}

/* ─── KPI Cards ─── */
.stat-kpi-card {
    @apply p-4 sm:p-5 rounded-2xl border transition-all cursor-pointer;
}

/* ─── Action Cards ─── */
.action-card {
    @apply bg-white rounded-2xl border p-4 sm:p-5 flex items-center gap-3.5 shadow-sm hover:shadow-md transition-all;
}
.action-icon {
    @apply w-12 h-12 rounded-2xl flex items-center justify-center shrink-0;
}
.module-blue   { @apply border-blue-100 hover:border-blue-300; }
.module-green  { @apply border-emerald-100 hover:border-emerald-300; }
.module-amber  { @apply border-amber-100 hover:border-amber-300; }
.module-indigo { @apply border-indigo-100 hover:border-indigo-300; }
.module-violet { @apply border-violet-100 hover:border-violet-300; }

/* ─── Catalogue Card ─── */
.cat-card {
    @apply bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden;
}
.cat-toolbar {
    @apply p-4 sm:p-5 bg-white border-b border-slate-200;
}

/* ─── Badges ─── */
.req-status-badge { @apply text-xs font-bold px-2 py-0.5 rounded border tracking-wide; }
.status-approved    { @apply bg-emerald-50 text-emerald-700 border-emerald-200; }
.status-correction  { @apply bg-amber-50 text-amber-800 border-amber-300; }
.status-submitted   { @apply bg-blue-50 text-blue-700 border-blue-200; }
.status-forwarded   { @apply bg-indigo-50 text-indigo-700 border-indigo-200; }
.status-draft       { @apply bg-slate-100 text-slate-600 border-slate-200; }
.status-rejected    { @apply bg-red-50 text-red-700 border-red-200; }

/* ─── Pagination ─── */
.pg-btn {
    @apply text-xs sm:text-sm font-bold px-3.5 py-2 rounded-xl border border-slate-300
           text-slate-700 hover:bg-slate-100 transition-all shadow-sm;
}
.pg-num {
    @apply w-9 h-9 flex items-center justify-center rounded-xl text-xs sm:text-sm font-bold transition-all;
}
.pg-active {
    @apply bg-blue-600 text-white border border-blue-600 shadow-sm
           w-9 h-9 flex items-center justify-center rounded-xl text-xs sm:text-sm font-bold;
}

/* ─── Modal Animation ─── */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
@keyframes modal-in {
    from { transform: scale(0.95) translateY(-10px); opacity: 0; }
    to   { transform: scale(1) translateY(0); opacity: 1; }
}
.animate-modal-in { animation: modal-in 0.25s ease-out; }
</style>
