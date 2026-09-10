<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AuditTimeline from '@/Components/AuditTimeline.vue';

const props = defineProps({
    report: {
        type: Object,
        required: true,
    },
    userRole: String,
});

const isBB = computed(() => props.userRole === 'board_official' || props.userRole === 'super_admin');
const isMoJS = computed(() => props.userRole === 'mojs_official' || props.userRole === 'super_admin');

const showDecisionModal = ref(false);
const decisionType = ref('');
const decisionForm = useForm({
    decision: '',
    remarks: '',
});

const openDecisionModal = (type) => {
    decisionType.value = type;
    decisionForm.decision = type;
    decisionForm.remarks = '';
    showDecisionModal.value = true;
};

const submitDecision = () => {
    const routeName = isBB.value ? 'progress-reports.bbReview' : 'progress-reports.mojsReview';
    decisionForm.post(route(routeName, props.report.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDecisionModal.value = false;
        },
    });
};
</script>

<template>
    <Head title="Progress Report Details" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Progress Report Details
                </h2>
                <span 
                    class="px-3 py-1 text-xs font-bold rounded-full ml-auto border"
                    :class="{
                        'bg-blue-50 text-blue-700 border-blue-200': report.status === 'SUBMITTED',
                        'bg-purple-50 text-purple-700 border-purple-200': report.status === 'REVIEWED_BY_BB',
                        'bg-emerald-50 text-emerald-700 border-emerald-200': report.status === 'APPROVED',
                        'bg-amber-50 text-amber-700 border-amber-200': report.status === 'NEEDS_CORRECTION'
                    }"
                >
                    Status: {{ report.status.replace(/_/g, ' ') }}
                </span>
            </div>
        </template>

        <div class="py-8 mx-auto sm:px-6 lg:px-8 w-full">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left Column -->
                <div class="col-span-2 space-y-6">
                    
                    <!-- Context -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-start gap-4">
                            <div class="bg-gray-100 p-3 rounded-lg text-gray-600 shrink-0">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg text-gray-900">{{ report.scheme?.scheme_name }}</h3>
                                <p class="text-sm text-gray-500 mt-1">Code: <span class="font-semibold">{{ report.scheme?.scheme_code }}</span> | State: <span class="font-semibold">{{ report.scheme?.state }}</span></p>
                                <p class="text-sm text-emerald-700 font-bold mt-2">Reporting Period: {{ report.reporting_period }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="grid grid-cols-2">
                            <div class="p-6 border-r border-gray-200">
                                <h4 class="font-bold text-blue-900 mb-4 flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                    Physical Progress
                                </h4>
                                <div class="text-4xl font-light text-gray-900 mb-2">{{ report.physical_progress_pct }}%</div>
                                <p class="text-sm text-gray-600 leading-relaxed">{{ report.physical_progress_description || 'No description provided.' }}</p>
                            </div>
                            <div class="p-6">
                                <h4 class="font-bold text-green-900 mb-4 flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                    Financial Progress
                                </h4>
                                <div class="text-4xl font-light text-gray-900 mb-2">{{ report.financial_progress_pct }}%</div>
                                <p class="text-sm text-gray-600 leading-relaxed">{{ report.financial_progress_description || 'No description provided.' }}</p>
                            </div>
                        </div>
                        <div class="p-6 border-t border-gray-200 bg-gray-50">
                            <h4 class="font-semibold text-gray-700 mb-2">Narrative Summary</h4>
                            <p class="text-sm text-gray-800 bg-white p-4 rounded border border-gray-200">{{ report.narrative_report || 'None provided.' }}</p>
                        </div>
                        <div v-if="report.progress_report_doc_path" class="p-6 border-t border-gray-200 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>
                                <div>
                                    <p class="text-sm font-bold text-gray-800">Attached Official PDF</p>
                                </div>
                            </div>
                            <a :href="report.progress_report_doc_path" target="_blank" class="text-emerald-600 font-semibold text-sm hover:underline border border-emerald-200 px-4 py-2 rounded">View Document</a>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Audit & Actions -->
                <div class="space-y-6 lg:sticky lg:top-6 lg:self-start lg:max-h-[90vh] overflow-y-auto pr-2 pb-4">
                    
                    <!-- Action Panel -->
                    <div v-if="report.status !== 'APPROVED'" class="bg-white rounded-xl shadow border border-emerald-200 overflow-hidden">
                        <div class="bg-emerald-50 px-6 py-4 border-b border-emerald-200">
                            <h3 class="font-bold text-emerald-900">Review Actions</h3>
                        </div>
                        <div class="p-6 flex flex-col gap-3">
                            
                            <!-- BB Actions -->
                            <template v-if="isBB && report.status === 'SUBMITTED'">
                                <button @click="openDecisionModal('REVIEWED_BY_BB')" class="w-full bg-indigo-600 text-white font-bold py-2.5 rounded hover:bg-indigo-700">
                                    Acknowledge & Forward
                                </button>
                                <button @click="openDecisionModal('NEEDS_CORRECTION')" class="w-full bg-amber-100 text-amber-800 border border-amber-300 font-bold py-2.5 rounded hover:bg-amber-200 flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                    Ask State to Correct
                                </button>
                            </template>
                            
                            <!-- MoJS Actions -->
                            <template v-if="isMoJS && report.status === 'REVIEWED_BY_BB'">
                                <button @click="openDecisionModal('APPROVED')" class="w-full bg-emerald-600 text-white font-bold py-2.5 rounded hover:bg-emerald-700">
                                    Approve & File Report
                                </button>
                                <button @click="openDecisionModal('NEEDS_CORRECTION')" class="w-full bg-amber-100 text-amber-800 border border-amber-300 font-bold py-2.5 rounded hover:bg-amber-200 flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                    Ask State to Correct
                                </button>
                            </template>

                            <div v-if="!isBB && !isMoJS && report.status !== 'NEEDS_CORRECTION'" class="text-sm text-gray-500 italic text-center">
                                Under review by higher authority.
                            </div>
                            <div v-if="report.status === 'NEEDS_CORRECTION' && userRole === 'state_official'" class="text-sm text-center">
                                <a :href="route('progress-reports.edit', report.id)" class="bg-amber-600 text-white px-4 py-2 block rounded font-bold hover:bg-amber-700">Go to Correction Form</a>
                            </div>
                        </div>
                    </div>

                    <AuditTimeline module="progress-reports" :recordId="report.id" />
                </div>
            </div>
        </div>

        <!-- Decision Modal -->
        <div v-if="showDecisionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-bold text-gray-900">Review Remarks</h3>
                
                <form @submit.prevent="submitDecision">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Remarks <span class="text-red-500">*</span></label>
                        <textarea v-model="decisionForm.remarks" rows="4" class="w-full border-gray-300 rounded" required placeholder="Enter review remarks..."></textarea>
                    </div>
                    
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showDecisionModal = false" class="px-4 py-2 border rounded font-medium">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-white rounded font-bold" :class="decisionType === 'NEEDS_CORRECTION' ? 'bg-amber-600' : 'bg-emerald-600'">
                            Confirm Action
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
