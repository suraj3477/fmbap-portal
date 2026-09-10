<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SchemeSearchDropdown from '@/Components/SchemeSearchDropdown.vue';

const isSubmitting = ref(false);
const errorMsg = ref('');

const form = ref({
    scheme_id: null,
    reporting_period: '',
    physical_progress_pct: '',
    physical_progress_description: '',
    financial_progress_pct: '',
    financial_progress_description: '',
    narrative_report: '',
    progress_report_pdf: null,
});

const submitReport = async () => {
    isSubmitting.value = true;
    errorMsg.value = '';

    const formData = new FormData();
    Object.keys(form.value).forEach(key => {
        if (form.value[key] !== null && form.value[key] !== '') {
            formData.append(key, form.value[key]);
        }
    });

    try {
        await axios.post(route('progress-reports.store'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        router.visit(route('progress-reports.index'));
    } catch (error) {
        errorMsg.value = error.response?.data?.message || 'An error occurred while saving.';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Head title="Submit Progress Report" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Submit Routine Progress Report
            </h2>
        </template>

        <div class="py-8 mx-auto sm:px-6 lg:px-8 w-full">
            <div class="bg-white rounded-xl shadow border border-gray-200 p-8">
                
                <div v-if="errorMsg" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm font-medium">
                    {{ errorMsg }}
                </div>

                <form @submit.prevent="submitReport" class="space-y-8">
                    
                    <!-- Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <SchemeSearchDropdown v-model="form.scheme_id" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Reporting Period <span class="text-red-500">*</span></label>
                            <input type="text" v-model="form.reporting_period" placeholder="e.g. Q1 2026-27 or April 2026" class="w-full rounded-md border-gray-300" required>
                        </div>
                    </div>

                    <!-- Progress Metrics -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 border-b pb-2 mb-4">Progress Metrics</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4 border p-4 rounded-lg bg-blue-50/30">
                                <h4 class="font-bold text-blue-800">Physical Progress</h4>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Percentage (%) <span class="text-red-500">*</span></label>
                                    <input type="number" step="0.01" max="100" v-model="form.physical_progress_pct" class="w-full rounded-md border-gray-300 text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Description</label>
                                    <textarea v-model="form.physical_progress_description" rows="3" class="w-full rounded-md border-gray-300 text-sm" placeholder="What works have been completed?"></textarea>
                                </div>
                            </div>

                            <div class="space-y-4 border p-4 rounded-lg bg-green-50/30">
                                <h4 class="font-bold text-green-800">Financial Progress</h4>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Percentage (%) <span class="text-red-500">*</span></label>
                                    <input type="number" step="0.01" max="100" v-model="form.financial_progress_pct" class="w-full rounded-md border-gray-300 text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Description</label>
                                    <textarea v-model="form.financial_progress_description" rows="3" class="w-full rounded-md border-gray-300 text-sm" placeholder="Financial utilization details..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Narrative & Docs -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 border-b pb-2 mb-4">Narrative & Attachments</h3>
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Narrative Summary Report</label>
                                <textarea v-model="form.narrative_report" rows="4" class="w-full rounded-md border-gray-300" placeholder="Provide a brief summary of the progress made during this period..."></textarea>
                            </div>

                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50 text-center relative hover:bg-gray-100 transition">
                                <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <label class="block text-sm font-bold text-gray-700">Attach Official Progress Report PDF (Optional)</label>
                                <input type="file" @change="e => form.progress_report_pdf = e.target.files[0]" accept=".pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                <div v-if="form.progress_report_pdf" class="text-xs text-emerald-600 font-semibold mt-2">
                                    {{ form.progress_report_pdf.name }}
                                </div>
                                <div v-else class="text-xs text-gray-400 mt-1">Click to browse</div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-8 pt-5 border-t border-gray-200 flex justify-end gap-4">
                        <Link :href="route('progress-reports.index')" class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition">
                            Cancel
                        </Link>
                        
                        <button 
                            type="submit" 
                            class="px-6 py-2.5 bg-emerald-600 text-white rounded-lg font-bold shadow hover:bg-emerald-700 transition flex items-center gap-2"
                            :disabled="isSubmitting || !form.scheme_id"
                        >
                            <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            Submit Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
