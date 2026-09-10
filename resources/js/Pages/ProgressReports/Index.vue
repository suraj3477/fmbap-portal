<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    reports: {
        type: Array,
        default: () => [],
    },
    userRole: {
        type: String,
        default: '',
    },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});
const effectiveRole = computed(() => props.userRole || currentUser.value?.role || '');

// ─── Search, Filter, Pagination ───
const search = ref('');
const statusFilter = ref('ALL');
const currentPage = ref(1);
const perPage = 10;

// ─── Metrics ───
const totalCount = computed(() => props.reports.length);

const approvedCount = computed(() => 
    props.reports.filter(r => r.status === 'APPROVED' || r.status === 'REVIEWED_BY_MOJS').length
);

const underReviewCount = computed(() => 
    props.reports.filter(r => r.status === 'SUBMITTED' || r.status === 'REVIEWED_BY_BB').length
);

const needsCorrectionCount = computed(() => 
    props.reports.filter(r => r.status === 'NEEDS_CORRECTION').length
);

const avgPhysicalProgress = computed(() => {
    if (!props.reports.length) return 0;
    const sum = props.reports.reduce((acc, r) => acc + (parseFloat(r.physical_progress_pct) || 0), 0);
    return Math.round(sum / props.reports.length);
});

// ─── Filtering ───
const filteredReports = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.reports.filter(r => {
        const code = r.scheme?.scheme_code?.toLowerCase() || '';
        const name = r.scheme?.scheme_name?.toLowerCase() || '';
        const period = r.reporting_period?.toLowerCase() || '';
        const idStr = String(r.id);
        const matchesQuery = !q || code.includes(q) || name.includes(q) || period.includes(q) || idStr.includes(q);

        if (!matchesQuery) return false;

        if (statusFilter.value === 'APPROVED') {
            return r.status === 'APPROVED' || r.status === 'REVIEWED_BY_MOJS';
        }
        if (statusFilter.value === 'UNDER_REVIEW') {
            return r.status === 'SUBMITTED' || r.status === 'REVIEWED_BY_BB';
        }
        if (statusFilter.value === 'NEEDS_CORRECTION') {
            return r.status === 'NEEDS_CORRECTION';
        }

        return true;
    });
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredReports.value.length / perPage)));
const paginatedReports = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    return filteredReports.value.slice(start, start + perPage);
});
const pageNumbers = computed(() => Array.from({ length: totalPages.value }, (_, i) => i + 1));

watch([search, statusFilter], () => {
    currentPage.value = 1;
});

const goToPage = (n) => {
    if (n >= 1 && n <= totalPages.value) {
        currentPage.value = n;
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch {
        return dateStr;
    }
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'APPROVED':
        case 'REVIEWED_BY_MOJS':
            return { label: status === 'APPROVED' ? 'Approved' : 'MoJS Verified', class: 'bg-emerald-50 text-emerald-700 border-emerald-200', dot: 'bg-emerald-500' };
        case 'REVIEWED_BY_BB':
            return { label: 'BB Reviewed', class: 'bg-purple-50 text-purple-700 border-purple-200', dot: 'bg-purple-500' };
        case 'SUBMITTED':
            return { label: 'Submitted', class: 'bg-blue-50 text-blue-700 border-blue-200', dot: 'bg-blue-500' };
        case 'NEEDS_CORRECTION':
            return { label: 'Correction Req.', class: 'bg-amber-50 text-amber-800 border-amber-300', dot: 'bg-amber-500' };
        default:
            return { label: status.replace(/_/g, ' '), class: 'bg-slate-100 text-slate-700 border-slate-200', dot: 'bg-slate-400' };
    }
};
</script>

