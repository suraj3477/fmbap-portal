<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GeoTaggedGallery from '@/Components/GeoTaggedGallery.vue';

const props = defineProps({
    paymentRequest: {
        type: Object,
        required: true,
    },
    report: {
        type: Object,
        default: null,
    },
});

const isSubmitting = ref(false);
const errorMsg = ref('');
const successMsg = ref('');

const fmbapProject = computed(() => props.paymentRequest?.scheme?.fmbap_project || props.paymentRequest?.scheme?.fmbapProject || null);

// Geo files handling
const newGeoFiles = ref([]); // Array of { file, lat, lng, caption, previewUrl }
const fileInput = ref(null);

const form = ref({
    inspection_date: '',
    site_description: '',
    bb_physical_progress_pct: '',
    bb_physical_progress_description: '',
    bb_financial_progress_pct: '',
    bb_financial_progress_description: '',
    bb_report_doc: null,
});

onMounted(() => {
    if (props.report) {
        form.value = {
            inspection_date: props.report.inspection_date || '',
            site_description: props.report.site_description || '',
            bb_physical_progress_pct: props.report.bb_physical_progress_pct || '',
            bb_physical_progress_description: props.report.bb_physical_progress_description || '',
            bb_financial_progress_pct: props.report.bb_financial_progress_pct || '',
            bb_financial_progress_description: props.report.bb_financial_progress_description || '',
            bb_report_doc: null,
        };
    }
});

const handleGeoFilesUpload = (event) => {
    const files = Array.from(event.target.files);
    files.forEach(file => {
        newGeoFiles.value.push({
            file: file,
            lat: '',
            lng: '',
            caption: '',
            previewUrl: URL.createObjectURL(file),
            type: file.type.startsWith('video') ? 'video' : 'photo'
        });
    });
    // Clear input so same file can be selected again if needed
    if (fileInput.value) fileInput.value.value = '';
};

const removeGeoFile = (index) => {
    URL.revokeObjectURL(newGeoFiles.value[index].previewUrl);
    newGeoFiles.value.splice(index, 1);
};

const bbRemarks = ref('');

