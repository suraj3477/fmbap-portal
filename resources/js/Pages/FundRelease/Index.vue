<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    requests: {
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

// ─── Metrics calculation ───
const totalCount = computed(() => props.requests.length);
const totalAmountCr = computed(() => {
    const sum = props.requests.reduce((acc, r) => acc + (parseFloat(r.requested_amount_cr) || 0), 0);
    return sum.toFixed(2);
});

const approvedCount = computed(() => 
    props.requests.filter(r => r.status === 'APPROVED').length
);

const underReviewCount = computed(() => 
    props.requests.filter(r => ['SUBMITTED_TO_BB', 'BB_MONITORING_PENDING', 'FORWARDED_TO_MOJS'].includes(r.status)).length
);

const needsCorrectionCount = computed(() => 
    props.requests.filter(r => r.status === 'NEEDS_CORRECTION').length
);

const draftCount = computed(() => 
    props.requests.filter(r => r.status === 'DRAFT').length
);

// ─── Filtering ───
const filteredRequests = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.requests.filter(r => {
        // Search term matching
        const code = r.scheme?.scheme_code?.toLowerCase() || '';
        const name = r.scheme?.scheme_name?.toLowerCase() || '';
        const idStr = String(r.id);
        const instStr = String(r.instalment_number || '');
        const matchesQuery = !q || code.includes(q) || name.includes(q) || idStr.includes(q) || instStr.includes(q);

        if (!matchesQuery) return false;

        // Status tab matching
        if (statusFilter.value === 'APPROVED') return r.status === 'APPROVED';
        if (statusFilter.value === 'UNDER_REVIEW') return ['SUBMITTED_TO_BB', 'BB_MONITORING_PENDING', 'FORWARDED_TO_MOJS'].includes(r.status);
        if (statusFilter.value === 'NEEDS_CORRECTION') return r.status === 'NEEDS_CORRECTION';
        if (statusFilter.value === 'DRAFT') return r.status === 'DRAFT';

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
            return { label: 'Approved', class: 'bg-emerald-50 text-emerald-700 border-emerald-200', dot: 'bg-emerald-500' };
        case 'FORWARDED_TO_MOJS':
            return { label: 'MoJS Review', class: 'bg-indigo-50 text-indigo-700 border-indigo-200', dot: 'bg-indigo-500' };
        case 'BB_MONITORING_PENDING':
            return { label: 'BB Inspection', class: 'bg-purple-50 text-purple-700 border-purple-200', dot: 'bg-purple-500' };
        case 'SUBMITTED_TO_BB':
            return { label: 'Submitted to BB', class: 'bg-blue-50 text-blue-700 border-blue-200', dot: 'bg-blue-500' };
        case 'NEEDS_CORRECTION':
            return { label: 'Correction Req.', class: 'bg-amber-50 text-amber-800 border-amber-300', dot: 'bg-amber-500' };
        case 'REJECTED':
            return { label: 'Rejected', class: 'bg-rose-50 text-rose-700 border-rose-200', dot: 'bg-rose-500' };
        case 'DRAFT':
        default:
            return { label: 'Draft', class: 'bg-slate-100 text-slate-700 border-slate-200', dot: 'bg-slate-400' };
    }
};
</script>

<template>
    <Head title="Fund Release Requests — FMBAP" />

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
                                Fund Release Requests
                            </h2>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200">
                                Module 1
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Payment claims, instalment tracking, and central release approvals under FMBAP
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link
                        v-if="effectiveRole === 'state_official' || effectiveRole === 'super_admin'"
                        :href="route('fund-release.create')"
                        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-sm hover:shadow transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Payment Request
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-5 max-w-screen-2xl mx-auto">

            <!-- ─── STAT STRIP ─── -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold text-gray-900 leading-none">{{ totalCount }}</div>
                        <div class="text-xs text-gray-500 font-medium mt-1">Total Requests</div>
                    </div>
                </div>

                <div class="w-px h-10 bg-gray-200 hidden sm:block"></div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                        ₹
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold text-emerald-700 leading-none">₹{{ totalAmountCr }} Cr</div>
                        <div class="text-xs text-gray-500 font-medium mt-1">Total Claimed</div>
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
                        <div class="text-xs text-gray-500 font-medium mt-1">Approved & Released</div>
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
                                class="w-full border border-gray-200 rounded-xl py-2 pl-9 pr-9 text-xs bg-white text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
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
                                Correction ({{ needsCorrectionCount }})
                            </button>
                            <button
                                v-if="draftCount > 0"
                                @click="statusFilter = 'DRAFT'"
                                :class="statusFilter === 'DRAFT' ? 'bg-slate-700 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                                class="text-xs font-semibold px-3 py-1.5 rounded-lg transition"
                            >
                                Drafts ({{ draftCount }})
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
                                <th class="px-5 py-3.5">Instalment</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5">Date</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            <tr
                                v-for="req in paginatedRequests"
                                :key="req.id"
                                class="hover:bg-blue-50/40 transition-colors group"
                            >
                                <!-- Req ID & Scheme -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-start gap-2.5">
                                        <span class="font-mono text-xs font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded border border-gray-200 shrink-0">
                                            #{{ req.id }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="font-bold text-blue-700 text-xs flex items-center gap-1.5">
                                                <span>{{ req.scheme?.scheme_code || 'UNASSIGNED' }}</span>
                                            </div>
                                            <div class="text-xs text-gray-600 truncate max-w-xs md:max-w-md mt-0.5" :title="req.scheme?.scheme_name">
                                                {{ req.scheme?.scheme_name || 'No scheme description available' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Requested Amount -->
                                <td class="px-5 py-3.5 font-mono">
                                    <div class="text-sm font-bold text-gray-900">
                                        ₹{{ req.requested_amount_cr }} <span class="text-xs text-gray-500 font-normal">Cr</span>
                                    </div>
                                </td>

                                <!-- Instalment -->
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                        Instalment {{ req.instalment_number ? '#' + req.instalment_number : 'N/A' }}
                                    </span>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-5 py-3.5">
                                    <span
                                        :class="['inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold rounded-lg border leading-tight', getStatusBadge(req.status).class]"
                                    >
                                        <span :class="['w-1.5 h-1.5 rounded-full', getStatusBadge(req.status).dot]"></span>
                                        {{ getStatusBadge(req.status).label }}
                                    </span>
                                </td>

                                <!-- Created Date -->
                                <td class="px-5 py-3.5 text-gray-500 font-medium text-xs">
                                    {{ formatDate(req.created_at) }}
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-3.5 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <Link
                                            v-if="req.status === 'DRAFT' || req.status === 'NEEDS_CORRECTION'"
                                            :href="route('fund-release.edit', req.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 transition"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </Link>

                                        <Link
                                            v-if="req.status === 'DRAFT'"
                                            :href="route('fund-release.destroy', req.id)"
                                            method="delete"
                                            as="button"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 transition"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Delete
                                        </Link>

                                        <Link
                                            :href="route('fund-release.show', req.id)"
                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 shadow-xs transition"
                                        >
                                            <span>View Dossier</span>
                                            <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-blue-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredRequests.length === 0">
                                <td colspan="6" class="px-5 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <div class="w-12 h-12 rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center text-xl">
                                            📄
                                        </div>
                                        <p class="font-bold text-gray-700 text-sm">
                                            {{ totalCount === 0 ? 'No fund release requests submitted yet.' : 'No requests match your current filters.' }}
                                        </p>
                                        <p class="text-xs text-gray-400 max-w-sm">
                                            {{ totalCount === 0 && (effectiveRole === 'state_official' || effectiveRole === 'super_admin') 
                                                ? 'Click "+ New Payment Request" above to initiate a new claim for an approved scheme.' 
                                                : 'Try clearing the search query or changing the status filter tabs.' }}
                                        </p>
                                        <button
                                            v-if="statusFilter !== 'ALL' || search"
                                            @click="statusFilter = 'ALL'; search = ''"
                                            class="mt-2 text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 px-3.5 py-1.5 rounded-lg transition"
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
                            :class="p === currentPage ? 'bg-blue-600 text-white font-bold shadow-xs' : 'text-gray-600 hover:bg-gray-100 border border-transparent'"
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
