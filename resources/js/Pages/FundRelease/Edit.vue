<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import WizardStepIndicator from '@/Components/WizardStepIndicator.vue';
import SchemeSearchDropdown from '@/Components/SchemeSearchDropdown.vue';
import Gfr12aModal from '@/Components/Gfr12aModal.vue';
import MojsSanctionLedgerCard from '@/Components/MojsSanctionLedgerCard.vue';

const props = defineProps({
    paymentRequest: {
        type: Object,
        required: true,
    },
    userRole: String,
});

const currentStep = ref(1);
const steps = ['Scheme Details', 'Financial Request', 'Progress Metrics', 'Supporting Docs'];
const isSubmitting = ref(false);
const errorMsg = ref('');
const isGfrModalOpen = ref(false);
const attachedGfrPath = ref('');

const onGfrAttached = (path) => {
    attachedGfrPath.value = path;
    if (props.paymentRequest) {
        props.paymentRequest.utilization_certificate_path = path;
    }
};

const form = ref({});

// Dynamic State Matching Share required for this release
const stateMatchingShareCr = computed(() => {
    const req = parseFloat(form.value?.requested_amount_cr) || 0;
    const parts = (form.value?.funding_pattern || '90/10').split('/').map(Number);
    const cPct = parts[0] || 90;
    const sPct = parts[1] || 10;
    if (req <= 0 || cPct <= 0) return '0.00';
    return (req * (sPct / cPct)).toFixed(2);
});

// Calculate Percentage of Central Share drawn
const centralUtilisationPct = computed(() => {
    const totalCentral = parseFloat(form.value?.central_share_cr) || 0;
    const released = parseFloat(form.value?.released_central_share_cr) || 0;
    if (totalCentral <= 0) return 0;
    return Math.min(100, Math.round((released / totalCentral) * 100));
});

// Warning if requested claim exceeds eligible remaining balance
const isOverclaimWarning = computed(() => {
    const req = parseFloat(form.value?.requested_amount_cr) || 0;
    const bal = parseFloat(form.value?.balance_central_share_cr) || 0;
    return req > 0 && bal > 0 && req > bal;
});

const onSchemeSelected = (scheme) => {
    form.value.scheme_id = scheme.id;
    form.value.scheme_obj = scheme;
    
    // Pre-fill FMBAP Project fields if linked project exists
    const proj = scheme.fmbap_project || scheme.fmbapProject;
    const cPct = scheme.central_share_pct || 90;
    const sPct = scheme.state_share_pct || (100 - cPct);
    form.value.funding_pattern = `${cPct}/${sPct}`;

    let costCr = 0;
    if (scheme.sanctioned_amount_cr && parseFloat(scheme.sanctioned_amount_cr) > 0) {
        costCr = parseFloat(scheme.sanctioned_amount_cr);
    } else if (scheme.estimated_cost_lakh && parseFloat(scheme.estimated_cost_lakh) > 0) {
        costCr = parseFloat(scheme.estimated_cost_lakh) / 100;
    }
    form.value.estimated_cost_cr = costCr > 0 ? costCr.toFixed(2) : '';

    computeCalculatedShares();
};

