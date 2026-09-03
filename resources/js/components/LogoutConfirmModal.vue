<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import MaterialIcon from '@/components/ui/MaterialIcon.vue';

defineProps<{ open: boolean }>();
const emit = defineEmits<{ (e: 'update:open', v: boolean): void }>();

const close = () => emit('update:open', false);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[70] flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm dark:bg-black/60"
                @click="close"
            />
            <div
                class="relative w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-2xl dark:border-slate-700 dark:bg-slate-900"
            >
                <button
                    type="button"
                    @click="close"
                    class="absolute top-3.5 right-3.5 flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-300"
                    aria-label="Cancel"
                >
                    <MaterialIcon name="close" :size="18" />
                </button>
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400"
                >
                    <MaterialIcon name="logout" :size="24" />
                </div>
                <h3
                    class="mt-3.5 text-base font-bold text-slate-900 dark:text-slate-100"
                >
                    Log out?
                </h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    You'll need to sign in again to access your account
                    features.
                </p>
                <div class="mt-5 flex gap-2.5">
                    <button
                        type="button"
                        @click="close"
                        class="flex-1 rounded-xl border border-slate-200 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        Cancel
                    </button>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-rose-600 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-rose-700 active:scale-95 dark:bg-rose-600 dark:hover:bg-rose-500"
                    >
                        <MaterialIcon name="logout" :size="16" />
                        <span>Log out</span>
                    </Link>
                </div>
            </div>
        </div>
    </Teleport>
</template>
