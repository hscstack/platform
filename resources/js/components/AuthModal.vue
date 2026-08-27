<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { X, LogIn } from 'lucide-vue-next';

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
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="modelValue"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs dark:bg-black/50"
            >
                <div
                    class="relative w-full max-w-xs rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <button
                        @click="modelValue = false"
                        class="absolute top-3.5 right-3.5 rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>

                    <h3
                        class="text-sm font-bold text-slate-900 dark:text-gray-100"
                    >
                        {{ title }}
                    </h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                        {{ message }}
                    </p>

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
                            @click="modelValue = false"
                            class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
