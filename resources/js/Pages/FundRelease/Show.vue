<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import WorkflowStatusBar from '@/Components/WorkflowStatusBar.vue';
import AuditTimeline from '@/Components/AuditTimeline.vue';
import GeoTaggedGallery from '@/Components/GeoTaggedGallery.vue';

const props = defineProps({
    dossier: {
        type: Object,
        required: true,
    },
    steps: {
        type: Array,
        required: true,
    },
    revisions: {
        type: Array,
        default: () => [],
    },
    userRole: String,
});

const payment_request = computed(() => props.dossier?.payment_request || {});
const scheme = computed(() => props.dossier?.scheme || {});
const fmbap_project = computed(() => scheme.value?.fmbap_project || scheme.value?.fmbapProject || null);
const state_documents = computed(() => props.dossier?.state_documents || {});
const bb_monitoring_report = computed(() => props.dossier?.bb_monitoring_report || null);
const summary = computed(() => props.dossier?.summary || {});

const isBB = computed(() => props.userRole === 'board_official' || props.userRole === 'super_admin');
const isMoJS = computed(() => props.userRole === 'mojs_official' || props.userRole === 'super_admin');
const isStateOfficial = computed(() => ['state_official', 'state', 'super_admin'].includes(props.userRole));
const canEditForCorrection = computed(() => 
    payment_request.value?.status === 'NEEDS_CORRECTION' && isStateOfficial.value
);

const showDecisionModal = ref(false);
const decisionType = ref(''); // 'FORWARDED_TO_MOJS', 'APPROVED', 'NEEDS_CORRECTION', 'REJECTED'
const decisionForm = useForm({
    decision: '',
    remarks: '',
    bb_recommended_amount_cr: '',
    approved_amount_cr: '',
    curtailment_reason: '',
    sanction_order_no: '',
    sanction_order_date: '',
    sanction_order_doc: null,
});

const mojsCurtailmentDiff = computed(() => {
    const asked = parseFloat(payment_request.value?.requested_amount_cr) || 0;
    const approved = parseFloat(decisionForm.approved_amount_cr);
    if (isNaN(approved)) return '0.00';
    return (asked - approved).toFixed(2);
});

const openDecisionModal = (type) => {
    decisionType.value = type;
    decisionForm.decision = type;
    decisionForm.remarks = '';
    
    if (type === 'APPROVED') {
        const defaultAmount = payment_request.value?.bb_recommended_amount_cr 
            ?? bb_monitoring_report.value?.report?.bb_recommended_amount_cr 
            ?? payment_request.value?.requested_amount_cr 
            ?? '';
        decisionForm.approved_amount_cr = defaultAmount;
        decisionForm.curtailment_reason = 'Unspent SNA Balance / Accrued Interest (GFR 230(8))';
        const stCode = (payment_request.value?.state || 'NE').substring(0, 3).toUpperCase();
        const schCode = scheme.value?.scheme_code || 'SCH';
        decisionForm.sanction_order_no = `MoJS/FMBAP/${stCode}/${schCode}/2026-${payment_request.value?.id}`;
        decisionForm.sanction_order_date = new Date().toISOString().substring(0, 10);
        decisionForm.sanction_order_doc = null;
    } else if (isBB.value) {
        decisionForm.bb_recommended_amount_cr = payment_request.value?.bb_recommended_amount_cr 
            ?? payment_request.value?.requested_amount_cr 
            ?? '';
    }
    
    showDecisionModal.value = true;
};

const submitDecision = () => {
    const routeName = isBB.value ? 'fund-release.bbDecision' : 'fund-release.mojsDecision';
    decisionForm.post(route(routeName, payment_request.value.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showDecisionModal.value = false;
        },
    });
};
</script>

