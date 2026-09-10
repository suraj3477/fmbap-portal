<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    schemes: Array,
});

const isModalOpen = ref(false);
const editMode = ref(false);

const form = useForm({
    id: null,
    scheme_code: '',
    scheme_name: '',
    river_basin: 'Brahmaputra',
    district: '',
    state: 'Assam',
    sanctioned_amount_cr: '',
    central_share_pct: 90,
    state_share_pct: 10,
    project_type: 'Flood Management',
    physical_status: 'Completed',
    physical_progress_pct: 100,
    is_active: true,
});

const openCreateModal = () => {
    editMode.value = false;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (scheme) => {
    editMode.value = true;
    form.id = scheme.id;
    form.scheme_code = scheme.scheme_code;
    form.scheme_name = scheme.scheme_name;
    form.river_basin = scheme.river_basin;
    form.district = scheme.district;
    form.state = scheme.state;
    form.sanctioned_amount_cr = scheme.sanctioned_amount_cr;
    form.central_share_pct = scheme.central_share_pct;
    form.state_share_pct = scheme.state_share_pct;
    form.project_type = scheme.project_type;
    form.physical_status = scheme.physical_status || 'Completed';
    form.physical_progress_pct = scheme.physical_progress_pct || 100;
    form.is_active = scheme.is_active;
    isModalOpen.value = true;
};

const submitForm = () => {
    if (editMode.value) {
        form.put(route('admin.schemes.update', form.id), {
            onSuccess: () => isModalOpen.value = false,
        });
    } else {
        form.post(route('admin.schemes.store'), {
            onSuccess: () => isModalOpen.value = false,
        });
    }
};

const deleteScheme = (id) => {
    if (confirm('Are you sure you want to delete this scheme?')) {
        router.delete(route('admin.schemes.destroy', id));
    }
};
</script>

<template>
    <Head title="Manage Schemes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Master Scheme Catalogue
                </h2>
                <button
                    @click="openCreateModal"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition"
                >
                    + Add New Scheme
                </button>
            </div>
        </template>

        <div class="py-6 mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 font-semibold text-gray-600">Code</th>
                                <th class="px-6 py-4 font-semibold text-gray-600">Name</th>
                                <th class="px-6 py-4 font-semibold text-gray-600">State / Basin</th>
                                <th class="px-6 py-4 font-semibold text-gray-600">Amount (Cr)</th>
                                <th class="px-6 py-4 font-semibold text-gray-600">Physical Status</th>
                                <th class="px-6 py-4 font-semibold text-gray-600">Status</th>
                                <th class="px-6 py-4 font-semibold text-gray-600 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="scheme in schemes" :key="scheme.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-bold text-gray-900">{{ scheme.scheme_code }}</td>
                                <td class="px-6 py-4 font-medium text-gray-800 max-w-xs truncate">{{ scheme.scheme_name }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ scheme.state }} ({{ scheme.river_basin }})</td>
                                <td class="px-6 py-4 font-mono font-bold">₹{{ scheme.sanctioned_amount_cr }}</td>
                                <td class="px-6 py-4">
                                    <span v-if="scheme.physical_status === 'Completed'" class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 text-xs px-2.5 py-1 rounded-full font-bold">
                                        ✓ Completed ({{ Number(scheme.physical_progress_pct) }}%)
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1 bg-amber-100 text-amber-800 text-xs px-2.5 py-1 rounded-full font-bold">
                                        ⏳ {{ scheme.physical_status || 'Ongoing' }} ({{ Number(scheme.physical_progress_pct) }}%)
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="scheme.is_active" class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded font-bold">Active</span>
                                    <span v-else class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded font-bold">Inactive</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="openEditModal(scheme)" class="text-blue-600 hover:text-blue-800 font-semibold mr-3">Edit</button>
                                </td>
                            </tr>
                            <tr v-if="schemes.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">No schemes found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ editMode ? 'Edit Scheme' : 'Add New Scheme' }}</h3>
                
                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Scheme Code</label>
                            <input type="text" v-model="form.scheme_code" class="w-full rounded border-gray-300" required>
                            <p v-if="form.errors.scheme_code" class="text-red-500 text-xs mt-1">{{ form.errors.scheme_code }}</p>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Scheme Name</label>
                            <input type="text" v-model="form.scheme_name" class="w-full rounded border-gray-300" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">State</label>
                            <input type="text" v-model="form.state" class="w-full rounded border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">District</label>
                            <input type="text" v-model="form.district" class="w-full rounded border-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">River Basin</label>
                            <input type="text" v-model="form.river_basin" class="w-full rounded border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Project Type</label>
                            <input type="text" v-model="form.project_type" class="w-full rounded border-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Physical Status</label>
                            <select v-model="form.physical_status" class="w-full rounded border-gray-300">
                                <option value="Completed">Completed</option>
                                <option value="Foreclosed / Ongoing">Foreclosed / Ongoing</option>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Delayed">Delayed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Physical Progress (%)</label>
                            <input type="number" step="0.01" min="0" max="100" v-model="form.physical_progress_pct" class="w-full rounded border-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Sanctioned Amount (₹ Cr)</label>
                            <input type="number" step="0.01" v-model="form.sanctioned_amount_cr" class="w-full rounded border-gray-300" required>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Central %</label>
                                <input type="number" v-model="form.central_share_pct" class="w-full rounded border-gray-300">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">State %</label>
                                <input type="number" v-model="form.state_share_pct" class="w-full rounded border-gray-300">
                            </div>
                        </div>

                        <div class="col-span-2 flex items-center gap-2 mt-2">
                            <input type="checkbox" v-model="form.is_active" id="isActive" class="rounded border-gray-300 text-blue-600">
                            <label for="isActive" class="text-sm font-semibold text-gray-700">Scheme is Active</label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t">
                        <button type="button" @click="isModalOpen = false" class="px-4 py-2 border rounded font-medium">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded font-bold" :disabled="form.processing">
                            {{ editMode ? 'Update Scheme' : 'Save Scheme' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
