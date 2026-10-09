<script setup>
import { computed } from 'vue';

const props = defineProps({
    scheme: {
        type: Object,
        default: null,
    },
    currentRequestedCr: {
        type: [Number, String],
        default: 0,
    },
    currentInstalment: {
        type: [Number, String],
        default: 1,
    },
});

// Helper for formatting Cr to Lakh
const toLakh = (cr) => {
    const num = parseFloat(cr) || 0;
    return (num * 100).toFixed(2);
};

// Funding Pattern
const fundingPattern = computed(() => {
    return props.scheme?.funding_pattern || `${props.scheme?.central_share_pct || 90}/${props.scheme?.state_share_pct || 10}`;
});

// Total Sanctioned Cost in Cr
const totalCostCr = computed(() => {
    const val = parseFloat(props.scheme?.sanctioned_amount_cr || 0);
    if (val > 0) return val.toFixed(2);
    if (props.scheme?.estimated_cost_lakh) return (parseFloat(props.scheme.estimated_cost_lakh) / 100).toFixed(2);
    return '0.00';
});

// Central Share Entitlement
const centralEntitlementCr = computed(() => {
    if (props.scheme?.central_share_entitlement_cr !== undefined) {
        return parseFloat(props.scheme.central_share_entitlement_cr).toFixed(2);
    }
    const cost = parseFloat(totalCostCr.value) || 0;
    const cPct = parseFloat((fundingPattern.value.split('/')[0])) || 90;
    return (cost * (cPct / 100)).toFixed(2);
});

// State Share Entitlement
const stateEntitlementCr = computed(() => {
    if (props.scheme?.state_share_entitlement_cr !== undefined) {
        return parseFloat(props.scheme.state_share_entitlement_cr).toFixed(2);
    }
    const cost = parseFloat(totalCostCr.value) || 0;
    const sPct = parseFloat((fundingPattern.value.split('/')[1])) || 10;
    return (cost * (sPct / 100)).toFixed(2);
});

// Total MoJS Released to Date
const mojsReleasedCr = computed(() => {
    if (props.scheme?.approved_claims_release_cr !== undefined && parseFloat(props.scheme.approved_claims_release_cr) > 0) {
        return parseFloat(props.scheme.approved_claims_release_cr).toFixed(2);
    }
    if (props.scheme?.released_central_share_cr !== undefined) {
        return parseFloat(props.scheme.released_central_share_cr).toFixed(2);
    }
    return '0.00';
});

// Total Deductions / Curtailments to Date
const mojsCurtailedCr = computed(() => {
    if (props.scheme?.curtailed_amount_cr !== undefined) {
        return parseFloat(props.scheme.curtailed_amount_cr).toFixed(2);
    }
    return '0.00';
});

// Balance Central Share Left
const balanceCentralCr = computed(() => {
    if (props.scheme?.balance_central_share_cr !== undefined) {
        return parseFloat(props.scheme.balance_central_share_cr).toFixed(2);
    }
    const total = parseFloat(centralEntitlementCr.value) || 0;
    const rel = parseFloat(mojsReleasedCr.value) || 0;
    return Math.max(0, total - rel).toFixed(2);
});

// Progress %
const centralDrawnPct = computed(() => {
    const total = parseFloat(centralEntitlementCr.value) || 0;
    const rel = parseFloat(mojsReleasedCr.value) || 0;
    if (total <= 0) return 0;
    return Math.min(100, Math.round((rel / total) * 100));
});

// Prior Approved Claims
const pastClaims = computed(() => {
    return props.scheme?.past_approved_claims || [];
});

const hasPastClaims = computed(() => {
    return pastClaims.value && pastClaims.value.length > 0;
});

// Validation against current typed request
const requestedNum = computed(() => {
    return parseFloat(props.currentRequestedCr) || 0;
});

const isOverclaim = computed(() => {
    const bal = parseFloat(balanceCentralCr.value) || 0;
    return requestedNum.value > 0 && bal > 0 && requestedNum.value > bal;
});