onMounted(() => {
    const scheme = props.paymentRequest.scheme;
    const proj = scheme?.fmbap_project || scheme?.fmbapProject;
    const cPct = scheme?.central_share_pct || 90;
    const sPct = scheme?.state_share_pct || (100 - cPct);

    let costCr = scheme?.sanctioned_amount_cr || proj?.estimated_cost_cr || '';
    if (!costCr && scheme?.estimated_cost_lakh) {
        costCr = (parseFloat(scheme.estimated_cost_lakh) / 100).toFixed(2);
    }

    const relCentral = scheme?.approved_claims_release_cr 
        ? parseFloat(scheme.approved_claims_release_cr).toFixed(2)
        : (scheme?.released_central_share_cr || proj?.released_central_share_cr || '0.00');
    const relState = (cPct > 0) ? ((parseFloat(relCentral) * (sPct / cPct)).toFixed(2)) : '0.00';
    const balCentral = scheme?.balance_central_share_cr || proj?.balance_central_share_cr || '';

    form.value = {
        payment_request_id: props.paymentRequest.id,
        // Step 1
        scheme_id: props.paymentRequest.scheme_id,
        scheme_obj: props.paymentRequest.scheme,
        // Step 2
        requested_amount_cr: props.paymentRequest.requested_amount_cr ?? '',
        instalment_number: props.paymentRequest.instalment_number ?? 1,
        bank_details: props.paymentRequest.bank_details ?? '',
        state_remarks: props.paymentRequest.state_remarks || proj?.remarks || '',
        // FMBAP Financial Tracking
        estimated_cost_cr: costCr,
        executed_amount_cr: proj?.executed_amount_cr || '',
        funding_pattern: scheme?.funding_pattern || proj?.funding_pattern || `${cPct}/${sPct}`,
        central_share_cr: scheme?.central_share_entitlement_cr || proj?.central_share_cr || '',
        state_share_cr: scheme?.state_share_entitlement_cr || proj?.state_share_cr || '',
        released_central_share_cr: relCentral,
        released_state_share_cr: relState,
        balance_central_share_cr: balCentral,
        balance_state_share_cr: proj?.balance_state_share_cr || '',
        state_govt_doc: null,
        // Step 3
        physical_progress_pct: props.paymentRequest.physical_progress_pct ?? '',
        physical_progress_description: props.paymentRequest.physical_progress_description ?? '',
        financial_progress_pct: props.paymentRequest.financial_progress_pct ?? '',
        financial_progress_description: props.paymentRequest.financial_progress_description ?? '',
        narrative_progress_report: props.paymentRequest.narrative_progress_report ?? '',
        // Step 4
        utilization_certificate: null,
        vouchers: [],
        progress_report_pdf: null,
    };
    computeCalculatedShares();
});

const computeCalculatedShares = () => {
    const cost = parseFloat(form.value.estimated_cost_cr) || 0;
    const pattern = form.value.funding_pattern || '90/10';
    const parts = pattern.split('/').map(Number);
    const centralPct = parts[0] || 90;
    const statePct = parts[1] || 10;

    if (cost > 0) {
        form.value.central_share_cr = (cost * (centralPct / 100)).toFixed(2);
        form.value.state_share_cr = (cost * (statePct / 100)).toFixed(2);
    } else {
        form.value.central_share_cr = '';
        form.value.state_share_cr = '';
    }

    const relCentral = parseFloat(form.value.released_central_share_cr) || 0;
    const relState = parseFloat(form.value.released_state_share_cr) || 0;

    if (form.value.central_share_cr !== '') {
        const balCentral = Math.max(0, parseFloat(form.value.central_share_cr) - relCentral);
        form.value.balance_central_share_cr = balCentral.toFixed(2);
    } else {
        form.value.balance_central_share_cr = '';
    }

    if (form.value.state_share_cr !== '') {
        const balState = Math.max(0, parseFloat(form.value.state_share_cr) - relState);
        form.value.balance_state_share_cr = balState.toFixed(2);
    } else {
        form.value.balance_state_share_cr = '';
    }
};

const handleFileUpload = (event, field, isMultiple = false) => {
    if (isMultiple) {
        form.value[field] = Array.from(event.target.files);
    } else {
        form.value[field] = event.target.files[0];
    }
};

