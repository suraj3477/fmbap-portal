<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    schemes: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({
            total: 0, completed: 0, ongoing: 0,
            total_sanctioned: '0.00', total_released: '0.00', total_curtailed: '0.00', avg_progress: 0,
        }),
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const canSubmitDpr = computed(() =>
    ['super_admin', 'board_official', 'state_official'].includes(user.value?.role)
);

// ─── Search, Filter, Sort & Pagination ───
const search = ref('');
const selectedState = ref('ALL');
const selectedBasin = ref('ALL');
const selectedFY = ref('ALL');
const statusFilter = ref('ALL');   // ALL | ONGOING | COMPLETED
const sortBy = ref('ONGOING_FIRST'); // ONGOING_FIRST | PROGRESS_DESC | SANCTIONED_DESC | NEWEST
const expandedSchemeId = ref(null);
const currentPage = ref(1);
const perPage = 10;

// Helper to determine financial year
const getSchemeFY = (s) => {
    if (s.metadata?.financial_year) return s.metadata.financial_year;
    if (s.financial_year) return s.financial_year;
    const dateStr = s.created_at || s.submitted_at;
    if (!dateStr) return '2026-2027';
    try {
        const d = new Date(dateStr);
        const y = d.getFullYear();
        const m = d.getMonth() + 1; // 1-12
        if (m >= 4) {
            return `${y}-${y + 1}`;
        } else {
            return `${y - 1}-${y}`;
        }
    } catch {
        return '2026-2027';
    }
};

// Helper to determine if a scheme is ongoing
const isSchemeOngoing = (s) => {
    return s.physical_status !== 'Completed' && Number(s.physical_progress_pct) < 100;
};

// ─── Computed counts (client-side) ───
const completedCount = computed(() =>
    props.schemes.filter(s => s.physical_status === 'Completed' || Number(s.physical_progress_pct) >= 100).length
);
const ongoingCount = computed(() =>
    props.schemes.filter(s => isSchemeOngoing(s)).length
);
const totalCount = computed(() => props.schemes.length);

const totalSanctionedCr = computed(() => {
    const sum = props.schemes.reduce((acc, s) => acc + (parseFloat(s.sanctioned_amount_cr) || 0), 0);
    return sum.toFixed(2);
});

const totalReleasedCr = computed(() => {
    const sum = props.schemes.reduce((acc, s) => acc + (parseFloat(s.released_central_share_cr) || 0), 0);
    return sum.toFixed(2);
});

const totalCurtailedCr = computed(() => {
    const sum = props.schemes.reduce((acc, s) => acc + (parseFloat(s.curtailed_amount_cr) || 0), 0);
    return sum.toFixed(2);
});

const avgPhysicalProgress = computed(() => {
    if (props.schemes.length === 0) return 0;
    const sum = props.schemes.reduce((acc, s) => acc + (parseFloat(s.physical_progress_pct) || 0), 0);
    return Math.round(sum / props.schemes.length);
});

const availableStates = computed(() => {
    const set = new Set(props.schemes.map(s => s.state).filter(Boolean));
    return ['ALL', ...Array.from(set)];
});

const availableBasins = computed(() => {
    const set = new Set(props.schemes.map(s => s.river_basin).filter(Boolean));
    return ['ALL', ...Array.from(set)];
});

const availableFYs = computed(() => {
    const fromData = props.schemes.map(s => getSchemeFY(s)).filter(Boolean);
    const defaults = ['2026-2027', '2025-2026', '2024-2025', '2023-2024', '2022-2023'];
    const set = new Set([...fromData, ...defaults]);
    const sorted = Array.from(set).sort().reverse();
    return ['ALL', ...sorted];
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

const filteredSchemes = computed(() => {
    const q = search.value.trim().toLowerCase();
    return sortedSchemes.value.filter(s => {
        const matchQ = !q ||
            s.scheme_code?.toLowerCase().includes(q) ||
            s.scheme_name?.toLowerCase().includes(q) ||
            s.district?.toLowerCase().includes(q) ||
            s.division?.toLowerCase().includes(q) ||
            s.state?.toLowerCase().includes(q) ||
            s.river_basin?.toLowerCase().includes(q);
        if (!matchQ) return false;

        if (selectedState.value !== 'ALL' && s.state !== selectedState.value) {
            return false;
        }

        if (selectedBasin.value !== 'ALL' && s.river_basin !== selectedBasin.value) {
            return false;
        }

        if (selectedFY.value !== 'ALL' && getSchemeFY(s) !== selectedFY.value) {
            return false;
        }

        if (statusFilter.value === 'COMPLETED')
            return s.physical_status === 'Completed' || Number(s.physical_progress_pct) >= 100;
        if (statusFilter.value === 'ONGOING')
            return isSchemeOngoing(s);
        return true;
    });
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredSchemes.value.length / perPage)));
const paginatedSchemes = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    return filteredSchemes.value.slice(start, start + perPage);
});

watch([search, selectedState, selectedBasin, selectedFY, statusFilter, sortBy], () => {
    currentPage.value = 1;
});

