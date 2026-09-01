<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LogIn } from 'lucide-vue-next';
import BaseModal from '@/components/BaseModal.vue';

const modelValue = defineModel<boolean>({ default: false });

defineProps({
    title: {
        type: String,
        default: 'Sign in required',
    },
    message: {
        type: String,
        default: 'Please sign in to perform this action.',
    },
});

const page = usePage();
</script>

<template>
    <BaseModal
        :is-open="modelValue"
        :title="title"
        :description="message"
        max-width="sm"
        position="center"
        @close="modelValue = false"
    >
        <div class="p-5 pt-2">
            <div class="mt-4 flex items-center gap-2">
                <Link
                    :href="`/login?redirect=${encodeURIComponent(page.url)}`"
                    @click="modelValue = false"
                    class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-slate-900 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                >
                    <LogIn class="h-3.5 w-3.5" />
                    <span>Sign in</span>
                </Link>
                <button
                    type="button"
                    @click="modelValue = false"
                    class="cursor-pointer rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                    Cancel
                </button>
            </div>
        </div>
    </BaseModal>
</template>
