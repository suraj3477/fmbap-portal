<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    analytics: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ state: 'ALL', basin: 'ALL', status: 'ALL', fy: 'ALL' }),
    },
    availableStates: {
        type: Array,
        default: () => [],
    },
    availableBasins: {
        type: Array,
        default: () => [],
    },
    userRole: String,
});

// Active Navigation Tab
const activeTab = ref('EXECUTIVE_CHARTS'); 
// Tabs: 'EXECUTIVE_CHARTS' | 'STATE_SCORECARD' | 'BASIN_RADAR' | 'AUDIT_RADAR' | 'BOTTLENECK_RADAR' | 'PARLIAMENTARY_REG'

// Filters state
const selectedState = ref(props.filters.state || 'ALL');
const selectedBasin = ref(props.filters.basin || 'ALL');
const selectedStatus = ref(props.filters.status || 'ALL');
const searchQuery = ref('');

// Sorting in Parliamentary Register
const sortField = ref('sanctioned_amount_cr');
const sortAsc = ref(false);

// Pagination in Parliamentary Register
const currentPage = ref(1);
const pageSize = ref(15); // 10, 15, 25, 50, -1 (All)

// 360° Scheme Drawer
const selectedScheme = ref(null);
const isDrawerOpen = ref(false);

const openSchemeDrawer = (scheme) => {
    selectedScheme.value = scheme;
    isDrawerOpen.value = true;
};

const closeSchemeDrawer = () => {
    isDrawerOpen.value = false;
    selectedScheme.value = null;
};

// State Expand Details
const expandedState = ref(null);
const toggleExpandState = (stateName) => {
    expandedState.value = expandedState.value === stateName ? null : stateName;
};

// State Details Modal
const isStateModalOpen = ref(false);
const selectedStateForModal = ref(null);

const openStateModal = (stateObj) => {
    selectedStateForModal.value = stateObj;
    isStateModalOpen.value = true;
};

const closeStateModal = () => {
    isStateModalOpen.value = false;
    selectedStateForModal.value = null;
};

const schemesForSelectedState = computed(() => {
    if (!selectedStateForModal.value) return [];
    const stateName = selectedStateForModal.value.state_name;
    const list = props.analytics?.parliamentary_list || [];
    return list.filter(s => s.state === stateName);
});

// Tooltip state for SVG Charts
const hoveredDonut = ref(null);

// Status Badge Formatting & Standardization
const formatStatus = (status, pct) => {
    const s = (status || '').toLowerCase().trim();
    if (s === 'completed' || pct >= 100) return 'Completed';
    if (s.includes('foreclosed')) return 'Foreclosed / Stalled';
    if (s === 'not started' || (s.includes('not') && (pct === null || pct <= 0))) return 'Not Started (0%)';
    return `Ongoing (${pct || 0}%)`;
};

const statusBadgeClass = (status, pct) => {
    const s = (status || '').toLowerCase().trim();
    if (s === 'completed' || pct >= 100) {
        return 'bg-emerald-100 text-emerald-900 border border-emerald-300';
    }
    if (s.includes('foreclosed') || s.includes('stalled')) {
        return 'bg-rose-100 text-rose-900 border border-rose-300';
    }
    if (s === 'not started' || (s.includes('not') && (pct === null || pct <= 0))) {
        return 'bg-slate-100 text-slate-700 border border-slate-300';
    }
    return 'bg-blue-100 text-blue-900 border border-blue-300 font-extrabold';
};

// Clipboard Copy Feedback
const copySuccess = ref(false);
const selectedSchemeIds = ref([]);

const toggleSelectAll = (event) => {
    if (event.target.checked) {
        selectedSchemeIds.value = paginatedParliamentaryList.value.map(s => s.id);
    } else {
        selectedSchemeIds.value = [];
    }
};

const copySelectedForPQ = () => {
    const listToCopy = filteredParliamentaryList.value.filter(s =>
        selectedSchemeIds.value.length === 0 || selectedSchemeIds.value.includes(s.id)
    );

    if (listToCopy.length === 0) return;

    let text = "Sl No.\tScheme Code\tScheme Name\tState\tDistrict\tRiver Basin\tSanctioned (₹ Cr)\tCentral Released (₹ Cr)\tBalance (₹ Cr)\tPhysical (%)\tFinancial (%)\tStatus\n";
    listToCopy.forEach((s, idx) => {
        text += `${idx + 1}\t${s.scheme_code}\t${s.scheme_name}\t${s.state}\t${s.district}\t${s.river_basin}\t${s.sanctioned_amount_cr}\t${s.released_central_cr}\t${s.balance_central_cr}\t${s.physical_progress_pct}%\t${s.financial_progress_pct}%\t${formatStatus(s.physical_status, s.physical_progress_pct)}\n`;
    });

    navigator.clipboard.writeText(text).then(() => {
        copySuccess.value = true;
        setTimeout(() => { copySuccess.value = false; }, 3000);
    });
};

