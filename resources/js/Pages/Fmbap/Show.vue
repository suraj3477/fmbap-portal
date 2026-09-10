<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    project: Object,
    auth_user: Object, // Passed from Controller
});

// Access current auth user via Inertia page props or passed props
const page = usePage();
const user = computed(() => props.auth_user || page.props.auth?.user);

// Permission Check: Only Super Admin and Board Officials can alter Status & Released Funds
const isBoardOrAdmin = computed(() => {
    return ['super_admin', 'board_official'].includes(user.value?.role);
});

// Form for status and progress updates
const updateForm = useForm({
    status: props.project.status,
    physical_progress_pct: props.project.physical_progress_pct,
    funds_released_cr: props.project.funds_released_cr,
    funds_utilized_cr: props.project.funds_utilized_cr,
    inspection_notes: props.project.inspection_notes || '',
});

const handleUpdate = () => {
    updateForm.patch(route('fmbap.update', props.project.id));
};

// Form for file uploads & geo-location
const docForm = useForm({
    document_type: 'INSPECTION_PHOTO',
    file: null,
    latitude: '',
    longitude: '',
});

// Auto-capture inspector location using browser Geolocation API
const captureLocation = () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                docForm.latitude = position.coords.latitude.toFixed(6);
                docForm.longitude = position.coords.longitude.toFixed(6);
            },
            () => {
                alert('Unable to retrieve location.');
            }
        );
    } else {
        alert('Geolocation is not supported by your browser.');
    }
};

const handleDocumentUpload = () => {
    docForm.post(route('fmbap.documents.upload', props.project.id), {
        forceFormData: true,
        onSuccess: () => {
            docForm.reset('file', 'latitude', 'longitude');
        },
    });
};
</script>

