<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-sm border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-slate-700 focus:ring-slate-700 leading-snug"
        v-model="model"
        ref="input"
    />
</template>

