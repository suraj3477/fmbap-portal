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

// ── Active Navigation Tab ──────────────────────────────────────────────────
// Tab 1: 'MASTER_MONITOR' (Master Project Status & Pendency Table)
// Tab 2: 'AGENCY_RADAR' (Authority-Wise Breakdown: BB • MoJS • State)
const activeTab = ref('MASTER_MONITOR');

// ── Global Backend Filters ─────────────────────────────────────────────────
const selectedState = ref(props.filters.state || 'ALL');
const selectedBasin = ref(props.filters.basin || 'ALL');
const selectedStatus = ref(props.filters.status || 'ALL');

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
    authorityFilter.value = 'ALL';
    typeFilter.value = 'ALL';
    statusFilter.value = 'ALL';
    agingFilter.value = 'ALL';
    agencyTab.value = 'ALL';
    applyFilters();
};

// ── Export URLs ────────────────────────────────────────────────────────────
const excelExportUrl = computed(() => {
    const params = new URLSearchParams();
    if (selectedState.value !== 'ALL') params.append('state', selectedState.value);
    if (selectedBasin.value !== 'ALL') params.append('basin', selectedBasin.value);
    if (selectedStatus.value !== 'ALL') params.append('status', selectedStatus.value);
    if (authorityFilter.value !== 'ALL') params.append('pending_with', authorityFilter.value);
    return `${route('analytics.export-project-monitoring-excel')}?${params.toString()}`;
});

const triggerPrint = () => {
    window.print();
};

// ── Shared Master Schemes List & KPIs ──────────────────────────────────────
const rawProjectsList = computed(() => {
    return props.analytics.project_monitoring || props.analytics.parliamentary_list || [];
});

const pmKpis = computed(() => {
    if (props.analytics.project_monitoring_kpis) {
        return props.analytics.project_monitoring_kpis;
    }
    const list = rawProjectsList.value;
    const pendingBb = list.filter(p => p.pending_with_party === 'BB');
    const pendingMojs = list.filter(p => p.pending_with_party === 'MOJS');
    const pendingState = list.filter(p => p.pending_with_party === 'STATE');
    const settled = list.filter(p => p.pending_with_party === 'SETTLED');
    const overdue = list.filter(p => p.is_overdue);

    return {
        total_projects: list.length,
        pending_bb_count: pendingBb.length,
        pending_bb_amount_cr: pendingBb.reduce((acc, p) => acc + (parseFloat(p.active_claim_amount_cr) || 0), 0).toFixed(2),
        pending_bb_fund_count: pendingBb.filter(p => p.is_fund_pendency).length,
        pending_bb_project_count: pendingBb.filter(p => p.is_project_pendency).length,
        pending_mojs_count: pendingMojs.length,
        pending_mojs_amount_cr: pendingMojs.reduce((acc, p) => acc + (parseFloat(p.active_claim_amount_cr) || 0), 0).toFixed(2),
        pending_mojs_fund_count: pendingMojs.filter(p => p.is_fund_pendency).length,
        pending_mojs_project_count: pendingMojs.filter(p => p.is_project_pendency).length,
        pending_state_count: pendingState.length,
        pending_state_amount_cr: pendingState.reduce((acc, p) => acc + (parseFloat(p.active_claim_amount_cr) || 0), 0).toFixed(2),
        pending_state_fund_count: pendingState.filter(p => p.is_fund_pendency).length,
        pending_state_project_count: pendingState.filter(p => p.is_project_pendency).length,
        fund_pendency_count: list.filter(p => p.is_fund_pendency).length,
        fund_pendency_amount_cr: list.filter(p => p.is_fund_pendency).reduce((acc, p) => acc + (parseFloat(p.active_claim_amount_cr) || 0), 0).toFixed(2),
        project_pendency_count: list.filter(p => p.is_project_pendency).length,
        settled_count: settled.length,
        overdue_count: overdue.length,
        critical_overdue_count: list.filter(p => p.days_pending > 60 && !p.is_completed).length,
        avg_pendency_days: 0,
        total_sanctioned_cr: props.analytics.macro?.total_sanctioned_cr || 0,
        total_central_share_cr: props.analytics.macro?.total_central_share_cr || 0,
        total_released_cr: props.analytics.macro?.total_released_cr || 0,
        disbursal_velocity: props.analytics.macro?.disbursal_velocity_pct || 0,
        avg_physical_progress: props.analytics.macro?.avg_physical_progress || 0,
        completed_count: list.filter(p => p.is_completed).length,
        ongoing_count: list.filter(p => !p.is_completed).length,
    };
});

// ── Badges & Formatting ────────────────────────────────────────────────────
const getAuthorityBadge = (party) => {
    switch (party) {
        case 'BB':
            return {
                bg: 'bg-purple-100 text-purple-900 border-purple-300',
                dot: 'bg-purple-600',
                icon: '🏢',
                name: 'Brahmaputra Board (BB)',
                short: 'BB Field Review',
            };
        case 'MOJS':
            return {
                bg: 'bg-blue-100 text-blue-900 border-blue-300',
                dot: 'bg-blue-600',
                icon: '🏛️',
                name: 'Ministry of Jal Shakti (MoJS)',
                short: 'MoJS Sanction',
            };
        case 'STATE':
            return {
                bg: 'bg-amber-100 text-amber-900 border-amber-300',
                dot: 'bg-amber-600',
                icon: '📍',
                name: 'State WRD Govt',
                short: 'State Desk',
            };
        case 'SETTLED':
            return {
                bg: 'bg-emerald-100 text-emerald-900 border-emerald-300',
                dot: 'bg-emerald-600',
                icon: '✅',
                name: 'Settled & Done',
                short: 'Completed',
            };
        default:
            return {
                bg: 'bg-slate-100 text-slate-800 border-slate-300',
                dot: 'bg-slate-500',
                icon: '⏳',
                name: 'Under Review',
                short: 'Review',
            };
    }
};

const getAgingBadge = (days, isCompleted) => {
    if (isCompleted) {
        return {
            bg: 'bg-emerald-100 text-emerald-900 border-emerald-300 font-bold',
            dot: 'bg-emerald-600',
            text: 'Completed',
            level: 'SETTLED',
        };
    }
    if (days > 60) {
        return {
            bg: 'bg-rose-100 text-rose-900 border-rose-400 font-black animate-pulse',
            dot: 'bg-rose-600',
            text: `${days}d (Critical >60d)`,
            level: 'CRITICAL',
        };
    }
    if (days > 30) {
        return {
            bg: 'bg-amber-100 text-amber-900 border-amber-300 font-black',
            dot: 'bg-amber-600',
            text: `${days}d (Delayed >30d)`,
            level: 'OVERDUE',
        };
    }
    if (days > 15) {
        return {
            bg: 'bg-blue-100 text-blue-900 border-blue-300 font-bold',
            dot: 'bg-blue-600',
            text: `${days}d (Standard Window)`,
            level: 'MODERATE',
        };
    }
    return {
        bg: 'bg-emerald-50 text-emerald-900 border-emerald-300 font-bold',
        dot: 'bg-emerald-600',
        text: `${days}d (Normal)`,
        level: 'NORMAL',
    };
};

const getPendencyTypeBadge = (type) => {
    if (type === 'FUND') {
        return {
            bg: 'bg-indigo-100 text-indigo-950 border-indigo-300 font-black',
            dot: 'bg-indigo-600',
            icon: '💰',
            label: 'FUND PENDENCY',
        };
    }
    if (type === 'PROJECT') {
        return {
            bg: 'bg-sky-100 text-sky-950 border-sky-300 font-black',
            dot: 'bg-sky-600',
            icon: '🏗️',
            label: 'PROJECT PENDENCY',
        };
    }
    return {
        bg: 'bg-emerald-100 text-emerald-900 border-emerald-300 font-bold',
        dot: 'bg-emerald-600',
        icon: '✅',
        label: 'NO PENDENCY',
    };
};

const formatStatus = (status, pct) => {
    const s = (status || '').toLowerCase().trim();
    if (s === 'completed' || pct >= 100) return 'Completed';
    if (s.includes('foreclosed') || s.includes('stalled')) return 'Stalled';
    if (s === 'not started' || (s.includes('not') && (pct === null || pct <= 0))) return 'Not Started (0%)';
    return `Ongoing (${pct || 0}%)`;
};

