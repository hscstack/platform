<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LogIn, ShieldCheck } from 'lucide-vue-next';
</script>

<template>
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
        class="relative z-10 flex min-h-[75vh] items-center justify-center px-6 py-16"
    >
        <div class="w-full max-w-sm">
            <div class="mb-10 text-center">
                <div
                    class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 shadow-sm dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <LogIn class="h-7 w-7" />
                </div>
                <h1
                    class="text-3xl font-black tracking-tight text-slate-900 dark:text-gray-100"
                >
                    Welcome Back
                </h1>
                <p
                    class="mt-2 text-xs font-semibold text-slate-500 dark:text-gray-400"
                >
                    Sign in to access your profile and platform resources
                </p>
            </div>

            <!-- Deep Shadow Login Card -->
            <div
                class="rounded-3xl border border-slate-100 bg-white/80 p-8 shadow-[0_20px_50px_rgba(8,11,46,0.15)] backdrop-blur-xl dark:border-gray-800 dark:bg-gray-900/80"
            >
                <a
                    href="/auth/google"
                    class="flex w-full items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-white py-3.5 text-xs font-bold text-slate-700 shadow-sm transition-all hover:scale-[1.02] hover:bg-slate-50 active:scale-[0.98] dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24">
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

                <div
                    class="mt-6 flex items-center justify-center gap-2 text-[10px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                >
                    <ShieldCheck class="h-3.5 w-3.5 text-emerald-500" />
                    <span>Fast & Secure Authentication</span>
                </div>
            </div>

            <div class="mt-8 text-center">
                <Link
                    href="/"
                    class="text-[10px] font-black tracking-widest text-slate-400 uppercase transition-colors hover:text-slate-900 dark:text-gray-500 dark:hover:text-gray-100"
                >
                    ← Back to Site
                </Link>
            </div>
        </div>
    </div>
</template>

