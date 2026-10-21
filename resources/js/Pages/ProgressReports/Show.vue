<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    report: {
        type: Object,
        required: true,
    },
    timeline: {
        type: Array,
        default: () => [],
    },
    userRole: {
        type: String,
        default: '',
    },
});

const isBB = computed(() => props.userRole === 'board_official' || props.userRole === 'super_admin');
const isMoJS = computed(() => props.userRole === 'mojs_official' || props.userRole === 'super_admin');
const isState = computed(() => props.userRole === 'state_official' || props.userRole === 'super_admin');

const showDecisionModal = ref(false);
const decisionType = ref('REVIEWED_BY_BB');
const activePhoto = ref(null);

const decisionForm = useForm({
    decision: 'REVIEWED_BY_BB',
    remarks: '',
    inspection_doc: null,
});

const openDecisionModal = (type) => {
    decisionType.value = type;
    decisionForm.decision = type;
    decisionForm.remarks = '';
    decisionForm.inspection_doc = null;
    showDecisionModal.value = true;
};

const submitDecision = () => {
    decisionForm.post(route('progress-reports.bbReview', props.report.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showDecisionModal.value = false;
        },
    });
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'APPROVED':
        case 'REVIEWED_BY_MOJS':
            return { label: 'Verified & Approved', class: 'bg-emerald-50 text-emerald-800 border-emerald-300', dot: 'bg-emerald-500' };
        case 'REVIEWED_BY_BB':
            return { label: 'BB Inspected & Submitted to MoJS', class: 'bg-emerald-50 text-emerald-800 border-emerald-300', dot: 'bg-emerald-500' };
        case 'SUBMITTED':
            return { label: 'Submitted (Pending BB Inspection)', class: 'bg-blue-50 text-blue-800 border-blue-300', dot: 'bg-blue-500' };
        case 'NEEDS_CORRECTION':
            return { label: 'Needs Correction', class: 'bg-amber-50 text-amber-900 border-amber-300', dot: 'bg-amber-500' };
        default:
            return { label: status || 'Submitted', class: 'bg-gray-50 text-gray-800 border-gray-300', dot: 'bg-gray-400' };
    }
};

const badge = computed(() => getStatusBadge(props.report?.status || ''));

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        return isNaN(d.getTime()) ? dateStr : d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch {
        return String(dateStr);
    }
};

const parsedBreakup = computed(() => {
    const raw = props.report?.physical_breakup;
    if (!raw) return [];
    if (Array.isArray(raw)) return raw;
    try {
        return typeof raw === 'string' ? JSON.parse(raw) : [];
    } catch {
        return [];
    }
});

const parsedMedia = computed(() => {
    const raw = props.report?.geo_tagged_files;
    if (!raw) return [];
    if (Array.isArray(raw)) return raw;
    try {
        return typeof raw === 'string' ? JSON.parse(raw) : [];
    } catch {
        return [];
    }
});
</script>

