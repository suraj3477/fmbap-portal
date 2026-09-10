<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    proposals: Array,
    userRole: String,
});

// ── Tab State & Filtering ───────────────────────────────────────────────────
const activeTab = ref('ALL');

const filteredProposals = computed(() => {
    if (activeTab.value === 'ALL') return props.proposals;
    return props.proposals.filter(p => p.status === activeTab.value);
});

const getCount = (tabKey) => {
    if (tabKey === 'ALL') return props.proposals.length;
    return props.proposals.filter(p => p.status === tabKey).length;
};

// ── Status Update Modal ────────────────────────────────────────────────────
const isStatusModalOpen = ref(false);
const activeProposal = ref(null);

const statusForm = useForm({
    status: '',
    remarks: '',
});

const openStatusModal = (proposal) => {
    activeProposal.value = proposal;
    statusForm.status = proposal.status;
    statusForm.remarks = ['mojs_official', 'central_admin'].includes(props.userRole)
        ? (proposal.mojs_remarks || '')
        : (proposal.bb_remarks || '');
    isStatusModalOpen.value = true;
};

const closeStatusModal = () => {
    isStatusModalOpen.value = false;
    activeProposal.value = null;
    statusForm.reset();
};

const submitStatus = () => {
    statusForm.patch(route('fmbap.proposals.status', activeProposal.value.id), {
        onSuccess: () => closeStatusModal(),
    });
};

// ── View Details Modal ─────────────────────────────────────────────────────
const isDetailOpen = ref(false);
const detailProposal = ref(null);

const openDetail = (proposal) => {
    detailProposal.value = proposal;
    isDetailOpen.value = true;
};

const closeDetail = () => {
    isDetailOpen.value = false;
    detailProposal.value = null;
};

// ── Helpers ────────────────────────────────────────────────────────────────
const isBBorMoJS = computed(() =>
    ['super_admin', 'admin', 'board_official', 'bbrd_inspector', 'mojs_official', 'central_admin'].includes(props.userRole)
);

const statusConfig = {
    SUBMITTED_BY_STATE: { label: 'Submitted by State', cls: 'bg-blue-100 text-blue-800' },
    FORWARDED_TO_MOJS:  { label: 'Forwarded to MoJS',  cls: 'bg-indigo-100 text-indigo-800' },
    NEEDS_CORRECTION:   { label: 'Needs Correction',   cls: 'bg-amber-100 text-amber-800' },
    REJECTED:           { label: 'Rejected',            cls: 'bg-red-100 text-red-800' },
    APPROVED:           { label: 'Approved',            cls: 'bg-green-100 text-green-800' },
};

const getStatus = (status) =>
    statusConfig[status] ?? { label: status, cls: 'bg-gray-100 text-gray-700' };

const storageUrl = (path) => `/storage/${path}`;
</script>

