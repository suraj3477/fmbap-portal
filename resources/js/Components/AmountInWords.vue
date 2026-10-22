<script setup>
import { computed } from 'vue';
import { numberToWordsIndian, formatIndianCurrency, formatLakhAndCrore } from '@/Utils/numberToWords';

const props = defineProps({
    amount: {
        type: [Number, String],
        default: 0,
    },
    label: {
        type: String,
        default: 'In Words',
    },
    showBreakdown: {
        type: Boolean,
        default: true,
    },
});

const words = computed(() => numberToWordsIndian(props.amount));
const formattedINR = computed(() => formatIndianCurrency(props.amount));
const lakhAndCr = computed(() => formatLakhAndCrore(props.amount));
const hasAmount = computed(() => {
    const n = parseFloat(props.amount);
    return !isNaN(n) && n > 0;
});
</script>

<template>
    <div v-if="hasAmount" class="mt-2 p-2.5 bg-gradient-to-r from-slate-50 to-indigo-50/50 border border-indigo-200/80 rounded-lg text-xs space-y-1 transition-all shadow-2xs">
        <div class="flex items-start gap-1.5 leading-tight">
            <span class="text-indigo-700 shrink-0 font-extrabold uppercase text-[10px] tracking-wider bg-indigo-100 px-1.5 py-0.5 rounded border border-indigo-200">
                🗣️ {{ label }}
            </span>
            <span class="text-indigo-950 font-bold font-serif italic text-xs tracking-wide">
                {{ words }}
            </span>
        </div>
        <div v-if="showBreakdown" class="flex items-center gap-2 text-[11px] text-slate-600 font-semibold pl-1">
            <span class="font-mono text-slate-900 font-bold">₹{{ formattedINR }}</span>
            <span class="text-slate-300">•</span>
            <span class="text-indigo-700 bg-white px-2 py-0.5 rounded border border-indigo-200 font-mono font-bold">{{ lakhAndCr }}</span>
        </div>
    </div>
</template>
