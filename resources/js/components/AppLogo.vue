<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link href="/" class="flex items-center gap-2.5">
        <div
            class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-lg bg-slate-900 shadow-sm"
        >
            <img
                src="/favicon.svg"
                alt="HSCStack"
                class="h-6 w-6 scale-110 object-cover"
            />
        </div>

        <span class="text-lg font-extrabold tracking-tight text-slate-900">
            HSC<span class="text-indigo-600"> Stack</span>
        </span>
    </Link>
</template>
