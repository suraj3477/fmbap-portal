<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import WizardStepIndicator from '@/Components/WizardStepIndicator.vue';
import SchemeSearchDropdown from '@/Components/SchemeSearchDropdown.vue';

const props = defineProps({
    userRole: String,
    availableSchemes: {
        type: Array,
        default: () => []
    },
    preselectedSchemeId: {
        type: Number,
        default: null
    }
});

const currentStep = ref(1);
const steps = ['Scheme Details', 'Financial Request', 'Progress Metrics', 'Supporting Docs'];
const isSubmitting = ref(false);
const errorMsg = ref('');

const form = ref({
    payment_request_id: null,
    // Step 1
    scheme_id: null,
    selected_scheme: null,
    // Step 2
    requested_amount_cr: '',
    instalment_number: 1,
    bank_details: '',
    state_remarks: '',
    // FMBAP Financial Tracking
    estimated_cost_cr: '',
    executed_amount_cr: '',
    funding_pattern: '90/10',
    central_share_cr: '',
    state_share_cr: '',
    released_central_share_cr: '0.00',
    released_state_share_cr: '0.00',
    balance_central_share_cr: '',
    balance_state_share_cr: '',
    state_govt_doc: null,
    // Step 3
    physical_progress_pct: '',
    physical_progress_description: '',
    financial_progress_pct: '',
    financial_progress_description: '',
    narrative_progress_report: '',
    // Step 4
    utilization_certificate: null,
    vouchers: [],
    progress_report_pdf: null,
});

// Dynamic State Matching Share required for this release
const stateMatchingShareCr = computed(() => {
    const req = parseFloat(form.value.requested_amount_cr) || 0;
    const parts = (form.value.funding_pattern || '90/10').split('/').map(Number);
    const cPct = parts[0] || 90;
    const sPct = parts[1] || 10;
    if (req <= 0 || cPct <= 0) return '0.00';
    return (req * (sPct / cPct)).toFixed(2);
});

// Calculate Percentage of Central Share already drawn + currently requested
const centralUtilisationPct = computed(() => {
    const totalCentral = parseFloat(form.value.central_share_cr) || 0;
    const released = parseFloat(form.value.released_central_share_cr) || 0;
    if (totalCentral <= 0) return 0;
    return Math.min(100, Math.round((released / totalCentral) * 100));
});

// Warning if requested claim exceeds eligible remaining balance
const isOverclaimWarning = computed(() => {
    const req = parseFloat(form.value.requested_amount_cr) || 0;
    const bal = parseFloat(form.value.balance_central_share_cr) || 0;
    return req > 0 && bal > 0 && req > bal;
});

