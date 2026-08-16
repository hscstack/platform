<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Loader2, Save } from 'lucide-vue-next';
import { computed } from 'vue';
import {
    kInput,
    kTextarea,
    kCheckbox,
    kToggle,
    kButton,
    kBlockTitle,
} from 'konsta/vue';

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
                <kBlockTitle> Site Notice </kBlockTitle>
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
                        <k-input
                            label="Title"
                            type="text"
                            :value="form.title"
                            @input="form.title = $event.target.value"
                            placeholder="Important announcement"
                            outline
                            :disabled="form.processing"
                            :error="form.errors.title"
                        />
                        <p
                            v-if="form.errors.title"
                            class="mt-1.5 text-xs font-medium text-rose-600"
                        >
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <div>
                        <k-textarea
                            label="Message"
                            :value="form.message"
                            @input="form.message = $event.target.value"
                            placeholder="Write the notice message for visitors..."
                            outline
                            :disabled="form.processing"
                            :rows="6"
                            resizable
                        />
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
                            <k-toggle
                                :checked="form.show_button"
                                @change="
                                    form.show_button = $event.target.checked
                                "
                                :disabled="form.processing"
                            />
                        </div>

                        <div
                            v-if="form.show_button"
                            class="grid grid-cols-1 gap-4 pt-2 md:grid-cols-2"
                        >
                            <div>
                                <k-input
                                    label="Button title"
                                    type="text"
                                    :value="form.button_title"
                                    @input="
                                        form.button_title = $event.target.value
                                    "
                                    placeholder="Learn more"
                                    outline
                                    :disabled="form.processing"
                                    :error="form.errors.button_title"
                                />
                                <p
                                    v-if="form.errors.button_title"
                                    class="mt-1.5 text-xs font-medium text-rose-600"
                                >
                                    {{ form.errors.button_title }}
                                </p>
                            </div>

                            <div>
                                <k-input
                                    label="Button link"
                                    type="text"
                                    :value="form.button_link"
                                    @input="
                                        form.button_link = $event.target.value
                                    "
                                    placeholder="https://example.com/details"
                                    outline
                                    :disabled="form.processing"
                                    :error="form.errors.button_link"
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
                            <k-toggle
                                :checked="form.is_active"
                                @change="form.is_active = $event.target.checked"
                                :disabled="form.processing"
                            />
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
                            <k-input
                                label="Cover Image URL"
                                type="text"
                                :value="form.image"
                                @input="form.image = $event.target.value"
                                placeholder="https://example.com/banner.jpg"
                                outline
                                :disabled="form.processing"
                                :error="form.errors.image"
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
                    <k-button outline @click="goBack"> Cancel </k-button>
                    <k-button type="submit" fill :disabled="form.processing">
                        <Loader2
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin"
                        />
                        <Save v-else class="h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Save Notice' }}
                    </k-button>
                </div>
            </form>
        </div>
    </div>
</template>
