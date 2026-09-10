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

// ─── Search, Filter, Pagination ───
const search = ref('');
const statusFilter = ref('ALL');
const currentPage = ref(1);
const perPage = 10;

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

// ─── Filtering ───
const filteredRequests = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.requests.filter(r => {
        const code = r.scheme?.scheme_code?.toLowerCase() || '';
        const name = r.scheme?.scheme_name?.toLowerCase() || '';
        const idStr = String(r.id);
        const matchesQuery = !q || code.includes(q) || name.includes(q) || idStr.includes(q);

        if (!matchesQuery) return false;

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
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredRequests.value.length / perPage)));
const paginatedRequests = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    return filteredRequests.value.slice(start, start + perPage);
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

const getReportStatus = (req) => {
    if (!req.bb_monitoring_report) {
        return {
            label: 'Pending Inspection',
            class: 'bg-rose-50 text-rose-700 border-rose-200',
            dot: 'bg-rose-500',
            icon: 'alert',
        };
    }
    if (req.bb_monitoring_report.status === 'DRAFT') {
        return {
            label: 'Draft Saved',
            class: 'bg-amber-50 text-amber-800 border-amber-300',
            dot: 'bg-amber-500',
            icon: 'draft',
        };
    }
    return {
        label: 'Report Submitted',
        class: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        dot: 'bg-emerald-500',
        icon: 'check',
    };
};

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
</script>

