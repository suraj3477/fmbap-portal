<script setup>
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
    userRole: {
        type: String,
        default: '',
    }
});

// ── Main project form modal ──────────────────────────────────────────────────
const isModalOpen = ref(false);
const editingProject = ref(null);

const form = useForm({
    scheme_code: '',
    scheme_name: '',
    estimated_cost_cr: '',
    executed_amount_cr: '',
    funding_pattern: '90/10',
    central_share_cr: '',
    state_share_cr: '',
    released_central_share_cr: '',
    released_state_share_cr: '',
    balance_central_share_cr: 0,
    balance_state_share_cr: 0,
    remarks: '',
    state_govt_doc: null,
    state_govt_submission_date: '',
    state_govt_doc_note: '',
    brahmaputra_board_doc: null,
    brahmaputra_board_submission_date: '',
    brahmaputra_board_doc_note: '',
    mojs_doc: null,
    mojs_submission_date: '',
    mojs_doc_note: '',
});

watch(
    [
        () => form.central_share_cr,
        () => form.state_share_cr,
        () => form.released_central_share_cr,
        () => form.released_state_share_cr,
    ],
    () => {
        const cShare = parseFloat(form.central_share_cr) || 0;
        const sShare = parseFloat(form.state_share_cr) || 0;
        const relCentral = parseFloat(form.released_central_share_cr) || 0;
        const relState = parseFloat(form.released_state_share_cr) || 0;
        form.balance_central_share_cr = (cShare - relCentral).toFixed(2);
        form.balance_state_share_cr = (sShare - relState).toFixed(2);
    }
);

// Real-time Share Calculation: Share = Executed Amount * (Pattern Percentage / 100)
watch(
    [
        () => form.executed_amount_cr,
        () => form.funding_pattern,
    ],
    () => {
        const execAmt = parseFloat(form.executed_amount_cr) || 0;
        const pattern = form.funding_pattern || '90/10';
        const parts = pattern.split('/');
        if (parts.length === 2) {
            const centralPct = parseFloat(parts[0]) || 0;
            const statePct = parseFloat(parts[1]) || 0;

            form.central_share_cr = ((execAmt * centralPct) / 100).toFixed(2);
            form.state_share_cr = ((execAmt * statePct) / 100).toFixed(2);
        }
    }
);