const balanceAfterClaimCr = computed(() => {
    const bal = parseFloat(balanceCentralCr.value) || 0;
    return (bal - requestedNum.value).toFixed(2);
});
</script>

<template>
    <div v-if="scheme" class="rounded-2xl border-2 border-indigo-200/90 bg-gradient-to-br from-white via-indigo-50/20 to-blue-50/30 p-5 shadow-sm space-y-5 transition-all">
        <!-- Header Banner -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-indigo-100 pb-3.5">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-blue-600 text-white flex items-center justify-center font-bold text-lg shadow-md shrink-0">
                    🏛️
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h4 class="text-sm font-extrabold text-slate-900 tracking-tight">
                            MoJS Sanction Audit Ledger & Central Release Standing
                        </h4>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-900 border border-blue-200">
                            PFMS / DoE Aligned
                        </span>
                        <span v-if="hasPastClaims" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            {{ pastClaims.length }} Prior Release(s) Sanctioned
                        </span>
                        <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-300">
                            Initial Release Claim
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Statutory tracking of Ministry of Jal Shakti approvals, tranches disbursed, curtailments, and remaining balance eligibility.
                    </p>
                </div>
            </div>

            <!-- Badges -->
            <div class="flex items-center gap-2 self-start sm:self-center shrink-0">
                <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-800 shadow-2xs">
                    {{ scheme.scheme_code }}
                </span>
                <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-indigo-100 text-indigo-900 border border-indigo-200">
                    Pattern: {{ fundingPattern }}
                </span>
            </div>
        </div>

        <!-- 4 Key Financial Metrics (Dashboard Grid) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
            <!-- 1. Central Share Entitlement -->
            <div class="bg-white/90 p-3.5 rounded-xl border border-blue-200 shadow-2xs hover:border-blue-300 transition">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider">Central Entitlement</span>
                    <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-blue-100 text-blue-800">
                        {{ fundingPattern.split('/')[0] }}% Share
                    </span>
                </div>
                <div class="text-lg font-black text-slate-900">
                    ₹{{ centralEntitlementCr }} <span class="text-xs font-bold text-slate-500">Cr</span>
                </div>
                <div class="text-[11px] font-semibold text-blue-600 mt-0.5">
                    = ₹{{ toLakh(centralEntitlementCr) }} Lakh
                </div>
                <div class="text-[10px] text-slate-400 mt-1">
                    of ₹{{ totalCostCr }} Cr approved cost
                </div>
            </div>

            <!-- 2. MoJS Sanctioned & Funded -->
            <div class="bg-white/90 p-3.5 rounded-xl border border-emerald-200 shadow-2xs hover:border-emerald-300 transition">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Funded by MoJS</span>
                    <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800">
                        Disbursed
                    </span>
                </div>
                <div class="text-lg font-black text-emerald-800">
                    ₹{{ mojsReleasedCr }} <span class="text-xs font-bold text-emerald-600">Cr</span>
                </div>
                <div class="text-[11px] font-semibold text-emerald-700 mt-0.5">
                    = ₹{{ toLakh(mojsReleasedCr) }} Lakh
                </div>
                <div class="text-[10px] text-slate-400 mt-1">
                    {{ hasPastClaims ? `${pastClaims.length} approved instalment(s)` : 'Baseline / prior releases' }}
                </div>
            </div>

            <!-- 3. Ministry Curtailments / Deductions -->
            <div class="bg-white/90 p-3.5 rounded-xl border border-amber-200 shadow-2xs hover:border-amber-300 transition">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">MoJS Curtailments</span>
                    <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-amber-100 text-amber-800">
                        Withheld
                    </span>
                </div>
                <div class="text-lg font-black text-amber-800">
                    ₹{{ mojsCurtailedCr }} <span class="text-xs font-bold text-amber-600">Cr</span>
                </div>
                <div class="text-[11px] font-semibold text-amber-700 mt-0.5">
                    = ₹{{ toLakh(mojsCurtailedCr) }} Lakh
                </div>
                <div class="text-[10px] text-slate-400 mt-1">
                    Deductions per inspection / SNA rules
                </div>
            </div>

            <!-- 4. Central Balance Left -->
            <div class="bg-gradient-to-br from-indigo-900 to-blue-900 p-3.5 rounded-xl border border-indigo-700 text-white shadow-md relative overflow-hidden">
                <div class="absolute -right-3 -bottom-3 opacity-10 text-4xl select-none font-bold">
                    ₹
                </div>
                <div class="flex items-center justify-between mb-1 relative z-10">
                    <span class="text-[10px] font-bold text-indigo-200 uppercase tracking-wider">Balance Left</span>
                    <span class="text-[9px] font-extrabold px-1.5 py-0.2 rounded bg-emerald-400 text-slate-950">
                        Eligible Ceiling
                    </span>
                </div>
                <div class="text-xl font-black text-white relative z-10">
                    ₹{{ balanceCentralCr }} <span class="text-xs font-bold text-indigo-200">Cr</span>
                </div>
                <div class="text-[11px] font-bold text-indigo-200 mt-0.5 relative z-10">
                    = ₹{{ toLakh(balanceCentralCr) }} Lakh
                </div>
                <div class="text-[10px] text-indigo-300 mt-1 relative z-10">
                    Available for current & future claims
                </div>
            </div>
        </div>

        <!-- Cumulative Progress Bar -->
        <div class="bg-white/80 p-3 rounded-xl border border-indigo-100">
            <div class="flex justify-between items-center text-xs font-semibold mb-1.5">
                <span class="text-slate-600 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                    Central Share Cumulative Drawing Status
                </span>
                <span class="font-bold text-indigo-950">
                    {{ centralDrawnPct }}% Drawn (₹{{ mojsReleasedCr }} Cr of ₹{{ centralEntitlementCr }} Cr)
                    <span class="text-emerald-700 font-extrabold ml-1.5">• Remaining: ₹{{ balanceCentralCr }} Cr ({{ 100 - centralDrawnPct }}%)</span>
                </span>
            </div>
            <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden flex shadow-inner">
                <div 
                    class="bg-gradient-to-r from-blue-600 to-indigo-600 h-2.5 transition-all duration-500 rounded-l-full" 
                    :style="{ width: `${centralDrawnPct}%` }"
                    :title="`Drawn: ${centralDrawnPct}%`"
                ></div>
                <div 
                    class="bg-emerald-400 h-2.5 transition-all duration-500" 
                    :style="{ width: `${100 - centralDrawnPct}%` }"
                    :title="`Remaining: ${100 - centralDrawnPct}%`"
                ></div>
            </div>
        </div>

        <!-- Prior Approved Instalment Claims Audit Table -->
        <div v-if="hasPastClaims" class="space-y-2.5">
            <div class="flex items-center justify-between">
                <h5 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                    <span>📋</span> Prior Ministry Sanctions & Released Instalments Ledger
                </h5>
                <span class="text-[11px] text-slate-500">
                    Official Central Sanctions for this Scheme
                </span>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-2xs">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/90 text-slate-600 font-bold border-b border-slate-200 text-[11px] uppercase tracking-wider">
                            <th class="py-2.5 px-3">Instalment #</th>
                            <th class="py-2.5 px-3">State Claim</th>
                            <th class="py-2.5 px-3 text-emerald-800">MoJS Approved</th>
                            <th class="py-2.5 px-3 text-amber-800">Curtailment</th>
                            <th class="py-2.5 px-3">MoJS Sanction Order</th>
                            <th class="py-2.5 px-3 text-right">Sanction Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <tr v-for="claim in pastClaims" :key="claim.id" class="hover:bg-indigo-50/30 transition">
                            <!-- Instalment Number -->
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center gap-1.5 font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                    Instalment {{ claim.instalment_number || 1 }}
                                </span>
                            </td>

                            <!-- State Claim -->
                            <td class="py-3 px-3">
                                <span class="font-bold text-slate-900">₹{{ claim.requested_amount_cr.toFixed(2) }} Cr</span>
                                <span class="text-[10px] text-slate-500 block">({{ toLakh(claim.requested_amount_cr) }} Lakh)</span>
                            </td>

                            <!-- MoJS Approved -->
                            <td class="py-3 px-3">
                                <span class="font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 inline-block">
                                    ₹{{ claim.approved_amount_cr.toFixed(2) }} Cr
                                </span>
                                <span class="text-[10px] text-emerald-700 block font-semibold">({{ toLakh(claim.approved_amount_cr) }} Lakh)</span>
                            </td>

                            <!-- Curtailment / Deduction -->
                            <td class="py-3 px-3">
                                <div v-if="claim.deduction_amount_cr > 0">
                                    <span class="font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 inline-block">
                                        -₹{{ claim.deduction_amount_cr.toFixed(2) }} Cr
                                    </span>
                                    <span class="text-[10px] text-slate-500 block mt-0.5 font-normal max-w-xs truncate" :title="claim.curtailment_reason || 'Administrative curtailment'">
                                        Reason: {{ claim.curtailment_reason || 'SNA / Progress Shortfall' }}
                                    </span>
                                </div>
                                <span v-else class="text-slate-400 font-medium text-[11px]">
                                    Nil (Full Sanction)
                                </span>
                            </td>

                            <!-- Sanction Order Reference -->
                            <td class="py-3 px-3 font-mono text-[11px]">
                                <div v-if="claim.sanction_order_no">
                                    <span class="font-bold text-indigo-900 block">{{ claim.sanction_order_no }}</span>
                                    <span class="text-[10px] text-slate-500 font-sans block">
                                        Dated: {{ claim.sanction_order_date || claim.approved_at || 'Recent' }}
                                    </span>
                                </div>
                                <span v-else class="text-slate-400 font-sans italic text-[11px]">
                                    Sanction Recorded
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="py-3 px-3 text-right">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    MoJS Sanctioned & Disbursed
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Initial Claim Notice if No Prior Claims -->
        <div v-else class="bg-blue-50/70 border border-blue-200 rounded-xl p-3.5 flex items-center gap-3">
            <span class="text-2xl select-none">💡</span>
            <div class="text-xs text-blue-950">
                <span class="font-bold">Initial Central Assistance Claim:</span>
                No prior fund release claims have been sanctioned under the portal for this scheme yet. 
                The full Central Share entitlement of <strong>₹{{ centralEntitlementCr }} Cr (₹{{ toLakh(centralEntitlementCr) }} Lakh)</strong> is available for claim.
            </div>
        </div>

        <!-- Real-Time Claim Impact & Overclaim Alert Banner -->
        <div v-if="isOverclaim" class="bg-rose-50 border-2 border-rose-300 rounded-xl p-3.5 flex items-start gap-3 animate-pulse">
            <span class="text-xl text-rose-600 select-none">⚠️</span>
            <div class="text-xs text-rose-950">
                <span class="font-extrabold text-rose-800 uppercase block">Overclaim Warning: Amount Exceeds Permissible Balance</span>
                Your requested claim of <strong>₹{{ requestedNum.toFixed(2) }} Cr</strong> exceeds the available Central balance of <strong>₹{{ balanceCentralCr }} Cr</strong> by <strong>₹{{ (requestedNum - parseFloat(balanceCentralCr)).toFixed(2) }} Cr</strong>. Claims exceeding the remaining sanctioned entitlement may be rejected or curtailed by the Brahmaputra Board and Ministry of Jal Shakti.
            </div>
        </div>

        <div v-else-if="requestedNum > 0" class="bg-emerald-50/80 border border-emerald-200 rounded-xl p-3 flex items-center justify-between text-xs text-emerald-950">
            <div class="flex items-center gap-2">
                <span class="text-base select-none">✓</span>
                <span>
                    Requested claim of <strong>₹{{ requestedNum.toFixed(2) }} Cr</strong> is within the eligible Central balance ceiling.
                </span>
            </div>
            <span class="font-bold text-emerald-800 bg-white px-2.5 py-1 rounded-md border border-emerald-200 shadow-2xs">
                Balance after this release: ₹{{ balanceAfterClaimCr }} Cr
            </span>
        </div>
    </div>
</template>