const saveReport = async (isFinal = false, decision = 'FORWARDED_TO_MOJS') => {
    isSubmitting.value = true;
    errorMsg.value = '';
    successMsg.value = '';

    const formData = new FormData();
    if (isFinal) {
        formData.append('is_final_submit', 1);
        formData.append('decision', decision);
        if (bbRemarks.value) {
            formData.append('bb_remarks', bbRemarks.value);
        }
    }

    // Append standard fields
    Object.keys(form.value).forEach(key => {
        if (form.value[key] !== null && form.value[key] !== '') {
            formData.append(key, form.value[key]);
        }
    });

    // Append geo files array structure
    newGeoFiles.value.forEach((item, index) => {
        formData.append(`geo_files[${index}][file]`, item.file);
        formData.append(`geo_files[${index}][lat]`, item.lat);
        formData.append(`geo_files[${index}][lng]`, item.lng);
        formData.append(`geo_files[${index}][caption]`, item.caption);
    });

    try {
        await axios.post(route('monitoring-requests.report.store', props.paymentRequest.id), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        if (isFinal) {
            router.visit(route('fund-release.show', props.paymentRequest.id));
        } else {
            successMsg.value = 'Draft saved successfully.';
            router.reload({ only: ['report'] });
            newGeoFiles.value = [];
        }
    } catch (error) {
        errorMsg.value = error.response?.data?.message || 'An error occurred while saving.';
    } finally {
        isSubmitting.value = false;
    }
};

const isSubmitted = computed(() => ['FORWARDED_TO_MOJS', 'APPROVED', 'REJECTED'].includes(props.paymentRequest?.status));
</script>

<template>
    <Head title="Fill BB Monitoring Report" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('monitoring-requests.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    BB Monitoring Report
                </h2>
                <span class="bg-indigo-100 text-indigo-800 text-sm px-3 py-1 rounded-full font-bold ml-auto">
                    Req #{{ paymentRequest.id }}
                </span>
            </div>
        </template>

        <div class="py-8 mx-auto sm:px-6 lg:px-8 w-full">
            
            <!-- Comprehensive Scheme & FMBAP Project Financial Tracking Dossier Details -->
            <div class="bg-white rounded-xl shadow-sm border border-indigo-100 overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-indigo-900 via-blue-900 to-slate-900 px-6 py-4 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-lg">Comprehensive Scheme & FMBAP Financial Dossier</h3>
                        <p class="text-xs text-indigo-200 mt-0.5">Payment Request #{{ paymentRequest.id }} | Scheme Code: {{ paymentRequest.scheme?.scheme_code }}</p>
                    </div>
                    <span class="bg-white/20 backdrop-blur text-white text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider">
                        {{ paymentRequest.state }} State
                    </span>
                </div>

                <div class="p-6 space-y-6">
                    <!-- 1. Scheme Meta Info -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pb-4 border-b border-gray-100 text-sm">
                        <div>
                            <span class="text-xs font-semibold text-gray-500 uppercase block">Scheme Name</span>
                            <span class="font-bold text-gray-900">{{ paymentRequest.scheme?.scheme_name || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-gray-500 uppercase block">Scheme Code</span>
                            <span class="font-mono font-medium text-gray-900">{{ paymentRequest.scheme?.scheme_code || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-gray-500 uppercase block">Category / Type</span>
                            <span class="font-medium text-gray-900">{{ paymentRequest.scheme?.fmbap_category || 'FMBAP Scheme' }}</span>
                        </div>
                    </div>

                    <!-- 2. Full FMBAP Financial Tracking Overview (matching FMBAP Project table) -->
                    <div class="border border-indigo-200 rounded-xl overflow-hidden shadow-sm">
                        <div class="bg-indigo-50/80 px-4 py-2.5 border-b border-indigo-200 flex justify-between items-center">
                            <h4 class="font-bold text-xs uppercase text-indigo-900 tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                FMBAP Project Financial Tracking Summary
                            </h4>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-indigo-900 bg-indigo-100 border border-indigo-300 px-3 py-1 rounded-md shadow-sm">
                                    Funding Pattern: {{ fmbapProject?.funding_pattern || (paymentRequest.scheme?.central_share_pct ? `${paymentRequest.scheme.central_share_pct}/${paymentRequest.scheme.state_share_pct}` : '70/30') }}
                                </span>
                            </div>
                        </div>
                        <div class="p-4 grid grid-cols-2 md:grid-cols-5 gap-3 bg-white text-sm">
                            <div class="bg-gray-50 p-3 rounded-lg border">
                                <span class="text-[11px] font-bold text-gray-500 uppercase block">Est. Cost / IMC</span>
                                <span class="text-base font-extrabold text-gray-900">₹{{ fmbapProject?.estimated_cost_cr || paymentRequest.scheme?.sanctioned_amount_cr || '0.00' }} Cr</span>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg border">
                                <span class="text-[11px] font-bold text-gray-500 uppercase block">Executed Amount</span>
                                <span class="text-base font-extrabold text-blue-900">₹{{ fmbapProject?.executed_amount_cr || '0.00' }} Cr</span>
                            </div>
                            <div class="bg-indigo-50/60 p-3 rounded-lg border border-indigo-200">
                                <span class="text-[11px] font-bold text-indigo-800 uppercase block">Funding Pattern</span>
                                <span class="text-base font-extrabold text-indigo-950">{{ fmbapProject?.funding_pattern || (paymentRequest.scheme?.central_share_pct ? `${paymentRequest.scheme.central_share_pct}/${paymentRequest.scheme.state_share_pct}` : '70/30') }}</span>
                            </div>
                            <div class="bg-blue-50/50 p-3 rounded-lg border border-blue-100">
                                <span class="text-[11px] font-bold text-blue-700 uppercase block">Central Share</span>
                                <span class="text-sm font-bold text-blue-950">₹{{ fmbapProject?.central_share_cr || (paymentRequest.scheme?.sanctioned_amount_cr ? (paymentRequest.scheme.sanctioned_amount_cr * (paymentRequest.scheme.central_share_pct || 70) / 100).toFixed(2) : '0.00') }} Cr</span>
                            </div>
                            <div class="bg-purple-50/50 p-3 rounded-lg border border-purple-100">
                                <span class="text-[11px] font-bold text-purple-700 uppercase block">State Share</span>
                                <span class="text-sm font-bold text-purple-950">₹{{ fmbapProject?.state_share_cr || (paymentRequest.scheme?.sanctioned_amount_cr ? (paymentRequest.scheme.sanctioned_amount_cr * (paymentRequest.scheme.state_share_pct || 30) / 100).toFixed(2) : '0.00') }} Cr</span>
                            </div>

                            <div class="bg-emerald-50/50 p-3 rounded-lg border border-emerald-100">
                                <span class="text-[11px] font-bold text-emerald-700 uppercase block">Released Central Share</span>
                                <span class="text-sm font-bold text-emerald-950">₹{{ fmbapProject?.released_central_share_cr || '0.00' }} Cr</span>
                            </div>
                            <div class="bg-teal-50/50 p-3 rounded-lg border border-teal-100">
                                <span class="text-[11px] font-bold text-teal-700 uppercase block">Released State Share</span>
                                <span class="text-sm font-bold text-teal-950">₹{{ fmbapProject?.released_state_share_cr || '0.00' }} Cr</span>
                            </div>
                            <div class="bg-amber-50/50 p-3 rounded-lg border border-amber-100">
                                <span class="text-[11px] font-bold text-amber-700 uppercase block">Balance Central Share</span>
                                <span class="text-sm font-bold text-amber-950">₹{{ fmbapProject?.balance_central_share_cr || '0.00' }} Cr</span>
                            </div>
                            <div class="bg-orange-50/50 p-3 rounded-lg border border-orange-100">
                                <span class="text-[11px] font-bold text-orange-700 uppercase block">Balance State Share</span>
                                <span class="text-sm font-bold text-orange-950">₹{{ fmbapProject?.balance_state_share_cr || '0.00' }} Cr</span>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Current Payment Request Claim Details -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 bg-indigo-50/50 rounded-xl border border-indigo-100 text-sm">
                        <div>
                            <span class="text-xs font-semibold text-indigo-600 uppercase block">Requested Amount</span>
                            <span class="text-xl font-extrabold text-indigo-900">₹{{ paymentRequest.requested_amount_cr || 0 }} Cr</span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-indigo-600 uppercase block">Instalment No.</span>
                            <span class="text-base font-bold text-indigo-950">Instalment #{{ paymentRequest.instalment_number || 1 }}</span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-indigo-600 uppercase block">State Physical Claim</span>
                            <span class="text-base font-bold text-blue-700">{{ paymentRequest.physical_progress_pct || 0 }}%</span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-indigo-600 uppercase block">State Financial Claim</span>
                            <span class="text-base font-bold text-emerald-700">{{ paymentRequest.financial_progress_pct || 0 }}%</span>
                        </div>
                    </div>

                    <!-- 4. Remarks Breakdown -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <span class="text-xs font-bold text-gray-500 uppercase block mb-1">State Remarks</span>
                            <p class="text-gray-900 font-medium whitespace-pre-line text-xs">{{ fmbapProject?.remarks || paymentRequest.state_remarks || 'No state remarks provided.' }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <span class="text-xs font-bold text-gray-500 uppercase block mb-1">BB Remarks</span>
                            <p class="text-gray-900 font-medium whitespace-pre-line text-xs">{{ fmbapProject?.bb_remarks || 'No BB remarks provided.' }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <span class="text-xs font-bold text-gray-500 uppercase block mb-1">MoJS Remarks</span>
                            <p class="text-gray-900 font-medium whitespace-pre-line text-xs">{{ fmbapProject?.mojs_remarks || 'No MoJS remarks provided.' }}</p>
                        </div>
                    </div>

                    <div v-if="paymentRequest.bank_details || paymentRequest.narrative_progress_report" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div v-if="paymentRequest.bank_details" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <span class="text-xs font-bold text-gray-500 uppercase block mb-1">Bank Details</span>
                            <p class="text-gray-900 font-medium whitespace-pre-line text-xs">{{ paymentRequest.bank_details }}</p>
                        </div>
                        <div v-if="paymentRequest.narrative_progress_report" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <span class="text-xs font-bold text-gray-500 uppercase block mb-1">State Narrative Progress Report</span>
                            <p class="text-gray-900 whitespace-pre-line text-xs">{{ paymentRequest.narrative_progress_report }}</p>
                        </div>
                    </div>

                    <!-- 5. All Submitted Documents (Project Docs + Claim Docs) -->
                    <div>
                        <span class="text-xs font-bold text-gray-700 uppercase block mb-3">All Project & Claim Documents (PDFs)</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            
                            <!-- State Govt Doc -->
                            <a v-if="fmbapProject?.state_govt_doc_path" :href="fmbapProject.state_govt_doc_path" target="_blank" class="flex items-center gap-3 p-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg text-blue-900 text-sm font-semibold transition">
                                <svg class="w-6 h-6 text-blue-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>
                                <div class="overflow-hidden">
                                    <p class="font-bold text-xs truncate">State Govt Document</p>
                                    <p class="text-[10px] text-blue-700">{{ fmbapProject.state_govt_submission_date || 'Click to View PDF' }}</p>
                                </div>
                            </a>

                            <!-- BB Official Doc -->
                            <a v-if="fmbapProject?.brahmaputra_board_doc_path" :href="fmbapProject.brahmaputra_board_doc_path" target="_blank" class="flex items-center gap-3 p-3 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg text-purple-900 text-sm font-semibold transition">
                                <svg class="w-6 h-6 text-purple-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>
                                <div class="overflow-hidden">
                                    <p class="font-bold text-xs truncate">Brahmaputra Board Doc</p>
                                    <p class="text-[10px] text-purple-700">{{ fmbapProject.brahmaputra_board_submission_date || 'Click to View PDF' }}</p>
                                </div>
                            </a>

                            <!-- MoJS Official Doc -->
                            <a v-if="fmbapProject?.mojs_doc_path" :href="fmbapProject.mojs_doc_path" target="_blank" class="flex items-center gap-3 p-3 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg text-indigo-900 text-sm font-semibold transition">
                                <svg class="w-6 h-6 text-indigo-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>
                                <div class="overflow-hidden">
                                    <p class="font-bold text-xs truncate">FM Section MoJS Doc</p>
                                    <p class="text-[10px] text-indigo-700">{{ fmbapProject.mojs_submission_date || 'Click to View PDF' }}</p>
                                </div>
                            </a>

                            <!-- Utilization Certificate (UC) -->
                            <a v-if="paymentRequest.utilization_certificate_path" :href="paymentRequest.utilization_certificate_path" target="_blank" class="flex items-center gap-3 p-3 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg text-red-800 text-sm font-semibold transition">
                                <svg class="w-6 h-6 text-red-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>
                                <div class="overflow-hidden">
                                    <p class="font-bold text-xs truncate">Utilization Certificate (UC)</p>
                                    <p class="text-[10px] text-red-600">Click to View PDF</p>
                                </div>
                            </a>

                            <!-- Claim Progress Report PDF -->
                            <a v-if="paymentRequest.progress_report_doc_path" :href="paymentRequest.progress_report_doc_path" target="_blank" class="flex items-center gap-3 p-3 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg text-emerald-800 text-sm font-semibold transition">
                                <svg class="w-6 h-6 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>
                                <div class="overflow-hidden">
                                    <p class="font-bold text-xs truncate">Claim Progress Report PDF</p>
                                    <p class="text-[10px] text-emerald-600">Click to View PDF</p>
                                </div>
                            </a>

                            <!-- Expenditure Vouchers -->
                            <template v-if="paymentRequest.voucher_doc_paths && paymentRequest.voucher_doc_paths.length > 0">
                                <a v-for="(vPath, idx) in paymentRequest.voucher_doc_paths" :key="idx" :href="vPath" target="_blank" class="flex items-center gap-3 p-3 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-lg text-amber-900 text-sm font-semibold transition">
                                    <svg class="w-6 h-6 text-amber-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z"></path></svg>
                                    <div class="overflow-hidden">
                                        <p class="font-bold text-xs truncate">Expenditure Voucher #{{ idx + 1 }}</p>
                                        <p class="text-[10px] text-amber-700">Click to View PDF</p>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-xl shadow border border-gray-200 p-8">
                
                <div v-if="errorMsg" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm font-medium">{{ errorMsg }}</div>
                <div v-if="successMsg" class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm font-medium">{{ successMsg }}</div>
                <div v-if="isSubmitted" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm font-bold flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    This report has already been submitted and cannot be edited.
                </div>

                <form @submit.prevent="saveReport(true)">
                    <div class="space-y-8" :class="{ 'opacity-60 pointer-events-none': isSubmitted }">
                        
                        <!-- Section 1: Inspection Meta -->
                        <div>
                            <h4 class="text-base font-bold text-indigo-900 border-b border-indigo-100 pb-2 mb-4">1. Inspection Details</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Date of Field Inspection</label>
                                    <input type="date" v-model="form.inspection_date" class="w-full rounded-md border-gray-300" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Site Description / Extent</label>
                                    <input type="text" v-model="form.site_description" placeholder="e.g. Chainage 0 to 500m" class="w-full rounded-md border-gray-300">
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Verified Progress -->
                        <div>
                            <h4 class="text-base font-bold text-indigo-900 border-b border-indigo-100 pb-2 mb-4">2. Verified Progress</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-gray-50 border border-gray-200 p-5 rounded-lg space-y-4">
                                    <h5 class="font-bold text-gray-800">Physical Progress</h5>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">BB Verified Percentage (%)</label>
                                        <input type="number" step="0.01" max="100" v-model="form.bb_physical_progress_pct" class="w-full rounded-md border-gray-300" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Remarks / Discrepancies</label>
                                        <textarea v-model="form.bb_physical_progress_description" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                                    </div>
                                </div>
                                <div class="bg-gray-50 border border-gray-200 p-5 rounded-lg space-y-4">
                                    <h5 class="font-bold text-gray-800">Financial Progress</h5>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">BB Verified Percentage (%)</label>
                                        <input type="number" step="0.01" max="100" v-model="form.bb_financial_progress_pct" class="w-full rounded-md border-gray-300" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Remarks / Discrepancies</label>
                                        <textarea v-model="form.bb_financial_progress_description" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Evidence -->
                        <div>
                            <h4 class="text-base font-bold text-indigo-900 border-b border-indigo-100 pb-2 mb-4">3. Evidence & Official Report</h4>
                            
                            <!-- Existing Evidence -->
                            <div v-if="report?.geo_tagged_files?.length" class="mb-6">
                                <p class="text-sm font-semibold text-gray-700 mb-2">Previously Uploaded Evidence</p>
                                <GeoTaggedGallery :files="report.geo_tagged_files" />
                            </div>

                            <!-- Upload New Evidence -->
                            <div class="mb-6 border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50 hover:bg-gray-100 transition relative text-center">
                                <label class="block text-sm font-bold text-gray-700 cursor-pointer">
                                    <span class="text-indigo-600 underline">+ Add Geo-Tagged Photos/Videos</span>
                                    <input type="file" ref="fileInput" @change="handleGeoFilesUpload" accept="image/*,video/*" multiple class="hidden">
                                </label>
                                <p class="text-xs text-gray-500 mt-1">Select multiple files (JPG, PNG, MP4)</p>
                            </div>

                            <!-- Selected New Evidence Previews & Meta Input -->
                            <div v-if="newGeoFiles.length > 0" class="space-y-4 mb-8">
                                <p class="text-sm font-semibold text-gray-700">New Files to Upload ({{ newGeoFiles.length }})</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-for="(item, index) in newGeoFiles" :key="index" class="border rounded-lg p-3 bg-white flex gap-4 shadow-sm relative">
                                        <button @click.prevent="removeGeoFile(index)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 shadow hover:bg-red-600">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                        
                                        <div class="w-24 h-24 shrink-0 rounded bg-gray-100 overflow-hidden">
                                            <img v-if="item.type==='photo'" :src="item.previewUrl" class="w-full h-full object-cover">
                                            <video v-else :src="item.previewUrl" class="w-full h-full object-cover"></video>
                                        </div>
                                        <div class="flex-1 space-y-2">
                                            <div class="grid grid-cols-2 gap-2">
                                                <input type="text" v-model="item.lat" placeholder="Latitude" class="w-full text-xs rounded border-gray-300 px-2 py-1">
                                                <input type="text" v-model="item.lng" placeholder="Longitude" class="w-full text-xs rounded border-gray-300 px-2 py-1">
                                            </div>
                                            <textarea v-model="item.caption" placeholder="Description/Caption..." rows="2" class="w-full text-xs rounded border-gray-300 px-2 py-1"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PDF Upload -->
                            <div class="mt-4">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Official BB Signed Monitoring Report (PDF)</label>
                                <input type="file" @change="e => form.bb_report_doc = e.target.files[0]" accept=".pdf" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-300 rounded p-1">
                                <p v-if="report?.bb_report_doc_path" class="text-xs mt-2 text-emerald-600 font-medium">A PDF is already uploaded. Uploading a new one will replace it.</p>
                            </div>
                        </div>

                        <!-- Section 4: Decision & Official Remarks -->
                        <div>
                            <h4 class="text-base font-bold text-indigo-900 border-b border-indigo-100 pb-2 mb-4">4. BB Decision & Recommendation Remarks</h4>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Remarks / Reason for Decision <span class="text-xs font-normal text-gray-500">(Will be visible to State and MoJS)</span></label>
                                <textarea v-model="bbRemarks" rows="3" placeholder="Enter inspection summary, reasons for sending back for correction, or rejection remarks..." class="w-full rounded-md border-gray-300 text-sm"></textarea>
                            </div>
                        </div>

                    </div>

                    <!-- Footer Actions -->
                    <div v-if="!isSubmitted" class="mt-8 pt-5 border-t border-gray-200 flex flex-wrap items-center justify-between gap-3">
                        <button 
                            type="button" 
                            @click="saveReport(false)" 
                            class="px-5 py-2.5 border-2 border-indigo-200 text-indigo-700 bg-indigo-50 rounded-lg font-bold hover:bg-indigo-100 transition"
                            :disabled="isSubmitting"
                        >
                            Save as Draft
                        </button>

                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Send to State for Correction -->
                            <button 
                                type="button" 
                                @click="saveReport(true, 'NEEDS_CORRECTION')" 
                                class="px-5 py-2.5 bg-amber-100 text-amber-900 border border-amber-300 rounded-lg font-bold hover:bg-amber-200 transition flex items-center gap-2"
                                :disabled="isSubmitting"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                Send to State for Correction
                            </button>

                            <!-- Reject Request -->
                            <button 
                                type="button" 
                                @click="saveReport(true, 'REJECTED')" 
                                class="px-5 py-2.5 bg-red-100 text-red-800 border border-red-300 rounded-lg font-bold hover:bg-red-200 transition flex items-center gap-2"
                                :disabled="isSubmitting"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                Reject Request
                            </button>

                            <!-- Submit & Forward to MoJS -->
                            <button 
                                type="button"
                                @click="saveReport(true, 'FORWARDED_TO_MOJS')" 
                                class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg font-bold shadow hover:bg-indigo-700 transition flex items-center gap-2"
                                :disabled="isSubmitting"
                            >
                                <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Submit & Forward to MoJS
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
