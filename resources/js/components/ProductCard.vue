<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ArrowRight, ExternalLink, Layers, Pencil, Trash2, Users } from 'lucide-vue-next';
import { computed } from 'vue';

export interface ProductItem {
    id?: number;
    name: string;
    description: string;
    image_url?: string | null;
    image_path?: string | null;
    users?: string | null;
    link: string;
    open_type?: string;
    button_text?: string | null;
    sort_order?: number;
    is_active?: boolean;
}

const props = defineProps<{
    product: ProductItem;
    canManage?: boolean;
}>();

const displayImage = computed(() => {
    return props.product.image_url || props.product.image_path || null;
});

const isBlank = computed(() => {
    return props.product.open_type === '_blank';
});

const buttonLabel = computed(() => {
    return props.product.button_text || `Visit ${props.product.name}`;
});

const deleteProduct = () => {
    if (confirm(`Are you sure you want to delete "${props.product.name}"?`)) {
        router.delete(`/products/${props.product.id}`);
    }
};
</script>

<template>
    <div
        class="group flex h-full flex-col justify-between overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md sm:p-6 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700"
    >
        <div class="flex flex-1 flex-col">
            <!-- Image / Thumbnail -->
            <div
                class="aspect-[16/9] overflow-hidden rounded-xl bg-slate-100 dark:bg-gray-800"
            >
                <img
                    v-if="displayImage"
                    :src="displayImage"
                    :alt="product.name"
                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    loading="lazy"
                />
                <div
                    v-else
                    class="flex h-full w-full items-center justify-center text-slate-300 dark:text-gray-600"
                >
                    <Layers class="h-12 w-12 stroke-[1.5]" />
                </div>
            </div>

            <!-- Title & User Badge -->
            <div class="mt-5 flex items-center justify-between gap-2">
                <h2
                    class="min-w-0 text-xl font-bold text-slate-900 dark:text-gray-100"
                >
                    {{ product.name }}
                </h2>
                <span
                    v-if="product.users"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300"
                >
                    <Users class="h-3.5 w-3.5" />
                    {{ product.users }}
                </span>
            </div>

            <!-- Description -->
            <p
                class="mt-2.5 text-sm leading-relaxed text-slate-600 dark:text-gray-400"
            >
                {{ product.description }}
            </p>
        </div>

        <!-- Action Button -->
        <div
            class="mt-6 border-t border-slate-100 pt-4 sm:mt-auto dark:border-gray-800"
        >
            <a
                :href="product.link"
                :target="product.open_type || '_self'"
                :rel="isBlank ? 'noopener noreferrer' : undefined"
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-xs transition-all hover:bg-indigo-700 active:scale-98"
            >
                <span>{{ buttonLabel }}</span>
                <ExternalLink v-if="isBlank" class="h-4 w-4" />
                <ArrowRight v-else class="h-4 w-4" />
            </a>

            <!-- Manage Actions (admin only) -->
            <div
                v-if="canManage"
                class="mt-2.5 flex items-center gap-2"
            >
                <span
                    v-if="product.is_active === false"
                    class="rounded-md border border-amber-200/80 bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 dark:border-amber-800/40 dark:bg-amber-950/50 dark:text-amber-400"
                >
                    Inactive
                </span>
                <div class="ml-auto flex items-center gap-1">
                    <Link
                        :href="`/products/${product.id}/edit`"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-2xs transition-colors hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        <Pencil class="h-3.5 w-3.5" />
                        <span>Edit</span>
                    </Link>
                    <button
                        @click="deleteProduct"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-600 shadow-2xs transition-colors hover:bg-rose-100 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-400 dark:hover:bg-rose-900/50"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                        <span>Delete</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