<template>
    <Head :title="`Progress Report #${report.id} — ${report.scheme?.scheme_code || ''}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('progress-reports.index')"
                        class="w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 transition shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="font-black text-lg text-gray-900 leading-tight">
                                Progress Report #{{ report.id }}
                            </h2>
                            <span class="text-xs font-mono font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                                {{ report.scheme?.scheme_code }}
                            </span>
                            <span :class="badge.class" class="px-2.5 py-0.5 rounded-full text-xs font-black border flex items-center gap-1.5 shadow-2xs">
                                <span :class="badge.dot" class="w-1.5 h-1.5 rounded-full"></span>
                                <span>{{ badge.label }}</span>
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Reporting Period: <strong class="text-gray-800">{{ report.reporting_period }}</strong> &bull; Submitted on {{ formatDate(report.created_at) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        v-if="report.progress_report_doc_path"
                        :href="report.progress_report_doc_path"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-gray-300 text-xs font-bold text-gray-700 hover:bg-gray-50 shadow-2xs transition"
                    >
                        <svg class="w-4 h-4 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                        Official PDF
                    </a>

                    <Link
                        v-if="report.status === 'NEEDS_CORRECTION' && (isState || isBB)"
                        :href="route('progress-reports.edit', report.id)"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-black shadow-sm transition"
                    >
                        ✏️ Amend Report
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-6 max-w-7xl mx-auto">

            <!-- Correction Alert if Required -->
            <div v-if="report.status === 'NEEDS_CORRECTION'" class="p-5 rounded-2xl bg-amber-50 border border-amber-300 text-amber-950 space-y-2 shadow-xs">
                <div class="flex items-center gap-2 font-black text-xs uppercase tracking-wide text-amber-900">
                    <span>⚠️</span>
                    <span>Correction Required Before Approval</span>
                </div>
                <div v-if="report.bb_remarks" class="text-xs">
                    <strong>Brahmaputra Board Remarks:</strong> {{ report.bb_remarks }}
                </div>
                <div v-if="report.mojs_remarks" class="text-xs">
                    <strong>MoJS Remarks:</strong> {{ report.mojs_remarks }}
                </div>
            </div>

            <!-- GRID: 2/3 Content & 1/3 Sidebar -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- MAIN COLUMN -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Scheme Context Banner -->
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-wider text-[#0F4C9F]">Scheme Overview</span>
                                <h3 class="text-base font-black text-gray-900 mt-0.5">
                                    {{ report.scheme?.scheme_name }}
                                </h3>
                                <div class="flex items-center gap-3 text-xs text-gray-500 mt-1 flex-wrap">
                                    <span>State: <strong class="text-gray-800">{{ report.scheme?.state }}</strong></span>
                                    <span>&bull;</span>
                                    <span>Sanctioned Cost: <strong class="text-gray-800">₹{{ report.scheme?.sanctioned_amount_cr }} Cr</strong></span>
                                    <span>&bull;</span>
                                    <span>Division: <strong class="text-gray-800">{{ report.scheme?.division || 'HQ' }}</strong></span>
                                </div>
                            </div>

                            <Link
                                :href="route('schemes.index')"
                                class="text-xs font-bold text-[#0F4C9F] hover:underline shrink-0"
                            >
                                Schemes Catalogue &rarr;
                            </Link>
                        </div>
                    </div>

                    <!-- KPI Cards: Physical vs Financial -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Physical Progress -->
                        <div class="bg-white rounded-2xl border border-blue-200 shadow-xs p-5 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black uppercase text-blue-900 flex items-center gap-1.5">
                                    <span>🏗️</span>
                                    <span>Physical Progress</span>
                                </span>
                                <span class="text-xs font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200">
                                    Ground Execution
                                </span>
                            </div>

                            <div class="flex items-baseline gap-2">
                                <span class="text-4xl font-black text-[#0F4C9F]">{{ report.physical_progress_pct }}%</span>
                                <span class="text-xs font-bold text-gray-500">completed</span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-blue-100 rounded-full h-3 overflow-hidden">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-blue-500 to-[#0F4C9F] transition-all duration-500"
                                    :style="{ width: `${Math.min(100, report.physical_progress_pct || 0)}%` }"
                                ></div>
                            </div>

                            <p class="text-xs text-gray-600 leading-relaxed pt-1">
                                {{ report.physical_progress_description || 'No detailed ground work description entered.' }}
                            </p>
                        </div>

                        <!-- Financial Progress -->
                        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-5 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black uppercase text-emerald-900 flex items-center gap-1.5">
                                    <span>💰</span>
                                    <span>Financial Progress</span>
                                </span>
                                <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    Audit &amp; Expenditure
                                </span>
                            </div>

                            <div class="flex items-baseline gap-2">
                                <span class="text-4xl font-black text-emerald-700">{{ report.financial_progress_pct }}%</span>
                                <span class="text-xs font-bold text-gray-500">utilized</span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-emerald-100 rounded-full h-3 overflow-hidden">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-600 transition-all duration-500"
                                    :style="{ width: `${Math.min(100, report.financial_progress_pct || 0)}%` }"
                                ></div>
                            </div>

                            <p class="text-xs text-gray-600 leading-relaxed pt-1">
                                {{ report.financial_progress_description || 'No detailed financial expenditure description entered.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Component Break-Up Table -->
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <div>
                                <h3 class="font-extrabold text-sm text-gray-900">
                                    Physical Progress Break-Up (Component-wise)
                                </h3>
                                <p class="text-[11px] text-gray-400">
                                    Detailed engineering quantities completed vs sanctioned scopes.
                                </p>
                            </div>
                            <span class="text-xs font-mono font-bold text-gray-500">
                                {{ parsedBreakup.length }} Components
                            </span>
                        </div>

                        <div v-if="!parsedBreakup.length" class="text-xs text-gray-500 italic py-4 text-center">
                            No granular component break-up recorded for this report.
                        </div>

                        <div v-else class="overflow-x-auto border border-gray-200 rounded-xl">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-gray-50 text-gray-600 font-bold uppercase text-[10px] tracking-wider border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-2.5">#</th>
                                        <th class="px-4 py-2.5">Component</th>
                                        <th class="px-4 py-2.5 text-right">Scope</th>
                                        <th class="px-4 py-2.5 text-center">Unit</th>
                                        <th class="px-4 py-2.5 text-right">Completed</th>
                                        <th class="px-4 py-2.5">Progress (%)</th>
                                        <th class="px-4 py-2.5">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="(item, idx) in parsedBreakup" :key="idx" class="hover:bg-slate-50/50">
                                        <td class="px-4 py-2.5 font-bold text-gray-400">{{ idx + 1 }}</td>
                                        <td class="px-4 py-2.5 font-bold text-gray-900">{{ item.name }}</td>
                                        <td class="px-4 py-2.5 text-right font-mono text-gray-700">{{ item.scope }}</td>
                                        <td class="px-4 py-2.5 text-center text-gray-500">{{ item.unit }}</td>
                                        <td class="px-4 py-2.5 text-right font-mono font-bold text-slate-900">{{ item.completed }}</td>
                                        <td class="px-4 py-2.5">
                                            <div class="flex items-center gap-2">
                                                <div class="w-16 bg-gray-100 rounded-full h-2 overflow-hidden">
                                                    <div
                                                        class="h-full rounded-full bg-emerald-500"
                                                        :style="{ width: `${Math.min(100, item.pct || 0)}%` }"
                                                    ></div>
                                                </div>
                                                <span class="font-mono font-black text-xs text-slate-900 w-10 text-right">{{ item.pct || 0 }}%</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <span
                                                :class="{
                                                    'bg-emerald-50 text-emerald-700 border-emerald-200': item.status === 'Completed',
                                                    'bg-blue-50 text-blue-700 border-blue-200': item.status === 'In Progress',
                                                    'bg-gray-100 text-gray-600 border-gray-200': item.status === 'Not Started'
                                                }"
                                                class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                            >
                                                {{ item.status }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Geo-Tagged Media Gallery -->
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <div>
                                <h3 class="font-extrabold text-sm text-gray-900">
                                    Geo-Tagged Site Media &amp; Field Inspection Photos
                                </h3>
                                <p class="text-[11px] text-gray-400">
                                    Photos captured on ground with GPS stamps for technical verification.
                                </p>
                            </div>
                            <span class="text-xs font-mono font-bold text-gray-500">
                                {{ parsedMedia.length }} files
                            </span>
                        </div>

                        <!-- Video / Drone URL Link -->
                        <div v-if="report.video_url" class="p-4 rounded-xl bg-gradient-to-r from-teal-50 to-blue-50 border border-teal-200 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="w-9 h-9 rounded-xl bg-teal-600 text-white flex items-center justify-center font-bold text-base shadow-xs shrink-0">
                                    🎥
                                </span>
                                <div>
                                    <div class="text-xs font-black text-teal-950">Drone Flight / Video Survey Footage</div>
                                    <p class="text-[11px] text-teal-700 truncate max-w-md font-mono mt-0.5">
                                        {{ report.video_url }}
                                    </p>
                                </div>
                            </div>
                            <a
                                :href="report.video_url"
                                target="_blank"
                                class="px-3.5 py-1.5 rounded-lg bg-white border border-teal-300 text-teal-800 text-xs font-bold shadow-2xs hover:bg-teal-50 shrink-0"
                            >
                                Open Video &rarr;
                            </a>
                        </div>

                        <!-- Photo Grid -->
                        <div v-if="!parsedMedia.length" class="text-xs text-gray-500 italic py-4 text-center">
                            No field photographs were uploaded with this submission.
                        </div>

                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            <div
                                v-for="(file, idx) in parsedMedia"
                                :key="idx"
                                @click="activePhoto = file"
                                class="rounded-xl border border-gray-200 overflow-hidden bg-slate-50 shadow-2xs group cursor-pointer hover:shadow-md transition"
                            >
                                <div class="h-36 bg-gray-200 relative overflow-hidden flex items-center justify-center">
                                    <img
                                        v-if="file.type === 'photo'"
                                        :src="file.path"
                                        :alt="file.caption"
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                    />
                                    <div v-else class="text-gray-400 font-bold text-xs flex flex-col items-center gap-1">
                                        <span class="text-2xl">🎬</span>
                                        <span>Video Attachment</span>
                                    </div>
                                    <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white font-bold text-xs">
                                        🔍 Click to Enlarge
                                    </div>
                                </div>
                                <div class="p-3 space-y-1">
                                    <div class="text-xs font-black text-gray-900 truncate" :title="file.caption || file.name">
                                        {{ file.caption || file.name || 'Site inspection photo' }}
                                    </div>
                                    <div v-if="file.lat" class="text-[10px] text-gray-500 font-mono flex items-center gap-1">
                                        <span>📍</span>
                                        <span>{{ file.lat }}, {{ file.lng }}</span>
                                    </div>
                                    <div v-if="file.capture_date" class="text-[10px] text-gray-400">
                                        Captured on {{ file.capture_date }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Narrative Summary -->
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5 space-y-2">
                        <h3 class="font-extrabold text-sm text-gray-900">Narrative Progress Summary</h3>
                        <p class="text-xs text-gray-700 leading-relaxed bg-slate-50 p-4 rounded-xl border border-gray-200 whitespace-pre-line">
                            {{ report.narrative_report || 'No detailed narrative entered.' }}
                        </p>
                    </div>

                </div>

                <!-- SIDEBAR: Progression Curve, Baseline & Review Actions -->
                <div class="space-y-6">

                    <!-- Technical Review & BB Inspection Panel -->
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5 space-y-4">
                        <h3 class="font-extrabold text-sm text-gray-900 border-b border-gray-100 pb-3 flex items-center justify-between">
                            <span>BB Technical Inspection</span>
                            <span :class="badge.class" class="text-[10px] font-mono px-2 py-0.5 rounded font-bold border">
                                {{ badge.label }}
                            </span>
                        </h3>

                        <!-- Inspected & Submitted to MoJS -->
                        <div v-if="report.status === 'REVIEWED_BY_BB' || report.status === 'APPROVED' || report.bb_reviewed_at" class="space-y-3">
                            <div v-if="report.bb_remarks" class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 space-y-1">
                                <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 block">
                                    Inspection Findings / Observations
                                </span>
                                <p class="leading-relaxed whitespace-pre-line">{{ report.bb_remarks }}</p>
                            </div>

                            <div v-if="report.bb_inspection_doc_path" class="pt-1">
                                <a
                                    :href="report.bb_inspection_doc_path"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-blue-200 text-blue-700 hover:bg-blue-50 text-xs font-bold transition shadow-2xs"
                                >
                                    <span>📑</span>
                                    <span>Download BB Inspection Document</span>
                                </a>
                            </div>

                            <div v-if="report.bb_reviewed_at" class="text-[11px] text-gray-500 flex items-center justify-between pt-2 border-t border-gray-100">
                                <span>Inspected Date:</span>
                                <strong class="text-gray-800">{{ formatDate(report.bb_reviewed_at) }}</strong>
                            </div>
                        </div>

                        <!-- If BB Official & Still Pending Inspection (SUBMITTED) -->
                        <div v-else-if="isBB && report.status === 'SUBMITTED'" class="space-y-2.5">
                            <p class="text-xs text-gray-600">
                                Brahmaputra Board field verification of monthly site execution. Record observations and forward directly to MoJS.
                            </p>
                            <button
                                @click="openDecisionModal('REVIEWED_BY_BB')"
                                class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <span>✅</span>
                                <span>Submit BB Inspection Report to MoJS</span>
                            </button>
                            <button
                                @click="openDecisionModal('NEEDS_CORRECTION')"
                                class="w-full py-2 px-4 rounded-xl bg-amber-50 hover:bg-amber-100 border border-amber-300 text-amber-900 font-bold text-xs transition cursor-pointer"
                            >
                                ⚠️ Request State Correction
                            </button>
                        </div>

                        <!-- If Pending BB Inspection for other users -->
                        <div v-else-if="report.status === 'SUBMITTED'" class="text-xs text-gray-500 italic py-2">
                            Monthly progress report submitted by State. Under routine scrutiny by Brahmaputra Board.
                        </div>
                    </div>

                    <!-- Chronological Progression Timeline for Scheme -->
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <h3 class="font-extrabold text-sm text-gray-900">
                                Progression History (Time-Series)
                            </h3>
                            <span class="text-[10px] font-bold text-[#0F4C9F]">
                                {{ timeline.length }} Reports
                            </span>
                        </div>

                        <div class="space-y-3 relative before:absolute before:inset-0 before:left-3 before:w-0.5 before:bg-gray-200">
                            <div
                                v-for="t in timeline"
                                :key="t.id"
                                class="relative flex items-start gap-3 text-xs pl-1 group"
                            >
                                <div
                                    :class="t.id === report.id ? 'bg-[#0F4C9F] ring-4 ring-blue-100' : 'bg-emerald-500'"
                                    class="w-5 h-5 rounded-full flex items-center justify-center text-white font-mono text-[9px] shrink-0 mt-0.5 shadow-xs"
                                >
                                    {{ t.reporting_month }}
                                </div>
                                <div
                                    :class="t.id === report.id ? 'bg-blue-50/70 border-blue-200' : 'bg-slate-50 border-gray-200'"
                                    class="flex-1 p-2.5 rounded-xl border"
                                >
                                    <div class="flex items-center justify-between">
                                        <Link :href="route('progress-reports.show', t.id)" class="font-bold text-gray-900 hover:text-[#0F4C9F]">
                                            {{ t.reporting_period }}
                                        </Link>
                                        <span class="font-black text-emerald-700 font-mono">{{ t.physical_progress_pct }}%</span>
                                    </div>
                                    <div class="text-[10px] text-gray-400 mt-0.5 flex items-center justify-between">
                                        <span>Fin: {{ t.financial_progress_pct }}%</span>
                                        <span>{{ t.status }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Linked Fund Release Claim Baseline Card -->
                    <div v-if="report.last_payment_request && report.last_payment_request.id" class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5 space-y-3">
                        <span class="text-[10px] font-black uppercase tracking-wider text-gray-400">Baseline Connection</span>
                        <h4 class="text-xs font-black text-gray-900">
                            Linked Fund Claim #{{ report.last_payment_request.id }} (Instalment #{{ report.last_payment_request.instalment_number }})
                        </h4>
                        <div class="space-y-1.5 text-xs text-gray-600">
                            <div class="flex justify-between">
                                <span>Physical at Claim:</span>
                                <strong class="text-gray-900">{{ report.last_payment_request.physical_progress_pct }}%</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Claim Amount:</span>
                                <strong class="text-gray-900">₹{{ report.last_payment_request.requested_amount_cr }} Cr</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Claim Status:</span>
                                <strong class="text-emerald-700">{{ report.last_payment_request.status }}</strong>
                            </div>
                        </div>
                        <Link
                            :href="route('fund-release.show', report.last_payment_request.id)"
                            class="block text-center text-xs font-bold text-[#0F4C9F] pt-2 border-t border-gray-100 hover:underline"
                        >
                            View Fund Release Claim &rarr;
                        </Link>
                    </div>

                </div>

            </div>

        </div>

        <!-- Lightbox Modal for Photo Zoom -->
        <div
            v-if="activePhoto"
            class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
            @click="activePhoto = null"
        >
            <div class="max-w-4xl max-h-[90vh] bg-white rounded-2xl overflow-hidden shadow-2xl relative" @click.stop>
                <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-sm">{{ activePhoto.caption || activePhoto.name }}</h4>
                        <p v-if="activePhoto.lat" class="text-xs text-gray-400 font-mono mt-0.5">
                            GPS: {{ activePhoto.lat }}, {{ activePhoto.lng }}
                        </p>
                    </div>
                    <button @click="activePhoto = null" class="text-gray-400 hover:text-white font-bold text-lg p-1">
                        ✕
                    </button>
                </div>
                <div class="p-2 bg-black flex items-center justify-center max-h-[75vh] overflow-hidden">
                    <img :src="activePhoto.path" class="max-h-[70vh] object-contain" />
                </div>
            </div>
        </div>

        <!-- Review Decision Modal -->
        <div
            v-if="showDecisionModal"
            class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
            @click="showDecisionModal = false"
        >
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.stop>
                <div class="border-b border-gray-100 pb-3">
                    <h3 class="font-extrabold text-base text-gray-900">
                        {{ decisionType === 'REVIEWED_BY_BB' ? 'Submit BB Inspection Report to MoJS' : 'Request State Correction' }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ decisionType === 'REVIEWED_BY_BB' 
                            ? 'Record technical field observations and forward verified progress to Ministry (MoJS).' 
                            : 'Specify discrepancies observed for the State Agency to amend and resubmit.' }}
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-700 mb-1">
                        {{ decisionType === 'REVIEWED_BY_BB' ? 'Field Inspection Observations & Findings' : 'Reason for Correction' }} <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        v-model="decisionForm.remarks"
                        rows="4"
                        :placeholder="decisionType === 'REVIEWED_BY_BB' ? 'Enter site inspection remarks, quality of civil works, physical verification notes...' : 'Specify what details or documents need correction...'"
                        class="w-full rounded-xl border-gray-300 text-xs focus:border-[#0F4C9F] focus:ring-[#0F4C9F]"
                        required
                    ></textarea>
                </div>

                <div v-if="decisionType === 'REVIEWED_BY_BB'">
                    <label class="block text-xs font-black text-gray-700 mb-1">
                        Attach Official Inspection Report PDF (Optional)
                    </label>
                    <input
                        type="file"
                        accept=".pdf,image/*"
                        @change="e => decisionForm.inspection_doc = e.target.files[0]"
                        class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#0F4C9F]/10 file:text-[#0F4C9F]"
                    />
                </div>

                <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                    <button
                        type="button"
                        @click="showDecisionModal = false"
                        class="px-4 py-2 rounded-xl border border-gray-300 text-xs font-bold text-gray-700 hover:bg-gray-50 cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitDecision"
                        :disabled="decisionForm.processing || !decisionForm.remarks"
                        class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs disabled:opacity-50 cursor-pointer"
                    >
                        {{ decisionType === 'REVIEWED_BY_BB' ? 'Submit Inspection to MoJS' : 'Send Correction Notice' }}
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
