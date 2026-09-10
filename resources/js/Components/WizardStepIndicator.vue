<script setup>
defineProps({
    steps: {
        type: Array,
        required: true
    },
    currentStep: {
        type: Number,
        required: true
    }
});
</script>

<template>
    <div class="mb-4">
        <h2 class="sr-only">Steps</h2>
        <div class="relative after:absolute after:inset-x-0 after:top-1/2 after:block after:h-0.5 after:-translate-y-1/2 after:bg-slate-200">
            <ol class="relative z-10 flex justify-between text-xs font-semibold text-slate-600">
                <li v-for="(step, index) in steps" :key="index" class="flex items-center gap-2 bg-white px-2 py-1 border border-slate-200 rounded-sm">
                    <span
                        class="flex h-6 w-6 items-center justify-center rounded-sm text-white font-bold shadow-xs text-xs"
                        :class="currentStep > index + 1 ? 'bg-slate-800' : (currentStep === index + 1 ? 'bg-blue-600' : 'bg-slate-300')"
                    >
                        <svg v-if="currentStep > index + 1" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span v-else>{{ index + 1 }}</span>
                    </span>
                    <span class="hidden sm:block text-xs" :class="{ 'text-blue-600 font-bold': currentStep === index + 1, 'text-slate-900': currentStep > index + 1 }">
                        {{ step }}
                    </span>
                </li>
            </ol>
        </div>
    </div>
</template>

