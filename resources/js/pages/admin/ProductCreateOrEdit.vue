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

const isExternalPath =
    props.product?.image_path &&
    (props.product.image_path.startsWith('http://') ||
        props.product.image_path.startsWith('https://'));

const form = useForm({
    name: props.product?.name || '',
    description: props.product?.description || '',
    image: null as File | null,
    image_url: isExternalPath ? props.product.image_path : '',
    users: props.product?.users || '',
    link: props.product?.link || '',
    open_type: props.product?.open_type || '_blank',
    button_text: props.product?.button_text || '',
    order: props.product?.order ?? 0,
    is_active: Boolean(props.product?.is_active ?? true),
});

const submitForm = () => {
    if (props.product) {
        form.post(`/admin/products/edit/${props.product.id}/patch`, {
            preserveScroll: true,
            forceFormData: true,
        });
    } else {
        form.post('/admin/products', {
            preserveScroll: true,
            forceFormData: true,
        });
    }
};
</script>

<template>
    <Head :title="product ? `Edit ${product.name}` : 'Create Product'" />

    <div class="flex w-full flex-1 flex-col">
        <!-- Page Header -->
        <div
            class="mb-6 flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center dark:border-gray-800"
        >
            <div class="flex items-center gap-3">
                <Link
                    href="/admin/products"
                    class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                    title="Back to products"
                >
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1
                        class="text-xl font-bold text-slate-900 dark:text-gray-100"
                    >
                        {{ props.product ? 'Edit' : 'Create' }} Product
                    </h1>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        Configure showcase products displayed on the /projects
                        page.
                    </p>
                </div>
            </div>
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
                        required
                        placeholder="e.g. ResultStack or HSCStack"
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
                        Users Badge (Optional)
                    </label>
                    <input
                        v-model="form.users"
                        type="text"
                        id="users"
                        placeholder="e.g. 640+ Users or 10k+ Learners"
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
                    required
                    placeholder="Short description highlighting what this product does..."
                    class="w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500"
                    :class="
                        form.errors.description
                            ? 'border-rose-500 focus:ring-2 focus:ring-rose-500/20'
                            : 'border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700'
                    "
                ></textarea>
                <p
                    v-if="form.errors.description"
                    class="mt-1 text-xs text-rose-600"
                >
                    {{ form.errors.description }}
                </p>
            </div>

            <!-- Link, Open Type & Button Text -->
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div class="md:col-span-1">
                    <label
                        for="link"
                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                    >
                        Link URL <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="form.link"
                        type="text"
                        id="link"
                        required
                        placeholder="e.g. / or https://example.com"
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
            </div>

            <!-- Image Upload Section -->
            <div>
                <ImageUpload
                    v-model="form.image"
                    :current-image-url="
                        form.image_url ||
                        product?.image_url ||
                        product?.image_path
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

                <!-- Optional Direct Image URL fallback -->
                <div class="mt-3">
                    <label
                        for="image_url"
                        class="mb-1 block text-xs font-medium text-slate-500 dark:text-gray-400"
                    >
                        Or provide direct image URL (e.g. CDN URL):
                    </label>
                    <input
                        v-model="form.image_url"
                        type="text"
                        id="image_url"
                        placeholder="https://cdn.example.com/image.png"
                        class="w-full rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-1.5 text-xs text-slate-800 transition outline-none placeholder:text-slate-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                    />
                </div>
            </div>

            <!-- Order & Active Status -->
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label
                        for="order"
                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                    >
                        Display Order
                    </label>
                    <input
                        v-model.number="form.order"
                        type="number"
                        id="order"
                        min="0"
                        placeholder="0"
                        class="w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500"
                        :class="
                            form.errors.order
                                ? 'border-rose-500 focus:ring-2 focus:ring-rose-500/20'
                                : 'border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700'
                        "
                    />
                    <p
                        v-if="form.errors.order"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.order }}
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
                                /projects showcase.
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
                    href="/admin/products"
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
</template>