const exportCsv = () => {
    const list = filteredParliamentaryList.value;
    if (list.length === 0) return;

    const headers = [
        'Sl No.',
        'Scheme Code',
        'Scheme Name',
        'State',
        'District',
        'Division',
        'River Basin',
        'Plan Period',
        'Sanctioned Amount (Cr)',
        'Central Released (Cr)',
        'Balance Central Share (Cr)',
        'Physical Progress (%)',
        'Financial Progress (%)',
        'Status',
        'Latest Sanction Order No',
        'Latest Sanction Date',
        'Latest Inspection Date',
    ];

    const rows = list.map((s, idx) => [
        idx + 1,
        `"${s.scheme_code}"`,
        `"${(s.scheme_name || '').replace(/"/g, '""')}"`,
        `"${s.state}"`,
        `"${s.district}"`,
        `"${s.division}"`,
        `"${s.river_basin}"`,
        `"${s.plan_period}"`,
        s.sanctioned_amount_cr,
        s.released_central_cr,
        s.balance_central_cr,
        s.physical_progress_pct,
        s.financial_progress_pct,
        `"${formatStatus(s.physical_status, s.physical_progress_pct)}"`,
        `"${s.latest_sanction_no}"`,
        `"${s.latest_sanction_date}"`,
        `"${s.latest_inspection_date}"`,
    ]);

    const csvContent = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `FMBAP_Parliamentary_MIS_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const triggerPrint = () => {
    window.print();
};

const applyFilters = () => {
    currentPage.value = 1;
    router.get(route('analytics.index'), {
        state: selectedState.value,
        basin: selectedBasin.value,
        status: selectedStatus.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    selectedState.value = 'ALL';
    selectedBasin.value = 'ALL';
    selectedStatus.value = 'ALL';
    searchQuery.value = '';
    currentPage.value = 1;
    applyFilters();
};

const excelExportUrl = computed(() => {
    const params = new URLSearchParams();
    if (selectedState.value !== 'ALL') params.append('state', selectedState.value);
    if (selectedBasin.value !== 'ALL') params.append('basin', selectedBasin.value);
    if (selectedStatus.value !== 'ALL') params.append('status', selectedStatus.value);
    return `${route('analytics.export-excel')}?${params.toString()}`;
});

const pdfExportUrl = computed(() => {
    const params = new URLSearchParams();
    if (selectedState.value !== 'ALL') params.append('state', selectedState.value);
    if (selectedBasin.value !== 'ALL') params.append('basin', selectedBasin.value);
    if (selectedStatus.value !== 'ALL') params.append('status', selectedStatus.value);
    return `${route('analytics.export-pdf')}?${params.toString()}`;
});

// Filtered & Sorted Parliamentary Register
const filteredParliamentaryList = computed(() => {
    let list = props.analytics.parliamentary_list || [];
    const q = searchQuery.value.trim().toLowerCase();

    if (q) {
        list = list.filter(s =>
            s.scheme_code?.toLowerCase().includes(q) ||
            s.scheme_name?.toLowerCase().includes(q) ||
            s.state?.toLowerCase().includes(q) ||
            s.district?.toLowerCase().includes(q) ||
            s.river_basin?.toLowerCase().includes(q)
        );
    }

    // Sort
    return [...list].sort((a, b) => {
        let valA = a[sortField.value];
        let valB = b[sortField.value];

        if (typeof valA === 'string' && !isNaN(parseFloat(valA.replace(/,/g, '')))) {
            valA = parseFloat(valA.replace(/,/g, ''));
            valB = parseFloat(valB.replace(/,/g, ''));
        }

        if (valA < valB) return sortAsc.value ? -1 : 1;
        if (valA > valB) return sortAsc.value ? 1 : -1;
        return 0;
    });
});

// Paginated view of Parliamentary Register
const totalFiltered = computed(() => filteredParliamentaryList.value.length);
const totalPages = computed(() => {
    if (pageSize.value === -1) return 1;
    return Math.ceil(totalFiltered.value / pageSize.value) || 1;
});

const paginatedParliamentaryList = computed(() => {
    if (pageSize.value === -1) return filteredParliamentaryList.value;
    const start = (currentPage.value - 1) * pageSize.value;
    return filteredParliamentaryList.value.slice(start, start + pageSize.value);
});

const sortBy = (field) => {
    if (sortField.value === field) {
        sortAsc.value = !sortAsc.value;
    } else {
        sortField.value = field;
        sortAsc.value = false;
    }
};

// Max sanctioned for SVG Bar Chart scaling
const maxSanctionedState = computed(() => {
    const list = props.analytics.state_scorecard || [];
    if (list.length === 0) return 100;
    return Math.max(...list.map(s => s.sanctioned_cr), 10);
});

// Donut Chart Segment computation
const donutSegments = computed(() => {
    const dist = props.analytics.progress_distribution || {};
    let cumulative = 0;
    const radius = 40;
    const circumference = 2 * Math.PI * radius; // ~251.32

    return Object.values(dist).map(item => {
        const strokeDasharray = `${(item.pct / 100) * circumference} ${circumference}`;
        const strokeDashoffset = -((cumulative / 100) * circumference);
        cumulative += item.pct;
        return {
            ...item,
            strokeDasharray,
            strokeDashoffset,
        };
    });
});
</script>

<template>
    <Head title="Executive MIS Strategic Intelligence Cockpit - FMBAP" />

    <AuthenticatedLayout>
        <!-- Header Ribbon -->
        <template #header>
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 print:hidden">
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <span class="p-2 bg-blue-100 text-[#0F4C9F] rounded-xl text-xl shadow-xs font-black">📈</span>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            Executive MIS &amp; Strategic Intelligence Cockpit
                        </h1>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-900 text-white shadow-xs">
                            MoJS / Brahmaputra Board HQ
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        High-precision financial velocity, verified ground progress, statutory GFR 12-A compliance, and Parliamentary oversight register.
                    </p>
                </div>

                <!-- Export Action Suite -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="triggerPrint"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl border border-slate-300 shadow-2xs transition cursor-pointer"
                        title="Print executive briefing note"
                    >
                        <span>🖨️ Print View</span>
                    </button>

                    <button
                        type="button"
                        @click="exportCsv"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl border border-slate-300 shadow-2xs transition cursor-pointer"
                        title="Export filtered records as CSV"
                    >
                        <span>📊 Export CSV</span>
                    </button>

                    <button
                        type="button"
                        @click="copySelectedForPQ"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer"
                        title="Copy tabular data for Parliamentary replies"
                    >
                        <span>{{ copySuccess ? '✅ Copied to Clipboard!' : '📋 Copy for PQ Reply' }}</span>
                    </button>

                    <a
                        :href="excelExportUrl"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer"
                    >
                        <span>📥 3-Sheet Excel (.xlsx)</span>
                    </a>

                    <a
                        :href="pdfExportUrl"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer"
                    >
                        <span>📄 Official PDF Brief</span>
                    </a>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <!-- ─── 1. TOP MACRO COMMAND RIBBON ─── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Macro 1: Total Statutory Outlay -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-xs transition space-y-2 relative overflow-hidden group">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-[#0F4C9F]"></div>
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="text-[11px] font-black uppercase tracking-wider text-slate-600">Total Statutory Outlay</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-blue-50 text-[#0F4C9F] border border-blue-200">
                            {{ analytics.macro.total_schemes }} Schemes
                        </span>
                    </div>
                    <div class="text-3xl font-black text-slate-900 leading-tight">
                        ₹{{ Number(analytics.macro.total_sanctioned_cr).toLocaleString('en-IN', { minimumFractionDigits: 2 }) }}
                        <span class="text-sm font-bold text-slate-400">Cr</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-[#0F4C9F] h-full rounded-full" style="width: 100%"></div>
                    </div>
                </div>

                <!-- Macro 2: Central Disbursal & Velocity -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-xs transition space-y-2 relative overflow-hidden group">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-emerald-600"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black uppercase tracking-wider text-emerald-800">Central Assistance Released</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            {{ analytics.macro.disbursal_velocity_pct }}% Velocity
                        </span>
                    </div>
                    <div class="text-3xl font-black text-emerald-950 leading-tight">
                        ₹{{ Number(analytics.macro.total_released_cr).toLocaleString('en-IN', { minimumFractionDigits: 2 }) }}
                        <span class="text-sm font-bold text-emerald-700">Cr</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-500 h-full rounded-full transition-all duration-700" :style="{ width: `${Math.min(100, analytics.macro.disbursal_velocity_pct)}%` }"></div>
                    </div>
                </div>

                <!-- Macro 3: Execution Portfolio Health -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-xs transition space-y-2 relative overflow-hidden group">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-amber-500"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black uppercase tracking-wider text-amber-900">Portfolio Execution Health</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-amber-100 text-amber-900 border border-amber-200">
                            {{ analytics.macro.ongoing_schemes }} Active
                        </span>
                    </div>
                    <div class="text-3xl font-black text-slate-900 leading-tight">
                        {{ analytics.macro.completed_schemes }} <span class="text-sm font-bold text-slate-400">/ {{ analytics.macro.total_schemes }} Completed</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden flex">
                        <div class="bg-emerald-500 h-full transition-all duration-700" :style="{ width: `${(analytics.macro.completed_schemes / (analytics.macro.total_schemes || 1)) * 100}%` }" title="Completed"></div>
                        <div class="bg-blue-500 h-full transition-all duration-700" :style="{ width: `${(analytics.macro.ongoing_schemes / (analytics.macro.total_schemes || 1)) * 100}%` }" title="Ongoing"></div>
                    </div>
                </div>

                <!-- Macro 4: Average Physical Progress & GFR 12-A -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-xs transition space-y-2 relative overflow-hidden group">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-blue-600"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black uppercase tracking-wider text-blue-900">Verified Physical Avg.</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-blue-100 text-blue-900 border border-blue-200">
                            BB Validated
                        </span>
                    </div>
                    <div class="text-3xl font-black text-blue-950 leading-tight">
                        {{ analytics.macro.avg_physical_progress }}%
                    </div>
                    <div class="w-full bg-blue-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-[#0F4C9F] h-full rounded-full transition-all duration-700" :style="{ width: `${Math.min(100, analytics.macro.avg_physical_progress)}%` }"></div>
                    </div>
                </div>

            </div>


            <!-- ─── 3. INTERACTIVE FILTER BAR ─── -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs flex flex-wrap items-center justify-between gap-4 text-xs print:hidden">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- State Filter -->
                    <div class="flex items-center gap-1.5">
                        <label class="font-bold text-slate-600">State:</label>
                        <select
                            v-model="selectedState"
                            @change="applyFilters"
                            class="rounded-xl border-slate-300 text-xs py-1.5 pr-8 font-semibold text-slate-800 focus:ring-[#0F4C9F]"
                        >
                            <option value="ALL">All Participating States ({{ availableStates.length }})</option>
                            <option v-for="st in availableStates" :key="st" :value="st">{{ st }}</option>
                        </select>
                    </div>

                    <!-- River Basin Filter -->
                    <div class="flex items-center gap-1.5">
                        <label class="font-bold text-slate-600">River Basin:</label>
                        <select
                            v-model="selectedBasin"
                            @change="applyFilters"
                            class="rounded-xl border-slate-300 text-xs py-1.5 pr-8 font-semibold text-slate-800 focus:ring-[#0F4C9F]"
                        >
                            <option value="ALL">All Catchments ({{ availableBasins.length }})</option>
                            <option v-for="b in availableBasins" :key="b" :value="b">{{ b }}</option>
                        </select>
                    </div>

                    <!-- Physical Status Filter -->
                    <div class="flex items-center gap-1.5">
                        <label class="font-bold text-slate-600">Status:</label>
                        <select
                            v-model="selectedStatus"
                            @change="applyFilters"
                            class="rounded-xl border-slate-300 text-xs py-1.5 pr-8 font-semibold text-slate-800 focus:ring-[#0F4C9F]"
                        >
                            <option value="ALL">All Execution Statuses</option>
                            <option value="ONGOING">Active Ongoing Only</option>
                            <option value="COMPLETED">100% Completed Only</option>
                        </select>
                    </div>

                    <!-- Reset Button -->
                    <button
                        type="button"
                        v-if="selectedState !== 'ALL' || selectedBasin !== 'ALL' || selectedStatus !== 'ALL'"
                        @click="resetFilters"
                        class="text-blue-700 hover:text-blue-900 font-extrabold text-xs cursor-pointer px-2 py-1 rounded bg-blue-50 border border-blue-200 transition"
                    >
                        ✕ Clear Filters
                    </button>
                </div>

                <div class="text-slate-500 font-semibold text-[11px] flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Monitoring <strong class="text-slate-900 font-black">{{ analytics.macro.total_schemes }}</strong> Schemes in Scope</span>
                </div>
            </div>

            <!-- ─── 4. EXECUTIVE TAB NAVIGATION ─── -->
            <div class="flex border-b border-slate-200 bg-white rounded-t-2xl px-4 pt-2 gap-1 overflow-x-auto text-xs font-black shadow-2xs print:hidden">
                
                <button
                    type="button"
                    @click="activeTab = 'EXECUTIVE_CHARTS'"
                    :class="activeTab === 'EXECUTIVE_CHARTS' ? 'border-b-2 border-[#0F4C9F] text-[#0F4C9F] bg-blue-50/50' : 'text-slate-500 hover:text-slate-900'"
                    class="py-3 px-3.5 transition cursor-pointer flex items-center gap-2 rounded-t-xl"
                >
                    <span>📊 Executive Visual Insights</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'STATE_SCORECARD'"
                    :class="activeTab === 'STATE_SCORECARD' ? 'border-b-2 border-[#0F4C9F] text-[#0F4C9F] bg-blue-50/50' : 'text-slate-500 hover:text-slate-900'"
                    class="py-3 px-3.5 transition cursor-pointer flex items-center gap-2 rounded-t-xl"
                >
                    <span>🏆 Inter-State Benchmark (CPI)</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'BASIN_RADAR'"
                    :class="activeTab === 'BASIN_RADAR' ? 'border-b-2 border-[#0F4C9F] text-[#0F4C9F] bg-blue-50/50' : 'text-slate-500 hover:text-slate-900'"
                    class="py-3 px-3.5 transition cursor-pointer flex items-center gap-2 rounded-t-xl"
                >
                    <span>🌊 River Basin Matrix</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'BOTTLENECK_RADAR'"
                    :class="activeTab === 'BOTTLENECK_RADAR' ? 'border-b-2 border-[#0F4C9F] text-[#0F4C9F] bg-blue-50/50' : 'text-slate-500 hover:text-slate-900'"
                    class="py-3 px-3.5 transition cursor-pointer flex items-center gap-2 rounded-t-xl"
                >
                    <span>⏱️ Workflow TAT Diagnostics</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'PARLIAMENTARY_REG'"
                    :class="activeTab === 'PARLIAMENTARY_REG' ? 'border-b-2 border-[#0F4C9F] text-[#0F4C9F] bg-blue-50/50' : 'text-slate-500 hover:text-slate-900'"
                    class="py-3 px-3.5 transition cursor-pointer flex items-center gap-2 rounded-t-xl"
                >
                    <span>🏛️ Parliamentary Master Register</span>
                </button>

            </div>

            <!-- ─── 5. TAB CONTENT PANELS ─── -->
            <div class="bg-white rounded-b-2xl border border-slate-200 border-t-0 p-6 shadow-2xs">

                <!-- ═════ TAB 1: EXECUTIVE VISUAL INSIGHTS & INTERACTIVE SVG CHARTS ═════ -->
                <div v-if="activeTab === 'EXECUTIVE_CHARTS'" class="space-y-8">
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="text-base font-black text-slate-900">National Strategic Visual Analytics</h3>
                            <p class="text-xs text-slate-500">Real-time allocation vs disbursal velocity, progress distribution, and work-type breakdown.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        <!-- CHART 1: State Outlay vs Disbursal Comparison (Click to Open State Modal) -->
                        <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-4 relative">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-black text-sm text-slate-900">State Sanctioned Outlay vs Central Released (₹ Cr)</h4>
                                    <p class="text-[11px] text-slate-500">Horizontal comparative breakdown across states &bull; <span class="text-[#0F4C9F] font-bold">Click state to view full profile</span></p>
                                </div>
                                <div class="flex items-center gap-3 text-[10px] font-bold">
                                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-300 inline-block"></span> Sanctioned</span>
                                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-500 inline-block"></span> Released</span>
                                </div>
                            </div>

                            <!-- Bar Rows -->
                            <div class="space-y-3 pt-2">
                                <div
                                    v-for="st in analytics.state_scorecard.slice(0, 8)"
                                    :key="st.state_name"
                                    class="space-y-1.5 p-2 rounded-xl hover:bg-white border border-transparent hover:border-slate-200 hover:shadow-xs transition cursor-pointer group"
                                    @click="openStateModal(st)"
                                    :title="`Click to view ${st.state_name} complete profile`"
                                >
                                    <div class="flex justify-between text-xs font-bold text-slate-700">
                                        <span class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-[#0F4C9F]"></span>
                                            {{ st.state_name }}
                                        </span>
                                        <span class="font-mono text-[11px]">
                                            ₹{{ st.released_cr }} Cr / <span class="text-slate-400">₹{{ st.sanctioned_cr }} Cr</span>
                                            <span class="ml-1 text-emerald-700 font-black">({{ st.disbursal_pct }}%)</span>
                                        </span>
                                    </div>
                                    <div class="h-4 bg-slate-200 rounded-full overflow-hidden flex relative">
                                        <!-- Sanctioned Outlay Bar (Base) -->
                                        <div
                                            class="h-full bg-slate-300 rounded-full"
                                            :style="{ width: `${(st.sanctioned_cr / maxSanctionedState) * 100}%` }"
                                        ></div>
                                        <!-- Released Amount Bar (Overlay) -->
                                        <div
                                            class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full absolute top-0 left-0 transition-all duration-700"
                                            :style="{ width: `${(st.released_cr / maxSanctionedState) * 100}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CHART 2: Physical Progress Donut Distribution (Interactive SVG) -->
                        <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-black text-sm text-slate-900">Physical Progress Stage Distribution</h4>
                                    <p class="text-[11px] text-slate-500">Portfolio segment breakdown across execution milestones</p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-[#0F4C9F]">
                                    {{ analytics.macro.total_schemes }} Total Schemes
                                </span>
                            </div>

                            <div class="flex flex-col sm:flex-row items-center justify-center gap-6 pt-2">
                                <!-- Donut SVG -->
                                <div class="relative w-44 h-44 shrink-0 flex items-center justify-center">
                                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                                        <!-- Background Circle -->
                                        <circle
                                            cx="50"
                                            cy="50"
                                            r="40"
                                            stroke="#e2e8f0"
                                            stroke-width="12"
                                            fill="transparent"
                                        />
                                        <!-- Segments -->
                                        <circle
                                            v-for="(seg, idx) in donutSegments"
                                            :key="idx"
                                            cx="50"
                                            cy="50"
                                            r="40"
                                            :stroke="seg.color"
                                            stroke-width="12"
                                            fill="transparent"
                                            :stroke-dasharray="seg.strokeDasharray"
                                            :stroke-dashoffset="seg.strokeDashoffset"
                                            class="transition-all duration-700 cursor-pointer hover:stroke-width-14"
                                            @mouseenter="hoveredDonut = seg"
                                            @mouseleave="hoveredDonut = null"
                                        />
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                                        <span class="text-2xl font-black text-slate-900 leading-none">
                                            {{ hoveredDonut ? hoveredDonut.count : analytics.macro.completed_schemes }}
                                        </span>
                                        <span class="text-[10px] font-bold text-slate-500 uppercase mt-0.5">
                                            {{ hoveredDonut ? hoveredDonut.label.split(' ')[0] : 'Completed' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Legend -->
                                <div class="space-y-1.5 text-xs w-full">
                                    <div
                                        v-for="(seg, k) in analytics.progress_distribution"
                                        :key="k"
                                        class="flex items-center justify-between p-2 rounded-xl hover:bg-white transition cursor-pointer"
                                        @mouseenter="hoveredDonut = seg"
                                        @mouseleave="hoveredDonut = null"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: seg.color }"></span>
                                            <span class="font-bold text-slate-700 text-[11px]">{{ seg.label }}</span>
                                        </div>
                                        <div class="font-mono font-bold text-slate-900 text-[11px]">
                                            {{ seg.count }} <span class="text-slate-400 font-normal">({{ seg.pct }}%)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Work Component Classification Matrix -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="font-black text-sm text-slate-900">Work Category &amp; Structural Measure Classification</h4>
                            <span class="text-[11px] text-slate-500 font-semibold">Flood Mitigation Component Analysis</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div v-for="cat in analytics.category_matrix" :key="cat.category" class="p-4 rounded-xl border border-slate-200 bg-white shadow-2xs space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-black text-slate-900 truncate">{{ cat.category }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-blue-50 text-[#0F4C9F]">
                                        {{ cat.schemes_count }} Schemes
                                    </span>
                                </div>
                                <div class="text-xl font-black text-slate-900">
                                    ₹{{ cat.sanctioned_cr }} <span class="text-xs font-bold text-slate-400">Cr</span>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex justify-between text-[10px] font-bold text-slate-500">
                                        <span>Average Physical</span>
                                        <span class="text-[#0F4C9F] font-black">{{ cat.avg_physical_pct }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-[#0F4C9F] h-full rounded-full" :style="{ width: `${cat.avg_physical_pct}%` }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ═════ TAB 2: INTER-STATE BENCHMARK & SCORECARD ═════ -->
                <div v-if="activeTab === 'STATE_SCORECARD'" class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="text-sm sm:text-base font-black text-slate-900">
                                Inter-State Flood Management Benchmark Index (CPI)
                            </h3>
                            <p class="text-xs text-slate-500">
                                Ranked by Composite Performance Index (40% Physical Progress + 35% Disbursal Velocity + 15% Completion Rate + 10% Claim Clearance).
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-50 text-slate-700 uppercase font-black text-[10px] tracking-wider border-y border-slate-200">
                                <tr>
                                    <th class="py-3.5 px-4">Rank &amp; State WRD</th>
                                    <th class="py-3.5 px-3 text-center">CPI Index</th>
                                    <th class="py-3.5 px-3 text-center">Schemes (Done)</th>
                                    <th class="py-3.5 px-3 text-right">Sanctioned Outlay</th>
                                    <th class="py-3.5 px-3 text-right">Central Released</th>
                                    <th class="py-3.5 px-3 text-center">Disbursal %</th>
                                    <th class="py-3.5 px-3 text-center">Physical %</th>
                                    <th class="py-3.5 px-3 text-center">Active In-Flight</th>
                                    <th class="py-3.5 px-4 text-center">Performance Tier</th>
                                    <th class="py-3.5 px-3 text-center">Drill-Down</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template v-for="(st, idx) in analytics.state_scorecard" :key="st.state_name">
                                    <tr class="hover:bg-slate-50 transition cursor-pointer" @click="openStateModal(st)">
                                        <td class="py-3.5 px-4 font-black text-slate-900 flex items-center gap-2.5">
                                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black"
                                                :class="idx === 0 ? 'bg-amber-100 text-amber-900 border border-amber-300 shadow-2xs' : 'bg-slate-100 text-slate-700'">
                                                {{ idx + 1 }}
                                            </span>
                                            <span class="text-sm font-bold text-slate-900 hover:text-[#0F4C9F] transition">{{ st.state_name }}</span>
                                        </td>
                                        <td class="py-3.5 px-3 text-center font-black text-sm text-[#0F4C9F]">
                                            {{ st.cpi_score }}
                                        </td>
                                        <td class="py-3.5 px-3 text-center font-semibold text-slate-800">
                                            {{ st.schemes_count }} <span class="text-[10px] text-emerald-700 font-bold">({{ st.completed_count }} done)</span>
                                        </td>
                                        <td class="py-3.5 px-3 text-right font-mono font-bold text-slate-900">
                                            ₹{{ st.sanctioned_cr }} Cr
                                        </td>
                                        <td class="py-3.5 px-3 text-right font-mono font-bold text-emerald-700">
                                            ₹{{ st.released_cr }} Cr
                                        </td>
                                        <td class="py-3.5 px-3 text-center font-bold text-slate-800">
                                            {{ st.disbursal_pct }}%
                                        </td>
                                        <td class="py-3.5 px-3 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <div class="w-16 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                    <div class="bg-[#0F4C9F] h-full rounded-full" :style="{ width: `${st.avg_physical_pct}%` }"></div>
                                                </div>
                                                <span class="font-black text-[#0F4C9F]">{{ st.avg_physical_pct }}%</span>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-3 text-center">
                                            <span v-if="st.pending_claims_count > 0" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-200">
                                                {{ st.pending_claims_count }} in review
                                            </span>
                                            <span v-else class="text-[10px] text-slate-400 font-medium">Clear</span>
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <span
                                                :class="{
                                                    'bg-emerald-100 text-emerald-900 border-emerald-200': st.tier.includes('Tier 1'),
                                                    'bg-blue-100 text-blue-900 border-blue-200': st.tier.includes('Tier 2'),
                                                    'bg-rose-100 text-rose-900 border-rose-200': st.tier.includes('Tier 3'),
                                                }"
                                                class="px-2.5 py-1 rounded-full text-[10px] font-black border inline-block"
                                            >
                                                {{ st.tier }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-3 text-center">
                                            <button
                                                type="button"
                                                @click.stop="openStateModal(st)"
                                                class="px-2.5 py-1 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white rounded-lg text-[10px] font-bold transition cursor-pointer"
                                            >
                                                View Box ↗
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Expanded State Administrative Breakdown & Schemes Sub-Table -->
                                    <tr v-if="expandedState === st.state_name" class="bg-blue-50/40">
                                        <td colspan="10" class="p-4 border-b border-blue-100 space-y-3">
                                            <div class="flex flex-wrap items-center justify-between gap-4 text-xs">
                                                <div>
                                                    <span class="font-black text-slate-900">{{ st.state_name }} WRD Operational Summary:</span>
                                                    <span class="text-slate-600 ml-2">Covering {{ st.divisions_count || 1 }} Executive Divisions across {{ st.districts_count || 1 }} Districts.</span>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <span class="text-[11px] font-semibold text-slate-700">Central Outlay: ₹{{ st.central_outlay_cr }} Cr</span>
                                                    <span class="text-[11px] font-semibold text-slate-700">State Outlay: ₹{{ st.state_outlay_cr }} Cr</span>
                                                    <button
                                                        type="button"
                                                        @click.stop="selectedState = st.state_name; applyFilters();"
                                                        class="px-2.5 py-1 bg-[#0F4C9F] text-white rounded-lg text-[10px] font-bold hover:bg-[#0c3c7d] transition cursor-pointer"
                                                    >
                                                        Filter Cockpit to {{ st.state_name }}
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Schemes Under this State -->
                                            <div class="bg-white rounded-xl border border-blue-200 overflow-hidden shadow-2xs">
                                                <table class="w-full text-xs text-left">
                                                    <thead class="bg-blue-100/60 text-blue-900 uppercase font-black text-[9px]">
                                                        <tr>
                                                            <th class="py-2 px-3">Scheme Code</th>
                                                            <th class="py-2 px-3">Title &amp; Division</th>
                                                            <th class="py-2 px-3 text-right">Sanctioned</th>
                                                            <th class="py-2 px-3 text-right">Released</th>
                                                            <th class="py-2 px-3 text-center">Physical %</th>
                                                            <th class="py-2 px-3 text-center">Status</th>
                                                            <th class="py-2 px-3 text-center">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        <tr v-for="sch in filteredParliamentaryList.filter(s => s.state === st.state_name)" :key="sch.id" class="hover:bg-slate-50 transition">
                                                            <td class="py-2 px-3 font-mono font-bold text-[#0F4C9F]">{{ sch.scheme_code }}</td>
                                                            <td class="py-2 px-3 font-semibold text-slate-800 max-w-xs truncate">{{ sch.scheme_name }}</td>
                                                            <td class="py-2 px-3 text-right font-mono font-bold">₹{{ sch.sanctioned_amount_cr }} Cr</td>
                                                            <td class="py-2 px-3 text-right font-mono font-bold text-emerald-700">₹{{ sch.released_central_cr }} Cr</td>
                                                            <td class="py-2 px-3 text-center font-bold text-blue-900">{{ sch.physical_progress_pct }}%</td>
                                                            <td class="py-2 px-3 text-center">
                                                                <span class="px-2 py-0.5 rounded text-[9px] font-bold border"
                                                                    :class="statusBadgeClass(sch.physical_status, sch.physical_progress_pct)">
                                                                    {{ formatStatus(sch.physical_status, sch.physical_progress_pct) }}
                                                                </span>
                                                            </td>
                                                            <td class="py-2 px-3 text-center">
                                                                <button
                                                                    type="button"
                                                                    @click.stop="openSchemeDrawer(sch)"
                                                                    class="text-[10px] text-[#0F4C9F] hover:underline font-bold cursor-pointer"
                                                                >
                                                                    Inspect 360°
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ═════ TAB 3: RIVER BASIN MATRIX ═════ -->
                <div v-if="activeTab === 'BASIN_RADAR'" class="space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h3 class="text-base font-black text-slate-900">River Basin &amp; Catchment Vulnerability Matrix</h3>
                        <p class="text-xs text-slate-500">Distribution of anti-erosion dykes and flood mitigation outlays across river systems.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="basin in analytics.basin_matrix" :key="basin.basin_name" class="p-5 rounded-2xl border border-slate-200 bg-slate-50/60 space-y-3 hover:shadow-xs transition">
                            <div class="flex items-center justify-between">
                                <h4 class="font-black text-sm text-slate-900 flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-[#0F4C9F]"></span>
                                    {{ basin.basin_name }} Basin
                                </h4>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-blue-100 text-[#0F4C9F]">
                                    {{ basin.schemes_count }} Schemes
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-xs pt-1">
                                <div>
                                    <span class="text-slate-500 text-[10px] block font-bold uppercase">Sanctioned Outlay</span>
                                    <span class="font-black text-slate-900 text-base">₹{{ basin.sanctioned_cr }} Cr</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 text-[10px] block font-bold uppercase">Average Physical</span>
                                    <span class="font-black text-[#0F4C9F] text-base">{{ basin.avg_physical_pct }}%</span>
                                </div>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden mt-2">
                                <div class="bg-[#0F4C9F] h-full rounded-full transition-all duration-500" :style="{ width: `${basin.avg_physical_pct}%` }"></div>
                            </div>
                            <div class="text-[10px] text-slate-500 flex justify-between font-semibold pt-1">
                                <span>Completed: {{ basin.completed_count || 0 }}</span>
                                <span>Active Ongoing: {{ basin.schemes_count - (basin.completed_count || 0) }}</span>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- ═════ TAB 5: WORKFLOW TAT & BOTTLENECK DIAGNOSTICS ═════ -->
                <div v-if="activeTab === 'BOTTLENECK_RADAR'" class="space-y-6">
                    <div class="border-b border-slate-100 pb-3">
                        <h3 class="text-base font-black text-slate-900">Workflow Pipeline &amp; Turnaround Time (TAT)</h3>
                        <p class="text-xs text-slate-500">End-to-end monitoring from State WRD initial claim to Ministry sanction release order.</p>
                    </div>

                    <!-- 4-Stage Pipeline -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="p-5 rounded-2xl border border-blue-200 bg-blue-50/50 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase text-blue-700">Stage 1: Field Monitoring</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-900">BB Inspection</span>
                            </div>
                            <div class="text-3xl font-black text-blue-950">{{ analytics.bottlenecks.submitted_to_bb }}</div>
                            <div class="text-xs text-blue-700 font-medium">Claims pending site inspection &amp; baseline scrutiny</div>
                        </div>

                        <div class="p-5 rounded-2xl border border-purple-200 bg-purple-50/50 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase text-purple-700">Stage 2: Ministry Sanction</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-900">MoJS Review</span>
                            </div>
                            <div class="text-3xl font-black text-purple-950">{{ analytics.bottlenecks.forwarded_to_mojs }}</div>
                            <div class="text-xs text-purple-700 font-medium">Claims forwarded to Ministry for financial clearance</div>
                        </div>

                        <div class="p-5 rounded-2xl border border-amber-200 bg-amber-50/50 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase text-amber-700">Stage 3: Needs Correction</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900">With State</span>
                            </div>
                            <div class="text-3xl font-black text-amber-950">{{ analytics.bottlenecks.needs_correction }}</div>
                            <div class="text-xs text-amber-700 font-medium">Returned to State WRD for discrepancy resolution</div>
                        </div>

                        <div class="p-5 rounded-2xl border border-emerald-200 bg-emerald-50/50 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase text-emerald-700">Stage 4: Sanctioned &amp; Disbursed</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-900">Approved</span>
                            </div>
                            <div class="text-3xl font-black text-emerald-950">{{ analytics.bottlenecks.approved }}</div>
                            <div class="text-xs text-emerald-700 font-medium">Payment claims disbursed to State Single Nodal Agency</div>
                        </div>
                    </div>

                </div>

                <!-- ═════ TAB 6: PARLIAMENTARY MASTER REGISTER ═════ -->
                <div v-if="activeTab === 'PARLIAMENTARY_REG'" class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="text-base font-black text-slate-900">Parliamentary Question (PQ) Live Master Register</h3>
                            <p class="text-xs text-slate-500">Official consolidated register formatted for Lok Sabha / Rajya Sabha Starred &amp; Unstarred Questions.</p>
                        </div>

                        <!-- Live Table Search Input -->
                        <div class="w-full sm:w-72 relative">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search scheme code, state, title..."
                                class="w-full rounded-xl border-slate-300 text-xs py-2 pl-8 focus:ring-[#0F4C9F]"
                            />
                            <span class="absolute left-2.5 top-2.5 text-slate-400 text-xs">🔍</span>
                        </div>
                    </div>

                    <!-- Action strip for selection & page sizing -->
                    <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 cursor-pointer font-bold">
                                <input
                                    type="checkbox"
                                    @change="toggleSelectAll"
                                    class="rounded border-slate-300 text-[#0F4C9F] focus:ring-[#0F4C9F]"
                                />
                                <span>Select on Page</span>
                            </label>
                            <span v-if="selectedSchemeIds.length > 0" class="text-[#0F4C9F] font-bold">
                                ({{ selectedSchemeIds.length }} selected)
                            </span>
                            <span class="text-slate-400">|</span>
                            <div class="flex items-center gap-1.5">
                                <span>Per Page:</span>
                                <select
                                    v-model="pageSize"
                                    class="rounded-lg border-slate-300 text-xs py-1 pr-6 font-semibold"
                                >
                                    <option :value="10">10</option>
                                    <option :value="15">15</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                    <option :value="-1">All ({{ totalFiltered }})</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="copySelectedForPQ"
                                class="text-xs text-[#0F4C9F] hover:underline font-extrabold cursor-pointer"
                            >
                                {{ copySuccess ? '✅ Copied to Clipboard!' : '📋 Copy Selected for Official Note' }}
                            </button>
                        </div>
                    </div>

                    <!-- Responsive Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-50 text-slate-700 uppercase font-black text-[10px] tracking-wider border-y border-slate-200">
                                <tr>
                                    <th class="py-3 px-2 text-center w-8">#</th>
                                    <th @click="sortBy('scheme_code')" class="py-3 px-3 cursor-pointer hover:text-[#0F4C9F]">Scheme Code ⬍</th>
                                    <th @click="sortBy('scheme_name')" class="py-3 px-3 cursor-pointer hover:text-[#0F4C9F]">Scheme Name ⬍</th>
                                    <th @click="sortBy('state')" class="py-3 px-3 cursor-pointer hover:text-[#0F4C9F]">State / Division ⬍</th>
                                    <th @click="sortBy('river_basin')" class="py-3 px-3 cursor-pointer hover:text-[#0F4C9F]">River Basin ⬍</th>
                                    <th @click="sortBy('sanctioned_amount_cr')" class="py-3 px-3 text-right cursor-pointer hover:text-[#0F4C9F]">Sanctioned (₹ Cr) ⬍</th>
                                    <th @click="sortBy('released_central_cr')" class="py-3 px-3 text-right cursor-pointer hover:text-[#0F4C9F]">Released (₹ Cr) ⬍</th>
                                    <th @click="sortBy('balance_central_cr')" class="py-3 px-3 text-right cursor-pointer hover:text-[#0F4C9F]">Balance (₹ Cr) ⬍</th>
                                    <th @click="sortBy('physical_progress_pct')" class="py-3 px-3 text-center cursor-pointer hover:text-[#0F4C9F]">Physical % ⬍</th>
                                    <th class="py-3 px-3 text-center">Status</th>
                                    <th class="py-3 px-3 text-center">Inspect</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="s in paginatedParliamentaryList" :key="s.id" class="hover:bg-slate-50 transition">
                                    <td class="py-3 px-2 text-center">
                                        <input
                                            type="checkbox"
                                            :value="s.id"
                                            v-model="selectedSchemeIds"
                                            class="rounded border-slate-300 text-[#0F4C9F] focus:ring-[#0F4C9F]"
                                        />
                                    </td>
                                    <td class="py-3 px-3 font-mono font-black text-[#0F4C9F]">{{ s.scheme_code }}</td>
                                    <td class="py-3 px-3 font-semibold text-slate-800 max-w-xs truncate" :title="s.scheme_name">{{ s.scheme_name }}</td>
                                    <td class="py-3 px-3 text-slate-700">
                                        <div class="font-bold">{{ s.state }}</div>
                                        <div class="text-[10px] text-slate-400">{{ s.district }}</div>
                                    </td>
                                    <td class="py-3 px-3 text-slate-700">{{ s.river_basin }}</td>
                                    <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">₹{{ s.sanctioned_amount_cr }}</td>
                                    <td class="py-3 px-3 text-right font-mono font-black text-emerald-700">₹{{ s.released_central_cr }}</td>
                                    <td class="py-3 px-3 text-right font-mono font-bold text-slate-500">₹{{ s.balance_central_cr }}</td>
                                    <td class="py-3 px-3 text-center font-black text-[#0F4C9F]">
                                        {{ s.physical_progress_pct }}%
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold border"
                                            :class="statusBadgeClass(s.physical_status, s.physical_progress_pct)">
                                            {{ formatStatus(s.physical_status, s.physical_progress_pct) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <button
                                            type="button"
                                            @click="openSchemeDrawer(s)"
                                            class="px-2.5 py-1 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white rounded-lg text-[10px] font-black transition cursor-pointer"
                                        >
                                            360° View
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100 text-xs">
                        <div class="text-slate-500 font-semibold">
                            Showing <strong class="text-slate-900">{{ Math.min(1 + (currentPage - 1) * (pageSize === -1 ? totalFiltered : pageSize), totalFiltered) }}</strong>
                            to <strong class="text-slate-900">{{ pageSize === -1 ? totalFiltered : Math.min(currentPage * pageSize, totalFiltered) }}</strong>
                            of <strong class="text-slate-900">{{ totalFiltered }}</strong> schemes
                        </div>

                        <div v-if="totalPages > 1" class="flex items-center gap-1.5">
                            <button
                                type="button"
                                :disabled="currentPage <= 1"
                                @click="currentPage--"
                                class="px-2.5 py-1 rounded-lg border border-slate-300 font-bold disabled:opacity-40 hover:bg-slate-50 transition cursor-pointer"
                            >
                                ◀ Previous
                            </button>
                            <span class="px-2 font-bold text-slate-700">Page {{ currentPage }} of {{ totalPages }}</span>
                            <button
                                type="button"
                                :disabled="currentPage >= totalPages"
                                @click="currentPage++"
                                class="px-2.5 py-1 rounded-lg border border-slate-300 font-bold disabled:opacity-40 hover:bg-slate-50 transition cursor-pointer"
                            >
                                Next ▶
                            </button>
                        </div>
                    </div>

                </div>

            </div>

        </div>

        <!-- ─── 6. STATE DETAILS MODAL DIALOG ("IN A BOX") ─── -->
        <div
            v-if="isStateModalOpen && selectedStateForModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 print:hidden"
            @click.self="closeStateModal"
        >
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-3xl overflow-hidden my-8">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-[#071F42] via-[#0F4C9F] to-[#0c3c7d] text-white p-5 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl p-2 bg-white/10 rounded-xl">🏛️</span>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-base sm:text-lg font-black tracking-tight text-white">
                                    {{ selectedStateForModal.state_name }} Water Resources Department
                                </h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-white/20 text-white">
                                    {{ selectedStateForModal.schemes_count }} Schemes
                                </span>
                            </div>
                            <p class="text-xs text-blue-200 mt-0.5">
                                Inter-State Benchmark Profile • FMBAP Special Category (90:10 Pattern)
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeStateModal"
                        class="p-1.5 rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition text-lg cursor-pointer"
                        title="Close modal"
                    >
                        ✕
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                    
                    <!-- KPI Cards Grid (In a Box) -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 space-y-1">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Sanctioned Outlay</span>
                            <div class="text-lg font-black text-slate-900">₹{{ selectedStateForModal.sanctioned_cr }} Cr</div>
                            <div class="text-[10px] text-slate-500 font-medium">
                                Central 90%: ₹{{ selectedStateForModal.central_outlay_cr }} Cr
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl border border-emerald-200 bg-emerald-50/50 space-y-1">
                            <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider block">Central Released</span>
                            <div class="text-lg font-black text-emerald-950">₹{{ selectedStateForModal.released_cr }} Cr</div>
                            <div class="text-[10px] text-emerald-700 font-bold">
                                {{ selectedStateForModal.disbursal_pct }}% Disbursal Velocity
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl border border-blue-200 bg-blue-50/50 space-y-1">
                            <span class="text-[10px] font-bold text-blue-800 uppercase tracking-wider block">Verified Physical</span>
                            <div class="text-lg font-black text-blue-950">{{ selectedStateForModal.avg_physical_pct }}%</div>
                            <div class="w-full bg-blue-200 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-blue-600 h-full rounded-full" :style="{ width: `${Math.min(100, selectedStateForModal.avg_physical_pct)}%` }"></div>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl border border-purple-200 bg-purple-50/50 space-y-1">
                            <span class="text-[10px] font-bold text-purple-800 uppercase tracking-wider block">CPI Performance</span>
                            <div class="text-lg font-black text-purple-950">{{ selectedStateForModal.cpi_score }} <span class="text-xs font-normal text-slate-400">/ 100</span></div>
                            <div class="text-[10px] font-bold truncate" :class="selectedStateForModal.tier.includes('Tier 1') ? 'text-emerald-700' : 'text-blue-700'">
                                {{ selectedStateForModal.tier.split('(')[0].trim() }}
                            </div>
                        </div>
                    </div>

                    <!-- Execution Status & Administrative Footprint -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div>
                            <span class="text-slate-500 text-[10px] font-bold block uppercase">Completed Works</span>
                            <span class="text-sm font-black text-emerald-700">{{ selectedStateForModal.completed_count }} Schemes</span>
                        </div>
                        <div>
                            <span class="text-slate-500 text-[10px] font-bold block uppercase">Active Ongoing</span>
                            <span class="text-sm font-black text-blue-700">{{ selectedStateForModal.ongoing_count }} Schemes</span>
                        </div>
                        <div>
                            <span class="text-slate-500 text-[10px] font-bold block uppercase">Districts Covered</span>
                            <span class="text-sm font-black text-slate-800">{{ selectedStateForModal.districts_count || 1 }} Districts</span>
                        </div>
                        <div>
                            <span class="text-slate-500 text-[10px] font-bold block uppercase">WRD Divisions</span>
                            <span class="text-sm font-black text-slate-800">{{ selectedStateForModal.divisions_count || 1 }} Divisions</span>
                        </div>
                    </div>

                    <!-- Schemes Table in this State -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="font-black text-xs uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                                <span>📋</span>
                                <span>Registered Schemes in {{ selectedStateForModal.state_name }}</span>
                            </h4>
                            <span class="text-[11px] font-bold text-slate-500">
                                {{ schemesForSelectedState.length }} Schemes
                            </span>
                        </div>

                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-slate-100 text-slate-700 uppercase font-black text-[10px] tracking-wider border-b border-slate-200">
                                    <tr>
                                        <th class="py-2.5 px-3">Code</th>
                                        <th class="py-2.5 px-3">Scheme Title &amp; District</th>
                                        <th class="py-2.5 px-3 text-right">Sanctioned</th>
                                        <th class="py-2.5 px-3 text-right">Released</th>
                                        <th class="py-2.5 px-3 text-center">Progress</th>
                                        <th class="py-2.5 px-3 text-center">Status</th>
                                        <th class="py-2.5 px-3 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr v-for="sch in schemesForSelectedState" :key="sch.id" class="hover:bg-blue-50/40 transition">
                                        <td class="py-2 px-3 font-mono font-black text-[#0F4C9F]">{{ sch.scheme_code }}</td>
                                        <td class="py-2 px-3 max-w-[220px]">
                                            <div class="font-bold text-slate-900 truncate" :title="sch.scheme_name">{{ sch.scheme_name }}</div>
                                            <div class="text-[10px] text-slate-500">📍 {{ sch.district || sch.division || 'Main' }} &bull; 🌊 {{ sch.river_basin || 'Brahmaputra' }}</div>
                                        </td>
                                        <td class="py-2 px-3 text-right font-mono font-bold text-slate-700">₹{{ sch.sanctioned_amount_cr }} Cr</td>
                                        <td class="py-2 px-3 text-right font-mono font-black text-emerald-700">₹{{ sch.released_central_cr }} Cr</td>
                                        <td class="py-2 px-3 text-center font-bold text-[#0F4C9F]">{{ sch.physical_progress_pct }}%</td>
                                        <td class="py-2 px-3 text-center">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-bold border" :class="statusBadgeClass(sch.physical_status, sch.physical_progress_pct)">
                                                {{ formatStatus(sch.physical_status, sch.physical_progress_pct) }}
                                            </span>
                                        </td>
                                        <td class="py-2 px-3 text-center">
                                            <button
                                                type="button"
                                                @click="closeStateModal(); openSchemeDrawer(sch);"
                                                class="px-2 py-1 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white rounded text-[10px] font-bold transition cursor-pointer"
                                            >
                                                Inspect 360°
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="schemesForSelectedState.length === 0">
                                        <td colspan="7" class="py-6 text-center text-slate-400 text-xs">
                                            No schemes found for this state in the current dataset.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs">
                    <button
                        type="button"
                        @click="selectedState = selectedStateForModal.state_name; applyFilters(); closeStateModal();"
                        class="px-3.5 py-1.5 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white rounded-xl font-bold transition cursor-pointer flex items-center gap-1.5"
                    >
                        <span>🔍</span>
                        <span>Filter Entire Cockpit to {{ selectedStateForModal.state_name }}</span>
                    </button>
                    <button
                        type="button"
                        @click="closeStateModal"
                        class="px-4 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl font-bold transition cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- ─── 7. 360° SCHEME DOSSIER SLIDE-OVER DRAWER ─── -->
        <div v-if="isDrawerOpen && selectedScheme" class="fixed inset-0 z-50 overflow-hidden print:hidden">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="closeSchemeDrawer"></div>

            <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
                <div class="w-screen max-w-lg bg-white shadow-2xl flex flex-col">
                    
                    <!-- Drawer Header -->
                    <div class="p-5 bg-gradient-to-r from-[#071F42] to-[#0F4C9F] text-white flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="px-2 py-0.5 rounded bg-blue-800 text-[10px] font-mono font-bold text-blue-200">
                                {{ selectedScheme.scheme_code }}
                            </span>
                            <h3 class="text-sm font-black leading-snug line-clamp-2">{{ selectedScheme.scheme_name }}</h3>
                        </div>
                        <button
                            type="button"
                            @click="closeSchemeDrawer"
                            class="p-1.5 rounded-full hover:bg-white/10 text-white transition text-lg leading-none cursor-pointer"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Drawer Body -->
                    <div class="p-5 overflow-y-auto space-y-6 flex-1 text-xs">
                        
                        <!-- Badges -->
                        <div class="flex flex-wrap gap-2">
                            <span class="px-2.5 py-1 rounded-full bg-blue-50 text-[#0F4C9F] font-bold border border-blue-200">
                                {{ selectedScheme.state }}
                            </span>
                            <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-800 font-bold border border-slate-200">
                                {{ selectedScheme.river_basin }} Basin
                            </span>
                            <span class="px-2.5 py-1 rounded-full font-bold border"
                                :class="statusBadgeClass(selectedScheme.physical_status, selectedScheme.physical_progress_pct)">
                                {{ formatStatus(selectedScheme.physical_status, selectedScheme.physical_progress_pct) }}
                            </span>
                        </div>

                        <!-- Financial Ledger Card -->
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 space-y-3">
                            <h4 class="font-black text-slate-900 uppercase text-[10px] tracking-wider">MoJS Statutory Sanction Ledger</h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <span class="text-slate-500 text-[10px] block font-bold uppercase">Total Sanctioned</span>
                                    <span class="font-black text-slate-900 text-sm">₹{{ selectedScheme.sanctioned_amount_cr }} Cr</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 text-[10px] block font-bold uppercase">Central Share ({{ selectedScheme.central_share_pct || 90 }}%)</span>
                                    <span class="font-black text-blue-900 text-sm">₹{{ selectedScheme.central_share_cr }} Cr</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 text-[10px] block font-bold uppercase">Released Central</span>
                                    <span class="font-black text-emerald-700 text-sm">₹{{ selectedScheme.released_central_cr }} Cr</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 text-[10px] block font-bold uppercase">Balance Entitlement</span>
                                    <span class="font-black text-slate-700 text-sm">₹{{ selectedScheme.balance_central_cr }} Cr</span>
                                </div>
                            </div>
                        </div>

                        <!-- Physical Execution Milestones -->
                        <div class="space-y-2">
                            <div class="flex justify-between font-bold">
                                <span>Verified Physical Completion</span>
                                <span class="text-[#0F4C9F] font-black">{{ selectedScheme.physical_progress_pct }}%</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-[#0F4C9F] h-full rounded-full transition-all duration-700" :style="{ width: `${selectedScheme.physical_progress_pct}%` }"></div>
                            </div>
                        </div>

                        <!-- Administrative Details -->
                        <div class="space-y-2 border-t border-slate-100 pt-3">
                            <h4 class="font-black text-slate-900 uppercase text-[10px] tracking-wider">Administrative &amp; Inspection Data</h4>
                            <div class="space-y-2 text-slate-700">
                                <div class="flex justify-between py-1 border-b border-slate-50">
                                    <span class="text-slate-500 font-semibold">District &amp; Division:</span>
                                    <strong>{{ selectedScheme.district }} ({{ selectedScheme.division }})</strong>
                                </div>
                                <div class="flex justify-between py-1 border-b border-slate-50">
                                    <span class="text-slate-500 font-semibold">Plan Period:</span>
                                    <strong>{{ selectedScheme.plan_period }}</strong>
                                </div>
                                <div class="flex justify-between py-1 border-b border-slate-50">
                                    <span class="text-slate-500 font-semibold">Latest Inspection Date:</span>
                                    <strong>{{ selectedScheme.latest_inspection_date }}</strong>
                                </div>
                                <div class="flex justify-between py-1 border-b border-slate-50">
                                    <span class="text-slate-500 font-semibold">Sanction Order Reference:</span>
                                    <strong class="font-mono">{{ selectedScheme.latest_sanction_no }}</strong>
                                </div>
                                <div class="flex justify-between py-1 border-b border-slate-50">
                                    <span class="text-slate-500 font-semibold">Sanction Order Date:</span>
                                    <strong>{{ selectedScheme.latest_sanction_date }}</strong>
                                </div>
                                <div class="flex justify-between py-1">
                                    <span class="text-slate-500 font-semibold">Active Claims Count:</span>
                                    <strong>{{ selectedScheme.payment_requests_count }} Claim(s) Recorded</strong>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Drawer Footer -->
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-between items-center gap-2">
                        <Link
                            :href="route('schemes.index', { search: selectedScheme.scheme_code })"
                            class="text-xs text-[#0F4C9F] hover:underline font-bold"
                        >
                            Open Scheme Catalogue Record ↗
                        </Link>
                        <button
                            type="button"
                            @click="closeSchemeDrawer"
                            class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl font-bold text-xs transition cursor-pointer"
                        >
                            Close Drawer
                        </button>
                    </div>

                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style>
@media print {
    body {
        background-color: white !important;
        color: black !important;
    }
    nav, header, button, .print\:hidden {
        display: none !important;
    }
    .shadow-2xs, .shadow-xs, .shadow-2xl {
        box-shadow: none !important;
    }
}
</style>
