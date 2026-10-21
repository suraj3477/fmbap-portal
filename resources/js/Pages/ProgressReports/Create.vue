<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    availableSchemes: {
        type: Array,
        default: () => [],
    },
    preselectedSchemeId: {
        type: Number,
        default: null,
    },
    userRole: {
        type: String,
        default: '',
    },
});

const isSubmitting = ref(false);
const isLoadingBaseline = ref(false);
const errorMsg = ref('');
const successMsg = ref('');

const currentDate = new Date();
const currentMonth = currentDate.getMonth() + 1; // 1-12
const currentYear = currentDate.getFullYear();

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

// Form Model
const form = ref({
    scheme_id: props.preselectedSchemeId || '',
    reporting_month: currentMonth,
    reporting_year: currentYear,
    reporting_period: '',
    physical_progress_pct: '',
    physical_progress_description: '',
    financial_progress_pct: '',
    financial_progress_description: '',
    narrative_report: '',
    video_url: '',
    physical_breakup: [
        { name: 'Embankment & Earthwork', scope: '1000', unit: 'm', completed: '650', pct: 65, status: 'In Progress' },
        { name: 'Geo-bag Pitching & Revetment', scope: '500', unit: 'm', completed: '350', pct: 70, status: 'In Progress' },
        { name: 'RCC Porcupine Screens', scope: '120', unit: 'nos', completed: '120', pct: 100, status: 'Completed' },
        { name: 'Drainage Sluice Construction', scope: '2', unit: 'nos', completed: '1', pct: 50, status: 'In Progress' },
    ],
    media_items: [],
    progress_report_pdf: null,
});

// Baseline data loaded from API
const baseline = ref({
    scheme: null,
    last_fund_release: null,
    last_monthly_report: null,
    history: [],
});

const selectedScheme = computed(() => {
    if (!form.value.scheme_id) return null;
    return props.availableSchemes.find(s => s.id === parseInt(form.value.scheme_id)) || baseline.value.scheme;
});

// Update period string whenever month or year changes
watch([() => form.value.reporting_month, () => form.value.reporting_year], () => {
    const m = months.find(item => item.value === parseInt(form.value.reporting_month));
    if (m) {
        form.value.reporting_period = `${m.label} ${form.value.reporting_year}`;
    }
}, { immediate: true });

// Load baseline whenever scheme_id changes
const fetchSchemeBaseline = async (schemeId) => {
    if (!schemeId) {
        baseline.value = { scheme: null, last_fund_release: null, last_monthly_report: null, history: [] };
        return;
    }

    isLoadingBaseline.value = true;
    errorMsg.value = '';

    try {
        const res = await axios.get(route('progress-reports.scheme.baseline', schemeId));
        baseline.value = res.data;

        // Auto-seed progress percentages with previous monthly report or fund release claim
        if (baseline.value.last_monthly_report) {
            const last = baseline.value.last_monthly_report;
            if (!form.value.physical_progress_pct) form.value.physical_progress_pct = last.physical_progress_pct;
            if (!form.value.financial_progress_pct) form.value.financial_progress_pct = last.financial_progress_pct;
            if (!form.value.physical_progress_description && last.physical_progress_description) {
                form.value.physical_progress_description = last.physical_progress_description;
            }
            if (!form.value.financial_progress_description && last.financial_progress_description) {
                form.value.financial_progress_description = last.financial_progress_description;
            }
            if (last.physical_breakup && Array.isArray(last.physical_breakup) && last.physical_breakup.length > 0) {
                form.value.physical_breakup = JSON.parse(JSON.stringify(last.physical_breakup));
            }
        } else if (baseline.value.last_fund_release) {
            const claim = baseline.value.last_fund_release;
            if (!form.value.physical_progress_pct) form.value.physical_progress_pct = claim.physical_progress_pct;
            if (!form.value.financial_progress_pct) form.value.financial_progress_pct = claim.financial_progress_pct;
            if (!form.value.physical_progress_description && claim.physical_progress_description) {
                form.value.physical_progress_description = claim.physical_progress_description;
            }
            if (!form.value.financial_progress_description && claim.financial_progress_description) {
                form.value.financial_progress_description = claim.financial_progress_description;
            }
        }
    } catch (err) {
        console.error('Failed to load baseline data:', err);
    } finally {
        isLoadingBaseline.value = false;
    }
};

