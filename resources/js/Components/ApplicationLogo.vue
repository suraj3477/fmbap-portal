<script setup>
defineProps({
    class: {
        type: String,
        default: 'h-12 w-auto'
    }
});
</script>

<template>
    <img
        src="/logo.png"
        alt="Brahmaputra Board Logo"
        :class="['object-contain shrink-0 drop-shadow-xs transition-transform duration-200', $props.class]"
    />
</template>
