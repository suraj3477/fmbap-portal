<template>
    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" v-bind="$attrs" class="object-contain shrink-0">
        <defs>
            <linearGradient id="bbRiverGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#1E3A8A" />
                <stop offset="50%" stop-color="#0284C7" />
                <stop offset="100%" stop-color="#0D9488" />
            </linearGradient>
            <linearGradient id="bbGoldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#D97706" />
                <stop offset="100%" stop-color="#F59E0B" />
            </linearGradient>
        </defs>
        
        <!-- Outer Shield / Circle with subtle border -->
        <circle cx="50" cy="50" r="46" fill="#F8FAFC" stroke="url(#bbRiverGrad)" stroke-width="3.5" />
        <circle cx="50" cy="50" r="41" fill="none" stroke="#E2E8F0" stroke-width="1" />
        
        <!-- Embankment / Flood Barrier Arc -->
        <path d="M 20 62 Q 50 48 80 62 L 80 68 Q 50 54 20 68 Z" fill="url(#bbGoldGrad)" opacity="0.9" />

        <!-- Brahmaputra Flowing River Waves -->
        <path d="M 18 68 C 28 60, 38 72, 50 64 C 62 56, 72 68, 82 60 L 82 82 C 72 87, 50 89, 18 82 Z" fill="url(#bbRiverGrad)" opacity="0.95" />
        <path d="M 22 74 C 32 68, 42 78, 52 72 C 62 66, 72 74, 80 70" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round" fill="none" opacity="0.8" />
        
        <!-- Central Sun / Energy / Progress Symbol -->
        <circle cx="50" cy="36" r="10" fill="url(#bbGoldGrad)" />
        <!-- Sun Rays -->
        <line x1="50" y1="20" x2="50" y2="23" stroke="#D97706" stroke-width="2" stroke-linecap="round" />
        <line x1="50" y1="49" x2="50" y2="52" stroke="#D97706" stroke-width="2" stroke-linecap="round" />
        <line x1="34" y1="36" x2="37" y2="36" stroke="#D97706" stroke-width="2" stroke-linecap="round" />
        <line x1="63" y1="36" x2="66" y2="36" stroke="#D97706" stroke-width="2" stroke-linecap="round" />
        <line x1="39" y1="25" x2="41" y2="27" stroke="#D97706" stroke-width="2" stroke-linecap="round" />
        <line x1="59" y1="45" x2="61" y2="47" stroke="#D97706" stroke-width="2" stroke-linecap="round" />
        <line x1="39" y1="47" x2="41" y2="45" stroke="#D97706" stroke-width="2" stroke-linecap="round" />
        <line x1="59" y1="27" x2="61" y2="25" stroke="#D97706" stroke-width="2" stroke-linecap="round" />

        <!-- Border Text Arch "FMBAP" -->
        <text x="50" y="16" text-anchor="middle" font-size="7" font-weight="900" fill="#1E3A8A" letter-spacing="1.5">FMBAP</text>
    </svg>
</template>
