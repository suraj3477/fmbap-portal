<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValue: {
        type: [String, Number, Object],
        default: null
    },
    initialScheme: {
        type: Object,
        default: null
    },
    error: String,
});

const emit = defineEmits(['update:modelValue', 'scheme-selected']);

const searchQuery = ref('');
const results = ref([]);
const loading = ref(false);
const showDropdown = ref(false);
const dropdownRef = ref(null);
let debounceTimeout = null;

const syncInitialValue = () => {
    if (props.initialScheme && (props.initialScheme.scheme_code || props.initialScheme.scheme_name)) {
        searchQuery.value = props.initialScheme.scheme_code || props.initialScheme.scheme_name;
    } else if (props.modelValue && typeof props.modelValue === 'object') {
        searchQuery.value = props.modelValue.scheme_code || '';
    }
};

onMounted(() => {
    syncInitialValue();
    document.addEventListener('click', handleClickOutside);
});

watch(() => props.initialScheme, () => {
    syncInitialValue();
}, { immediate: true, deep: true });

watch(() => props.modelValue, (newVal) => {
    if (newVal && typeof newVal === 'object') {
        searchQuery.value = newVal.scheme_code || '';
    }
}, { immediate: true });

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        showDropdown.value = false;
    }
};

const search = (force = false) => {
    if (debounceTimeout) clearTimeout(debounceTimeout);
    
    // Allow empty query to fetch latest active schemes
    if (!force && searchQuery.value.length === 1) {
        results.value = [];
        showDropdown.value = false;
        return;
    }

    debounceTimeout = setTimeout(async () => {
        loading.value = true;
        try {
            const response = await axios.get(route('schemes.search'), { 
                params: { q: searchQuery.value || '' } 
            });
            results.value = response.data;
            showDropdown.value = true;
        } catch (error) {
            console.error('Error fetching schemes:', error);
        } finally {
            loading.value = false;
        }
    }, 150);
};

const handleFocus = () => {
    if (results.value.length === 0) {
        search(true);
    } else {
        showDropdown.value = true;
    }
};

const selectScheme = (scheme) => {
    searchQuery.value = `${scheme.scheme_code} - ${scheme.scheme_name}`;
    showDropdown.value = false;
    emit('update:modelValue', scheme.id);
    emit('scheme-selected', scheme);
};
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <label class="block text-sm font-semibold text-gray-700 mb-1">
            Select / Search Scheme (Code, Name, or Division) <span class="text-red-500">*</span>
        </label>
        <div class="relative">
            <input 
                type="text" 
                v-model="searchQuery" 
                @input="search(false)"
                @focus="handleFocus"
                class="block w-full rounded-lg shadow-sm pl-10 pr-10 py-2.5 transition-colors text-sm"
                :class="error ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'"
                placeholder="Click to browse schemes or type code (e.g. AS-13)..."
                autocomplete="off"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg v-if="!loading" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <svg v-else class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <div v-if="searchQuery" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <button type="button" @click="searchQuery = ''; search(true);" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>

        <!-- Dropdown -->
        <div v-if="showDropdown && results.length > 0" class="absolute z-50 w-full mt-1 bg-white rounded-xl shadow-xl border border-gray-200 max-h-72 overflow-y-auto divide-y divide-gray-100">
            <div class="p-2 bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider flex justify-between">
                <span>Available Schemes ({{ results.length }})</span>
                <span>Click to select</span>
            </div>
            <div 
                v-for="scheme in results" 
                :key="scheme.id"
                @click="selectScheme(scheme)"
                class="p-3 hover:bg-blue-50/80 cursor-pointer transition flex items-start justify-between gap-3 group"
            >
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200 font-mono">
                            {{ scheme.scheme_code }}
                        </span>
                        <span v-if="scheme.division" class="text-xs text-gray-500 font-medium">
                            • {{ scheme.division }}
                        </span>
                        <span v-if="scheme.plan_period" class="text-[11px] px-1.5 py-0.2 bg-gray-100 text-gray-600 rounded">
                            {{ scheme.plan_period }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-800 font-medium line-clamp-1 group-hover:text-blue-900">
                        {{ scheme.scheme_name }}
                    </p>
                </div>
                <div class="text-right shrink-0">
                    <div class="text-xs font-extrabold text-gray-900">
                        ₹{{ scheme.sanctioned_amount_cr || (scheme.estimated_cost_lakh ? (scheme.estimated_cost_lakh/100).toFixed(2) : '0.00') }} Cr
                    </div>
                    <div class="text-[10px] text-gray-400">
                        {{ scheme.estimated_cost_lakh ? `₹${scheme.estimated_cost_lakh} L` : '' }}
                    </div>
                </div>
            </div>
        </div>
        <div v-else-if="showDropdown && !loading && results.length === 0" class="absolute z-50 w-full mt-1 bg-white rounded-xl shadow-lg border border-gray-200 p-4 text-center text-xs text-gray-500">
            No schemes found. Please add or import schemes first.
        </div>
    </div>
</template>