const statusBadgeClass = (status, pct) => {
    const s = (status || '').toLowerCase().trim();
    if (s === 'completed' || pct >= 100) {
        return 'bg-emerald-100 text-emerald-900 border-emerald-300 font-bold';
    }
    if (s.includes('foreclosed') || s.includes('stalled')) {
        return 'bg-rose-100 text-rose-900 border-rose-300 font-black';
    }
    if (s === 'not started' || (s.includes('not') && (pct === null || pct <= 0))) {
        return 'bg-slate-100 text-slate-700 border-slate-300 font-medium';
    }
    if (pct < 20) {
        return 'bg-amber-100 text-amber-900 border-amber-300 font-bold';
    }
    return 'bg-blue-100 text-blue-900 border-blue-300 font-bold';
};

// ═══════════════════════════════════════════════════════════════════════════
// TAB 1: 📋 MASTER PROJECT STATUS & PENDENCY MONITOR STATE & FILTERING
// ═══════════════════════════════════════════════════════════════════════════
const searchQuery = ref('');
const authorityFilter = ref('ALL'); // 'ALL' | 'BB' | 'MOJS' | 'STATE' | 'OVERDUE' | 'CRITICAL' | 'SETTLED'
const typeFilter = ref('ALL'); // 'ALL' | 'FUND' | 'PROJECT' | 'SETTLED'
const statusFilter = ref('ALL'); // 'ALL' | 'ONGOING' | 'COMPLETED' | 'STALLED' | 'NOT_STARTED'
const agingFilter = ref('ALL'); // 'ALL' | '0_15' | '16_30' | '31_60' | 'above_60'
const sortField = ref('days_pending');
const sortAsc = ref(false);
const currentPage = ref(1);
const pageSize = ref(15);

const filteredProjects = computed(() => {
    let list = rawProjectsList.value;

    // Filter by Authority Pill
    if (authorityFilter.value !== 'ALL') {
        if (authorityFilter.value === 'OVERDUE') {
            list = list.filter(p => p.is_overdue);
        } else if (authorityFilter.value === 'CRITICAL') {
            list = list.filter(p => p.aging_bucket === 'above_60');
        } else if (authorityFilter.value === 'SETTLED') {
            list = list.filter(p => p.pending_with_party === 'SETTLED');
        } else {
            list = list.filter(p => p.pending_with_party === authorityFilter.value);
        }
    }

    // Filter by Pendency Type (Fund vs Project)
    if (typeFilter.value !== 'ALL') {
        if (typeFilter.value === 'FUND') {
            list = list.filter(p => p.is_fund_pendency);
        } else if (typeFilter.value === 'PROJECT') {
            list = list.filter(p => p.is_project_pendency);
        } else if (typeFilter.value === 'SETTLED') {
            list = list.filter(p => p.pending_with_party === 'SETTLED');
        }
    }

    // Filter by Execution Status
    if (statusFilter.value !== 'ALL') {
        if (statusFilter.value === 'COMPLETED') {
            list = list.filter(p => p.is_completed || (p.physical_status && p.physical_status.toLowerCase() === 'completed'));
        } else if (statusFilter.value === 'ONGOING') {
            list = list.filter(p => !p.is_completed && ((p.physical_status && p.physical_status.toLowerCase().includes('ongoing')) || (p.physical_progress_pct > 0 && p.physical_progress_pct < 100)));
        } else if (statusFilter.value === 'STALLED') {
            list = list.filter(p => !p.is_completed && p.physical_progress_pct < 20 && p.physical_status && p.physical_status.toLowerCase().includes('stall'));
        } else if (statusFilter.value === 'NOT_STARTED') {
            list = list.filter(p => !p.is_completed && p.physical_progress_pct <= 0);
        }
    }

    // Filter by Aging Bucket
    if (agingFilter.value !== 'ALL') {
        list = list.filter(p => p.aging_bucket === agingFilter.value);
    }

    // Filter by Search Query
    const q = searchQuery.value.trim().toLowerCase();
    if (q) {
        list = list.filter(p =>
            (p.scheme_code && p.scheme_code.toLowerCase().includes(q)) ||
            (p.scheme_name && p.scheme_name.toLowerCase().includes(q)) ||
            (p.state && p.state.toLowerCase().includes(q)) ||
            (p.district && p.district.toLowerCase().includes(q)) ||
            (p.pending_with && p.pending_with.toLowerCase().includes(q)) ||
            (p.pending_desk && p.pending_desk.toLowerCase().includes(q)) ||
            (p.pending_what_title && p.pending_what_title.toLowerCase().includes(q)) ||
            (p.pending_what_detail && p.pending_what_detail.toLowerCase().includes(q)) ||
            (p.active_ticket_id && p.active_ticket_id.toLowerCase().includes(q)) ||
            (p.action_required && p.action_required.toLowerCase().includes(q))
        );
    }

    // Sorting
    const field = sortField.value;
    const asc = sortAsc.value;
    return [...list].sort((a, b) => {
        let valA = a[field];
        let valB = b[field];

        if (field === 'days_pending' || field === 'physical_progress_pct' || field === 'financial_progress_pct' || field === 'disbursal_velocity_pct') {
            valA = parseFloat(valA) || 0;
            valB = parseFloat(valB) || 0;
            if (valA !== valB) {
                return asc ? valA - valB : valB - valA;
            }
            // Tie breaker for equal days_pending (e.g. 0 days for completed schemes):
            // Most recently completed/updated scheme comes at the top!
            const dateA = a.updated_at ? new Date(a.updated_at).getTime() : (a.id || 0);
            const dateB = b.updated_at ? new Date(b.updated_at).getTime() : (b.id || 0);
            return dateB - dateA;
        }

        if (field === 'active_claim_amount_cr' || field === 'sanctioned_amount_raw') {
            valA = parseFloat(a[field] || 0) || 0;
            valB = parseFloat(b[field] || 0) || 0;
            if (valA !== valB) {
                return asc ? valA - valB : valB - valA;
            }
            const dateA = a.updated_at ? new Date(a.updated_at).getTime() : (a.id || 0);
            const dateB = b.updated_at ? new Date(b.updated_at).getTime() : (b.id || 0);
            return dateB - dateA;
        }

        valA = (valA || '').toString().toLowerCase();
        valB = (valB || '').toString().toLowerCase();
        if (valA !== valB) {
            return asc ? valA.localeCompare(valB) : valB.localeCompare(valA);
        }
        const dateA = a.updated_at ? new Date(a.updated_at).getTime() : (a.id || 0);
        const dateB = b.updated_at ? new Date(b.updated_at).getTime() : (b.id || 0);
        return dateB - dateA;
    });
});

const paginatedProjects = computed(() => {
    if (pageSize.value === -1) return filteredProjects.value;
    const start = (currentPage.value - 1) * pageSize.value;
    return filteredProjects.value.slice(start, start + pageSize.value);
});

const totalPages = computed(() => {
    if (pageSize.value === -1) return 1;
    return Math.ceil(filteredProjects.value.length / pageSize.value) || 1;
});

const sortBy = (field) => {
    if (sortField.value === field) {
        sortAsc.value = !sortAsc.value;
    } else {
        sortField.value = field;
        sortAsc.value = false;
    }
};

const setAuthority = (auth) => {
    authorityFilter.value = auth;
    typeFilter.value = 'ALL';
    statusFilter.value = 'ALL';
    agingFilter.value = 'ALL';
    currentPage.value = 1;
};

const setType = (type) => {
    typeFilter.value = type;
    authorityFilter.value = 'ALL';
    statusFilter.value = 'ALL';
    agingFilter.value = 'ALL';
    currentPage.value = 1;
};

// ═══════════════════════════════════════════════════════════════════════════
// TAB 2: 🎯 AUTHORITY PENDENCY RADAR (BB vs MoJS vs State Breakdown)
// ═══════════════════════════════════════════════════════════════════════════
const agencyTab = ref('ALL'); // 'ALL' | 'BB' | 'MOJS' | 'STATE' | 'DELAYED'

