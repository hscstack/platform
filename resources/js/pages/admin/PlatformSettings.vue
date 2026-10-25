<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Loader2 } from 'lucide-vue-next';

interface PlatformSettingsProps {
    settings: {
        allow_registration: boolean;
        allow_resource_uploads: boolean;
    };
}

const props = defineProps<PlatformSettingsProps>();

const form = useForm({
    allow_registration: Boolean(props.settings?.allow_registration),
    allow_resource_uploads: Boolean(props.settings?.allow_resource_uploads),
});

const submit = () => {
    form.post('/admin/platform/settings', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Platform Settings" />

    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-gray-800"
        >
            <div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-gray-100">
                    Platform Settings
                </h1>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                    Configure global accessibility toggles for registrations and
                    resource submissions.
                </p>
            </div>

            <button
                type="button"
                @click="submit"
                :disabled="form.processing"
                class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 active:scale-95 disabled:opacity-50"
            >
                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                <Save v-else class="h-4 w-4" />
                <span>Save Settings</span>
            </button>
        </div>

        <!-- Settings Cards -->
        <div class="space-y-4">
            <!-- Registration Control Card -->
            <div
                class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <h3
                            class="text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            Student Account Registration
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            When paused, new visitors cannot create an account
                            via Google onboarding. Existing students can
                            continue logging in.
                        </p>
                    </div>

                    <label
                        class="relative inline-flex cursor-pointer items-center"
                    >
                        <input
                            type="checkbox"
                            v-model="form.allow_registration"
                            class="peer sr-only"
                        />
                        <div
                            class="peer h-6 w-11 rounded-full bg-slate-200 peer-checked:bg-emerald-600 peer-focus:outline-hidden after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white dark:bg-gray-700"
                        ></div>
                    </label>
                </div>
            </div>

            <!-- Resource Uploads Control Card -->
            <div
                class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <h3
                            class="text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            Community Resource Uploads
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            When paused, students cannot upload new lecture
                            notes, books, or practicals. Moderators and admins
                            bypass this restriction.
                        </p>
                    </div>

                    <label
                        class="relative inline-flex cursor-pointer items-center"
                    >
                        <input
                            type="checkbox"
                            v-model="form.allow_resource_uploads"
                            class="peer sr-only"
                        />
                        <div
                            class="peer h-6 w-11 rounded-full bg-slate-200 peer-checked:bg-emerald-600 peer-focus:outline-hidden after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white dark:bg-gray-700"
                        ></div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</template>
