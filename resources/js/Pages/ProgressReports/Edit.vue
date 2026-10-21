<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    report: {
        type: Object,
        required: true,
    },
    userRole: {
        type: String,
        default: '',
    },
});

const isSubmitting = ref(false);
const errorMsg = ref('');

const months = [
    { value: 1, label: 'January' },
    { value: 2, label: 'February' },
    { value: 3, label: 'March' },
    { value: 4, label: 'April' },
    { value: 5, label: 'May' },
    { value: 6, label: 'June' },
    { value: 7, label: 'July' },
    { value: 8, label: 'August' },
    { value: 9, label: 'September' },
    { value: 10, label: 'October' },
    { value: 11, label: 'November' },
    { value: 12, label: 'December' },
];

const years = [2024, 2025, 2026, 2027, 2028, 2029, 2030];

// Parse existing component breakup
const initialBreakup = Array.isArray(props.report.physical_breakup) && props.report.physical_breakup.length > 0
    ? JSON.parse(JSON.stringify(props.report.physical_breakup))
    : [
        { name: 'Embankment & Earthwork', scope: '1000', unit: 'm', completed: '650', pct: 65, status: 'In Progress' },
    ];

const form = ref({
    reporting_month: props.report.reporting_month || 1,
    reporting_year: props.report.reporting_year || 2026,
    reporting_period: props.report.reporting_period || '',
    physical_progress_pct: props.report.physical_progress_pct || '',
    physical_progress_description: props.report.physical_progress_description || '',
    financial_progress_pct: props.report.financial_progress_pct || '',
    financial_progress_description: props.report.financial_progress_description || '',
    narrative_report: props.report.narrative_report || '',
    video_url: props.report.video_url || '',
    physical_breakup: initialBreakup,
    media_items: [],
    progress_report_pdf: null,
});

const addBreakupRow = () => {
    form.value.physical_breakup.push({
        name: '',
        scope: '',
        unit: 'm',
        completed: '',
        pct: 0,
        status: 'In Progress'
    });
};

const removeBreakupRow = (index) => {
    if (form.value.physical_breakup.length > 1) {
        form.value.physical_breakup.splice(index, 1);
    }
};

const updateComponentPct = (row) => {
    const scope = parseFloat(row.scope) || 0;
    const completed = parseFloat(row.completed) || 0;
    if (scope > 0) {
        row.pct = Math.min(100, Math.round((completed / scope) * 100));
        if (row.pct >= 100) row.status = 'Completed';
        else if (row.pct > 0) row.status = 'In Progress';
        else row.status = 'Not Started';
    }
};

const addMediaSlot = () => {
    form.value.media_items.push({
        file: null,
        caption: 'Updated site work progress photo',
        lat: '',
        lng: '',
        isLocating: false,
    });
};

const removeMediaSlot = (index) => {
    form.value.media_items.splice(index, 1);
};

const onMediaFileSelected = (event, index) => {
    const file = event.target.files[0];
    if (file) {
        form.value.media_items[index].file = file;
    }
};

const captureGps = (item) => {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        return;
    }
    item.isLocating = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            item.lat = pos.coords.latitude.toFixed(6);
            item.lng = pos.coords.longitude.toFixed(6);
            item.isLocating = false;
        },
        (err) => {
            alert('Unable to retrieve your location: ' + err.message);
            item.isLocating = false;
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
};

