<script setup>
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
});

// Search, Filters & Pagination
const searchQuery = ref('');
const selectedRole = ref('ALL');
const selectedState = ref('ALL');
const selectedStatus = ref('ALL'); // 'ALL' | 'APPROVED' | 'PENDING'
const currentPage = ref(1);
const perPage = 10;

// Computed Stats
const totalUsers = computed(() => props.users.length);
const approvedUsers = computed(() => props.users.filter(u => u.is_approved).length);
const pendingUsers = computed(() => props.users.filter(u => !u.is_approved).length);
const stateOfficialsCount = computed(() => props.users.filter(u => u.role === 'state_official').length);
const boardOfficialsCount = computed(() => props.users.filter(u => u.role === 'board_official').length);
const mojsOfficialsCount = computed(() => props.users.filter(u => u.role === 'mojs_official').length);
const superAdminsCount = computed(() => props.users.filter(u => u.role === 'super_admin').length);

const availableStates = computed(() => {
    const set = new Set(props.users.map(u => u.state).filter(Boolean));
    return ['ALL', ...Array.from(set)];
});

// Filtered Users
const filteredUsers = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    return props.users.filter(u => {
        const matchesQuery = !q ||
            (u.name && u.name.toLowerCase().includes(q)) ||
            (u.email && u.email.toLowerCase().includes(q)) ||
            (u.state && u.state.toLowerCase().includes(q)) ||
            (u.department && u.department.toLowerCase().includes(q));

        if (!matchesQuery) return false;

        if (selectedRole.value !== 'ALL' && u.role !== selectedRole.value) {
            return false;
        }

        if (selectedState.value !== 'ALL' && u.state !== selectedState.value) {
            return false;
        }

        if (selectedStatus.value === 'APPROVED' && !u.is_approved) return false;
        if (selectedStatus.value === 'PENDING' && u.is_approved) return false;

        return true;
    });
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredUsers.value.length / perPage)));
const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    return filteredUsers.value.slice(start, start + perPage);
});

watch([searchQuery, selectedRole, selectedState, selectedStatus], () => {
    currentPage.value = 1;
});

const goToPage = (n) => {
    if (n >= 1 && n <= totalPages.value) {
        currentPage.value = n;
        window.scrollTo({ top: 300, behavior: 'smooth' });
    }
};

const toggleApproval = (user) => {
    const action = user.is_approved ? 'revoke approval for' : 'approve access for';
    if (confirm(`Are you sure you want to ${action} ${user.name} (${user.email})?`)) {
        router.patch(route('admin.users.approve', user.id));
    }
};

const changeRole = (user, newRole) => {
    if (confirm(`Change role of ${user.name} to "${formatRole(newRole)}"?`)) {
        router.patch(route('admin.users.role', user.id), {
            role: newRole,
        });
    }
};