<template>
    <Head title="Monitoring Requests Queue — FMBAP" />

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
                                Monitoring &amp; Inspection Queue
                            </h2>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200">
                                Brahmaputra Board
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Field inspections and monitoring verification reports required before central fund release
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs">
                    <span class="font-medium text-gray-500">Pipeline Stage:</span>
                    <span class="bg-indigo-100 text-indigo-800 font-bold px-2.5 py-1 rounded-lg">
                        State Claim &rarr; <strong>BB Field Audit</strong> &rarr; MoJS Release
                    </span>
                </div>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-5 max-w-screen-2xl mx-auto">

            <!-- ─── STAT STRIP ─── -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold text-gray-900 leading-none">{{ totalCount }}</div>
                        <div class="text-xs text-gray-500 font-medium mt-1">Total in Queue</div>
                    </div>
                </div>

                <div class="w-px h-10 bg-gray-200 hidden sm:block"></div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold text-rose-600 leading-none">{{ pendingInspectionCount }}</div>
                        <div class="text-xs text-gray-500 font-medium mt-1">Pending Inspection</div>
                    </div>
                </div>

                <div class="w-px h-10 bg-gray-200 hidden sm:block"></div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold text-amber-600 leading-none">{{ draftReportCount }}</div>
                        <div class="text-xs text-gray-500 font-medium mt-1">Draft Reports</div>
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
                        <div class="text-2xl font-extrabold text-emerald-600 leading-none">{{ completedReportCount }}</div>
                        <div class="text-xs text-gray-500 font-medium mt-1">Reports Completed</div>
                    </div>
                </div>

                <div class="w-px h-10 bg-gray-200 hidden sm:block"></div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                        ₹
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold text-purple-700 leading-none">₹{{ totalAmountCr }} Cr</div>
                        <div class="text-xs text-gray-500 font-medium mt-1">Value in Audit</div>
                    </div>
                </div>
            </div>

            <!-- ─── WORKFLOW NOTICE BANNER ─── -->
            <div class="bg-gradient-to-r from-indigo-50/90 to-blue-50/70 rounded-2xl border border-indigo-100 p-4 flex items-start gap-3.5">
                <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="text-xs text-indigo-950 leading-relaxed">
                    <span class="font-bold text-indigo-900">Brahmaputra Board Inspection Duty:</span>
                    Payment requests submitted by State Agencies require physical site inspection, Geo-tagged photo documentation, and a verification report from the Brahmaputra Board before funds can be forwarded to the Ministry of Jal Shakti (MoJS) for central release.
                </div>
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
                                placeholder="Search by scheme code, name, or Req #..."
                                class="w-full border border-gray-200 rounded-xl py-2 pl-9 pr-9 text-xs bg-white text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
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
                                All Queue ({{ totalCount }})
                            </button>
                            <button
                                @click="statusFilter = 'PENDING'"
                                :class="statusFilter === 'PENDING' ? 'bg-rose-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                                class="text-xs font-semibold px-3 py-1.5 rounded-lg transition"
                            >
                                Needs Inspection ({{ pendingInspectionCount }})
                            </button>
                            <button
                                v-if="draftReportCount > 0"
                                @click="statusFilter = 'DRAFT'"
                                :class="statusFilter === 'DRAFT' ? 'bg-amber-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                                class="text-xs font-semibold px-3 py-1.5 rounded-lg transition"
                            >
                                In Draft ({{ draftReportCount }})
                            </button>
                            <button
                                @click="statusFilter = 'COMPLETED'"
                                :class="statusFilter === 'COMPLETED' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                                class="text-xs font-semibold px-3 py-1.5 rounded-lg transition"
                            >
                                Completed ({{ completedReportCount }})
                            </button>
                        </div>
                    </div>

                    <div class="text-xs text-gray-500 shrink-0 font-medium">
                        Showing {{ filteredRequests.length }} of {{ totalCount }}
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50/50 border-b border-gray-200 text-gray-500 font-semibold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="px-5 py-3.5">Req ID & Scheme</th>
                                <th class="px-5 py-3.5">Requested Amount</th>
                                <th class="px-5 py-3.5">State Claimed Progress</th>
                                <th class="px-5 py-3.5">Inspection Status</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            <tr
                                v-for="req in paginatedRequests"
                                :key="req.id"
                                class="hover:bg-indigo-50/30 transition-colors group"
                            >
                                <!-- Req ID & Scheme -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-start gap-2.5">
                                        <span class="font-mono text-xs font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded border border-gray-200 shrink-0">
                                            #{{ req.id }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="font-bold text-indigo-700 text-xs">
                                                {{ req.scheme?.scheme_code || 'UNASSIGNED' }}
                                            </div>
                                            <div class="text-xs text-gray-600 truncate max-w-xs md:max-w-md mt-0.5" :title="req.scheme?.scheme_name">
                                                {{ req.scheme?.scheme_name || 'No description available' }}
                                            </div>
                                            <div class="text-[11px] text-gray-400 mt-0.5">
                                                {{ req.scheme?.state || 'State' }} &bull; {{ req.scheme?.district || 'District' }}
                                            </div>
                                            <div class="mt-1 flex items-center gap-1 font-medium">
                                                <span class="inline-flex items-center gap-1 text-blue-800 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-md font-mono text-[11px] font-bold">
                                                    📅 Claim Date: {{ formatDate(req.submitted_at || req.created_at) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Requested Amount -->
                                <td class="px-5 py-3.5 font-mono">
                                    <div class="text-sm font-bold text-gray-900">
                                        ₹{{ req.requested_amount_cr }} <span class="text-xs text-gray-500 font-normal">Cr</span>
                                    </div>
                                    <div class="text-[11px] text-gray-400">
                                        Instalment {{ req.instalment_number ? '#' + req.instalment_number : '—' }}
                                    </div>
                                </td>

                                <!-- Progress (Physical & Financial) -->
                                <td class="px-5 py-3.5">
                                    <div class="space-y-1.5 min-w-[150px] max-w-[200px]">
                                        <!-- Physical -->
                                        <div>
                                            <div class="flex justify-between text-[10px] font-semibold text-gray-500 mb-0.5">
                                                <span>Physical</span>
                                                <span class="text-blue-700 font-bold">{{ req.physical_progress_pct }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                                <div
                                                    class="bg-blue-600 h-1.5 rounded-full transition-all duration-300"
                                                    :style="{ width: Math.min(100, Math.max(0, req.physical_progress_pct)) + '%' }"
                                                ></div>
                                            </div>
                                        </div>
                                        <!-- Financial -->
                                        <div>
                                            <div class="flex justify-between text-[10px] font-semibold text-gray-500 mb-0.5">
                                                <span>Financial</span>
                                                <span class="text-emerald-700 font-bold">{{ req.financial_progress_pct }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                                <div
                                                    class="bg-emerald-600 h-1.5 rounded-full transition-all duration-300"
                                                    :style="{ width: Math.min(100, Math.max(0, req.financial_progress_pct)) + '%' }"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Inspection Status -->
                                <td class="px-5 py-3.5">
                                    <span
                                        :class="['inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold rounded-lg border leading-tight', getReportStatus(req).class]"
                                    >
                                        <span :class="['w-1.5 h-1.5 rounded-full', getReportStatus(req).dot]"></span>
                                        {{ getReportStatus(req).label }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-3.5 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <!-- Primary Inspection Action for BB / Admin -->
                                        <Link
                                            v-if="isBBOrAdmin && (req.status === 'SUBMITTED_TO_BB' || req.status === 'BB_MONITORING_PENDING')"
                                            :href="route('monitoring-requests.report.create', req.id)"
                                            class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white px-3 py-1.5 rounded-xl text-xs font-bold shadow-sm hover:shadow transition-all"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <span>{{ req.bb_monitoring_report?.status === 'DRAFT' ? 'Resume Report' : 'Fill Report' }}</span>
                                        </Link>

                                        <!-- View Dossier -->
                                        <Link
                                            :href="route('fund-release.show', req.id)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 shadow-sm transition"
                                        >
                                            <span>View Dossier</span>
                                            <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-indigo-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredRequests.length === 0">
                                <td colspan="5" class="px-5 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <div class="w-12 h-12 rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center text-xl">
                                            ✅
                                        </div>
                                        <p class="font-bold text-gray-700 text-sm">
                                            {{ totalCount === 0 ? 'Queue is clear. No pending inspections.' : 'No monitoring requests match your current filters.' }}
                                        </p>
                                        <p class="text-xs text-gray-400 max-w-sm">
                                            {{ totalCount === 0 
                                                ? 'When State Agencies submit payment claims, they will automatically appear here for Brahmaputra Board field verification.' 
                                                : 'Try clearing your search query or selecting a different status filter.' }}
                                        </p>
                                        <button
                                            v-if="statusFilter !== 'ALL' || search"
                                            @click="statusFilter = 'ALL'; search = ''"
                                            class="mt-2 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-3.5 py-1.5 rounded-lg transition"
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
                <div v-if="filteredRequests.length > 0" class="px-5 py-3.5 bg-gray-50/70 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div class="text-gray-500 font-medium">
                        Showing <span class="font-bold text-gray-800">{{ (currentPage - 1) * perPage + 1 }}</span>
                        to <span class="font-bold text-gray-800">{{ Math.min(currentPage * perPage, filteredRequests.length) }}</span>
                        of <span class="font-bold text-gray-800">{{ filteredRequests.length }}</span> entries
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
                            :class="p === currentPage ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-gray-600 hover:bg-gray-100 border border-transparent'"
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
