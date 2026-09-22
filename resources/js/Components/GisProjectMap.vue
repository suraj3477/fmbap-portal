<script setup>
import { ref, onMounted, watch, computed, nextTick } from 'vue';
import L from 'leaflet';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    schemes: {
        type: Array,
        default: () => []
    },
    height: {
        type: String,
        default: '580px'
    }
});

const mapContainer = ref(null);
let map = null;
let markersLayer = null;
const markerMap = new Map(); // scheme id -> leaflet marker

// State & Filters
const selectedState = ref('ALL');
const selectedStatus = ref('ALL');
const selectedBasin = ref('ALL');
const searchQuery = ref('');
const activeTileLayer = ref('topo');
const selectedScheme = ref(null);
const showGuide = ref(false);
const viewMode = ref('split'); // 'split' or 'full'

// District / State Reference Coordinates in North-East & River Basins
const locationGeoMap = {
    'assam': { lat: 26.2006, lng: 92.9376 },
    'kamrup': { lat: 26.1445, lng: 91.7362 },
    'guwahati': { lat: 26.1856, lng: 91.7477 },
    'dibrugarh': { lat: 27.4728, lng: 94.9120 },
    'majuli': { lat: 26.9634, lng: 94.2212 },
    'barpeta': { lat: 26.3216, lng: 91.0063 },
    'dhubri': { lat: 26.0207, lng: 89.9742 },
    'silchar': { lat: 24.8333, lng: 92.7789 },
    'cachar': { lat: 24.8100, lng: 92.8000 },
    'dhemaji': { lat: 27.4816, lng: 94.5824 },
    'lakhimpur': { lat: 27.2346, lng: 94.1037 },
    'jorhat': { lat: 26.7509, lng: 94.2037 },
    'sivasagar': { lat: 26.9826, lng: 94.6425 },
    'tinsukia': { lat: 27.4922, lng: 95.3468 },
    'morigaon': { lat: 26.2575, lng: 92.3424 },
    'nagaon': { lat: 26.3476, lng: 92.6840 },
    'goalpara': { lat: 26.1667, lng: 90.6167 },
    'bongaigaon': { lat: 26.4797, lng: 90.5595 },
    'kokrajhar': { lat: 26.4014, lng: 90.2718 },
    'sonitpur': { lat: 26.6528, lng: 92.7926 },
    'tezpur': { lat: 26.6338, lng: 92.8006 },
    'biswanath': { lat: 26.7329, lng: 93.1491 },
    'golaghat': { lat: 26.5167, lng: 93.9667 },
    'chirang': { lat: 26.5414, lng: 90.4908 },
    'baksa': { lat: 26.6784, lng: 91.3556 },
    'udalguri': { lat: 26.7452, lng: 92.0962 },
    'darrang': { lat: 26.4525, lng: 92.0296 },
    'nalbari': { lat: 26.4447, lng: 91.4398 },
    'karbi anglong': { lat: 26.1522, lng: 93.4353 },
    'dima hasao': { lat: 25.1833, lng: 93.0167 },
    'karimganj': { lat: 24.8667, lng: 92.3500 },
    'hailakandi': { lat: 24.6833, lng: 92.5667 },
    'meghalaya': { lat: 25.5788, lng: 91.8933 },
    'shillong': { lat: 25.5788, lng: 91.8933 },
    'east khasi hills': { lat: 25.5700, lng: 91.8800 },
    'west garo hills': { lat: 25.5167, lng: 90.2167 },
    'tura': { lat: 25.5144, lng: 90.2201 },
    'arunachal pradesh': { lat: 28.2180, lng: 94.7278 },
    'itanagar': { lat: 27.0844, lng: 93.6053 },
    'pasighat': { lat: 28.0667, lng: 95.3333 },
    'manipur': { lat: 24.6637, lng: 93.9063 },
    'imphal': { lat: 24.8170, lng: 93.9368 },
    'mizoram': { lat: 23.1645, lng: 92.9376 },
    'aizawl': { lat: 23.7271, lng: 92.7176 },
    'nagaland': { lat: 26.1584, lng: 94.5624 },
    'kohima': { lat: 25.6701, lng: 94.1077 },
    'dimapur': { lat: 25.9094, lng: 93.7266 },
    'tripura': { lat: 23.9408, lng: 91.9882 },
    'agartala': { lat: 23.8315, lng: 91.2868 },
    'sikkim': { lat: 27.5330, lng: 88.5122 },
    'gangtok': { lat: 27.3389, lng: 88.6065 },
    'west bengal': { lat: 26.5400, lng: 88.7199 },
    'jalpaiguri': { lat: 26.5400, lng: 88.7199 },
    'cooch behar': { lat: 26.3239, lng: 89.4510 },
    'alipurduar': { lat: 26.4919, lng: 89.5271 },
    'siliguri': { lat: 26.7271, lng: 88.3953 }
};