const submitUpdate = async () => {
    isSubmitting.value = true;
    errorMsg.value = '';

    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('reporting_month', form.value.reporting_month);
    formData.append('reporting_year', form.value.reporting_year);
    formData.append('reporting_period', form.value.reporting_period);
    formData.append('physical_progress_pct', form.value.physical_progress_pct);
    formData.append('physical_progress_description', form.value.physical_progress_description || '');
    formData.append('financial_progress_pct', form.value.financial_progress_pct);
    formData.append('financial_progress_description', form.value.financial_progress_description || '');
    formData.append('narrative_report', form.value.narrative_report || '');
    formData.append('video_url', form.value.video_url || '');
    formData.append('physical_breakup', JSON.stringify(form.value.physical_breakup));

    if (form.value.progress_report_pdf) {
        formData.append('progress_report_pdf', form.value.progress_report_pdf);
    }

    form.value.media_items.forEach((item, idx) => {
        if (item.file) {
            formData.append(`media_files[${idx}]`, item.file);
            formData.append(`media_captions[${idx}]`, item.caption || '');
            if (item.lat) formData.append(`media_latitudes[${idx}]`, item.lat);
            if (item.lng) formData.append(`media_longitudes[${idx}]`, item.lng);
        }
    });

    try {
        await axios.post(route('progress-reports.update', props.report.id), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        router.visit(route('progress-reports.show', props.report.id));
    } catch (err) {
        if (err.response?.data?.errors) {
            const firstErr = Object.values(err.response.data.errors)[0];
            errorMsg.value = Array.isArray(firstErr) ? firstErr[0] : firstErr;
        } else {
            errorMsg.value = err.response?.data?.message || 'Error updating progress report.';
        }
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Head :title="`Edit Progress Report #${report.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('progress-reports.show', report.id)"
                        class="w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <div>
                        <h2 class="font-black text-lg text-gray-900 leading-tight">
                            Amend Progress Report #{{ report.id }}
                        </h2>
                        <p class="text-xs text-gray-500">
                            {{ report.scheme?.scheme_name }} &bull; {{ report.reporting_period }}
                        </p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-200">
                    {{ report.status }}
                </span>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-6 max-w-7xl mx-auto">

            <!-- Correction Reason Banner -->
            <div v-if="report.bb_remarks || report.mojs_remarks" class="p-5 rounded-2xl bg-amber-50 border border-amber-300 text-amber-950 space-y-2 shadow-xs">
                <div class="flex items-center gap-2 font-black text-xs uppercase tracking-wide text-amber-900">
                    <span>⚠️</span>
                    <span>Correction Instructions from Monitoring Authority</span>
                </div>
                <div v-if="report.bb_remarks" class="text-xs">
                    <strong class="text-amber-900">Brahmaputra Board Remarks:</strong>
                    <p class="mt-0.5 bg-white/70 p-2.5 rounded-lg border border-amber-200 font-medium">{{ report.bb_remarks }}</p>
                </div>
                <div v-if="report.mojs_remarks" class="text-xs">
                    <strong class="text-amber-900">Ministry (MoJS) Remarks:</strong>
                    <p class="mt-0.5 bg-white/70 p-2.5 rounded-lg border border-amber-200 font-medium">{{ report.mojs_remarks }}</p>
                </div>
            </div>

            <!-- Error Banner -->
            <div v-if="errorMsg" class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold">
                {{ errorMsg }}
            </div>

            <form @submit.prevent="submitUpdate" class="space-y-6">

                <!-- Scheme & Period Details -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 space-y-4">
                    <h3 class="font-extrabold text-sm text-gray-900 border-b border-gray-100 pb-3">
                        Reporting Period &amp; Identification
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2 p-3 bg-gray-50 rounded-xl border border-gray-200">
                            <div class="text-[10px] font-bold uppercase text-gray-400">Scheme</div>
                            <div class="font-extrabold text-xs text-gray-900 mt-0.5">[{{ report.scheme?.scheme_code }}] {{ report.scheme?.scheme_name }}</div>
                            <div class="text-[11px] text-gray-500 mt-0.5">{{ report.scheme?.state }} &bull; Sanctioned: ₹{{ report.scheme?.sanctioned_amount_cr }} Cr</div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Month</label>
                                <select v-model="form.reporting_month" class="w-full rounded-xl border-gray-300 text-xs font-semibold">
                                    <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Year</label>
                                <select v-model="form.reporting_year" class="w-full rounded-xl border-gray-300 text-xs font-semibold">
                                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Metrics -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 space-y-4">
                    <h3 class="font-extrabold text-sm text-gray-900 border-b border-gray-100 pb-3">
                        Progress Percentages &amp; Descriptions
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-5 rounded-xl border border-blue-200 bg-blue-50/20 space-y-3">
                            <label class="block text-xs font-black text-blue-900">Physical Progress (%) *</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                v-model="form.physical_progress_pct"
                                class="w-full rounded-xl border-gray-300 text-sm font-black"
                                required
                            />
                            <textarea
                                v-model="form.physical_progress_description"
                                rows="3"
                                class="w-full rounded-xl border-gray-300 text-xs"
                                placeholder="Ground execution description..."
                            ></textarea>
                        </div>

                        <div class="p-5 rounded-xl border border-emerald-200 bg-emerald-50/20 space-y-3">
                            <label class="block text-xs font-black text-emerald-900">Financial Progress (%) *</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                v-model="form.financial_progress_pct"
                                class="w-full rounded-xl border-gray-300 text-sm font-black"
                                required
                            />
                            <textarea
                                v-model="form.financial_progress_description"
                                rows="3"
                                class="w-full rounded-xl border-gray-300 text-xs"
                                placeholder="Financial utilization breakdown..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Component Break-up Table -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <h3 class="font-extrabold text-sm text-gray-900">
                            Physical Component Breakdown
                        </h3>
                        <button
                            type="button"
                            @click="addBreakupRow"
                            class="px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-bold hover:bg-indigo-100"
                        >
                            + Add Row
                        </button>
                    </div>

                    <div class="overflow-x-auto border border-gray-200 rounded-xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 text-gray-600 font-bold uppercase text-[10px]">
                                <tr>
                                    <th class="px-4 py-2.5">Component</th>
                                    <th class="px-4 py-2.5">Scope</th>
                                    <th class="px-4 py-2.5">Unit</th>
                                    <th class="px-4 py-2.5">Completed</th>
                                    <th class="px-4 py-2.5">Progress</th>
                                    <th class="px-4 py-2.5">Status</th>
                                    <th class="px-4 py-2.5 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(row, idx) in form.physical_breakup" :key="idx">
                                    <td class="px-4 py-2">
                                        <input type="text" v-model="row.name" class="w-full rounded-lg border-gray-300 text-xs" required />
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" step="any" v-model="row.scope" @input="updateComponentPct(row)" class="w-full rounded-lg border-gray-300 text-xs text-right font-mono" required />
                                    </td>
                                    <td class="px-4 py-2">
                                        <select v-model="row.unit" class="w-full rounded-lg border-gray-300 text-xs">
                                            <option value="m">m</option>
                                            <option value="km">km</option>
                                            <option value="nos">nos</option>
                                            <option value="m3">m³</option>
                                            <option value="sqm">m²</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" step="any" v-model="row.completed" @input="updateComponentPct(row)" class="w-full rounded-lg border-gray-300 text-xs text-right font-mono font-bold" required />
                                    </td>
                                    <td class="px-4 py-2 font-mono font-bold text-xs text-slate-800">
                                        {{ row.pct || 0 }}%
                                    </td>
                                    <td class="px-4 py-2">
                                        <select v-model="row.status" class="w-full rounded-lg border-gray-300 text-xs">
                                            <option value="Not Started">Not Started</option>
                                            <option value="In Progress">In Progress</option>
                                            <option value="Completed">Completed</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <button type="button" @click="removeBreakupRow(idx)" class="text-rose-500 hover:text-rose-700 text-xs font-bold" :disabled="form.physical_breakup.length <= 1">✕</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Existing Media & New Attachments -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <h3 class="font-extrabold text-sm text-gray-900">
                            Site Media &amp; Drone URL
                        </h3>
                        <button
                            type="button"
                            @click="addMediaSlot"
                            class="px-3 py-1.5 rounded-lg bg-teal-50 text-teal-700 text-xs font-bold hover:bg-teal-100"
                        >
                            + Add New Photo
                        </button>
                    </div>

                    <!-- Existing Media Preview -->
                    <div v-if="report.geo_tagged_files && report.geo_tagged_files.length" class="space-y-2">
                        <div class="text-xs font-bold text-gray-700">Existing Uploaded Media:</div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div v-for="(m, i) in report.geo_tagged_files" :key="i" class="border border-gray-200 rounded-xl p-2 bg-slate-50 text-xs">
                                <img v-if="m.type === 'photo'" :src="m.path" class="w-full h-24 object-cover rounded-lg mb-1" />
                                <div v-else class="w-full h-24 bg-gray-900 rounded-lg flex items-center justify-center text-white mb-1">🎬 Video</div>
                                <div class="font-bold truncate text-[11px]">{{ m.caption || m.name }}</div>
                                <div v-if="m.lat" class="text-[10px] text-gray-500 font-mono">{{ m.lat }}, {{ m.lng }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- New Upload Slots -->
                    <div v-if="form.media_items.length" class="space-y-3 pt-3 border-t border-gray-100">
                        <div class="text-xs font-bold text-teal-800">New Media to Append:</div>
                        <div v-for="(item, idx) in form.media_items" :key="idx" class="p-3 border border-teal-200 rounded-xl bg-teal-50/20 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-teal-900">New Media #{{ idx + 1 }}</span>
                                <button type="button" @click="removeMediaSlot(idx)" class="text-rose-500 font-bold text-xs">Remove</button>
                            </div>
                            <input type="file" accept="image/*,video/*" @change="e => onMediaFileSelected(e, idx)" class="text-xs w-full" />
                            <input type="text" v-model="item.caption" placeholder="Caption..." class="w-full rounded-lg border-gray-300 text-xs" />
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" v-model="item.lat" placeholder="Latitude" class="rounded-lg border-gray-300 text-xs font-mono" />
                                <input type="text" v-model="item.lng" placeholder="Longitude" class="rounded-lg border-gray-300 text-xs font-mono" />
                            </div>
                            <button type="button" @click="captureGps(item)" class="text-[11px] font-bold text-gray-600 bg-white border border-gray-200 px-3 py-1 rounded-lg w-full">
                                📍 Auto-Detect GPS
                            </button>
                        </div>
                    </div>

                    <!-- Video URL -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Drone Video / Cloud URL</label>
                        <input type="url" v-model="form.video_url" class="w-full rounded-xl border-gray-300 text-xs font-mono" />
                    </div>

                    <!-- PDF -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Replace Official PDF Report (Optional)</label>
                        <input type="file" accept=".pdf" @change="e => form.progress_report_pdf = e.target.files[0]" class="text-xs text-gray-500" />
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="bg-white rounded-2xl border border-gray-200 p-6 flex justify-between items-center shadow-xs">
                    <Link :href="route('progress-reports.show', report.id)" class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 text-xs font-bold hover:bg-gray-50">
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs shadow-md transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
                    >
                        <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <span>Re-Submit Corrected Progress Report</span>
                    </button>
                </div>

            </form>

        </div>
    </AuthenticatedLayout>
</template>
