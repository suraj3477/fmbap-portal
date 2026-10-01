<script setup>
import { ref, reactive, watch, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    schemeId: { type: [Number, String], default: null },
    paymentRequestId: { type: [Number, String], default: null },
    currentPhysicalProgress: { type: [Number, String], default: null },
    currentFinancialProgress: { type: [Number, String], default: null },
    currentRequestedAmount: { type: [Number, String], default: null },
    currentInstalment: { type: [Number, String], default: null },
});

const emit = defineEmits(['close', 'attached']);

const isLoading = ref(false);
const isGenerating = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const form = reactive({
    scheme_id: null,
    payment_request_id: null,
    scheme_code: '',
    scheme_name: '',
    state: '',
    financial_year: '2026-2027',
    instalment_number: 1,
    sanction_letter_no: '',
    sanction_date: '',
    central_amount_cr: 0,
    state_amount_cr: 0,
    total_amount_cr: 0,
    utilized_amount_cr: 0,
    unspent_balance_cr: 0,
    interest_accrued_cr: 0,
    physical_progress_pct: 0,
    financial_progress_pct: 0,
    officer_name: '',
    officer_designation: 'Executive Engineer, WRD',
});

// Auto-recalculate unspent balance and total
const recalculate = () => {
    form.total_amount_cr = Number((Number(form.central_amount_cr || 0) + Number(form.state_amount_cr || 0)).toFixed(2));
    form.unspent_balance_cr = Number(Math.max(0, form.total_amount_cr - Number(form.utilized_amount_cr || 0)).toFixed(2));
};

watch(() => [form.central_amount_cr, form.state_amount_cr, form.utilized_amount_cr], () => {
    recalculate();
});

const fetchPrefillData = async () => {
    if (!props.schemeId) return;
    isLoading.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const response = await axios.get(route('fund-release.gfr12a.data'), {
            params: {
                scheme_id: props.schemeId,
                payment_request_id: props.paymentRequestId || undefined,
            }
        });

        const data = response.data;
        Object.assign(form, {
            scheme_id: data.scheme_id,
            payment_request_id: props.paymentRequestId || null,
            scheme_code: data.scheme_code || '',
            scheme_name: data.scheme_name || '',
            state: data.state || '',
            financial_year: data.financial_year || '2026-2027',
            instalment_number: props.currentInstalment || data.instalment_number || 1,
            sanction_letter_no: data.sanction_letter_no || '',
            sanction_date: data.sanction_date || '',
            central_amount_cr: props.currentRequestedAmount || data.central_amount_cr || 0,
            state_amount_cr: data.state_amount_cr || 0,
            total_amount_cr: data.total_amount_cr || 0,
            utilized_amount_cr: data.utilized_amount_cr || 0,
            unspent_balance_cr: data.unspent_balance_cr || 0,
            interest_accrued_cr: data.interest_accrued_cr || 0,
            physical_progress_pct: props.currentPhysicalProgress !== null ? props.currentPhysicalProgress : data.physical_progress_pct,
            financial_progress_pct: props.currentFinancialProgress !== null ? props.currentFinancialProgress : data.financial_progress_pct,
            officer_name: data.officer_name || '',
            officer_designation: data.officer_designation || 'Executive Engineer, WRD',
        });
        recalculate();
    } catch (err) {
        console.error('Failed to prefill GFR-12A:', err);
        errorMessage.value = 'Failed to load statutory scheme data for Form GFR-12A.';
    } finally {
        isLoading.value = false;
    }
};

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        fetchPrefillData();
    }
});

const handleDownloadPdf = () => {
    isGenerating.value = true;
    errorMessage.value = '';

    // Create a temporary hidden form to trigger browser native download
    const submitForm = document.createElement('form');
    submitForm.method = 'POST';
    submitForm.action = route('fund-release.gfr12a.generate');
    submitForm.target = '_blank';

    // CSRF Token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = csrfToken;
        submitForm.appendChild(tokenInput);
    }

    const payload = { ...form, action: 'download' };
    for (const key in payload) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = payload[key];
        submitForm.appendChild(input);
    }

    document.body.appendChild(submitForm);
    submitForm.submit();
    document.body.removeChild(submitForm);

    setTimeout(() => {
        isGenerating.value = false;
        successMessage.value = 'GFR-12A PDF generated successfully.';
    }, 1200);
};