const getSchemeCoords = (scheme, index) => {
    if (scheme.latitude && scheme.longitude) {
        return [parseFloat(scheme.latitude), parseFloat(scheme.longitude)];
    }
    const districtKey = (scheme.district || '').toLowerCase().trim();
    const divisionKey = (scheme.division || '').toLowerCase().trim();
    const stateKey = (scheme.state || 'assam').toLowerCase().trim();

    let base = locationGeoMap[districtKey] || locationGeoMap[divisionKey] || locationGeoMap[stateKey] || { lat: 26.2006, lng: 92.9376 };

    // Add deterministic micro jitter so overlapping pins in same district fan out neatly
    const seed = (scheme.id || index * 17) % 100;
    const offsetLat = ((seed % 10) - 5) * 0.035;
    const offsetLng = (Math.floor(seed / 10) - 5) * 0.035;

    return [base.lat + offsetLat, base.lng + offsetLng];
};

const availableStates = computed(() => {
    const states = new Set(props.schemes.map(s => s.state).filter(Boolean));
    return ['ALL', ...Array.from(states)];
});

const availableBasins = computed(() => {
    const basins = new Set(props.schemes.map(s => s.river_basin).filter(Boolean));
    return Array.from(basins);
});

const filteredSchemes = computed(() => {
    return props.schemes.filter(s => {
        const matchesState = selectedState.value === 'ALL' || s.state === selectedState.value;
        const matchesBasin = selectedBasin.value === 'ALL' || s.river_basin === selectedBasin.value;
        const matchesStatus = selectedStatus.value === 'ALL' ||
            (selectedStatus.value === 'Completed' && (s.physical_status === 'Completed' || parseFloat(s.physical_progress_pct) >= 90)) ||
            (selectedStatus.value === 'Ongoing' && (s.physical_status !== 'Completed' && parseFloat(s.physical_progress_pct) < 90));
        
        const q = searchQuery.value.toLowerCase().trim();
        const matchesQuery = !q ||
            (s.scheme_code && s.scheme_code.toLowerCase().includes(q)) ||
            (s.scheme_name && s.scheme_name.toLowerCase().includes(q)) ||
            (s.district && s.district.toLowerCase().includes(q)) ||
            (s.river_basin && s.river_basin.toLowerCase().includes(q));

        return matchesState && matchesBasin && matchesStatus && matchesQuery;
    });
});

// Summary stats for the current view
const stats = computed(() => {
    const total = filteredSchemes.value.length;
    let completed = 0;
    let ongoing = 0;
    let early = 0;
    let totalOutlay = 0;

    filteredSchemes.value.forEach(s => {
        const p = parseFloat(s.physical_progress_pct) || 0;
        if (s.physical_status === 'Completed' || p >= 75) {
            completed++;
        } else if (p >= 40) {
            ongoing++;
        } else {
            early++;
        }
        totalOutlay += parseFloat(s.sanctioned_amount_cr) || 0;
    });

    return {
        total,
        completed,
        ongoing,
        early,
        totalOutlay: totalOutlay.toFixed(2)
    };
});