const goToPage = n => {
    if (n >= 1 && n <= totalPages.value) {
        currentPage.value = n;
        window.scrollTo({ top: 300, behavior: 'smooth' });
    }
};

const toggleExpand = id => {
    expandedSchemeId.value = expandedSchemeId.value === id ? null : id;
};

// ─── Date Helpers ───
const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    try {
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

// ─── Export CSV ───
const exportCSV = () => {
    const headers = [
        'Scheme Code',
        'Project Name',
        'Division Name',
        'State',
        'River Basin',
        'Date of Submission',
        'Sanctioned Cost (Cr)',
        'Central Share %',
        'Central Released (Cr)',
        'Curtailed Amount (Cr)',
        'Balance Central Share (Cr)',
        'Physical Progress %',
        'Status',
        'Last Updated'
    ];
    const rows = filteredSchemes.value.map(s => [
        `"${s.scheme_code || ''}"`,
        `"${(s.scheme_name || '').replace(/"/g, '""')}"`,
        `"${(s.division || s.district || '').replace(/"/g, '""')}"`,
        `"${s.state || ''}"`,
        `"${s.river_basin || ''}"`,
        `"${formatDate(s.created_at || s.submitted_at)}"`,
        `"${s.sanctioned_amount_cr || 0}"`,
        `"${s.fmbap_project?.funding_pattern ? s.fmbap_project.funding_pattern.split('/')[0] : (s.central_share_pct || 90)}"`,
        `"${s.released_central_share_cr || 0}"`,
        `"${s.curtailed_amount_cr || 0}"`,
        `"${s.balance_central_share_cr || 0}"`,
        `"${s.physical_progress_pct || 0}"`,
        `"${s.physical_status || 'Ongoing'}"`,
        `"${formatDate(s.updated_at || s.created_at)}"`
    ]);
    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `FMBAP_Scheme_Master_Catalogue_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// Modal State: Excel Ingestion & Manual Entry
const isModalOpen = ref(false);
const modalMode = ref('manual'); // 'excel' | 'manual' - default to manual entry
const excelFile = ref(null);
const isParsing = ref(false);
const parseError = ref('');
const parsedRows = ref([]);
const isImporting = ref(false);
const isFetchingCode = ref(false);

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

const fetchNextCode = async () => {
    try {
        isFetchingCode.value = true;
        const res = await window.axios.get(route('schemes.next-code'), {
            params: { state: manualForm.state || user.value?.state || 'Assam' }
        });
        if (res.data?.next_code) {
            manualForm.scheme_code = res.data.next_code;
            manualForm.clearErrors('scheme_code');
        }
    } catch (e) {
        console.error('Failed to get next scheme code', e);
    } finally {
        isFetchingCode.value = false;
    }
};

const openAddModal = (mode = 'manual') => {
    modalMode.value = mode;
    isModalOpen.value = true;
    manualForm.clearErrors();
    if (mode === 'manual' && !manualForm.scheme_code) {
        fetchNextCode();
    }
};

watch(modalMode, (mode) => {
    if (mode === 'manual' && !manualForm.scheme_code) {
        fetchNextCode();
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
    <Head title="Scheme Master Catalogue — FMBAP" />

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
                                Scheme Master Catalogue
                            </h1>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-[#0F4C9F] border border-blue-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#0F4C9F]"></span>
                                Master Register
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium mt-1 truncate">
                            FMBAP Scheme Baseline &bull; Technical Parameters &bull; Central/State Funding &bull; Execution Progress
                        </p>
                    </div>
                </div>

                <!-- Header Actions (Streamlined Single Row) -->
                <div class="flex items-center gap-2 shrink-0 flex-wrap sm:flex-nowrap">
                    <button
                        v-if="canSubmitDpr"
                        @click="openAddModal('manual')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white text-xs font-bold rounded-md shadow-xs transition cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add Scheme / Import</span>
                    </button>

                    <a
                        :href="route('schemes.download-template')"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-300 text-slate-700 text-xs font-semibold rounded-md transition"
                        title="Download official FMBAP master spreadsheet template"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Template (.xlsx)</span>
                    </a>

                    <button
                        @click="exportCSV"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-xs font-semibold rounded-md shadow-2xs transition"
                        title="Export current filtered schemes to CSV"
                    >
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        <span>Export CSV</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="w-full max-w-[1720px] mx-auto px-3 sm:px-6 py-5 space-y-5">

            <!-- ─── 1. EXECUTIVE METRIC CARDS ─── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                <!-- Total Sanctioned Cost -->
                <div class="bg-white p-4.5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Total Sanctioned Outlay</span>
                        <span class="p-1.5 bg-blue-50 text-[#0F4C9F] rounded-lg">₹ Cr</span>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                            ₹{{ totalSanctionedCr }} <span class="text-sm font-semibold text-slate-500">Cr</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1 flex items-center justify-between font-medium">
                            <span>Central 90% : State 10% Ratio</span>
                            <span class="text-[#0F4C9F] font-bold">{{ totalCount }} Schemes</span>
                        </div>
                    </div>
                </div>

                <!-- Central Assistance Released -->
                <div class="bg-white p-4.5 rounded-xl border border-emerald-200 shadow-2xs flex flex-col justify-between relative overflow-hidden">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Central Funds Released</span>
                        <span class="p-1.5 bg-emerald-50 text-emerald-700 rounded-lg">🏛️ ₹ Cr</span>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-emerald-950 leading-tight">
                            ₹{{ totalReleasedCr }} <span class="text-sm font-semibold text-slate-500">Cr</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1 flex items-center justify-between font-medium">
                            <span v-if="parseFloat(totalCurtailedCr) > 0" class="inline-flex items-center gap-1 text-amber-700 font-bold" :title="'Total central deductions: ₹' + totalCurtailedCr + ' Cr'">
                                ⚠️ Deductions: -₹{{ totalCurtailedCr }} Cr
                            </span>
                            <span v-else class="text-emerald-700 font-medium">100% Full Sanctions</span>
                            <span class="text-slate-400 font-semibold text-[10px]">MoJS Disbursed</span>
                        </div>
                    </div>
                </div>

                <!-- Ongoing Schemes (Active) -->
                <div class="bg-white p-4.5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-800">Ongoing Portfolio</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-200">
                            {{ ongoingCount }} Active
                        </span>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-amber-950 leading-tight">
                            {{ ongoingCount }} <span class="text-base font-bold text-slate-600">in execution</span>
                        </div>
                        <div class="text-[11px] text-amber-700 mt-1 font-medium">
                            High Priority monitoring focus
                        </div>
                    </div>
                </div>

                <!-- Completed Schemes -->
                <div class="bg-white p-4.5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Delivered Schemes</span>
                        <span class="p-1.5 bg-emerald-50 text-emerald-700 rounded-lg">✓</span>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-emerald-950 leading-tight">
                            {{ completedCount }} <span class="text-base font-bold text-slate-600">delivered</span>
                        </div>
                        <div class="text-[11px] text-emerald-700 mt-1 font-medium">
                            100% physical completion verified
                        </div>
                    </div>
                </div>

                <!-- Average Physical Progress -->
                <div class="bg-white p-4.5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-blue-800">Average Physical Progress</span>
                        <span class="text-xs font-bold text-blue-900 bg-blue-50 px-2 py-0.5 rounded">% Metric</span>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-blue-950 leading-tight">
                            {{ avgPhysicalProgress }}%
                        </div>
                        <div class="w-full bg-blue-100 rounded-full h-2 mt-2 overflow-hidden">
                            <div class="bg-blue-600 h-full rounded-full transition-all duration-500" :style="{ width: `${avgPhysicalProgress}%` }"></div>
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
                        placeholder="Search scheme code, name, basin, division..."
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
                            @click="statusFilter = 'ONGOING'"
                            :class="statusFilter === 'ONGOING' ? 'bg-amber-500 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-md text-[11px] transition cursor-pointer"
                        >
                            Ongoing ({{ ongoingCount }})
                        </button>
                        <button
                            type="button"
                            @click="statusFilter = 'COMPLETED'"
                            :class="statusFilter === 'COMPLETED' ? 'bg-emerald-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-md text-[11px] transition cursor-pointer"
                        >
                            Done ({{ completedCount }})
                        </button>
                    </div>

                    <!-- Sort -->
                    <div class="flex items-center gap-1.5">
                        <select
                            v-model="sortBy"
                            class="py-1.5 px-2.5 text-xs bg-slate-50 border border-slate-300 rounded-lg text-slate-800 font-medium focus:bg-white"
                        >
                            <option value="ONGOING_FIRST">Ongoing First</option>
                            <option value="SANCTIONED_DESC">Outlay (High to Low)</option>
                            <option value="PROGRESS_DESC">Progress (High to Low)</option>
                            <option value="NEWEST">Newest Added</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ─── 3. ENTERPRISE DATA TABLE ─── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-[#0F4C9F] text-white font-bold uppercase tracking-wider text-[10px]">
                                <th class="py-3 px-3 text-center w-10">#</th>
                                <th class="py-3 px-3 min-w-[125px]">Scheme Code</th>
                                <th class="py-3 px-3 min-w-[230px]">Project Name</th>
                                <th class="py-3 px-3 min-w-[120px]">Division Name</th>
                                <th class="py-3 px-3 min-w-[120px]">State &amp; River Basin</th>
                                <th class="py-3 px-3 text-center min-w-[110px]">Date of Submission</th>
                                <th class="py-3 px-3 text-right min-w-[110px]">Sanctioned Cost</th>
                                <th class="py-3 px-3 text-center min-w-[95px]">Central Share</th>
                                <th class="py-3 px-3 text-right min-w-[135px]">Central Released</th>
                                <th class="py-3 px-3 text-center min-w-[115px]">Physical Progress</th>
                                <th class="py-3 px-3 text-center min-w-[90px]">Claims</th>
                                <th class="py-3 px-3 text-center min-w-[140px]">Status &amp; Last Updated</th>
                                <th class="py-3 px-3 text-center min-w-[80px]">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-800">
                            <template v-for="(scheme, index) in paginatedSchemes" :key="scheme.id">
                                <tr
                                    :class="[
                                        expandedSchemeId === scheme.id ? 'bg-blue-50/50' : (index % 2 === 0 ? 'bg-white' : 'bg-slate-50/40'),
                                        'hover:bg-blue-50/30 transition'
                                    ]"
                                >
                                    <!-- Index -->
                                    <td class="py-2.5 px-3 text-center font-mono text-slate-400 font-bold">
                                        {{ (currentPage - 1) * perPage + index + 1 }}
                                    </td>

                                    <!-- Scheme Code -->
                                    <td class="py-2.5 px-3 font-mono">
                                        <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-[#0F4C9F] uppercase border border-blue-200 whitespace-nowrap">
                                            {{ scheme.scheme_code }}
                                        </span>
                                        <span v-if="scheme.plan_period" class="text-[10px] text-slate-500 font-semibold block mt-0.5">
                                            {{ scheme.plan_period }}
                                        </span>
                                    </td>

                                    <!-- Project Name -->
                                    <td class="py-2.5 px-3">
                                        <div class="font-bold text-slate-900 leading-snug">
                                            {{ scheme.scheme_name }}
                                        </div>
                                    </td>

                                    <!-- Division Name -->
                                    <td class="py-2.5 px-3">
                                        <div class="font-bold text-slate-800 text-xs">
                                            {{ scheme.division || scheme.district || 'Assam WRD' }}
                                        </div>
                                    </td>

                                    <!-- State / Basin -->
                                    <td class="py-2.5 px-3">
                                        <div class="font-bold text-slate-900">{{ scheme.state || 'Assam' }}</div>
                                        <div class="text-[11px] text-blue-700 font-semibold flex items-center gap-1 mt-0.5">
                                            <span>🌊</span> {{ scheme.river_basin || 'Brahmaputra' }}
                                        </div>
                                    </td>

                                    <!-- Date of Initial Submission -->
                                    <td class="py-2.5 px-3 text-center font-mono text-[11px] text-slate-700 font-medium whitespace-nowrap">
                                        {{ formatDate(scheme.created_at || scheme.submitted_at) }}
                                    </td>

                                    <!-- Sanctioned Outlay -->
                                    <td class="py-2.5 px-3 text-right font-mono">
                                        <div class="font-black text-slate-900 text-xs">
                                            ₹{{ scheme.sanctioned_amount_cr ? parseFloat(scheme.sanctioned_amount_cr).toFixed(2) : '0.00' }} Cr
                                        </div>
                                        <div class="text-[10px] text-slate-500">
                                            (₹{{ (parseFloat(scheme.sanctioned_amount_cr || 0) * 100).toFixed(0) }} L)
                                        </div>
                                    </td>

                                    <!-- Central Share -->
                                    <td class="py-2.5 px-3 text-center font-mono text-xs">
                                        <span class="font-bold text-blue-900">
                                            {{ scheme.fmbap_project?.funding_pattern ? scheme.fmbap_project.funding_pattern.split('/')[0] : (scheme.central_share_pct || 90) }}%
                                        </span>
                                        <span class="text-[10px] text-slate-400 block">
                                            / {{ scheme.fmbap_project?.funding_pattern ? scheme.fmbap_project.funding_pattern.split('/')[1] : (scheme.state_share_pct || 10) }}% St.
                                        </span>
                                    </td>

                                    <!-- Central Released & Curtailment & Balance -->
                                    <td class="py-2.5 px-3 text-right font-mono">
                                        <div class="font-black text-emerald-900 text-xs">
                                            ₹{{ scheme.released_central_share_cr ? parseFloat(scheme.released_central_share_cr).toFixed(2) : '0.00' }} Cr
                                        </div>
                                        <!-- MoJS Curtailment Badge -->
                                        <div v-if="parseFloat(scheme.curtailed_amount_cr || 0) > 0" class="mt-0.5">
                                            <span
                                                class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300"
                                                :title="'MoJS Curtailed: -₹' + parseFloat(scheme.curtailed_amount_cr).toFixed(2) + ' Cr | Reason: ' + (scheme.latest_curtailment_reason || 'Central Allocation Deduction')"
                                            >
                                                ⚠️ -₹{{ parseFloat(scheme.curtailed_amount_cr).toFixed(2) }} Cr Curtailed
                                            </span>
                                        </div>
                                        <!-- Balance Central Share -->
                                        <div class="text-[10px] text-slate-500 mt-0.5">
                                            Bal: ₹{{ scheme.balance_central_share_cr !== undefined ? parseFloat(scheme.balance_central_share_cr).toFixed(2) : '—' }} Cr
                                        </div>
                                    </td>

                                    <!-- Physical Progress -->
                                    <td class="py-2.5 px-3 text-center">
                                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-700 mb-1">
                                            <span>{{ parseFloat(scheme.physical_progress_pct || 0) }}%</span>
                                            <span v-if="scheme.physical_status === 'Completed'" class="text-emerald-600 font-bold text-[10px]">Done</span>
                                        </div>
                                        <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all duration-300"
                                                :class="scheme.physical_status === 'Completed' || parseFloat(scheme.physical_progress_pct) >= 95 ? 'bg-emerald-500' : (parseFloat(scheme.physical_progress_pct) >= 40 ? 'bg-amber-500' : 'bg-blue-600')"
                                                :style="{ width: `${parseFloat(scheme.physical_progress_pct || 0)}%` }"
                                            ></div>
                                        </div>
                                        <div v-if="scheme.metadata?.bb_verified_progress_pct" class="mt-1">
                                            <span class="inline-flex items-center gap-0.5 text-[9px] font-bold text-blue-800 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200" title="Progress verified by Brahmaputra Board monitoring report">
                                                🛡️ BB Verified: {{ scheme.metadata.bb_verified_progress_pct }}%
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Claims Count & Release Status -->
                                    <td class="py-2.5 px-3 text-center">
                                        <div class="inline-flex flex-col items-center gap-1">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 whitespace-nowrap">
                                                {{ scheme.payment_requests?.length || 0 }} Claims
                                            </span>
                                            <span
                                                v-if="parseFloat(scheme.approved_claims_release_cr || scheme.released_central_share_cr || 0) > 0"
                                                class="px-1.5 py-0.5 rounded text-[9px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 whitespace-nowrap inline-flex items-center gap-0.5"
                                                title="Central Assistance released by MoJS"
                                            >
                                                <span>✓ ₹{{ parseFloat(scheme.approved_claims_release_cr || scheme.released_central_share_cr).toFixed(2) }} Cr</span>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Status & Last Updated By with Date -->
                                    <td class="py-2.5 px-3 text-center">
                                        <div
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="scheme.physical_status === 'Completed' || parseFloat(scheme.physical_progress_pct) >= 100 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-900 border border-amber-300'"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full" :class="scheme.physical_status === 'Completed' || parseFloat(scheme.physical_progress_pct) >= 100 ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse'"></span>
                                            {{ scheme.physical_status === 'Completed' || parseFloat(scheme.physical_progress_pct) >= 100 ? 'Completed' : 'Ongoing' }}
                                        </div>
                                        <div class="text-[10px] text-slate-500 mt-1 leading-tight whitespace-nowrap">
                                            <span class="font-medium">{{ formatDate(scheme.updated_at || scheme.created_at) }}</span>
                                            <span class="block text-[9px] text-slate-400 font-semibold">by {{ scheme.metadata?.updated_by || (scheme.physical_status === 'Completed' ? 'MoJS Approver' : 'State WRD') }}</span>
                                        </div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-2.5 px-3 text-center">
                                        <button
                                            type="button"
                                            @click="toggleExpand(scheme.id)"
                                            class="px-2.5 py-1 rounded bg-slate-100 hover:bg-[#0F4C9F] text-slate-700 hover:text-white font-bold text-[11px] transition shadow-2xs cursor-pointer"
                                        >
                                            {{ expandedSchemeId === scheme.id ? '▲ Close' : '▼ Details' }}
                                        </button>
                                    </td>
                                </tr>

                                <!-- Expanded Row Drawer: Dossier & Quick Actions -->
                                <tr v-if="expandedSchemeId === scheme.id" class="bg-blue-50/40 border-b border-blue-200">
                                    <td colspan="13" class="p-4 sm:p-5">
                                        <div class="bg-white p-4 rounded-xl border border-blue-200 shadow-sm space-y-4">
                                            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                                                <div>
                                                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                                        Project Technical Baseline
                                                    </span>
                                                    <h4 class="text-sm font-bold text-slate-900 mt-1">
                                                        {{ scheme.scheme_name }} ({{ scheme.scheme_code }})
                                                    </h4>
                                                </div>
                                                <div v-if="['state_official', 'super_admin'].includes(user?.role)" class="flex items-center gap-2">
                                                    <Link
                                                        :href="route('fund-release.create', { scheme_id: scheme.id })"
                                                        class="px-3 py-1.5 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white text-xs font-bold rounded shadow-xs transition flex items-center gap-1"
                                                    >
                                                        <span>⚡</span> Initiate Fund Claim
                                                    </Link>
                                                </div>
                                            </div>

                                            <!-- Grid Details -->
                                            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 text-xs">
                                                <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                                    <span class="text-[10px] text-slate-500 font-bold uppercase block">Division / District</span>
                                                    <strong class="text-slate-800">{{ scheme.division || scheme.district || 'Assam Division' }}</strong>
                                                </div>
                                                <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                                    <span class="text-[10px] text-slate-500 font-bold uppercase block">River Basin</span>
                                                    <strong class="text-blue-700">{{ scheme.river_basin || 'Brahmaputra' }}</strong>
                                                </div>
                                                <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                                    <span class="text-[10px] text-slate-500 font-bold uppercase block">Central Share Entitlement</span>
                                                    <strong class="text-slate-900">₹{{ (parseFloat(scheme.sanctioned_amount_cr || 0) * (scheme.fmbap_project?.funding_pattern ? parseFloat(scheme.fmbap_project.funding_pattern.split('/')[0]) : (scheme.central_share_pct || 90)) / 100).toFixed(2) }} Cr</strong>
                                                </div>
                                                <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                                    <span class="text-[10px] text-slate-500 font-bold uppercase block">State Matching Share</span>
                                                    <strong class="text-slate-900">₹{{ (parseFloat(scheme.sanctioned_amount_cr || 0) * (scheme.fmbap_project?.funding_pattern ? parseFloat(scheme.fmbap_project.funding_pattern.split('/')[1]) : (scheme.state_share_pct || 10)) / 100).toFixed(2) }} Cr</strong>
                                                </div>
                                                <div class="bg-emerald-50 p-2.5 rounded-lg border border-emerald-200">
                                                    <span class="text-[10px] text-emerald-800 font-bold uppercase block">Central Funds Released</span>
                                                    <strong class="text-emerald-950 font-black">₹{{ scheme.released_central_share_cr ? parseFloat(scheme.released_central_share_cr).toFixed(2) : '0.00' }} Cr</strong>
                                                </div>
                                                <div class="bg-blue-50 p-2.5 rounded-lg border border-blue-200">
                                                    <span class="text-[10px] text-blue-800 font-bold uppercase block">Balance Central Share</span>
                                                    <strong class="text-blue-950 font-bold">₹{{ scheme.balance_central_share_cr !== undefined ? parseFloat(scheme.balance_central_share_cr).toFixed(2) : '0.00' }} Cr</strong>
                                                </div>

                                                <!-- Curtailment Alert Card if deductions occurred -->
                                                <div v-if="parseFloat(scheme.curtailed_amount_cr || 0) > 0" class="bg-amber-50 p-2.5 rounded-lg border border-amber-300 col-span-2 sm:col-span-4 lg:col-span-6 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-base">⚠️</span>
                                                        <div>
                                                            <div class="font-bold text-xs text-amber-950">
                                                                Central Allocation Curtailment: <span class="font-black text-rose-700">-₹{{ parseFloat(scheme.curtailed_amount_cr).toFixed(2) }} Cr</span>
                                                            </div>
                                                            <div class="text-[11px] text-amber-800">
                                                                Curtailment Justification: <strong>{{ scheme.latest_curtailment_reason || 'Field Inspection / Technical Compliance Shortfall' }}</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div v-if="scheme.latest_sanction_order_no" class="flex items-center gap-2 text-[11px] font-mono text-slate-700 shrink-0">
                                                        <span class="font-bold">Order: {{ scheme.latest_sanction_order_no }}</span>
                                                        <a
                                                            v-if="scheme.latest_sanction_order_doc_path"
                                                            :href="scheme.latest_sanction_order_doc_path"
                                                            target="_blank"
                                                            class="px-2 py-0.5 bg-white hover:bg-amber-100 text-[#0F4C9F] font-sans font-bold rounded border border-amber-300 shadow-2xs transition"
                                                        >
                                                            View Order PDF &rarr;
                                                        </a>
                                                    </div>
                                                </div>

                                                <!-- BB Verified Progress Card -->
                                                <div v-if="scheme.metadata?.bb_verified_progress_pct" class="bg-blue-50 p-2.5 rounded-lg border border-blue-200 col-span-2 sm:col-span-4 lg:col-span-6 flex items-center justify-between">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-lg">🛡️</span>
                                                        <div>
                                                            <div class="font-bold text-xs text-blue-950">
                                                                Brahmaputra Board Verified Progress: <span class="text-sm font-black text-[#0F4C9F]">{{ scheme.metadata.bb_verified_progress_pct }}%</span>
                                                            </div>
                                                            <div class="text-[11px] text-blue-700">
                                                                Authoritative progress certified from statutory on-site inspection
                                                                <span v-if="scheme.metadata.bb_inspection_date"> on {{ formatDate(scheme.metadata.bb_inspection_date) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-200 text-[#0F4C9F]">BB Verified</span>
                                                </div>
                                            </div>

                                            <!-- Payment Requests on this Scheme -->
                                            <div v-if="scheme.payment_requests && scheme.payment_requests.length > 0" class="border-t border-slate-100 pt-3">
                                                <span class="text-xs font-bold text-slate-700 block mb-2">Claim &amp; Release History ({{ scheme.payment_requests.length }} entries):</span>
                                                <div class="space-y-2">
                                                    <div
                                                        v-for="pr in scheme.payment_requests"
                                                        :key="pr.id"
                                                        class="p-3 rounded-lg border text-xs transition"
                                                        :class="pr.status === 'APPROVED' ? 'bg-emerald-50/40 border-emerald-200' : (pr.status === 'NEEDS_CORRECTION' ? 'bg-amber-50/50 border-amber-300' : 'bg-slate-50 border-slate-200')"
                                                    >
                                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                            <div class="flex items-center gap-2 flex-wrap">
                                                                <span class="font-mono font-bold text-blue-950 bg-blue-100 px-2 py-0.5 rounded text-[11px]">
                                                                    #CLAIM-{{ pr.id }}
                                                                </span>
                                                                <span class="text-slate-700 font-semibold text-[11px]">
                                                                    Instalment {{ pr.instalment_number || 1 }}
                                                                </span>
                                                                <span class="text-slate-600 font-mono text-[11px]">
                                                                    Claimed: <strong class="text-slate-900">₹{{ pr.requested_amount_cr }} Cr</strong>
                                                                </span>

                                                                <!-- Approved Release Pill -->
                                                                <span
                                                                    v-if="pr.status === 'APPROVED'"
                                                                    class="px-2 py-0.5 rounded text-[11px] font-black bg-emerald-600 text-white shadow-2xs inline-flex items-center gap-1"
                                                                >
                                                                    <span>✓ Sanctioned &amp; Released:</span>
                                                                    <span>₹{{ pr.approved_amount_cr || pr.requested_amount_cr }} Cr</span>
                                                                </span>

                                                                <!-- Curtailment / Deduction Pill -->
                                                                <span
                                                                    v-if="parseFloat(pr.deduction_amount_cr || 0) > 0"
                                                                    class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1"
                                                                    :title="pr.curtailment_reason || 'Central Allocation Deduction'"
                                                                >
                                                                    <span>⚠️ Curtailment: -₹{{ pr.deduction_amount_cr }} Cr</span>
                                                                </span>

                                                                <span v-if="pr.bb_monitoring_report?.bb_physical_progress_pct" class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-800 border border-blue-200">
                                                                    🛡️ BB Progress: {{ pr.bb_monitoring_report.bb_physical_progress_pct }}%
                                                                </span>
                                                            </div>

                                                            <div class="flex items-center gap-2 shrink-0">
                                                                <span
                                                                    :class="{
                                                                        'bg-emerald-100 text-emerald-900 border-emerald-300': pr.status === 'APPROVED',
                                                                        'bg-amber-100 text-amber-900 border-amber-300': pr.status === 'NEEDS_CORRECTION',
                                                                        'bg-blue-100 text-blue-800 border-blue-200': pr.status !== 'APPROVED' && pr.status !== 'NEEDS_CORRECTION'
                                                                    }"
                                                                    class="text-[10px] font-bold px-2 py-0.5 rounded border uppercase"
                                                                >
                                                                    {{ pr.status }}
                                                                </span>
                                                                <Link
                                                                    v-if="pr.status === 'NEEDS_CORRECTION' && ['state_official', 'state', 'super_admin'].includes(user?.role)"
                                                                    :href="route('fund-release.edit', pr.id)"
                                                                    class="px-2 py-0.5 rounded bg-amber-600 hover:bg-amber-700 text-white font-bold text-[10px] transition inline-flex items-center gap-1"
                                                                >
                                                                    <span>✏️ Correct Claim</span>
                                                                </Link>
                                                                <Link :href="route('fund-release.show', pr.id)" class="text-[#0F4C9F] font-bold hover:underline inline-flex items-center gap-0.5">
                                                                    <span>View Dossier</span>
                                                                    <span>&rarr;</span>
                                                                </Link>
                                                            </div>
                                                        </div>

                                                        <!-- Curtailment Justification & Sanction Order Details -->
                                                        <div v-if="pr.status === 'APPROVED' && (parseFloat(pr.deduction_amount_cr || 0) > 0 || pr.sanction_order_no)" class="mt-2 pt-2 border-t border-emerald-200/60 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[11px]">
                                                            <div v-if="parseFloat(pr.deduction_amount_cr || 0) > 0" class="text-amber-900 font-medium">
                                                                <strong class="font-bold text-amber-950">MoJS Curtailment Reason:</strong>
                                                                <span class="italic ml-1">{{ pr.curtailment_reason || 'Field Inspection / Physical Progress Shortfall' }}</span>
                                                            </div>
                                                            <div v-if="pr.sanction_order_no" class="flex items-center gap-3 font-mono text-slate-700 shrink-0">
                                                                <div>
                                                                    <span class="text-slate-500 font-sans text-[10px] uppercase font-bold">Sanction Order:</span>
                                                                    <span class="font-bold text-slate-900 ml-1">{{ pr.sanction_order_no }}</span>
                                                                    <span v-if="pr.sanction_order_date" class="text-slate-500 font-sans text-[10px] ml-1">({{ formatDate(pr.sanction_order_date) }})</span>
                                                                </div>
                                                                <a
                                                                    v-if="pr.sanction_order_doc_path"
                                                                    :href="pr.sanction_order_doc_path"
                                                                    target="_blank"
                                                                    class="inline-flex items-center gap-1 px-2 py-0.5 bg-white hover:bg-blue-50 text-[#0F4C9F] font-sans font-bold text-[10px] rounded border border-blue-200 transition"
                                                                    title="Download MoJS Sanction Order PDF"
                                                                >
                                                                    <svg class="w-3 h-3 text-[#0F4C9F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                    </svg>
                                                                    <span>Download Sanction Order</span>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty Row -->
                            <tr v-if="paginatedSchemes.length === 0">
                                <td colspan="13" class="p-8 text-center text-slate-500 italic">
                                    No schemes match your selected search or filter criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Bar -->
                <div class="bg-slate-50 border-t border-slate-200 px-4 py-3 flex items-center justify-between text-xs text-slate-600">
                    <div>
                        Showing <strong>{{ filteredSchemes.length > 0 ? (currentPage - 1) * perPage + 1 : 0 }}</strong> to <strong>{{ Math.min(currentPage * perPage, filteredSchemes.length) }}</strong> of <strong>{{ filteredSchemes.length }}</strong> schemes
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            @click="goToPage(currentPage - 1)"
                            :disabled="currentPage <= 1"
                            class="px-2.5 py-1 rounded bg-white border border-slate-300 disabled:opacity-40 hover:bg-slate-100 font-semibold"
                        >
                            &larr; Prev
                        </button>
                        <span class="px-2 font-bold text-slate-800">Page {{ currentPage }} of {{ totalPages }}</span>
                        <button
                            type="button"
                            @click="goToPage(currentPage + 1)"
                            :disabled="currentPage >= totalPages"
                            class="px-2.5 py-1 rounded bg-white border border-slate-300 disabled:opacity-40 hover:bg-slate-100 font-semibold"
                        >
                            Next &rarr;
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- ─── 4. MODAL: ADD SCHEME (DPR) / EXCEL INGESTION ─── -->
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

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-bold text-slate-700">
                                    Scheme Code <span class="text-rose-500">*</span>
                                </label>
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
                                class="w-full rounded-lg text-xs font-mono font-bold uppercase tracking-wider"
                                placeholder="e.g. AS-19"
                                required
                            />
                            <InputError :message="manualForm.errors.scheme_code" class="mt-1" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">State / UT <span class="text-rose-500">*</span></label>
                            <select
                                v-model="manualForm.state"
                                @change="fetchNextCode"
                                :class="manualForm.errors.state ? 'border-rose-400' : 'border-slate-300'"
                                class="w-full rounded-lg text-xs font-semibold"
                                required
                            >
                                <option value="Assam">Assam</option>
                                <option value="Arunachal Pradesh">Arunachal Pradesh</option>
                                <option value="Meghalaya">Meghalaya</option>
                                <option value="Manipur">Manipur</option>
                                <option value="Mizoram">Mizoram</option>
                                <option value="Nagaland">Nagaland</option>
                                <option value="Sikkim">Sikkim</option>
                                <option value="Tripura">Tripura</option>
                                <option value="West Bengal">West Bengal</option>
                            </select>
                            <InputError :message="manualForm.errors.state" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Scheme Title / Description <span class="text-rose-500">*</span></label>
                        <textarea
                            v-model="manualForm.scheme_name"
                            rows="2"
                            :class="manualForm.errors.scheme_name ? 'border-rose-400 ring-1 ring-rose-400' : 'border-slate-300'"
                            class="w-full rounded-lg text-xs"
                            placeholder="Full official title of the flood management / anti-erosion project"
                            required
                        ></textarea>
                        <InputError :message="manualForm.errors.scheme_name" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">District / Division</label>
                            <input
                                v-model="manualForm.division"
                                type="text"
                                class="w-full rounded-lg border-slate-300 text-xs"
                                placeholder="e.g. Guwahati East"
                            />
                            <InputError :message="manualForm.errors.division" class="mt-1" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">River Basin</label>
                            <input
                                v-model="manualForm.river_basin"
                                type="text"
                                class="w-full rounded-lg border-slate-300 text-xs"
                                placeholder="e.g. Brahmaputra"
                            />
                            <InputError :message="manualForm.errors.river_basin" class="mt-1" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Estimated Cost (₹ Lakh) <span class="text-rose-500">*</span></label>
                            <input
                                v-model="manualForm.estimated_cost_lakh"
                                type="number"
                                step="0.01"
                                min="0"
                                :class="manualForm.errors.estimated_cost_lakh ? 'border-rose-400 ring-1 ring-rose-400' : 'border-slate-300'"
                                class="w-full rounded-lg text-xs font-semibold"
                                placeholder="e.g. 608"
                                required
                            />
                            <div v-if="manualForm.sanctioned_amount_cr" class="text-[10px] text-blue-700 font-bold mt-0.5">
                                = ₹{{ manualForm.sanctioned_amount_cr }} Cr (Outlay)
                            </div>
                            <InputError :message="manualForm.errors.estimated_cost_lakh" class="mt-1" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Physical Progress (%)</label>
                            <input
                                v-model="manualForm.physical_progress_pct"
                                type="number"
                                min="0"
                                max="100"
                                step="0.1"
                                class="w-full rounded-lg border-slate-300 text-xs font-semibold bg-white"
                                placeholder="0 (Defaults to 0% for new schemes)"
                            />
                            <InputError :message="manualForm.errors.physical_progress_pct" class="mt-1" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Execution Status</label>
                            <select
                                v-model="manualForm.physical_status"
                                class="w-full rounded-lg border-slate-300 text-xs font-semibold bg-white"
                            >
                                <option value="Ongoing">Ongoing</option>
                                <option value="Completed">Completed</option>
                                <option value="Delayed">Delayed</option>
                            </select>
                            <InputError :message="manualForm.errors.physical_status" class="mt-1" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isModalOpen = false"
                            class="px-4 py-2 border border-slate-300 rounded-lg font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="manualForm.processing"
                            class="px-5 py-2 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white font-bold rounded-lg shadow-md hover:shadow-lg transition flex items-center gap-2 disabled:opacity-50 cursor-pointer"
                        >
                            <svg v-if="manualForm.processing" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
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