const handleAttachToClaim = async () => {
    if (!props.paymentRequestId) {
        errorMessage.value = 'Please save your draft first or download the GFR-12A PDF directly to upload.';
        return;
    }

    isGenerating.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const response = await axios.post(route('fund-release.gfr12a.generate'), {
            ...form,
            action: 'attach',
        });

        successMessage.value = response.data.message || 'Form GFR-12A generated and attached to claim!';
        emit('attached', response.data.file_path);
        setTimeout(() => {
            emit('close');
        }, 1500);
    } catch (err) {
        console.error('Error attaching GFR-12A:', err);
        errorMessage.value = err.response?.data?.message || 'Failed to attach Form GFR-12A.';
    } finally {
        isGenerating.value = false;
    }
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-3xl overflow-hidden animate-fade-in flex flex-col max-h-[92vh]">
            <!-- Header -->
            <div class="bg-[#0F4C9F] text-white p-4 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">📜</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-black tracking-tight text-white uppercase">Form GFR 12-A &bull; Statutory UC Generator</h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-400 text-slate-900">Rule 238(1) GFR 2017</span>
                        </div>
                        <p class="text-[11px] text-blue-200 mt-0.5">
                            Automated statutory Utilization Certificate for Ministry of Jal Shakti Central Assistance (90:10 Pattern)
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="$emit('close')"
                    class="p-1.5 rounded-lg text-white/80 hover:text-white hover:bg-white/10 text-lg leading-none cursor-pointer"
                >
                    &times;
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto space-y-4 text-xs">
                <!-- Alerts -->
                <div v-if="isLoading" class="py-12 text-center text-slate-500 font-bold flex flex-col items-center gap-2">
                    <svg class="animate-spin h-6 w-6 text-[#0F4C9F]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Extracting Scheme Sanction &amp; Grant Balances...</span>
                </div>

                <div v-if="errorMessage" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-semibold flex items-center gap-2">
                    <span>⚠️</span>
                    <span>{{ errorMessage }}</span>
                </div>

                <div v-if="successMessage" class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-semibold flex items-center gap-2">
                    <span>✅</span>
                    <span>{{ successMessage }}</span>
                </div>

                <div v-if="!isLoading" class="space-y-4">
                    <!-- Scheme Card Info -->
                    <div class="p-3 bg-blue-50/70 border border-blue-200/80 rounded-xl flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <div class="text-[11px] font-bold text-blue-900 uppercase tracking-wide">
                                Scheme: {{ form.scheme_code }}
                            </div>
                            <div class="text-xs font-semibold text-slate-800 line-clamp-1">
                                {{ form.scheme_name }}
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-blue-700 font-bold block">State / Basin</span>
                            <span class="text-xs font-bold text-slate-900">{{ form.state }} ({{ form.river_basin || 'Brahmaputra' }})</span>
                        </div>
                    </div>

                    <!-- Row 1: Statutory Sanction Reference -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Financial Year *</label>
                            <input
                                v-model="form.financial_year"
                                type="text"
                                class="w-full rounded-lg border-slate-300 text-xs font-bold"
                                placeholder="e.g. 2026-2027"
                                required
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Instalment No. *</label>
                            <input
                                v-model="form.instalment_number"
                                type="number"
                                min="1"
                                class="w-full rounded-lg border-slate-300 text-xs font-bold"
                                required
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Sanction Order Date *</label>
                            <input
                                v-model="form.sanction_date"
                                type="text"
                                class="w-full rounded-lg border-slate-300 text-xs font-mono"
                                placeholder="DD/MM/YYYY"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">MoJS Central Sanction Reference No. *</label>
                        <input
                            v-model="form.sanction_letter_no"
                            type="text"
                            class="w-full rounded-lg border-slate-300 text-xs font-mono"
                            placeholder="e.g. MoJS/FMBAP/AS/2026-27/04"
                            required
                        />
                    </div>

                    <!-- Row 2: Financial Matrix (Crores) -->
                    <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                        <div class="font-bold text-slate-800 text-xs flex items-center justify-between border-b border-slate-200 pb-1.5">
                            <span>Financial Reconciliation (₹ in Crore)</span>
                            <span class="text-[10px] text-blue-700 font-semibold">90:10 Central / State Formula</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Central Share (90%) *</label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-2 text-slate-400 font-bold">₹</span>
                                    <input
                                        v-model="form.central_amount_cr"
                                        type="number"
                                        step="0.01"
                                        class="w-full pl-6 rounded-lg border-slate-300 text-xs font-bold text-blue-900"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">State Share (10%) *</label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-2 text-slate-400 font-bold">₹</span>
                                    <input
                                        v-model="form.state_amount_cr"
                                        type="number"
                                        step="0.01"
                                        class="w-full pl-6 rounded-lg border-slate-300 text-xs font-bold text-purple-900"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Total Available Funds</label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-2 text-slate-400 font-bold">₹</span>
                                    <input
                                        :value="form.total_amount_cr"
                                        type="number"
                                        disabled
                                        class="w-full pl-6 rounded-lg border-slate-200 bg-slate-100 text-xs font-black text-slate-900"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Utilized on Works (₹ Cr) *</label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-2 text-slate-400 font-bold">₹</span>
                                    <input
                                        v-model="form.utilized_amount_cr"
                                        type="number"
                                        step="0.01"
                                        class="w-full pl-6 rounded-lg border-emerald-400 text-xs font-bold text-emerald-900 bg-emerald-50/30"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Unspent Balance (₹ Cr)</label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-2 text-slate-400 font-bold">₹</span>
                                    <input
                                        :value="form.unspent_balance_cr"
                                        type="number"
                                        disabled
                                        class="w-full pl-6 rounded-lg border-slate-200 bg-slate-100 text-xs font-bold text-amber-900"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">SNA Accrued Interest (₹ Cr)</label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-2 text-slate-400 font-bold">₹</span>
                                    <input
                                        v-model="form.interest_accrued_cr"
                                        type="number"
                                        step="0.01"
                                        class="w-full pl-6 rounded-lg border-slate-300 text-xs font-medium"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Progress & Signatory Verification -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Claimed Physical Progress (%)</label>
                            <input
                                v-model="form.physical_progress_pct"
                                type="number"
                                min="0"
                                max="100"
                                class="w-full rounded-lg border-slate-300 text-xs font-bold text-blue-900"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Financial Progress (%)</label>
                            <input
                                v-model="form.financial_progress_pct"
                                type="number"
                                min="0"
                                max="100"
                                class="w-full rounded-lg border-slate-300 text-xs font-bold text-emerald-900"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">DDO / Verifying Officer Name *</label>
                            <input
                                v-model="form.officer_name"
                                type="text"
                                class="w-full rounded-lg border-slate-300 text-xs font-medium"
                                placeholder="e.g. Er. R. K. Sarma"
                                required
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Designation &amp; Division *</label>
                            <input
                                v-model="form.officer_designation"
                                type="text"
                                class="w-full rounded-lg border-slate-300 text-xs font-medium"
                                placeholder="e.g. Executive Engineer, Guwahati WRD"
                                required
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer / Actions -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2 shrink-0">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-4 py-2 border border-slate-300 rounded-lg font-semibold text-slate-700 hover:bg-slate-100 transition text-xs cursor-pointer"
                >
                    Cancel
                </button>

                <div class="flex items-center gap-2">
                    <!-- Direct PDF Download -->
                    <button
                        type="button"
                        @click="handleDownloadPdf"
                        :disabled="isGenerating || isLoading"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                    >
                        <span>📄 Download GFR-12A PDF</span>
                    </button>

                    <!-- Auto Attach Button -->
                    <button
                        v-if="paymentRequestId"
                        type="button"
                        @click="handleAttachToClaim"
                        :disabled="isGenerating || isLoading"
                        class="px-4 py-2 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white font-bold rounded-lg text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                    >
                        <svg v-if="isGenerating" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ isGenerating ? 'Attaching...' : '📎 Attach to this Claim' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.15s ease-out forwards;
}
</style>