const saveStep = async (isFinalSubmit = false) => {
    isSubmitting.value = true;
    errorMsg.value = '';

    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('step', currentStep.value);
    
    if (isFinalSubmit) {
        formData.append('is_final_submit', 1);
    }

    // Append data based on step
    if (currentStep.value === 1) {
        formData.append('scheme_id', form.value.scheme_id);
    } else if (currentStep.value === 2) {
        formData.append('requested_amount_cr', form.value.requested_amount_cr);
        formData.append('instalment_number', form.value.instalment_number);
        formData.append('bank_details', form.value.bank_details);
        formData.append('state_remarks', form.value.state_remarks || '');
        formData.append('estimated_cost_cr', form.value.estimated_cost_cr || '');
        formData.append('executed_amount_cr', form.value.executed_amount_cr || '');
        formData.append('funding_pattern', form.value.funding_pattern || '');
        formData.append('central_share_cr', form.value.central_share_cr || '');
        formData.append('state_share_cr', form.value.state_share_cr || '');
        formData.append('released_central_share_cr', form.value.released_central_share_cr || '');
        formData.append('released_state_share_cr', form.value.released_state_share_cr || '');
        formData.append('balance_central_share_cr', form.value.balance_central_share_cr || '');
        formData.append('balance_state_share_cr', form.value.balance_state_share_cr || '');
        if (form.value.state_govt_doc) {
            formData.append('state_govt_doc', form.value.state_govt_doc);
        }
    } else if (currentStep.value === 3) {
        formData.append('physical_progress_pct', form.value.physical_progress_pct);
        formData.append('physical_progress_description', form.value.physical_progress_description || '');
        formData.append('financial_progress_pct', form.value.financial_progress_pct);
        formData.append('financial_progress_description', form.value.financial_progress_description || '');
        formData.append('narrative_progress_report', form.value.narrative_progress_report || '');
    } else if (currentStep.value === 4) {
        if (form.value.utilization_certificate) {
            formData.append('utilization_certificate', form.value.utilization_certificate);
        }
        if (form.value.progress_report_pdf) {
            formData.append('progress_report_pdf', form.value.progress_report_pdf);
        }
        if (form.value.vouchers.length) {
            form.value.vouchers.forEach((v, idx) => {
                formData.append(`vouchers[${idx}]`, v);
            });
        }
    }

    try {
        await axios.post(route('fund-release.update', props.paymentRequest.id), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        if (isFinalSubmit) {
            router.visit(route('fund-release.show', props.paymentRequest.id));
        } else {
            currentStep.value++;
        }
    } catch (error) {
        errorMsg.value = error.response?.data?.message || 'An error occurred while saving.';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Head title="Edit Payment Request" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ paymentRequest.status === 'NEEDS_CORRECTION' ? 'Correct Payment Request' : 'Edit Draft Payment Request' }}
            </h2>
        </template>

        <div class="py-8 mx-auto sm:px-6 lg:px-8 w-full">
            
            <WizardStepIndicator :steps="steps" :currentStep="currentStep" />

            <!-- Correction Alert -->
            <div v-if="paymentRequest.status === 'NEEDS_CORRECTION'" class="mb-6 bg-amber-50 border border-amber-200 p-4 rounded-xl shadow-sm flex gap-4 items-start">
                <div class="mt-1 text-amber-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h3 class="font-bold text-amber-800 text-sm">Sent Back for Correction</h3>
                    <p class="text-sm text-amber-700 mt-1">Please review the remarks from the reviewing authority and update the form accordingly.</p>
                    <div class="mt-3 bg-white p-3 rounded border border-amber-100 text-sm font-medium text-gray-800">
                        "{{ paymentRequest.bb_remarks || paymentRequest.mojs_remarks }}"
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow border border-gray-200 p-8">
                
                <div v-if="errorMsg" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm font-medium">
                    {{ errorMsg }}
                </div>

                <form @submit.prevent="currentStep === 4 ? saveStep(true) : saveStep(false)">
                    
                    <!-- Step 1: Scheme Selection -->
                    <div v-if="currentStep === 1" class="space-y-6 animate-fade-in">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 border-b pb-2 mb-4">Target Scheme</h3>
                            <SchemeSearchDropdown v-model="form.scheme_id" :initialScheme="form.scheme_obj" @scheme-selected="onSchemeSelected" />
                        </div>
                    </div>

                    <!-- Step 2: Financial Details & FMBAP Project Tracking -->
                    <div v-if="currentStep === 2" class="space-y-6 animate-fade-in">
                        <!-- Header Banner -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b pb-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                    <span>Step 2: Financial Request & FMBAP Baseline Audit</span>
                                    <span class="text-xs px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-semibold">Live Calibrated</span>
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">Parameters are pre-loaded from official records. Both ₹ in Crore and ₹ in Lakh are displayed side-by-side.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono font-bold bg-blue-100 text-blue-800 px-3 py-1 rounded-lg border border-blue-200">
                                    {{ form.scheme_obj?.scheme_code || 'FMBAP-SCHEME' }}
                                </span>
                            </div>
                        </div>

                        <!-- 1. BASELINE PROJECT FINANCIAL METRICS (FMBAP STANDARDS) -->
                        <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-200/80 pb-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                                    <h4 class="font-bold text-xs text-slate-800 uppercase tracking-wider">
                                        1. Statutory Sanction & Funding Ratio Baseline
                                    </h4>
                                </div>
                                <span class="text-[11px] text-slate-500">Unit: ₹ in Crore (1 Cr = 100 Lakh)</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <!-- Estimated Cost -->
                                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="text-xs font-bold text-slate-700">Estimated Cost / IMC (₹ Cr)</label>
                                        <span v-if="form.estimated_cost_cr" class="text-[10px] font-semibold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">
                                            ₹{{ (parseFloat(form.estimated_cost_cr) * 100).toFixed(2) }} Lakh
                                        </span>
                                    </div>
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        v-model="form.estimated_cost_cr" 
                                        @input="computeCalculatedShares" 
                                        class="w-full rounded-lg border-slate-300 text-sm font-bold text-slate-900 focus:ring-blue-500 focus:border-blue-500" 
                                        placeholder="e.g. 6.08"
                                    >
                                    <span class="text-[10px] text-slate-400 mt-1 block">Total approved project outlay</span>
                                </div>

                                <!-- Executed Amount -->
                                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="text-xs font-bold text-slate-700">Executed Amount to Date (₹ Cr)</label>
                                        <span v-if="form.executed_amount_cr" class="text-[10px] font-semibold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded">
                                            ₹{{ (parseFloat(form.executed_amount_cr) * 100).toFixed(2) }} Lakh
                                        </span>
                                    </div>
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        v-model="form.executed_amount_cr" 
                                        class="w-full rounded-lg border-slate-300 text-sm font-semibold text-slate-800" 
                                        placeholder="e.g. 5.47"
                                    >
                                    <span class="text-[10px] text-slate-400 mt-1 block">Work physically completed on ground</span>
                                </div>

                                <!-- Funding Pattern -->
                                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Funding Pattern (CS / SS)</label>
                                    <select 
                                        v-model="form.funding_pattern" 
                                        @change="computeCalculatedShares" 
                                        class="w-full rounded-lg border-slate-300 text-sm font-bold text-indigo-900 focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="90/10">90/10</option>
                                        <option value="70/30">70/30</option>
                                        <option value="60/40">60/40</option>
                                        <option value="50/50">50/50</option>
                                        <option value="80/20">80/20</option>
                                        <option value="100/0">100/0</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Statutory Share Entitlement Cards -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 pt-2">
                                <div class="bg-blue-50/80 p-3 rounded-xl border border-blue-200">
                                    <span class="text-[10px] text-blue-700 font-bold uppercase block tracking-wider">Central Share Entitlement</span>
                                    <span class="text-base font-extrabold text-blue-950 block">₹{{ form.central_share_cr || '0.00' }} Cr</span>
                                    <span class="text-[10px] text-blue-600 font-medium">₹{{ (parseFloat(form.central_share_cr || 0) * 100).toFixed(2) }} Lakh</span>
                                </div>

                                <div class="bg-purple-50/80 p-3 rounded-xl border border-purple-200">
                                    <span class="text-[10px] text-purple-700 font-bold uppercase block tracking-wider">State Share Entitlement</span>
                                    <span class="text-base font-extrabold text-purple-950 block">₹{{ form.state_share_cr || '0.00' }} Cr</span>
                                    <span class="text-[10px] text-purple-600 font-medium">₹{{ (parseFloat(form.state_share_cr || 0) * 100).toFixed(2) }} Lakh</span>
                                </div>

                                <div class="bg-white p-3 rounded-xl border border-slate-300">
                                    <label class="block text-[10px] text-slate-600 font-bold uppercase tracking-wider mb-0.5">Central Released to Date</label>
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        v-model="form.released_central_share_cr" 
                                        @input="computeCalculatedShares" 
                                        class="w-full rounded border-slate-300 py-1 text-xs font-bold text-slate-800" 
                                        placeholder="0.00"
                                    >
                                    <span class="text-[10px] text-slate-500 font-medium mt-0.5 block">
                                        = ₹{{ (parseFloat(form.released_central_share_cr || 0) * 100).toFixed(2) }} Lakh
                                    </span>
                                </div>

                                <div class="bg-white p-3 rounded-xl border border-slate-300">
                                    <label class="block text-[10px] text-slate-600 font-bold uppercase tracking-wider mb-0.5">State Released to Date</label>
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        v-model="form.released_state_share_cr" 
                                        @input="computeCalculatedShares" 
                                        class="w-full rounded border-slate-300 py-1 text-xs font-bold text-slate-800" 
                                        placeholder="0.00"
                                    >
                                    <span class="text-[10px] text-slate-500 font-medium mt-0.5 block">
                                        = ₹{{ (parseFloat(form.released_state_share_cr || 0) * 100).toFixed(2) }} Lakh
                                    </span>
                                </div>
                            </div>

                            <!-- Central Share Utilization Bar -->
                            <div class="pt-2">
                                <div class="flex justify-between text-xs mb-1 font-semibold">
                                    <span class="text-slate-600">Central Share Drawing Progress</span>
                                    <span class="text-blue-700">{{ centralUtilisationPct }}% Drawn (₹{{ form.released_central_share_cr || 0 }} Cr of ₹{{ form.central_share_cr || 0 }} Cr)</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                    <div 
                                        class="bg-blue-600 h-2 rounded-full transition-all duration-500" 
                                        :style="{ width: `${centralUtilisationPct}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- MoJS Sanction Audit Ledger & Release Standing (Above Section 2) -->
                        <MojsSanctionLedgerCard 
                            :scheme="form.scheme_obj" 
                            :current-requested-cr="form.requested_amount_cr"
                            :current-instalment="form.instalment_number"
                        />

                        <!-- 2. CURRENT PAYMENT CLAIM (THE ACTIVE REQUEST) -->
                        <div class="bg-gradient-to-br from-indigo-50/50 to-blue-50/50 p-5 rounded-2xl border border-indigo-200 space-y-4">
                            <div class="flex items-center justify-between border-b border-indigo-200/80 pb-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                    <h4 class="font-bold text-xs text-indigo-950 uppercase tracking-wider">
                                        2. Current Central Fund Release Claim
                                    </h4>
                                </div>
                                <span class="text-xs font-bold text-indigo-700">Official Ministry Payment Request</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <!-- Requested Amount -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-sm font-bold text-gray-800">
                                            Requested Central Assistance (₹ Cr) <span class="text-red-500">*</span>
                                        </label>
                                        <span v-if="form.balance_central_share_cr" class="text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">
                                            Permissible Balance: ₹{{ form.balance_central_share_cr }} Cr
                                        </span>
                                    </div>
                                    <div class="relative">
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            v-model="form.requested_amount_cr" 
                                            :class="[
                                                'w-full rounded-xl pr-24 text-base font-extrabold text-blue-950 focus:ring-blue-500 focus:border-blue-500',
                                                isOverclaimWarning ? 'border-red-400 bg-red-50/30' : 'border-gray-300'
                                            ]"
                                            required 
                                            placeholder="e.g. 0.54"
                                        >
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-xs font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded">₹ Crore</span>
                                        </div>
                                    </div>

                                    <!-- Live Lakh Conversion & Alerts -->
                                    <div v-if="form.requested_amount_cr" class="mt-2 flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-bold text-slate-700 bg-white border border-slate-200 px-2.5 py-1 rounded-md shadow-2xs inline-block">
                                            = ₹{{ (parseFloat(form.requested_amount_cr || 0) * 100).toFixed(2) }} Lakh
                                        </span>
                                        <span v-if="!isOverclaimWarning && form.balance_central_share_cr" class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-md inline-block">
                                            ✓ Permissible Ceiling Available
                                        </span>
                                        <span v-else-if="isOverclaimWarning" class="text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-1 rounded-md inline-block">
                                            ⚠️ Exceeds Balance by ₹{{ (parseFloat(form.requested_amount_cr) - parseFloat(form.balance_central_share_cr)).toFixed(2) }} Cr
                                        </span>
                                    </div>
                                </div>

                                <!-- Instalment Selection -->
                                <div>
                                    <label class="block text-sm font-bold text-gray-800 mb-1">
                                        Instalment Number & Category <span class="text-red-500">*</span>
                                    </label>
                                    <select 
                                        v-model="form.instalment_number" 
                                        class="w-full rounded-xl border-gray-300 text-sm font-bold text-gray-900 focus:ring-blue-500 focus:border-blue-500" 
                                        required
                                    >
                                        <option :value="1">1 - First Instalment (Initial Advance / Mobilization)</option>
                                        <option :value="2">2 - Second Instalment (Mid-term / Progress-linked)</option>
                                        <option :value="3">3 - Third Instalment (Subsequent Progress Release)</option>
                                        <option :value="4">4 - Final Instalment (Project Closure / Final Settlement)</option>
                                    </select>
                                    <span class="text-[11px] text-gray-500 mt-1 block">
                                        {{ form.instalment_number === 1 ? 'Initial advance release before major expenditure' : 'Progress-linked release based on Utilization Certificate (UC)' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. STATE NODAL AGENCY (SNA) BANK ACCOUNT DETAILS -->
                        <div class="bg-white p-5 rounded-2xl border border-gray-200 space-y-4">
                            <div class="flex items-center gap-2 border-b pb-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-gray-700"></span>
                                <h4 class="font-bold text-xs text-gray-800 uppercase tracking-wider">
                                    3. State Nodal Agency (SNA) Bank Account (PFMS Integrated)
                                </h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">
                                        SNA Account & PFMS Transfer Credentials
                                    </label>
                                    <textarea 
                                        v-model="form.bank_details" 
                                        rows="4" 
                                        class="w-full rounded-xl border-gray-300 text-xs text-gray-800 focus:ring-blue-500 focus:border-blue-500" 
                                        placeholder="Enter Account Name, Bank Name, Account Number, IFSC Code, PFMS Unique Code..."
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">
                                        Departmental Justification & State Remarks
                                    </label>
                                    <textarea 
                                        v-model="form.state_remarks" 
                                        rows="4" 
                                        class="w-full rounded-xl border-gray-300 text-xs text-gray-800 focus:ring-blue-500 focus:border-blue-500" 
                                        placeholder="Enter work urgency, milestone completion summary, monsoon flood protection status, or any specific remarks for Brahmaputra Board review..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- 4. OFFICIAL STATE SANCTION ORDER (PDF) -->
                        <div class="bg-white p-5 rounded-2xl border border-gray-200 space-y-3">
                            <div class="flex items-center gap-2 border-b pb-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-gray-700"></span>
                                <h4 class="font-bold text-xs text-gray-800 uppercase tracking-wider">
                                    4. State Government Sanction / Administrative Approval Order (PDF)
                                </h4>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <input 
                                        type="file" 
                                        @change="e => handleFileUpload(e, 'state_govt_doc')" 
                                        accept=".pdf" 
                                        class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded-xl p-1"
                                    >
                                </div>
                                <div v-if="form.state_govt_doc" class="shrink-0 flex items-center gap-2">
                                    <span class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        {{ form.state_govt_doc.name }}
                                    </span>
                                </div>
                                <div v-else-if="paymentRequest.scheme?.fmbapProject?.state_govt_doc_path || paymentRequest.scheme?.fmbap_project?.state_govt_doc_path" class="shrink-0">
                                    <a :href="paymentRequest.scheme?.fmbapProject?.state_govt_doc_path || paymentRequest.scheme?.fmbap_project?.state_govt_doc_path" target="_blank" class="text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 px-3 py-1.5 rounded-lg flex items-center gap-1 hover:bg-blue-100">
                                        View Current Sanction PDF ↗
                                    </a>
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-500">Attach official sanction order issued by Finance Department / State Water Resources Department approving the claim.</p>
                        </div>
                    </div>

                    <!-- Step 3: Progress Metrics -->
                    <div v-if="currentStep === 3" class="space-y-6 animate-fade-in">
                        <h3 class="text-lg font-bold text-gray-900 border-b pb-2">Latest Progress Updates</h3>
                        
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-4 border p-4 rounded-lg bg-gray-50">
                                <h4 class="font-bold text-blue-800">Physical Progress</h4>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Percentage (%)</label>
                                    <input type="number" step="0.01" max="100" v-model="form.physical_progress_pct" class="w-full rounded-md border-gray-300 text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Description of works completed</label>
                                    <textarea v-model="form.physical_progress_description" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                                </div>
                            </div>

                            <div class="space-y-4 border p-4 rounded-lg bg-gray-50">
                                <h4 class="font-bold text-green-800">Financial Progress</h4>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Percentage (%)</label>
                                    <input type="number" step="0.01" max="100" v-model="form.financial_progress_pct" class="w-full rounded-md border-gray-300 text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Utilization Details</label>
                                    <textarea v-model="form.financial_progress_description" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Narrative Progress Report Summary</label>
                            <textarea v-model="form.narrative_progress_report" rows="3" class="w-full rounded-md border-gray-300"></textarea>
                        </div>
                    </div>

                    <!-- Step 4: Documents -->
                    <div v-if="currentStep === 4" class="space-y-6 animate-fade-in">
                        <div class="border-b pb-2">
                            <h3 class="text-lg font-bold text-gray-900">Upload Supporting Documents</h3>
                            <p class="text-xs text-gray-500 italic">Note: Leave file inputs empty to keep previously uploaded files.</p>
                        </div>

                        <!-- Form GFR 12-A Assistant Banner -->
                        <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl flex flex-wrap items-center justify-between gap-3 shadow-2xs">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl p-2 bg-blue-100 rounded-xl">📜</span>
                                <div>
                                    <div class="text-xs font-black text-blue-950 uppercase tracking-wide">Statutory Form GFR 12-A Assistant</div>
                                    <div class="text-[11px] text-blue-700">Auto-generate and update your statutory Utilization Certificate under Rule 238(1) with pre-filled sanction balances.</div>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="isGfrModalOpen = true"
                                class="px-4 py-2 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                            >
                                <span>✨ Auto-Generate Form GFR 12-A</span>
                            </button>
                        </div>

                        <!-- Attached GFR 12-A Banner -->
                        <div v-if="attachedGfrPath" class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between text-xs text-emerald-900 font-bold">
                            <span class="flex items-center gap-1.5">
                                <span>✅</span>
                                <span>Attached Updated Form GFR 12-A</span>
                            </span>
                            <a :href="attachedGfrPath" target="_blank" class="text-blue-700 hover:underline">View Attached Certificate &rarr;</a>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- UC -->
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50 text-center relative hover:bg-gray-100 transition">
                                <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <label class="block text-sm font-bold text-gray-700">Utilization Certificate (UC / GFR 12-A)</label>
                                <input type="file" @change="e => handleFileUpload(e, 'utilization_certificate')" accept=".pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                
                                <div v-if="form.utilization_certificate" class="text-xs text-blue-600 font-semibold bg-blue-50 py-1 rounded inline-block px-2 mt-2">
                                    New: {{ form.utilization_certificate.name }}
                                </div>
                                <div v-else-if="paymentRequest.utilization_certificate_path" class="text-xs text-emerald-600 font-semibold mt-2">
                                    Currently: <a :href="paymentRequest.utilization_certificate_path" target="_blank" class="underline relative z-10">View Existing UC</a>
                                </div>
                            </div>

                            <!-- Vouchers -->
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50 text-center relative hover:bg-gray-100 transition">
                                <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                <label class="block text-sm font-bold text-gray-700">Replace Expenditure Vouchers</label>
                                <input type="file" @change="e => handleFileUpload(e, 'vouchers', true)" accept=".pdf" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                <div v-if="form.vouchers.length" class="text-xs text-blue-600 font-semibold bg-blue-50 py-1 rounded inline-block px-2 mt-2">
                                    New: {{ form.vouchers.length }} file(s)
                                </div>
                                <div v-else-if="paymentRequest.voucher_doc_paths?.length" class="text-xs text-emerald-600 font-semibold mt-2">
                                    Currently: {{ paymentRequest.voucher_doc_paths.length }} files uploaded
                                </div>
                            </div>

                            <!-- Progress Report PDF -->
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50 text-center relative hover:bg-gray-100 transition md:col-span-2">
                                <label class="block text-sm font-bold text-gray-700">Replace State Progress Report (Optional)</label>
                                <input type="file" @change="e => handleFileUpload(e, 'progress_report_pdf')" accept=".pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                <div v-if="form.progress_report_pdf" class="text-xs text-blue-600 font-semibold mt-2">
                                    New: {{ form.progress_report_pdf.name }}
                                </div>
                                <div v-else-if="paymentRequest.progress_report_doc_path" class="text-xs text-emerald-600 font-semibold mt-2">
                                    Currently: <a :href="paymentRequest.progress_report_doc_path" target="_blank" class="underline relative z-10">View Existing</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Footer -->
                    <div class="mt-8 pt-5 border-t border-gray-200 flex justify-between">
                        <button 
                            type="button" 
                            v-if="currentStep > 1" 
                            @click="currentStep--" 
                            class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition"
                            :disabled="isSubmitting"
                        >
                            Back
                        </button>
                        <div v-else></div>

                        <button 
                            type="submit" 
                            class="px-5 py-2.5 bg-blue-600 text-white rounded-lg font-bold shadow-sm hover:bg-blue-700 transition flex items-center gap-2"
                            :disabled="isSubmitting || (currentStep === 1 && !form.scheme_id)"
                        >
                            <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            {{ currentStep === 4 ? 'Re-Submit to BB' : 'Save & Next' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <Gfr12aModal
            :is-open="isGfrModalOpen"
            :scheme-id="form.scheme_id || paymentRequest.scheme_id"
            :payment-request-id="paymentRequest.id"
            :current-physical-progress="form.physical_progress_pct"
            :current-financial-progress="form.financial_progress_pct"
            :current-requested-amount="form.requested_amount_cr"
            :current-instalment="form.instalment_number"
            @close="isGfrModalOpen = false"
            @attached="onGfrAttached"
        />
    </AuthenticatedLayout>
</template>

<style>
.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
