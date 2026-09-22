<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});

const roleLabel = (role, state) => {
    switch(role) {
        case 'super_admin': return 'Super Admin (HQ)';
        case 'state_official': return `State Official (${state || 'WRD'})`;
        case 'board_official': return 'Brahmaputra Board Official';
        case 'mojs_official': return 'Ministry of Jal Shakti (MoJS)';
        default: return 'Departmental User';
    }
};

const userInitials = computed(() => {
    const name = currentUser.value?.name || 'Officer';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
});

const canViewMonitoring = computed(() =>
    ['board_official', 'mojs_official', 'super_admin'].includes(currentUser.value?.role)
);
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex flex-col font-sans text-slate-800">
        <!-- Top Government Identity Header & Sub-Header Navigation (Sticky) -->
        <header class="sticky top-0 z-50 bg-[#0F4C9F] text-white shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex items-center justify-between">
                <!-- Left: National Emblem & Brahmaputra Board -->
                <div class="flex items-center gap-3 sm:gap-5">
                    <!-- Ashok Stamp -->
                    <div class="flex items-center gap-2.5 pr-4 border-r border-blue-400/40">
                        <div class="bg-white/95 p-1 rounded-md shadow-xs flex items-center justify-center shrink-0">
                            <img
                                src="/emblem.svg"
                                alt="State Emblem of India"
                                class="h-9 w-auto object-contain"
                            />
                        </div>
                        <div class="leading-tight">
                            <div class="text-[11px] font-bold text-white tracking-tight uppercase">भारत सरकार</div>
                            <div class="text-[10px] font-medium text-blue-100">Government of India</div>
                            <div class="text-[9px] text-amber-300 font-semibold hidden sm:block">Ministry of Jal Shakti</div>
                        </div>
                    </div>

                    <!-- Brahmaputra Board Identity -->
                    <Link :href="route('dashboard')" class="flex items-center gap-2.5">
                        <div class="bg-white/95 p-1 rounded-full shadow-xs flex items-center justify-center shrink-0">
                            <ApplicationLogo class="h-9 w-auto" />
                        </div>
                        <div>
                            <div class="text-xs sm:text-sm font-black text-white tracking-tight leading-none uppercase">
                                ब्रह्मपुत्र बोर्ड • Brahmaputra Board
                            </div>
                            <div class="text-[10px] sm:text-[11px] font-semibold text-blue-200 tracking-wide mt-0.5">
                                FMBAP Monitoring Portal
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Right: Modern Officer Profile Widget -->
                <div class="flex items-center gap-3">
                    <Dropdown align="right" width="72">
                        <template #trigger>
                            <button
                                type="button"
                                class="group flex items-center gap-3 pl-2 pr-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/15 border border-white/20 backdrop-blur-md shadow-xs transition-all duration-150 cursor-pointer text-left"
                            >
                                <!-- Avatar with Ring & Status Indicator -->
                                <div class="relative">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-100 to-white text-[#0F4C9F] font-black text-xs flex items-center justify-center shadow-xs border border-white/30">
                                        {{ userInitials }}
                                    </div>
                                    <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-400 border-2 border-[#0F4C9F] rounded-full"></span>
                                </div>

                                <!-- Officer Name & Role -->
                                <div class="hidden sm:flex flex-col">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-bold text-white tracking-tight leading-none group-hover:text-blue-100">
                                            {{ currentUser.name || 'Officer' }}
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-blue-200 font-semibold tracking-wide mt-0.5 leading-none">
                                        {{ roleLabel(currentUser.role, currentUser.state) }}
                                    </span>
                                </div>

                                <!-- Chevron Indicator -->
                                <svg class="w-4 h-4 text-blue-200 group-hover:text-white transition-transform duration-150 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </template>

                        <template #content>
                            <!-- Officer Profile Header Card -->
                            <div class="p-4 bg-gradient-to-br from-slate-50 via-blue-50/30 to-white border-b border-slate-100">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-10 h-10 rounded-xl bg-[#0F4C9F] text-white font-black text-sm flex items-center justify-center shadow-xs shrink-0">
                                        {{ userInitials }}
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-xs font-black text-slate-900 leading-snug truncate">
                                            {{ currentUser.name || 'Officer' }}
                                        </h4>
                                        <p class="text-[11px] text-slate-500 font-mono truncate">
                                            {{ currentUser.email }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-2.5 pt-2 border-t border-slate-200/60 flex items-center justify-between">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-[#0F4C9F] border border-blue-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#0F4C9F]"></span>
                                        {{ roleLabel(currentUser.role, currentUser.state) }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-medium">
                                        {{ currentUser.state || 'National HQ' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Menu Links List -->
                            <div class="p-1.5 space-y-0.5 text-xs font-semibold text-slate-700">
                                <Link
                                    :href="route('profile.edit')"
                                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-slate-100 text-slate-800 transition"
                                >
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span>Officer Profile Settings</span>
                                </Link>

                                <Link
                                    v-if="currentUser.role === 'super_admin'"
                                    :href="route('admin.users.index')"
                                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-slate-100 text-slate-800 transition"
                                >
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span>Manage Users &amp; Permissions</span>
                                </Link>
                            </div>

                            <!-- Divider & Logout -->
                            <div class="p-1.5 border-t border-slate-100">
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold text-rose-700 hover:bg-rose-50 transition text-left cursor-pointer"
                                >
                                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Log Out (प्रस्थान)</span>
                                </Link>
                            </div>
                        </template>
                    </Dropdown>

                    <!-- Mobile Hamburger -->
                    <div class="flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="p-1.5 rounded-md text-white hover:bg-white/10 transition"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Primary Navigation Bar Sub-strip with Distinct Contrast (#071F42 Deep Midnight Navy) -->
            <nav class="bg-[#071F42] border-t border-blue-400/20 shadow-inner">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="hidden sm:flex space-x-1 py-1.5">
                        <Link
                            :href="route('dashboard')"
                            :class="[
                                route().current('dashboard') ? 'bg-white text-[#0F4C9F] shadow-xs font-extrabold' : 'text-blue-100/90 hover:bg-white/10 hover:text-white font-medium',
                                'px-3.5 py-2 rounded-lg text-xs transition'
                            ]"
                        >
                            Dashboard &amp; GIS Map
                        </Link>

                        <Link
                            :href="route('schemes.index')"
                            :class="[
                                route().current('schemes.*') ? 'bg-white text-[#0F4C9F] shadow-xs font-extrabold' : 'text-blue-100/90 hover:bg-white/10 hover:text-white font-medium',
                                'px-3.5 py-2 rounded-lg text-xs transition'
                            ]"
                        >
                            Schemes Catalogue
                        </Link>

                        <Link
                            :href="route('fund-release.index')"
                            :class="[
                                route().current('fund-release.*') ? 'bg-white text-[#0F4C9F] shadow-xs font-extrabold' : 'text-blue-100/90 hover:bg-white/10 hover:text-white font-medium',
                                'px-3.5 py-2 rounded-lg text-xs transition'
                            ]"
                        >
                            Fund Release Claims
                        </Link>

                        <Link
                            v-if="canViewMonitoring"
                            :href="route('monitoring-requests.index')"
                            :class="[
                                route().current('monitoring-requests.*') ? 'bg-white text-[#0F4C9F] shadow-xs font-extrabold' : 'text-blue-100/90 hover:bg-white/10 hover:text-white font-medium',
                                'px-3.5 py-2 rounded-lg text-xs transition'
                            ]"
                        >
                            BB Site Monitoring
                        </Link>

                        <!-- Super Admin Users Link -->
                        <Link
                            v-if="$page.props.auth.user.role === 'super_admin'"
                            :href="route('admin.users.index')"
                            :class="[
                                route().current('admin.users.*') ? 'bg-white text-[#0F4C9F] shadow-xs font-extrabold' : 'text-blue-100/90 hover:bg-white/10 hover:text-white font-medium',
                                'px-3.5 py-2 rounded-lg text-xs transition'
                            ]"
                        >
                            Manage Users
                        </Link>
                    </div>
                </div>

                <!-- Responsive Mobile Menu -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden bg-[#051731] px-4 pt-2 pb-3 space-y-1">
                    <Link
                        :href="route('dashboard')"
                        class="block px-3 py-2 rounded-md text-xs font-bold text-white hover:bg-white/10"
                    >
                        Dashboard &amp; GIS Map
                    </Link>
                    <Link
                        :href="route('schemes.index')"
                        class="block px-3 py-2 rounded-md text-xs font-bold text-white hover:bg-white/10"
                    >
                        Schemes Catalogue
                    </Link>
                    <Link
                        :href="route('fund-release.index')"
                        class="block px-3 py-2 rounded-md text-xs font-bold text-white hover:bg-white/10"
                    >
                        Fund Release Claims
                    </Link>
                    <Link
                        v-if="canViewMonitoring"
                        :href="route('monitoring-requests.index')"
                        class="block px-3 py-2 rounded-md text-xs font-bold text-white hover:bg-white/10"
                    >
                        BB Site Monitoring
                    </Link>
                    <Link
                        v-if="$page.props.auth.user.role === 'super_admin'"
                        :href="route('admin.users.index')"
                        class="block px-3 py-2 rounded-md text-xs font-bold text-white hover:bg-white/10"
                    >
                        Manage Users
                    </Link>
                    <div class="pt-2 border-t border-white/10">
                        <Link
                            :href="route('profile.edit')"
                            class="block px-3 py-1.5 text-xs text-blue-200 hover:text-white"
                        >
                            Profile
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="block w-full text-left px-3 py-1.5 text-xs text-red-300 hover:text-red-100"
                        >
                            Log Out
                        </Link>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Page Header Slot -->
        <header v-if="$slots.header" class="bg-white border-b border-slate-200 shadow-2xs">
            <div class="max-w-7xl mx-auto px-4 py-2 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Global Flash Alerts -->
        <div v-if="$page.props.flash?.success || $page.props.flash?.error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            <div
                v-if="$page.props.flash?.success"
                class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center justify-between shadow-xs"
            >
                <div class="flex items-center gap-2 font-bold">
                    <span class="text-base">✅</span>
                    <span>{{ $page.props.flash.success }}</span>
                </div>
                <button type="button" @click="$page.props.flash.success = null" class="text-emerald-700 hover:text-emerald-950 font-black text-sm px-2">&times;</button>
            </div>

            <div
                v-if="$page.props.flash?.error"
                class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs flex items-center justify-between shadow-xs"
            >
                <div class="flex items-center gap-2 font-bold">
                    <span class="text-base">❌</span>
                    <span>{{ $page.props.flash.error }}</span>
                </div>
                <button type="button" @click="$page.props.flash.error = null" class="text-rose-700 hover:text-rose-950 font-black text-sm px-2">&times;</button>
            </div>
        </div>

        <!-- Main Body Content -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Standard Government Footer -->
        <footer class="bg-white border-t border-slate-200 py-3.5 text-center text-[11px] text-slate-500 mt-auto">
            <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div>
                    © 2026 <strong>Brahmaputra Board</strong>, Ministry of Jal Shakti, Government of India. All rights reserved.
                </div>
                <div class="text-[10px] text-slate-400">
                    Flood Management and Border Areas Programme (FMBAP) Monitoring System
                </div>
            </div>
        </footer>
    </div>
</template>