<template>
    <Head :title="`${project.project_code} - FMBAP Detail`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div>
                    <Link :href="route('dashboard')" class="text-xs text-blue-600 font-bold hover:underline mb-1 inline-block">
                        &larr; Back to Dashboard
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ project.project_code }}: {{ project.title }}
                    </h2>
                </div>

                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 bg-amber-100 text-amber-800 font-bold rounded-full text-sm">
                        {{ project.status }}
                    </span>

                    <a 
                        :href="route('fmbap.projects.pdf', project.id)" 
                        target="_blank"
                        class="rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700 shadow flex items-center gap-1"
                    >
                        📄 Download PDF Report
                    </a>
                </div>
            </div>
        </template>

        <div class="py-6   mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Key Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <p class="text-xs text-gray-500 font-bold uppercase">Sanctioned Cost</p>
                    <p class="text-xl font-bold text-gray-800 mt-1">₹ {{ project.sanctioned_cost_cr }} Cr</p>
                    <p class="text-xs text-gray-400 mt-1">
                        Central: ₹{{ project.central_share_cr }} Cr | State: ₹{{ project.state_share_cr }} Cr
                    </p>
                </div>
                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <p class="text-xs text-gray-500 font-bold uppercase">Funds Released</p>
                    <p class="text-xl font-bold text-emerald-600 mt-1">₹ {{ project.funds_released_cr }} Cr</p>
                </div>
                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <p class="text-xs text-gray-500 font-bold uppercase">Funds Utilized</p>
                    <p class="text-xl font-bold text-indigo-600 mt-1">₹ {{ project.funds_utilized_cr }} Cr</p>
                </div>
                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <p class="text-xs text-gray-500 font-bold uppercase">Physical Progress</p>
                    <p class="text-xl font-bold text-blue-600 mt-1">{{ project.physical_progress_pct }}%</p>
                </div>
            </div>

            <!-- Detail & Inspection Update Form -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Scheme Overview -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-3">
                    <h3 class="font-bold text-gray-800 border-b pb-2">Scheme Summary</h3>
                    <div><strong class="text-xs text-gray-500 uppercase block">State & District</strong> {{ project.state }} ({{ project.district }})</div>
                    <div><strong class="text-xs text-gray-500 uppercase block">River Basin</strong> {{ project.river_basin }}</div>
                    <div><strong class="text-xs text-gray-500 uppercase block">Component</strong> {{ project.component }}</div>
                    <div><strong class="text-xs text-gray-500 uppercase block">Created At</strong> {{ new Date(project.created_at).toLocaleDateString() }}</div>
                </div>

                <!-- Inspection Update Form with RBAC Controls -->
                <div class="md:col-span-2 bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                    <h3 class="font-bold text-gray-800 border-b pb-2 mb-4">Inspection & Status Update Panel</h3>

                    <form @submit.prevent="handleUpdate" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">
                                    Project Status 
                                    <span v-if="!isBoardOrAdmin" class="text-red-500 font-normal lowercase">(read-only)</span>
                                </label>
                                <!-- DISABLED FOR STATE OFFICIALS -->
                                <select 
                                    v-model="updateForm.status" 
                                    :disabled="!isBoardOrAdmin"
                                    class="mt-1 w-full border-gray-300 rounded-md text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                                >
                                    <option>DPR Submitted</option>
                                    <option>In Technical Review</option>
                                    <option>Sanctioned</option>
                                    <option>Work in Progress</option>
                                    <option>Monsoon Delayed</option>
                                    <option>Completed</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">Physical Progress (%)</label>
                                <input v-model="updateForm.physical_progress_pct" type="number" min="0" max="100" class="mt-1 w-full border-gray-300 rounded-md text-sm" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">
                                    Released Amount (₹ Cr)
                                    <span v-if="!isBoardOrAdmin" class="text-red-500 font-normal lowercase">(read-only)</span>
                                </label>
                                <!-- DISABLED FOR STATE OFFICIALS -->
                                <input 
                                    v-model="updateForm.funds_released_cr" 
                                    type="number" 
                                    step="0.01" 
                                    :disabled="!isBoardOrAdmin"
                                    class="mt-1 w-full border-gray-300 rounded-md text-sm disabled:bg-gray-100 disabled:cursor-not-allowed" 
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">Utilized Amount (₹ Cr)</label>
                                <input v-model="updateForm.funds_utilized_cr" type="number" step="0.01" class="mt-1 w-full border-gray-300 rounded-md text-sm" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase">Field Inspection & Technical Notes</label>
                            <textarea v-model="updateForm.inspection_notes" rows="3" placeholder="Enter observations from site visit..." class="mt-1 w-full border-gray-300 rounded-md text-sm"></textarea>
                        </div>

                        <div class="flex justify-end">
                            <button 
                                type="submit" 
                                :disabled="updateForm.processing"
                                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-md text-sm shadow disabled:opacity-50"
                            >
                                {{ updateForm.processing ? 'Saving...' : 'Save Technical Log & Updates' }}
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            <!-- Geo-Tagged Upload & Document Repository -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Upload Form -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-4">
                    <h3 class="font-bold text-gray-800 border-b pb-2">Upload Site Media / Document</h3>

                    <form @submit.prevent="handleDocumentUpload" class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase">Document Type</label>
                            <select v-model="docForm.document_type" class="mt-1 w-full border-gray-300 rounded-md text-sm">
                                <option value="INSPECTION_PHOTO">Geo-Tagged Site Photo</option>
                                <option value="DPR_PDF">DPR Document (PDF)</option>
                                <option value="UTILIZATION_CERTIFICATE">Utilization Certificate (UC)</option>
                                <option value="SITE_MAP">Site Layout / Map</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase">File Attachment</label>
                            <input @input="docForm.file = $event.target.files[0]" type="file" accept="image/*,.pdf" class="mt-1 w-full text-sm text-gray-500" required />
                        </div>

                        <!-- Geo-Coordinates -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <label class="block text-xs font-bold text-gray-700 uppercase">GPS Location</label>
                                <button type="button" @click="captureLocation" class="text-xs text-blue-600 font-bold hover:underline">
                                    📍 Get GPS Coordinates
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <input v-model="docForm.latitude" type="text" placeholder="Latitude" class="border-gray-300 rounded-md text-xs" />
                                <input v-model="docForm.longitude" type="text" placeholder="Longitude" class="border-gray-300 rounded-md text-xs" />
                            </div>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="docForm.processing"
                            class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-md text-sm shadow disabled:opacity-50"
                        >
                            {{ docForm.processing ? 'Uploading...' : 'Upload Attachment' }}
                        </button>
                    </form>
                </div>

                <!-- Attachment Gallery -->
                <div class="md:col-span-2 bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                    <h3 class="font-bold text-gray-800 border-b pb-2 mb-4">Inspection Documents & Site Photos</h3>

                    <div v-if="project.documents && project.documents.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-for="doc in project.documents" :key="doc.id" class="border rounded-lg p-3 flex flex-col justify-between bg-gray-50">
                            <div>
                                <div class="flex justify-between items-start gap-2">
                                    <span class="px-2 py-0.5 bg-gray-200 text-gray-800 rounded text-xs font-bold uppercase">
                                        {{ doc.document_type }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ new Date(doc.created_at).toLocaleDateString() }}</span>
                                </div>
                                <p class="text-sm font-semibold text-gray-800 mt-2 truncate">{{ doc.file_name }}</p>
                                <p class="text-xs text-gray-500 mt-1">Uploaded by: {{ doc.uploaded_by || 'System User' }}</p>
                                <p v-if="doc.latitude" class="text-xs text-blue-600 font-mono mt-1">
                                    📍 GPS: {{ doc.latitude }}, {{ doc.longitude }}
                                </p>
                            </div>

                            <div class="mt-3 border-t pt-2 flex justify-between items-center">
                                <a :href="doc.file_path" target="_blank" class="text-xs text-blue-600 font-bold hover:underline">
                                    View / Download &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-10 text-gray-400">
                        <p class="text-sm">No inspection documents or geo-tagged photos uploaded yet.</p>
                    </div>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>