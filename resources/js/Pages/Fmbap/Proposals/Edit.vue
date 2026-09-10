<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    proposal: Object,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isState = computed(() => ['state', 'state_official'].includes(user.value?.role));

const form = useForm({
    _method: 'POST', // standard post request since PHP has issues parsing PUT/PATCH requests with files
    scheme_name: props.proposal.scheme_name || '',
    project_type: props.proposal.project_type || '',
    river_basin: props.proposal.river_basin || '',
    district: props.proposal.district || '',
    latitude: props.proposal.latitude || '',
    longitude: props.proposal.longitude || '',
    estimated_cost_cr: props.proposal.estimated_cost_cr || '',
    description: props.proposal.description || '',
    photos: [],
    videos: [],
    pdfs: [],
});

const submit = () => {
    form.post(route('fmbap.proposals.update', props.proposal.id), {
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};

const handlePhotoUpload = (e) => {
    form.photos = Array.from(e.target.files);
};

const handleVideoUpload = (e) => {
    form.videos = Array.from(e.target.files);
};

const handlePdfUpload = (e) => {
    form.pdfs = Array.from(e.target.files);
};

const storageUrl = (path) => `/storage/${path}`;
</script>

<template>
    <Head title="Edit Proposal" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit & Correct FMBAP Proposal
                </h2>
                <Link :href="route('fmbap.proposals.index')" class="text-gray-500 hover:text-gray-700 font-medium text-sm">
                    &larr; Back to Proposals
                </Link>
            </div>
        </template>

        <div class="py-6 mx-auto sm:px-6 lg:px-8 w-full">
            <!-- Warning / Instruction Banner -->
            <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h4 class="text-sm font-bold text-amber-800">Proposal Returned for Correction</h4>
                    <p class="text-xs text-amber-700 mt-1">
                        Please review the feedback and remarks from the authority below, update the required fields or media attachments, and click <strong>Resubmit Proposal</strong>. Saving changes will automatically reset the status to "Submitted by State".
                    </p>
                </div>
            </div>

            <!-- Authority Remarks View -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-if="proposal.bb_remarks" class="p-4 bg-gray-50 border border-gray-200 rounded-xl">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Brahmaputra Board Remarks</p>
                    <p class="text-sm text-gray-700 mt-1 whitespace-pre-wrap">{{ proposal.bb_remarks }}</p>
                </div>
                <div v-if="proposal.mojs_remarks" class="p-4 bg-indigo-50 border border-indigo-100 rounded-xl">
                    <p class="text-xs font-semibold text-indigo-500 uppercase tracking-wide">MoJS Remarks</p>
                    <p class="text-sm text-gray-700 mt-1 whitespace-pre-wrap">{{ proposal.mojs_remarks }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Scheme Name <span class="text-red-500">*</span></label>
                            <textarea v-model="form.scheme_name" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required placeholder="Enter scheme name"></textarea>
                            <div v-if="form.errors.scheme_name" class="text-red-500 text-xs mt-1">{{ form.errors.scheme_name }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Project Type</label>
                            <input v-if="isState" v-model="form.project_type" type="text" placeholder="Enter project type manually" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <select v-else v-model="form.project_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Type</option>
                                <option value="Flood Control">Flood Control</option>
                                <option value="Anti-Erosion">Anti-Erosion</option>
                                <option value="Drainage Development">Drainage Development</option>
                                <option value="Anti-Sea Erosion">Anti-Sea Erosion</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Estimated Cost (in Crores) <span class="text-red-500">*</span></label>
                            <input v-model="form.estimated_cost_cr" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <div v-if="form.errors.estimated_cost_cr" class="text-red-500 text-xs mt-1">{{ form.errors.estimated_cost_cr }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">River Basin</label>
                            <input v-model="form.river_basin" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">District</label>
                            <input v-model="form.district" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Latitude</label>
                            <input v-model="form.latitude" type="text" placeholder="e.g. 26.2006" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Longitude</label>
                            <input v-model="form.longitude" type="text" placeholder="e.g. 92.9376" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Description / Justification</label>
                            <textarea v-model="form.description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                    </div>

                    <!-- Existing Attachments View -->
                    <div class="border-t border-gray-200 pt-6" v-if="(proposal.photos && proposal.photos.length) || (proposal.videos && proposal.videos.length) || (proposal.pdfs && proposal.pdfs.length)">
                        <h3 class="text-base font-bold text-gray-800 mb-4">Existing Attachments</h3>
                        
                        <!-- Photos -->
                        <div v-if="proposal.photos && proposal.photos.length" class="mb-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Photos</p>
                            <div class="grid grid-cols-6 gap-2">
                                <div v-for="(photo, idx) in proposal.photos" :key="idx" class="relative group">
                                    <img :src="storageUrl(photo)" class="w-full h-16 object-cover rounded-lg border" />
                                </div>
                            </div>
                        </div>

                        <!-- PDFs -->
                        <div v-if="proposal.pdfs && proposal.pdfs.length" class="mb-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">PDF Documents</p>
                            <div class="flex flex-wrap gap-2">
                                <a v-for="(pdf, idx) in proposal.pdfs" :key="idx" :href="storageUrl(pdf)" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1 bg-red-50 text-red-700 text-xs font-medium rounded-lg border border-red-100 hover:bg-red-100 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    Document #{{ idx + 1 }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Media Uploads -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Upload Additional Media Attachments</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Photos (JPEG/PNG)</label>
                                <input type="file" multiple accept="image/*" @change="handlePhotoUpload" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <div v-if="form.errors.photos" class="text-red-500 text-xs mt-1">{{ form.errors.photos }}</div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Videos (MP4/MOV)</label>
                                <input type="file" multiple accept="video/*" @change="handleVideoUpload" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <div v-if="form.errors.videos" class="text-red-500 text-xs mt-1">{{ form.errors.videos }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">PDF Documents</label>
                                <input type="file" multiple accept="application/pdf" @change="handlePdfUpload" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <div v-if="form.errors.pdfs" class="text-red-500 text-xs mt-1">{{ form.errors.pdfs }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-6 flex justify-end">
                        <Link :href="route('fmbap.proposals.index')" class="px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 mr-3">
                            Cancel
                        </Link>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Resubmit Proposal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
