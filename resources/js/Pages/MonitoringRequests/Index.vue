<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    requests: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});
const isBBOrAdmin = computed(() => 
    ['board_official', 'super_admin'].includes(currentUser.value?.role)
);

// ─── Search, Filter, Sort, Pagination ───
const search = ref('');
const selectedState = ref('ALL');
const selectedBasin = ref('ALL');
const selectedFY = ref('ALL');
const statusFilter = ref('ALL');
const sortBy = ref('NEWEST');
const currentPage = ref(1);
const perPage = 10;

// Helper to determine financial year
const getMonitoringFY = (r) => {
    if (r.scheme?.metadata?.financial_year) return r.scheme.metadata.financial_year;
    if (r.scheme?.financial_year) return r.scheme.financial_year;
    if (r.metadata?.financial_year) return r.metadata.financial_year;
    const dateStr = r.submitted_at || r.created_at || r.scheme?.created_at;
    if (!dateStr) return '2026-2027';
    try {
        const d = new Date(dateStr);
        const y = d.getFullYear();
        const m = d.getMonth() + 1;
        if (m >= 4) {
            return `${y}-${y + 1}`;
        } else {
            return `${y - 1}-${y}`;
        }
    } catch {
        return '2026-2027';
    }
};

// ─── Metrics ───
const totalCount = computed(() => props.requests.length);

const pendingInspectionCount = computed(() => 
    props.requests.filter(r => !r.bb_monitoring_report || (r.status === 'SUBMITTED_TO_BB' && !r.bb_monitoring_report)).length
);

const draftReportCount = computed(() => 
    props.requests.filter(r => r.bb_monitoring_report?.status === 'DRAFT').length
);

const completedReportCount = computed(() => 
    props.requests.filter(r => r.bb_monitoring_report && r.bb_monitoring_report.status !== 'DRAFT').length
);

const totalAmountCr = computed(() => {
    const sum = props.requests.reduce((acc, r) => acc + (parseFloat(r.requested_amount_cr) || 0), 0);
    return sum.toFixed(2);
});

// Available States, Basins & FYs
const availableStates = computed(() => {
    const set = new Set(props.requests.map(r => r.scheme?.state).filter(Boolean));
    return ['ALL', ...Array.from(set)];
});

const availableBasins = computed(() => {
    const set = new Set(props.requests.map(r => r.scheme?.river_basin).filter(Boolean));
    return ['ALL', ...Array.from(set)];
});

const availableFYs = computed(() => {
    const fromData = props.requests.map(r => getMonitoringFY(r)).filter(Boolean);
    const defaults = ['2026-2027', '2025-2026', '2024-2025', '2023-2024', '2022-2023'];
    const set = new Set([...fromData, ...defaults]);
    const sorted = Array.from(set).sort().reverse();
    return ['ALL', ...sorted];
});

// ─── Filtering & Sorting ───
const filteredRequests = computed(() => {
    const q = search.value.trim().toLowerCase();
    
    const list = props.requests.filter(r => {
        const code = r.scheme?.scheme_code?.toLowerCase() || '';
        const name = r.scheme?.scheme_name?.toLowerCase() || '';
        const idStr = String(r.id);
        const div = (r.scheme?.division || r.scheme?.district || '').toLowerCase();
        const matchesQuery = !q || code.includes(q) || name.includes(q) || idStr.includes(q) || div.includes(q);

        if (!matchesQuery) return false;

        if (selectedState.value !== 'ALL' && r.scheme?.state !== selectedState.value) {
            return false;
        }

        if (selectedBasin.value !== 'ALL' && r.scheme?.river_basin !== selectedBasin.value) {
            return false;
        }

        if (selectedFY.value !== 'ALL' && getMonitoringFY(r) !== selectedFY.value) {
            return false;
        }

        if (statusFilter.value === 'PENDING') {
            return !r.bb_monitoring_report || (r.status === 'SUBMITTED_TO_BB' && !r.bb_monitoring_report);
        }
        if (statusFilter.value === 'DRAFT') {
            return r.bb_monitoring_report?.status === 'DRAFT';
        }
        if (statusFilter.value === 'COMPLETED') {
            return r.bb_monitoring_report && r.bb_monitoring_report.status !== 'DRAFT';
        }

        return true;
    });

    return list.sort((a, b) => {
        if (sortBy.value === 'AMOUNT_DESC') {
            return (parseFloat(b.requested_amount_cr) || 0) - (parseFloat(a.requested_amount_cr) || 0);
        }
        if (sortBy.value === 'PROGRESS_DESC') {
            return (parseFloat(b.physical_progress_pct) || 0) - (parseFloat(a.physical_progress_pct) || 0);
        }
        if (sortBy.value === 'NEWEST') {
            return (b.id || 0) - (a.id || 0);
        }
        return (b.id || 0) - (a.id || 0);
    });
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredRequests.value.length / perPage)));
const paginatedRequests = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    return filteredRequests.value.slice(start, start + perPage);
});

