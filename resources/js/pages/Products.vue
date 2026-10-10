<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import AdUnit from '@/components/AdUnit.vue';
import ProductCard from '@/components/ProductCard.vue';
import type { ProductItem } from '@/components/ProductCard.vue';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();

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

        <div v-if="can('manage products')" class="mt-5 flex justify-center">
            <Link
                href="/products/create"
                class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-2xs transition-colors hover:bg-indigo-700"
            >
                <Plus class="h-4 w-4 stroke-[2.2]" />
                <span>Add Product</span>
            </Link>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 pb-20 sm:px-6">
        <div
            v-if="products && products.length > 0"
            class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:gap-8"
        >
            <template
                v-for="(product, index) in products"
                :key="product.id || product.name"
            >
                <ProductCard
                    :product="product"
                    :can-manage="can('manage products')"
                />

                <!-- Native Sponsored Tool / Partner Card in More From Us -->
                <AdUnit
                    v-if="index === 1 || (index === 0 && products.length === 1)"
                    variant="native-product"
                    title="Your Ad Goes Here"
                    subtitle="Reach thousands of students. Study & education promotions only — strictly verified and student-safe."
                    cta-text="Book Ad Space"
                    badge-text="Sponsored"
                />
            </template>
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
