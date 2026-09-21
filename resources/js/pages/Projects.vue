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
        <title>Our Products & Open Source Projects</title>
        <meta
            name="description"
            content="Explore educational platforms, open-source web applications, and learning tools developed by the HSCStack team."
        />
        <meta
            property="og:title"
            content="Our Products & Open Source Projects - HSCStack"
        />
        <meta
            property="og:description"
            content="Explore educational platforms, open-source web applications, and learning tools developed by the HSCStack team."
        />
    </Head>

    <header
        class="mx-auto max-w-4xl px-4 pt-8 pb-6 text-center sm:pt-12 sm:pb-10"
    >
        <h1
            class="mb-3 text-3xl font-black tracking-tight text-slate-950 sm:text-5xl dark:text-gray-100"
        >
            Our
            <span class="text-indigo-600 dark:text-indigo-400">Products</span>
        </h1>

        <p
            class="mx-auto max-w-md text-sm font-medium text-slate-500 dark:text-gray-400"
        >
            Explore platforms and tools built by HSCStack
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
                No products are currently available. Check back soon!
            </p>
        </div>
    </main>
</template>
