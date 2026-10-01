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
        default: () => ({ state: 'ALL', basin: 'ALL', fy: 'ALL' }),
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

const activeTab = ref('STATE_SCORECARD'); // 'STATE_SCORECARD' | 'BASIN_RADAR' | 'BOTTLENECK_RADAR' | 'PARLIAMENTARY_REG'

const selectedState = ref(props.filters.state || 'ALL');
const selectedBasin = ref(props.filters.basin || 'ALL');
const searchQuery = ref('');

const applyFilters = () => {
    router.get(route('analytics.index'), {
        state: selectedState.value,
        basin: selectedBasin.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    selectedState.value = 'ALL';
    selectedBasin.value = 'ALL';
    applyFilters();
};

const excelExportUrl = computed(() => {
    const params = new URLSearchParams();
    if (selectedState.value !== 'ALL') params.append('state', selectedState.value);
    if (selectedBasin.value !== 'ALL') params.append('basin', selectedBasin.value);
    return `${route('analytics.export-excel')}?${params.toString()}`;
});

const pdfExportUrl = computed(() => {
    const params = new URLSearchParams();
    if (selectedState.value !== 'ALL') params.append('state', selectedState.value);
    if (selectedBasin.value !== 'ALL') params.append('basin', selectedBasin.value);
    return `${route('analytics.export-pdf')}?${params.toString()}`;
});

const filteredParliamentaryList = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.analytics.parliamentary_list || [];
    return (props.analytics.parliamentary_list || []).filter(s =>
        s.scheme_code?.toLowerCase().includes(q) ||
        s.scheme_name?.toLowerCase().includes(q) ||
        s.state?.toLowerCase().includes(q) ||
        s.district?.toLowerCase().includes(q)
    );
});
</script>

