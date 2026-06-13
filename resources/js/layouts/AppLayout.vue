<script setup>
import Footer from '@/components/Footer.vue';
import NavBar from '@/components/NavBar.vue';

// const searchQuery = ref('');
</script>

<template>
    <div
        class="relative min-h-screen bg-slate-50 font-sans text-slate-900 antialiased selection:bg-indigo-600 selection:text-white"
    >
        <div
            class="pointer-events-none fixed inset-0 z-0 overflow-hidden"
        >
            <div
                class="absolute -top-[30%] left-1/2 -translate-x-1/2 h-[900px] w-[1200px] rounded-full bg-[radial-gradient(ellipse_at_center,rgba(99,102,241,0.18)_0%,rgba(165,180,252,0.05)_50%,transparent_70%)] blur-[120px]"
            ></div>
            
            <div
                class="absolute top-[20%] -right-[10%] h-[600px] w-[600px] rounded-full bg-[radial-gradient(circle_at_center,rgba(56,189,248,0.15)_0%,transparent_65%)] blur-[100px]"
            ></div>
            <div
                class="absolute -bottom-[10%] -left-[10%] h-[700px] w-[700px] rounded-full bg-[radial-gradient(circle_at_center,rgba(244,63,94,0.06)_0%,transparent_70%)] blur-[110px]"
            ></div>
        </div>

        <div class="relative z-10 flex flex-col min-h-screen justify-between">
            <div>
                <NavBar />
                <slot />
            </div>
            
            <Footer />
        </div>
    </div>
</template>