<template>
    <Head title="Payment Request Dossier" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Consolidated Dossier: #{{ payment_request.id }}
                    </h2>
                    <span
                        v-if="payment_request.status === 'NEEDS_CORRECTION'"
                        class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300"
                    >
                        ⚠️ Sent Back for Correction
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        v-if="canEditForCorrection"
                        :href="route('fund-release.edit', payment_request.id)"
                        class="text-sm font-bold text-white bg-amber-600 hover:bg-amber-700 px-4 py-2 rounded-lg shadow-sm transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        <span>✏️ Edit &amp; Resubmit Request</span>
                    </Link>
                    <a :href="route('fund-release.dossier', payment_request.id)" class="text-sm font-semibold text-blue-600 bg-white border border-blue-600 px-4 py-2 rounded shadow-sm hover:bg-blue-50">
                        Download PDF
                    </a>
                </div>
            </div>
        </template>

        <div class="py-8 mx-auto sm:px-6 lg:px-8 w-full">
            
            <!-- Sent Back for Correction Notification Banner -->
            <div v-if="payment_request.status === 'NEEDS_CORRECTION'" class="bg-amber-50 border-2 border-amber-400 rounded-xl p-5 mb-6 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="p-2.5 bg-amber-100 rounded-xl text-amber-800 text-xl shrink-0">
                        ⚠️
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-black text-amber-900 text-base">Claim Returned to State for Correction</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-200 text-amber-900 border border-amber-300">
                                Action Required
                            </span>
                        </div>
                        <p class="text-xs text-amber-800 mt-1 leading-relaxed">
                            <strong class="text-amber-950">Reason / Directives:</strong> {{ payment_request.bb_remarks || payment_request.mojs_remarks || 'Please review remarks and update the claim data / attachments.' }}
                        </p>
                    </div>
                </div>
                <Link
                    v-if="canEditForCorrection"
                    :href="route('fund-release.edit', payment_request.id)"
                    class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-lg shadow-md hover:shadow-lg transition shrink-0 flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    <span>✏️ Edit &amp; Resubmit Request &rarr;</span>
                </Link>
            </div>

            <!-- Workflow Status -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <WorkflowStatusBar :steps="steps" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left Column: Data & Docs -->
                <div class="col-span-2 space-y-6">
                    
                    <!-- Scheme & Request Info -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="font-bold text-gray-800">1. Scheme & Claim Details</h3>
                            <span class="text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-3 py-1 rounded-full">
                                Funding Pattern: {{ fmbap_project?.funding_pattern || (scheme?.central_share_pct ? `${scheme.central_share_pct}/${scheme.state_share_pct}` : '70/30') }}
                            </span>
                        </div>
                        <div class="p-6 grid grid-cols-2 gap-4 text-sm">
                            <div><span class="text-gray-500 block">Scheme Code</span><span class="font-bold text-gray-900">{{ scheme.scheme_code }}</span></div>
                            <div><span class="text-gray-500 block">State</span><span class="font-bold text-gray-900">{{ scheme.state }}</span></div>
                            <div class="col-span-2"><span class="text-gray-500 block">Scheme Name</span><span class="font-bold text-gray-900">{{ scheme.scheme_name }}</span></div>
                            
                            <div class="col-span-2 border-t mt-2 pt-4"></div>
                            
                            <div><span class="text-gray-500 block">Requested Amount</span><span class="font-mono font-bold text-blue-700 text-lg">₹{{ summary.requested_amount_cr }} Cr</span></div>
                            <div><span class="text-gray-500 block">Instalment No.</span><span class="font-bold text-gray-900">{{ payment_request.instalment_number || 'N/A' }}</span></div>
                            <div class="col-span-2"><span class="text-gray-500 block">Bank Details</span><span class="font-medium text-gray-900 bg-gray-50 p-2 rounded block mt-1 border">{{ payment_request.bank_details || 'N/A' }}</span></div>
                        </div>
                    </div>

                    <!-- Central Allocation, Scrutiny & Sanction Tracking Card -->
                    <div class="bg-gradient-to-br from-white to-slate-50/70 rounded-xl shadow-sm border border-blue-200 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-900 to-indigo-900 px-6 py-4 text-white flex justify-between items-center flex-wrap gap-2">
                            <div class="flex items-center gap-2.5">
                                <span class="p-1.5 bg-blue-800 rounded-lg text-sm">🏛️</span>
                                <div>
                                    <h3 class="font-bold text-sm tracking-wide uppercase">
                                        Central Allocation &amp; Sanction Audit (3-Tier Multi-Stakeholder)
                                    </h3>
                                    <p class="text-[11px] text-blue-200">
                                        State Claimed vs. Brahmaputra Board Recommended vs. Ministry Sanctioned Disbursal
                                    </p>
                                </div>
                            </div>
                            <span 
                                class="px-2.5 py-1 rounded-md text-xs font-bold"
                                :class="payment_request.status === 'APPROVED' ? 'bg-emerald-500 text-white' : 'bg-blue-800 text-blue-200'"
                            >
                                {{ payment_request.status === 'APPROVED' ? 'Sanction Released' : 'In Workflow Scrutiny' }}
                            </span>
                        </div>

                        <div class="p-6 space-y-5">
                            <!-- 3-Way Metrics Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <!-- Stage 1: State Claimed -->
                                <div class="p-4 rounded-xl border border-blue-100 bg-blue-50/50">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider">1. State Claimed</span>
                                        <span class="text-[10px] font-semibold text-blue-600 bg-blue-100/80 px-2 py-0.5 rounded">Asking Amount</span>
                                    </div>
                                    <div class="text-xl font-black text-blue-950 font-mono">
                                        ₹{{ payment_request.requested_amount_cr || '0.00' }} <span class="text-xs font-sans text-blue-700 font-semibold">Cr</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Submitted by {{ payment_request.state || 'State' }} WRD
                                    </p>
                                </div>

                                <!-- Stage 2: BB Recommended -->
                                <div class="p-4 rounded-xl border border-indigo-100 bg-indigo-50/50">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider">2. BB Scrutiny</span>
                                        <span 
                                            class="text-[10px] font-semibold px-2 py-0.5 rounded"
                                            :class="payment_request.bb_recommended_amount_cr || bb_monitoring_report?.report?.bb_recommended_amount_cr ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500'"
                                        >
                                            Recommended
                                        </span>
                                    </div>
                                    <div class="text-xl font-black text-indigo-950 font-mono">
                                        <span v-if="payment_request.bb_recommended_amount_cr || bb_monitoring_report?.report?.bb_recommended_amount_cr">
                                            ₹{{ payment_request.bb_recommended_amount_cr || bb_monitoring_report?.report?.bb_recommended_amount_cr }} <span class="text-xs font-sans text-indigo-700 font-semibold">Cr</span>
                                        </span>
                                        <span v-else class="text-sm font-bold text-slate-400 font-sans italic">
                                            Pending Inspection
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Verified by Brahmaputra Board field engineers
                                    </p>
                                </div>

                                <!-- Stage 3: MoJS Sanctioned -->
                                <div 
                                    class="p-4 rounded-xl border"
                                    :class="payment_request.status === 'APPROVED' ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-200 bg-slate-50/60'"
                                >
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider" :class="payment_request.status === 'APPROVED' ? 'text-emerald-800' : 'text-slate-600'">
                                            3. Centre Sanctioned
                                        </span>
                                        <span 
                                            class="text-[10px] font-semibold px-2 py-0.5 rounded"
                                            :class="payment_request.status === 'APPROVED' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'"
                                        >
                                            {{ payment_request.status === 'APPROVED' ? 'Actual Disbursal' : 'Pending' }}
                                        </span>
                                    </div>
                                    <div class="text-xl font-black font-mono" :class="payment_request.status === 'APPROVED' ? 'text-emerald-950' : 'text-slate-400'">
                                        <span v-if="payment_request.status === 'APPROVED'">
                                            ₹{{ payment_request.approved_amount_cr || payment_request.requested_amount_cr }} <span class="text-xs font-sans text-emerald-700 font-semibold">Cr</span>
                                        </span>
                                        <span v-else class="text-sm font-bold text-slate-400 font-sans italic">
                                            Awaiting MoJS Order
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Final allocation by Ministry of Jal Shakti
                                    </p>
                                </div>
                            </div>

                            <!-- Curtailment / Allocation Deduction Alert Banner -->
                            <div 
                                v-if="(payment_request.deduction_amount_cr && payment_request.deduction_amount_cr > 0) || (payment_request.status === 'APPROVED' && payment_request.approved_amount_cr && payment_request.approved_amount_cr < payment_request.requested_amount_cr)" 
                                class="p-4 bg-amber-50/90 border-2 border-amber-300 rounded-xl space-y-2 text-xs"
                            >
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="p-1 bg-amber-200 rounded-md text-amber-900 text-sm font-black">⚠️</span>
                                        <span class="font-black text-amber-950 text-sm">
                                            Central Curtailment Recorded: -₹{{ payment_request.deduction_amount_cr || (payment_request.requested_amount_cr - payment_request.approved_amount_cr).toFixed(2) }} Cr
                                        </span>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-200/90 text-amber-900 border border-amber-300">
                                        Adjusted Release
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-amber-900 pt-1">
                                    <div>
                                        <span class="font-bold text-amber-950 block">Curtailment / Deduction Grounds:</span>
                                        <span class="text-slate-700 font-medium">{{ payment_request.curtailment_reason || 'SNA Unspent Balance Deduction / Field Shortfall' }}</span>
                                    </div>
                                    <div>
                                        <span class="font-bold text-amber-950 block">Sanction Remarks / Directives:</span>
                                        <span class="text-slate-700 font-medium">{{ payment_request.mojs_remarks || 'Deduction applied as per scheme guidelines.' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Sanction Order Details & Official PDF -->
                            <div 
                                v-if="payment_request.sanction_order_no || payment_request.sanction_order_doc_path" 
                                class="p-4 bg-emerald-50/80 border border-emerald-200 rounded-xl flex items-center justify-between flex-wrap gap-3 text-xs"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="text-xl p-2 bg-emerald-100 rounded-lg">📜</span>
                                    <div>
                                        <div class="font-black text-emerald-950">
                                            Central Sanction Order: {{ payment_request.sanction_order_no || 'Official MoJS Sanction' }}
                                        </div>
                                        <div class="text-[11px] text-emerald-800">
                                            Sanction Date: {{ payment_request.sanction_order_date || 'Approved Date Recorded' }}
                                        </div>
                                    </div>
                                </div>
                                <a 
                                    v-if="payment_request.sanction_order_doc_path" 
                                    :href="payment_request.sanction_order_doc_path" 
                                    target="_blank" 
                                    class="px-3.5 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-lg shadow-2xs transition flex items-center gap-1.5 text-xs"
                                >
                                    <span>📄 View Official Sanction Letter (PDF)</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- 2. FMBAP Financial Tracking Breakdown Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-indigo-200 overflow-hidden">
                        <div class="bg-indigo-50/80 px-6 py-4 border-b border-indigo-200 flex justify-between items-center">
                            <h3 class="font-bold text-indigo-950 uppercase tracking-wider text-sm flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                2. FMBAP Baseline Project Financial Tracking
                            </h3>
                            <span class="text-xs font-bold text-indigo-900 bg-indigo-100 border border-indigo-300 px-3 py-1 rounded-md">
                                Funding Pattern: {{ fmbap_project?.funding_pattern || (scheme?.central_share_pct ? `${scheme.central_share_pct}/${scheme.state_share_pct}` : '70/30') }}
                            </span>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
                                <div class="bg-gray-50 p-3 rounded-lg border">
                                    <span class="text-[11px] font-bold text-gray-500 uppercase block">Est. Cost / IMC</span>
                                    <span class="text-base font-extrabold text-gray-900">₹{{ fmbap_project?.estimated_cost_cr || scheme?.sanctioned_amount_cr || '0.00' }} Cr</span>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg border">
                                    <span class="text-[11px] font-bold text-gray-500 uppercase block">Executed Amount</span>
                                    <span class="text-base font-extrabold text-blue-900">₹{{ fmbap_project?.executed_amount_cr || '0.00' }} Cr</span>
                                </div>
                                <div class="bg-indigo-50/60 p-3 rounded-lg border border-indigo-200">
                                    <span class="text-[11px] font-bold text-indigo-800 uppercase block">Funding Pattern</span>
                                    <span class="text-base font-extrabold text-indigo-950">{{ fmbap_project?.funding_pattern || (scheme?.central_share_pct ? `${scheme.central_share_pct}/${scheme.state_share_pct}` : '70/30') }}</span>
                                </div>
                                <div class="bg-blue-50/50 p-3 rounded-lg border border-blue-100">
                                    <span class="text-[11px] font-bold text-blue-700 uppercase block">Central Share</span>
                                    <span class="text-sm font-bold text-blue-950">₹{{ fmbap_project?.central_share_cr || (scheme?.sanctioned_amount_cr ? (scheme.sanctioned_amount_cr * (scheme.central_share_pct || 70) / 100).toFixed(2) : '0.00') }} Cr</span>
                                </div>
                                <div class="bg-purple-50/50 p-3 rounded-lg border border-purple-100">
                                    <span class="text-[11px] font-bold text-purple-700 uppercase block">State Share</span>
                                    <span class="text-sm font-bold text-purple-950">₹{{ fmbap_project?.state_share_cr || (scheme?.sanctioned_amount_cr ? (scheme.sanctioned_amount_cr * (scheme.state_share_pct || 30) / 100).toFixed(2) : '0.00') }} Cr</span>
                                </div>

                                <div class="bg-emerald-50/50 p-3 rounded-lg border border-emerald-100">
                                    <span class="text-[11px] font-bold text-emerald-700 uppercase block">Released Central Share</span>
                                    <span class="text-sm font-bold text-emerald-950">₹{{ fmbap_project?.released_central_share_cr || '0.00' }} Cr</span>
                                </div>
                                <div class="bg-teal-50/50 p-3 rounded-lg border border-teal-100">
                                    <span class="text-[11px] font-bold text-teal-700 uppercase block">Released State Share</span>
                                    <span class="text-sm font-bold text-teal-950">₹{{ fmbap_project?.released_state_share_cr || '0.00' }} Cr</span>
                                </div>
                                <div class="bg-amber-50/50 p-3 rounded-lg border border-amber-100">
                                    <span class="text-[11px] font-bold text-amber-700 uppercase block">Balance Central Share</span>
                                    <span class="text-sm font-bold text-amber-950">₹{{ fmbap_project?.balance_central_share_cr || '0.00' }} Cr</span>
                                </div>
                                <div class="bg-orange-50/50 p-3 rounded-lg border border-orange-100">
                                    <span class="text-[11px] font-bold text-orange-700 uppercase block">Balance State Share</span>
                                    <span class="text-sm font-bold text-orange-950">₹{{ fmbap_project?.balance_state_share_cr || '0.00' }} Cr</span>
                                </div>
                            </div>

                            <!-- Remarks Log across all stages -->
                            <div class="border-t pt-3 space-y-2 text-xs">
                                <div class="bg-blue-50/40 p-3 rounded-lg border border-blue-100">
                                    <span class="font-bold text-blue-900 uppercase block">State Nodal Agency Remarks:</span>
                                    <p class="text-gray-800 mt-1 font-medium">{{ payment_request.state_remarks || fmbap_project?.remarks || 'None provided' }}</p>
                                </div>
                                <div class="bg-indigo-50/40 p-3 rounded-lg border border-indigo-100">
                                    <span class="font-bold text-indigo-900 uppercase block">Brahmaputra Board Inspection Remarks:</span>
                                    <p class="text-gray-800 mt-1 font-medium">{{ payment_request.bb_remarks || bb_monitoring_report?.report?.site_description || 'Pending / None' }}</p>
                                </div>
                                <div class="bg-emerald-50/40 p-3 rounded-lg border border-emerald-100">
                                    <span class="font-bold text-emerald-900 uppercase block">MoJS Remarks:</span>
                                    <p class="text-gray-800 mt-1 font-medium">{{ payment_request.mojs_remarks || fmbap_project?.mojs_remarks || 'Awaiting MoJS Review' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Comparison -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                            <h3 class="font-bold text-gray-800">2. Progress Verification</h3>
                        </div>
                        <div class="p-6">
                            <table class="w-full text-sm text-left border rounded-lg overflow-hidden">
                                <thead class="bg-gray-50 text-gray-700">
                                    <tr>
                                        <th class="p-3 border-b">Metric</th>
                                        <th class="p-3 border-b border-l text-blue-700 bg-blue-50/50">State Reported</th>
                                        <th class="p-3 border-b border-l text-indigo-700 bg-indigo-50/50">BB Verified</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="p-3 border-b font-medium">Physical Progress</td>
                                        <td class="p-3 border-b border-l text-blue-900 bg-blue-50/20 font-bold">{{ summary.physical_progress_pct }}%</td>
                                        <td class="p-3 border-b border-l text-indigo-900 bg-indigo-50/20 font-bold">
                                            <span v-if="summary.bb_physical_pct !== null">{{ summary.bb_physical_pct }}%</span>
                                            <span v-else class="text-gray-400 font-normal italic">Pending</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="p-3 font-medium">Financial Progress</td>
                                        <td class="p-3 border-l text-blue-900 bg-blue-50/20 font-bold">{{ summary.financial_progress_pct }}%</td>
                                        <td class="p-3 border-l text-indigo-900 bg-indigo-50/20 font-bold">
                                            <span v-if="summary.bb_financial_pct !== null">{{ summary.bb_financial_pct }}%</span>
                                            <span v-else class="text-gray-400 font-normal italic">Pending</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="mt-4">
                                <span class="text-gray-500 block text-sm">State Narrative Report:</span>
                                <p class="text-sm text-gray-900 mt-1 bg-gray-50 p-3 rounded">{{ payment_request.narrative_progress_report || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- BB Field Evidence -->
                    <div v-if="bb_monitoring_report" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="font-bold text-gray-800">3. BB Field Evidence</h3>
                            <a v-if="bb_monitoring_report.official_pdf" :href="bb_monitoring_report.official_pdf" target="_blank" class="text-xs font-semibold text-indigo-600 underline">View Official BB Report PDF</a>
                        </div>
                        <div class="p-6">
                            <div class="mb-4 text-sm">
                                <span class="text-gray-500 block">Inspection Date & Site Description:</span>
                                <p class="text-gray-900 font-medium">
                                    {{ bb_monitoring_report.report?.inspection_date || 'N/A' }} — 
                                    {{ bb_monitoring_report.report?.site_description || 'N/A' }}
                                </p>
                            </div>
                            <GeoTaggedGallery :files="bb_monitoring_report.geo_tagged_files" />
                        </div>
                            <!-- Complete Official Project & State Documents -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                            <h3 class="font-bold text-gray-800">4. Official Project & Supporting Documents</h3>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- State Govt Doc -->
                            <div v-if="fmbap_project?.state_govt_doc_path" class="border rounded p-4 flex items-center justify-between hover:bg-gray-50 transition">
                                <div class="flex items-center gap-3">
                                    <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">State Govt Approval Doc</p>
                                        <p v-if="fmbap_project?.state_govt_submission_date" class="text-xs text-gray-500">Sub: {{ fmbap_project.state_govt_submission_date }}</p>
                                    </div>
                                </div>
                                <a :href="fmbap_project.state_govt_doc_path" target="_blank" class="text-blue-600 font-semibold text-sm hover:underline">View PDF</a>
                            </div>

                            <!-- BB Official Report Doc -->
                            <div v-if="fmbap_project?.brahmaputra_board_doc_path || bb_monitoring_report?.official_pdf" class="border rounded p-4 flex items-center justify-between hover:bg-gray-50 transition">
                                <div class="flex items-center gap-3">
                                    <svg class="w-8 h-8 text-indigo-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">BB Inspection Report PDF</p>
                                    </div>
                                </div>
                                <a :href="fmbap_project?.brahmaputra_board_doc_path || bb_monitoring_report?.official_pdf" target="_blank" class="text-indigo-600 font-semibold text-sm hover:underline">View PDF</a>
                            </div>

                            <!-- MoJS Official Doc -->
                            <div v-if="fmbap_project?.mojs_doc_path" class="border rounded p-4 flex items-center justify-between hover:bg-gray-50 transition">
                                <div class="flex items-center gap-3">
                                    <svg class="w-8 h-8 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">FM Section MoJS Doc</p>
                                    </div>
                                </div>
                                <a :href="fmbap_project.mojs_doc_path" target="_blank" class="text-emerald-600 font-semibold text-sm hover:underline">View PDF</a>
                            </div>

                            <!-- Utilization Certificate (UC) -->
                            <div v-if="state_documents.utilization_certificate" class="border rounded p-4 flex items-center justify-between hover:bg-gray-50 transition">
                                <div class="flex items-center gap-3">
                                    <svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">Utilization Cert (UC)</p>
                                    </div>
                                </div>
                                <a :href="state_documents.utilization_certificate" target="_blank" class="text-blue-600 font-semibold text-sm hover:underline">View PDF</a>
                            </div>

                            <!-- State Progress Report PDF -->
                            <div v-if="state_documents.progress_report" class="border rounded p-4 flex items-center justify-between hover:bg-gray-50 transition">
                                <div class="flex items-center gap-3">
                                    <svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">State Progress Report PDF</p>
                                    </div>
                                </div>
                                <a :href="state_documents.progress_report" target="_blank" class="text-blue-600 font-semibold text-sm hover:underline">View PDF</a>
                            </div>

                            <!-- Expenditure Vouchers -->
                            <div v-if="state_documents.vouchers?.length" class="border rounded p-4 flex items-center justify-between hover:bg-gray-50 transition md:col-span-2">
                                <div class="flex items-center gap-3">
                                    <svg class="w-8 h-8 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z"></path><path d="M3 8a2 2 0 012-2v10h8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"></path></svg>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">Expenditure Vouchers</p>
                                        <p class="text-xs text-gray-500">{{ state_documents.vouchers.length }} files uploaded</p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <a v-for="(v, i) in state_documents.vouchers" :key="i" :href="v" target="_blank" class="text-blue-600 font-semibold text-xs border px-2.5 py-1 rounded hover:bg-blue-50">Voucher {{ i+1 }}</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historical Submission Revisions Card -->
                    <div v-if="revisions?.length > 0" class="bg-white rounded-sm shadow-xs border border-slate-200 overflow-hidden">
                        <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex justify-between items-center">
                            <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wider flex items-center gap-2">
                                <span>📜 Submission Revisions & Discussion History</span>
                                <span class="text-xs font-bold text-slate-600 bg-slate-200 px-2 py-0.5 rounded-xs">{{ revisions.length }} Versions</span>
                            </h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <div v-for="rev in revisions" :key="rev.id" class="border border-slate-200 rounded-sm p-3 bg-slate-50/50 space-y-2 text-xs">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-900 bg-slate-200 border border-slate-300 px-2 py-0.5 rounded-xs">
                                            {{ rev.version_label }}
                                        </span>
                                        <span class="text-slate-500 font-semibold">{{ rev.author_name }} ({{ rev.author_role }})</span>
                                    </div>
                                    <span class="text-[11px] text-slate-500">{{ new Date(rev.created_at).toLocaleString() }}</span>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-white p-2.5 rounded-sm border border-slate-200 text-xs">
                                    <div><span class="text-slate-400 block text-[10px] uppercase font-bold">Requested Claim</span><span class="font-bold font-mono text-slate-900">₹{{ rev.requested_amount_cr || '0.00' }} Cr</span></div>
                                    <div><span class="text-slate-400 block text-[10px] uppercase font-bold">Instalment</span><span class="font-bold text-slate-800">No. {{ rev.instalment_number || 'N/A' }}</span></div>
                                    <div><span class="text-slate-400 block text-[10px] uppercase font-bold">Physical Progress</span><span class="font-bold text-slate-800">{{ rev.physical_progress_pct || 0 }}%</span></div>
                                    <div><span class="text-slate-400 block text-[10px] uppercase font-bold">Financial Progress</span><span class="font-bold text-slate-800">{{ rev.financial_progress_pct || 0 }}%</span></div>
                                </div>

                                <!-- Remarks -->
                                <div v-if="rev.state_remarks" class="bg-white p-2 rounded-xs border border-slate-200">
                                    <span class="font-bold text-slate-700 block">State Remarks:</span> "{{ rev.state_remarks }}"
                                </div>
                                <div v-if="rev.bb_remarks" class="bg-amber-50 p-2 rounded-xs border border-amber-200 text-amber-900">
                                    <span class="font-bold text-amber-800 block">BB Reviewer Remarks:</span> "{{ rev.bb_remarks }}"
                                </div>
                                <div v-if="rev.mojs_remarks" class="bg-indigo-50 p-2 rounded-xs border border-indigo-200 text-indigo-900">
                                    <span class="font-bold text-indigo-800 block">MoJS Reviewer Remarks:</span> "{{ rev.mojs_remarks }}"
                                </div>

                                <!-- Documents -->
                                <div v-if="rev.utilization_certificate_path || rev.state_govt_doc_path || rev.progress_report_doc_path" class="pt-2 border-t border-slate-200 flex flex-wrap gap-2 text-[11px]">
                                    <a v-if="rev.utilization_certificate_path" :href="rev.utilization_certificate_path" target="_blank" class="text-blue-700 font-bold underline bg-blue-50 px-2 py-0.5 rounded-xs border border-blue-200">
                                        📄 View Utilization Certificate ({{ rev.version_label }})
                                    </a>
                                    <a v-if="rev.state_govt_doc_path" :href="rev.state_govt_doc_path" target="_blank" class="text-emerald-700 font-bold underline bg-emerald-50 px-2 py-0.5 rounded-xs border border-emerald-200">
                                        📜 View State Sanction Doc
                                    </a>
                                    <a v-if="rev.progress_report_doc_path" :href="rev.progress_report_doc_path" target="_blank" class="text-purple-700 font-bold underline bg-purple-50 px-2 py-0.5 rounded-xs border border-purple-200">
                                        📊 View Progress Report PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

                <!-- Right Column: Audit & Actions -->
                <div class="space-y-6 lg:sticky lg:top-6 lg:self-start lg:max-h-[90vh] overflow-y-auto pr-2 pb-4">
                    
                    <!-- Action Panel -->
                    <div v-if="payment_request.status !== 'APPROVED' && payment_request.status !== 'REJECTED'" class="bg-white rounded-xl shadow border border-blue-200 overflow-hidden">
                        <div class="bg-blue-50 px-6 py-4 border-b border-blue-200">
                            <h3 class="font-bold text-blue-900">Review Actions</h3>
                        </div>
                        <div class="p-6 flex flex-col gap-3">
                            
                            <!-- BB Actions -->
                            <template v-if="isBB && (payment_request.status === 'SUBMITTED_TO_BB' || payment_request.status === 'BB_MONITORING_PENDING')">
                                <Link :href="route('monitoring-requests.report.create', payment_request.id)" class="w-full bg-indigo-600 text-white font-bold py-2.5 rounded hover:bg-indigo-700 flex items-center justify-center gap-2 text-center shadow">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Review & Fill BB Monitoring Report
                                </Link>
                                <button @click="openDecisionModal('NEEDS_CORRECTION')" class="w-full bg-amber-100 text-amber-800 border border-amber-300 font-bold py-2.5 rounded hover:bg-amber-200 flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                    Send to State for Correction
                                </button>
                                <button @click="openDecisionModal('REJECTED')" class="w-full bg-red-100 text-red-700 border border-red-300 font-bold py-2.5 rounded hover:bg-red-200 flex items-center justify-center gap-2">
                                    Reject Request
                                </button>
                            </template>
                            
                            <!-- MoJS Actions -->
                            <template v-if="isMoJS && payment_request.status === 'FORWARDED_TO_MOJS'">
                                <button @click="openDecisionModal('APPROVED')" class="w-full bg-emerald-600 text-white font-bold py-2.5 rounded hover:bg-emerald-700 flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Approve & Release Payment
                                </button>
                                <button @click="openDecisionModal('NEEDS_CORRECTION')" class="w-full bg-amber-100 text-amber-800 border border-amber-300 font-bold py-2.5 rounded hover:bg-amber-200 flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                    Send back for Correction
                                </button>
                                <button @click="openDecisionModal('REJECTED')" class="w-full bg-red-100 text-red-700 border border-red-300 font-bold py-2.5 rounded hover:bg-red-200 flex items-center justify-center gap-2">
                                    Reject Request
                                </button>
                            </template>

                            <!-- State Action when sent back for correction -->
                            <template v-if="canEditForCorrection">
                                <div class="p-4 bg-amber-50 rounded-xl border border-amber-300 space-y-3">
                                    <div class="font-bold text-amber-950 text-xs flex items-center gap-1.5">
                                        <span class="text-base">✏️</span> Action Required from State
                                    </div>
                                    <p class="text-[11px] text-amber-800 leading-relaxed">
                                        This claim was sent back for correction. Click below to open the submission wizard, modify financial or physical metrics, re-upload documents, and resubmit to the Board.
                                    </p>
                                    <Link
                                        :href="route('fund-release.edit', payment_request.id)"
                                        class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 rounded-lg flex items-center justify-center gap-2 text-center text-xs shadow transition cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        <span>Edit &amp; Resubmit Request</span>
                                    </Link>
                                </div>
                            </template>

                            <div v-else-if="!isBB && !isMoJS" class="text-sm text-gray-500 italic text-center">
                                Awaiting action from higher authority.
                            </div>
                        </div>
                    </div>

                    <!-- Audit Trail Component -->
                    <AuditTimeline :key="payment_request.status" module="fund-release" :recordId="payment_request.id" />
                </div>
            </div>
        </div>

        <!-- Decision Modal -->
        <div v-if="showDecisionModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[92vh] overflow-y-auto p-6 sm:p-7 space-y-5 border border-slate-200">
                
                <!-- Modal Header -->
                <div class="flex items-start justify-between border-b pb-4">
                    <div class="flex items-center gap-3">
                        <span 
                            class="p-2.5 rounded-xl text-xl font-bold"
                            :class="decisionType === 'APPROVED' ? 'bg-emerald-100 text-emerald-800' : (decisionType === 'REJECTED' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800')"
                        >
                            {{ decisionType === 'APPROVED' ? '🏛️' : (decisionType === 'REJECTED' ? '🚫' : '⚠️') }}
                        </span>
                        <div>
                            <h3 class="text-lg font-black" :class="decisionType === 'REJECTED' ? 'text-red-900' : 'text-slate-900'">
                                {{ decisionType === 'APPROVED' ? 'Ministry Sanction & Fund Disbursal Order' : (decisionType === 'REJECTED' ? 'Reject Payment Claim' : 'Return Claim to State for Correction') }}
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ decisionType === 'APPROVED' ? 'Record central sanctioned amount, document deductions, and issue sanction order.' : 'Provide official directives and rationale for this decision.' }}
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="showDecisionModal = false" class="text-slate-400 hover:text-slate-600 font-black text-xl p-1">
                        ✕
                    </button>
                </div>
                
                <form @submit.prevent="submitDecision" class="space-y-5">
                    
                    <!-- MoJS APPROVED Fields -->
                    <template v-if="decisionType === 'APPROVED'">
                        <!-- Comparison Reference Box -->
                        <div class="grid grid-cols-2 gap-3 bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs">
                            <div>
                                <span class="text-slate-500 block font-semibold uppercase text-[10px]">State Claimed Amount</span>
                                <span class="text-base font-black text-blue-900 font-mono">₹{{ payment_request.requested_amount_cr || '0.00' }} Cr</span>
                            </div>
                            <div>
                                <span class="text-slate-500 block font-semibold uppercase text-[10px]">BB Recommended Release</span>
                                <span class="text-base font-black text-indigo-900 font-mono">
                                    ₹{{ payment_request.bb_recommended_amount_cr || bb_monitoring_report?.report?.bb_recommended_amount_cr || payment_request.requested_amount_cr }} Cr
                                </span>
                            </div>
                        </div>

                        <!-- Sanctioned Amount Input -->
                        <div class="bg-gradient-to-br from-emerald-50/60 to-blue-50/60 p-4 rounded-xl border-2 border-emerald-300 space-y-3">
                            <label class="block text-sm font-black text-emerald-950">
                                Sanctioned / Approved Central Release (₹ in Cr) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    min="0"
                                    :max="payment_request.requested_amount_cr || 9999"
                                    v-model="decisionForm.approved_amount_cr" 
                                    class="w-full rounded-xl border-emerald-300 pr-24 text-lg font-black text-emerald-950 focus:ring-emerald-500 focus:border-emerald-500"
                                    required
                                    placeholder="e.g. 3.25"
                                >
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-xs font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded">₹ Crore</span>
                                </div>
                            </div>

                            <!-- Live Curtailment / Deduction Detector -->
                            <div v-if="parseFloat(mojsCurtailmentDiff) > 0" class="p-3 bg-amber-100 border border-amber-300 rounded-xl space-y-2">
                                <div class="flex items-center justify-between text-xs font-bold text-amber-950">
                                    <span>⚠️ Curtailment / Allocation Deduction:</span>
                                    <span class="font-mono text-sm font-black text-amber-900">-₹{{ mojsCurtailmentDiff }} Cr</span>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-amber-950 mb-1">Curtailment / Deduction Reason <span class="text-red-600">*</span></label>
                                    <select 
                                        v-model="decisionForm.curtailment_reason" 
                                        class="w-full text-xs rounded-lg border-amber-300 bg-white font-medium text-slate-800 focus:ring-amber-500 focus:border-amber-500"
                                        required
                                    >
                                        <option value="Unspent SNA Balance / Accrued Interest (GFR 230(8))">Unspent SNA Balance / Accrued Interest (GFR 230(8))</option>
                                        <option value="Field Inspection / Physical Progress Shortfall">Field Inspection / Physical Progress Shortfall</option>
                                        <option value="Inadmissible / Non-eligible Expenditure Disallowed">Inadmissible / Non-eligible Expenditure Disallowed</option>
                                        <option value="Annual Budget Tranche Ceiling / Allocation Cap">Annual Budget Tranche Ceiling / Allocation Cap</option>
                                        <option value="State Matching Share Proportion Pending">State Matching Share Proportion Pending</option>
                                        <option value="Other Technical / Financial Reason">Other Technical / Financial Reason</option>
                                    </select>
                                </div>
                            </div>
                            <div v-else-if="decisionForm.approved_amount_cr" class="p-2.5 bg-emerald-100/70 border border-emerald-300 rounded-lg text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                                <span>✅</span>
                                <span>Sanctioning 100% of claimed amount without deduction.</span>
                            </div>
                        </div>

                        <!-- Sanction Order Details -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Central Sanction Order Number</label>
                                <input 
                                    type="text" 
                                    v-model="decisionForm.sanction_order_no" 
                                    class="w-full rounded-lg border-slate-300 text-xs font-medium"
                                    placeholder="e.g. MoJS/FMBAP/AS/089-Sanction"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Sanction Order Date</label>
                                <input 
                                    type="date" 
                                    v-model="decisionForm.sanction_order_date" 
                                    class="w-full rounded-lg border-slate-300 text-xs font-medium"
                                >
                            </div>
                        </div>

                        <!-- Sanction Order Letter PDF -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Upload Official Central Sanction Letter (PDF)
                            </label>
                            <input 
                                type="file" 
                                @change="e => decisionForm.sanction_order_doc = e.target.files[0]" 
                                accept=".pdf" 
                                class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-300 rounded-xl p-1"
                            >
                        </div>
                    </template>

                    <!-- Remarks / Justification (Applies to all actions) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            {{ decisionType === 'APPROVED' ? 'Sanction Conditions & Remarks' : (decisionType === 'NEEDS_CORRECTION' ? 'Correction Directives for State WRD' : 'Rejection Rationale') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            v-model="decisionForm.remarks" 
                            rows="3" 
                            class="w-full border-slate-300 rounded-xl text-xs text-slate-900 focus:ring-blue-500 focus:border-blue-500" 
                            required 
                            :placeholder="decisionType === 'APPROVED' ? 'Enter sanction conditions, IFD concurrence reference, or specific release instructions...' : 'Enter directives for State officials...'"
                        ></textarea>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-3">
                        <button 
                            type="button" 
                            @click="showDecisionModal = false" 
                            class="px-4 py-2 border border-slate-300 rounded-xl font-semibold text-xs text-slate-700 hover:bg-slate-50 transition"
                            :disabled="decisionForm.processing"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-5 py-2.5 text-white rounded-xl font-bold text-xs shadow-md transition flex items-center gap-1.5"
                            :class="decisionType === 'REJECTED' ? 'bg-red-600 hover:bg-red-700' : (decisionType === 'APPROVED' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-amber-600 hover:bg-amber-700')"
                            :disabled="decisionForm.processing"
                        >
                            <span v-if="decisionForm.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <span v-if="decisionType === 'APPROVED'">
                                Confirm Sanction &amp; Disburse ₹{{ decisionForm.approved_amount_cr || payment_request.requested_amount_cr }} Cr →
                            </span>
                            <span v-else-if="decisionType === 'NEEDS_CORRECTION'">
                                Return to State for Correction →
                            </span>
                            <span v-else>
                                Confirm Rejection →
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