watch([search, selectedState, selectedBasin, selectedFY, statusFilter, sortBy], () => {
    currentPage.value = 1;
});

const goToPage = (n) => {
    if (n >= 1 && n <= totalPages.value) {
        currentPage.value = n;
        window.scrollTo({ top: 300, behavior: 'smooth' });
    }
};

const getReportStatus = (req) => {
    if (!req.bb_monitoring_report) {
        return {
            label: 'Pending Inspection',
            class: 'bg-rose-100 text-rose-800 border-rose-300',
            dot: 'bg-rose-500',
        };
    }
    if (req.bb_monitoring_report.status === 'DRAFT') {
        return {
            label: 'Draft Saved',
            class: 'bg-amber-100 text-amber-900 border-amber-300',
            dot: 'bg-amber-500',
        };
    }
    return {
        label: 'Report Submitted',
        class: 'bg-emerald-100 text-emerald-800 border-emerald-300',
        dot: 'bg-emerald-500',
    };
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return dateStr;
    }
};

// CSV Export
const exportToCsv = () => {
    const headers = [
        'Claim ID',
        'Scheme Code',
        'Project Name',
        'Division Name',
        'State',
        'River Basin',
        'Financial Year',
        'Date of Submission',
        'Claimed Outlay (Cr)',
        'Instalment',
        'Physical Progress %',
        'Inspection Status',
        'Last Audited / Updated'
    ];
    const rows = filteredRequests.value.map(r => [
        `"#CLAIM-${r.id}"`,
        `"${r.scheme?.scheme_code || ''}"`,
        `"${(r.scheme?.scheme_name || '').replace(/"/g, '""')}"`,
        `"${(r.scheme?.division || r.scheme?.district || '').replace(/"/g, '""')}"`,
        `"${r.scheme?.state || ''}"`,
        `"${r.scheme?.river_basin || ''}"`,
        `"FY ${getMonitoringFY(r)}"`,
        `"${formatDate(r.submitted_at || r.created_at)}"`,
        `"${r.requested_amount_cr || 0}"`,
        `"Instalment ${r.instalment_number || 1}"`,
        `"${r.physical_progress_pct || 0}"`,
        `"${getReportStatus(r).label}"`,
        `"${formatDate(r.bb_monitoring_report?.updated_at || r.updated_at || r.created_at)}"`
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `FMBAP_Site_Monitoring_Queue_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <Head title="Brahmaputra Board Site Monitoring & Inspection — FMBAP" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 py-0.5">
                <div class="flex items-center gap-2.5 min-w-0">
                    <Link
                        :href="route('dashboard')"
                        class="w-8 h-8 rounded-md bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 hover:text-slate-900 transition shrink-0"
                        title="Back to Dashboard"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-none">
                                BB Site Monitoring &amp; Inspection Queue
                            </h1>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-[#0F4C9F] border border-blue-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#0F4C9F]"></span>
                                Brahmaputra Board HQ
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium mt-1 truncate">
                            Physical Field Audits &bull; Geo-tagged Photo Verification &bull; Inspection Dossiers before Central Release
                        </p>
                    </div>
                </div>

                <!-- Header Actions (Streamlined Single Row) -->
                <div class="flex items-center gap-2 shrink-0 flex-wrap sm:flex-nowrap">
                    <button
                        type="button"
                        @click="exportToCsv"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-xs font-semibold rounded-md shadow-2xs transition cursor-pointer"
                        title="Export filtered monitoring queue to CSV"
                    >
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        <span>Export Queue</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="w-full max-w-[1720px] mx-auto px-3 sm:px-6 py-5 space-y-5">

            <!-- ─── 1. EXECUTIVE METRIC CARDS ─── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total In Queue -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Total in Inspection Queue</span>
                        <span class="p-1.5 bg-blue-50 text-[#0F4C9F] rounded-lg">📍</span>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                            {{ totalCount }} <span class="text-sm font-semibold text-slate-500">Schemes</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1 flex items-center justify-between font-medium">
                            <span>Value in Audit:</span>
                            <strong class="text-slate-900 font-bold">₹{{ totalAmountCr }} Cr</strong>
                        </div>
                    </div>
                </div>

                <!-- Pending Field Inspection -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-800">Pending Field Inspection</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-900 border border-rose-200">
                            {{ pendingInspectionCount }} Pending
                        </span>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-rose-950 leading-tight">
                            {{ pendingInspectionCount }} <span class="text-base font-bold text-slate-600">pending visit</span>
                        </div>
                        <div class="text-[11px] text-rose-700 mt-1 font-medium">
                            Requires physical site audit &amp; geo-photos
                        </div>
                    </div>
                </div>

                <!-- In Draft Reports -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-800">Draft Inspection Reports</span>
                        <span class="p-1.5 bg-amber-50 text-amber-700 rounded-lg">📝</span>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-amber-950 leading-tight">
                            {{ draftReportCount }} <span class="text-base font-bold text-slate-600">in progress</span>
                        </div>
                        <div class="text-[11px] text-amber-700 mt-1 font-medium">
                            Saved locally &bull; pending BB HQ review
                        </div>
                    </div>
                </div>

                <!-- Reports Completed -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Audits Completed</span>
                        <span class="p-1.5 bg-emerald-50 text-emerald-700 rounded-lg">✓</span>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-emerald-950 leading-tight">
                            {{ completedReportCount }} <span class="text-base font-bold text-slate-600">verified</span>
                        </div>
                        <div class="text-[11px] text-emerald-700 mt-1 font-medium">
                            Forwarded to MoJS for release sanction
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── 2. ENTERPRISE FILTER & SEARCH TOOLBAR ─── -->
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-3 text-xs">
                <!-- Search Input -->
                <div class="w-full md:w-80 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search claim #, scheme code, name, basin, division..."
                        class="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:bg-white focus:ring-1 focus:ring-[#0F4C9F]"
                    />
                </div>

                <!-- Dropdowns & Status Pills -->
                <div class="flex items-center gap-2.5 flex-wrap w-full md:w-auto">
                    <!-- State Filter -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-500 font-bold uppercase text-[10px]">State:</span>
                        <select
                            v-model="selectedState"
                            class="py-1.5 px-2.5 text-xs bg-slate-50 border border-slate-300 rounded-lg text-slate-800 font-medium focus:bg-white"
                        >
                            <option value="ALL">All States</option>
                            <option v-for="st in availableStates.filter(s => s !== 'ALL')" :key="st" :value="st">
                                {{ st }}
                            </option>
                        </select>
                    </div>

                    <!-- Basin Filter -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-500 font-bold uppercase text-[10px]">Basin:</span>
                        <select
                            v-model="selectedBasin"
                            class="py-1.5 px-2.5 text-xs bg-slate-50 border border-slate-300 rounded-lg text-slate-800 font-medium focus:bg-white"
                        >
                            <option value="ALL">All River Basins</option>
                            <option v-for="bs in availableBasins.filter(b => b !== 'ALL')" :key="bs" :value="bs">
                                {{ bs }}
                            </option>
                        </select>
                    </div>

                    <!-- Financial Year Filter -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-500 font-bold uppercase text-[10px]">FY:</span>
                        <select
                            v-model="selectedFY"
                            class="py-1.5 px-2.5 text-xs bg-slate-50 border border-slate-300 rounded-lg text-slate-800 font-medium focus:bg-white"
                        >
                            <option value="ALL">All Financial Years</option>
                            <option v-for="fy in availableFYs.filter(f => f !== 'ALL')" :key="fy" :value="fy">
                                FY {{ fy }}
                            </option>
                        </select>
                    </div>

                    <!-- Status Filter Pills -->
                    <div class="inline-flex rounded-lg bg-slate-100 p-0.5 border border-slate-200">
                        <button
                            type="button"
                            @click="statusFilter = 'ALL'"
                            :class="statusFilter === 'ALL' ? 'bg-white text-slate-900 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-md text-[11px] transition cursor-pointer"
                        >
                            All ({{ totalCount }})
                        </button>
                        <button
                            type="button"
                            @click="statusFilter = 'PENDING'"
                            :class="statusFilter === 'PENDING' ? 'bg-rose-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-md text-[11px] transition cursor-pointer"
                        >
                            Needs Visit ({{ pendingInspectionCount }})
                        </button>
                        <button
                            v-if="draftReportCount > 0"
                            type="button"
                            @click="statusFilter = 'DRAFT'"
                            :class="statusFilter === 'DRAFT' ? 'bg-amber-500 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-md text-[11px] transition cursor-pointer"
                        >
                            In Draft ({{ draftReportCount }})
                        </button>
                        <button
                            type="button"
                            @click="statusFilter = 'COMPLETED'"
                            :class="statusFilter === 'COMPLETED' ? 'bg-emerald-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-md text-[11px] transition cursor-pointer"
                        >
                            Verified ({{ completedReportCount }})
                        </button>
                    </div>

                    <!-- Sort -->
                    <div class="flex items-center gap-1.5">
                        <select
                            v-model="sortBy"
                            class="py-1.5 px-2.5 text-xs bg-slate-50 border border-slate-300 rounded-lg text-slate-800 font-medium focus:bg-white"
                        >
                            <option value="NEWEST">Newest Claims</option>
                            <option value="AMOUNT_DESC">Outlay (High to Low)</option>
                            <option value="PROGRESS_DESC">Progress (High to Low)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ─── 3. ENTERPRISE MONITORING TABLE ─── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-[#0F4C9F] text-white font-bold uppercase tracking-wider text-[10px]">
                                <th class="py-3 px-3 text-center w-10">#</th>
                                <th class="py-3 px-3 text-center min-w-[90px]">Claim #</th>
                                <th class="py-3 px-3 min-w-[130px]">Scheme Code</th>
                                <th class="py-3 px-3 min-w-[240px]">Project Name</th>
                                <th class="py-3 px-3 min-w-[130px]">Division Name</th>
                                <th class="py-3 px-3 min-w-[130px]">State &amp; River Basin</th>
                                <th class="py-3 px-3 text-center min-w-[120px]">Date of Submission</th>
                                <th class="py-3 px-3 text-right min-w-[120px]">Claimed Outlay</th>
                                <th class="py-3 px-3 text-center min-w-[90px]">Instalment</th>
                                <th class="py-3 px-3 text-center min-w-[120px]">Physical Progress</th>
                                <th class="py-3 px-3 text-center min-w-[150px]">Inspection Status</th>
                                <th class="py-3 px-3 text-center min-w-[120px]">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-800">
                            <tr
                                v-for="(req, index) in paginatedRequests"
                                :key="req.id"
                                :class="[
                                    index % 2 === 0 ? 'bg-white' : 'bg-slate-50/40',
                                    'hover:bg-blue-50/30 transition'
                                ]"
                            >
                                <!-- Index -->
                                <td class="py-2.5 px-3 text-center font-mono text-slate-400 font-bold">
                                    {{ (currentPage - 1) * perPage + index + 1 }}
                                </td>

                                <!-- Claim ID -->
                                <td class="py-2.5 px-3 text-center font-mono text-blue-900 font-bold whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                        #CLAIM-{{ req.id }}
                                    </span>
                                </td>

                                <!-- Scheme Code -->
                                <td class="py-2.5 px-3 font-mono">
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-[#0F4C9F] uppercase border border-blue-200 whitespace-nowrap">
                                        {{ req.scheme?.scheme_code || 'UNASSIGNED' }}
                                    </span>
                                    <span v-if="req.scheme?.plan_period" class="text-[10px] text-slate-500 font-semibold block mt-0.5">
                                        {{ req.scheme.plan_period }}
                                    </span>
                                </td>

                                <!-- Project Name -->
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900 leading-snug">
                                        {{ req.scheme?.scheme_name || 'No scheme description available' }}
                                    </div>
                                </td>

                                <!-- Division Name -->
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-800 text-xs">
                                        {{ req.scheme?.division || req.scheme?.district || 'Assam WRD' }}
                                    </div>
                                </td>

                                <!-- State & River Basin -->
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900">{{ req.scheme?.state || 'Assam' }}</div>
                                    <div class="text-[11px] text-blue-700 font-semibold flex items-center gap-1 mt-0.5">
                                        <span>🌊</span> {{ req.scheme?.river_basin || 'Brahmaputra' }}
                                    </div>
                                </td>

                                <!-- Date of Submission -->
                                <td class="py-2.5 px-3 text-center font-mono text-[11px] text-slate-700 font-medium whitespace-nowrap">
                                    {{ formatDate(req.submitted_at || req.created_at) }}
                                </td>

                                <!-- Claimed Amount -->
                                <td class="py-2.5 px-3 text-right font-mono">
                                    <div class="font-black text-slate-900 text-xs">
                                        ₹{{ parseFloat(req.requested_amount_cr || 0).toFixed(2) }} Cr
                                    </div>
                                    <div class="text-[10px] text-slate-500">
                                        (₹{{ (parseFloat(req.requested_amount_cr || 0) * 100).toFixed(0) }} L)
                                    </div>
                                </td>

                                <!-- Instalment -->
                                <td class="py-2.5 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-800 border border-slate-200 whitespace-nowrap">
                                        Instalment #{{ req.instalment_number || '1' }}
                                    </span>
                                </td>

                                <!-- Physical Progress -->
                                <td class="py-2.5 px-3 text-center">
                                    <div class="flex items-center justify-between text-[11px] font-bold text-slate-700 mb-1">
                                        <span>{{ parseFloat(req.physical_progress_pct || 0) }}%</span>
                                        <span v-if="parseFloat(req.physical_progress_pct) >= 100" class="text-emerald-600 font-bold text-[10px]">Done</span>
                                    </div>
                                    <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-300"
                                            :class="parseFloat(req.physical_progress_pct) >= 95 ? 'bg-emerald-500' : (parseFloat(req.physical_progress_pct) >= 40 ? 'bg-amber-500' : 'bg-blue-600')"
                                            :style="{ width: `${parseFloat(req.physical_progress_pct || 0)}%` }"
                                        ></div>
                                    </div>
                                </td>

                                <!-- Inspection Status Badge & Last Updated -->
                                <td class="py-2.5 px-3 text-center">
                                    <span
                                        :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border leading-tight', getReportStatus(req).class]"
                                    >
                                        <span :class="['w-1.5 h-1.5 rounded-full', getReportStatus(req).dot]"></span>
                                        {{ getReportStatus(req).label }}
                                    </span>
                                    <div class="text-[10px] text-slate-500 mt-1 leading-tight whitespace-nowrap">
                                        <span class="font-medium">{{ formatDate(req.bb_monitoring_report?.updated_at || req.updated_at || req.submitted_at || req.created_at) }}</span>
                                        <span class="block text-[9px] text-slate-400 font-semibold">by {{ req.bb_monitoring_report?.auditor_name || 'Brahmaputra Board' }}</span>
                                    </div>
                                </td>

                                <!-- Action Buttons -->
                                <td class="py-2.5 px-3 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <Link
                                            v-if="isBBOrAdmin && (req.status === 'SUBMITTED_TO_BB' || req.status === 'BB_MONITORING_PENDING')"
                                            :href="route('monitoring-requests.report.create', req.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white font-bold text-[11px] transition shadow-2xs"
                                        >
                                            <span>{{ req.bb_monitoring_report?.status === 'DRAFT' ? '✏️ Resume' : '📋 Inspect' }}</span>
                                        </Link>

                                        <Link
                                            :href="route('fund-release.show', req.id)"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] border border-slate-300 transition shadow-2xs"
                                        >
                                            <span>Dossier &rarr;</span>
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty Row -->
                            <tr v-if="filteredRequests.length === 0">
                                <td colspan="12" class="p-8 text-center text-slate-500 italic">
                                    No monitoring inspection requests match your search or filter criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Summary & Pagination -->
                <div class="bg-slate-50 border-t border-slate-200 px-4 py-3 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
                    <div>
                        Showing <strong>{{ filteredRequests.length > 0 ? (currentPage - 1) * perPage + 1 : 0 }}</strong> to <strong>{{ Math.min(currentPage * perPage, filteredRequests.length) }}</strong> of <strong>{{ filteredRequests.length }}</strong> requests
                    </div>

                    <div v-if="totalPages > 1" class="flex items-center gap-1.5">
                        <button
                            type="button"
                            @click="goToPage(currentPage - 1)"
                            :disabled="currentPage <= 1"
                            class="px-2.5 py-1 rounded bg-white border border-slate-300 disabled:opacity-40 hover:bg-slate-100 font-semibold cursor-pointer"
                        >
                            &larr; Prev
                        </button>
                        <span class="px-2 font-bold text-slate-800">Page {{ currentPage }} of {{ totalPages }}</span>
                        <button
                            type="button"
                            @click="goToPage(currentPage + 1)"
                            :disabled="currentPage >= totalPages"
                            class="px-2.5 py-1 rounded bg-white border border-slate-300 disabled:opacity-40 hover:bg-slate-100 font-semibold cursor-pointer"
                        >
                            Next &rarr;
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