watch(() => form.value.scheme_id, (newId) => {
    if (newId) {
        fetchSchemeBaseline(newId);
    }
});

onMounted(() => {
    if (props.preselectedSchemeId) {
        fetchSchemeBaseline(props.preselectedSchemeId);
    } else if (props.availableSchemes.length === 1) {
        form.value.scheme_id = props.availableSchemes[0].id;
        fetchSchemeBaseline(props.availableSchemes[0].id);
    }
});

// Component Breakup Table Operations
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

// Site Media Uploader
const addMediaSlot = () => {
    form.value.media_items.push({
        file: null,
        caption: 'Site work progress photo',
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

// Calculate Progress Delta compared to previous monthly report
const progressDelta = computed(() => {
    if (!baseline.value.last_monthly_report) return null;
    const current = parseFloat(form.value.physical_progress_pct) || 0;
    const previous = parseFloat(baseline.value.last_monthly_report.physical_progress_pct) || 0;
    const delta = current - previous;
    return {
        diff: delta.toFixed(1),
        isPositive: delta >= 0,
        previous,
    };
});

// Submission
const submitReport = async () => {
    if (!form.value.scheme_id) {
        errorMsg.value = 'Please select a scheme to submit progress report for.';
        return;
    }
    if (form.value.physical_progress_pct === '' || form.value.financial_progress_pct === '') {
        errorMsg.value = 'Please enter both Physical (%) and Financial (%) progress.';
        return;
    }

    isSubmitting.value = true;
    errorMsg.value = '';

    const formData = new FormData();
    formData.append('scheme_id', form.value.scheme_id);
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

    // Append geo-tagged media
    form.value.media_items.forEach((item, idx) => {
        if (item.file) {
            formData.append(`media_files[${idx}]`, item.file);
            formData.append(`media_captions[${idx}]`, item.caption || '');
            if (item.lat) formData.append(`media_latitudes[${idx}]`, item.lat);
            if (item.lng) formData.append(`media_longitudes[${idx}]`, item.lng);
        }
    });

    try {
        await axios.post(route('progress-reports.store'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        router.visit(route('progress-reports.index'), {
            preserveState: false,
        });
    } catch (err) {
        if (err.response?.data?.errors) {
            const firstErr = Object.values(err.response.data.errors)[0];
            errorMsg.value = Array.isArray(firstErr) ? firstErr[0] : firstErr;
        } else {
            errorMsg.value = err.response?.data?.message || 'Error submitting monthly progress report.';
        }
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Head title="Submit Monthly Physical & Financial Progress — FMBAP" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('progress-reports.index')"
                        class="w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 hover:text-gray-900 transition-colors shrink-0"
                        title="Back to Reports"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-black text-lg text-gray-900 leading-tight">
                                Monthly Physical &amp; Financial Progress Report
                            </h2>
                            <span class="text-xs font-black px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                                State Submission
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Report monthly execution progress, component breakup, and site media. Automatically links to future Fund Release claims.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 font-semibold bg-gray-50 border border-gray-200 px-3 py-1.5 rounded-lg">
                        {{ form.reporting_period }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-6 max-w-7xl mx-auto">

            <!-- Alert Notice -->
            <div v-if="errorMsg" class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex items-center gap-3 shadow-xs">
                <span class="text-base">⚠️</span>
                <span>{{ errorMsg }}</span>
            </div>

            <!-- Form Card -->
            <form @submit.prevent="submitReport" class="space-y-6">

                <!-- SECTION 1: SCHEME & REPORTING PERIOD SELECTION -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 space-y-5">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-blue-100 text-[#0F4C9F] flex items-center justify-center font-black text-xs">
                                1
                            </div>
                            <h3 class="font-extrabold text-sm text-gray-900">
                                Scheme &amp; Reporting Month
                            </h3>
                        </div>
                        <span class="text-[11px] font-semibold text-gray-400">Step 1 of 4</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- Scheme Dropdown -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-black text-gray-700 mb-1.5">
                                Select Scheme <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.scheme_id"
                                class="w-full rounded-xl border-gray-300 text-xs font-semibold focus:border-[#0F4C9F] focus:ring-[#0F4C9F]"
                                required
                            >
                                <option value="" disabled>-- Select Scheme from Catalogue --</option>
                                <option
                                    v-for="s in availableSchemes"
                                    :key="s.id"
                                    :value="s.id"
                                >
                                    [{{ s.scheme_code }}] {{ s.scheme_name }} ({{ s.state }}) - ₹{{ s.sanctioned_amount_cr }} Cr
                                </option>
                            </select>
                            <p class="text-[11px] text-gray-400 mt-1">
                                Only active schemes assigned to your state are displayed.
                            </p>
                        </div>

                        <!-- Month & Year -->
                        <div class="grid grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-xs font-black text-gray-700 mb-1.5">
                                    Month <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.reporting_month"
                                    class="w-full rounded-xl border-gray-300 text-xs font-semibold focus:border-[#0F4C9F] focus:ring-[#0F4C9F]"
                                    required
                                >
                                    <option v-for="m in months" :key="m.value" :value="m.value">
                                        {{ m.label }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-700 mb-1.5">
                                    Year <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.reporting_year"
                                    class="w-full rounded-xl border-gray-300 text-xs font-semibold focus:border-[#0F4C9F] focus:ring-[#0F4C9F]"
                                    required
                                >
                                    <option v-for="y in years" :key="y" :value="y">
                                        {{ y }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Baseline Cards Strip -->
                    <div v-if="isLoadingBaseline" class="p-4 bg-gray-50 rounded-xl text-center text-xs text-gray-500 flex items-center justify-center gap-2">
                        <span class="w-4 h-4 border-2 border-[#0F4C9F] border-t-transparent rounded-full animate-spin"></span>
                        Loading previous monthly report &amp; fund release claim baseline...
                    </div>

                    <div v-else-if="baseline.scheme" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        <!-- Card 1: Last Reported Fund Release Baseline -->
                        <div class="p-4 rounded-xl bg-gradient-to-br from-blue-50/70 to-slate-50 border border-blue-200/80 shadow-2xs">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-black uppercase text-[#0F4C9F] tracking-wide flex items-center gap-1.5">
                                    <span>📑</span>
                                    <span>Last Fund Release Claim</span>
                                </span>
                                <span v-if="baseline.last_fund_release" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-[#0F4C9F] border border-blue-200">
                                    Instalment #{{ baseline.last_fund_release.instalment_number }}
                                </span>
                            </div>

                            <div v-if="baseline.last_fund_release" class="space-y-1.5 text-xs text-slate-700">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Physical Progress:</span>
                                    <span class="font-extrabold text-slate-900">{{ baseline.last_fund_release.physical_progress_pct }}%</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Financial Progress:</span>
                                    <span class="font-extrabold text-slate-900">{{ baseline.last_fund_release.financial_progress_pct }}%</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Claim Amount:</span>
                                    <span class="font-bold text-slate-800">₹{{ baseline.last_fund_release.requested_amount_cr }} Cr ({{ baseline.last_fund_release.status }})</span>
                                </div>
                                <div class="text-[10px] text-gray-400 pt-1 border-t border-blue-100">
                                    Reported on {{ baseline.last_fund_release.created_at }}
                                </div>
                            </div>
                            <div v-else class="text-xs text-gray-500 italic py-2">
                                No previous fund release claims filed yet. This report will establish the initial baseline.
                            </div>
                        </div>

                        <!-- Card 2: Previous Monthly Progress Report -->
                        <div class="p-4 rounded-xl bg-gradient-to-br from-emerald-50/70 to-slate-50 border border-emerald-200/80 shadow-2xs">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-black uppercase text-emerald-800 tracking-wide flex items-center gap-1.5">
                                    <span>📈</span>
                                    <span>Previous Monthly Report</span>
                                </span>
                                <span v-if="baseline.last_monthly_report" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    {{ baseline.last_monthly_report.reporting_period }}
                                </span>
                            </div>

                            <div v-if="baseline.last_monthly_report" class="space-y-1.5 text-xs text-slate-700">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Physical Progress:</span>
                                    <span class="font-extrabold text-emerald-800">{{ baseline.last_monthly_report.physical_progress_pct }}%</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Financial Progress:</span>
                                    <span class="font-extrabold text-slate-900">{{ baseline.last_monthly_report.financial_progress_pct }}%</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Report Status:</span>
                                    <span class="font-bold text-slate-800">{{ baseline.last_monthly_report.status }}</span>
                                </div>
                                <div class="text-[10px] text-gray-400 pt-1 border-t border-emerald-100">
                                    Reported on {{ baseline.last_monthly_report.created_at }}
                                </div>
                            </div>
                            <div v-else class="text-xs text-gray-500 italic py-2">
                                First monthly report for this scheme. Initializing progression curve.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: CUMULATIVE PHYSICAL & FINANCIAL METRICS -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 space-y-5">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-black text-xs">
                                2
                            </div>
                            <h3 class="font-extrabold text-sm text-gray-900">
                                Cumulative Progress Metrics ({{ form.reporting_period }})
                            </h3>
                        </div>
                        <!-- Live Progression Increase Indicator -->
                        <div v-if="progressDelta" class="flex items-center gap-1.5 text-xs font-bold">
                            <span class="text-gray-500">Prev: {{ progressDelta.previous }}%</span>
                            <span class="text-gray-400">&rarr;</span>
                            <span
                                :class="progressDelta.isPositive ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                class="px-2 py-0.5 rounded-full text-[11px] font-black"
                            >
                                {{ progressDelta.isPositive ? '+' : '' }}{{ progressDelta.diff }}% Gain
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Physical Progress -->
                        <div class="p-5 rounded-xl border border-blue-200 bg-blue-50/20 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-black text-blue-900 flex items-center gap-1.5">
                                    <span>🏗️</span>
                                    <span>Physical Progress Completion (%)</span>
                                    <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-xs font-mono font-black text-[#0F4C9F]">0 - 100%</span>
                            </div>

                            <div class="relative">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    v-model="form.physical_progress_pct"
                                    placeholder="e.g. 72.50"
                                    class="w-full rounded-xl border-gray-300 text-sm font-black focus:border-[#0F4C9F] focus:ring-[#0F4C9F]"
                                    required
                                />
                                <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-xs font-black text-gray-400">%</span>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 mb-1">
                                    Description of Works Executed on Ground
                                </label>
                                <textarea
                                    v-model="form.physical_progress_description"
                                    rows="3"
                                    placeholder="e.g. Geo-bag revetment laid along 850m of river reach; 40 RCC porcupines assembled; foundation dewatering in progress..."
                                    class="w-full rounded-xl border-gray-300 text-xs focus:border-[#0F4C9F] focus:ring-[#0F4C9F]"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Financial Progress -->
                        <div class="p-5 rounded-xl border border-emerald-200 bg-emerald-50/20 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-black text-emerald-900 flex items-center gap-1.5">
                                    <span>💰</span>
                                    <span>Financial Expenditure Progress (%)</span>
                                    <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-xs font-mono font-black text-emerald-700">0 - 100%</span>
                            </div>

                            <div class="relative">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    v-model="form.financial_progress_pct"
                                    placeholder="e.g. 68.00"
                                    class="w-full rounded-xl border-gray-300 text-sm font-black focus:border-emerald-600 focus:ring-emerald-600"
                                    required
                                />
                                <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-xs font-black text-gray-400">%</span>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 mb-1">
                                    Expenditure &amp; Running Bill Details
                                </label>
                                <textarea
                                    v-model="form.financial_progress_description"
                                    rows="3"
                                    placeholder="e.g. Running Account Bill #4 cleared for M/s GeoTech infra; ₹1.24 Cr utilized towards boulder supply and labor charges..."
                                    class="w-full rounded-xl border-gray-300 text-xs focus:border-emerald-600 focus:ring-emerald-600"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: PHYSICAL PROGRESS BREAK-UP BY COMPONENT -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-black text-xs">
                                3
                            </div>
                            <div>
                                <h3 class="font-extrabold text-sm text-gray-900">
                                    Physical Progress Break-Up (Component-wise)
                                </h3>
                                <p class="text-[11px] text-gray-400">
                                    Break down execution by civil engineering works, scopes, and units.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="addBreakupRow"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 text-xs font-bold transition"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Component Row
                        </button>
                    </div>

                    <div class="overflow-x-auto border border-gray-200 rounded-xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50/80 text-gray-600 font-bold uppercase text-[10px] tracking-wider border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3 min-w-[200px]">Component Name</th>
                                    <th class="px-4 py-3 min-w-[100px]">Total Scope</th>
                                    <th class="px-4 py-3 min-w-[90px]">Unit</th>
                                    <th class="px-4 py-3 min-w-[100px]">Completed</th>
                                    <th class="px-4 py-3 min-w-[120px]">Progress (%)</th>
                                    <th class="px-4 py-3 min-w-[120px]">Status</th>
                                    <th class="px-4 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="(row, idx) in form.physical_breakup"
                                    :key="idx"
                                    class="hover:bg-slate-50/50"
                                >
                                    <td class="px-4 py-2.5 font-bold text-gray-400">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <input
                                            type="text"
                                            v-model="row.name"
                                            placeholder="e.g. Geo-bag Revetment"
                                            class="w-full rounded-lg border-gray-300 text-xs font-semibold py-1.5 px-2.5"
                                            required
                                        />
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <input
                                            type="number"
                                            step="any"
                                            v-model="row.scope"
                                            @input="updateComponentPct(row)"
                                            placeholder="1000"
                                            class="w-full rounded-lg border-gray-300 text-xs py-1.5 px-2.5 text-right font-mono"
                                            required
                                        />
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <select
                                            v-model="row.unit"
                                            class="w-full rounded-lg border-gray-300 text-xs py-1.5 px-2"
                                        >
                                            <option value="m">m (meter)</option>
                                            <option value="km">km</option>
                                            <option value="nos">nos</option>
                                            <option value="m3">m³</option>
                                            <option value="sqm">m²</option>
                                            <option value="ha">ha</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <input
                                            type="number"
                                            step="any"
                                            v-model="row.completed"
                                            @input="updateComponentPct(row)"
                                            placeholder="650"
                                            class="w-full rounded-lg border-gray-300 text-xs py-1.5 px-2.5 text-right font-mono font-bold"
                                            required
                                        />
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-2">
                                            <div class="w-16 bg-gray-100 rounded-full h-2 overflow-hidden">
                                                <div
                                                    class="h-full rounded-full bg-emerald-500"
                                                    :style="{ width: `${Math.min(100, row.pct || 0)}%` }"
                                                ></div>
                                            </div>
                                            <span class="font-mono font-extrabold text-xs text-slate-900 w-10 text-right">
                                                {{ row.pct || 0 }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <select
                                            v-model="row.status"
                                            class="w-full rounded-lg border-gray-300 text-[11px] font-bold py-1 px-2"
                                        >
                                            <option value="Not Started">Not Started</option>
                                            <option value="In Progress">In Progress</option>
                                            <option value="Completed">Completed</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2.5 text-right">
                                        <button
                                            type="button"
                                            @click="removeBreakupRow(idx)"
                                            class="p-1 rounded text-rose-500 hover:bg-rose-50 hover:text-rose-700"
                                            title="Delete Row"
                                            :disabled="form.physical_breakup.length <= 1"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SECTION 4: GEO-TAGGED PHOTOS, SITE VIDEOS & DRONE FOOTAGE -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 space-y-5">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center font-black text-xs">
                                4
                            </div>
                            <div>
                                <h3 class="font-extrabold text-sm text-gray-900">
                                    Geo-Tagged Site Media (Photos &amp; Video)
                                </h3>
                                <p class="text-[11px] text-gray-400">
                                    Upload site execution photographs with GPS coordinates &amp; optional drone flight video link.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="addMediaSlot"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100 border border-teal-200 text-xs font-bold transition"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Add Site Photo/Video
                        </button>
                    </div>

                    <!-- Video & Drone URL -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                        <label class="block text-xs font-black text-slate-800 flex items-center gap-2">
                            <span>🎥</span>
                            <span>Drone Survey / Video Documentation URL (YouTube, Vimeo, or Cloud Link)</span>
                        </label>
                        <input
                            type="url"
                            v-model="form.video_url"
                            placeholder="https://youtu.be/... or https://drive.google.com/..."
                            class="w-full rounded-xl border-gray-300 text-xs font-mono focus:border-teal-600 focus:ring-teal-600"
                        />
                        <p class="text-[11px] text-gray-400">
                            Optional: Provide direct link to aerial drone footage, river gauge inspection video, or cloud media repository.
                        </p>
                    </div>

                    <!-- Geo-tagged items list -->
                    <div v-if="form.media_items.length === 0" class="p-6 border-2 border-dashed border-gray-200 rounded-xl text-center space-y-2 bg-gray-50/50">
                        <div class="text-2xl">📸</div>
                        <p class="text-xs font-bold text-gray-600">No site photos added yet</p>
                        <p class="text-[11px] text-gray-400">Click "Add Site Photo/Video" above to attach geo-stamped field photos for Brahmaputra Board inspection review.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="(item, idx) in form.media_items"
                            :key="idx"
                            class="p-4 rounded-xl border border-gray-200 bg-white shadow-2xs space-y-3 relative group"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-700">Media Item #{{ idx + 1 }}</span>
                                <button
                                    type="button"
                                    @click="removeMediaSlot(idx)"
                                    class="text-rose-500 hover:text-rose-700 text-xs font-bold"
                                >
                                    Remove
                                </button>
                            </div>

                            <!-- File input -->
                            <div>
                                <input
                                    type="file"
                                    accept="image/*,video/*"
                                    @change="e => onMediaFileSelected(e, idx)"
                                    class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100"
                                />
                            </div>

                            <!-- Caption -->
                            <div>
                                <input
                                    type="text"
                                    v-model="item.caption"
                                    placeholder="Photo caption (e.g. Chainage 1+200m revetment view)"
                                    class="w-full rounded-lg border-gray-300 text-xs py-1.5 px-2.5"
                                />
                            </div>

                            <!-- Geo Coordinates -->
                            <div class="grid grid-cols-2 gap-2">
                                <input
                                    type="text"
                                    v-model="item.lat"
                                    placeholder="Latitude (e.g. 26.1820)"
                                    class="rounded-lg border-gray-300 text-xs font-mono py-1 px-2"
                                />
                                <input
                                    type="text"
                                    v-model="item.lng"
                                    placeholder="Longitude (e.g. 91.7482)"
                                    class="rounded-lg border-gray-300 text-xs font-mono py-1 px-2"
                                />
                            </div>

                            <!-- Auto-GPS Button -->
                            <button
                                type="button"
                                @click="captureGps(item)"
                                :disabled="item.isLocating"
                                class="w-full inline-flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-[11px] font-bold transition"
                            >
                                <span v-if="item.isLocating" class="w-3 h-3 border-2 border-gray-700 border-t-transparent rounded-full animate-spin"></span>
                                <span>📍</span>
                                <span>{{ item.isLocating ? 'Detecting GPS...' : 'Auto-Detect Current GPS Coordinates' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- PDF Official Report -->
                    <div class="border-t border-gray-100 pt-4">
                        <label class="block text-xs font-black text-gray-700 mb-1.5 flex items-center gap-1.5">
                            <span>📄</span>
                            <span>Official Monthly Progress Report PDF (Signed Copy)</span>
                        </label>
                        <input
                            type="file"
                            accept=".pdf"
                            @change="e => form.progress_report_pdf = e.target.files[0]"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#0F4C9F]/10 file:text-[#0F4C9F] hover:file:bg-[#0F4C9F]/20 cursor-pointer"
                        />
                        <p class="text-[11px] text-gray-400 mt-1">
                            Attach formal executive summary signed by the Executive Engineer / Chief Engineer.
                        </p>
                    </div>
                </div>

                <!-- SECTION 5: NARRATIVE & SUBMISSION ACTIONS -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-black text-gray-700 mb-1.5">
                            Comprehensive Narrative Summary &amp; Highlights
                        </label>
                        <textarea
                            v-model="form.narrative_report"
                            rows="4"
                            placeholder="State agency remarks on progress pace, river discharge observations, flood season preparations, challenges faced, or clearance updates..."
                            class="w-full rounded-xl border-gray-300 text-xs focus:border-[#0F4C9F] focus:ring-[#0F4C9F]"
                        ></textarea>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <Link
                            :href="route('progress-reports.index')"
                            class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 text-xs font-bold hover:bg-gray-50 transition"
                        >
                            Cancel &amp; Discard
                        </Link>

                        <button
                            type="submit"
                            :disabled="isSubmitting || !form.scheme_id"
                            class="px-7 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-black text-xs shadow-md hover:shadow-lg transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
                        >
                            <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <span>Submit Monthly Progress Report ({{ form.reporting_period }})</span>
                        </button>
                    </div>
                </div>

            </form>

        </div>
    </AuthenticatedLayout>
</template>
