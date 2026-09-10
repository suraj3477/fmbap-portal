<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    users: Array,
});

const toggleApproval = (user) => {
    if (confirm(`Are you sure you want to ${user.is_approved ? 'revoke approval for' : 'approve'} ${user.name}?`)) {
        router.patch(route('admin.users.approve', user.id));
    }
};

const changeRole = (user, newRole) => {
    router.patch(route('admin.users.role', user.id), {
        role: newRole,
    });
};
</script>

<template>
    <Head title="User Management - Admin Panel" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-slate-100 p-4">
            <div class="w-full mx-auto space-y-4">
                <!-- Header Banner -->
                <div class="bg-slate-900 p-4 rounded-sm shadow-xs text-white flex justify-between items-center border border-slate-800">
                    <div>
                        <h1 class="text-xl font-bold uppercase tracking-tight">User Access & Role Control</h1>
                        <p class="text-xs text-slate-300 mt-0.5">Super Admin Panel — Brahmaputra Board (FMBAP)</p>
                    </div>
                    <a :href="route('dashboard')" class="bg-white/10 hover:bg-white/20 text-white font-bold text-xs px-3 py-1.5 rounded-sm border border-white/20 transition leading-none">
                        &larr; Back to Dashboard
                    </a>
                </div>

                <!-- Users Data Table -->
                <div class="bg-white rounded-sm shadow-xs border border-slate-200 overflow-hidden">
                    <div class="p-3 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                        <h2 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Registered Officers Directory ({{ users.length }})</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-100 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                                    <th class="p-2.5">Officer Name</th>
                                    <th class="p-2.5">Email Address</th>
                                    <th class="p-2.5">State</th>
                                    <th class="p-2.5">Current Role</th>
                                    <th class="p-2.5">Approval Status</th>
                                    <th class="p-2.5 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50 transition">
                                    <td class="p-2.5 font-bold text-slate-900">{{ user.name }}</td>
                                    <td class="p-2.5 text-slate-600 font-mono text-[11px]">{{ user.email }}</td>
                                    <td class="p-2.5 font-semibold text-slate-700">{{ user.state || 'N/A' }}</td>
                                    
                                    <!-- Role Selector Dropdown -->
                                    <td class="p-2.5">
                                        <select
                                            :value="user.role"
                                            @change="changeRole(user, $event.target.value)"
                                            class="text-xs font-semibold rounded-sm border-slate-300 py-1 px-2 focus:ring-slate-800 focus:border-slate-800"
                                        >
                                            <option value="state_official">State Governments</option>
                                            <option value="board_official">Brahmaputra Board (BB)</option>
                                            <option value="mojs_official">Ministry of Jal Shakti (MoJS)</option>
                                            <option value="super_admin">Super Admin</option>
                                        </select>
                                    </td>

                                    <!-- Approval Badge -->
                                    <td class="p-2.5">
                                        <span
                                            :class="[
                                                'px-2 py-0.5 rounded-xs text-[10px] font-bold uppercase tracking-wider border inline-block leading-tight',
                                                user.is_approved ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-amber-50 text-amber-800 border-amber-300'
                                            ]"
                                        >
                                            {{ user.is_approved ? 'Approved' : 'Pending Approval' }}
                                        </span>
                                    </td>

                                    <!-- Action Buttons -->
                                    <td class="p-2.5 text-center space-x-2">
                                        <button
                                            @click="toggleApproval(user)"
                                            :class="[
                                                'px-2.5 py-1 rounded-sm text-xs font-bold transition shadow-xs border leading-none',
                                                user.is_approved 
                                                    ? 'bg-rose-50 text-rose-700 hover:bg-rose-100 border-rose-200' 
                                                    : 'bg-emerald-600 text-white hover:bg-emerald-700 border-transparent'
                                            ]"
                                        >
                                            {{ user.is_approved ? 'Revoke Access' : 'Approve User' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>