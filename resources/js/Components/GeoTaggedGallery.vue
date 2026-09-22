<script setup>
import { ref, onMounted, watch, nextTick } from 'vue';
import L from 'leaflet';

const props = defineProps({
    files: {
        type: Array,
        default: () => []
    }
});

const viewMode = ref('grid'); // 'grid' | 'map'
const mapContainer = ref(null);
let map = null;
let markersLayer = null;

const storageUrl = (path) => path;

const initMap = () => {
    if (!mapContainer.value) return;
    if (map) {
        map.remove();
        map = null;
    }

    const validFiles = props.files.filter(f => f.lat && f.lng);
    const defaultCenter = validFiles.length > 0 
        ? [parseFloat(validFiles[0].lat), parseFloat(validFiles[0].lng)]
        : [26.2006, 92.9376];

    map = L.map(mapContainer.value, {
        center: defaultCenter,
        zoom: 12,
        zoomControl: true,
        attributionControl: false
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        maxZoom: 19
    }).addTo(map);

    markersLayer = L.layerGroup().addTo(map);

    const bounds = [];

    validFiles.forEach((file, index) => {
        const lat = parseFloat(file.lat);
        const lng = parseFloat(file.lng);
        if (isNaN(lat) || isNaN(lng)) return;

        bounds.push([lat, lng]);

        const iconHtml = `
            <div class="relative flex items-center justify-center cursor-pointer" style="transform: translate(-50%, -100%);">
                <div class="w-8 h-8 rounded-full bg-[#0F4C9F] border-2 border-white shadow-md flex items-center justify-center text-white text-xs font-bold">
                    <span>📷</span>
                </div>
            </div>
        `;

        const customIcon = L.divIcon({
            className: 'geotag-pin',
            html: iconHtml,
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        });

        const popupContent = `
            <div class="p-1 max-w-[240px]">
                <div class="aspect-video bg-slate-100 rounded overflow-hidden mb-1.5 border">
                    <img src="${storageUrl(file.path)}" class="w-full h-full object-cover" alt="Evidence" />
                </div>
                <div class="text-[10px] font-mono text-slate-500 mb-1">
                    📍 GPS: ${lat.toFixed(5)}, ${lng.toFixed(5)}
                </div>
                ${file.caption ? `<p class="text-xs text-slate-800 font-medium leading-snug">${file.caption}</p>` : ''}
            </div>
        `;

        const marker = L.marker([lat, lng], { icon: customIcon }).bindPopup(popupContent);
        markersLayer.addLayer(marker);
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [30, 30], maxZoom: 15 });
    }
};

const switchView = async (mode) => {
    viewMode.value = mode;
    if (mode === 'map') {
        await nextTick();
        initMap();
    }
};

watch(() => props.files, () => {
    if (viewMode.value === 'map') {
        initMap();
    }
}, { deep: true });
</script>

<template>
    <div class="space-y-3">
        <!-- View Mode Switcher -->
        <div v-if="files && files.length > 0" class="flex items-center justify-between bg-slate-50 p-2 rounded-lg border border-slate-200">
            <div class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                <span>📸</span>
                <span>Geo-Tagged Field Media ({{ files.length }} items)</span>
            </div>

            <div class="inline-flex rounded-md shadow-2xs bg-white border border-slate-300 p-0.5 text-xs">
                <button
                    type="button"
                    @click="switchView('grid')"
                    :class="viewMode === 'grid' ? 'bg-[#0F4C9F] text-white font-bold' : 'text-slate-600 hover:text-slate-900'"
                    class="px-3 py-1 rounded transition text-[11px] flex items-center gap-1"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span>Gallery</span>
                </button>
                <button
                    type="button"
                    @click="switchView('map')"
                    :class="viewMode === 'map' ? 'bg-[#0F4C9F] text-white font-bold' : 'text-slate-600 hover:text-slate-900'"
                    class="px-3 py-1 rounded transition text-[11px] flex items-center gap-1"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    <span>GPS Map View</span>
                </button>
            </div>
        </div>

        <!-- Grid View -->
        <div v-if="files && files.length > 0 && viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 animate-fade-in">
            <div v-for="(file, index) in files" :key="index" class="relative group rounded-lg overflow-hidden border border-slate-200 bg-slate-50 shadow-2xs hover:shadow-md transition">
                <!-- Media -->
                <a v-if="file.type === 'photo'" :href="storageUrl(file.path)" target="_blank" class="block aspect-video bg-slate-200">
                    <img :src="storageUrl(file.path)" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="Geo-tagged Evidence" loading="lazy" />
                </a>
                <div v-else-if="file.type === 'video'" class="aspect-video bg-black">
                    <video :src="storageUrl(file.path)" controls class="w-full h-full object-contain"></video>
                </div>

                <!-- Overlay Badges (Top) -->
                <div class="absolute top-2 left-2 flex flex-col gap-1 pointer-events-none">
                    <span v-if="file.lat || file.lng" class="bg-black/80 text-white text-[10px] font-mono px-2 py-0.5 rounded backdrop-blur-sm flex items-center gap-1">
                        <svg class="w-3 h-3 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                        {{ file.lat || 'N/A' }}, {{ file.lng || 'N/A' }}
                    </span>
                </div>
                
                <!-- Caption (Bottom) -->
                <div v-if="file.caption" class="p-2.5 bg-white border-t border-slate-100">
                    <p class="text-xs text-slate-700 line-clamp-2" :title="file.caption">{{ file.caption }}</p>
                </div>
            </div>
        </div>

        <!-- Interactive GPS Map View -->
        <div v-else-if="files && files.length > 0 && viewMode === 'map'" class="rounded-lg overflow-hidden border border-slate-200 shadow-sm animate-fade-in">
            <div ref="mapContainer" class="w-full h-[380px] z-10"></div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-xs text-slate-500 italic p-6 bg-slate-50 rounded-lg border border-slate-200 border-dashed text-center">
            No field inspection media or geo-tagged evidence uploaded yet.
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.15s ease-out forwards;
}
</style>
