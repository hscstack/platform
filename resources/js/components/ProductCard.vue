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
                class="relative aspect-[16/9] overflow-hidden rounded-xl bg-slate-100 dark:bg-gray-800"
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

                <!-- Overlay: Unpublished badge (top-left) + Edit/Delete icons (top-right) -->
                <div
                    v-if="canManage"
                    class="absolute inset-x-0 top-0 flex items-start justify-between p-2"
                >
                    <span
                        v-if="product.is_active === false"
                        class="rounded-md bg-amber-500/95 px-2 py-0.5 text-[10px] font-bold tracking-wider text-white uppercase shadow-xs backdrop-blur-xs"
                    >
                        Unpublished
                    </span>
                    <span v-else />

                    <div class="flex items-center gap-1" @click.stop>
                        <Link
                            :href="`/products/${product.id}/edit`"
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/90 text-slate-700 shadow-xs backdrop-blur-xs transition hover:bg-white hover:text-indigo-600 dark:bg-gray-900/80 dark:text-gray-200 dark:hover:bg-gray-900 dark:hover:text-indigo-400"
                            title="Edit product"
                        >
                            <Pencil class="h-3.5 w-3.5" />
                        </Link>
                        <button
                            @click="deleteProduct"
                            type="button"
                            class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-lg bg-white/90 text-slate-400 shadow-xs backdrop-blur-xs transition hover:bg-rose-50 hover:text-rose-600 dark:bg-gray-900/80 dark:text-gray-400 dark:hover:bg-rose-950/60 dark:hover:text-rose-400"
                            title="Delete product"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>
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
        </div>
    </div>
</template>