<template>
    <Head title="Executive MIS & Analytics Cockpit - FMBAP" />

    <AuthenticatedLayout>
        <!-- Header Strip -->
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <span class="p-1.5 bg-blue-100 text-[#0F4C9F] rounded-lg text-lg">📊</span>
                        <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                            Executive MIS Analytics &amp; Reporting Cockpit
                        </h1>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-900 border border-purple-200 hidden sm:inline-block">
                            MoJS / Brahmaputra Board HQ
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        High-level financial velocity, state performance benchmark ranking, and parliamentary oversight reports.
                    </p>
                </div>

                <!-- Export Actions -->
                <div class="flex items-center gap-2">
                    <a
                        :href="excelExportUrl"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-lg shadow-xs transition cursor-pointer"
                    >
                        <span>📥 Export Parliamentary Excel</span>
                    </a>

                    <a
                        :href="pdfExportUrl"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white text-xs font-bold rounded-lg shadow-xs transition cursor-pointer"
                    >
                        <span>📄 Export Executive Briefing Note</span>
                    </a>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <!-- ─── 1. TOP MACRO KPI TILES ─── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Outlay Card -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Total Statutory Outlay</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-[#0F4C9F]">90:10 Ratio</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                        ₹{{ analytics.macro.total_sanctioned_cr }} <span class="text-sm font-semibold text-slate-500">Cr</span>
                    </div>
                    <div class="text-[11px] text-slate-500 pt-1 border-t border-slate-100 flex justify-between font-medium">
                        <span>Central 90%: ₹{{ analytics.macro.total_central_share_cr }} Cr</span>
                        <span>State 10%: ₹{{ analytics.macro.total_state_share_cr }} Cr</span>
                    </div>
                </div>

                <!-- Disbursal Card -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">Central Assistance Released</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                            {{ analytics.macro.disbursal_velocity_pct }}% Drawn
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-950 leading-tight">
                        ₹{{ analytics.macro.total_released_cr }} <span class="text-sm font-semibold text-emerald-700">Cr</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-emerald-500 h-full rounded-full" :style="{ width: `${analytics.macro.disbursal_velocity_pct}%` }"></div>
                    </div>
                </div>

                <!-- Schemes Execution Portfolio -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-800">Schemes Execution Status</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900">
                            {{ analytics.macro.ongoing_schemes }} Active
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                        {{ analytics.macro.completed_schemes }} <span class="text-base font-bold text-slate-500">/ {{ analytics.macro.total_schemes }} Completed</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden flex">
                        <div class="bg-emerald-500 h-full" :style="{ width: `${(analytics.macro.completed_schemes / (analytics.macro.total_schemes || 1)) * 100}%` }"></div>
                        <div class="bg-amber-400 h-full" :style="{ width: `${(analytics.macro.ongoing_schemes / (analytics.macro.total_schemes || 1)) * 100}%` }"></div>
                    </div>
                </div>

                <!-- Average Physical Progress -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-blue-800">Physical Progress Avg.</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-900">Verified Inspection</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-blue-950 leading-tight">
                        {{ analytics.macro.avg_physical_progress }}%
                    </div>
                    <div class="w-full bg-blue-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-[#0F4C9F] h-full rounded-full transition-all duration-500" :style="{ width: `${analytics.macro.avg_physical_progress}%` }"></div>
                    </div>
                </div>
            </div>

            <!-- ─── 2. INTERACTIVE FILTER BAR ─── -->
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5">
                        <span class="font-bold text-slate-600">State:</span>
                        <select
                            v-model="selectedState"
                            @change="applyFilters"
                            class="rounded-lg border-slate-300 text-xs py-1.5 pr-8 font-semibold text-slate-800 focus:ring-[#0F4C9F]"
                        >
                            <option value="ALL">All Participating States</option>
                            <option v-for="st in availableStates" :key="st" :value="st">{{ st }}</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span class="font-bold text-slate-600">River Basin:</span>
                        <select
                            v-model="selectedBasin"
                            @change="applyFilters"
                            class="rounded-lg border-slate-300 text-xs py-1.5 pr-8 font-semibold text-slate-800 focus:ring-[#0F4C9F]"
                        >
                            <option value="ALL">All River Basins</option>
                            <option v-for="b in availableBasins" :key="b" :value="b">{{ b }}</option>
                        </select>
                    </div>

                    <button
                        type="button"
                        v-if="selectedState !== 'ALL' || selectedBasin !== 'ALL'"
                        @click="resetFilters"
                        class="text-blue-700 hover:underline font-bold text-xs cursor-pointer"
                    >
                        Reset Filters
                    </button>
                </div>

                <div class="text-slate-500 font-semibold text-[11px]">
                    Showing data for <span class="font-bold text-slate-800">{{ analytics.macro.total_schemes }}</span> schemes
                </div>
            </div>

            <!-- ─── 3. TAB NAVIGATION ─── -->
            <div class="flex border-b border-slate-200 bg-white rounded-t-xl px-4 pt-2 gap-2 text-xs font-bold shadow-2xs">
                <button
                    type="button"
                    @click="activeTab = 'STATE_SCORECARD'"
                    :class="activeTab === 'STATE_SCORECARD' ? 'border-b-2 border-[#0F4C9F] text-[#0F4C9F]' : 'text-slate-500 hover:text-slate-800'"
                    class="py-3 px-3 transition cursor-pointer flex items-center gap-1.5"
                >
                    <span>🏆 State Performance Scorecard</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'BASIN_RADAR'"
                    :class="activeTab === 'BASIN_RADAR' ? 'border-b-2 border-[#0F4C9F] text-[#0F4C9F]' : 'text-slate-500 hover:text-slate-800'"
                    class="py-3 px-3 transition cursor-pointer flex items-center gap-1.5"
                >
                    <span>🌊 River Basin Matrix</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'BOTTLENECK_RADAR'"
                    :class="activeTab === 'BOTTLENECK_RADAR' ? 'border-b-2 border-[#0F4C9F] text-[#0F4C9F]' : 'text-slate-500 hover:text-slate-800'"
                    class="py-3 px-3 transition cursor-pointer flex items-center gap-1.5"
                >
                    <span>⏱️ Turnaround Time (TAT) &amp; Bottlenecks</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'PARLIAMENTARY_REG'"
                    :class="activeTab === 'PARLIAMENTARY_REG' ? 'border-b-2 border-[#0F4C9F] text-[#0F4C9F]' : 'text-slate-500 hover:text-slate-800'"
                    class="py-3 px-3 transition cursor-pointer flex items-center gap-1.5"
                >
                    <span>🏛️ Parliamentary Master Register</span>
                </button>
            </div>

            <!-- ─── 4. TAB CONTENTS ─── -->
            <div class="bg-white rounded-b-xl border border-slate-200 border-t-0 p-5 shadow-2xs">

                <!-- TAB 1: STATE SCORECARD -->
                <div v-if="activeTab === 'STATE_SCORECARD'" class="space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Inter-State Flood Management Benchmark Ranking</h3>
                            <p class="text-xs text-slate-500">Sorted by highest verified physical completion percentage.</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-[10px] tracking-wider border-y border-slate-200">
                                <tr>
                                    <th class="py-3 px-4">Rank &amp; State</th>
                                    <th class="py-3 px-3 text-center">Schemes</th>
                                    <th class="py-3 px-3 text-right">Sanctioned Outlay</th>
                                    <th class="py-3 px-3 text-right">Central Released</th>
                                    <th class="py-3 px-3 text-center">Disbursal %</th>
                                    <th class="py-3 px-3 text-center">Avg Physical Progress</th>
                                    <th class="py-3 px-3 text-center">Active In-flight Claims</th>
                                    <th class="py-3 px-4 text-center">Performance Tier</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(st, idx) in analytics.state_scorecard" :key="st.state_name" class="hover:bg-slate-50/80 transition">
                                    <td class="py-3 px-4 font-bold text-slate-900 flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black"
                                            :class="idx === 0 ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-slate-100 text-slate-700'">
                                            {{ idx + 1 }}
                                        </span>
                                        <span>{{ st.state_name }}</span>
                                    </td>
                                    <td class="py-3 px-3 text-center font-semibold text-slate-700">
                                        {{ st.schemes_count }} <span class="text-[10px] text-slate-400">({{ st.completed_count }} done)</span>
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">
                                        ₹{{ st.sanctioned_cr }} Cr
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-bold text-emerald-700">
                                        ₹{{ st.released_cr }} Cr
                                    </td>
                                    <td class="py-3 px-3 text-center font-bold text-slate-800">
                                        {{ st.disbursal_pct }}%
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <div class="w-16 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                <div class="bg-[#0F4C9F] h-full rounded-full" :style="{ width: `${st.avg_physical_pct}%` }"></div>
                                            </div>
                                            <span class="font-bold text-blue-900">{{ st.avg_physical_pct }}%</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span v-if="st.pending_claims_count > 0" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900">
                                            {{ st.pending_claims_count }} in review
                                        </span>
                                        <span v-else class="text-[10px] text-slate-400">None</span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span
                                            :class="{
                                                'bg-emerald-100 text-emerald-900 border-emerald-200': st.tier.includes('Leader'),
                                                'bg-blue-100 text-blue-900 border-blue-200': st.tier.includes('On Track'),
                                                'bg-rose-100 text-rose-900 border-rose-200': st.tier.includes('Needs Acceleration'),
                                            }"
                                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-block"
                                        >
                                            {{ st.tier }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: RIVER BASIN RADAR -->
                <div v-if="activeTab === 'BASIN_RADAR'" class="space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-bold text-slate-900">River Basin Allocation &amp; Protection Metrics</h3>
                        <p class="text-xs text-slate-500">Distribution of anti-erosion and flood mitigation schemes across river catchments.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="basin in analytics.basin_matrix" :key="basin.basin_name" class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-sm text-slate-900 flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#0F4C9F]"></span>
                                    {{ basin.basin_name }} Basin
                                </h4>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-[#0F4C9F]">
                                    {{ basin.schemes_count }} Schemes
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs pt-1">
                                <div>
                                    <span class="text-slate-500 text-[10px] block">Sanctioned Outlay</span>
                                    <span class="font-black text-slate-900 text-sm">₹{{ basin.sanctioned_cr }} Cr</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 text-[10px] block">Average Physical Progress</span>
                                    <span class="font-black text-blue-900 text-sm">{{ basin.avg_physical_pct }}%</span>
                                </div>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden mt-2">
                                <div class="bg-[#0F4C9F] h-full rounded-full" :style="{ width: `${basin.avg_physical_pct}%` }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: BOTTLENECK RADAR -->
                <div v-if="activeTab === 'BOTTLENECK_RADAR'" class="space-y-6">
                    <div class="border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-bold text-slate-900">Workflow Pipeline &amp; Bottleneck Diagnostics</h3>
                        <p class="text-xs text-slate-500">Tracking turnaround time (TAT) and claims requiring immediate departmental intervention.</p>
                    </div>

                    <!-- Pipeline Stages -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="p-4 rounded-xl border border-blue-200 bg-blue-50/40">
                            <div class="text-[10px] font-bold uppercase text-blue-700">Stage 1: Pending BB Inspection</div>
                            <div class="text-2xl font-black text-blue-950 mt-1">{{ analytics.bottlenecks.submitted_to_bb }}</div>
                            <div class="text-[11px] text-blue-600 mt-1">Awaiting field inspector report</div>
                        </div>

                        <div class="p-4 rounded-xl border border-purple-200 bg-purple-50/40">
                            <div class="text-[10px] font-bold uppercase text-purple-700">Stage 2: Pending MoJS Approval</div>
                            <div class="text-2xl font-black text-purple-950 mt-1">{{ analytics.bottlenecks.forwarded_to_mojs }}</div>
                            <div class="text-[11px] text-purple-600 mt-1">Under ministry sanction review</div>
                        </div>

                        <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/40">
                            <div class="text-[10px] font-bold uppercase text-amber-700">Stage 3: Needs Correction</div>
                            <div class="text-2xl font-black text-amber-950 mt-1">{{ analytics.bottlenecks.needs_correction }}</div>
                            <div class="text-[11px] text-amber-700 mt-1">Returned to State WRD</div>
                        </div>

                        <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/40">
                            <div class="text-[10px] font-bold uppercase text-emerald-700">Stage 4: Approved &amp; Released</div>
                            <div class="text-2xl font-black text-emerald-950 mt-1">{{ analytics.bottlenecks.approved }}</div>
                            <div class="text-[11px] text-emerald-700 mt-1">Disbursed successfully</div>
                        </div>
                    </div>

                    <!-- TAT Metric Banner -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">⚡</span>
                            <div>
                                <div class="font-bold text-slate-900">Average National Turnaround Time (TAT)</div>
                                <div class="text-slate-500 text-[11px]">Calculated from State initial submission to final Ministry release order.</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-black text-[#0F4C9F]">~{{ analytics.bottlenecks.avg_turnaround_days }} Days</span>
                            <span class="text-[10px] text-slate-400 block font-medium">Standard SLA Target: 30 Days</span>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: PARLIAMENTARY REGISTER -->
                <div v-if="activeTab === 'PARLIAMENTARY_REG'" class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Parliamentary Question (PQ) Ready Master Register</h3>
                            <p class="text-xs text-slate-500">Live consolidated dataset formatted for Lok Sabha / Rajya Sabha questions.</p>
                        </div>

                        <div class="w-full sm:w-64">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search scheme code, state, title..."
                                class="w-full rounded-lg border-slate-300 text-xs py-1.5 focus:ring-[#0F4C9F]"
                            />
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-[10px] tracking-wider border-y border-slate-200">
                                <tr>
                                    <th class="py-3 px-3">Scheme Code</th>
                                    <th class="py-3 px-3">Scheme Name</th>
                                    <th class="py-3 px-3">State / District</th>
                                    <th class="py-3 px-3">River Basin</th>
                                    <th class="py-3 px-3 text-right">Sanctioned (₹ Cr)</th>
                                    <th class="py-3 px-3 text-center">Physical %</th>
                                    <th class="py-3 px-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="s in filteredParliamentaryList" :key="s.id" class="hover:bg-slate-50/80 transition">
                                    <td class="py-3 px-3 font-mono font-bold text-[#0F4C9F]">{{ s.scheme_code }}</td>
                                    <td class="py-3 px-3 font-semibold text-slate-800 max-w-xs truncate">{{ s.scheme_name }}</td>
                                    <td class="py-3 px-3 text-slate-700">{{ s.state }} ({{ s.district }})</td>
                                    <td class="py-3 px-3 text-slate-700">{{ s.river_basin }}</td>
                                    <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">₹{{ s.sanctioned_amount_cr }} Cr</td>
                                    <td class="py-3 px-3 text-center font-bold text-blue-900">{{ s.physical_progress_pct }}%</td>
                                    <td class="py-3 px-3 text-center">
                                        <span :class="s.physical_status === 'Completed' ? 'bg-emerald-100 text-emerald-900' : 'bg-amber-100 text-amber-900'"
                                            class="px-2 py-0.5 rounded text-[10px] font-bold">
                                            {{ s.physical_status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
