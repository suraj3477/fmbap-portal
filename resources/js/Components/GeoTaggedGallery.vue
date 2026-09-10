<script setup>
defineProps({
    files: {
        type: Array,
        required: true,
        // Array of { path: string, type: 'photo'|'video', lat: string, lng: string, caption: string }
    }
});

const storageUrl = (path) => path; // Assuming path is already formatted correctly (e.g., starts with /storage/)
</script>

<template>
    <div v-if="files && files.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        <div v-for="(file, index) in files" :key="index" class="relative group rounded-lg overflow-hidden border border-gray-200 bg-gray-50 shadow-sm hover:shadow-md transition-shadow">
            
            <!-- Media -->
            <a v-if="file.type === 'photo'" :href="storageUrl(file.path)" target="_blank" class="block aspect-video bg-gray-200">
                <img :src="storageUrl(file.path)" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="Geo-tagged Evidence" loading="lazy" />
            </a>
            <div v-else-if="file.type === 'video'" class="aspect-video bg-black">
                <video :src="storageUrl(file.path)" controls class="w-full h-full object-contain"></video>
            </div>

            <!-- Overlay Badges (Top) -->
            <div class="absolute top-2 left-2 flex flex-col gap-1 pointer-events-none">
                <span v-if="file.lat || file.lng" class="bg-black/70 text-white text-[10px] font-mono px-2 py-0.5 rounded backdrop-blur-sm flex items-center gap-1">
                    <svg class="w-3 h-3 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                    {{ file.lat || 'N/A' }}, {{ file.lng || 'N/A' }}
                </span>
            </div>
            
            <!-- Caption (Bottom) -->
            <div v-if="file.caption" class="p-3 bg-white border-t border-gray-100">
                <p class="text-xs text-gray-700 line-clamp-2" :title="file.caption">{{ file.caption }}</p>
            </div>
        </div>
    </div>
    <div v-else class="text-sm text-gray-500 italic p-4 bg-gray-50 rounded-lg border border-gray-200 border-dashed text-center">
        No field evidence uploaded.
    </div>
</template>