// BB Lists
const bbFundClaims = computed(() => rawProjectsList.value.filter(p => p.pending_with_party === 'BB' && p.is_fund_pendency));
const bbProjectReports = computed(() => rawProjectsList.value.filter(p => p.pending_with_party === 'BB' && p.is_project_pendency));

// MoJS Lists
const mojsFundClaims = computed(() => rawProjectsList.value.filter(p => p.pending_with_party === 'MOJS' && p.is_fund_pendency));
const mojsProjectReviews = computed(() => rawProjectsList.value.filter(p => p.pending_with_party === 'MOJS' && p.is_project_pendency));

// State Lists
const stateFundClaims = computed(() => rawProjectsList.value.filter(p => p.pending_with_party === 'STATE' && p.is_fund_pendency));
const stateProjectWorks = computed(() => rawProjectsList.value.filter(p => p.pending_with_party === 'STATE' && p.is_project_pendency));

// Delayed List
const allDelayedProjects = computed(() => rawProjectsList.value.filter(p => p.is_overdue).sort((a, b) => b.days_pending - a.days_pending));

// ── 360° Project Slide-Over Drawer ─────────────────────────────────────────
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

const switchTab = (tab) => {
    activeTab.value = tab;
    closeSchemeDrawer();
};

if (typeof window !== 'undefined') {
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeSchemeDrawer();
        }
    });
}
</script>

