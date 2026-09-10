<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    schemes: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({
            total: 0, completed: 0, ongoing: 0,
            total_sanctioned: '0.00', avg_progress: 0,
        }),
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const canSubmitDpr = computed(() =>
    ['super_admin', 'board_official', 'state_official'].includes(user.value?.role)
);

// ─── Search, Filter, Sort & Pagination ───
const search       = ref('');
const statusFilter = ref('ALL');   // ALL | ONGOING | COMPLETED
const sortBy       = ref('ONGOING_FIRST'); // ONGOING_FIRST | PROGRESS_DESC | SANCTIONED_DESC | NEWEST
const currentPage  = ref(1);
const PER_PAGE     = 10;

const activeSchemeId   = ref(null);

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

// Sorted schemes with ONGOING FIRST by default
const sortedSchemes = computed(() => {
    return [...props.schemes].sort((a, b) => {
        if (sortBy.value === 'ONGOING_FIRST') {
            const aOngoing = isSchemeOngoing(a) ? 1 : 0;
            const bOngoing = isSchemeOngoing(b) ? 1 : 0;
            if (aOngoing !== bOngoing) {
                return bOngoing - aOngoing; // Ongoing (1) comes first before Completed (0)
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
            s.state?.toLowerCase().includes(q) ||
            s.river_basin?.toLowerCase().includes(q);
        if (!matchQ) return false;
        if (statusFilter.value === 'COMPLETED')
            return s.physical_status === 'Completed' || Number(s.physical_progress_pct) >= 100;
        if (statusFilter.value === 'ONGOING')
            return isSchemeOngoing(s);
        return true;
    });
});

const totalPages      = computed(() => Math.max(1, Math.ceil(filteredSchemes.value.length / PER_PAGE)));
const paginatedSchemes = computed(() => {
    const start = (currentPage.value - 1) * PER_PAGE;
    return filteredSchemes.value.slice(start, start + PER_PAGE);
});
const pageNumbers = computed(() => Array.from({ length: totalPages.value }, (_, i) => i + 1));

watch([search, statusFilter, sortBy], () => { currentPage.value = 1; activeSchemeId.value = null; });

const goToPage = n => {
    if (n >= 1 && n <= totalPages.value) { currentPage.value = n; activeSchemeId.value = null; }
};

const toggleScheme = id => {
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

// ─── Export CSV ───
const exportCSV = () => {
    const headers = ['Code', 'Name', 'State', 'District', 'River Basin', 'Date', 'Sanctioned (Cr)', 'Physical %', 'Status'];
    const rows = filteredSchemes.value.map(s => [
        s.scheme_code, `"${s.scheme_name}"`, s.state, s.district, s.river_basin,
        formatDate(getSchemeDate(s).date),
        s.sanctioned_amount_cr, s.physical_progress_pct,
        isSchemeOngoing(s) ? 'Ongoing' : 'Completed',
    ]);
    const csv = [headers, ...rows].map(r => r.join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob);
    a.download = 'fmbap_scheme_catalogue.csv'; a.click();
};
</script>

<template>
    <Head title="Scheme Master Catalogue — FMBAP" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <!-- Back button -->
                    <Link :href="route('dashboard')"
                        class="w-10 h-10 rounded-xl bg-white border border-gray-200 shadow-sm hover:bg-gray-50 hover:border-gray-300 flex items-center justify-center text-gray-700 transition-all shrink-0"
                        title="Back to Dashboard">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h2 class="font-extrabold text-xl sm:text-2xl text-slate-900 tracking-tight leading-tight">
                                Scheme Master Catalogue
                            </h2>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                Ongoing Prioritized
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 font-medium mt-0.5">
                            FMBAP — Flood Management and Border Areas Programme &bull; Central &amp; State Schemes
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <a
                        :href="route('schemes.download-template')"
                        class="inline-flex items-center gap-2 text-sm font-bold text-blue-800 bg-blue-50 hover:bg-blue-100 border border-blue-200 px-4 py-2.5 rounded-xl shadow-sm transition-all"
                        title="Download official FMBAP master spreadsheet template"
                    >
                        <svg class="w-4 h-4 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Official Template (.xlsx)
                    </a>
                    <button @click="exportCSV"
                        class="inline-flex items-center gap-2 text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 px-4 py-2.5 rounded-xl shadow-sm transition-all"
                        title="Export current filtered view to CSV">
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export CSV
                    </button>
                </div>
            </div>
        </template>

        <div class="cat-page-wrap">

            <!-- ─── STAT CARDS (ONGOING PLACED FIRST) ─── -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 sm:gap-4">
                
                <!-- 1. ONGOING SCHEMES (FIRST & HIGHLIGHTED) -->
                <button @click="statusFilter = statusFilter === 'ONGOING' ? 'ALL' : 'ONGOING'"
                    type="button"
                    class="stat-box transition-all text-left group"
                    :class="statusFilter === 'ONGOING' ? 'ring-2 ring-amber-500 bg-amber-50/70 border-amber-300 shadow-md' : 'bg-white border-slate-200 hover:border-amber-300 hover:shadow-sm'">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-amber-800 flex items-center gap-1.5">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                            </span>
                            Ongoing Schemes
                        </span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-amber-100 text-amber-900 border border-amber-200">
                            Active
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl sm:text-4xl font-black text-amber-900 leading-none">{{ ongoingCount }}</span>
                        <span class="text-xs sm:text-sm font-medium text-amber-800">in execution</span>
                    </div>
                    <p class="text-xs text-amber-800/80 mt-1 font-medium flex items-center gap-1">
                        <span>High Priority</span> &bull; <span>Click to focus</span>
                    </p>
                </button>

                <!-- 2. TOTAL SCHEMES -->
                <button @click="statusFilter = 'ALL'"
                    type="button"
                    class="stat-box transition-all text-left group"
                    :class="statusFilter === 'ALL' ? 'ring-2 ring-blue-500 bg-blue-50/60 border-blue-300 shadow-md' : 'bg-white border-slate-200 hover:border-blue-300 hover:shadow-sm'">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-blue-800">Total Schemes</span>
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl sm:text-4xl font-black text-blue-900 leading-none">{{ stats.total }}</span>
                        <span class="text-xs sm:text-sm font-medium text-slate-500">catalogue</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium">All recorded works</p>
                </button>

                <!-- 3. COMPLETED SCHEMES -->
                <button @click="statusFilter = statusFilter === 'COMPLETED' ? 'ALL' : 'COMPLETED'"
                    type="button"
                    class="stat-box transition-all text-left group"
                    :class="statusFilter === 'COMPLETED' ? 'ring-2 ring-emerald-500 bg-emerald-50/70 border-emerald-300 shadow-md' : 'bg-white border-slate-200 hover:border-emerald-300 hover:shadow-sm'">
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
                    <p class="text-xs text-emerald-800/80 mt-1 font-medium">100% physical completion</p>
                </button>

                <!-- 4. TOTAL SANCTIONED OUTLAY -->
                <div class="stat-box bg-white border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-violet-800">Total Sanctioned</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded bg-violet-100 text-violet-800">Cr</span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-2xl sm:text-3xl font-black text-violet-900 leading-none">₹{{ stats.total_sanctioned }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium">Approved Programme Cost</p>
                </div>

                <!-- 5. AVERAGE PHYSICAL PROGRESS -->
                <div class="stat-box bg-white border-slate-200 col-span-2 sm:col-span-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-orange-800">Avg. Progress</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded bg-orange-100 text-orange-800">%</span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-3xl sm:text-4xl font-black text-orange-900 leading-none">{{ stats.avg_progress }}%</span>
                    </div>
                    <div class="w-full bg-orange-100 rounded-full h-2 mt-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-orange-500 transition-all duration-700" :style="{ width: `${stats.avg_progress}%` }"></div>
                    </div>
                </div>

            </div>

            <!-- ─── MAIN CATALOGUE CARD ─── -->
            <div class="cat-card">

                <!-- ─── TOOLBAR (ONGOING TAB FIRST) ─── -->
                <div class="cat-toolbar">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 flex-1">
                        
                        <!-- Search Bar with Large Legible Input -->
                        <div class="relative flex-1 max-w-lg">
                            <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
                                </svg>
                            </div>
                            <input v-model="search" type="text"
                                placeholder="Search scheme by code, name, river basin, district or state..."
                                class="w-full h-12 pl-11 pr-10 text-sm sm:text-base text-slate-800 placeholder-slate-400 bg-white border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm" />
                            <button v-if="search" @click="search = ''"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1 text-base font-bold"
                                title="Clear search">✕</button>
                        </div>

                        <!-- Filter Tabs (Ongoing First) & Sort Dropdown -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            
                            <!-- 1. Ongoing Pill (First) -->
                            <button @click="statusFilter = 'ONGOING'"
                                :class="statusFilter === 'ONGOING' ? 'bg-amber-600 text-white shadow-sm ring-2 ring-amber-300' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                class="inline-flex items-center gap-2 text-xs sm:text-sm font-bold px-3.5 py-2.5 rounded-xl transition-all">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-200 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2" :class="statusFilter === 'ONGOING' ? 'bg-white' : 'bg-amber-600'"></span>
                                </span>
                                Ongoing Schemes ({{ ongoingCount }})
                            </button>

                            <!-- 2. All Pill -->
                            <button @click="statusFilter = 'ALL'"
                                :class="statusFilter === 'ALL' ? 'bg-slate-900 text-white shadow-sm ring-2 ring-slate-400' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold px-3.5 py-2.5 rounded-xl transition-all">
                                All Schemes ({{ schemes.length }})
                            </button>

                            <!-- 3. Completed Pill -->
                            <button @click="statusFilter = 'COMPLETED'"
                                :class="statusFilter === 'COMPLETED' ? 'bg-emerald-600 text-white shadow-sm ring-2 ring-emerald-300' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold px-3.5 py-2.5 rounded-xl transition-all">
                                ✓ Completed ({{ completedCount }})
                            </button>

                            <!-- Sort Selector -->
                            <div class="flex items-center gap-1.5 ml-auto sm:ml-2">
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider hidden sm:inline">Sort:</span>
                                <select v-model="sortBy"
                                    class="h-10 text-xs sm:text-sm font-semibold text-slate-800 bg-white border border-slate-300 rounded-xl px-3 py-1.5 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    <option value="ONGOING_FIRST">⚡ Ongoing First</option>
                                    <option value="PROGRESS_DESC">Highest Progress %</option>
                                    <option value="SANCTIONED_DESC">Sanctioned Outlay (High to Low)</option>
                                    <option value="NEWEST">Newest ID First</option>
                                </select>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Info Sub-bar -->
                <div class="px-6 py-2.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between text-xs sm:text-sm text-slate-600">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-800">{{ filteredSchemes.length }}</span>
                        <span>schemes found</span>
                        <span v-if="statusFilter !== 'ALL'" class="font-semibold text-blue-700">
                            ({{ statusFilter === 'ONGOING' ? 'Showing Ongoing only' : 'Showing Completed only' }})
                        </span>
                    </div>
                    <div class="text-xs text-slate-500 font-medium">
                        Click on any scheme row to inspect financial sharing, payment releases, and official records.
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredSchemes.length === 0" class="py-20 px-6 flex flex-col items-center text-center gap-3">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-3xl mb-1">
                        {{ schemes.length === 0 ? '📂' : '🔍' }}
                    </div>
                    <h3 class="font-extrabold text-xl text-slate-800">
                        {{ schemes.length === 0 ? 'No schemes in the catalogue yet.' : 'No schemes match your criteria.' }}
                    </h3>
                    <p class="text-sm text-slate-500 max-w-md">
                        {{ schemes.length === 0 ? 'Upload or register new projects through DPR submission to populate the master catalogue.' : 'Try adjusting your search query, or clear filters to see all ongoing and completed projects.' }}
                    </p>
                    <div class="flex items-center gap-3 mt-2">
                        <button v-if="statusFilter !== 'ALL' || search" @click="statusFilter = 'ALL'; search = ''; sortBy = 'ONGOING_FIRST';"
                            class="text-sm font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 px-5 py-2.5 rounded-xl transition-all shadow-sm">
                            Reset All Filters
                        </button>
                        <Link v-if="schemes.length === 0 && canSubmitDpr" :href="route('dashboard')"
                            class="text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 px-5 py-2.5 rounded-xl transition-all shadow-sm">
                            Go to Dashboard &rarr;
                        </Link>
                    </div>
                </div>

                <!-- ─── SCHEME ROWS (WITH ONGOING VISUAL EMPHASIS) ─── -->
                <div v-else class="divide-y divide-slate-200">
                    <div v-for="scheme in paginatedSchemes" :key="scheme.id"
                        class="transition-colors"
                        :class="[
                            activeSchemeId === scheme.id ? 'bg-blue-50/40' : '',
                            isSchemeOngoing(scheme) ? 'border-l-4 border-l-amber-500 bg-amber-50/10 hover:bg-amber-50/30' : 'border-l-4 border-l-emerald-500 hover:bg-slate-50/80'
                        ]">

                        <!-- ─── Row Clickable Header ─── -->
                        <div @click="toggleScheme(scheme.id)"
                            role="button"
                            tabindex="0"
                            class="w-full px-5 sm:px-6 py-4 sm:py-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 cursor-pointer text-left select-none">

                            <!-- Left: Code + Date + Big Scheme Name + Tags -->
                            <div class="min-w-0 flex-1 space-y-2">
                                
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <!-- Scheme Code -->
                                    <span class="scheme-code-badge">{{ scheme.scheme_code }}</span>

                                    <!-- Plan Period Tag -->
                                    <span v-if="scheme.plan_period" class="text-xs font-bold px-2.5 py-1 rounded-md bg-slate-100 text-slate-800 border border-slate-200">
                                        {{ scheme.plan_period }}
                                    </span>

                                    <!-- Status Badge (Ongoing / Completed) -->
                                    <div v-if="isSchemeOngoing(scheme)" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs sm:text-sm font-bold bg-amber-100 text-amber-900 border border-amber-300 shadow-sm">
                                        <span class="relative flex h-2.5 w-2.5">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-500 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-600"></span>
                                        </span>
                                        Ongoing &bull; {{ Number(scheme.physical_progress_pct || 0) }}% Physical Progress
                                    </div>
                                    <div v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs sm:text-sm font-bold bg-emerald-100 text-emerald-900 border border-emerald-300 shadow-sm">
                                        <svg class="w-4 h-4 text-emerald-700" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Completed &bull; 100%
                                    </div>
                                </div>

                                <!-- Scheme Title (Large, readable, no truncation) -->
                                <h3 class="font-extrabold text-base sm:text-lg lg:text-xl text-slate-900 leading-snug break-words">
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

                                <!-- Location, River & Division Chips -->
                                <div class="flex items-center flex-wrap gap-2 text-xs sm:text-sm text-slate-600 font-medium">
                                    <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-800 font-semibold px-2.5 py-1 rounded-md border border-slate-200">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ scheme.district || 'District N/A' }}, {{ scheme.state }}
                                    </span>

                                    <span v-if="scheme.river_basin" class="inline-flex items-center gap-1 bg-teal-50 text-teal-800 font-semibold px-2.5 py-1 rounded-md border border-teal-200">
                                        <svg class="w-3.5 h-3.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        Basin: {{ scheme.river_basin }}
                                    </span>

                                    <span v-if="scheme.division" class="inline-flex items-center gap-1 bg-purple-50 text-purple-800 font-semibold px-2.5 py-1 rounded-md border border-purple-200">
                                        Division: {{ scheme.division }}
                                    </span>
                                </div>

                            </div>

                            <!-- Right: Date Box + Financial Outlay + Badges + Chevron -->
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

                                <!-- Financial Outlay Pill -->
                                <div class="px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-200 text-slate-800 flex flex-col items-end">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Sanctioned</span>
                                    <span class="text-sm sm:text-base font-black text-slate-900 leading-tight">
                                        ₹{{ scheme.sanctioned_amount_cr }} Cr
                                    </span>
                                </div>

                                <!-- Releases Count Pill -->
                                <div class="px-3 py-2 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 flex flex-col items-center">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-blue-600">Releases</span>
                                    <span class="text-sm sm:text-base font-black leading-tight">
                                        {{ scheme.payment_requests?.length || 0 }}
                                    </span>
                                </div>

                                <!-- Reports Count Pill -->
                                <div class="px-3 py-2 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex flex-col items-center">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">Reports</span>
                                    <span class="text-sm sm:text-base font-black leading-tight">
                                        {{ scheme.progress_reports?.length || 0 }}
                                    </span>
                                </div>

                                <!-- View Details CTA & Animated Chevron -->
                                <div class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-slate-300 text-slate-700 font-bold text-xs sm:text-sm hover:bg-slate-50 transition-all shadow-sm">
                                    <span>{{ activeSchemeId === scheme.id ? 'Collapse' : 'Details' }}</span>
                                    <svg class="w-4 h-4 text-slate-600 transition-transform duration-200"
                                        :class="activeSchemeId === scheme.id ? 'rotate-180' : ''"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>

                            </div>
                        </div>

                        <!-- ─── EXPANDED DETAIL PANEL ─── -->
                        <Transition name="panel-slide">
                            <div v-if="activeSchemeId === scheme.id" class="cat-panel">
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

                                    <!-- LEFT COLUMN: Physical Progress & Scheme Metadata (7 cols) -->
                                    <div class="lg:col-span-7 space-y-5">

                                        <!-- Physical Progress Banner -->
                                        <div class="panel-card bg-gradient-to-br from-white to-slate-50 border-slate-200">
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold"
                                                        :class="isSchemeOngoing(scheme) ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'">
                                                        <span v-if="isSchemeOngoing(scheme)">⏳</span>
                                                        <span v-else>✓</span>
                                                    </div>
                                                    <div>
                                                        <h4 class="text-base font-extrabold text-slate-900">Physical Progress Status</h4>
                                                        <p class="text-xs text-slate-500">Official execution stage under FMBAP programme</p>
                                                    </div>
                                                </div>
                                                <span :class="isSchemeOngoing(scheme)
                                                    ? 'bg-amber-100 text-amber-900 border-amber-300'
                                                    : 'bg-emerald-100 text-emerald-900 border-emerald-300'"
                                                    class="text-xs sm:text-sm font-black px-3 py-1 rounded-full border shadow-sm">
                                                    {{ isSchemeOngoing(scheme) ? `Ongoing — ${Number(scheme.physical_progress_pct || 0)}% Completed` : '✓ Completed — 100%' }}
                                                </span>
                                            </div>

                                            <div class="w-full bg-slate-200 rounded-full h-4 overflow-hidden mt-3">
                                                <div class="h-4 rounded-full transition-all duration-700 font-bold text-[10px] text-white flex items-center justify-end pr-2"
                                                    :class="isSchemeOngoing(scheme) ? 'bg-gradient-to-r from-amber-500 to-amber-600' : 'bg-gradient-to-r from-emerald-500 to-emerald-600'"
                                                    :style="{ width: `${Math.min(100, Math.max(8, Number(scheme.physical_progress_pct || 0)))}%` }">
                                                    {{ Number(scheme.physical_progress_pct || 0) }}%
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between text-xs sm:text-sm text-slate-600 mt-2.5 font-medium">
                                                <span>Recorded Status: <strong class="text-slate-900 font-bold">{{ scheme.physical_status || (isSchemeOngoing(scheme) ? 'Ongoing' : 'Completed') }}</strong></span>
                                                <span v-if="isSchemeOngoing(scheme)" class="text-amber-800 font-bold">Remaining Work: {{ (100 - Number(scheme.physical_progress_pct || 0)).toFixed(1) }}%</span>
                                                <span v-else class="text-emerald-800 font-bold">Scope Fully Completed</span>
                                            </div>
                                        </div>

                                        <!-- Official Scheme Details & Financial Pattern -->
                                        <div class="panel-card space-y-4">
                                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                                <h4 class="text-base font-extrabold text-slate-900">Official Metadata &amp; Financial Sharing</h4>
                                                <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-blue-50 text-blue-800 border border-blue-200">
                                                    Plan: {{ scheme.plan_period || 'XI / XII Plan' }}
                                                </span>
                                            </div>

                                            <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Name of Division</dt>
                                                    <dd class="font-extrabold text-slate-900 text-sm sm:text-base mt-0.5">{{ scheme.division || scheme.district || 'WRD Division' }}</dd>
                                                </div>
                                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">River Basin</dt>
                                                    <dd class="font-extrabold text-slate-900 text-sm sm:text-base mt-0.5">{{ scheme.river_basin || 'Brahmaputra' }}</dd>
                                                </div>
                                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">State / District</dt>
                                                    <dd class="font-extrabold text-slate-900 text-sm sm:text-base mt-0.5">{{ scheme.district ? `${scheme.district}, ` : '' }}{{ scheme.state }}</dd>
                                                </div>
                                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Sanction / Entry Date</dt>
                                                    <dd class="font-extrabold text-slate-900 text-sm sm:text-base mt-0.5 font-mono">{{ formatDate(scheme.created_at) }}</dd>
                                                </div>
                                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Last System Update</dt>
                                                    <dd class="font-extrabold text-slate-900 text-sm sm:text-base mt-0.5 font-mono">{{ formatDate(scheme.updated_at) }}</dd>
                                                </div>
                                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Official Submission</dt>
                                                    <dd class="font-extrabold text-slate-900 text-sm sm:text-base mt-0.5 font-mono">{{ formatDate(scheme.fmbap_project?.state_govt_submission_date || scheme.created_at) }}</dd>
                                                </div>
                                            </dl>

                                            <!-- Financial Sharing Pattern Box -->
                                            <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-200">
                                                <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-2">
                                                    <div>
                                                        <span class="text-xs font-extrabold uppercase tracking-wider text-blue-900">Estimated Outlay &amp; Sanction</span>
                                                        <div class="flex items-baseline gap-3 mt-1">
                                                            <span class="text-2xl sm:text-3xl font-black text-blue-950">
                                                                ₹{{ scheme.estimated_cost_lakh ? Number(scheme.estimated_cost_lakh).toFixed(2) : ((scheme.sanctioned_amount_cr || 0) * 100).toFixed(2) }} Lakh
                                                            </span>
                                                            <span class="text-sm font-bold text-blue-800 font-mono">
                                                                (₹{{ scheme.sanctioned_amount_cr }} Cr)
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="text-xs text-blue-700 font-bold bg-white px-3 py-1 rounded-lg border border-blue-200 shadow-sm self-start">
                                                        Ratio: {{ scheme.central_share_pct || 90 }}% Central &bull; {{ scheme.state_share_pct || 10 }}% State
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-2 gap-3 mt-3 pt-3 border-t border-blue-200/60 text-sm">
                                                    <div class="bg-white p-3 rounded-lg border border-blue-100">
                                                        <span class="text-xs text-slate-500 font-bold uppercase">Central Share ({{ scheme.central_share_pct || 90 }}%)</span>
                                                        <p class="text-base sm:text-lg font-black text-blue-900 mt-0.5">
                                                            ₹{{ (((Number(scheme.sanctioned_amount_cr) || 0) * (Number(scheme.central_share_pct) || 90)) / 100).toFixed(2) }} Cr
                                                        </p>
                                                    </div>
                                                    <div class="bg-white p-3 rounded-lg border border-blue-100">
                                                        <span class="text-xs text-slate-500 font-bold uppercase">State Share ({{ scheme.state_share_pct || 10 }}%)</span>
                                                        <p class="text-base sm:text-lg font-black text-slate-900 mt-0.5">
                                                            ₹{{ (((Number(scheme.sanctioned_amount_cr) || 0) * (Number(scheme.state_share_pct) || 10)) / 100).toFixed(2) }} Cr
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Government Fund Position Table -->
                                            <div v-if="scheme.fund_utilised_total_lakh || scheme.fund_req_total_lakh" class="pt-2">
                                                <h5 class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                                                    Government Fund Position (Rs. in Lakh)
                                                </h5>
                                                <div class="overflow-x-auto rounded-xl border border-slate-200">
                                                    <table class="w-full text-sm text-left">
                                                        <thead class="bg-slate-100 text-xs text-slate-600 font-bold uppercase">
                                                            <tr>
                                                                <th class="px-3.5 py-2.5">Component</th>
                                                                <th class="px-3.5 py-2.5 text-right font-bold text-blue-800">Central Share (CS)</th>
                                                                <th class="px-3.5 py-2.5 text-right font-bold text-slate-700">State Share (SS)</th>
                                                                <th class="px-3.5 py-2.5 text-right font-black text-slate-900">Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-slate-200 font-mono text-xs sm:text-sm">
                                                            <tr class="hover:bg-blue-50/40">
                                                                <td class="px-3.5 py-2.5 font-sans text-slate-800 font-bold">Fund Utilised</td>
                                                                <td class="px-3.5 py-2.5 text-right font-bold text-blue-700">₹{{ scheme.fund_utilised_cs_lakh || '0.00' }}</td>
                                                                <td class="px-3.5 py-2.5 text-right font-semibold text-slate-700">₹{{ scheme.fund_utilised_ss_lakh || '0.00' }}</td>
                                                                <td class="px-3.5 py-2.5 text-right font-black text-slate-900">₹{{ scheme.fund_utilised_total_lakh || '0.00' }}</td>
                                                            </tr>
                                                            <tr class="hover:bg-amber-50/40 bg-amber-50/20">
                                                                <td class="px-3.5 py-2.5 font-sans text-amber-900 font-bold">Fund Requirement</td>
                                                                <td class="px-3.5 py-2.5 text-right font-bold text-amber-800">₹{{ scheme.fund_req_cs_lakh || '0.00' }}</td>
                                                                <td class="px-3.5 py-2.5 text-right font-semibold text-slate-700">₹{{ scheme.fund_req_ss_lakh || '0.00' }}</td>
                                                                <td class="px-3.5 py-2.5 text-right font-black text-amber-950">₹{{ scheme.fund_req_total_lakh || '0.00' }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- RIGHT COLUMN: Fund Releases & Progress Reports (5 cols) -->
                                    <div class="lg:col-span-5 space-y-5">

                                        <!-- Fund Release Requests -->
                                        <div class="panel-card space-y-3">
                                            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-base font-extrabold text-slate-900">Fund Releases</span>
                                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">
                                                        {{ scheme.payment_requests?.length || 0 }} Requests
                                                    </span>
                                                </div>
                                                <Link :href="route('fund-release.index')" class="text-xs sm:text-sm text-blue-600 hover:text-blue-800 font-bold underline">
                                                    All Releases &rarr;
                                                </Link>
                                            </div>

                                            <div v-if="!scheme.payment_requests?.length"
                                                class="text-sm text-slate-500 italic border border-dashed border-slate-200 rounded-xl p-5 text-center bg-slate-50">
                                                No fund release requests submitted for this scheme yet.
                                            </div>

                                            <div v-for="req in scheme.payment_requests" :key="req.id"
                                                class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2 hover:border-slate-300 transition-all">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div>
                                                        <span class="text-xs font-semibold text-slate-500">Instalment #{{ req.instalment_number || '1' }}</span>
                                                        <p class="font-extrabold text-base text-slate-900">₹{{ req.requested_amount_cr }} Cr</p>
                                                    </div>
                                                    <div class="flex items-center gap-1.5 flex-wrap justify-end">
                                                        <span class="req-badge" :class="{
                                                            'req-approved':    req.status === 'APPROVED',
                                                            'req-correction':  req.status === 'NEEDS_CORRECTION',
                                                            'req-submitted':   req.status === 'SUBMITTED_TO_BB',
                                                            'req-forwarded':   req.status === 'FORWARDED_TO_MOJS',
                                                            'req-draft':       req.status === 'DRAFT',
                                                            'req-rejected':    req.status === 'REJECTED',
                                                        }">{{ req.status.replace(/_/g, ' ') }}</span>
                                                        <Link :href="route('fund-release.show', req.id)"
                                                            class="text-xs sm:text-sm text-blue-600 hover:text-blue-800 font-bold underline px-1">
                                                            View Dossier &rarr;
                                                        </Link>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                </div>
                                </div>
                            </div>
                        </Transition>

                    </div>
                </div>

                <!-- ─── PAGINATION ─── -->
                <div v-if="totalPages > 1"
                    class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-slate-600 font-medium">
                        Showing
                        <strong class="text-slate-900 font-bold">{{ (currentPage - 1) * PER_PAGE + 1 }}–{{ Math.min(currentPage * PER_PAGE, filteredSchemes.length) }}</strong>
                        of <strong class="text-slate-900 font-bold">{{ filteredSchemes.length }}</strong> schemes
                    </p>
                    <div class="flex items-center gap-1.5">
                        <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                            class="pg-btn" :class="currentPage === 1 ? 'opacity-40 cursor-not-allowed' : ''">
                            &larr; Previous
                        </button>
                        <button v-for="n in pageNumbers" :key="n" @click="goToPage(n)"
                            :class="n === currentPage ? 'pg-active' : 'pg-btn'"
                            class="pg-num">{{ n }}</button>
                        <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages"
                            class="pg-btn" :class="currentPage === totalPages ? 'opacity-40 cursor-not-allowed' : ''">
                            Next &rarr;
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* ─── Page Layout ─── */
.cat-page-wrap {
    @apply py-6 px-4 sm:px-6 lg:px-8 space-y-6 max-w-screen-2xl mx-auto;
}
.cat-card {
    @apply bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden;
}

/* ─── Stat Box ─── */
.stat-box {
    @apply p-4 sm:p-5 rounded-2xl border transition-all cursor-pointer;
}

/* ─── Toolbar ─── */
.cat-toolbar {
    @apply p-4 sm:p-5 bg-white border-b border-slate-200;
}

/* ─── Badges ─── */
.scheme-code-badge {
    @apply font-mono font-black text-blue-900 text-xs sm:text-sm bg-blue-50 border border-blue-200 px-3 py-1 rounded-md tracking-wider shadow-sm;
}

/* ─── Expanded Panel ─── */
.cat-panel {
    @apply bg-slate-50/80 border-t border-slate-200 px-5 sm:px-7 py-6;
}
.panel-card {
    @apply bg-white rounded-2xl border border-slate-200 p-5 shadow-sm;
}

/* ─── Request Badges ─── */
.req-badge { @apply text-xs font-bold px-2.5 py-1 rounded border tracking-wide; }
.req-approved   { @apply bg-emerald-50 text-emerald-800 border-emerald-300; }
.req-correction { @apply bg-amber-50 text-amber-900 border-amber-300; }
.req-submitted  { @apply bg-blue-50 text-blue-800 border-blue-300; }
.req-forwarded  { @apply bg-indigo-50 text-indigo-800 border-indigo-300; }
.req-draft      { @apply bg-slate-100 text-slate-700 border-slate-300; }
.req-rejected   { @apply bg-red-50 text-red-800 border-red-300; }

/* ─── Pagination Buttons ─── */
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

/* ─── Transitions ─── */
.panel-slide-enter-active,
.panel-slide-leave-active { transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
.panel-slide-enter-from,
.panel-slide-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
