<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ProductCard from '@/components/ProductCard.vue';
import type { ProductItem } from '@/components/ProductCard.vue';

defineProps<{
    products: ProductItem[];
}>();
</script>

<template>
    <Head>
        <title>The HSCStack Ecosystem & Connected Apps</title>
        <meta
            name="description"
            content="Explore interconnected platforms, learning tools, and analytics built under the HSCStack ecosystem."
        />
        <meta
            property="og:title"
            content="The HSCStack Ecosystem - Connected Apps & Platforms"
        />
        <meta
            property="og:description"
            content="Explore interconnected platforms, learning tools, and analytics built under the HSCStack ecosystem."
        />
    </Head>

    <header
        class="mx-auto max-w-4xl px-4 pt-8 pb-6 text-center sm:pt-12 sm:pb-10"
    >
        <div
            class="mb-3 inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50/80 px-3 py-1 text-xs font-semibold text-indigo-700 backdrop-blur-xs dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300"
        >
            <span
                class="inline-block h-1.5 w-1.5 rounded-full bg-indigo-600 dark:bg-indigo-400"
            ></span>
            <span>HSCStack Suite</span>
        </div>

        <h1
            class="mb-3 text-3xl font-black tracking-tight text-slate-950 sm:text-5xl dark:text-gray-100"
        >
            The
            <span class="text-indigo-600 dark:text-indigo-400">Ecosystem</span>
        </h1>

        <p
            class="mx-auto max-w-lg text-sm font-medium text-slate-600 sm:text-base dark:text-gray-300"
        >
            Connected platforms and specialized tools designed for your complete
            HSC & SSC study journey.
        </p>
    </header>

    <main class="mx-auto max-w-5xl px-4 pb-20 sm:px-6">
        <div
            v-if="products && products.length > 0"
            class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:gap-8"
        >
            <ProductCard
                v-for="product in products"
                :key="product.id || product.name"
                :product="product"
            />
        </div>

        <div
            v-else
            class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-xs dark:border-gray-800 dark:bg-gray-900"
        >
            <p class="text-sm font-medium text-slate-500 dark:text-gray-400">
                No ecosystem products are currently available. Check back soon!
            </p>
        </div>
    </main>
</template>
