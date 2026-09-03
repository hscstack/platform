<script setup lang="ts">
/**
 * Shared atmospheric background — the login-page look, everywhere.
 * Renders the layout's indigo/sky/rose radial washes plus the
 * indigo/violet atmospheric blobs, fixed behind content.
 */
</script>

<template>
    <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden">
        <div
            class="absolute -top-[30%] left-1/2 h-[900px] w-[1200px] -translate-x-1/2 rounded-full bg-[radial-gradient(ellipse_at_center,rgba(99,102,241,0.18)_0%,rgba(165,180,252,0.05)_50%,transparent_70%)] blur-[120px] dark:bg-[radial-gradient(ellipse_at_center,rgba(99,102,241,0.06)_0%,rgba(165,180,252,0.02)_50%,transparent_70%)]"
        ></div>

        <div
            class="absolute top-[20%] -right-[10%] h-[600px] w-[600px] rounded-full bg-[radial-gradient(circle_at_center,rgba(56,189,248,0.15)_0%,transparent_65%)] blur-[100px] dark:bg-[radial-gradient(circle_at_center,rgba(56,189,248,0.05)_0%,transparent_65%)]"
        ></div>
        <div
            class="absolute -bottom-[10%] -left-[10%] h-[700px] w-[700px] rounded-full bg-[radial-gradient(circle_at_center,rgba(244,63,94,0.06)_0%,transparent_70%)] blur-[110px] dark:bg-[radial-gradient(circle_at_center,rgba(244,63,94,0.02)_0%,transparent_70%)]"
        ></div>

        <!-- Login-page atmosphere -->
        <div
            class="absolute top-[-10%] left-[-10%] h-[50%] w-[50%] rounded-full bg-indigo-200/40 blur-[120px] dark:bg-indigo-500/10"
        ></div>
        <div
            class="absolute right-[-5%] bottom-[10%] h-[40%] w-[40%] rounded-full bg-violet-200/30 blur-[100px] dark:bg-violet-500/10"
        ></div>
    </div>
</template>