const submitProject = () => {
    if (editingProject.value) {
        form.transform((data) => ({
            ...data,
            _method: 'patch',
        })).post(route('fmbap.update', editingProject.value.id), {
            forceFormData: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
                editingProject.value = null;
            },
        });
    } else {
        form.post(route('fmbap.store'), {
            forceFormData: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
};

const openAddModal = () => {
    editingProject.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (item) => {
    editingProject.value = item;
    form.scheme_code = item.scheme_code || '';
    form.scheme_name = item.scheme_name || item.title || '';
    form.estimated_cost_cr = item.estimated_cost_cr || '';
    form.executed_amount_cr = item.executed_amount_cr || '';
    form.funding_pattern = item.funding_pattern || '90/10';
    form.central_share_cr = item.central_share_cr || '';
    form.state_share_cr = item.state_share_cr || '';
    form.released_central_share_cr = item.released_central_share_cr || '';
    form.released_state_share_cr = item.released_state_share_cr || '';
    form.balance_central_share_cr = item.balance_central_share_cr || 0;
    form.balance_state_share_cr = item.balance_state_share_cr || 0;
    form.remarks = item.remarks || '';
    form.state_govt_submission_date = item.state_govt_submission_date || '';
    form.state_govt_doc_note = item.state_govt_doc_note || '';
    form.brahmaputra_board_submission_date = item.brahmaputra_board_submission_date || '';
    form.brahmaputra_board_doc_note = item.brahmaputra_board_doc_note || '';
    form.mojs_submission_date = item.mojs_submission_date || '';
    form.mojs_doc_note = item.mojs_doc_note || '';
    form.state_govt_doc = null;
    form.brahmaputra_board_doc = null;
    form.mojs_doc = null;
    isModalOpen.value = true;
};

// ── Forward to MoJS custom modal ─────────────────────────────────────────────
const forwardMojsModal = ref(false);
const forwardMojsItem = ref(null);
const forwardMojsRemarks = ref('');
const forwardMojsProcessing = ref(false);

const forwardToMojs = (item) => {
    forwardMojsItem.value = item;
    forwardMojsRemarks.value = '';
    forwardMojsModal.value = true;
};

const submitForwardToMojs = () => {
    forwardMojsProcessing.value = true;
    useForm({ bb_remarks: forwardMojsRemarks.value }).post(
        route('fmbap.projects.forwardMojs', forwardMojsItem.value.id),
        {
            onSuccess: () => {
                forwardMojsModal.value = false;
                forwardMojsProcessing.value = false;
            },
            onError: () => { forwardMojsProcessing.value = false; },
        }
    );
};

// ── Add MoJS Decision custom modal ───────────────────────────────────────────
const mojsDecisionModal = ref(false);
const mojsDecisionItem = ref(null);
const mojsDecisionRemarks = ref('');
const mojsDecisionProcessing = ref(false);

const addMojsDecision = (item) => {
    mojsDecisionItem.value = item;
    mojsDecisionRemarks.value = '';
    mojsDecisionModal.value = true;
};

const submitMojsDecision = () => {
    mojsDecisionProcessing.value = true;
    useForm({ mojs_remarks: mojsDecisionRemarks.value }).post(
        route('fmbap.projects.mojsDecision', mojsDecisionItem.value.id),
        {
            onSuccess: () => {
                mojsDecisionModal.value = false;
                mojsDecisionProcessing.value = false;
            },
            onError: () => { mojsDecisionProcessing.value = false; },
        }
    );
};

// ── Forward Decision to State — direct submit, no modal needed ───────────────
const forwardStateProcessing = ref(false);

const forwardDecisionToState = (item) => {
    if (forwardStateProcessing.value) return;
    forwardStateProcessing.value = true;
    useForm().post(route('fmbap.projects.forwardState', item.id), {
        onSuccess: () => { forwardStateProcessing.value = false; },
        onError:   () => { forwardStateProcessing.value = false; },
    });
};

// ── Status badge helper ──────────────────────────────────────────────────────
const statusConfig = {
    SUBMITTED_BY_STATE:  { label: 'Submitted by State', color: 'bg-blue-100 text-blue-800 border-blue-200' },
    PROPOSAL_SUBMITTED:  { label: 'Proposal Submitted', color: 'bg-sky-100 text-sky-800 border-sky-200' },
    FORWARDED_TO_MOJS:   { label: 'Forwarded to MoJS', color: 'bg-indigo-100 text-indigo-800 border-indigo-200' },
    REVIEWED_BY_MOJS:    { label: 'Reviewed by MoJS', color: 'bg-purple-100 text-purple-800 border-purple-200' },
    RETURNED_TO_STATE:   { label: 'Approved by BB', color: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
};

const getStatusConfig = (status) =>
    statusConfig[status] ?? { label: status, color: 'bg-gray-100 text-gray-700 border-gray-200' };

// ── Fund release modal ────────────────────────────────────────────────────────
const isReleaseModalOpen = ref(false);
const releaseProject = ref(null);
const releaseForm = useForm({
    instalment_number: '',
    requested_amount_cr: '',
    bank_details: '',
    physical_progress_pct: '',
    financial_progress_pct: '',
    utilization_certificate: null,
    state_remarks: '',
});

const openReleaseModal = (item) => {
    releaseProject.value = item;
    releaseForm.reset();
    isReleaseModalOpen.value = true;
};

const submitReleaseRequest = () => {
    releaseForm.post(route('fmbap.projects.requestRelease', releaseProject.value.id), {
        forceFormData: true,
        onSuccess: () => {
            isReleaseModalOpen.value = false;
            releaseForm.reset();
            releaseProject.value = null;
        },
    });
};

const requestStatusConfigs = {
    DRAFT: { label: 'Draft', color: 'bg-gray-100 text-gray-700 border border-gray-200' },
    SUBMITTED_TO_BB: { label: 'Submitted to Board', color: 'bg-blue-100 text-blue-800 border border-blue-200' },
    BB_MONITORING_PENDING: { label: 'BB Monitoring', color: 'bg-amber-100 text-amber-800 border border-amber-200' },
    FORWARDED_TO_MOJS: { label: 'Forwarded to MoJS', color: 'bg-indigo-100 text-indigo-800 border border-indigo-200' },
    APPROVED: { label: 'Approved', color: 'bg-emerald-100 text-emerald-800 border border-emerald-200' },
    REJECTED: { label: 'Rejected', color: 'bg-red-100 text-red-800 border border-red-200' },
    NEEDS_CORRECTION: { label: 'Needs Correction', color: 'bg-orange-100 text-orange-800 border border-orange-200' },
};

const getRequestStatusLabel = (status) => requestStatusConfigs[status]?.label ?? status;
const getRequestStatusColor = (status) => requestStatusConfigs[status]?.color ?? 'bg-gray-100 text-gray-700';

const hasPendingRequest = (item) => {
    if (!item.scheme || !item.scheme.payment_requests) return false;
    return item.scheme.payment_requests.some(req => !['APPROVED', 'REJECTED'].includes(req.status));
};
</script>

<template>
    <Head title="Submission of FMBAP proposals" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    FMBAP Approved Projects (Financial Tracking)
                </h2>
                <div class="flex space-x-2">
                    <a :href="route('fmbap.export')" target="_blank"
                        class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded-lg shadow text-sm transition-all duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                        Export to Excel
                    </a>
                </div>
            </div>
        </template>

        <div class="py-6 mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-lg font-bold text-gray-800">Approved FMBAP Projects</h3>
                    <span class="text-sm text-gray-500 font-semibold bg-gray-100 px-3 py-1 rounded-lg">₹ in Crore</span>
                </div>

                <table class="w-full text-left border-collapse text-sm border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100 text-gray-800 font-bold border-b text-center">
                            <th class="py-3 px-2 border border-gray-300" rowspan="2">SL No</th>
                            <th class="py-3 px-2 border border-gray-300" rowspan="2">Scheme Code</th>
                            <th class="py-3 px-2 border border-gray-300" rowspan="2">Name of Scheme</th>
                            <th class="py-3 px-2 border border-gray-300" rowspan="2">Status</th>
                            <th class="py-3 px-2 border border-gray-300" rowspan="2">Est. Cost / IMC</th>
                            <th class="py-3 px-2 border border-gray-300" rowspan="2">Executed Amt</th>
                            <th class="py-3 px-2 border border-gray-300" rowspan="2">Funding Pattern</th>
                            <th class="py-3 px-2 border border-gray-300" colspan="2">Share</th>
                            <th class="py-3 px-2 border border-gray-300" colspan="2">Released</th>
                            <th class="py-3 px-2 border border-gray-300" colspan="2">Balance</th>
                            <th class="py-3 px-2 border border-gray-300" rowspan="2">State Remarks</th>
                            <th class="py-3 px-2 border border-gray-300" rowspan="2">BB Remarks</th>
                            <th class="py-3 px-2 border border-gray-300" rowspan="2">MoJS Remarks</th>
                            <th class="py-3 px-2 border border-gray-300" colspan="2">State Government</th>
                            <th class="py-3 px-2 border border-gray-300" colspan="2">Brahmaputra Board</th>
                            <th class="py-3 px-2 border border-gray-300" colspan="2">FM Section, MoJS</th>
                            <th class="py-3 px-2 border border-gray-300" rowspan="2">Edit</th>
                            <th class="py-3 px-2 border border-gray-300" rowspan="2">Workflow</th>
                        </tr>
                        <tr class="bg-gray-50 text-gray-700 font-semibold border-b text-center">
                            <th class="py-3 px-2 border border-gray-300">Central</th>
                            <th class="py-3 px-2 border border-gray-300">State</th>
                            <th class="py-3 px-2 border border-gray-300">Central</th>
                            <th class="py-3 px-2 border border-gray-300">State</th>
                            <th class="py-3 px-2 border border-gray-300">Central</th>
                            <th class="py-3 px-2 border border-gray-300">State</th>
                            <th class="py-3 px-2 border border-gray-300">Doc (PDF)</th>
                            <th class="py-3 px-2 border border-gray-300">Date</th>
                            <th class="py-3 px-2 border border-gray-300">Doc (PDF)</th>
                            <th class="py-3 px-2 border border-gray-300">Date</th>
                            <th class="py-3 px-2 border border-gray-300">Doc (PDF)</th>
                            <th class="py-3 px-2 border border-gray-300">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in projects" :key="item.id"
                            class="border-b hover:bg-blue-50/40 transition-colors duration-100 text-center">
                            <td class="py-3 px-2 border border-gray-200 font-bold text-gray-700">{{ index + 1 }}</td>
                            <td class="py-3 px-2 border border-gray-200 font-mono text-gray-800 font-semibold">{{ item.scheme_code }}</td>
                            <td class="py-3 px-2 border border-gray-200 text-left font-semibold text-gray-900">
                                {{ item.scheme_name || item.title }}
                            </td>
                            <!-- Status Badge -->
                            <td class="py-3 px-2 border border-gray-200">
                                <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border', getStatusConfig(item.status).color]">
                                    {{ getStatusConfig(item.status).label }}
                                </span>
                            </td>
                            <td class="py-3 px-2 border border-gray-200 font-medium text-gray-800">{{ item.estimated_cost_cr }}</td>
                            <td class="py-3 px-2 border border-gray-200 font-medium text-gray-800">{{ item.executed_amount_cr }}</td>
                            <td class="py-3 px-2 border border-gray-200 font-medium text-gray-800">{{ item.funding_pattern }}</td>
                            <td class="py-3 px-2 border border-gray-200 font-medium text-gray-800">{{ item.central_share_cr }}</td>
                            <td class="py-3 px-2 border border-gray-200 font-medium text-gray-800">{{ item.state_share_cr }}</td>
                            <td class="py-3 px-2 border border-gray-200 font-medium text-gray-800">{{ item.released_central_share_cr }}</td>
                            <td class="py-3 px-2 border border-gray-200 font-medium text-gray-800">{{ item.released_state_share_cr }}</td>
                            <td class="py-3 px-2 border border-gray-200 font-bold text-blue-700">{{ item.balance_central_share_cr }}</td>
                            <td class="py-3 px-2 border border-gray-200 font-bold text-blue-700">{{ item.balance_state_share_cr }}</td>
                            <td class="py-3 px-2 border border-gray-200 text-left text-gray-700">{{ item.remarks }}</td>
                            <td class="py-3 px-2 border border-gray-200 text-left text-gray-700">{{ item.bb_remarks }}</td>
                            <td class="py-3 px-2 border border-gray-200 text-left text-gray-700">{{ item.mojs_remarks }}</td>

                            <!-- State Govt -->
                            <td class="py-3 px-2 border border-gray-200">
                                <a v-if="item.state_govt_doc_path" :href="item.state_govt_doc_path" target="_blank"
                                    class="inline-flex items-center gap-1 text-red-600 hover:text-red-800 font-semibold">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4l6 6v10a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                                    PDF
                                </a>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="py-3 px-2 border border-gray-200 text-gray-700">{{ item.state_govt_submission_date || '—' }}</td>

                            <!-- Brahmaputra Board -->
                            <td class="py-3 px-2 border border-gray-200">
                                <a v-if="item.brahmaputra_board_doc_path" :href="item.brahmaputra_board_doc_path" target="_blank"
                                    class="inline-flex items-center gap-1 text-red-600 hover:text-red-800 font-semibold">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4l6 6v10a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                                    PDF
                                </a>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="py-3 px-2 border border-gray-200 text-gray-700">{{ item.brahmaputra_board_submission_date || '—' }}</td>

                            <!-- MoJS -->
                            <td class="py-3 px-2 border border-gray-200">
                                <a v-if="item.mojs_doc_path" :href="item.mojs_doc_path" target="_blank"
                                    class="inline-flex items-center gap-1 text-red-600 hover:text-red-800 font-semibold">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4l6 6v10a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                                    PDF
                                </a>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="py-3 px-2 border border-gray-200 text-gray-700">{{ item.mojs_submission_date || '—' }}</td>

                            <!-- Edit -->
                            <td class="py-3 px-2 border border-gray-200">
                                <button
                                    v-if="['board_official','mojs_official','viewer','super_admin','admin', 'bbrd_inspector', 'central_admin'].includes(userRole)"
                                    @click="openEditModal(item)"
                                    class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 font-semibold text-sm border border-blue-200 hover:border-blue-400 rounded-md px-2 py-1 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.536-6.536a2 2 0 012.828 2.828L11.828 15.828a2 2 0 01-1.415.586H7v-3.414a2 2 0 01.586-1.414z"/></svg>
                                    Edit
                                </button>
                                <button
                                    v-else-if="['state_official','state'].includes(userRole) && !item.state_govt_doc_path"
                                    @click="openEditModal(item)"
                                    class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-800 font-semibold text-sm border border-emerald-200 hover:border-emerald-400 rounded-md px-2 py-1 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    Add Details
                                </button>
                                <span v-else class="text-gray-300">—</span>
                            </td>

                            <!-- Workflow -->
                            <td class="py-3 px-2 border border-gray-200 space-y-1">
                                <!-- MoJS can add decision when forwarded -->
                                <button
                                    v-if="['mojs_official', 'central_admin', 'viewer', 'super_admin', 'admin'].includes(userRole) && item.status === 'FORWARDED_TO_MOJS'"
                                    @click="addMojsDecision(item)"
                                    class="block w-full bg-purple-600 hover:bg-purple-700 text-white rounded-md px-2 py-1 text-xs font-semibold transition-colors">
                                    Add Decision
                                </button>
                                <!-- BB can forward MoJS decision back to State -->
                                <button
                                    v-if="['board_official', 'bbrd_inspector', 'super_admin', 'admin'].includes(userRole) && item.status === 'REVIEWED_BY_MOJS'"
                                    @click="forwardDecisionToState(item)"
                                    class="block w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-md px-2 py-1 text-xs font-semibold transition-colors">
                                    Forward to State
                                </button>
                                <!-- Active Payment Requests List -->
                                <div v-if="item.scheme && item.scheme.payment_requests && item.scheme.payment_requests.length > 0" class="pb-2 space-y-1">
                                    <div v-for="req in item.scheme.payment_requests" :key="req.id" class="text-[10px] border border-gray-200 rounded p-1 bg-gray-50 text-left leading-normal">
                                        <div class="font-bold text-gray-700 flex justify-between">
                                            <span>Instalment #{{ req.instalment_number }}</span>
                                            <span class="font-mono text-gray-500">₹{{ req.requested_amount_cr }} Cr</span>
                                        </div>
                                        <div class="mt-1 flex items-center justify-between gap-1">
                                            <span :class="['px-1 py-0.5 rounded text-[8px] font-extrabold uppercase tracking-wider', getRequestStatusColor(req.status)]">
                                                {{ getRequestStatusLabel(req.status) }}
                                            </span>
                                            <Link 
                                                v-if="['board_official','bbrd_inspector','super_admin','admin'].includes(userRole) && (req.status === 'SUBMITTED_TO_BB' || req.status === 'BB_MONITORING_PENDING')"
                                                :href="route('monitoring-requests.report.create', req.id)"
                                                class="text-[9px] font-bold text-indigo-600 hover:text-indigo-800 underline shrink-0">
                                                Fill Report
                                            </Link>
                                        </div>
                                    </div>
                                </div>

                                <!-- State can request fund release if they own the project and scheme_code is set -->
                                <button
                                    v-if="['state_official', 'state'].includes(userRole) && item.scheme_code && !hasPendingRequest(item)"
                                    @click="openReleaseModal(item)"
                                    class="block w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-md px-2 py-1 text-xs font-semibold text-center transition-colors shadow-sm">
                                    Request Release
                                </button>
                                <span
                                    v-if="(!['board_official','bbrd_inspector','mojs_official','central_admin','viewer','super_admin','admin'].includes(userRole) && !item.scheme_code && (!item.scheme || !item.scheme.payment_requests || item.scheme.payment_requests.length === 0)) || (['mojs_official','central_admin','viewer'].includes(userRole) && item.status !== 'FORWARDED_TO_MOJS') || (['board_official','bbrd_inspector'].includes(userRole) && !['APPROVED','SUBMITTED_BY_STATE','PROPOSAL_SUBMITTED','REVIEWED_BY_MOJS'].includes(item.status))"
                                    class="text-gray-300">—</span>
                            </td>
                        </tr>
                        <tr v-if="projects.length === 0">
                            <td colspan="24" class="p-12 text-center text-gray-500 text-base">
                                <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l2 2h5a2 2 0 012 2v10a2 2 0 01-2 2z"/></svg>
                                No submission records available.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════
             MAIN PROJECT ADD/EDIT MODAL
        ══════════════════════════════════════════════════════════════════ -->
        <Transition name="modal-fade">
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="isModalOpen = false" />

            <!-- Panel -->
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-h-[92vh] flex flex-col overflow-hidden">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b bg-gradient-to-r from-blue-600 to-indigo-600">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l2 2h5a2 2 0 012 2v14a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-white font-bold text-base">
                            {{ editingProject ? 'Edit FMBAP Proposal' : 'Submit FMBAP Proposal' }}
                        </h3>
                    </div>
                    <button @click="isModalOpen = false"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-white/70 hover:text-white hover:bg-white/20 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="overflow-y-auto p-6 space-y-5">
                    <form id="projectForm" @submit.prevent="submitProject" class="space-y-5">

                        <!-- Scheme Info -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Scheme Code No</label>
                                <input v-model="form.scheme_code" type="text"
                                    class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-sm outline-none transition" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Name of Scheme</label>
                                <input v-model="form.scheme_name" type="text"
                                    class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-sm outline-none transition" />
                            </div>
                        </div>

                        <!-- Amounts -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Est. Amount / IMC Cost</label>
                                <input v-model="form.estimated_cost_cr" type="number" step="0.01" min="0"
                                    class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-sm outline-none transition" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Executed Amount</label>
                                <input v-model="form.executed_amount_cr" type="number" step="0.01" min="0"
                                    class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-sm outline-none transition" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Funding Pattern</label>
                                <select v-model="form.funding_pattern"
                                    class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-sm outline-none transition">
                                    <option value="" disabled>Select pattern</option>
                                    <option value="90/10">90/10</option>
                                    <option value="80/20">80/20</option>
                                    <option value="70/30">70/30</option>
                                    <option value="60/40">60/40</option>
                                    <option value="50/50">50/50</option>
                                    <option value="100/0">100/0</option>
                                </select>
                            </div>
                        </div>

                        <!-- Share -->
                        <div class="rounded-xl border border-gray-200 p-4 bg-gray-50">
                            <p class="text-xs font-bold text-gray-500 uppercase mb-3">Share (₹ Cr)</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Central Share</label>
                                    <input v-model="form.central_share_cr" type="number" step="0.01" min="0"
                                        class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-sm outline-none transition" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">State Share</label>
                                    <input v-model="form.state_share_cr" type="number" step="0.01" min="0"
                                        class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-sm outline-none transition" />
                                </div>
                            </div>
                        </div>

                        <!-- Released -->
                        <div class="rounded-xl border border-gray-200 p-4 bg-gray-50">
                            <p class="text-xs font-bold text-gray-500 uppercase mb-3">Released (₹ Cr)</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Central Share</label>
                                    <input v-model="form.released_central_share_cr" type="number" step="0.01" min="0"
                                        class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-sm outline-none transition" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">State Share</label>
                                    <input v-model="form.released_state_share_cr" type="number" step="0.01" min="0"
                                        class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-sm outline-none transition" />
                                </div>
                            </div>
                        </div>

                        <!-- Balance (read-only) -->
                        <div class="rounded-xl border border-blue-200 p-4 bg-blue-50">
                            <p class="text-xs font-bold text-blue-600 uppercase mb-3">Balance (Auto-calculated)</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-blue-600 mb-1">Central Share</label>
                                    <input :value="form.balance_central_share_cr" type="number" readonly
                                        class="w-full rounded-lg border border-blue-200 bg-white text-blue-800 font-bold px-3 py-2 text-sm outline-none cursor-not-allowed" />
                                </div>
                                <div>
                                    <label class="block text-xs text-blue-600 mb-1">State Share</label>
                                    <input :value="form.balance_state_share_cr" type="number" readonly
                                        class="w-full rounded-lg border border-blue-200 bg-white text-blue-800 font-bold px-3 py-2 text-sm outline-none cursor-not-allowed" />
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">State Remarks</label>
                            <textarea v-model="form.remarks" rows="2"
                                class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-sm outline-none transition resize-none" />
                        </div>

                        <!-- Documents -->
                        <div class="space-y-3">
                            <p class="text-xs font-bold text-gray-500 uppercase">Document Uploads (PDF only)</p>

                            <div class="rounded-xl border border-gray-200 p-4 bg-slate-50">
                                <p class="text-xs font-semibold text-slate-600 mb-3">State Government</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Document</label>
                                        <input type="file" accept="application/pdf"
                                            @change="e => form.state_govt_doc = e.target.files[0]"
                                            class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Date of Submission</label>
                                        <input v-model="form.state_govt_submission_date" type="date"
                                            class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-sm outline-none transition" />
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="block text-xs text-gray-500 mb-1">Additional Information <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <textarea v-model="form.state_govt_doc_note" rows="2" placeholder="Add any notes or information about this document..."
                                        class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-sm outline-none transition resize-none"></textarea>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 p-4 bg-slate-50">
                                <p class="text-xs font-semibold text-slate-600 mb-3">Brahmaputra Board</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Document</label>
                                        <input type="file" accept="application/pdf"
                                            @change="e => form.brahmaputra_board_doc = e.target.files[0]"
                                            class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Date of Submission</label>
                                        <input v-model="form.brahmaputra_board_submission_date" type="date"
                                            class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-sm outline-none transition" />
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="block text-xs text-gray-500 mb-1">Additional Information <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <textarea v-model="form.brahmaputra_board_doc_note" rows="2" placeholder="Add any notes or information about this document..."
                                        class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-sm outline-none transition resize-none"></textarea>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 p-4 bg-slate-50">
                                <p class="text-xs font-semibold text-slate-600 mb-3">FM Section, MoJS</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Document</label>
                                        <input type="file" accept="application/pdf"
                                            @change="e => form.mojs_doc = e.target.files[0]"
                                            class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Date of Submission</label>
                                        <input v-model="form.mojs_submission_date" type="date"
                                            class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-sm outline-none transition" />
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="block text-xs text-gray-500 mb-1">Additional Information <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <textarea v-model="form.mojs_doc_note" rows="2" placeholder="Add any notes or information about this document..."
                                        class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-sm outline-none transition resize-none"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
                    <button type="button" @click="isModalOpen = false"
                        class="px-5 py-2 text-sm font-semibold text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" form="projectForm" :disabled="form.processing"
                        class="px-6 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2">
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        {{ editingProject ? 'Update Proposal' : 'Submit Proposal' }}
                    </button>
                </div>
            </div>
        </div>
        </Transition>



        <!-- ══════════════════════════════════════════════════════════════════
             MOJS DECISION MODAL
        ══════════════════════════════════════════════════════════════════ -->
        <Transition name="modal-fade">
        <div v-if="mojsDecisionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="mojsDecisionModal = false" />
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                <!-- Header -->
                <div class="flex items-center gap-3 px-6 py-4 bg-gradient-to-r from-purple-600 to-purple-700 border-b">
                    <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-white font-bold text-sm">MoJS Decision</h3>
                        <p class="text-purple-200 text-xs mt-0.5">Record decision / remarks from MoJS</p>
                    </div>
                    <button @click="mojsDecisionModal = false" class="ml-auto w-7 h-7 flex items-center justify-center rounded-lg text-white/70 hover:text-white hover:bg-white/20 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <!-- Body -->
                <div class="p-6 space-y-4">
                    <div class="bg-purple-50 border border-purple-100 rounded-xl p-3 text-xs text-purple-700">
                        <span class="font-semibold">Scheme:</span> {{ mojsDecisionItem?.scheme_name || mojsDecisionItem?.title }}
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Decision / Remarks <span class="text-gray-400 font-normal">(optional)</span></label>
                        <textarea v-model="mojsDecisionRemarks" rows="3" placeholder="Enter MoJS decision or remarks..."
                            class="w-full rounded-xl border border-gray-300 focus:border-purple-500 focus:ring-2 focus:ring-purple-200 px-3 py-2.5 text-sm outline-none transition resize-none" />
                    </div>
                </div>
                <!-- Footer -->
                <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
                    <button @click="mojsDecisionModal = false"
                        class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                        Cancel
                    </button>
                    <button @click="submitMojsDecision" :disabled="mojsDecisionProcessing"
                        class="px-5 py-2 text-sm font-semibold text-white bg-purple-600 hover:bg-purple-700 rounded-lg transition-colors disabled:opacity-60 flex items-center gap-2">
                        <svg v-if="mojsDecisionProcessing" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        Save Decision
                    </button>
                </div>
            </div>
        </div>
        </Transition>

        <!-- ══════════════════════════════════════════════════════════════════
             FORWARD DECISION TO STATE CONFIRM MODAL
        ══════════════════════════════════════════════════════════════════ -->
        <Transition name="modal-fade">
        <div v-if="forwardStateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="forwardStateModal = false" />
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
                <!-- Header -->
                <div class="flex items-center gap-3 px-6 py-4 bg-gradient-to-r from-emerald-600 to-emerald-700 border-b">
                    <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <h3 class="text-white font-bold text-sm">Forward to State</h3>
                        <p class="text-emerald-200 text-xs mt-0.5">Please confirm this action</p>
                    </div>
                    <button @click="forwardStateModal = false" class="ml-auto w-7 h-7 flex items-center justify-center rounded-lg text-white/70 hover:text-white hover:bg-white/20 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <!-- Body -->
                <div class="p-6 space-y-4">
                    <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-3 text-xs text-emerald-700">
                        <span class="font-semibold">Scheme:</span> {{ forwardStateItem?.scheme_name || forwardStateItem?.title }}
                    </div>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Are you sure you want to forward the <strong>MoJS decision</strong> back to the State Government? This action will update the proposal status to <span class="font-semibold text-amber-600">Returned to State</span>.
                    </p>
                </div>
                <!-- Footer -->
                <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
                    <button @click="forwardStateModal = false"
                        class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                        Cancel
                    </button>
                    <button @click="submitForwardToState" :disabled="forwardStateProcessing"
                        class="px-5 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors disabled:opacity-60 flex items-center gap-2">
                        <svg v-if="forwardStateProcessing" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        Yes, Forward
                    </button>
                </div>
            </div>
        </div>
        </Transition>

        <!-- ══════════════════════════════════════════════════════════════════
             REQUEST FUND RELEASE MODAL
        ══════════════════════════════════════════════════════════════════ -->
        <Transition name="modal-fade">
        <div v-if="isReleaseModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="isReleaseModalOpen = false" />

            <!-- Panel -->
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col overflow-hidden max-h-[92vh]">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b bg-gradient-to-r from-emerald-600 to-teal-600">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-base">Request Fund Release</h3>
                            <p class="text-emerald-100 text-xs mt-0.5">{{ releaseProject?.scheme_code }} — {{ releaseProject?.scheme_name || releaseProject?.title }}</p>
                        </div>
                    </div>
                    <button @click="isReleaseModalOpen = false"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-white/70 hover:text-white hover:bg-white/20 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 overflow-y-auto space-y-4 text-left">
                    <div v-if="releaseForm.errors.message" class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-lg">
                        {{ releaseForm.errors.message }}
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Instalment Number <span class="text-red-500">*</span></label>
                            <input v-model="releaseForm.instalment_number" type="number" min="1" step="1"
                                class="w-full rounded-lg border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 px-3 py-2 text-sm outline-none transition" required />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Requested Amount (₹ Cr) <span class="text-red-500">*</span></label>
                            <input v-model="releaseForm.requested_amount_cr" type="number" min="0.01" step="0.01"
                                class="w-full rounded-lg border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 px-3 py-2 text-sm outline-none transition" required />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Bank Details <span class="text-gray-400 font-normal">(optional)</span></label>
                        <textarea v-model="releaseForm.bank_details" rows="2" placeholder="Enter bank account name, number, IFSC..."
                            class="w-full rounded-lg border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 px-3 py-2 text-sm outline-none transition resize-none" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Physical Progress (%)</label>
                            <input v-model="releaseForm.physical_progress_pct" type="number" min="0" max="100" step="0.1"
                                class="w-full rounded-lg border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 px-3 py-2 text-sm outline-none transition" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Financial Progress (%)</label>
                            <input v-model="releaseForm.financial_progress_pct" type="number" min="0" max="100" step="0.1"
                                class="w-full rounded-lg border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 px-3 py-2 text-sm outline-none transition" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Utilization Certificate (UC) <span class="text-red-500">*</span> <span class="text-gray-400 font-normal">(PDF only)</span></label>
                        <input type="file" accept="application/pdf"
                            @change="e => releaseForm.utilization_certificate = e.target.files[0]"
                            class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-gray-300 rounded-lg p-1.5 bg-white outline-none" required />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Remarks / Notes</label>
                        <textarea v-model="releaseForm.state_remarks" rows="2" placeholder="Brief progress summary or remarks..."
                            class="w-full rounded-lg border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 px-3 py-2 text-sm outline-none transition resize-none" />
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
                    <button @click="isReleaseModalOpen = false"
                        class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                        Cancel
                    </button>
                    <button @click="submitReleaseRequest" :disabled="releaseForm.processing"
                        class="px-5 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors disabled:opacity-60 flex items-center gap-2">
                        <svg v-if="releaseForm.processing" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        Submit Release Request
                    </button>
                </div>
            </div>
        </div>
        </Transition>

    </AuthenticatedLayout>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}
.modal-fade-enter-active .relative,
.modal-fade-leave-active .relative {
    transition: transform 0.2s ease, opacity 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
.modal-fade-enter-from .relative {
    transform: scale(0.95) translateY(8px);
    opacity: 0;
}
</style>