const onSchemeSelected = (scheme) => {
    if (!scheme) return;
    form.value.scheme_id = scheme.id;
    form.value.selected_scheme = scheme;
    
    // 1. Funding Pattern: Default to 90/10 for Assam/NE Special Category
    const cPct = scheme.central_share_pct || 90;
    const sPct = scheme.state_share_pct || (100 - cPct);
    form.value.funding_pattern = `${cPct}/${sPct}`;

    // 2. Baseline Estimated Cost in ₹ Cr
    let costCr = 0;
    if (scheme.sanctioned_amount_cr && parseFloat(scheme.sanctioned_amount_cr) > 0) {
        costCr = parseFloat(scheme.sanctioned_amount_cr);
    } else if (scheme.estimated_cost_lakh && parseFloat(scheme.estimated_cost_lakh) > 0) {
        costCr = parseFloat(scheme.estimated_cost_lakh) / 100;
    }
    form.value.estimated_cost_cr = costCr > 0 ? costCr.toFixed(2) : '';

    // 3. Central & State Share
    const centralCr = costCr > 0 ? costCr * (cPct / 100) : 0;
    const stateCr = costCr > 0 ? costCr * (sPct / 100) : 0;
    form.value.central_share_cr = centralCr > 0 ? centralCr.toFixed(2) : '';
    form.value.state_share_cr = stateCr > 0 ? stateCr.toFixed(2) : '';

    // 4. Cumulative Prior Releases from Excel / Project
    const proj = scheme.fmbap_project || scheme.fmbapProject;
    let relCentral = 0;
    let relState = 0;

    if (proj && (proj.released_central_share_cr || proj.released_state_share_cr)) {
        relCentral = parseFloat(proj.released_central_share_cr) || 0;
        relState = parseFloat(proj.released_state_share_cr) || 0;
    } else if (scheme.fund_utilised_cs_lakh || scheme.fund_utilised_ss_lakh) {
        relCentral = (parseFloat(scheme.fund_utilised_cs_lakh) || 0) / 100;
        relState = (parseFloat(scheme.fund_utilised_ss_lakh) || 0) / 100;
    } else if (scheme.fund_utilised_total_lakh && parseFloat(scheme.fund_utilised_total_lakh) > 0) {
        const totalLakh = parseFloat(scheme.fund_utilised_total_lakh);
        relCentral = (totalLakh * (cPct / 100)) / 100;
        relState = (totalLakh * (sPct / 100)) / 100;
    }

    form.value.released_central_share_cr = relCentral > 0 ? relCentral.toFixed(2) : '0.00';
    form.value.released_state_share_cr = relState > 0 ? relState.toFixed(2) : '0.00';

    // 5. Calculate Balances
    const balCentral = Math.max(0, centralCr - relCentral);
    const balState = Math.max(0, stateCr - relState);
    form.value.balance_central_share_cr = balCentral > 0 ? balCentral.toFixed(2) : '0.00';
    form.value.balance_state_share_cr = balState > 0 ? balState.toFixed(2) : '0.00';

    // 6. Intelligent Claim Suggester
    if (scheme.fund_req_cs_lakh && parseFloat(scheme.fund_req_cs_lakh) > 0) {
        form.value.requested_amount_cr = (parseFloat(scheme.fund_req_cs_lakh) / 100).toFixed(2);
    } else if (balCentral > 0) {
        form.value.requested_amount_cr = balCentral.toFixed(2);
    } else {
        form.value.requested_amount_cr = '';
    }

    // 7. Suggest Instalment Number
    if (relCentral > 0) {
        form.value.instalment_number = 2;
    } else {
        form.value.instalment_number = 1;
    }

    // 8. Remarks pre-population
    if (proj?.remarks) {
        form.value.state_remarks = proj.remarks;
    } else if (scheme.metadata?.remarks) {
        form.value.state_remarks = scheme.metadata.remarks;
    }
};

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

onMounted(() => {
    if (props.preselectedSchemeId && props.availableSchemes?.length) {
        const found = props.availableSchemes.find(s => s.id === props.preselectedSchemeId);
        if (found) onSchemeSelected(found);
    } else if (props.availableSchemes?.length === 1) {
        onSchemeSelected(props.availableSchemes[0]);
    }
});

const handleFileUpload = (event, field, isMultiple = false) => {
    if (isMultiple) {
        form.value[field] = Array.from(event.target.files);
    } else {
        form.value[field] = event.target.files[0];
    }
};

