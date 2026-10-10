<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Loader2, Save } from 'lucide-vue-next';
import ImageUpload from '@/components/ImageUpload.vue';

const props = defineProps({
    product: {
        type: Object,
        default: null,
    },
});

const form = useForm({
    name: props.product?.name || '',
    description: props.product?.description || '',
    image: null as File | null,
    users: props.product?.users || '',
    link: props.product?.link || '',
    open_type: props.product?.open_type || '_blank',
    button_text: props.product?.button_text || '',
    sort_order: props.product?.sort_order ?? 0,
    is_active: Boolean(props.product?.is_active ?? true),
});

const submitForm = () => {
    if (props.product) {
        form.post(`/products/${props.product.id}/patch`, {
            preserveScroll: true,
            forceFormData: true,
        });
    } else {
        form.post('/products', {
            preserveScroll: true,
            forceFormData: true,
        });
    }
};
</script>

<template>
    <Head :title="product ? `Edit ${product.name}` : 'Create Product'" />

    <main class="mx-auto max-w-4xl px-3.5 py-4 sm:px-6 sm:py-8">
        <!-- Back Link -->
        <div class="mb-4">
            <Link
                href="/products"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
            >
                <ArrowLeft class="h-4 w-4" />
                <span>Back to Products</span>
            </Link>
        </div>

        <div
            class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:p-8 dark:border-gray-800 dark:bg-gray-900"
        >
            <div
                class="mb-6 border-b border-slate-100 pb-5 dark:border-gray-800"
            >
                <h1
                    class="text-xl font-extrabold text-slate-900 sm:text-2xl dark:text-gray-100"
                >
                    {{ props.product ? 'Edit' : 'Create' }} Product
                </h1>
                <p
                    class="mt-1 text-xs text-slate-500 sm:text-sm dark:text-gray-400"
                >
                    Configure showcase products displayed on the /products page.
                </p>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Name & Users Badge -->
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label
                            for="name"
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Product Name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            id="name"
                            placeholder="e.g. HSCStack Platform"
                            class="w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500"
                            :class="
                                form.errors.name
                                    ? 'border-rose-500 focus:ring-2 focus:ring-rose-500/20'
                                    : 'border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700'
                            "
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="users"
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Users / Stats Badge (Optional)
                        </label>
                        <input
                            v-model="form.users"
                            type="text"
                            id="users"
                            placeholder="e.g. 50k+ students"
                            class="w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500"
                            :class="
                                form.errors.users
                                    ? 'border-rose-500 focus:ring-2 focus:ring-rose-500/20'
                                    : 'border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700'
                            "
                        />
                        <p
                            v-if="form.errors.users"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.users }}
                        </p>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label
                        for="description"
                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                    >
                        Description <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        v-model="form.description"
                        id="description"
                        rows="3"
                        placeholder="Short description of this product..."
                        class="w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500"
                        :class="
                            form.errors.description
                                ? 'border-rose-500 focus:ring-2 focus:ring-rose-500/20'
                                : 'border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700'
                        "
                    />
                    <p
                        v-if="form.errors.description"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.description }}
                    </p>
                </div>

                <!-- Link & Open Type -->
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label
                            for="link"
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                        >
                            URL <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.link"
                            type="text"
                            id="link"
                            placeholder="https://example.com"
                            class="w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500"
                            :class="
                                form.errors.link
                                    ? 'border-rose-500 focus:ring-2 focus:ring-rose-500/20'
                                    : 'border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700'
                            "
                        />
                        <p
                            v-if="form.errors.link"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.link }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="open_type"
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Open Type <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="form.open_type"
                            id="open_type"
                            class="w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-slate-900 transition outline-none dark:bg-gray-900 dark:text-gray-100"
                            :class="
                                form.errors.open_type
                                    ? 'border-rose-500 focus:ring-2 focus:ring-rose-500/20'
                                    : 'border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700'
                            "
                        >
                            <option value="_blank">New Tab (_blank)</option>
                            <option value="_self">Same Page (_self)</option>
                        </select>
                        <p
                            v-if="form.errors.open_type"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.open_type }}
                        </p>
                    </div>
                </div>

                <div>
                    <label
                        for="button_text"
                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                    >
                        Button Text (Optional)
                    </label>
                    <input
                        v-model="form.button_text"
                        type="text"
                        id="button_text"
                        placeholder="e.g. Visit Platform"
                        class="w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500"
                        :class="
                            form.errors.button_text
                                ? 'border-rose-500 focus:ring-2 focus:ring-rose-500/20'
                                : 'border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700'
                        "
                    />
                    <p
                        v-if="form.errors.button_text"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.button_text }}
                    </p>
                </div>

                <!-- Image Upload Section -->
                <div>
                    <ImageUpload
                        v-model="form.image"
                        :current-image-url="
                            product?.image_url || product?.image_path
                        "
                        label="Product Banner Image"
                        help-text="16:9 banner image recommended (JPG, PNG, WebP)"
                        shape="rectangle"
                    />
                    <p
                        v-if="form.errors.image"
                        class="mt-1.5 text-xs text-rose-600"
                    >
                        {{ form.errors.image }}
                    </p>
                </div>

                <!-- Order & Active Status -->
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label
                            for="sort_order"
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Sort Order
                        </label>
                        <input
                            v-model.number="form.sort_order"
                            type="number"
                            id="sort_order"
                            min="0"
                            placeholder="0"
                            class="w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500"
                            :class="
                                form.errors.sort_order
                                    ? 'border-rose-500 focus:ring-2 focus:ring-rose-500/20'
                                    : 'border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700'
                            "
                        />
                        <p
                            v-if="form.errors.sort_order"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.sort_order }}
                        </p>
                    </div>

                    <div class="flex items-end">
                        <label
                            class="flex w-full cursor-pointer items-center justify-between rounded-xl border border-slate-200 bg-slate-50/60 p-3.5 transition dark:border-gray-800 dark:bg-gray-800/40"
                        >
                            <div>
                                <span
                                    class="text-sm font-semibold text-slate-900 dark:text-gray-100"
                                >
                                    Active & Visible
                                </span>
                                <p
                                    class="text-xs text-slate-500 dark:text-gray-400"
                                >
                                    Make this product visible on the public
                                    /products showcase.
                                </p>
                            </div>
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900"
                            />
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <div
                    class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5 dark:border-gray-800"
                >
                    <Link
                        href="/products"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-bold text-white shadow-xs transition hover:bg-indigo-700 active:scale-98 disabled:opacity-70"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin"
                        />
                        <Save v-else class="h-4 w-4" />
                        <span>{{
                            props.product ? 'Update Product' : 'Create Product'
                        }}</span>
                    </button>
                </div>
            </form>
        </div>
    </main>
</template>
