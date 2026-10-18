<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, Trash2, ArrowLeft, ShieldAlert } from 'lucide-vue-next';
import { ref } from 'vue';

interface UserInfo {
    id: number;
    name: string;
    email?: string;
}

const props = defineProps<{
    user: UserInfo;
    confirmUrl: string;
}>();

const isProcessing = ref(false);

const handleConfirmDelete = () => {
    isProcessing.value = true;
    router.post(
        props.confirmUrl,
        {},
        {
            onFinish: () => {
                isProcessing.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="অ্যাকাউন্ট মুছে ফেলার নিশ্চিতকরণ - HSCStack" />

    <div
        class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-12 sm:px-6 lg:px-8"
    >
        <div class="w-full max-w-lg">
            <div
                class="overflow-hidden rounded-2xl border border-rose-200/80 bg-white shadow-xl dark:border-rose-900/40 dark:bg-gray-900"
            >
                <!-- Header / Warning Banner -->
                <div
                    class="border-b border-rose-100 bg-rose-50/60 p-6 text-center dark:border-rose-900/30 dark:bg-rose-950/20"
                >
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 shadow-inner dark:bg-rose-900/40 dark:text-rose-400"
                    >
                        <AlertTriangle class="h-7 w-7" />
                    </div>
                    <h1
                        class="mt-4 text-xl font-bold tracking-tight text-slate-900 dark:text-white"
                    >
                        অ্যাকাউন্ট স্থায়ীভাবে মুছে ফেলা
                    </h1>
                    <p class="mt-1 text-sm text-slate-600 dark:text-gray-400">
                        এই প্রক্রিয়াটি সম্পন্ন হলে আপনার অ্যাকাউন্ট এবং ডাটা
                        চিরতরে মুছে যাবে
                    </p>
                </div>

                <!-- Content Body -->
                <div class="space-y-6 p-6 sm:p-8">
                    <!-- Account info notice -->
                    <div
                        class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-gray-800 dark:bg-gray-800/60"
                    >
                        <p
                            class="text-xs font-semibold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                        >
                            চিহ্নিত অ্যাকাউন্ট:
                        </p>
                        <p
                            class="mt-1 text-base font-bold text-slate-900 dark:text-white"
                        >
                            {{ user.name }}
                        </p>
                        <p
                            v-if="user.email"
                            class="font-mono text-sm text-slate-600 dark:text-gray-400"
                        >
                            {{ user.email }}
                        </p>
                    </div>

                    <!-- Warning Details -->
                    <div
                        class="space-y-3 rounded-xl border border-amber-200/80 bg-amber-50/50 p-4 text-xs text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300"
                    >
                        <div class="flex items-start gap-2.5">
                            <ShieldAlert
                                class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
                            />
                            <div class="space-y-1 leading-relaxed">
                                <p class="font-semibold">
                                    গুরুত্বপূর্ণ সতর্কতা:
                                </p>
                                <ul
                                    class="list-inside list-disc space-y-1 pl-1"
                                >
                                    <li>
                                        আপনার প্রোফাইল, অ্যাক্টিভিটি ও ব্যক্তিগত
                                        ডেটা স্থায়ীভাবে মুছে যাবে (আপলোডকৃত
                                        রিসোর্স ব্যতীত, যা আমাদের নীতিমালা
                                        অনুযায়ী অক্ষত থাকবে)।
                                    </li>
                                    <li>
                                        এই কাজটি পূর্বাবস্থায় ফিরিয়ে নেওয়া
                                        (Undo) সম্ভব নয়।
                                    </li>
                                    <li>
                                        আপনি যদি অ্যাকাউন্ট ডিলিট করতে না চান,
                                        তবে পেজটি বন্ধ করুন অথবা নিরাপদে ফিরে
                                        যান।
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div
                        class="flex flex-col-reverse items-center gap-3 pt-2 sm:flex-row"
                    >
                        <a
                            href="/"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 sm:w-1/2 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            ফিরে যান (বাতিল)
                        </a>

                        <button
                            type="button"
                            :disabled="isProcessing"
                            @click="handleConfirmDelete"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-500 focus:ring-2 focus:ring-rose-500/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 sm:w-1/2"
                        >
                            <Trash2 class="h-4 w-4" />
                            <span v-if="isProcessing">মুছে ফেলা হচ্ছে...</span>
                            <span v-else>স্থায়ীভাবে মুছুন</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