<template>
    <Head title="Routine Progress Reports — FMBAP" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('dashboard')"
                        class="w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 hover:text-gray-900 transition-colors shrink-0"
                        title="Back to Dashboard"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-bold text-lg text-gray-900 leading-tight">
                                Routine Progress Reports
                            </h2>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Module 3
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Periodic physical and financial tracking reported by State Agencies
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link
                        v-if="effectiveRole === 'state_official' || effectiveRole === 'super_admin'"
                        :href="route('progress-reports.create')"
                        class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-sm hover:shadow transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Submit Progress Report
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-5 max-w-screen-2xl mx-auto">

            <!-- ─── STAT STRIP ─── -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold text-gray-900 leading-none">{{ totalCount }}</div>
                        <div class="text-xs text-gray-500 font-medium mt-1">Total Reports</div>
                    </div>
                </div>

                <div class="w-px h-10 bg-gray-200 hidden sm:block"></div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold text-emerald-600 leading-none">{{ approvedCount }}</div>
                        <div class="text-xs text-gray-500 font-medium mt-1">Verified & Approved</div>
                    </div>
                </div>

                <div class="w-px h-10 bg-gray-200 hidden sm:block"></div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold text-blue-600 leading-none">{{ underReviewCount }}</div>
                        <div class="text-xs text-gray-500 font-medium mt-1">Under Review</div>
                    </div>
                </div>

                <div class="w-px h-10 bg-gray-200 hidden sm:block"></div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold text-amber-600 leading-none">{{ needsCorrectionCount }}</div>
                        <div class="text-xs text-gray-500 font-medium mt-1">Action Required</div>
                    </div>
                </div>

                <div class="w-px h-10 bg-gray-200 hidden sm:block"></div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center font-bold">
                        %
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold text-teal-700 leading-none">{{ avgPhysicalProgress }}%</div>
                        <div class="text-xs text-gray-500 font-medium mt-1">Avg. Physical Progress</div>
                    </div>
                </div>
            </div>

            <!-- ─── NOTICE STRIP ─── -->
            <div class="bg-emerald-50/70 border border-emerald-100 rounded-2xl p-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-xs text-emerald-950 font-medium leading-relaxed">
                    Routine Progress Reports represent monthly or quarterly updates submitted by State Agencies. They provide ongoing oversight of work progress and expenditure independent of central fund release claims.
                </p>
            </div>

            <!-- ─── MAIN CARD ─── -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

                <!-- Toolbar -->
                <div class="px-5 py-4 bg-gray-50/80 border-b border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 flex-1">
                        <!-- Search Box -->
                        <div class="relative flex-1 max-w-md">
                            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
                            </svg>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search by scheme code, name, period, or Req #..."
                                class="w-full border border-gray-200 rounded-xl py-2 pl-9 pr-9 text-xs bg-white text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                            />
                            <button
                                v-if="search"
                                @click="search = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs"
                            >
                                ✕
                            </button>
                        </div>

                        <!-- Filter Pills -->
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <button
                                @click="statusFilter = 'ALL'"
                                :class="statusFilter === 'ALL' ? 'bg-gray-800 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                                class="text-xs font-semibold px-3 py-1.5 rounded-lg transition"
                            >
                                All ({{ totalCount }})
                            </button>
                            <button
                                @click="statusFilter = 'APPROVED'"
                                :class="statusFilter === 'APPROVED' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                                class="text-xs font-semibold px-3 py-1.5 rounded-lg transition"
                            >
                                Approved ({{ approvedCount }})
                            </button>
                            <button
                                @click="statusFilter = 'UNDER_REVIEW'"
                                :class="statusFilter === 'UNDER_REVIEW' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                                class="text-xs font-semibold px-3 py-1.5 rounded-lg transition"
                            >
                                Under Review ({{ underReviewCount }})
                            </button>
                            <button
                                v-if="needsCorrectionCount > 0"
                                @click="statusFilter = 'NEEDS_CORRECTION'"
                                :class="statusFilter === 'NEEDS_CORRECTION' ? 'bg-amber-600 text-white' : 'bg-white text-amber-700 hover:bg-amber-50 border border-amber-200'"
                                class="text-xs font-semibold px-3 py-1.5 rounded-lg transition"
                            >
                                Needs Correction ({{ needsCorrectionCount }})
                            </button>
                        </div>
                    </div>

                    <div class="text-xs text-gray-500 shrink-0 font-medium">
                        Showing {{ filteredReports.length }} of {{ totalCount }}
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50/50 border-b border-gray-200 text-gray-500 font-semibold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="px-5 py-3.5">Report ID & Scheme</th>
                                <th class="px-5 py-3.5">Reporting Period</th>
                                <th class="px-5 py-3.5">Progress (Physical / Financial)</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5">Submitted</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            <tr
                                v-for="report in paginatedReports"
                                :key="report.id"
                                class="hover:bg-emerald-50/30 transition-colors group"
                            >
                                <!-- Report ID & Scheme -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-start gap-2.5">
                                        <span class="font-mono text-xs font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded border border-gray-200 shrink-0">
                                            #{{ report.id }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="font-bold text-emerald-800 text-xs">
                                                {{ report.scheme?.scheme_code || 'UNASSIGNED' }}
                                            </div>
                                            <div class="text-xs text-gray-600 truncate max-w-xs md:max-w-md mt-0.5" :title="report.scheme?.scheme_name">
                                                {{ report.scheme?.scheme_name || 'No scheme title' }}
                                            </div>
                                            <div class="text-[11px] text-gray-400 mt-0.5">
                                                {{ report.scheme?.state || 'State' }} &bull; {{ report.scheme?.district || 'District' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Reporting Period -->
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-800 border border-gray-200">
                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ report.reporting_period }}
                                    </span>
                                </td>

                                <!-- Dual Progress -->
                                <td class="px-5 py-3.5">
                                    <div class="space-y-1.5 min-w-[150px] max-w-[200px]">
                                        <!-- Physical -->
                                        <div>
                                            <div class="flex justify-between text-[10px] font-semibold text-gray-500 mb-0.5">
                                                <span>Physical</span>
                                                <span class="text-blue-700 font-bold">{{ report.physical_progress_pct }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                                <div
                                                    class="bg-blue-600 h-1.5 rounded-full transition-all duration-300"
                                                    :style="{ width: Math.min(100, Math.max(0, report.physical_progress_pct)) + '%' }"
                                                ></div>
                                            </div>
                                        </div>
                                        <!-- Financial -->
                                        <div>
                                            <div class="flex justify-between text-[10px] font-semibold text-gray-500 mb-0.5">
                                                <span>Financial</span>
                                                <span class="text-emerald-700 font-bold">{{ report.financial_progress_pct }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                                <div
                                                    class="bg-emerald-600 h-1.5 rounded-full transition-all duration-300"
                                                    :style="{ width: Math.min(100, Math.max(0, report.financial_progress_pct)) + '%' }"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-5 py-3.5">
                                    <span
                                        :class="['inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold rounded-lg border leading-tight', getStatusBadge(report.status).class]"
                                    >
                                        <span :class="['w-1.5 h-1.5 rounded-full', getStatusBadge(report.status).dot]"></span>
                                        {{ getStatusBadge(report.status).label }}
                                    </span>
                                </td>

                                <!-- Submitted Date -->
                                <td class="px-5 py-3.5 text-gray-500 font-medium text-xs">
                                    {{ formatDate(report.created_at) }}
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-3.5 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <Link
                                            v-if="report.status === 'NEEDS_CORRECTION' && (effectiveRole === 'state_official' || effectiveRole === 'super_admin')"
                                            :href="route('progress-reports.edit', report.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 transition"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Correct
                                        </Link>

                                        <Link
                                            :href="route('progress-reports.show', report.id)"
                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 shadow-xs transition"
                                        >
                                            <span>View Details</span>
                                            <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-emerald-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredReports.length === 0">
                                <td colspan="6" class="px-5 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <div class="w-12 h-12 rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center text-xl">
                                            📊
                                        </div>
                                        <p class="font-bold text-gray-700 text-sm">
                                            {{ totalCount === 0 ? 'No progress reports submitted yet.' : 'No progress reports match your current filters.' }}
                                        </p>
                                        <p class="text-xs text-gray-400 max-w-sm">
                                            {{ totalCount === 0 && (effectiveRole === 'state_official' || effectiveRole === 'super_admin') 
                                                ? 'Click "+ Submit Progress Report" above to enter periodic physical and financial milestone data.' 
                                                : 'Try clearing your search query or selecting a different filter tab.' }}
                                        </p>
                                        <button
                                            v-if="statusFilter !== 'ALL' || search"
                                            @click="statusFilter = 'ALL'; search = ''"
                                            class="mt-2 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-3.5 py-1.5 rounded-lg transition"
                                        >
                                            Clear Filters
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="filteredReports.length > 0" class="px-5 py-3.5 bg-gray-50/70 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div class="text-gray-500 font-medium">
                        Showing <span class="font-bold text-gray-800">{{ (currentPage - 1) * perPage + 1 }}</span>
                        to <span class="font-bold text-gray-800">{{ Math.min(currentPage * perPage, filteredReports.length) }}</span>
                        of <span class="font-bold text-gray-800">{{ filteredReports.length }}</span> entries
                    </div>

                    <div v-if="totalPages > 1" class="flex items-center gap-1.5 self-center sm:self-auto">
                        <button
                            @click="goToPage(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed font-medium transition"
                        >
                            &larr; Prev
                        </button>

                        <button
                            v-for="p in pageNumbers"
                            :key="p"
                            @click="goToPage(p)"
                            :class="p === currentPage ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-gray-600 hover:bg-gray-100 border border-transparent'"
                            class="w-7 h-7 rounded-lg flex items-center justify-center text-xs transition"
                        >
                            {{ p }}
                        </button>

                        <button
                            @click="goToPage(currentPage + 1)"
                            :disabled="currentPage === totalPages"
                            class="px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed font-medium transition"
                        >
                            Next &rarr;
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
