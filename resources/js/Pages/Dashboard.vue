<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GisProjectMap from '@/Components/GisProjectMap.vue';
import InputError from '@/Components/InputError.vue';

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

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Role helpers
const isSuperAdmin = computed(() => user.value?.role === 'super_admin');
const isBB = computed(() => user.value?.role === 'board_official');
const isMoJS = computed(() => user.value?.role === 'mojs_official');
const isStateOfficial = computed(() => ['state_official', 'state'].includes(user.value?.role));
const canSubmitDpr = computed(() => ['super_admin', 'board_official', 'state_official', 'state'].includes(user.value?.role));

// Active Main Tab: 'TABLE' | 'GIS_MAP' | 'ANALYTICS'
const activeMainTab = ref('TABLE');

// Search & filter & pagination
const schemeSearch = ref('');
const selectedState = ref('ALL');
const selectedBasin = ref('ALL');
const activeStatusFilter = ref('ALL'); // 'ALL' | 'ONGOING' | 'COMPLETED'
const sortBy = ref('ONGOING_FIRST'); // 'ONGOING_FIRST' | 'PROGRESS_DESC' | 'SANCTIONED_DESC' | 'NEWEST'
const expandedSchemeId = ref(null);
const currentPage = ref(1);
const PER_PAGE = 8;

// Ongoing helper
const isSchemeOngoing = (s) => {
    return s.physical_status !== 'Completed' && Number(s.physical_progress_pct) < 100;
};

// Counts
const completedCount = computed(() =>
    props.schemes.filter(s => s.physical_status === 'Completed' || Number(s.physical_progress_pct) >= 100).length
);
const ongoingCount = computed(() =>
    props.schemes.filter(s => isSchemeOngoing(s)).length
);
const totalSchemesCount = computed(() => props.schemes.length);

const availableStates = computed(() => {
    const set = new Set(props.schemes.map(s => s.state).filter(Boolean));
    return ['ALL', ...Array.from(set)];
});

const availableBasins = computed(() => {
    const set = new Set(props.schemes.map(s => s.river_basin).filter(Boolean));
    return ['ALL', ...Array.from(set)];
});

// State-specific pending requests requiring correction
const stateNeedsCorrectionRequests = computed(() => {
    return props.schemes.flatMap(s => s.payment_requests || []).filter(r => r.status === 'NEEDS_CORRECTION');
});

