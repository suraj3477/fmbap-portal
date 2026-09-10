<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const isOpen = ref(false);

const form = useForm({
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
    
    // Stage 1: State Govt
    state_govt_doc: null,
    state_govt_submission_date: '',
    
    // Stage 2: Brahmaputra Board
    brahmaputra_board_doc: null,
    brahmaputra_board_submission_date: '',
    
    // Stage 3: FM Section, MoJS
    mojs_doc: null,
    mojs_submission_date: '',
});

// Real-time Balance Calculation: Balance = Share - Released
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

const submit = () => {
    form.post(route('fmbap.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            isOpen.value = false;
        },
    });
};
</script>

<template>
    <div>
        <!-- Renamed Trigger Button -->
        <button
            @click="isOpen = true"
            class="bg-blue-700 hover:bg-blue-800 text-white font-semibold py-2 px-4 rounded-md text-sm shadow flex items-center gap-2"
        >
            <span>+</span> Submission of FMBAP projects
        </button>

        <!-- Modal Overlay -->
        <div v-if="isOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-h-[90vh] overflow-y-auto p-6 border border-gray-200">
                
                <!-- Modal Header -->
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <h3 class="text-base font-bold text-gray-800 uppercase tracking-wide">
                        Submission of FMBAP proposals
                    </h3>
                    <button @click="isOpen = false" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    
                    <!-- 1. Name of Scheme -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase">Name of Scheme</label>
                        <textarea
                            v-model="form.scheme_name"
                            placeholder="e.g. Extension of existing protection works..."
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500"
                            rows="2"
                            required
                        ></textarea>
                    </div>

                    <!-- 2. Cost & Funding Pattern -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase">Estimated amount / IMC approved cost (₹ in Cr)</label>
                            <input type="number" step="0.01" v-model="form.estimated_cost_cr" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase">Executed Amount (₹ in Cr)</label>
                            <input type="number" step="0.01" v-model="form.executed_amount_cr" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase">Funding Pattern</label>
                            <select v-model="form.funding_pattern" class="mt-1 block w-full rounded-md border-gray-300 text-sm bg-white">
                                <option value="90/10">90/10</option>
                                <option value="80/20">80/20</option>
                                <option value="70/30">70/30</option>
                                <option value="60/40">60/40</option>
                                <option value="50/50">50/50</option>
                                <option value="100/0">100/0</option>
                            </select>
                        </div>
                    </div>

                    <!-- 3. Shares (Direct Entry by State) -->
                    <div class="p-3 bg-gray-50 rounded-md border border-gray-200">
                        <span class="text-xs font-bold text-gray-700 uppercase block mb-2">Funding Shares (₹ in Cr)</span>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Central Share</label>
                                <input type="number" step="0.01" v-model="form.central_share_cr" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">State Share</label>
                                <input type="number" step="0.01" v-model="form.state_share_cr" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required />
                            </div>
                        </div>
                    </div>

                    <!-- 4. Released Shares -->
                    <div class="p-3 bg-gray-50 rounded-md border border-gray-200">
                        <span class="text-xs font-bold text-gray-700 uppercase block mb-2">Released (₹ in Cr)</span>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Central Share</label>
                                <input type="number" step="0.01" v-model="form.released_central_share_cr" class="mt-1 block w-full rounded-md border-gray-300 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">State Share</label>
                                <input type="number" step="0.01" v-model="form.released_state_share_cr" class="mt-1 block w-full rounded-md border-gray-300 text-sm" />
                            </div>
                        </div>
                    </div>

                    <!-- 5. Calculated Balance Shares -->
                    <div class="p-3 bg-blue-50 rounded-md border border-blue-200">
                        <span class="text-xs font-bold text-blue-800 uppercase block mb-2">Balance (₹ in Cr)</span>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-blue-700">Central Share</label>
                                <input type="number" readonly :value="form.balance_central_share_cr" class="mt-1 block w-full bg-blue-100 border-blue-300 text-sm rounded-md font-semibold text-blue-900" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-blue-700">State Share</label>
                                <input type="number" readonly :value="form.balance_state_share_cr" class="mt-1 block w-full bg-blue-100 border-blue-300 text-sm rounded-md font-semibold text-blue-900" />
                            </div>
                        </div>
                    </div>

                    <!-- 6. Remarks -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase">Remarks</label>
                        <textarea v-model="form.remarks" class="mt-1 block w-full rounded-md border-gray-300 text-sm" rows="2"></textarea>
                    </div>

                    <!-- 7. Document Upload Sections -->
                    <div class="space-y-3 pt-2">
                        <!-- State Government -->
                        <div class="p-3 bg-slate-50 rounded-md border border-slate-200">
                            <span class="text-xs font-bold text-slate-800 uppercase block mb-2">State Government</span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-600">Upload Document (PDF)</label>
                                    <input type="file" accept="application/pdf" @change="e => form.state_govt_doc = e.target.files[0]" class="mt-1 block w-full text-xs" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600">Date of Submission</label>
                                    <input type="date" v-model="form.state_govt_submission_date" class="mt-1 block w-full rounded-md border-gray-300 text-sm" />
                                </div>
                            </div>
                        </div>

                        <!-- Brahmaputra Board -->
                        <div class="p-3 bg-slate-50 rounded-md border border-slate-200">
                            <span class="text-xs font-bold text-slate-800 uppercase block mb-2">Brahmaputra Board</span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-600">Upload Document (PDF)</label>
                                    <input type="file" accept="application/pdf" @change="e => form.brahmaputra_board_doc = e.target.files[0]" class="mt-1 block w-full text-xs" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600">Date of Submission</label>
                                    <input type="date" v-model="form.brahmaputra_board_submission_date" class="mt-1 block w-full rounded-md border-gray-300 text-sm" />
                                </div>
                            </div>
                        </div>

                        <!-- FM Section, MoJS -->
                        <div class="p-3 bg-slate-50 rounded-md border border-slate-200">
                            <span class="text-xs font-bold text-slate-800 uppercase block mb-2">FM Section, MoJS</span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-600">Upload Document (PDF)</label>
                                    <input type="file" accept="application/pdf" @change="e => form.mojs_doc = e.target.files[0]" class="mt-1 block w-full text-xs" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600">Date of Submission</label>
                                    <input type="date" v-model="form.mojs_submission_date" class="mt-1 block w-full rounded-md border-gray-300 text-sm" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex justify-end gap-3 pt-3 border-t">
                        <button type="button" @click="isOpen = false" class="bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-semibold px-4 py-2 rounded-md">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold px-5 py-2 rounded-md shadow">
                            Submit Proposal
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</template>