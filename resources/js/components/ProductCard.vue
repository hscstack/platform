<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ExternalLink, ArrowRight, Users, Layers } from 'lucide-vue-next';
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
}>();

const displayImage = computed(() => {
    return props.product.image_url || props.product.image_path || null;
});

const isBlank = computed(() => {
    return (
        props.product.open_type === '_blank' ||
        props.product.link.startsWith('http://') ||
        props.product.link.startsWith('https://')
    );
});

const buttonLabel = computed(() => {
    return props.product.button_text || `Visit ${props.product.name}`;
});
</script>

<template>
    <div
        class="group flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md sm:p-6 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700"
    >
        <div>
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
                <h2 class="text-xl font-bold text-slate-900 dark:text-gray-100">
                    {{ product.name }}
                </h2>
                <span
                    v-if="product.users"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300"
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
        <div class="mt-6 border-t border-slate-100 pt-4 dark:border-gray-800">
            <a
                v-if="isBlank"
                :href="product.link"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-xs transition-all hover:bg-indigo-700 active:scale-98"
            >
                <span>{{ buttonLabel }}</span>
                <ExternalLink class="h-4 w-4" />
            </a>

            <Link
                v-else
                :href="product.link"
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-xs transition-all hover:bg-indigo-700 active:scale-98"
            >
                <span>{{ buttonLabel }}</span>
                <ArrowRight class="h-4 w-4" />
            </Link>
        </div>
    </div>
</template>
