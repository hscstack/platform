<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Loader2, Save } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    notice: Object,
});

const form = useForm({
    title: props.notice?.title || '',
    message: props.notice?.message || '',
    image: props.notice?.image || '',
    show_button: props.notice?.show_button ?? false,
    button_title: props.notice?.button_title || '',
    button_link: props.notice?.button_link || '',
    is_active: props.notice?.is_active ?? false,
});

const hasContent = computed(
    () => form.title.trim() !== '' || form.message.trim() !== '',
);

const goBack = () => {
    window.history.back();
};

const submitForm = () => {
    form.patch('/admin/notice', {
        preserveScroll: true,
    });
};
</script>
<template>
    <div
        class="flex min-h-full w-full flex-col justify-start bg-slate-50 p-4 lg:p-10 dark:bg-gray-950"
    >
        <div
            class="w-full rounded-3xl bg-white p-6 shadow-xs ring-1 ring-slate-900/5 md:p-10 dark:bg-gray-900 dark:ring-gray-700"
        >
            <div
                class="mb-10 border-b border-slate-100 pb-6 dark:border-gray-800"
            >
                <h1
                    class="text-2xl font-bold tracking-tight text-slate-900 dark:text-gray-100"
                >
                    Site Notice
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                    Configure the announcement dialog shown on the home page.
                    Only one notice is displayed at a time.
                </p>
            </div>

            <form
                @submit.prevent="submitForm"
                class="grid grid-cols-1 gap-8 lg:grid-cols-3"
            >
                <div class="space-y-6 lg:col-span-2">
                    <div>
                        <label
                            for="title"
                            class="mb-1.5 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                        >
                            Title
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            id="title"
                            placeholder="Important announcement"
                            :disabled="form.processing"
                            class="w-full rounded-xl border px-4 py-3 text-sm transition outline-none focus:ring-4 disabled:bg-slate-50 disabled:text-slate-400 dark:bg-gray-900 dark:disabled:bg-gray-800 dark:disabled:text-gray-500"
                            :class="
                                form.errors.title
                                    ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500/10'
                                    : 'border-slate-200 focus:border-slate-900 focus:ring-slate-900/5 dark:border-gray-700'
                            "
                        />
                        <p
                            v-if="form.errors.title"
                            class="mt-1.5 text-xs font-medium text-rose-600"
                        >
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="message"
                            class="mb-1.5 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                        >
                            Message
                        </label>
                        <textarea
                            v-model="form.message"
                            id="message"
                            rows="6"
                            placeholder="Write the notice message for visitors..."
                            :disabled="form.processing"
                            class="w-full rounded-xl border px-4 py-3 text-sm transition outline-none focus:ring-4 disabled:bg-slate-50 disabled:text-slate-400 dark:bg-gray-900 dark:disabled:bg-gray-800 dark:disabled:text-gray-500"
                            :class="
                                form.errors.message
                                    ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500/10'
                                    : 'border-slate-200 focus:border-slate-900 focus:ring-slate-900/5 dark:border-gray-700'
                            "
                        ></textarea>
                        <p
                            v-if="form.errors.message"
                            class="mt-1.5 text-xs font-medium text-rose-600"
                        >
                            {{ form.errors.message }}
                        </p>
                    </div>

                    <div
                        class="space-y-5 rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-900/5 dark:bg-gray-800 dark:ring-gray-700"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p
                                    class="text-sm font-semibold text-slate-900 dark:text-gray-100"
                                >
                                    Action button
                                </p>
                                <p
                                    class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                                >
                                    Optional call-to-action link in the dialog.
                                </p>
                            </div>
                            <label
                                class="relative inline-flex cursor-pointer items-center"
                            >
                                <input
                                    v-model="form.show_button"
                                    type="checkbox"
                                    class="peer sr-only"
                                    :disabled="form.processing"
                                />
                                <span
                                    class="peer h-6 w-11 rounded-full bg-slate-200 shadow-xs transition peer-checked:bg-slate-900 peer-disabled:opacity-50 after:absolute after:top-0.5 after:left-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5 dark:bg-gray-600 dark:peer-checked:bg-gray-200"
                                ></span>
                            </label>
                        </div>

                        <div
                            v-if="form.show_button"
                            class="grid grid-cols-1 gap-4 pt-2 md:grid-cols-2"
                        >
                            <div>
                                <label
                                    for="button_title"
                                    class="mb-1.5 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                                >
                                    Button title
                                </label>
                                <input
                                    v-model="form.button_title"
                                    type="text"
                                    id="button_title"
                                    placeholder="Learn more"
                                    :disabled="form.processing"
                                    class="w-full rounded-xl border bg-white px-4 py-2.5 text-sm transition outline-none focus:ring-4 dark:bg-gray-900"
                                    :class="
                                        form.errors.button_title
                                            ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500/10'
                                            : 'border-slate-200 focus:border-slate-900 focus:ring-slate-900/5 dark:border-gray-700'
                                    "
                                />
                                <p
                                    v-if="form.errors.button_title"
                                    class="mt-1.5 text-xs font-medium text-rose-600"
                                >
                                    {{ form.errors.button_title }}
                                </p>
                            </div>

                            <div>
                                <label
                                    for="button_link"
                                    class="mb-1.5 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                                >
                                    Button link
                                </label>
                                <input
                                    v-model="form.button_link"
                                    type="text"
                                    id="button_link"
                                    placeholder="https://example.com/details"
                                    :disabled="form.processing"
                                    class="w-full rounded-xl border bg-white px-4 py-2.5 text-sm transition outline-none focus:ring-4 dark:bg-gray-900"
                                    :class="
                                        form.errors.button_link
                                            ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500/10'
                                            : 'border-slate-200 focus:border-slate-900 focus:ring-slate-900/5 dark:border-gray-700'
                                    "
                                />
                                <p
                                    v-if="form.errors.button_link"
                                    class="mt-1.5 text-xs font-medium text-rose-600"
                                >
                                    {{ form.errors.button_link }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div
                        class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-900/5 dark:bg-gray-800 dark:ring-gray-700"
                    >
                        <div class="flex items-center justify-between">
                            <p
                                class="text-sm font-semibold text-slate-900 dark:text-gray-100"
                            >
                                Show Notice
                            </p>
                            <label
                                class="relative inline-flex cursor-pointer items-center"
                            >
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="peer sr-only"
                                    :disabled="form.processing"
                                />
                                <span
                                    class="peer h-6 w-11 rounded-full bg-slate-200 shadow-xs transition peer-checked:bg-slate-900 peer-disabled:opacity-50 after:absolute after:top-0.5 after:left-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5 dark:bg-gray-600 dark:peer-checked:bg-gray-200"
                                ></span>
                            </label>
                        </div>
                    </div>

                    <div
                        v-if="form.is_active && !hasContent"
                        class="rounded-2xl bg-amber-50 p-4 text-xs font-medium text-amber-800 ring-1 ring-amber-900/5 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20"
                    >
                        Please add a title or message body before switching this
                        notice alive.
                    </div>

                    <div
                        class="space-y-4 rounded-2xl border border-slate-100 p-5 ring-1 ring-slate-900/5 dark:border-gray-800 dark:ring-gray-700"
                    >
                        <div>
                            <label
                                for="image"
                                class="mb-1.5 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                            >
                                Cover Image URL
                            </label>
                            <input
                                v-model="form.image"
                                type="text"
                                id="image"
                                placeholder="https://example.com/banner.jpg"
                                :disabled="form.processing"
                                class="w-full rounded-xl border px-4 py-2.5 text-sm transition outline-none focus:ring-4 disabled:bg-slate-50 disabled:text-slate-400 dark:bg-gray-900 dark:disabled:bg-gray-800 dark:disabled:text-gray-500"
                                :class="
                                    form.errors.image
                                        ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500/10'
                                        : 'border-slate-200 focus:border-slate-900 focus:ring-slate-900/5 dark:border-gray-700'
                                "
                            />
                            <p
                                v-if="form.errors.image"
                                class="mt-1.5 text-xs font-medium text-rose-600"
                            >
                                {{ form.errors.image }}
                            </p>
                        </div>

                        <div
                            v-if="form.image"
                            class="overflow-hidden rounded-xl ring-1 ring-slate-900/10 dark:ring-gray-700"
                        >
                            <img
                                :src="form.image"
                                alt="Notice preview"
                                class="h-32 w-full object-cover"
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-end gap-3 border-t border-slate-100 pt-6 lg:col-span-3 dark:border-gray-800"
                >
                    <button
                        type="button"
                        @click="goBack"
                        class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:ring-4 focus:ring-slate-900/10 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin"
                        />
                        <Save v-else class="h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Save Notice' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