<template>
    <Head title="Executive MIS: Project Status &amp; Pendency Radar - FMBAP" />

    <AuthenticatedLayout>
        <!-- Top Executive Header Ribbon -->
        <template #header>
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 print:hidden">
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <span class="p-2 bg-blue-100 text-[#0F4C9F] rounded-xl text-xl shadow-xs font-black">📈</span>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            Executive MIS: Project Status &amp; Pendency Cockpit
                        </h1>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-[#0F4C9F] text-white shadow-xs">
                            Live Inter-Agency Oversight
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        Crystal-clear monitoring: exactly <strong class="text-slate-800">who the file is pending with</strong> (Brahmaputra Board, MoJS, or State WRD), <strong class="text-slate-800">what is pending (Project Report or Fund Claim)</strong>, and <strong class="text-slate-800">from how many days</strong>.
                    </p>
                </div>

                <!-- Action Toolbar -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="triggerPrint"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl border border-slate-300 shadow-2xs transition cursor-pointer"
                        title="Print executive briefing view"
                    >
                        <span>🖨️ Print</span>
                    </button>

                    <a
                        :href="excelExportUrl"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white text-xs font-black rounded-xl shadow-xs transition cursor-pointer"
                        title="Download comprehensive Project Status &amp; Pendency Excel workbook"
                    >
                        <span>📥 Download Report (Excel)</span>
                    </a>

                    <button
                        type="button"
                        @click="resetFilters"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-800 text-xs font-bold rounded-xl border border-rose-200 shadow-2xs transition cursor-pointer"
                        title="Reset all filters"
                    >
                        <span>🔄 Reset Filters</span>
                    </button>
                </div>
            </div>
        </template>

        <!-- Main Workspace -->
        <div class="space-y-6 pb-12">



            <!-- ─── 2. DEDICATED 2-TAB NAVIGATION BAR ─── -->
            <div class="flex justify-center items-center flex-wrap border-b border-slate-200 bg-white rounded-t-2xl px-4 pt-2 gap-3 text-sm font-black shadow-2xs print:hidden">
                
                <!-- TAB 1: Master Project Status & Pendency Table -->
                <button
                    type="button"
                    @click="switchTab('MASTER_MONITOR')"
                    :class="activeTab === 'MASTER_MONITOR' ? 'border-b-2 border-[#0F4C9F] text-[#0F4C9F] bg-blue-50/70 font-black' : 'text-slate-500 hover:text-slate-900 font-bold'"
                    class="py-3 px-5 transition cursor-pointer flex items-center gap-2 rounded-t-xl"
                >
                    <span>📋 Project Status &amp; Pendency Master Report</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-black" :class="activeTab === 'MASTER_MONITOR' ? 'bg-[#0F4C9F] text-white' : 'bg-slate-100 text-slate-700'">
                        {{ rawProjectsList.length }} Schemes
                    </span>
                </button>

                <!-- TAB 2: Authority Breakdown Radar -->
                <button
                    type="button"
                    @click="switchTab('AGENCY_RADAR')"
                    :class="activeTab === 'AGENCY_RADAR' ? 'border-b-2 border-purple-600 text-purple-900 bg-purple-50/70 font-black' : 'text-slate-500 hover:text-slate-900 font-bold'"
                    class="py-3 px-5 transition cursor-pointer flex items-center gap-2 rounded-t-xl"
                >
                    <span>🎯 Authority Pendency Breakdown (BB &bull; MoJS &bull; State)</span>
                    <span
                        class="px-2 py-0.5 rounded-full text-xs font-black"
                        :class="(pmKpis.overdue_count || 0) > 0 ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-purple-100 text-purple-900'"
                    >
                        {{ (pmKpis.total_projects || 0) - (pmKpis.settled_count || 0) }} Pending
                    </span>
                </button>

            </div>

            <!-- ─── 3. TAB CONTENT PANELS ─── -->
            <div class="bg-white rounded-b-2xl border border-slate-200 border-t-0 p-5 sm:p-6 shadow-2xs">

                <!-- ═════════════════════════════════════════════════════════════ -->
                <!-- 📋 TAB 1: MASTER PROJECT STATUS & PENDENCY REPORT              -->
                <!-- ═════════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'MASTER_MONITOR'" class="space-y-6">
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                        <div>
                            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-black uppercase tracking-wider bg-blue-100 text-[#0F4C9F] border border-blue-200">
                                ALL SCHEMES STATUS &amp; PENDENCY DIRECTORY
                            </span>
                            <h3 class="text-base sm:text-lg font-black text-slate-900 mt-1">
                                Complete Project Status with Pending Level, Details, &amp; Days
                            </h3>
                            <p class="text-xs text-slate-500">
                                Full transparency: ground physical execution, exact authority holding the file, what is pending, and delay duration.
                            </p>
                        </div>
                    </div>

                    <!-- 1-Click Interactive Quick Filter Pills -->
                    <div class="flex flex-wrap items-center justify-between gap-2.5 bg-slate-50 p-3 rounded-2xl border border-slate-200 text-xs">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 mr-1">Authority Filter:</span>

                            <!-- All Files -->
                            <button
                                type="button"
                                @click="setAuthority('ALL')"
                                :class="authorityFilter === 'ALL' && typeFilter === 'ALL' ? 'bg-[#0F4C9F] text-white shadow-xs font-black' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-300 font-bold'"
                                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1.5"
                            >
                                <span>All Schemes</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="authorityFilter === 'ALL' && typeFilter === 'ALL' ? 'bg-blue-900 text-white' : 'bg-slate-200 text-slate-800'">
                                    {{ rawProjectsList.length }}
                                </span>
                            </button>

                            <!-- BB Desk -->
                            <button
                                type="button"
                                @click="setAuthority('BB')"
                                :class="authorityFilter === 'BB' ? 'bg-purple-700 text-white shadow-xs font-black' : 'bg-purple-50 text-purple-900 hover:bg-purple-100 border border-purple-200 font-bold'"
                                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1.5"
                            >
                                <span>🏢 Pending at BB</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="authorityFilter === 'BB' ? 'bg-purple-900 text-purple-100' : 'bg-purple-200 text-purple-900'">
                                    {{ pmKpis.pending_bb_count }}
                                </span>
                            </button>

                            <!-- MoJS Desk -->
                            <button
                                type="button"
                                @click="setAuthority('MOJS')"
                                :class="authorityFilter === 'MOJS' ? 'bg-blue-700 text-white shadow-xs font-black' : 'bg-blue-50 text-blue-900 hover:bg-blue-100 border border-blue-200 font-bold'"
                                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1.5"
                            >
                                <span>🏛️ Pending at MoJS</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="authorityFilter === 'MOJS' ? 'bg-blue-900 text-blue-100' : 'bg-blue-200 text-blue-900'">
                                    {{ pmKpis.pending_mojs_count }}
                                </span>
                            </button>

                            <!-- State Desk -->
                            <button
                                type="button"
                                @click="setAuthority('STATE')"
                                :class="authorityFilter === 'STATE' ? 'bg-amber-700 text-white shadow-xs font-black' : 'bg-amber-50 text-amber-900 hover:bg-amber-100 border border-amber-200 font-bold'"
                                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1.5"
                            >
                                <span>📍 Pending at State</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="authorityFilter === 'STATE' ? 'bg-amber-900 text-amber-100' : 'bg-amber-200 text-amber-900'">
                                    {{ pmKpis.pending_state_count }}
                                </span>
                            </button>

                            <!-- Settled / Completed (Zero Pendency) -->
                            <button
                                type="button"
                                @click="setAuthority('SETTLED')"
                                :class="authorityFilter === 'SETTLED' ? 'bg-emerald-700 text-white shadow-xs font-black' : 'bg-emerald-50 text-emerald-900 hover:bg-emerald-100 border border-emerald-300 font-bold'"
                                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1.5"
                                title="Show completed schemes settled with zero pendency"
                            >
                                <span>✅ Settled / Done</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="authorityFilter === 'SETTLED' ? 'bg-emerald-950 text-white' : 'bg-emerald-200 text-emerald-900'">
                                    {{ pmKpis.settled_count }}
                                </span>
                            </button>

                            <span class="text-slate-300">|</span>

                            <!-- FUND PENDENCY -->
                            <button
                                type="button"
                                @click="setType(typeFilter === 'FUND' ? 'ALL' : 'FUND')"
                                :class="typeFilter === 'FUND' ? 'bg-indigo-700 text-white shadow-xs font-black' : 'bg-indigo-50 text-indigo-950 hover:bg-indigo-100 border border-indigo-300 font-bold'"
                                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1.5"
                                title="Fund Pendency"
                            >
                                <span>💰 Fund Pendency</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="typeFilter === 'FUND' ? 'bg-indigo-950 text-white' : 'bg-indigo-200 text-indigo-900'">
                                    {{ pmKpis.fund_pendency_count }}
                                </span>
                            </button>

                            <!-- PROJECT PENDENCY ONLY -->
                            <button
                                type="button"
                                @click="setType(typeFilter === 'PROJECT' ? 'ALL' : 'PROJECT')"
                                :class="typeFilter === 'PROJECT' ? 'bg-sky-700 text-white shadow-xs font-black' : 'bg-sky-50 text-sky-950 hover:bg-sky-100 border border-sky-300 font-black'"
                                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1.5"
                                title="Show only schemes where project execution or progress reports are pending"
                            >
                                <span>🏗️ Project Progress Only</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="typeFilter === 'PROJECT' ? 'bg-sky-950 text-white' : 'bg-sky-200 text-sky-900'">
                                    {{ pmKpis.project_pendency_count }}
                                </span>
                            </button>

                            <!-- Delayed (>30d) -->
                            <button
                                type="button"
                                @click="setAuthority(authorityFilter === 'OVERDUE' ? 'ALL' : 'OVERDUE')"
                                :class="authorityFilter === 'OVERDUE' ? 'bg-rose-700 text-white shadow-xs font-black' : 'bg-rose-50 text-rose-900 hover:bg-rose-100 border border-rose-300 font-bold'"
                                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1.5"
                            >
                                <span>🚨 Delayed (>30d)</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="authorityFilter === 'OVERDUE' ? 'bg-rose-900 text-rose-100' : 'bg-rose-200 text-rose-900'">
                                    {{ pmKpis.overdue_count }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Secondary Search & Filter Bar -->
                    <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- State Dropdown -->
                            <div class="flex items-center gap-1.5">
                                <label class="font-bold text-slate-600">State:</label>
                                <select
                                    v-model="selectedState"
                                    @change="applyFilters"
                                    class="rounded-xl border-slate-300 text-xs py-1.5 pr-8 font-semibold text-slate-800 focus:ring-[#0F4C9F]"
                                >
                                    <option value="ALL">All States</option>
                                    <option v-for="st in availableStates" :key="st" :value="st">{{ st }}</option>
                                </select>
                            </div>

                            <!-- Search Input -->
                            <div class="relative w-72">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search project, #REQ, authority, action..."
                                    class="w-full text-xs rounded-xl border-slate-300 pl-8 pr-3 py-1.5 focus:border-[#0F4C9F] focus:ring-[#0F4C9F]"
                                />
                                <span class="absolute left-2.5 top-2 text-slate-400">🔍</span>
                            </div>
                        </div>

                        <div class="text-[11px] font-bold text-slate-500">
                            Showing <strong class="text-slate-900">{{ filteredProjects.length }}</strong> schemes
                        </div>
                    </div>

                    <!-- ─── MASTER TABLE: PROJECT STATUS & PENDENCY DIRECTORY ─── -->
                    <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-2xs">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-100 text-slate-800 uppercase font-black text-[10px] border-b border-slate-200">
                                <tr>
                                    <th class="py-3 px-3 text-center">#</th>
                                    <th @click="sortBy('scheme_code')" class="py-3 px-3 cursor-pointer hover:bg-slate-200 transition">
                                        Scheme Code &amp; State <span class="text-slate-400">↕</span>
                                    </th>
                                    <th class="py-3 px-3">Project Title</th>
                                    <th @click="sortBy('physical_progress_pct')" class="py-3 px-3 cursor-pointer hover:bg-slate-200 transition min-w-[170px]" title="Click to sort by Progress">
                                        <div class="flex items-center justify-between">
                                            <span>PHYSICAL &amp; FINANCIAL PROGRESS</span>
                                            <span class="text-slate-400">↕</span>
                                        </div>
                                    </th>
                                    <th class="py-3 px-3 text-center bg-purple-50/60 border-l border-r border-purple-200 min-w-[160px]">
                                        PENDING AT WHAT LEVEL?
                                    </th>
                                    <th class="py-3 px-3 bg-indigo-50/60 border-r border-indigo-200 min-w-[260px]">
                                        PENDENCY: WHAT FUND / PROGRESS
                                    </th>
                                    <th @click="sortBy('days_pending')" class="py-3 px-3 text-center cursor-pointer hover:bg-slate-200 transition whitespace-nowrap min-w-[130px]">
                                        FROM HOW MANY DAYS <span class="text-slate-400">↕</span>
                                    </th>
                                    <th class="py-3 px-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(p, idx) in paginatedProjects"
                                    :key="p.id"
                                    class="hover:bg-blue-50/20 transition"
                                    :class="p.is_overdue ? 'bg-rose-50/20' : ''"
                                >
                                    <!-- # -->
                                    <td class="py-3.5 px-3 text-center font-mono text-slate-400 font-bold">
                                        {{ (currentPage - 1) * pageSize + idx + 1 }}
                                    </td>

                                    <!-- Scheme Code & State -->
                                    <td class="py-3.5 px-3 whitespace-nowrap">
                                        <div class="font-mono font-black text-[#0F4C9F] text-xs">{{ p.scheme_code }}</div>
                                        <div class="text-[10px] font-semibold text-slate-700 mt-0.5">{{ p.state }}</div>
                                        <div class="text-[10px] text-slate-400">{{ p.district }}</div>
                                    </td>

                                    <!-- Project Title -->
                                    <td class="py-3.5 px-3 max-w-xs">
                                        <div class="font-bold text-slate-900 leading-snug line-clamp-2" :title="p.scheme_name">
                                            {{ p.scheme_name }}
                                        </div>
                                        <div class="mt-1 flex items-center gap-2">
                                            <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-slate-100 text-slate-700 border">
                                                {{ p.river_basin }}
                                            </span>
                                            <span class="text-[10px] text-slate-500 font-mono">Outlay: ₹{{ p.sanctioned_amount_cr }} Cr</span>
                                        </div>
                                    </td>

                                    <!-- 🏗️ & 💰 Physical & Financial Progress Column -->
                                    <td class="py-3.5 px-3">
                                        <!-- Completed Scheme -->
                                        <div v-if="p.is_completed || (p.physical_status && p.physical_status.toLowerCase() === 'completed') || p.physical_progress_pct >= 100" class="space-y-1">
                                            <div class="flex items-center justify-between">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-900 border border-emerald-300">
                                                    ✅ Completed
                                                </span>
                                                <span class="text-[10px] font-mono font-black text-emerald-700">100%</span>
                                            </div>
                                            <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                                <div class="h-1.5 rounded-full bg-emerald-600 w-full"></div>
                                            </div>
                                            <div class="flex items-center justify-between text-[9px] text-slate-500 font-mono">
                                                <span>Phys 100% &bull; Fin 100%</span>
                                                <span>₹{{ p.released_central_cr }} Cr</span>
                                            </div>
                                        </div>

                                        <!-- Ongoing Scheme (Showing BOTH Physical & Financial Progress) -->
                                        <div v-else class="space-y-1.5 min-w-[160px]">
                                            <!-- Execution Stage Badge & Outlay -->
                                            <div class="flex items-center justify-between">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                                    :class="statusBadgeClass(p.physical_status, p.physical_progress_pct)"
                                                >
                                                    {{ formatStatus(p.physical_status, p.physical_progress_pct) }}
                                                </span>
                                                <span class="text-[9px] font-mono text-slate-500 font-semibold" title="Total Sanctioned Outlay">
                                                    ₹{{ p.sanctioned_amount_cr }} Cr
                                                </span>
                                            </div>

                                            <!-- 🏗️ Physical Progress (Civil Works) -->
                                            <div>
                                                <div class="flex items-center justify-between text-[10px] font-bold">
                                                    <span class="text-slate-600 flex items-center gap-1">
                                                        <span class="text-blue-600 text-[11px]">🏗️</span> Physical:
                                                    </span>
                                                    <span class="font-mono font-black" :class="p.physical_progress_pct >= 40 ? 'text-blue-700' : (p.physical_progress_pct > 0 ? 'text-amber-700' : 'text-slate-500')">
                                                        {{ p.physical_progress_pct }}%
                                                    </span>
                                                </div>
                                                <div class="w-full bg-slate-200 rounded-full h-1.5 mt-0.5 overflow-hidden">
                                                    <div
                                                        class="h-1.5 rounded-full transition-all"
                                                        :class="p.physical_progress_pct >= 40 ? 'bg-blue-600' : (p.physical_progress_pct > 0 ? 'bg-amber-500' : 'bg-slate-300')"
                                                        :style="{ width: `${Math.min(100, p.physical_progress_pct)}%` }"
                                                    ></div>
                                                </div>
                                            </div>

                                            <!-- 💰 Financial Progress (Disbursed Funds) -->
                                            <div>
                                                <div class="flex items-center justify-between text-[10px] font-bold">
                                                    <span class="text-slate-600 flex items-center gap-1">
                                                        <span class="text-emerald-600 text-[11px]">💰</span> Financial:
                                                    </span>
                                                    <span class="font-mono font-black text-emerald-800">
                                                        {{ p.financial_progress_pct || p.disbursal_velocity_pct }}%
                                                    </span>
                                                </div>
                                                <div class="w-full bg-slate-200 rounded-full h-1.5 mt-0.5 overflow-hidden">
                                                    <div
                                                        class="h-1.5 rounded-full bg-emerald-600 transition-all"
                                                        :style="{ width: `${Math.min(100, p.financial_progress_pct || p.disbursal_velocity_pct)}%` }"
                                                    ></div>
                                                </div>
                                                <div class="flex items-center justify-between text-[9px] text-slate-500 font-mono mt-0.5">
                                                    <span title="Central Share Released">Rel: ₹{{ p.released_central_cr }} Cr</span>
                                                    <span :class="(p.balance_central_raw > 0) ? 'text-amber-800 font-bold' : 'text-slate-400'" title="Central Share Balance">
                                                        Bal: ₹{{ p.balance_central_cr }} Cr
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 🏢 PENDING AT WHAT LEVEL? -->
                                    <td class="py-3.5 px-3 text-center bg-purple-50/30 border-l border-r border-purple-100 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-black border shadow-2xs"
                                            :class="getAuthorityBadge(p.pending_with_party).bg"
                                        >
                                            <span class="w-2 h-2 rounded-full" :class="getAuthorityBadge(p.pending_with_party).dot"></span>
                                            <span>{{ getAuthorityBadge(p.pending_with_party).icon }} {{ p.pending_with }}</span>
                                        </span>
                                        <div class="text-[10px] text-purple-900 font-semibold mt-1 truncate max-w-[170px] mx-auto" :title="p.pending_desk">
                                            {{ p.pending_desk }}
                                        </div>
                                    </td>

                                    <!-- 💰/🏗️ DETAILS -->
                                    <td class="py-3.5 px-3 bg-indigo-50/30 border-r border-indigo-100">
                                        <!-- Category Tag (Fund vs Project) -->
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] border shadow-2xs"
                                                :class="getPendencyTypeBadge(p.pendency_type).bg"
                                            >
                                                <span>{{ getPendencyTypeBadge(p.pendency_type).icon }}</span>
                                                <span>{{ getPendencyTypeBadge(p.pendency_type).label }}</span>
                                            </span>

                                            <span v-if="p.active_claim_amount_cr > 0" class="font-mono font-black text-emerald-800 text-xs">
                                                ₹{{ p.active_claim_amount_cr }} Cr Claimed
                                            </span>
                                        </div>

                                        <!-- Item Title -->
                                        <div class="font-bold text-slate-900 text-xs mt-1">
                                            {{ p.pending_what_title }}
                                        </div>

                                        <!-- Detail Description -->
                                        <div class="text-[11px] text-slate-600 leading-snug mt-0.5 line-clamp-2" :title="p.pending_what_detail">
                                            {{ p.pending_what_detail }}
                                        </div>
                                    </td>

                                    <!-- FROM HOW MANY DAYS -->
                                    <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                        <div v-if="p.pending_with_party === 'SETTLED'">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                                ✅ Completed
                                            </span>
                                            <div class="text-[9px] text-slate-400 mt-0.5">0d pendency</div>
                                        </div>
                                        <div v-else>
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] border shadow-2xs"
                                                :class="getAgingBadge(p.days_pending, false).bg"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full" :class="getAgingBadge(p.days_pending, false).dot"></span>
                                                <span>{{ getAgingBadge(p.days_pending, false).text }}</span>
                                            </span>
                                            <div class="text-[10px] font-mono text-slate-500 mt-0.5">
                                                Since {{ p.date_raised }}
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Action -->
                                    <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                        <button
                                            type="button"
                                            @click="openSchemeDrawer(p)"
                                            class="px-2.5 py-1 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white rounded-lg text-[10px] font-black transition cursor-pointer shadow-2xs"
                                            title="Inspect dossier"
                                        >
                                            Inspect ↗
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="filteredProjects.length === 0">
                                    <td colspan="8" class="py-12 text-center text-slate-500 font-bold text-xs">
                                        No schemes match the selected filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between text-xs text-slate-500 pt-2">
                        <div>
                            Page <strong class="text-slate-900">{{ currentPage }}</strong> of <strong class="text-slate-900">{{ totalPages }}</strong>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                :disabled="currentPage <= 1"
                                @click="currentPage--"
                                class="px-2.5 py-1 border rounded-lg hover:bg-slate-100 disabled:opacity-40 font-bold cursor-pointer"
                            >
                                Previous
                            </button>
                            <button
                                type="button"
                                :disabled="currentPage >= totalPages"
                                @click="currentPage++"
                                class="px-2.5 py-1 border rounded-lg hover:bg-slate-100 disabled:opacity-40 font-bold cursor-pointer"
                            >
                                Next
                            </button>
                        </div>
                    </div>

                </div>

                <!-- ═════════════════════════════════════════════════════════════ -->
                <!-- 🎯 TAB 2: AUTHORITY PENDENCY BREAKDOWN (BB vs MoJS vs State)   -->
                <!-- ═════════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'AGENCY_RADAR'" class="space-y-8">
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                        <div>
                            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-black uppercase tracking-wider bg-purple-100 text-purple-900 border border-purple-200">
                                AGENCY BOTTLENECK RADAR
                            </span>
                            <h3 class="text-base sm:text-lg font-black text-slate-900 mt-1">
                                Exact Breakdown of Files Pending at Brahmaputra Board, MoJS, &amp; State WRD
                            </h3>
                            <p class="text-xs text-slate-500">
                                Grouped by agency to instantly see what fund claims and what project reports are sitting with each authority.
                            </p>
                        </div>

                        <!-- Agency Selector Pill Group -->
                        <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl text-xs font-bold">
                            <button
                                type="button"
                                @click="agencyTab = 'ALL'"
                                :class="agencyTab === 'ALL' ? 'bg-white text-slate-900 shadow-2xs font-black' : 'text-slate-600 hover:text-slate-900'"
                                class="px-2.5 py-1 rounded-lg transition cursor-pointer"
                            >
                                All Agencies
                            </button>
                            <button
                                type="button"
                                @click="agencyTab = 'BB'"
                                :class="agencyTab === 'BB' ? 'bg-purple-700 text-white shadow-2xs font-black' : 'text-purple-900 hover:bg-purple-200'"
                                class="px-2.5 py-1 rounded-lg transition cursor-pointer"
                            >
                                🏢 BB ({{ pmKpis.pending_bb_count }})
                            </button>
                            <button
                                type="button"
                                @click="agencyTab = 'MOJS'"
                                :class="agencyTab === 'MOJS' ? 'bg-blue-700 text-white shadow-2xs font-black' : 'text-blue-900 hover:bg-blue-200'"
                                class="px-2.5 py-1 rounded-lg transition cursor-pointer"
                            >
                                🏛️ MoJS ({{ pmKpis.pending_mojs_count }})
                            </button>
                            <button
                                type="button"
                                @click="agencyTab = 'STATE'"
                                :class="agencyTab === 'STATE' ? 'bg-amber-700 text-white shadow-2xs font-black' : 'text-amber-900 hover:bg-amber-200'"
                                class="px-2.5 py-1 rounded-lg transition cursor-pointer"
                            >
                                📍 State ({{ pmKpis.pending_state_count }})
                            </button>
                        </div>
                    </div>

                    <!-- ────────────────────────────────────────────────────────── -->
                    <!-- SECTION 1: BRAHMAPUTRA BOARD (BB) PENDENCY                 -->
                    <!-- ────────────────────────────────────────────────────────── -->
                    <div v-if="agencyTab === 'ALL' || agencyTab === 'BB'" class="space-y-4 p-5 rounded-2xl bg-purple-50/40 border border-purple-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="p-2 rounded-xl bg-purple-200 text-purple-950 font-black text-lg">🏢</span>
                                <div>
                                    <h4 class="text-sm font-black text-purple-950">
                                        Files Pending at Brahmaputra Board (BB HQ &amp; Field Divisions)
                                    </h4>
                                    <p class="text-xs text-purple-800">
                                        {{ bbFundClaims.length }} Fund Claims (₹{{ pmKpis.pending_bb_amount_cr }} Cr) &bull; {{ bbProjectReports.length }} Project Reports
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 1A: Fund Claims at BB -->
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-indigo-100 text-indigo-900 border border-indigo-200">
                                    💰 FUND CLAIMS UNDER SCRUTINY AT BB ({{ bbFundClaims.length }})
                                </span>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-purple-200 bg-white">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-purple-100/70 text-purple-950 uppercase font-black text-[10px]">
                                        <tr>
                                            <th class="py-2.5 px-3">Scheme Code</th>
                                            <th class="py-2.5 px-3">Project Title &amp; State</th>
                                            <th class="py-2.5 px-3">Claim Requisition</th>
                                            <th class="py-2.5 px-3 text-right">Claim Amount</th>
                                            <th class="py-2.5 px-3 text-center">Days at BB</th>
                                            <th class="py-2.5 px-3">Required BB Action</th>
                                            <th class="py-2.5 px-3 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-purple-100">
                                        <tr v-for="p in bbFundClaims" :key="p.id" class="hover:bg-purple-50/50">
                                            <td class="py-2.5 px-3 font-mono font-black text-[#0F4C9F]">{{ p.scheme_code }}</td>
                                            <td class="py-2.5 px-3 max-w-xs">
                                                <div class="font-bold text-slate-900 truncate" :title="p.scheme_name">{{ p.scheme_name }}</div>
                                                <div class="text-[10px] text-slate-500">{{ p.state }} &bull; {{ p.district }}</div>
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <span class="font-mono font-black text-indigo-900">{{ p.active_ticket_id }}</span>
                                                <div class="text-[10px] text-slate-500">{{ p.claim_instalment }}</div>
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono font-black text-emerald-800">
                                                ₹{{ p.active_claim_amount_cr }} Cr
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="getAgingBadge(p.days_pending, false).bg">
                                                    {{ p.days_pending }} days
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-700">
                                                {{ p.action_required }}
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <button
                                                    type="button"
                                                    @click="openSchemeDrawer(p)"
                                                    class="px-2.5 py-1 bg-[#0F4C9F] text-white rounded-lg text-[10px] font-black cursor-pointer shadow-2xs"
                                                >
                                                    Inspect ↗
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="bbFundClaims.length === 0">
                                            <td colspan="7" class="py-4 text-center text-slate-500 text-xs">
                                                ✅ No fund claims currently pending at Brahmaputra Board.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- 1B: Project Reports at BB -->
                        <div class="space-y-2 pt-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-sky-100 text-sky-900 border border-sky-200">
                                    🏗️ PROJECT PROGRESS REPORTS PENDING INSPECTION AT BB ({{ bbProjectReports.length }})
                                </span>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-purple-200 bg-white">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-purple-100/70 text-purple-950 uppercase font-black text-[10px]">
                                        <tr>
                                            <th class="py-2.5 px-3">Scheme Code</th>
                                            <th class="py-2.5 px-3">Project Title &amp; State</th>
                                            <th class="py-2.5 px-3">Report Number</th>
                                            <th class="py-2.5 px-3 text-center">Physical Progress</th>
                                            <th class="py-2.5 px-3 text-center">Days at BB</th>
                                            <th class="py-2.5 px-3">Required BB Action</th>
                                            <th class="py-2.5 px-3 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-purple-100">
                                        <tr v-for="p in bbProjectReports" :key="p.id" class="hover:bg-purple-50/50">
                                            <td class="py-2.5 px-3 font-mono font-black text-[#0F4C9F]">{{ p.scheme_code }}</td>
                                            <td class="py-2.5 px-3 max-w-xs">
                                                <div class="font-bold text-slate-900 truncate" :title="p.scheme_name">{{ p.scheme_name }}</div>
                                                <div class="text-[10px] text-slate-500">{{ p.state }} &bull; {{ p.district }}</div>
                                            </td>
                                            <td class="py-2.5 px-3 font-mono font-bold text-slate-800">
                                                {{ p.active_ticket_id }}
                                            </td>
                                            <td class="py-2.5 px-3 text-center font-bold">
                                                {{ p.physical_progress_pct }}%
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="getAgingBadge(p.days_pending, false).bg">
                                                    {{ p.days_pending }} days
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-700">
                                                {{ p.action_required }}
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <button
                                                    type="button"
                                                    @click="openSchemeDrawer(p)"
                                                    class="px-2.5 py-1 bg-[#0F4C9F] text-white rounded-lg text-[10px] font-black cursor-pointer shadow-2xs"
                                                >
                                                    Inspect ↗
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="bbProjectReports.length === 0">
                                            <td colspan="7" class="py-4 text-center text-slate-500 text-xs">
                                                ✅ No project progress reports pending at Brahmaputra Board.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- ────────────────────────────────────────────────────────── -->
                    <!-- SECTION 2: MINISTRY OF JAL SHAKTI (MoJS) PENDENCY          -->
                    <!-- ────────────────────────────────────────────────────────── -->
                    <div v-if="agencyTab === 'ALL' || agencyTab === 'MOJS'" class="space-y-4 p-5 rounded-2xl bg-blue-50/40 border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="p-2 rounded-xl bg-blue-200 text-blue-950 font-black text-lg">🏛️</span>
                                <div>
                                    <h4 class="text-sm font-black text-blue-950">
                                        Files Pending at Ministry of Jal Shakti (MoJS Finance &amp; Monitoring)
                                    </h4>
                                    <p class="text-xs text-blue-800">
                                        {{ mojsFundClaims.length }} Fund Sanctions (₹{{ pmKpis.pending_mojs_amount_cr }} Cr) &bull; {{ mojsProjectReviews.length }} Project Reviews
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 2A: Fund Releases at MoJS -->
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-indigo-100 text-indigo-900 border border-indigo-200">
                                    💰 CENTRAL FUND RELEASES PENDING AT MINISTRY ({{ mojsFundClaims.length }})
                                </span>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-blue-200 bg-white">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-blue-100/70 text-blue-950 uppercase font-black text-[10px]">
                                        <tr>
                                            <th class="py-2.5 px-3">Scheme Code</th>
                                            <th class="py-2.5 px-3">Project Title &amp; State</th>
                                            <th class="py-2.5 px-3">Requisition #</th>
                                            <th class="py-2.5 px-3 text-right">Sanction Amount</th>
                                            <th class="py-2.5 px-3 text-center">Days at Ministry</th>
                                            <th class="py-2.5 px-3">Required Ministry Action</th>
                                            <th class="py-2.5 px-3 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-blue-100">
                                        <tr v-for="p in mojsFundClaims" :key="p.id" class="hover:bg-blue-50/50">
                                            <td class="py-2.5 px-3 font-mono font-black text-[#0F4C9F]">{{ p.scheme_code }}</td>
                                            <td class="py-2.5 px-3 max-w-xs">
                                                <div class="font-bold text-slate-900 truncate" :title="p.scheme_name">{{ p.scheme_name }}</div>
                                                <div class="text-[10px] text-slate-500">{{ p.state }} &bull; {{ p.district }}</div>
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <span class="font-mono font-black text-indigo-900">{{ p.active_ticket_id }}</span>
                                                <div class="text-[10px] text-slate-500">{{ p.claim_instalment }}</div>
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono font-black text-emerald-800">
                                                ₹{{ p.active_claim_amount_cr }} Cr
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="getAgingBadge(p.days_pending, false).bg">
                                                    {{ p.days_pending }} days
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-700">
                                                {{ p.action_required }}
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <button
                                                    type="button"
                                                    @click="openSchemeDrawer(p)"
                                                    class="px-2.5 py-1 bg-[#0F4C9F] text-white rounded-lg text-[10px] font-black cursor-pointer shadow-2xs"
                                                >
                                                    Inspect ↗
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="mojsFundClaims.length === 0">
                                            <td colspan="7" class="py-4 text-center text-slate-500 text-xs">
                                                ✅ No fund releases currently pending at Ministry of Jal Shakti.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- ────────────────────────────────────────────────────────── -->
                    <!-- SECTION 3: STATE WRD GOVERNMENT PENDENCY                   -->
                    <!-- ────────────────────────────────────────────────────────── -->
                    <div v-if="agencyTab === 'ALL' || agencyTab === 'STATE'" class="space-y-4 p-5 rounded-2xl bg-amber-50/40 border border-amber-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="p-2 rounded-xl bg-amber-200 text-amber-950 font-black text-lg">📍</span>
                                <div>
                                    <h4 class="text-sm font-black text-amber-950">
                                        Files / Actions Pending with State WRD Governments
                                    </h4>
                                    <p class="text-xs text-amber-800">
                                        {{ stateFundClaims.length }} Fund UCs &amp; Corrections &bull; {{ stateProjectWorks.length }} Ground Works / Progress Reports
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 3A: Fund UCs & Corrections at State -->
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-indigo-100 text-indigo-900 border border-indigo-200">
                                    💰 UTILIZATION CERTIFICATES (UC) &amp; REQUISITION CORRECTIONS DUE FROM STATE ({{ stateFundClaims.length }})
                                </span>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-amber-200 bg-white">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-amber-100/70 text-amber-950 uppercase font-black text-[10px]">
                                        <tr>
                                            <th class="py-2.5 px-3">Scheme Code</th>
                                            <th class="py-2.5 px-3">Project Title &amp; State</th>
                                            <th class="py-2.5 px-3">Pending Requirement</th>
                                            <th class="py-2.5 px-3 text-right">Amount (₹ Cr)</th>
                                            <th class="py-2.5 px-3 text-center">Days Pending</th>
                                            <th class="py-2.5 px-3">Required State Action</th>
                                            <th class="py-2.5 px-3 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-amber-100">
                                        <tr v-for="p in stateFundClaims" :key="p.id" class="hover:bg-amber-50/50">
                                            <td class="py-2.5 px-3 font-mono font-black text-[#0F4C9F]">{{ p.scheme_code }}</td>
                                            <td class="py-2.5 px-3 max-w-xs">
                                                <div class="font-bold text-slate-900 truncate" :title="p.scheme_name">{{ p.scheme_name }}</div>
                                                <div class="text-[10px] text-slate-500">{{ p.state }} &bull; {{ p.district }}</div>
                                            </td>
                                            <td class="py-2.5 px-3 font-bold text-slate-900">
                                                {{ p.pending_what_title }}
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono font-black text-amber-900">
                                                ₹{{ p.active_claim_amount_cr }} Cr
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="getAgingBadge(p.days_pending, false).bg">
                                                    {{ p.days_pending }} days
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-700">
                                                {{ p.action_required }}
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <button
                                                    type="button"
                                                    @click="openSchemeDrawer(p)"
                                                    class="px-2.5 py-1 bg-[#0F4C9F] text-white rounded-lg text-[10px] font-black cursor-pointer shadow-2xs"
                                                >
                                                    Inspect ↗
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="stateFundClaims.length === 0">
                                            <td colspan="7" class="py-4 text-center text-slate-500 text-xs">
                                                ✅ No fund actions or UCs currently pending at State WRD.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- 3B: Ground Works & Reports Due from State -->
                        <div class="space-y-2 pt-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-sky-100 text-sky-900 border border-sky-200">
                                    🏗️ GROUND EXECUTION, TENDERS &amp; REPORTS DUE FROM STATE ({{ stateProjectWorks.length }})
                                </span>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-amber-200 bg-white">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-amber-100/70 text-amber-950 uppercase font-black text-[10px]">
                                        <tr>
                                            <th class="py-2.5 px-3">Scheme Code</th>
                                            <th class="py-2.5 px-3">Project Title &amp; State</th>
                                            <th class="py-2.5 px-3">Current Physical Status</th>
                                            <th class="py-2.5 px-3 text-center">Days Since Action</th>
                                            <th class="py-2.5 px-3">Required State Action</th>
                                            <th class="py-2.5 px-3 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-amber-100">
                                        <tr v-for="p in stateProjectWorks" :key="p.id" class="hover:bg-amber-50/50">
                                            <td class="py-2.5 px-3 font-mono font-black text-[#0F4C9F]">{{ p.scheme_code }}</td>
                                            <td class="py-2.5 px-3 max-w-xs">
                                                <div class="font-bold text-slate-900 truncate" :title="p.scheme_name">{{ p.scheme_name }}</div>
                                                <div class="text-[10px] text-slate-500">{{ p.state }} &bull; {{ p.district }}</div>
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border" :class="statusBadgeClass(p.physical_status, p.physical_progress_pct)">
                                                    {{ formatStatus(p.physical_status, p.physical_progress_pct) }}
                                                </span>
                                                <div class="text-[10px] text-slate-500 mt-0.5">{{ p.pending_what_title }}</div>
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="getAgingBadge(p.days_pending, false).bg">
                                                    {{ p.days_pending }} days
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-700">
                                                {{ p.action_required }}
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <button
                                                    type="button"
                                                    @click="openSchemeDrawer(p)"
                                                    class="px-2.5 py-1 bg-[#0F4C9F] text-white rounded-lg text-[10px] font-black cursor-pointer shadow-2xs"
                                                >
                                                    Inspect ↗
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="stateProjectWorks.length === 0">
                                            <td colspan="6" class="py-4 text-center text-slate-500 text-xs">
                                                ✅ No ground actions or progress reports pending at State WRD.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- ═══════════════════════════════════════════════════════════════════════ -->
        <!-- 360° PROJECT SLIDE-OVER INSPECTION DRAWER                              -->
        <!-- ═══════════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div
                v-if="isDrawerOpen && selectedScheme"
                class="fixed inset-0 z-50 overflow-hidden"
                aria-labelledby="slide-over-title"
                role="dialog"
                aria-modal="true"
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                    @click="closeSchemeDrawer"
                ></div>

                <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
                    <div class="w-screen max-w-xl bg-white shadow-2xl flex flex-col">
                        
                        <!-- Drawer Header -->
                        <div class="p-6 bg-gradient-to-r from-[#0A2540] to-[#0F4C9F] text-white">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <span class="font-mono text-xs font-black uppercase tracking-wider px-2 py-0.5 rounded bg-white/20 text-white">
                                        {{ selectedScheme.scheme_code }}
                                    </span>
                                    <h2 class="text-lg font-black mt-2 leading-snug">
                                        {{ selectedScheme.scheme_name }}
                                    </h2>
                                    <p class="text-xs text-blue-200 mt-1">
                                        {{ selectedScheme.state }} &bull; {{ selectedScheme.district }} &bull; {{ selectedScheme.river_basin }} Basin
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="closeSchemeDrawer"
                                    class="text-white/70 hover:text-white p-1 rounded-lg hover:bg-white/10 transition cursor-pointer text-xl font-bold"
                                >
                                    &times;
                                </button>
                            </div>
                        </div>

                        <!-- Drawer Body Content -->
                        <div class="flex-1 overflow-y-auto p-6 space-y-6 text-xs">
                            
                            <!-- Pendency Diagnosis Box -->
                            <div class="p-4 rounded-xl border space-y-3" :class="selectedScheme.is_overdue ? 'bg-rose-50 border-rose-300' : 'bg-slate-50 border-slate-200'">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Live Pendency Diagnosis</span>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-black border"
                                        :class="getAgingBadge(selectedScheme.days_pending, selectedScheme.is_completed).bg"
                                    >
                                        {{ getAgingBadge(selectedScheme.days_pending, selectedScheme.is_completed).text }}
                                    </span>
                                </div>

                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black border" :class="getPendencyTypeBadge(selectedScheme.pendency_type).bg">
                                            {{ getPendencyTypeBadge(selectedScheme.pendency_type).icon }} {{ getPendencyTypeBadge(selectedScheme.pendency_type).label }}
                                        </span>
                                        <span class="font-bold text-slate-900 text-sm">
                                            {{ selectedScheme.pending_what_title }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-700 leading-relaxed pt-1">
                                        {{ selectedScheme.pending_what_detail }}
                                    </p>
                                </div>

                                <div class="pt-2 border-t border-slate-200 grid grid-cols-2 gap-2 text-[11px]">
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Held At:</span>
                                        <strong class="text-slate-900">{{ selectedScheme.pending_with }}</strong>
                                        <div class="text-[10px] text-slate-500">{{ selectedScheme.pending_desk }}</div>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Days Pending:</span>
                                        <strong class="text-slate-900">{{ selectedScheme.days_pending }} Days</strong>
                                        <div class="text-[10px] text-slate-500">Since {{ selectedScheme.date_raised }}</div>
                                    </div>
                                </div>

                                <div class="p-2.5 rounded-lg bg-white border border-slate-200">
                                    <span class="text-[10px] font-black uppercase tracking-wider text-[#0F4C9F] block mb-0.5">Required Next Action:</span>
                                    <p class="text-xs font-bold text-slate-900">
                                        {{ selectedScheme.action_required }}
                                    </p>
                                </div>
                            </div>

                            <!-- Financial Health Snapshot -->
                            <div class="space-y-2">
                                <h4 class="font-black text-xs uppercase tracking-wider text-slate-500">Financial Snapshot</h4>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                                        <span class="text-[10px] text-slate-500 block">Sanctioned Outlay</span>
                                        <strong class="text-sm font-mono text-slate-900">₹{{ selectedScheme.sanctioned_amount_cr }} Cr</strong>
                                    </div>
                                    <div class="p-3 bg-blue-50/70 rounded-xl border border-blue-200">
                                        <span class="text-[10px] text-blue-700 block">Central Entitlement</span>
                                        <strong class="text-sm font-mono text-blue-900">₹{{ selectedScheme.central_share_cr }} Cr</strong>
                                    </div>
                                    <div class="p-3 bg-teal-50/70 rounded-xl border border-teal-200">
                                        <span class="text-[10px] text-teal-700 block">Released So Far</span>
                                        <strong class="text-sm font-mono text-teal-900">₹{{ selectedScheme.released_central_cr }} Cr</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Physical & Financial Progress Gauges -->
                            <div class="space-y-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-black text-xs uppercase tracking-wider text-slate-700">Physical &amp; Financial Execution</h4>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border" :class="statusBadgeClass(selectedScheme.physical_status, selectedScheme.physical_progress_pct)">
                                        {{ selectedScheme.physical_status }}
                                    </span>
                                </div>

                                <!-- 🏗️ Physical Progress -->
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="font-bold text-slate-700 flex items-center gap-1">
                                            <span class="text-blue-600">🏗️</span> Physical Progress (Civil Works)
                                        </span>
                                        <strong class="font-mono text-sm text-blue-900">{{ selectedScheme.physical_progress_pct }}%</strong>
                                    </div>
                                    <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                        <div
                                            class="h-2 rounded-full transition-all"
                                            :class="selectedScheme.physical_progress_pct >= 100 ? 'bg-emerald-600' : 'bg-blue-600'"
                                            :style="{ width: `${Math.min(100, selectedScheme.physical_progress_pct)}%` }"
                                        ></div>
                                    </div>
                                </div>

                                <!-- 💰 Financial Progress -->
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="font-bold text-slate-700 flex items-center gap-1">
                                            <span class="text-emerald-600">💰</span> Financial Progress (Funds Released)
                                        </span>
                                        <strong class="font-mono text-sm text-emerald-900">{{ selectedScheme.financial_progress_pct || selectedScheme.disbursal_velocity_pct }}%</strong>
                                    </div>
                                    <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                        <div
                                            class="h-2 rounded-full bg-emerald-600 transition-all"
                                            :style="{ width: `${Math.min(100, selectedScheme.financial_progress_pct || selectedScheme.disbursal_velocity_pct)}%` }"
                                        ></div>
                                    </div>
                                    <div class="flex justify-between text-[10px] text-slate-500 font-mono mt-1">
                                        <span>Released: ₹{{ selectedScheme.released_central_cr }} Cr</span>
                                        <span>Balance: ₹{{ selectedScheme.balance_central_cr }} Cr</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Direct Operational Links -->
                            <div class="pt-4 border-t border-slate-200 space-y-2">
                                <h4 class="font-black text-xs uppercase tracking-wider text-slate-500">Operational Links</h4>
                                <div class="flex flex-wrap gap-2">
                                    <Link
                                        :href="route('schemes.show', selectedScheme.id)"
                                        class="px-3 py-2 bg-[#0F4C9F] text-white rounded-xl text-xs font-bold hover:bg-[#0c3c7d] transition"
                                    >
                                        Open Full Scheme Details ↗
                                    </Link>
                                    <Link
                                        v-if="selectedScheme.active_claim_id"
                                        :href="route('fund-release.show', selectedScheme.active_claim_id)"
                                        class="px-3 py-2 bg-indigo-700 text-white rounded-xl text-xs font-bold hover:bg-indigo-800 transition"
                                    >
                                        Open Active Claim ({{ selectedScheme.active_ticket_id }}) ↗
                                    </Link>
                                </div>
                            </div>

                        </div>

                        <!-- Drawer Footer -->
                        <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                            <button
                                type="button"
                                @click="closeSchemeDrawer"
                                class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl text-xs font-bold transition cursor-pointer"
                            >
                                Close Dossier
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>