const saveStep = async (isFinalSubmit = false) => {
    if (currentStep.value === 1 && !form.value.scheme_id) {
        errorMsg.value = 'Please select a scheme from the list or search box before proceeding.';
        return;
    }

    isSubmitting.value = true;
    errorMsg.value = '';

    const formData = new FormData();
    formData.append('step', currentStep.value);
    if (form.value.payment_request_id) {
        formData.append('payment_request_id', form.value.payment_request_id);
    }
    if (isFinalSubmit) {
        formData.append('is_final_submit', 1);
    }

    if (currentStep.value === 1) {
        formData.append('scheme_id', form.value.scheme_id);
    } else if (currentStep.value === 2) {
        formData.append('requested_amount_cr', form.value.requested_amount_cr || 0);
        formData.append('instalment_number', form.value.instalment_number || 1);
        formData.append('bank_details', form.value.bank_details || '');
        formData.append('state_remarks', form.value.state_remarks || '');
        formData.append('estimated_cost_cr', form.value.estimated_cost_cr || 0);
        formData.append('executed_amount_cr', form.value.executed_amount_cr || 0);
        formData.append('funding_pattern', form.value.funding_pattern || '90/10');
        formData.append('central_share_cr', form.value.central_share_cr || 0);
        formData.append('state_share_cr', form.value.state_share_cr || 0);
        formData.append('released_central_share_cr', form.value.released_central_share_cr || 0);
        formData.append('released_state_share_cr', form.value.released_state_share_cr || 0);
        formData.append('balance_central_share_cr', form.value.balance_central_share_cr || 0);
        formData.append('balance_state_share_cr', form.value.balance_state_share_cr || 0);
        if (form.value.state_govt_doc) {
            formData.append('state_govt_doc', form.value.state_govt_doc);
        }
    } else if (currentStep.value === 3) {
        formData.append('physical_progress_pct', form.value.physical_progress_pct || 0);
        formData.append('physical_progress_description', form.value.physical_progress_description || '');
        formData.append('financial_progress_pct', form.value.financial_progress_pct || 0);
        formData.append('financial_progress_description', form.value.financial_progress_description || '');
        formData.append('narrative_progress_report', form.value.narrative_progress_report || '');
    } else if (currentStep.value === 4) {
        if (form.value.utilization_certificate) {
            formData.append('utilization_certificate', form.value.utilization_certificate);
        }
        if (form.value.progress_report_pdf) {
            formData.append('progress_report_pdf', form.value.progress_report_pdf);
        }
        form.value.vouchers.forEach((v, idx) => {
            formData.append(`vouchers[${idx}]`, v);
        });
    }

    try {
        const response = await axios.post(route('fund-release.store'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        form.value.payment_request_id = response.data.payment_request_id;

        if (isFinalSubmit) {
            router.visit(route('fund-release.show', response.data.payment_request_id));
        } else {
            currentStep.value++;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    } catch (error) {
        errorMsg.value = error.response?.data?.message || 'An error occurred while saving. Please check your entries.';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Head title="Submit New Fund Release Request - FMBAP" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-xl text-gray-900 leading-tight flex items-center gap-2">
                        <span class="p-1.5 bg-blue-100 text-blue-800 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </span>
                        New Fund Release Request (FMBAP Claim)
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">Submit Central Assistance Claim with official FMBAP baseline tracking and statutory share breakdown</p>
                </div>
                <div v-if="form.selected_scheme" class="text-right">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        Target: {{ form.selected_scheme.scheme_code }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-8 mx-auto max-w-7xl sm:px-6 lg:px-8 w-full">
            
            <WizardStepIndicator :steps="steps" :currentStep="currentStep" />

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                
                <div v-if="errorMsg" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm font-medium flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ errorMsg }}</span>
                </div>

                <form @submit.prevent="currentStep === 4 ? saveStep(true) : saveStep(false)">
                    
                    <!-- ================= STEP 1: SCHEME SELECTION ================= -->
                    <div v-if="currentStep === 1" class="space-y-6 animate-fade-in">
                        <div class="border-b pb-4">
                            <h3 class="text-lg font-bold text-gray-900">Step 1: Select Target Scheme</h3>
                            <p class="text-xs text-gray-500 mt-1">Pick a scheme from the catalogue or your uploaded Excel master sheet to initialize financial baseline parameters.</p>
                        </div>

                        <!-- Dropdown Search -->
                        <div class="max-w-3xl">
                            <SchemeSearchDropdown 
                                v-model="form.scheme_id" 
                                :initial-scheme="form.selected_scheme"
                                @scheme-selected="onSchemeSelected" 
                            />
                        </div>

                        <!-- Quick Picker from Available Schemes -->
                        <div v-if="availableSchemes.length > 0" class="pt-2">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Recently Added / Available Schemes ({{ availableSchemes.length }})
                                </label>
                                <span class="text-[11px] text-gray-400">Click any card below to select instantly</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <div 
                                    v-for="s in availableSchemes.slice(0, 6)" 
                                    :key="s.id"
                                    @click="onSchemeSelected(s)"
                                    :class="[
                                        'p-4 rounded-xl border cursor-pointer transition-all relative',
                                        form.scheme_id === s.id 
                                            ? 'bg-blue-50/80 border-blue-500 ring-2 ring-blue-500/20 shadow-sm' 
                                            : 'bg-gray-50/60 border-gray-200 hover:bg-white hover:border-gray-300'
                                    ]"
                                >
                                    <div class="flex items-start justify-between gap-2 mb-1.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold font-mono bg-blue-100 text-blue-800">
                                            {{ s.scheme_code }}
                                        </span>
                                        <span class="text-xs font-extrabold text-gray-900">
                                            ₹{{ s.sanctioned_amount_cr || (s.estimated_cost_lakh ? (s.estimated_cost_lakh/100).toFixed(2) : '0.00') }} Cr
                                        </span>
                                    </div>
                                    <h4 class="text-xs font-semibold text-gray-800 line-clamp-2 mb-2 leading-relaxed">
                                        {{ s.scheme_name }}
                                    </h4>
                                    <div class="flex items-center justify-between text-[11px] text-gray-500 pt-2 border-t border-gray-100">
                                        <span>{{ s.division || s.district || 'Assam' }}</span>
                                        <span class="font-medium text-indigo-600">{{ s.plan_period || 'XI Plan' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Scheme Official Dossier Card -->
                        <div v-if="form.selected_scheme" class="mt-6 p-5 bg-gradient-to-br from-blue-50/60 to-indigo-50/60 rounded-2xl border border-blue-200">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-md text-xs font-extrabold bg-blue-600 text-white font-mono">
                                        {{ form.selected_scheme.scheme_code }}
                                    </span>
                                    <span class="text-xs font-bold text-blue-900 uppercase tracking-wider">
                                        Selected Scheme Dossier
                                    </span>
                                </div>
                                <span class="text-xs font-semibold text-emerald-700 bg-emerald-100/70 border border-emerald-300 px-2.5 py-0.5 rounded-full flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Ready for Financial Claim
                                </span>
                            </div>

                            <h3 class="text-sm font-bold text-gray-900 mb-3">
                                {{ form.selected_scheme.scheme_name }}
                            </h3>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                <div class="bg-white/80 p-2.5 rounded-lg border border-blue-100">
                                    <span class="text-[10px] text-gray-500 font-bold uppercase block">Division / District</span>
                                    <span class="font-semibold text-gray-800">{{ form.selected_scheme.division || form.selected_scheme.district || 'Assam WRD' }}</span>
                                </div>
                                <div class="bg-white/80 p-2.5 rounded-lg border border-blue-100">
                                    <span class="text-[10px] text-gray-500 font-bold uppercase block">Plan Period</span>
                                    <span class="font-semibold text-indigo-700">{{ form.selected_scheme.plan_period || 'XI Plan' }}</span>
                                </div>
                                <div class="bg-white/80 p-2.5 rounded-lg border border-blue-100">
                                    <span class="text-[10px] text-gray-500 font-bold uppercase block">Sanctioned Cost</span>
                                    <span class="font-extrabold text-gray-900">
                                        ₹{{ form.estimated_cost_cr || '0.00' }} Cr
                                        <span class="text-[10px] text-gray-500 font-normal">({{ form.selected_scheme.estimated_cost_lakh || (parseFloat(form.estimated_cost_cr)*100).toFixed(2) }} Lakh)</span>
                                    </span>
                                </div>
                                <div class="bg-white/80 p-2.5 rounded-lg border border-blue-100">
                                    <span class="text-[10px] text-gray-500 font-bold uppercase block">Prior Utilised / Released</span>
                                    <span class="font-bold text-emerald-700">
                                        ₹{{ (parseFloat(form.released_central_share_cr) + parseFloat(form.released_state_share_cr)).toFixed(2) }} Cr
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================= STEP 2: FINANCIAL REQUEST & AUDIT BASELINE ================= -->
                    <div v-if="currentStep === 2" class="space-y-6 animate-fade-in">
                        
                        <!-- Header Banner -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b pb-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                    <span>Step 2: Financial Request & FMBAP Baseline Audit</span>
                                    <span class="text-xs px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-semibold">Auto-Synced</span>
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">Parameters are pre-loaded from official master sheet. Both ₹ in Crore and ₹ in Lakh are displayed side-by-side.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono font-bold bg-blue-100 text-blue-800 px-3 py-1 rounded-lg border border-blue-200">
                                    {{ form.selected_scheme?.scheme_code || 'FMBAP-SCHEME' }}
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
                                    <label class="block text-sm font-bold text-gray-800 mb-1">
                                        Requested Central Assistance (₹ Cr) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            v-model="form.requested_amount_cr" 
                                            class="w-full rounded-xl border-gray-300 pr-24 text-base font-extrabold text-blue-950 focus:ring-blue-500 focus:border-blue-500" 
                                            required 
                                            placeholder="e.g. 0.54"
                                        >
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-xs font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded">₹ Crore</span>
                                        </div>
                                    </div>

                                    <!-- Live Lakh Conversion -->
                                    <div v-if="form.requested_amount_cr" class="mt-2">
                                        <span class="text-xs font-bold text-slate-700 bg-white border border-slate-200 px-2.5 py-1 rounded-md shadow-2xs inline-block">
                                            = ₹{{ (parseFloat(form.requested_amount_cr || 0) * 100).toFixed(2) }} Lakh
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
                            </div>
                            <p class="text-[11px] text-gray-500">Attach official sanction order issued by Finance Department / State Water Resources Department approving the claim.</p>
                        </div>

                    </div>

                    <!-- ================= STEP 3: PROGRESS METRICS ================= -->
                    <div v-if="currentStep === 3" class="space-y-6 animate-fade-in">
                        <div class="border-b pb-4">
                            <h3 class="text-lg font-bold text-gray-900">Step 3: Latest Physical & Financial Progress</h3>
                            <p class="text-xs text-gray-500 mt-1">Report cumulative site execution status to justify release of funds as per Brahmaputra Board monitoring standards.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Physical Progress -->
                            <div class="space-y-4 border border-blue-200 p-5 rounded-2xl bg-blue-50/30">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-bold text-sm text-blue-900 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                        Physical Progress Metrics
                                    </h4>
                                    <span class="text-xs font-bold text-blue-700">Site Works</span>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Physical Completion (%) <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            max="100" 
                                            v-model="form.physical_progress_pct" 
                                            class="w-full rounded-lg border-gray-300 text-sm font-bold" 
                                            required 
                                            placeholder="e.g. 65.50"
                                        >
                                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs font-bold text-gray-400">%</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Description of Works Completed on Ground</label>
                                    <textarea 
                                        v-model="form.physical_progress_description" 
                                        rows="3" 
                                        class="w-full rounded-lg border-gray-300 text-xs" 
                                        placeholder="e.g. Geo-bag revetment completed for 1200m; Porcupine screens placed at 4 locations along Kollong river..."
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Financial Progress -->
                            <div class="space-y-4 border border-emerald-200 p-5 rounded-2xl bg-emerald-50/30">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-bold text-sm text-emerald-900 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                                        Financial Progress & Expenditure
                                    </h4>
                                    <span class="text-xs font-bold text-emerald-700">Audit Status</span>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Financial Progress (%) <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            max="100" 
                                            v-model="form.financial_progress_pct" 
                                            class="w-full rounded-lg border-gray-300 text-sm font-bold" 
                                            required 
                                            placeholder="e.g. 58.00"
                                        >
                                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs font-bold text-gray-400">%</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Utilization Details / Expenditure Breakdown</label>
                                    <textarea 
                                        v-model="form.financial_progress_description" 
                                        rows="3" 
                                        class="w-full rounded-lg border-gray-300 text-xs" 
                                        placeholder="Details of payments made to contractors, running bills cleared, and pending liabilities..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Executive Narrative Progress Summary</label>
                            <textarea 
                                v-model="form.narrative_progress_report" 
                                rows="3" 
                                class="w-full rounded-xl border-gray-300 text-xs" 
                                placeholder="Overall progress overview to be reviewed by Brahmaputra Board Chief Engineer..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- ================= STEP 4: SUPPORTING DOCUMENTS ================= -->
                    <div v-if="currentStep === 4" class="space-y-6 animate-fade-in">
                        <div class="border-b pb-4">
                            <h3 class="text-lg font-bold text-gray-900">Step 4: Upload Statutory Supporting Documents</h3>
                            <p class="text-xs text-gray-500 mt-1">Attach certified Utilization Certificates (GFR 12-C) and site expenditure vouchers for Brahmaputra Board audit.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Utilization Certificate (UC) -->
                            <div class="border-2 border-dashed border-blue-300 rounded-2xl p-6 bg-blue-50/40 text-center relative hover:bg-blue-50/80 transition">
                                <svg class="mx-auto h-9 w-9 text-blue-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <label class="block text-sm font-bold text-gray-800">Utilization Certificate (UC / GFR 12-C) <span class="text-red-500">*</span></label>
                                <p class="text-xs text-gray-500 mb-3">Certified PDF format, max 20MB</p>
                                <input type="file" @change="e => handleFileUpload(e, 'utilization_certificate')" accept=".pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required>
                                <div v-if="form.utilization_certificate" class="text-xs text-blue-700 font-bold bg-blue-100 py-1.5 px-3 rounded-lg inline-flex items-center gap-1.5 mt-2">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    {{ form.utilization_certificate.name }}
                                </div>
                            </div>

                            <!-- Expenditure Vouchers -->
                            <div class="border-2 border-dashed border-gray-300 rounded-2xl p-6 bg-gray-50 text-center relative hover:bg-gray-100/70 transition">
                                <svg class="mx-auto h-9 w-9 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                <label class="block text-sm font-bold text-gray-800">Expenditure Vouchers / Invoices</label>
                                <p class="text-xs text-gray-500 mb-3">Multiple PDFs allowed</p>
                                <input type="file" @change="e => handleFileUpload(e, 'vouchers', true)" accept=".pdf" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                <div v-if="form.vouchers.length" class="text-xs text-blue-700 font-bold bg-blue-100 py-1.5 px-3 rounded-lg inline-block mt-2">
                                    {{ form.vouchers.length }} file(s) attached
                                </div>
                            </div>

                            <!-- Detailed State Progress Report -->
                            <div class="border-2 border-dashed border-gray-300 rounded-2xl p-6 bg-gray-50 text-center relative hover:bg-gray-100/70 transition md:col-span-2">
                                <label class="block text-sm font-bold text-gray-800">Detailed State Progress Report (Optional PDF)</label>
                                <p class="text-xs text-gray-500 mb-2">Technical inspection notes, geo-tagged photos, or river embankment cross-sections</p>
                                <input type="file" @change="e => handleFileUpload(e, 'progress_report_pdf')" accept=".pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                <div v-if="form.progress_report_pdf" class="text-xs text-blue-700 font-bold mt-2">
                                    Attached: {{ form.progress_report_pdf.name }}
                                </div>
                                <div v-else class="text-xs text-gray-400 mt-2 underline">Click to browse file</div>
                            </div>
                        </div>
                        
                        <!-- Confirmation Alert -->
                        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mt-6">
                            <h4 class="text-amber-900 font-bold text-sm flex items-center gap-2">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                Formal Workflow Submission Notice
                            </h4>
                            <p class="text-xs text-amber-800 mt-1 leading-relaxed">
                                Upon clicking "Submit to Brahmaputra Board", this payment dossier will be securely transmitted to the Brahmaputra Board Monitoring Cell for scrutiny, site inspection, and recommendation to the Ministry of Jal Shakti.
                            </p>
                        </div>
                    </div>

                    <!-- ================= NAVIGATION FOOTER ================= -->
                    <div class="mt-8 pt-5 border-t border-gray-200 flex items-center justify-between">
                        <button 
                            type="button" 
                            v-if="currentStep > 1" 
                            @click="currentStep--" 
                            class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition"
                            :disabled="isSubmitting"
                        >
                            ← Back
                        </button>
                        <div v-else></div> <!-- Spacer -->

                        <button 
                            type="submit" 
                            class="px-6 py-2.5 bg-blue-600 text-white rounded-xl font-bold shadow-md hover:bg-blue-700 transition flex items-center gap-2"
                            :disabled="isSubmitting || (currentStep === 1 && !form.scheme_id)"
                        >
                            <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <span>{{ currentStep === 4 ? 'Submit to Brahmaputra Board →' : 'Save & Continue →' }}</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.animate-fade-in {
    animation: fadeIn 0.25s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
