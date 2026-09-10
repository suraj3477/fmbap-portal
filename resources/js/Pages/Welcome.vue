<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});
</script>

<template>
    <Head title="Welcome to FMBAP Scheme Monitoring Portal" />

    <div class="min-h-screen bg-slate-50 text-slate-800 flex flex-col justify-between font-sans">
        <!-- Top Navigation Header -->
        <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-50">
            <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <!-- Branding -->
                <div class="flex items-center space-x-4">
                    <ApplicationLogo class="w-12 h-12" />
                    <div>
                        <h1 class="text-lg font-bold text-slate-900 uppercase tracking-tight leading-none">
                            Brahmaputra Board
                        </h1>
                        <p class="text-xs font-semibold text-blue-700 tracking-wide mt-1">
                            Ministry of Jal Shakti, Govt. of India
                        </p>
                    </div>
                </div>

                <!-- Auth Navigation -->
                <nav v-if="canLogin" class="flex items-center space-x-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="bg-blue-700 hover:bg-blue-800 text-white font-bold text-sm px-5 py-2.5 rounded-lg shadow-sm transition"
                    >
                        Go to Dashboard &rarr;
                    </Link>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="bg-blue-700 hover:bg-blue-800 text-white font-bold text-sm px-5 py-2.5 rounded-lg shadow-sm transition"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm px-4 py-2.5 rounded-lg transition border border-slate-300"
                        >
                            Register
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Main Hero & Overview -->
        <main class="flex-1">
            <!-- Hero Banner -->
            <section class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 text-white py-16 px-4 sm:px-6 lg:px-8 shadow-inner">
                <div class="w-full mx-auto text-center space-y-4">
                    <span class="inline-block px-3 py-1 bg-blue-800/80 text-blue-200 text-xs font-bold uppercase tracking-wider rounded-full border border-blue-600">
                        Flood Management & Border Areas Programme (FMBAP)
                    </span>
                    <h2 class="text-3xl sm:text-5xl font-extrabold tracking-tight">
                        Real-Time Flood & Anti-Erosion Scheme Monitoring
                    </h2>
                    <p class="text-slate-300 max-w-3xl mx-auto text-sm sm:text-base leading-relaxed">
                        Centralized G2G platform for tracking project DPR submissions, fund disbursements, site inspections, and physical progress across the North Eastern States.
                    </p>
                    <div class="pt-4 flex justify-center gap-4">
                        <Link
                            :href="route('login')"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-base px-8 py-3 rounded-lg shadow-lg transition"
                        >
                            Access Official Portal
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Key Portal Features -->
            <section class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-2">
                        <div class="w-10 h-10 bg-blue-100 text-blue-700 font-bold rounded-lg flex items-center justify-center text-lg">
                            📋
                        </div>
                        <h3 class="font-bold text-slate-800 text-base">DPR Submission & Workflow</h3>
                        <p class="text-slate-600 text-xs leading-relaxed">
                            State nodal officers can directly submit Detailed Project Reports (DPR) for technical evaluation and approval.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-2">
                        <div class="w-10 h-10 bg-emerald-100 text-emerald-700 font-bold rounded-lg flex items-center justify-center text-lg">
                            📍
                        </div>
                        <h3 class="font-bold text-slate-800 text-base">Geo-Tagged Field Audits</h3>
                        <p class="text-slate-600 text-xs leading-relaxed">
                            Upload site photos and inspection logs stamped with automatic GPS coordinates for verification.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-2">
                        <div class="w-10 h-10 bg-purple-100 text-purple-700 font-bold rounded-lg flex items-center justify-center text-lg">
                            📊
                        </div>
                        <h3 class="font-bold text-slate-800 text-base">Fund & Progress Analytics</h3>
                        <p class="text-slate-600 text-xs leading-relaxed">
                            Track sanctioned funds, releases, physical execution metrics, and utilization certificates in one view.
                        </p>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-slate-900 text-slate-400 border-t border-slate-800 text-xs py-6">
            <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-2">
                <p>© {{ new Date().getFullYear() }} Brahmaputra Board, Ministry of Jal Shakti. All rights reserved.</p>
                <p class="font-mono text-slate-500">FMBAP Monitoring System v1.0</p>
            </div>
        </footer>
    </div>
</template>