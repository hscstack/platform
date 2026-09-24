<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import ProductRow from '@/components/admin/ProductRow.vue';
import { adminPageClass, adminPrimaryBtnClass } from '@/components/admin/ui';
import EmptyState from '@/components/EmptyState.vue';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();

defineProps({
    products: {
        type: Array as () => any[],
        default: () => [],
    },
});
</script>

<template>
    <Head title="Manage Products" />

    <div :class="adminPageClass">
        <!-- Compact Page Title Bar -->
        <div
            class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
        >
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h1
                        class="truncate text-lg font-bold tracking-tight text-slate-900 dark:text-gray-100"
                    >
                        Manage Products
                    </h1>

                    <span
                        class="inline-flex items-center rounded-md bg-indigo-50 px-1.5 py-0.5 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        {{ products.length }}
                    </span>
                </div>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                    Showcase products and manage their visibility.
                </p>
            </div>

            <div
                v-if="can('manage products')"
                class="flex shrink-0 items-center gap-2"
            >
                <Link
                    href="/admin/products/create"
                    :class="adminPrimaryBtnClass"
                >
                    <Plus class="h-3.5 w-3.5" :stroke-width="2.2" />
                    <span>Create Product</span>
                </Link>
            </div>
        </div>

        <div class="flex flex-1 flex-col">
            <div v-if="products.length > 0" class="flex flex-col gap-3">
                <ProductRow
                    v-for="product in products"
                    :key="product.id"
                    :product="product"
                />
            </div>

            <EmptyState
                v-else
                title="No products found"
                description="No products have been added yet."
                :show-cta="false"
            />
        </div>
    </div>
</template>
