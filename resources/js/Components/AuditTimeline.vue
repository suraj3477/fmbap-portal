<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    module: {
        type: String,
        required: true,
        // e.g., 'fund-release' or 'progress-reports'
    },
    recordId: {
        type: [Number, String],
        required: true,
    }
});

const logs = ref([]);
const loading = ref(true);
const error = ref(null);

onMounted(async () => {
    try {
        const response = await axios.get(`/${props.module}/${props.recordId}/audit`);
        logs.value = response.data;
    } catch (e) {
        error.value = 'Failed to load audit trail.';
        console.error(e);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Audit Trail
        </h3>

        <div v-if="loading" class="text-sm text-gray-500 animate-pulse">Loading history...</div>
        <div v-else-if="error" class="text-sm text-red-500">{{ error }}</div>
        <div v-else-if="logs.length === 0" class="text-sm text-gray-500 italic">No activity recorded yet.</div>
        
        <div v-else class="relative border-l border-gray-200 ml-3 space-y-6">
            <div v-for="log in logs" :key="log.id" class="relative pl-6">
                <!-- Icon -->
                <span class="absolute -left-3.5 flex items-center justify-center w-7 h-7 bg-gray-100 rounded-full ring-4 ring-white shadow-sm text-sm">
                    {{ log.icon }}
                </span>
                
                <!-- Content -->
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ log.action }}
                            <span v-if="log.field_name" class="font-normal text-gray-500">
                                — Updated <span class="font-medium text-gray-700">{{ log.field_name }}</span>
                            </span>
                        </p>
                        
                        <!-- Value Changes -->
                        <div v-if="log.old_value || log.new_value" class="mt-1 flex items-center gap-2 text-xs">
                            <span v-if="log.old_value" class="line-through text-gray-400 bg-gray-50 px-1 rounded truncate max-w-[150px]">{{ log.old_value }}</span>
                            <svg v-if="log.old_value" class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            <span v-if="log.new_value" class="text-green-700 bg-green-50 px-1 rounded truncate max-w-[200px]">{{ log.new_value }}</span>
                        </div>

                        <!-- Remarks -->
                        <div v-if="log.remarks" class="mt-2 text-sm text-gray-600 bg-gray-50 p-2 rounded border border-gray-100 italic">
                            "{{ log.remarks }}"
                        </div>
                    </div>

                    <!-- Meta -->
                    <div class="text-right flex flex-col items-end">
                        <span class="text-xs font-medium text-gray-900">{{ log.user }}</span>
                        <span class="text-xs text-gray-500" :title="log.date">{{ log.time_ago }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
