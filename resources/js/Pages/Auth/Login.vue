<script setup>
import { ref } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: true,
    },
    status: {
        type: String,
    },
});

const showPassword = ref(false);
const showDemoAccounts = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const fillDemo = (email) => {
    form.email = email;
    form.password = 'password@123';
};
</script>

<template>
    <Head title="Officer Login - FMBAP Portal | Brahmaputra Board" />

    <div class="min-h-screen bg-slate-100 flex flex-col justify-between font-sans text-slate-800">
        <!-- Government Top Bar / Parichay Header with #0F4C9F -->
        <header class="bg-[#0F4C9F] text-white shadow-md sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex items-center justify-between">
                <!-- Govt & Ministry Branding -->
                <div class="flex items-center gap-3 sm:gap-5">
                    <!-- National Emblem / Ashok Stamp -->
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
            </div>
        </header>

        <!-- Main Content Area (Parichay-style Split Container) -->
        <main class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8">
            <div class="w-full max-w-5xl bg-white rounded-2xl shadow-xl border border-slate-200/90 overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[580px]">
                
                <!-- LEFT PANEL: Official Government / Parichay Info Banner (5 cols) -->
                <div class="lg:col-span-5 bg-gradient-to-br from-[#0b2545] via-[#133e68] to-[#0a192f] text-white p-6 sm:p-8 flex flex-col justify-between relative overflow-hidden">
                    <!-- Subtle Decorative Glow & Waves -->
                    <div class="absolute -right-20 -top-20 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -left-20 -bottom-20 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                    <!-- Top Branding on Left Panel -->
                    <div class="relative z-10">
                        <div class="flex items-center gap-3.5 mb-5">
                            <div class="p-2 bg-white/95 rounded-xl shadow-md border border-white/20">
                                <ApplicationLogo class="h-12 w-auto" />
                            </div>
                            <div>
                                <h2 class="text-base font-extrabold text-white tracking-tight leading-tight">
                                    ब्रह्मपुत्र बोर्ड
                                </h2>
                                <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider">
                                    Brahmaputra Board, Guwahati
                                </h3>
                                <p class="text-[10px] text-slate-300 font-medium">
                                    Ministry of Jal Shakti, Govt. of India
                                </p>
                            </div>
                        </div>

                        <!-- System Title -->
                        <div class="mt-4 p-4 rounded-xl bg-white/10 backdrop-blur-xs border border-white/15 space-y-2">
                            <h4 class="text-sm sm:text-base font-bold text-white leading-snug">
                                Flood Management and Border Areas Programme
                            </h4>
                            <p class="text-xs text-blue-100/90 leading-relaxed">
                                Unified digital platform for statutory central fund releases, physical progress monitoring, and IMC baseline compliance.
                            </p>
                        </div>

                        <!-- Key Pillars / Parichay Features -->
                        <div class="mt-6 space-y-3 text-xs text-blue-100">
                            <div class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-blue-500/30 border border-blue-400/40 flex items-center justify-center text-[10px] font-bold shrink-0 text-blue-200">✓</span>
                                <div>
                                    <strong class="text-white font-semibold">Statutory Financial Tracking:</strong>
                                    <span class="text-blue-200/90 ml-1">Central/State funding ratio computation & UC verification.</span>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-blue-500/30 border border-blue-400/40 flex items-center justify-center text-[10px] font-bold shrink-0 text-blue-200">✓</span>
                                <div>
                                    <strong class="text-white font-semibold">Inter-Departmental Workflow:</strong>
                                    <span class="text-blue-200/90 ml-1">Live tracking between State WRD, Board & Ministry.</span>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-blue-500/30 border border-blue-400/40 flex items-center justify-center text-[10px] font-bold shrink-0 text-blue-200">✓</span>
                                <div>
                                    <strong class="text-white font-semibold">Role-based Access Control:</strong>
                                    <span class="text-blue-200/90 ml-1">Single Sign-On (SSO) authenticated security.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Left Compliance Footer -->
                    <div class="mt-8 pt-4 border-t border-white/10 relative z-10 flex items-center justify-between text-[11px] text-blue-200/80">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            NIC Standards Compliant
                        </span>
                    </div>
                </div>

                <!-- RIGHT PANEL: Parichay Officer Login Form (7 cols) -->
                <div class="lg:col-span-7 p-6 sm:p-10 flex flex-col justify-between bg-white">
                    <div>
                        <!-- Header Section -->
                        <div class="border-b border-slate-100 pb-5 mb-6">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                    <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                                    विभागीय लॉगिन • Officer Login
                                </span>
                                <span class="text-[11px] text-slate-400 font-medium">
                                    Secure Single Sign-On
                                </span>
                            </div>
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                                Sign In to Your Account
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">
                                Enter your registered departmental email (<span class="font-semibold text-slate-700">@gov.in, @nic.in, @brahmaputraboard.gov.in</span>)
                            </p>
                        </div>

                        <!-- Status Alert if any -->
                        <div v-if="status" class="mb-5 p-3.5 text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ status }}</span>
                        </div>

                        <!-- Login Form -->
                        <form @submit.prevent="submit" class="space-y-4">
                            <!-- Official Email / Username -->
                            <div>
                                <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Official Email Address / Username <span class="text-red-500">*</span>
                                </label>
                                <div class="relative rounded-lg shadow-2xs">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                        </svg>
                                    </div>
                                    <input
                                        id="email"
                                        type="email"
                                        v-model="form.email"
                                        class="block w-full pl-10 pr-3.5 py-2.5 text-sm bg-slate-50/50 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0F4C9F] focus:border-transparent transition"
                                        placeholder="officer@brahmaputraboard.gov.in"
                                        required
                                        autofocus
                                        autocomplete="username"
                                    />
                                </div>
                                <InputError class="mt-1 text-xs" :message="form.errors.email" />
                            </div>

                            <!-- Password -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                        Password <span class="text-red-500">*</span>
                                    </label>
                                    <Link
                                        v-if="canResetPassword"
                                        :href="route('password.request')"
                                        class="text-xs font-semibold text-[#0F4C9F] hover:underline"
                                    >
                                        Forgot Password?
                                    </Link>
                                </div>
                                <div class="relative rounded-lg shadow-2xs">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <input
                                        id="password"
                                        :type="showPassword ? 'text' : 'password'"
                                        v-model="form.password"
                                        class="block w-full pl-10 pr-10 py-2.5 text-sm bg-slate-50/50 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0F4C9F] focus:border-transparent transition"
                                        placeholder="••••••••••••"
                                        required
                                        autocomplete="current-password"
                                    />
                                    <!-- Toggle Password Visibility -->
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition"
                                        tabindex="-1"
                                    >
                                        <svg v-if="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                        </svg>
                                    </button>
                                </div>
                                <InputError class="mt-1 text-xs" :message="form.errors.password" />
                            </div>

                            <!-- Remember Me Checkbox -->
                            <div class="flex items-center justify-between pt-1">
                                <label class="flex items-center cursor-pointer select-none">
                                    <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-slate-300 text-[#0F4C9F] shadow-2xs focus:ring-[#0F4C9F] h-4 w-4" />
                                    <span class="ms-2 text-xs text-slate-600 font-medium">Keep me signed in on this workstation</span>
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-2">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full flex items-center justify-center gap-2 py-3 px-4 bg-[#0F4C9F] hover:bg-[#0c3c7d] text-white font-bold text-sm rounded-lg shadow-md hover:shadow-lg transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg v-if="form.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" viewBox="0 0 24 24"></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Sign In / प्रवेश करें</span>
                                </button>
                            </div>
                        </form>

                        <!-- Quick Demo Accounts Dropdown / Pill -->
                        <div class="mt-4 pt-3 border-t border-slate-100">
                            <button
                                type="button"
                                @click="showDemoAccounts = !showDemoAccounts"
                                class="w-full flex items-center justify-between text-[11px] text-slate-500 hover:text-[#0F4C9F] font-semibold py-1 transition"
                            >
                                <span class="flex items-center gap-1.5">
                                    <span>🔑</span> Quick Role Autofill (Test Accounts)
                                </span>
                                <span>{{ showDemoAccounts ? '▲ Hide' : '▼ View Roles' }}</span>
                            </button>

                            <div v-if="showDemoAccounts" class="mt-2 grid grid-cols-2 gap-1.5 p-2 bg-slate-50 rounded-lg border border-slate-200 animate-fade-in text-[10px]">
                                <button
                                    type="button"
                                    @click="fillDemo('admin@brahmaputraboard.gov.in')"
                                    class="p-1.5 text-left bg-white hover:bg-blue-50 border border-slate-200 rounded text-slate-800 transition"
                                >
                                    <strong class="text-[#0F4C9F] block">Super Admin</strong>
                                    admin@brahmaputraboard.gov.in
                                </button>
                                <button
                                    type="button"
                                    @click="fillDemo('brahmaputraboard@gov.in')"
                                    class="p-1.5 text-left bg-white hover:bg-blue-50 border border-slate-200 rounded text-slate-800 transition"
                                >
                                    <strong class="text-indigo-800 block">Board Official</strong>
                                    brahmaputraboard@gov.in
                                </button>
                                <button
                                    type="button"
                                    @click="fillDemo('assam@gov.in')"
                                    class="p-1.5 text-left bg-white hover:bg-blue-50 border border-slate-200 rounded text-slate-800 transition"
                                >
                                    <strong class="text-emerald-800 block">State Official (Assam)</strong>
                                    assam@gov.in
                                </button>
                                <button
                                    type="button"
                                    @click="fillDemo('mojs@gov.in')"
                                    class="p-1.5 text-left bg-white hover:bg-blue-50 border border-slate-200 rounded text-slate-800 transition"
                                >
                                    <strong class="text-amber-800 block">Ministry (MoJS)</strong>
                                    mojs@gov.in
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Register / Assistance Footer Link -->
                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Need official departmental access?</span>
                        <Link
                            :href="route('register')"
                            class="font-bold text-[#0F4C9F] hover:underline flex items-center gap-1"
                        >
                            <span>Register New Officer Account</span> &rarr;
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <!-- Government Standard Footer -->
        <footer class="bg-white border-t border-slate-200 py-3 text-center text-[11px] text-slate-500 space-y-1">
            <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div>
                    © 2026 <strong>Brahmaputra Board</strong>, Ministry of Jal Shakti, Government of India. All rights reserved.
                </div>
                <div class="flex items-center gap-3 text-slate-400 text-[10px]">
                    <span class="hover:text-slate-600">Privacy Policy</span>
                    <span>•</span>
                    <span class="hover:text-slate-600">Terms of Service</span>
                    <span>•</span>
                    <span class="hover:text-slate-600">Cyber Security Guidelines</span>
                    <span>•</span>
                    <span class="hover:text-slate-600">Parichay SSO Gateway</span>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.15s ease-out forwards;
}
</style>