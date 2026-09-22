<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex flex-col justify-between font-sans text-slate-800">
        <!-- Govt Header with #0F4C9F -->
        <header class="bg-[#0F4C9F] text-white shadow-md sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex items-center justify-between">
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
                            <div class="text-xs sm:text-sm font-black text-white tracking-tight uppercase leading-none">
                                ब्रह्मपुत्र बोर्ड • Brahmaputra Board
                            </div>
                            <div class="text-[10px] sm:text-[11px] font-semibold text-blue-200 tracking-wide mt-0.5">
                                FMBAP Scheme Monitoring Portal
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Card Container -->
        <main class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8">
            <div class="w-full max-w-xl overflow-hidden bg-white p-6 sm:p-8 shadow-xl border border-slate-200 rounded-2xl">
                <slot />
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
                </div>
            </div>
        </footer>
    </div>
</template>