const createCustomPin = (scheme, isSelected = false) => {
    const progress = Math.round(parseFloat(scheme.physical_progress_pct) || 0);
    const isCompleted = scheme.physical_status === 'Completed' || progress >= 75;
    const isOngoing = progress >= 40 && !isCompleted;

    let pinColor = '#2563EB'; // Blue (Early Stage <40%)
    let label = 'Early';

    if (isCompleted) {
        pinColor = '#10B981'; // Green (Completed >75%)
        label = 'Done';
    } else if (isOngoing) {
        pinColor = '#F59E0B'; // Amber (Ongoing 40-74%)
        label = 'Work';
    }

    const ringEffect = isSelected
        ? 'ring-4 ring-blue-400 scale-125 shadow-2xl animate-bounce'
        : 'shadow-md group-hover:scale-110';

    const html = `
        <div class="relative flex flex-col items-center cursor-pointer group" style="transform: translate(-50%, -100%);">
            <div style="background-color: ${pinColor};" class="px-2 py-0.5 rounded-full text-white text-[10px] font-black tracking-tight border-2 border-white flex items-center gap-1 transition-all duration-200 ${ringEffect}">
                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                <span>${progress}%</span>
            </div>
            <div style="border-top-color: ${pinColor};" class="w-0 h-0 border-x-4 border-x-transparent border-t-5 -mt-0.5"></div>
        </div>
    `;

    return L.divIcon({
        className: 'custom-gis-pin',
        html: html,
        iconSize: [46, 32],
        iconAnchor: [23, 32],
        popupAnchor: [0, -32]
    });
};

const tileLayers = {
    topo: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles © Esri — Esri Topographic River Basins',
        maxZoom: 18
    }),
    satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles © Esri — Source: Maxar Satellite Imagery',
        maxZoom: 18
    }),
    osm: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    })
};

const setTileLayer = (type) => {
    activeTileLayer.value = type;
    if (!map) return;
    Object.values(tileLayers).forEach(layer => {
        if (map.hasLayer(layer)) {
            map.removeLayer(layer);
        }
    });
    tileLayers[type].addTo(map);
};

