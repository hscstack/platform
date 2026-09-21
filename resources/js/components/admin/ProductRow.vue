<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    Pencil,
    Trash2,
    ExternalLink,
    ArrowRight,
    Layers,
    Users,
} from 'lucide-vue-next';
import StatusBadge from '@/components/StatusBadge.vue';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const deleteProduct = () => {
    if (confirm(`Are you sure you want to delete "${props.product.name}"?`)) {
        router.delete(`/admin/products/${props.product.id}`);
    }
};
</script>

<template>
    <div
        class="group relative flex flex-col justify-between gap-3 rounded-xl border border-slate-100 bg-white p-3.5 transition-colors duration-150 hover:border-indigo-200 hover:bg-slate-50/50 sm:flex-row sm:items-center dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-500/30 dark:hover:bg-gray-800/40"
    >
        <div class="flex min-w-0 flex-1 items-start gap-3.5 sm:items-center">
            <!-- Thumbnail / Icon -->
            <div
                class="h-14 w-20 shrink-0 overflow-hidden rounded-lg border border-black/5 bg-slate-100 dark:border-white/10 dark:bg-gray-800"
            >
                <img
                    v-if="product.image_url || product.image_path"
                    :src="product.image_url || product.image_path"
                    :alt="product.name"
                    class="h-full w-full object-cover"
                />
                <div
                    v-else
                    class="flex h-full w-full items-center justify-center text-slate-400 dark:text-gray-500"
                >
                    <Layers class="h-5 w-5 stroke-[1.8]" />
                </div>
            </div>

            <!-- Details -->
            <div class="flex min-w-0 flex-1 flex-col">
                <div class="flex flex-wrap items-center gap-2">
                    <h4
                        class="text-sm font-semibold break-words text-slate-900 dark:text-gray-100"
                    >
                        {{ product.name }}
                    </h4>

                    <span
                        v-if="product.users"
                        class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300"
                    >
                        <Users class="h-3 w-3" />
                        {{ product.users }}
                    </span>

                    <StatusBadge
                        :status="product.is_active ? 'published' : 'draft'"
                        size="xs"
                    />

                    <span
                        class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-gray-800 dark:text-gray-400"
                    >
                        {{
                            product.open_type === '_blank'
                                ? 'New Tab (_blank)'
                                : 'Same Tab (_self)'
                        }}
                    </span>

                    <span
                        v-if="
                            product.sort_order !== undefined &&
                            product.sort_order !== null
                        "
                        class="text-[10px] text-slate-400 dark:text-gray-500"
                    >
                        Sort Order: {{ product.sort_order }}
                    </span>
                </div>

                <p
                    class="mt-1 line-clamp-1 text-xs text-slate-500 dark:text-gray-400"
                >
                    {{ product.description }}
                </p>

                <div
                    class="mt-1 flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400"
                >
                    <span class="max-w-xs truncate font-mono text-[11px]">{{
                        product.link
                    }}</span>
                    <ExternalLink
                        v-if="product.open_type === '_blank'"
                        class="h-3 w-3 shrink-0"
                    />
                    <ArrowRight v-else class="h-3 w-3 shrink-0" />
                </div>
            </div>
        </div>

        <!-- Right: Actions -->
        <div
            v-if="can('manage products')"
            class="flex shrink-0 items-center justify-end gap-1 border-t border-slate-100 pt-2 sm:border-t-0 sm:pt-0 dark:border-gray-800"
        >
            <Link
                :href="`/admin/products/edit/${product.id}`"
                class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-indigo-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                title="Edit product"
            >
                <Pencil class="h-4 w-4" :stroke-width="1.8" />
            </Link>

            <button
                @click="deleteProduct"
                type="button"
                class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                title="Delete product"
            >
                <Trash2 class="h-4 w-4" :stroke-width="1.8" />
            </button>
        </div>
    </div>
</template>