// Sorted schemes
const sortedSchemes = computed(() => {
    return [...props.schemes].sort((a, b) => {
        if (sortBy.value === 'ONGOING_FIRST') {
            const aOngoing = isSchemeOngoing(a) ? 1 : 0;
            const bOngoing = isSchemeOngoing(b) ? 1 : 0;
            if (aOngoing !== bOngoing) {
                return bOngoing - aOngoing;
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

// Filtered schemes
const filteredSchemes = computed(() => {
    const q = schemeSearch.value.trim().toLowerCase();
    return sortedSchemes.value.filter(s => {
        const matchesQuery = !q ||
            s.scheme_code?.toLowerCase().includes(q) ||
            s.scheme_name?.toLowerCase().includes(q) ||
            s.river_basin?.toLowerCase().includes(q) ||
            (s.district && s.district.toLowerCase().includes(q)) ||
            (s.division && s.division.toLowerCase().includes(q)) ||
            (s.state && s.state.toLowerCase().includes(q));

        if (!matchesQuery) return false;

        const matchesState = selectedState.value === 'ALL' || s.state === selectedState.value;
        if (!matchesState) return false;

        const matchesBasin = selectedBasin.value === 'ALL' || s.river_basin === selectedBasin.value;
        if (!matchesBasin) return false;

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

watch([schemeSearch, selectedState, selectedBasin, activeStatusFilter, sortBy], () => {
    currentPage.value = 1;
});

const goToPage = (n) => {
    if (n >= 1 && n <= totalPages.value) {
        currentPage.value = n;
        window.scrollTo({ top: 400, behavior: 'smooth' });
    }
};

const toggleExpand = (id) => {
    expandedSchemeId.value = expandedSchemeId.value === id ? null : id;
};

// Date Formatter
const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch (e) {
        return dateStr;
    }
};

// CSV Export
const exportToCsv = () => {
    const headers = ['Scheme Code', 'Scheme Name', 'State', 'Division/District', 'River Basin', 'Sanctioned Cost (Cr)', 'Central Share %', 'Physical Progress %', 'Status'];
    const rows = filteredSchemes.value.map(s => [
        `"${s.scheme_code || ''}"`,
        `"${(s.scheme_name || '').replace(/"/g, '""')}"`,
        `"${s.state || ''}"`,
        `"${s.division || s.district || ''}"`,
        `"${s.river_basin || ''}"`,
        `"${s.sanctioned_amount_cr || 0}"`,
        `"${s.central_share_pct || 90}"`,
        `"${s.physical_progress_pct || 0}"`,
        `"${s.physical_status || 'Ongoing'}"`
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `FMBAP_Schemes_Export_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// Modal State: Excel Ingestion & Manual Entry
const isModalOpen = ref(false);
const modalMode = ref('excel'); // 'excel' | 'manual'
const excelFile = ref(null);
const isParsing = ref(false);
const parseError = ref('');
const parsedRows = ref([]);
const isImporting = ref(false);

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
        },
        onError: () => {
            isImporting.value = false;
        },
    });
};

const isFetchingCode = ref(false);
const fetchNextCode = async () => {
    try {
        isFetchingCode.value = true;
        const res = await window.axios.get(route('schemes.next-code'), {
            params: { state: manualForm.state || 'Assam' }
        });
        if (res.data && res.data.next_code) {
            manualForm.scheme_code = res.data.next_code;
        }
    } catch (e) {
        console.warn('Could not auto-fetch scheme code', e);
    } finally {
        isFetchingCode.value = false;
    }
};

const submitManualScheme = () => {
    manualForm.post(route('schemes.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isModalOpen.value = false;
            manualForm.reset();
            manualForm.clearErrors();
        },
    });
};
</script>

<template>
    <Head title="FMBAP Scheme Monitoring & Management Dashboard" />

    <AuthenticatedLayout>
        <!-- Page Header Strip -->
        <template #header>
            <div class="py-0.5">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-none">
                        Flood Management &amp; Border Areas Programme (FMBAP)
                    </h1>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-[#0F4C9F] border border-blue-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#0F4C9F]"></span>
                        {{ isSuperAdmin ? 'Super Admin HQ' : (user?.state ? `${user.state} WRD` : 'Departmental Portal') }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 font-medium mt-1 truncate">
                    Central Assistance Monitoring &bull; Scheme Baseline Register &amp; Statutory Fund Releases &bull; Brahmaputra Board &amp; MoJS
                </p>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <!-- Action Required: Claims Returned for Correction -->
            <div v-if="stateNeedsCorrectionRequests.length > 0 && isStateOfficial" class="p-4 bg-amber-50 border-2 border-amber-300 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-xs">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">⚠️</span>
                    <div>
                        <div class="font-bold text-amber-950 text-sm">Action Required: {{ stateNeedsCorrectionRequests.length }} Claim(s) Sent Back for Correction</div>
                        <p class="text-xs text-amber-800 mt-0.5">The Board or Ministry has returned claims requiring amendments. Open Fund Release Claims to edit and resubmit.</p>
                    </div>
                </div>
                <Link
                    :href="route('fund-release.index')"
                    class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-lg transition shrink-0 shadow-xs cursor-pointer"
                >
                    View &amp; Correct Claims &rarr;
                </Link>
            </div>

            <!-- ─── 1. EXECUTIVE SUMMARY METRIC CARDS ─── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Total Sanctioned Cost -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Total Sanctioned Outlay</span>
                        <span class="p-1.5 bg-blue-50 text-[#0F4C9F] rounded-lg">₹ Cr</span>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                            ₹{{ summary?.total_sanctioned_cr || '0.00' }} <span class="text-sm font-semibold text-slate-500">Cr</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1 flex items-center justify-between font-medium">
                            <span>Central 90% : State 10% Ratio</span>
                            <span class="text-[#0F4C9F] font-bold">{{ totalSchemesCount }} Schemes</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Central Share Disbursed -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Central Assistance Released</span>
                        <span class="p-1.5 bg-emerald-50 text-emerald-700 rounded-lg">✓ Fund</span>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-emerald-950 leading-tight">
                            ₹{{ summary?.total_released_cr || '0.00' }} <span class="text-sm font-semibold text-emerald-700">Cr</span>
                        </div>
                        <div class="text-[11px] text-emerald-700 mt-1 font-medium">
                            Released against verified GFR-12A UCs
                        </div>
                    </div>
                </div>

                <!-- Card 3: Execution Portfolio -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-800">Execution Portfolio</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-200">
                            {{ ongoingCount }} Active
                        </span>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                            {{ ongoingCount }} <span class="text-base font-bold text-slate-600">/ {{ totalSchemesCount }} Schemes</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 mt-2 overflow-hidden flex">
                            <div class="bg-amber-500 h-full" :style="{ width: `${(ongoingCount / (totalSchemesCount || 1)) * 100}%` }"></div>
                            <div class="bg-emerald-500 h-full" :style="{ width: `${(completedCount / (totalSchemesCount || 1)) * 100}%` }"></div>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Average Physical Progress -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-blue-800">Physical Progress Avg.</span>
                        <span class="text-xs font-bold text-blue-900 bg-blue-50 px-2 py-0.5 rounded">% Metric</span>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-blue-950 leading-tight">
                            {{ summary?.avg_physical_progress || 0 }}%
                        </div>
                        <div class="w-full bg-blue-100 rounded-full h-2 mt-2 overflow-hidden">
                            <div class="bg-blue-600 h-full rounded-full transition-all duration-500" :style="{ width: `${summary?.avg_physical_progress || 0}%` }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── 2. RIVER BASIN & FLOOD PROTECTION PROJECT LOCATIONS ─── -->
            <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-2xs space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm sm:text-base font-black text-slate-900 tracking-tight">
                                Project Locations & River Basin Map
                            </h2>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Live Map
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Visual overview of anti-erosion and flood mitigation schemes across the Brahmaputra & North-Eastern river basins.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('schemes.index')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white text-xs font-bold rounded-md shadow-xs transition"
                        >
                            <span>Open Schemes Catalogue &rarr;</span>
                        </Link>
                    </div>
                </div>

                <!-- GIS Map Embed with Side Explorer -->
                <div class="rounded-lg overflow-hidden border border-slate-200 shadow-xs">
                    <GisProjectMap :schemes="schemes" height="600px" />
                </div>
            </div>

            <!-- ─── 3. FINANCIAL & STATUTORY PORTFOLIO ANALYTICS ─── -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Implementation Ratio Card -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                            Scheme Implementation Progress Ratio
                        </h3>
                        <span class="text-[11px] font-bold text-slate-500">{{ totalSchemesCount }} Total Registered</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="p-3.5 bg-amber-50/80 rounded-lg border border-amber-200/80">
                            <span class="text-slate-600 font-bold uppercase text-[10px]">Ongoing Schemes</span>
                            <strong class="text-2xl font-black text-amber-900 block mt-1">{{ ongoingCount }}</strong>
                            <span class="text-amber-700 text-[11px] font-medium">{{ ((ongoingCount / (totalSchemesCount || 1)) * 100).toFixed(0) }}% of Portfolio</span>
                        </div>
                        <div class="p-3.5 bg-emerald-50/80 rounded-lg border border-emerald-200/80">
                            <span class="text-slate-600 font-bold uppercase text-[10px]">Completed Schemes</span>
                            <strong class="text-2xl font-black text-emerald-950 block mt-1">{{ completedCount }}</strong>
                            <span class="text-emerald-700 text-[11px] font-medium">{{ ((completedCount / (totalSchemesCount || 1)) * 100).toFixed(0) }}% of Portfolio</span>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div>
                        <div class="flex justify-between text-xs text-slate-600 font-medium mb-1">
                            <span>Overall Completion Pace</span>
                            <span class="font-bold text-slate-800">{{ ((completedCount / (totalSchemesCount || 1)) * 100).toFixed(0) }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden flex">
                            <div class="bg-emerald-500 h-full" :style="{ width: `${(completedCount / (totalSchemesCount || 1)) * 100}%` }"></div>
                            <div class="bg-amber-400 h-full" :style="{ width: `${(ongoingCount / (totalSchemesCount || 1)) * 100}%` }"></div>
                        </div>
                    </div>
                </div>

                <!-- Financial Funding Pattern Ratio -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                            Statutory Funding Pattern Split (90:10)
                        </h3>
                        <span class="text-[11px] font-bold text-blue-900">₹{{ summary?.total_sanctioned_cr || '0.00' }} Cr Total</span>
                    </div>

                    <div class="space-y-3.5 text-xs">
                        <div>
                            <div class="flex justify-between font-bold text-slate-700 mb-1">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-[#0F4C9F]"></span>
                                    Central Assistance Outlay (90%)
                                </span>
                                <span class="font-black text-slate-900">₹{{ (parseFloat(summary?.total_sanctioned_cr || 0) * 0.9).toFixed(2) }} Cr</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                <div class="bg-[#0F4C9F] h-full rounded-full" style="width: 90%;"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between font-bold text-slate-700 mb-1">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                                    State Matching Share (10%)
                                </span>
                                <span class="font-black text-slate-900">₹{{ (parseFloat(summary?.total_sanctioned_cr || 0) * 0.1).toFixed(2) }} Cr</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                <div class="bg-purple-600 h-full rounded-full" style="width: 10%;"></div>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                            <span>Statutory MoJS FMBAP Funding Norm</span>
                            <span class="font-semibold text-slate-700">Special Category States (90:10)</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ─── 6. MODAL: ADD SCHEME (DPR) / EXCEL INGESTION ─── -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-2xl overflow-hidden animate-fade-in">
                <!-- Modal Header -->
                <div class="bg-[#0F4C9F] text-white p-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🏛️</span>
                        <div>
                            <h3 class="text-sm font-bold text-white">Add New FMBAP Scheme / Batch Import</h3>
                            <p class="text-[11px] text-blue-200">Register statutory scheme baseline in master catalogue</p>
                        </div>
                    </div>
                    <button @click="isModalOpen = false" class="p-1 rounded text-white/80 hover:text-white hover:bg-white/10 text-lg">&times;</button>
                </div>

                <!-- Modal Mode Tabs -->
                <div class="flex border-b border-slate-200 bg-slate-50 text-xs font-bold">
                    <button
                        type="button"
                        @click="modalMode = 'excel'"
                        :class="modalMode === 'excel' ? 'border-b-2 border-[#0F4C9F] text-[#0F4C9F] bg-white' : 'text-slate-500 hover:text-slate-800'"
                        class="flex-1 py-3 text-center transition"
                    >
                        📁 Excel Batch Ingestion (.xlsx)
                    </button>
                    <button
                        type="button"
                        @click="modalMode = 'manual'"
                        :class="modalMode === 'manual' ? 'border-b-2 border-[#0F4C9F] text-[#0F4C9F] bg-white' : 'text-slate-500 hover:text-slate-800'"
                        class="flex-1 py-3 text-center transition"
                    >
                        ✏️ Manual Scheme Entry (Single)
                    </button>
                </div>

                <!-- Modal Body: Excel Mode -->
                <div v-if="modalMode === 'excel'" class="p-6 space-y-4">
                    <div class="p-4 border-2 border-dashed border-slate-300 rounded-xl bg-slate-50 text-center relative hover:bg-slate-100 transition">
                        <input type="file" @change="onFileSelected" accept=".xlsx,.xls" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                        <svg class="w-8 h-8 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <p class="text-xs font-bold text-slate-700">Click or drag &amp; drop FMBAP Master Excel Spreadsheet</p>
                        <p class="text-[11px] text-slate-400 mt-1">Supports standard .xlsx format</p>
                    </div>

                    <div v-if="isParsing" class="text-center py-3 text-xs text-blue-700 font-bold">
                        ⏳ Parsing spreadsheet rows...
                    </div>
                    <div v-if="parseError" class="p-3 bg-red-50 text-red-700 text-xs rounded border border-red-200">
                        {{ parseError }}
                    </div>

                    <div v-if="parsedRows.length > 0" class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs flex items-center justify-between">
                        <span class="font-bold text-blue-900">Found {{ parsedRows.length }} schemes ready for ingestion.</span>
                        <button
                            type="button"
                            @click="commitImport"
                            :disabled="isImporting"
                            class="px-4 py-2 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white font-bold rounded shadow transition disabled:opacity-50"
                        >
                            {{ isImporting ? 'Ingesting...' : 'Import to Master Catalogue' }}
                        </button>
                    </div>
                </div>

                <!-- Modal Body: Manual Entry Mode -->
                <form v-else-if="modalMode === 'manual'" @submit.prevent="submitManualScheme" class="p-6 space-y-4 text-xs">
                    <!-- Form Error Alert Banner -->
                    <div v-if="manualForm.hasErrors" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs">
                        <div class="font-bold flex items-center gap-1.5 mb-1 text-rose-900">
                            <span class="text-sm">⚠️</span> Scheme Could Not Be Saved:
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-700">
                            <li v-for="(err, key) in manualForm.errors" :key="key">{{ err }}</li>
                        </ul>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-bold text-slate-700">Scheme Code *</label>
                                <button
                                    type="button"
                                    @click="fetchNextCode"
                                    :disabled="isFetchingCode"
                                    class="text-[11px] font-bold text-[#0F4C9F] hover:underline flex items-center gap-1 cursor-pointer disabled:opacity-50"
                                >
                                    <span>{{ isFetchingCode ? 'Generating...' : '✨ Next Free Code' }}</span>
                                </button>
                            </div>
                            <input
                                v-model="manualForm.scheme_code"
                                type="text"
                                :class="manualForm.errors.scheme_code ? 'border-rose-400 ring-1 ring-rose-400 bg-rose-50/40' : 'border-slate-300'"
                                class="w-full rounded text-xs font-mono font-bold uppercase"
                                placeholder="e.g. AS-14"
                                required
                            />
                            <InputError :message="manualForm.errors.scheme_code" class="mt-1" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">State *</label>
                            <input
                                v-model="manualForm.state"
                                type="text"
                                :class="manualForm.errors.state ? 'border-rose-400 ring-1 ring-rose-400 bg-rose-50/40' : 'border-slate-300'"
                                class="w-full rounded text-xs"
                                required
                            />
                            <InputError :message="manualForm.errors.state" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Scheme Title / Description *</label>
                        <textarea
                            v-model="manualForm.scheme_name"
                            rows="2"
                            :class="manualForm.errors.scheme_name ? 'border-rose-400 ring-1 ring-rose-400 bg-rose-50/40' : 'border-slate-300'"
                            class="w-full rounded text-xs"
                            placeholder="Full name of the flood management / anti-erosion project"
                            required
                        ></textarea>
                        <InputError :message="manualForm.errors.scheme_name" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Division / District</label>
                            <input v-model="manualForm.division" type="text" class="w-full rounded border-slate-300 text-xs" placeholder="e.g. Guwahati East" />
                            <InputError :message="manualForm.errors.division" class="mt-1" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">River Basin</label>
                            <input v-model="manualForm.river_basin" type="text" class="w-full rounded border-slate-300 text-xs" placeholder="e.g. Brahmaputra" />
                            <InputError :message="manualForm.errors.river_basin" class="mt-1" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Estimated Cost (₹ Lakh) *</label>
                            <input
                                v-model="manualForm.estimated_cost_lakh"
                                type="number"
                                step="0.01"
                                :class="manualForm.errors.estimated_cost_lakh ? 'border-rose-400 ring-1 ring-rose-400 bg-rose-50/40' : 'border-slate-300'"
                                class="w-full rounded text-xs"
                                placeholder="e.g. 608"
                                required
                            />
                            <InputError :message="manualForm.errors.estimated_cost_lakh" class="mt-1" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="isModalOpen = false" class="px-4 py-2 border rounded font-semibold text-slate-600 hover:bg-slate-50 transition">Cancel</button>
                        <button
                            type="submit"
                            :disabled="manualForm.processing"
                            class="px-5 py-2 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white font-bold rounded shadow transition disabled:opacity-50 flex items-center gap-1.5"
                        >
                            <svg v-if="manualForm.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ manualForm.processing ? 'Saving Scheme...' : 'Save Scheme' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.15s ease-out forwards;
}
</style>