const updateMarkers = () => {
    if (!map || !markersLayer) return;
    markersLayer.clearLayers();
    markerMap.clear();

    const bounds = [];

    filteredSchemes.value.forEach((scheme, index) => {
        const coords = getSchemeCoords(scheme, index);
        bounds.push(coords);

        const isSelected = selectedScheme.value?.id === scheme.id;
        const marker = L.marker(coords, {
            icon: createCustomPin(scheme, isSelected)
        });

        // Hover Tooltip: Instantly understandable without clicking
        const progress = Math.round(parseFloat(scheme.physical_progress_pct) || 0);
        marker.bindTooltip(`
            <div class="font-sans text-xs">
                <strong class="text-slate-900">${scheme.district || scheme.state || 'Assam'}</strong>: 
                <span class="text-slate-600">${scheme.scheme_name?.substring(0, 40)}...</span> 
                <span class="font-bold text-blue-700">(${progress}% Done)</span>
            </div>
        `, { direction: 'top', offset: [0, -28] });

        // Popup Content
        const sanctioned = scheme.sanctioned_amount_cr ? `₹${parseFloat(scheme.sanctioned_amount_cr).toFixed(2)} Cr` : '₹0.00 Cr';
        const isCompleted = scheme.physical_status === 'Completed' || progress >= 75;
        const statusBadgeClass = isCompleted ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800';

        const popupContent = `
            <div style="font-family: inherit;" class="p-1 min-w-[280px] max-w-[340px]">
                <div class="flex items-center justify-between gap-2 border-b border-slate-200 pb-2 mb-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-blue-100 text-blue-900 uppercase">
                        ${scheme.scheme_code || 'FMBAP-SCHEME'}
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${statusBadgeClass}">
                        ${isCompleted ? 'Completed / Functional' : 'Under Construction'}
                    </span>
                </div>
                <h4 class="text-xs font-bold text-slate-900 leading-snug mb-2">
                    ${scheme.scheme_name || 'Anti-Erosion & Flood Protection Project'}
                </h4>
                <div class="grid grid-cols-2 gap-2 text-[11px] bg-slate-50 p-2.5 rounded-lg border border-slate-200 mb-2.5">
                    <div>
                        <span class="text-slate-500 block text-[9px] uppercase font-bold">📍 District & State</span>
                        <strong class="text-slate-800 font-semibold">${scheme.district || scheme.division || 'Main Basin'}, ${scheme.state || 'Assam'}</strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[9px] uppercase font-bold">🌊 River Basin</span>
                        <strong class="text-blue-700 font-semibold">${scheme.river_basin || 'Brahmaputra'}</strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[9px] uppercase font-bold">💰 Approved Cost</span>
                        <strong class="text-slate-900 font-bold">${sanctioned}</strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[9px] uppercase font-bold">📊 Progress</span>
                        <strong class="${isCompleted ? 'text-emerald-700' : 'text-amber-700'} font-bold">${progress}% Completed</strong>
                    </div>
                </div>
                <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden mb-3">
                    <div class="${isCompleted ? 'bg-emerald-500' : 'bg-amber-500'} h-full rounded-full transition-all duration-300" style="width: ${progress}%"></div>
                </div>
                <a href="/schemes" class="block text-center py-2 px-3 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white text-xs font-bold rounded-lg shadow-xs transition">
                    View Complete Scheme Baseline & Reports &rarr;
                </a>
            </div>
        `;

        marker.bindPopup(popupContent, { maxWidth: 360 });

        marker.on('click', () => {
            selectedScheme.value = scheme;
        });

        markerMap.set(scheme.id, marker);
        markersLayer.addLayer(marker);
    });

    if (bounds.length > 0 && map && !selectedScheme.value) {
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 9 });
    }
};

const selectScheme = (scheme) => {
    selectedScheme.value = scheme;
    if (!map) return;

    const marker = markerMap.get(scheme.id);
    if (marker) {
        const coords = marker.getLatLng();
        map.flyTo([coords.lat, coords.lng], 10, {
            animate: true,
            duration: 1
        });
        setTimeout(() => {
            marker.openPopup();
        }, 800);
    }
};

const resetZoom = () => {
    selectedScheme.value = null;
    selectedBasin.value = 'ALL';
    selectedState.value = 'ALL';
    selectedStatus.value = 'ALL';
    searchQuery.value = '';
    if (!map) return;
    map.setView([26.2006, 92.9376], 7);
};

const toggleViewMode = () => {
    viewMode.value = viewMode.value === 'split' ? 'full' : 'split';
    nextTick(() => {
        if (map) {
            map.invalidateSize();
        }
    });
};

onMounted(() => {
    if (!mapContainer.value) return;

    map = L.map(mapContainer.value, {
        center: [26.2006, 92.9376],
        zoom: 7,
        zoomControl: false,
        attributionControl: true
    });

    L.control.zoom({ position: 'topright' }).addTo(map);

    tileLayers.topo.addTo(map);
    markersLayer = L.layerGroup().addTo(map);

    updateMarkers();
});

watch([selectedState, selectedStatus, selectedBasin, searchQuery, () => props.schemes], () => {
    updateMarkers();
}, { deep: true });
</script>

