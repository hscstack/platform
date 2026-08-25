<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    LogIn,
    ShieldCheck,
    Zap,
    KeyRound,
    UserCheck,
    AlertCircle,
    ArrowLeft,
} from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const flashError = computed(() => page.props.flash?.error);
const flashSuccess = computed(() => page.props.flash?.success);
</script>

<template>
    <Head>
        <title>Sign In / Register</title>
        <meta
            name="description"
            content="Sign in or create an account on HSCStack with 1-click Google authentication."
        />
        <meta property="og:title" content="Sign In / Register - HSCStack" />
    </Head>

    <!-- Atmospheric Blobs -->
    <div class="pointer-events-none fixed inset-0 z-0">
        <div
            class="absolute top-[-10%] left-[-10%] h-[50%] w-[50%] rounded-full bg-indigo-200/40 blur-[120px] dark:bg-indigo-500/10"
        ></div>
        <div
            class="absolute right-[-5%] bottom-[10%] h-[40%] w-[40%] rounded-full bg-violet-200/30 blur-[100px] dark:bg-violet-500/10"
        ></div>
    </div>

    <div
        class="relative z-10 flex min-h-[85vh] items-center justify-center px-4 py-12 sm:px-6"
    >
        <div class="w-full max-w-md">
            <!-- Header -->
            <div class="mb-8 text-center">
                <div
                    class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 shadow-sm dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <LogIn class="h-7 w-7" />
                </div>
                <h1
                    class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100"
                >
                    Welcome to HSCStack
                </h1>
                <p
                    class="mt-2 text-xs font-semibold text-slate-500 dark:text-gray-400"
                >
                    সহজ ও নিরাপদ Passwordless Login সিস্টেম
                </p>
            </div>

            <!-- Flash Error Alert -->
            <div
                v-if="flashError"
                class="mb-6 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-xs font-medium text-rose-700 backdrop-blur-sm dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400"
            >
                <AlertCircle class="h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400" />
                <div class="flex-1">{{ flashError }}</div>
            </div>

            <!-- Flash Success Alert -->
            <div
                v-if="flashSuccess"
                class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-xs font-medium text-emerald-700 backdrop-blur-sm dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400"
            >
                <ShieldCheck class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                <div class="flex-1">{{ flashSuccess }}</div>
            </div>

            <!-- Deep Shadow Login Card -->
            <div
                class="rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-[0_20px_50px_rgba(8,11,46,0.08)] backdrop-blur-xl sm:p-8 dark:border-gray-800 dark:bg-gray-900/90 dark:shadow-[0_20px_50px_rgba(0,0,0,0.4)]"
            >
                <!-- Main Action Button -->
                <a
                    href="/auth/google"
                    class="group flex w-full items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-white py-3.5 px-4 text-sm font-bold text-slate-800 shadow-xs transition-all duration-200 hover:scale-[1.01] hover:border-slate-300 hover:bg-slate-50 hover:shadow-md active:scale-[0.98] dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:hover:border-gray-600 dark:hover:bg-gray-750"
                >
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24">
                        <path
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                            fill="#4285F4"
                        />
                        <path
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                            fill="#34A853"
                        />
                        <path
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"
                            fill="#FBBC05"
                        />
                        <path
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"
                            fill="#EA4335"
                        />
                    </svg>
                    <span>Continue with Google</span>
                </a>

                <!-- Guided How-It-Works section -->
                <div class="mt-6 border-t border-slate-100 pt-6 dark:border-gray-800">
                    <p
                        class="mb-3.5 text-[11px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                    >
                        How Sign-in Works
                    </p>

                    <div class="space-y-3.5">
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400"
                            >
                                <Zap class="h-3.5 w-3.5" />
                            </div>
                            <div class="text-xs leading-relaxed text-slate-600 dark:text-gray-400">
                                <span class="font-semibold text-slate-900 dark:text-gray-200"
                                    >Instant 1-Click Access:</span
                                >
                                নতুন ইউজার হলে এক ক্লিকেই auto account create হবে, আর পুরাতন ইউজার হলে সরাসরি Login হয়ে যাবে।
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                            >
                                <KeyRound class="h-3.5 w-3.5" />
                            </div>
                            <div class="text-xs leading-relaxed text-slate-600 dark:text-gray-400">
                                <span class="font-semibold text-slate-900 dark:text-gray-200"
                                    >Zero Password :</span
                                >
                                কোনো পাসওয়ার্ড মনে রাখা বা রিসেট করার ঝামেলা নেই—সবকিছু Google-এর মাধ্যমে secure।
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                            >
                                <UserCheck class="h-3.5 w-3.5" />
                            </div>
                            <div class="text-xs leading-relaxed text-slate-600 dark:text-gray-400">
                                <span class="font-semibold text-slate-900 dark:text-gray-200"
                                    >Be a Contributor:</span
                                >
                                একাউন্ট খোলার পর আপনি আমাদের টিমে Contributor বা Curator হিসেবে
                                <Link
                                    href="/join"
                                    class="font-semibold text-indigo-600 underline decoration-indigo-300 hover:text-indigo-700 dark:text-indigo-400 dark:decoration-indigo-600"
                                >
                                    Apply করতে পারবেন
                                </Link>।
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms & Privacy subtext -->
                <div class="mt-6 border-t border-slate-100 pt-4 text-center dark:border-gray-800">
                    <p class="text-[11px] leading-relaxed text-slate-400 dark:text-gray-500">
                        লগইন করার মাধ্যমে আপনি আমাদের
                        <Link
                            href="/terms-service"
                            class="font-medium text-slate-600 underline decoration-slate-300 hover:text-slate-900 dark:text-gray-400 dark:decoration-gray-600 dark:hover:text-gray-200"
                        >
                            Terms of Service
                        </Link>
                        ও
                        <Link
                            href="/privacy-policy"
                            class="font-medium text-slate-600 underline decoration-slate-300 hover:text-slate-900 dark:text-gray-400 dark:decoration-gray-600 dark:hover:text-gray-200"
                        >
                            Privacy Policy
                        </Link>-তে সম্মতি দিচ্ছেন।
                    </p>
                </div>
            </div>

            <!-- Back to site link -->
            <div class="mt-6 text-center">
                <Link
                    href="/"
                    class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 transition-colors hover:text-slate-900 dark:text-gray-500 dark:hover:text-gray-200"
                >
                    <ArrowLeft class="h-3.5 w-3.5" />
                    <span>Back to Site</span>
                </Link>
            </div>
        </div>
    </div>
</template>