const formatRole = (role) => {
    switch (role) {
        case 'super_admin': return 'Super Admin (HQ)';
        case 'board_official': return 'Brahmaputra Board Official';
        case 'state_official': return 'State Govt WRD Official';
        case 'mojs_official': return 'Ministry of Jal Shakti (MoJS)';
        default: return role;
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch {
        return dateStr;
    }
};

// CSV Export
const exportToCsv = () => {
    const headers = [
        'User ID',
        'Officer Name',
        'Email Address',
        'State / Jurisdiction',
        'Assigned Role',
        'Access Status',
        'Registration Date'
    ];
    const rows = filteredUsers.value.map(u => [
        `"#USER-${u.id}"`,
        `"${(u.name || '').replace(/"/g, '""')}"`,
        `"${u.email || ''}"`,
        `"${u.state || 'Headquarters / All'}"`,
        `"${formatRole(u.role)}"`,
        `"${u.is_approved ? 'Approved' : 'Pending'}"`,
        `"${formatDate(u.created_at)}"`
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `FMBAP_User_Directory_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <Head title="User Access & Role Management — FMBAP Admin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 py-0.5">
                <div class="flex items-center gap-2.5 min-w-0">
                    <Link
                        :href="route('dashboard')"
                        class="w-8 h-8 rounded-md bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 hover:text-slate-900 transition shrink-0"
                        title="Back to Dashboard"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-none">
                                User Access &amp; Role Directory
                            </h1>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 text-purple-900 border border-purple-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-700"></span>
                                Super Admin Control
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium mt-1 truncate">
                            Authorize Departmental Logins &bull; Access Grants &bull; Agency Role Configurations
                        </p>
                    </div>
                </div>

                <!-- Header Actions (Streamlined Single Row) -->
                <div class="flex items-center gap-2 shrink-0 flex-wrap sm:flex-nowrap">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        SSO &amp; RBAC Active
                    </span>

                    <button
                        type="button"
                        @click="exportToCsv"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-xs font-semibold rounded-md shadow-2xs transition cursor-pointer"
                        title="Export filtered users to CSV"
                    >
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        <span>Export Users</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="w-full max-w-[1720px] mx-auto px-3 sm:px-6 py-5 space-y-5">

            <!-- ─── 1. EXECUTIVE METRIC CARDS ─── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Registered Officers -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Total Registered</span>
                        <span class="p-1.5 bg-blue-50 text-[#0F4C9F] rounded-lg">👥</span>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                            {{ totalUsers }} <span class="text-sm font-semibold text-slate-500">Officers</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1 flex items-center justify-between font-medium">
                            <span>State WRD, Board &amp; MoJS</span>
                            <span class="text-[#0F4C9F] font-bold">100% RBAC</span>
                        </div>
                    </div>
                </div>

                <!-- Approved Users -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Approved Access</span>
                        <span class="p-1.5 bg-emerald-50 text-emerald-700 rounded-lg">✓</span>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-emerald-950 leading-tight">
                            {{ approvedUsers }} <span class="text-base font-bold text-slate-600">active</span>
                        </div>
                        <div class="text-[11px] text-emerald-700 mt-1 font-medium">
                            {{ ((approvedUsers / (totalUsers || 1)) * 100).toFixed(0) }}% Authorized for portal access
                        </div>
                    </div>
                </div>

                <!-- Pending Approvals -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-800">Pending Review</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-200">
                            {{ pendingUsers }} Pending
                        </span>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-amber-950 leading-tight">
                            {{ pendingUsers }} <span class="text-base font-bold text-slate-600">awaiting</span>
                        </div>
                        <div class="text-[11px] text-amber-700 mt-1 font-medium">
                            {{ pendingUsers > 0 ? 'Requires Super Admin verification' : 'All registrations verified' }}
                        </div>
                    </div>
                </div>

                <!-- Role Breakdown Summary -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-800">Role Breakdown</span>
                        <span class="p-1.5 bg-purple-50 text-purple-700 rounded-lg">🏛️</span>
                    </div>
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between font-medium">
                            <span class="text-slate-600">State Govt WRD:</span>
                            <strong class="text-slate-900 font-bold">{{ stateOfficialsCount }}</strong>
                        </div>
                        <div class="flex justify-between font-medium">
                            <span class="text-slate-600">Board / MoJS / Admins:</span>
                            <strong class="text-slate-900 font-bold">{{ boardOfficialsCount + mojsOfficialsCount + superAdminsCount }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── 2. ENTERPRISE FILTER & SEARCH TOOLBAR ─── -->
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-3 text-xs">
                <!-- Search Input -->
                <div class="w-full md:w-80 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search officer name, email, or state..."
                        class="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:bg-white focus:ring-1 focus:ring-[#0F4C9F]"
                    />
                </div>

                <!-- Dropdowns & Status Pills -->
                <div class="flex items-center gap-2.5 flex-wrap w-full md:w-auto">
                    <!-- Role Filter -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-500 font-bold uppercase text-[10px]">Role:</span>
                        <select
                            v-model="selectedRole"
                            class="py-1.5 px-2.5 text-xs bg-slate-50 border border-slate-300 rounded-lg text-slate-800 font-medium focus:bg-white"
                        >
                            <option value="ALL">All Roles</option>
                            <option value="state_official">State Governments</option>
                            <option value="board_official">Brahmaputra Board (BB)</option>
                            <option value="mojs_official">Ministry of Jal Shakti (MoJS)</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>

                    <!-- State Filter -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-500 font-bold uppercase text-[10px]">State:</span>
                        <select
                            v-model="selectedState"
                            class="py-1.5 px-2.5 text-xs bg-slate-50 border border-slate-300 rounded-lg text-slate-800 font-medium focus:bg-white"
                        >
                            <option value="ALL">All States</option>
                            <option v-for="st in availableStates.filter(s => s !== 'ALL')" :key="st" :value="st">
                                {{ st }}
                            </option>
                        </select>
                    </div>

                    <!-- Status Filter Pills -->
                    <div class="inline-flex rounded-lg bg-slate-100 p-0.5 border border-slate-200">
                        <button
                            type="button"
                            @click="selectedStatus = 'ALL'"
                            :class="selectedStatus === 'ALL' ? 'bg-white text-slate-900 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-md text-[11px] transition cursor-pointer"
                        >
                            All ({{ totalUsers }})
                        </button>
                        <button
                            type="button"
                            @click="selectedStatus = 'APPROVED'"
                            :class="selectedStatus === 'APPROVED' ? 'bg-emerald-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-md text-[11px] transition cursor-pointer"
                        >
                            Approved ({{ approvedUsers }})
                        </button>
                        <button
                            type="button"
                            @click="selectedStatus = 'PENDING'"
                            :class="selectedStatus === 'PENDING' ? 'bg-amber-500 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-md text-[11px] transition cursor-pointer"
                        >
                            Pending ({{ pendingUsers }})
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─── 3. ENTERPRISE USERS DATA TABLE ─── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-[#0F4C9F] text-white font-bold uppercase tracking-wider text-[10px]">
                                <th class="py-3 px-3 text-center w-10">#</th>
                                <th class="py-3 px-3 min-w-[220px]">Officer Details</th>
                                <th class="py-3 px-3 min-w-[200px]">Official Email</th>
                                <th class="py-3 px-3 min-w-[130px]">State &amp; Org</th>
                                <th class="py-3 px-3 min-w-[180px]">Assigned Role</th>
                                <th class="py-3 px-3 text-center min-w-[120px]">Date of Registration</th>
                                <th class="py-3 px-3 text-center min-w-[130px]">Access Status</th>
                                <th class="py-3 px-3 text-center min-w-[120px]">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-800">
                            <tr
                                v-for="(user, index) in paginatedUsers"
                                :key="user.id"
                                :class="[
                                    index % 2 === 0 ? 'bg-white' : 'bg-slate-50/40',
                                    'hover:bg-blue-50/30 transition'
                                ]"
                            >
                                <!-- Index -->
                                <td class="py-2.5 px-3 text-center font-mono text-slate-400 font-bold">
                                    {{ (currentPage - 1) * perPage + index + 1 }}
                                </td>

                                <!-- Officer Name & Avatar -->
                                <td class="py-2.5 px-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 text-[#0F4C9F] font-black flex items-center justify-center text-xs shrink-0 border border-blue-200">
                                            {{ user.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 leading-snug">
                                                {{ user.name }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-mono">
                                                ID: #USER-{{ user.id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Official Email -->
                                <td class="py-2.5 px-3 font-mono text-slate-600">
                                    <span class="font-semibold text-slate-800">{{ user.email }}</span>
                                </td>

                                <!-- State / Org -->
                                <td class="py-2.5 px-3">
                                    <span class="font-bold text-slate-800">
                                        {{ user.state || 'Headquarters / All' }}
                                    </span>
                                </td>

                                <!-- Role Selector Dropdown -->
                                <td class="py-2.5 px-3">
                                    <select
                                        :value="user.role"
                                        @change="changeRole(user, $event.target.value)"
                                        class="text-xs font-semibold rounded-lg border-slate-300 py-1 px-2.5 bg-slate-50 hover:bg-white focus:ring-[#0F4C9F] focus:border-[#0F4C9F] transition"
                                    >
                                        <option value="state_official">State Governments</option>
                                        <option value="board_official">Brahmaputra Board (BB)</option>
                                        <option value="mojs_official">Ministry of Jal Shakti (MoJS)</option>
                                        <option value="super_admin">Super Admin</option>
                                    </select>
                                </td>

                                <!-- Registration Date -->
                                <td class="py-2.5 px-3 text-center text-slate-500 font-mono text-[11px] whitespace-nowrap">
                                    {{ formatDate(user.created_at) }}
                                </td>

                                <!-- Access Status Badge -->
                                <td class="py-2.5 px-3 text-center">
                                    <span
                                        v-if="user.is_approved"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Approved
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Pending
                                    </span>
                                    <div v-if="user.updated_at" class="text-[9px] text-slate-400 mt-0.5">
                                        {{ formatDate(user.updated_at) }}
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="py-2.5 px-3 text-center">
                                    <button
                                        @click="toggleApproval(user)"
                                        :class="[
                                            'px-2.5 py-1 rounded text-xs font-bold transition shadow-2xs border cursor-pointer',
                                            user.is_approved
                                                ? 'bg-rose-50 text-rose-700 hover:bg-rose-100 border-rose-200'
                                                : 'bg-emerald-600 text-white hover:bg-emerald-700 border-transparent'
                                        ]"
                                    >
                                        {{ user.is_approved ? 'Revoke Access' : 'Approve User' }}
                                    </button>
                                </td>
                            </tr>

                            <!-- Empty Row -->
                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="8" class="p-8 text-center text-slate-500 italic">
                                    No users found matching your search and filter criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Summary & Pagination -->
                <div class="bg-slate-50 border-t border-slate-200 px-4 py-3 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
                    <div>
                        Showing <strong>{{ filteredUsers.length > 0 ? (currentPage - 1) * perPage + 1 : 0 }}</strong> to <strong>{{ Math.min(currentPage * perPage, filteredUsers.length) }}</strong> of <strong>{{ filteredUsers.length }}</strong> registered officers
                    </div>

                    <div v-if="totalPages > 1" class="flex items-center gap-1.5">
                        <button
                            type="button"
                            @click="goToPage(currentPage - 1)"
                            :disabled="currentPage <= 1"
                            class="px-2.5 py-1 rounded bg-white border border-slate-300 disabled:opacity-40 hover:bg-slate-100 font-semibold cursor-pointer"
                        >
                            &larr; Prev
                        </button>
                        <span class="px-2 font-bold text-slate-800">Page {{ currentPage }} of {{ totalPages }}</span>
                        <button
                            type="button"
                            @click="goToPage(currentPage + 1)"
                            :disabled="currentPage >= totalPages"
                            class="px-2.5 py-1 rounded bg-white border border-slate-300 disabled:opacity-40 hover:bg-slate-100 font-semibold cursor-pointer"
                        >
                            Next &rarr;
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>