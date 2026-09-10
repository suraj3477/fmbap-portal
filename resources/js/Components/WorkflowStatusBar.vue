<script setup>
defineProps({
    steps: {
        type: Array,
        required: true,
        // Array of objects: { key: 'DRAFT', label: 'Draft', icon: '📝', status: 'completed|active|pending' }
    }
});
</script>

<template>
    <div class="py-2">
        <div class="flex items-center justify-between relative">
            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-0.5 bg-slate-200 z-0"></div>
            
            <div v-for="(step, index) in steps" :key="index" class="relative z-10 flex flex-col items-center group w-1/5">
                <div 
                    class="w-8 h-8 flex items-center justify-center rounded-sm text-sm border shadow-xs bg-white transition-colors duration-200"
                    :class="{
                        'border-emerald-600 bg-emerald-50 text-emerald-700': step.status === 'completed',
                        'border-blue-600 bg-blue-50 ring-2 ring-blue-100 text-blue-700 font-bold': step.status === 'active',
                        'border-slate-300 text-slate-400': step.status === 'pending'
                    }"
                >
                    <span v-if="step.status === 'completed'" class="text-emerald-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    </span>
                    <span v-else>{{ step.icon }}</span>
                </div>
                
                <div class="mt-1.5 text-center">
                    <p 
                        class="text-[11px] font-semibold whitespace-nowrap leading-none"
                        :class="{
                            'text-emerald-700': step.status === 'completed',
                            'text-blue-700 font-bold': step.status === 'active',
                            'text-slate-500': step.status === 'pending'
                        }"
                    >
                        {{ step.label }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