<template>
    <Head title="FMBAP Proposals" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">FMBAP Proposals</h2>
                <Link v-if="['state_official', 'state'].includes(userRole)"
                    :href="route('fmbap.proposals.create')"
                    class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow text-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Proposal
                </Link>
            </div>
        </template>

        <div class="py-6 mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <!-- Status Filter Tabs -->
                <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 pb-4 mb-5">
                    <button
                        v-for="tab in [
                            { key: 'ALL', label: 'All Proposals', color: 'border-blue-600 text-blue-600 bg-blue-50/50' },
                            { key: 'APPROVED', label: 'Approved', color: 'border-green-600 text-green-600 bg-green-50/50' },
                            { key: 'NEEDS_CORRECTION', label: 'Needs Correction', color: 'border-amber-600 text-amber-600 bg-amber-50/50' },
                            { key: 'REJECTED', label: 'Rejected', color: 'border-red-600 text-red-600 bg-red-50/50' },
                            { key: 'SUBMITTED_BY_STATE', label: 'Submitted by State', color: 'border-sky-600 text-sky-600 bg-sky-50/50' },
                            { key: 'FORWARDED_TO_MOJS', label: 'Forwarded to MoJS', color: 'border-indigo-600 text-indigo-600 bg-indigo-50/50' }
                        ]"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        type="button"
                        :class="[
                            'px-4 py-2 text-xs sm:text-sm font-semibold rounded-lg border transition-all duration-150 flex items-center gap-2',
                            activeTab === tab.key
                                ? tab.color + ' border-2 shadow-sm'
                                : 'border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900 bg-white'
                        ]"
                    >
                        {{ tab.label }}
                        <span
                            :class="[
                                'px-2 py-0.5 text-xs rounded-full font-bold',
                                activeTab === tab.key
                                    ? 'bg-white/80'
                                    : 'bg-gray-100 text-gray-600'
                            ]"
                        >
                            {{ getCount(tab.key) }}
                        </span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100 text-gray-800 font-bold">
                                <th class="py-3 px-4 border border-gray-300">ID</th>
                                <th class="py-3 px-4 border border-gray-300">State</th>
                                <th class="py-3 px-4 border border-gray-300">Scheme Name</th>
                                <th class="py-3 px-4 border border-gray-300">Type</th>
                                <th class="py-3 px-4 border border-gray-300">River Basin</th>
                                <th class="py-3 px-4 border border-gray-300">District</th>
                                <th class="py-3 px-4 border border-gray-300">Est. Cost (Cr)</th>
                                <th class="py-3 px-4 border border-gray-300">Status</th>
                                <th class="py-3 px-4 border border-gray-300">BB Remarks</th>
                                <th class="py-3 px-4 border border-gray-300">MoJS Remarks</th>
                                <th class="py-3 px-4 border border-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="proposal in filteredProposals" :key="proposal.id"
                                class="border-b hover:bg-gray-50 align-top">
                                <td class="py-3 px-4 border border-gray-300">{{ proposal.id }}</td>
                                <td class="py-3 px-4 border border-gray-300">{{ proposal.state }}</td>
                                <td class="py-3 px-4 border border-gray-300">
                                    <button @click="openDetail(proposal)"
                                        class="text-blue-700 hover:underline font-medium text-left">
                                        {{ proposal.scheme_name }}
                                    </button>
                                </td>
                                <td class="py-3 px-4 border border-gray-300">{{ proposal.project_type || '—' }}</td>
                                <td class="py-3 px-4 border border-gray-300">{{ proposal.river_basin || '—' }}</td>
                                <td class="py-3 px-4 border border-gray-300">{{ proposal.district || '—' }}</td>
                                <td class="py-3 px-4 border border-gray-300 text-right">{{ proposal.estimated_cost_cr }}</td>

                                <!-- Status Badge -->
                                <td class="py-3 px-4 border border-gray-300">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                        :class="getStatus(proposal.status).cls">
                                        {{ getStatus(proposal.status).label }}
                                    </span>
                                </td>

                                <!-- BB Remarks -->
                                <td class="py-3 px-4 border border-gray-300 max-w-[180px]">
                                    <span v-if="proposal.bb_remarks" class="text-gray-700 text-xs leading-relaxed whitespace-pre-wrap">
                                        {{ proposal.bb_remarks }}
                                    </span>
                                    <span v-else class="text-gray-400 text-xs">—</span>
                                </td>

                                <!-- MoJS Remarks -->
                                <td class="py-3 px-4 border border-gray-300 max-w-[180px]">
                                    <span v-if="proposal.mojs_remarks" class="text-gray-700 text-xs leading-relaxed whitespace-pre-wrap">
                                        {{ proposal.mojs_remarks }}
                                    </span>
                                    <span v-else class="text-gray-400 text-xs">—</span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 border border-gray-300 space-y-1">
                                    <button @click="openDetail(proposal)"
                                        class="block w-full text-left text-indigo-600 hover:text-indigo-800 text-xs font-medium underline">
                                        View Details
                                    </button>
                                    <button v-if="isBBorMoJS"
                                        @click="openStatusModal(proposal)"
                                        class="block w-full text-left text-blue-600 hover:text-blue-800 text-xs font-medium underline">
                                        Update Status
                                    </button>
                                    <Link v-if="['state_official', 'state'].includes(userRole) && proposal.status === 'NEEDS_CORRECTION'"
                                        :href="route('fmbap.proposals.edit', proposal.id)"
                                        class="block w-full text-left text-emerald-600 hover:text-emerald-800 text-xs font-medium underline">
                                        Edit / Correct
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="filteredProposals.length === 0">
                                <td colspan="11" class="py-8 text-center text-gray-500">No proposals found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── View Details Modal ─────────────────────────────────────────── -->
        <div v-if="isDetailOpen && detailProposal"
            class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-start justify-center p-4 overflow-y-auto">
            <div class="bg-white rounded-xl shadow-2xl w-full w-full my-8">
                <!-- Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-t-xl flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-white">{{ detailProposal.scheme_name }}</h3>
                    </div>
                    <button @click="closeDetail" class="text-white hover:text-blue-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Status -->
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-gray-600">Status:</span>
                        <span class="px-3 py-1 rounded-full text-xs font-bold"
                            :class="getStatus(detailProposal.status).cls">
                            {{ getStatus(detailProposal.status).label }}
                        </span>
                    </div>

                    <!-- Core Details Grid -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Project Type</p>
                            <p class="text-sm font-medium text-gray-800">{{ detailProposal.project_type || '—' }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">River Basin</p>
                            <p class="text-sm font-medium text-gray-800">{{ detailProposal.river_basin || '—' }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">District</p>
                            <p class="text-sm font-medium text-gray-800">{{ detailProposal.district || '—' }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Estimated Cost</p>
                            <p class="text-sm font-medium text-gray-800">₹ {{ detailProposal.estimated_cost_cr }} Cr</p>
                        </div>
                        <div v-if="detailProposal.latitude || detailProposal.longitude" class="bg-gray-50 rounded-lg p-4 col-span-2">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Location (Lat / Long)</p>
                            <p class="text-sm font-medium text-gray-800">
                                {{ detailProposal.latitude || '—' }} / {{ detailProposal.longitude || '—' }}
                            </p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div v-if="detailProposal.description" class="bg-blue-50 rounded-lg p-4">
                        <p class="text-xs text-blue-600 uppercase tracking-wide font-semibold mb-2">Description</p>
                        <p class="text-sm text-gray-800 leading-relaxed whitespace-pre-wrap">{{ detailProposal.description }}</p>
                    </div>

                    <!-- Photos -->
                    <div v-if="detailProposal.photos && detailProposal.photos.length">
                        <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-3">Photos</p>
                        <div class="grid grid-cols-3 gap-3">
                            <a v-for="(photo, idx) in detailProposal.photos" :key="idx"
                                :href="storageUrl(photo)" target="_blank">
                                <img :src="storageUrl(photo)" :alt="'Photo ' + (idx+1)"
                                    class="w-full h-28 object-cover rounded-lg border border-gray-200 hover:opacity-80 transition-opacity"/>
                            </a>
                        </div>
                    </div>

                    <!-- Videos -->
                    <div v-if="detailProposal.videos && detailProposal.videos.length">
                        <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-3">Videos</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div v-for="(video, idx) in detailProposal.videos" :key="idx" class="border border-gray-200 rounded-lg overflow-hidden">
                                <video :src="storageUrl(video)" controls class="w-full h-40 object-cover"></video>
                            </div>
                        </div>
                    </div>

                    <!-- PDF Documents -->
                    <div v-if="detailProposal.pdfs && detailProposal.pdfs.length">
                        <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-3">PDF Documents</p>
                        <div class="space-y-2">
                            <a v-for="(pdf, idx) in detailProposal.pdfs" :key="idx"
                                :href="storageUrl(pdf)" target="_blank"
                                class="flex items-center gap-2 p-3 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg border border-red-100 transition-colors text-sm font-medium">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <span>Document #{{ idx + 1 }} (PDF)</span>
                                <span class="ml-auto text-xs text-red-500 underline">View PDF</span>
                            </a>
                        </div>
                    </div>

                    <!-- Remarks Section -->
                    <div class="border-t pt-4 space-y-4">
                        <h4 class="text-sm font-bold text-gray-700">Remarks / Decisions</h4>

                        <div class="grid grid-cols-1 gap-4">
                            <!-- BB Remarks -->
                            <div class="rounded-lg border border-gray-200 p-4">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                    🏛 Brahmaputra Board Remarks
                                </p>
                                <p v-if="detailProposal.bb_remarks" class="text-sm text-gray-800 whitespace-pre-wrap">
                                    {{ detailProposal.bb_remarks }}
                                </p>
                                <p v-else class="text-sm text-gray-400 italic">No remarks added yet.</p>
                            </div>

                            <!-- MoJS Remarks -->
                            <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-4">
                                <p class="text-xs font-semibold text-indigo-500 uppercase tracking-wide mb-2">
                                    🏛 Ministry of Jal Shakti Remarks
                                </p>
                                <p v-if="detailProposal.mojs_remarks" class="text-sm text-gray-800 whitespace-pre-wrap">
                                    {{ detailProposal.mojs_remarks }}
                                </p>
                                <p v-else class="text-sm text-indigo-400 italic">No remarks added yet.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t flex justify-between items-center">
                    <span class="text-xs text-gray-400">Submitted: {{ new Date(detailProposal.created_at).toLocaleDateString('en-IN') }}</span>
                    <div class="flex gap-3">
                        <button v-if="isBBorMoJS" @click="openStatusModal(detailProposal); closeDetail();"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            Update Status / Remarks
                        </button>
                        <button @click="closeDetail"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Status Update Modal ────────────────────────────────────────── -->
        <div v-if="isStatusModalOpen"
            class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg">
                <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center rounded-t-xl">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Update Proposal Status</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ activeProposal?.scheme_name }}</p>
                    </div>
                    <button @click="closeStatusModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submitStatus" class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                            <select v-model="statusForm.status"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="SUBMITTED_BY_STATE">Submitted by State</option>
                                <option value="FORWARDED_TO_MOJS">Forwarded to MoJS</option>
                                <option value="NEEDS_CORRECTION">Needs Correction (Sent back to State)</option>
                                <option value="REJECTED">Rejected</option>
                                <option value="APPROVED">Approved</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">
                                Remarks
                                <span class="text-gray-400 font-normal">({{ ['mojs_official', 'central_admin'].includes(userRole) ? 'MoJS' : 'BB' }} remarks)</span>
                            </label>
                            <textarea v-model="statusForm.remarks" rows="4"
                                placeholder="Add your remarks or decision notes..."
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                            <button type="button" @click="closeStatusModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" :disabled="statusForm.processing"
                                class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-60">
                                {{ statusForm.processing ? 'Saving...' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