<template>
    <div class="bg-white rounded-xl shadow-md border border-slate-200 overflow-hidden font-sans">
        
        <!-- ─── 1. TOP HEADER & MAP CONTROLS ─── -->
        <div class="bg-gradient-to-r from-[#0B2E63] via-[#0F4C9F] to-[#125EB8] text-white p-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <!-- Title & Context -->
                <div class="space-y-1">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <span class="text-xl">🗺️</span>
                        <h3 class="text-base sm:text-lg font-black tracking-tight text-white">
                            Flood Protection Projects & River Basin Map
                        </h3>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-400 text-slate-950 shadow-xs">
                            <span class="w-2 h-2 rounded-full bg-slate-950 animate-ping"></span>
                            Live Works Tracker
                        </span>
                    </div>
                    <p class="text-xs text-blue-100/90 leading-relaxed max-w-2xl">
                        Each pin shows an on-ground central flood embankment, anti-erosion spur, or drainage project along the Brahmaputra and tributary river basins.
                    </p>
                </div>

                <!-- Right Action Buttons -->
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Layer Switcher (Plain Language) -->
                    <div class="inline-flex rounded-lg shadow-inner bg-blue-950/70 p-1 border border-white/20 text-xs">
                        <button
                            type="button"
                            @click="setTileLayer('topo')"
                            :class="activeTileLayer === 'topo' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-blue-200 hover:text-white'"
                            class="px-2.5 py-1 rounded-md transition text-xs flex items-center gap-1"
                            title="Topographic elevation and river drainage basins"
                        >
                            <span>🏔️</span>
                            <span>River Basin</span>
                        </button>
                        <button
                            type="button"
                            @click="setTileLayer('satellite')"
                            :class="activeTileLayer === 'satellite' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-blue-200 hover:text-white'"
                            class="px-2.5 py-1 rounded-md transition text-xs flex items-center gap-1"
                            title="Real aerial satellite imagery of rivers and erosion"
                        >
                            <span>🛰️</span>
                            <span>Satellite</span>
                        </button>
                        <button
                            type="button"
                            @click="setTileLayer('osm')"
                            :class="activeTileLayer === 'osm' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-blue-200 hover:text-white'"
                            class="px-2.5 py-1 rounded-md transition text-xs flex items-center gap-1"
                            title="Districts and roads"
                        >
                            <span>🏙️</span>
                            <span>Roads</span>
                        </button>
                    </div>

                    <!-- Help / Guide Button -->
                    <button
                        type="button"
                        @click="showGuide = !showGuide"
                        :class="showGuide ? 'bg-amber-400 text-slate-900 font-bold' : 'bg-white/10 hover:bg-white/20 text-white'"
                        class="px-3 py-1.5 rounded-lg border border-white/20 text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer"
                    >
                        <span>💡</span>
                        <span>{{ showGuide ? 'Hide Guide' : 'How to Read' }}</span>
                    </button>

                    <!-- Split / Full Toggle -->
                    <button
                        type="button"
                        @click="toggleViewMode"
                        class="hidden md:flex px-3 py-1.5 bg-white/10 hover:bg-white/20 rounded-lg border border-white/20 text-white text-xs font-semibold items-center gap-1.5 transition"
                    >
                        <span>{{ viewMode === 'split' ? '⛶ Full Map' : '▦ Split Explorer' }}</span>
                    </button>

                    <!-- Reset View -->
                    <button
                        type="button"
                        @click="resetZoom"
                        class="px-3 py-1.5 bg-white/15 hover:bg-white/25 rounded-lg border border-white/20 text-white text-xs font-semibold flex items-center gap-1 transition"
                        title="Reset view to whole North-East region"
                    >
                        <span>↺ Reset</span>
                    </button>
                </div>
            </div>

            <!-- ─── Collapsible "How to Read this Map" Banner ─── -->
            <div v-if="showGuide" class="mt-3.5 p-3.5 rounded-xl bg-blue-950/90 border border-amber-400/40 text-xs space-y-2 animate-fadeIn">
                <div class="font-bold text-amber-300 flex items-center gap-1.5 text-xs uppercase tracking-wider">
                    <span>💡</span> Quick Presentation Guide for This Map:
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-blue-100 text-[11px] leading-relaxed">
                    <div class="p-2 bg-white/5 rounded-lg border border-white/10">
                        <strong class="text-white block font-bold mb-0.5">🟢 Green Pins (>75%)</strong>
                        Schemes completed or near-completion providing active flood protection to surrounding villages.
                    </div>
                    <div class="p-2 bg-white/5 rounded-lg border border-white/10">
                        <strong class="text-white block font-bold mb-0.5">🟡 Amber Pins (40%–75%)</strong>
                        Projects actively under civil construction (e.g. revetment, geo-bag pitching, spur raising).
                    </div>
                    <div class="p-2 bg-white/5 rounded-lg border border-white/10">
                        <strong class="text-white block font-bold mb-0.5">🔵 Blue Pins (<40%)</strong>
                        Early-phase projects undergoing initial earthworks, site mobilization, or procurement.
                    </div>
                </div>
                <div class="text-[10px] text-amber-200/90 italic pt-1">
                    👉 Tip: Click any marker on the map OR select a scheme from the side panel to zoom directly to its physical river bank location.
                </div>
            </div>
        </div>

        <!-- ─── 2. LIVE METRIC STRIP (INSTANT OVERVIEW) ─── -->
        <div class="bg-slate-50 border-b border-slate-200 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs">
            <!-- Left Live Counters -->
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Showing:</span>
                
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md font-bold bg-white border border-slate-200 text-slate-800 shadow-2xs">
                    <span class="font-black text-[#0F4C9F]">{{ stats.total }}</span> Projects
                </span>

                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md font-bold bg-emerald-50 border border-emerald-200 text-emerald-800 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>{{ stats.completed }} Completed</span>
                </span>

                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md font-bold bg-amber-50 border border-amber-200 text-amber-800 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>{{ stats.ongoing }} Ongoing</span>
                </span>

                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md font-bold bg-blue-50 border border-blue-200 text-blue-800 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <span>{{ stats.early }} Initial Phase</span>
                </span>

                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md font-bold bg-slate-100 text-slate-700">
                    Approved Outlay: <strong class="text-slate-900">₹{{ stats.totalOutlay }} Cr</strong>
                </span>
            </div>

            <!-- River Basin Quick-Select Chips -->
            <div v-if="availableBasins.length > 0" class="flex items-center gap-1.5 flex-wrap">
                <span class="text-[10px] font-bold text-slate-500 uppercase">Basin:</span>
                <button
                    type="button"
                    @click="selectedBasin = 'ALL'"
                    :class="selectedBasin === 'ALL' ? 'bg-[#0F4C9F] text-white font-bold' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                    class="px-2 py-0.5 rounded text-[11px] transition"
                >
                    All Basins
                </button>
                <button
                    v-for="basin in availableBasins"
                    :key="basin"
                    type="button"
                    @click="selectedBasin = basin"
                    :class="selectedBasin === basin ? 'bg-[#0F4C9F] text-white font-bold' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                    class="px-2 py-0.5 rounded text-[11px] transition"
                >
                    {{ basin }}
                </button>
            </div>
        </div>

        <!-- ─── 3. FILTER BAR (STATE / STATUS / SEARCH) ─── -->
        <div class="bg-white border-b border-slate-200 p-2.5 sm:px-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2.5 w-full sm:w-auto flex-wrap">
                <!-- State Selector -->
                <div class="flex items-center gap-1.5">
                    <span class="text-slate-500 font-bold uppercase text-[10px]">State:</span>
                    <select
                        v-model="selectedState"
                        class="py-1 px-2.5 text-xs bg-slate-50 border border-slate-300 rounded-lg font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#0F4C9F]"
                    >
                        <option value="ALL">All States ({{ props.schemes.length }})</option>
                        <option v-for="st in availableStates.filter(s => s !== 'ALL')" :key="st" :value="st">
                            {{ st }}
                        </option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="flex items-center gap-1.5">
                    <span class="text-slate-500 font-bold uppercase text-[10px]">Status:</span>
                    <select
                        v-model="selectedStatus"
                        class="py-1 px-2.5 text-xs bg-slate-50 border border-slate-300 rounded-lg font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#0F4C9F]"
                    >
                        <option value="ALL">All Statuses</option>
                        <option value="Completed">Completed Works (>75%)</option>
                        <option value="Ongoing">Active Works (<75%)</option>
                    </select>
                </div>
            </div>

            <!-- Search Input -->
            <div class="w-full sm:w-72 relative">
                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search scheme name, code, district..."
                    class="w-full py-1 pl-8 pr-3 text-xs bg-slate-50 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-[#0F4C9F]"
                />
            </div>
        </div>

        <!-- ─── 4. MAIN MAP + INTERACTIVE EXPLORER SPLIT CONTAINER ─── -->
        <div class="flex flex-col lg:flex-row relative" :style="{ minHeight: height }">
            
            <!-- MAP CANVAS -->
            <div
                class="relative transition-all duration-300"
                :class="viewMode === 'split' ? 'w-full lg:w-8/12 xl:w-8/12' : 'w-full'"
                :style="{ height: height }"
            >
                <div ref="mapContainer" class="w-full h-full z-10"></div>

                <!-- Floating Clear Visual Legend -->
                <div class="absolute bottom-4 left-4 z-20 bg-white/95 backdrop-blur-md p-3 rounded-xl shadow-xl border border-slate-200 text-xs space-y-2 max-w-[220px]">
                    <div class="font-extrabold text-slate-900 border-b border-slate-200 pb-1 flex items-center justify-between text-[11px] uppercase tracking-wider">
                        <span>Work Progress</span>
                        <span class="text-[9px] font-normal text-slate-500">Live Pins</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-white shadow-xs shrink-0"></span>
                        <div class="text-[11px] leading-tight">
                            <strong class="text-slate-800 font-bold block">> 75% Completed</strong>
                            <span class="text-slate-500 text-[10px]">Embankment functional</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded-full bg-amber-500 border-2 border-white shadow-xs shrink-0"></span>
                        <div class="text-[11px] leading-tight">
                            <strong class="text-slate-800 font-bold block">40% – 75% Active</strong>
                            <span class="text-slate-500 text-[10px]">Under construction</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded-full bg-blue-600 border-2 border-white shadow-xs shrink-0"></span>
                        <div class="text-[11px] leading-tight">
                            <strong class="text-slate-800 font-bold block">< 40% Early Phase</strong>
                            <span class="text-slate-500 text-[10px]">Initial earthworks</span>
                        </div>
                    </div>
                </div>

                <!-- Floating Selected Scheme Quick Banner (when user clicks a pin) -->
                <div
                    v-if="selectedScheme"
                    class="absolute top-4 left-4 right-16 z-20 bg-white/95 backdrop-blur-md p-3 rounded-xl shadow-2xl border-2 border-[#0F4C9F] text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 animate-fadeIn"
                >
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-900 font-mono font-bold text-[10px]">
                                {{ selectedScheme.scheme_code }}
                            </span>
                            <span class="font-bold text-slate-700 text-[11px]">
                                📍 {{ selectedScheme.district || 'Assam' }} ({{ selectedScheme.state }}) • {{ selectedScheme.river_basin || 'Brahmaputra Basin' }}
                            </span>
                        </div>
                        <h4 class="font-black text-slate-900 text-xs truncate">
                            {{ selectedScheme.scheme_name }}
                        </h4>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold" :class="selectedScheme.physical_status === 'Completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                            {{ Math.round(selectedScheme.physical_progress_pct || 0) }}% Done
                        </span>
                        <Link
                            :href="route('schemes.show', selectedScheme.id)"
                            class="px-3 py-1 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white text-xs font-bold rounded-lg shadow-xs transition"
                        >
                            Open Details &rarr;
                        </Link>
                        <button
                            type="button"
                            @click="selectedScheme = null"
                            class="p-1 text-slate-400 hover:text-slate-600 rounded-md"
                            title="Close"
                        >
                            ✕
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─── 5. SIDE PROJECT EXPLORER (CLICK TO LOCATE ON MAP) ─── -->
            <div
                v-if="viewMode === 'split'"
                class="w-full lg:w-4/12 xl:w-4/12 bg-slate-50 border-t lg:border-t-0 lg:border-l border-slate-200 flex flex-col"
                :style="{ maxHeight: height }"
            >
                <!-- Side Panel Header -->
                <div class="p-3 bg-white border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                            <span>📋</span> Project List &amp; Locations
                        </h4>
                        <p class="text-[10px] text-slate-500">
                            Click any project to fly to its river location on the map
                        </p>
                    </div>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-100 text-[#0F4C9F]">
                        {{ filteredSchemes.length }}
                    </span>
                </div>

                <!-- Scheme Cards List (Scrollable) -->
                <div class="flex-1 overflow-y-auto p-2.5 space-y-2">
                    <div
                        v-if="filteredSchemes.length === 0"
                        class="p-6 text-center text-slate-400 text-xs"
                    >
                        No schemes match your filter criteria.
                    </div>

                    <div
                        v-for="(scheme, idx) in filteredSchemes"
                        :key="scheme.id"
                        @click="selectScheme(scheme)"
                        :class="selectedScheme?.id === scheme.id ? 'border-[#0F4C9F] bg-blue-50/80 shadow-md ring-2 ring-blue-300' : 'border-slate-200 bg-white hover:border-blue-300 hover:bg-slate-50/80 shadow-2xs'"
                        class="p-3 rounded-xl border transition-all duration-150 cursor-pointer text-left space-y-2"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <span class="px-1.5 py-0.5 rounded font-mono font-bold text-[10px] bg-slate-100 text-slate-700">
                                {{ scheme.scheme_code || `SCHEME-${idx + 1}` }}
                            </span>
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-extrabold"
                                :class="scheme.physical_status === 'Completed' || parseFloat(scheme.physical_progress_pct) >= 75 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                            >
                                {{ Math.round(scheme.physical_progress_pct || 0) }}% Done
                            </span>
                        </div>

                        <h5 class="text-xs font-bold text-slate-900 leading-snug line-clamp-2">
                            {{ scheme.scheme_name }}
                        </h5>

                        <div class="flex items-center justify-between text-[10px] text-slate-500">
                            <span class="font-semibold text-slate-700">
                                📍 {{ scheme.district || scheme.division || 'Main' }} ({{ scheme.state }})
                            </span>
                            <span class="font-bold text-blue-700">
                                🌊 {{ scheme.river_basin || 'Brahmaputra' }}
                            </span>
                        </div>

                        <!-- Progress Bar & Outlay -->
                        <div class="space-y-1 pt-1 border-t border-slate-100">
                            <div class="flex items-center justify-between text-[10px]">
                                <span class="text-slate-500">Approved Cost:</span>
                                <strong class="text-slate-900 font-bold">₹{{ scheme.sanctioned_amount_cr || '0.00' }} Cr</strong>
                            </div>
                            <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="scheme.physical_status === 'Completed' || parseFloat(scheme.physical_progress_pct) >= 75 ? 'bg-emerald-500' : 'bg-amber-500'"
                                    :style="{ width: `${Math.min(100, parseFloat(scheme.physical_progress_pct) || 0)}%` }"
                                ></div>
                            </div>
                        </div>

                        <div class="pt-1 flex items-center justify-end text-[10px] font-bold text-[#0F4C9F]">
                            <span>Click to locate on map &rarr;</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* Leaflet Tooltip & Popup Enhancements */
.leaflet-popup-content-wrapper {
    border-radius: 12px !important;
    box-shadow: 0 14px 35px -5px rgba(0, 0, 0, 0.3) !important;
    padding: 2px !important;
}
.leaflet-popup-content {
    margin: 8px 10px !important;
}
.leaflet-tooltip {
    background: rgba(15, 23, 42, 0.92) !important;
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    border-radius: 6px !important;
    padding: 4px 8px !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important;
}
.leaflet-tooltip-top:before {
    border-top-color: rgba(15, 23, 42, 0.92) !important;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
    animation: fadeIn 0.2s ease-out forwards;
}
</style>
