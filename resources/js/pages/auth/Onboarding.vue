<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    User,
    AtSign,
    GraduationCap,
    AlertCircle,
    ArrowLeft,
    CheckCircle2,
    Loader2,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface OnboardingUser {
    google_id: string;
    email: string;
    name: string;
    avatar?: string | null;
}

const props = defineProps<{
    user?: OnboardingUser;
}>();

const page = usePage();
const flashError = computed(() => (page.props as any).flash?.error);

const form = useForm({
    name: props.user?.name || '',
    username: '',
    school: '',
});

const submit = () => {
    form.post('/onboarding');
};
</script>

<template>
    <Head>
        <title>Complete Your Profile - HSCStack</title>
        <meta
            name="description"
            content="Set up your username, name, and school to complete your HSCStack account setup."
        />
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
        class="relative z-10 flex min-h-[85vh] items-center justify-center px-4 py-8 sm:px-6 sm:py-10"
    >
        <div class="w-full max-w-md">
            <!-- Header -->
            <div class="mb-5 text-center">
                <h1
                    class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100"
                >
                    Almost there!
                </h1>
                <p
                    class="mt-1.5 text-xs font-semibold text-slate-500 dark:text-gray-400"
                >
                    অ্যাকাউন্ট তৈরি সম্পন্ন করতে আপনার তথ্যগুলো নিশ্চিত করুন
                </p>
            </div>

            <!-- Flash Error Alert -->
            <div
                v-if="flashError"
                class="mb-6 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-xs font-medium text-rose-700 backdrop-blur-sm dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400"
            >
                <AlertCircle
                    class="h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400"
                />
                <div class="flex-1">{{ flashError }}</div>
            </div>

            <!-- Deep Shadow Card -->
            <div
                class="rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-[0_20px_50px_rgba(8,11,46,0.08)] backdrop-blur-xl sm:p-8 dark:border-gray-800 dark:bg-gray-900/90 dark:shadow-[0_20px_50px_rgba(0,0,0,0.4)]"
            >
                <!-- Connected Google Account Badge -->
                <div
                    v-if="props.user?.email"
                    class="mb-6 flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 p-3.5 dark:border-gray-800 dark:bg-gray-800/40"
                >
                    <img
                        v-if="props.user.avatar"
                        :src="props.user.avatar"
                        :alt="props.user.name"
                        class="h-10 w-10 shrink-0 rounded-full border border-slate-200 object-cover dark:border-gray-700"
                    />
                    <div
                        v-else
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300"
                    >
                        {{ props.user.name?.charAt(0)?.toUpperCase() || 'U' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <p
                                class="truncate text-xs font-bold text-slate-800 dark:text-gray-200"
                            >
                                {{ props.user.email }}
                            </p>
                            <CheckCircle2
                                class="h-3.5 w-3.5 shrink-0 text-emerald-500"
                            />
                        </div>
                        <p
                            class="text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            Verified via Google
                        </p>
                    </div>
                </div>

                <!-- Onboarding Form -->
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Full Name -->
                    <div>
                        <label
                            for="name"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Full Name <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-gray-500"
                            >
                                <User class="h-4 w-4" />
                            </div>
                            <input
                                v-model="form.name"
                                type="text"
                                id="name"
                                required
                                placeholder="Your full name"
                                :disabled="form.processing"
                                class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pr-3.5 pl-10 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 disabled:bg-slate-50 dark:border-gray-700 dark:bg-gray-800/80 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400/20"
                                :class="{
                                    'border-rose-500 focus:ring-rose-500/20 dark:border-rose-500 dark:focus:border-rose-400 dark:focus:ring-rose-400/20':
                                        form.errors.name,
                                }"
                            />
                        </div>
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-xs text-rose-600 dark:text-rose-400"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Username -->
                    <div>
                        <label
                            for="username"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Username <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-gray-500"
                            >
                                <AtSign class="h-4 w-4" />
                            </div>
                            <input
                                v-model="form.username"
                                type="text"
                                id="username"
                                required
                                placeholder="your_username"
                                :disabled="form.processing"
                                class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pr-3.5 pl-10 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 disabled:bg-slate-50 dark:border-gray-700 dark:bg-gray-800/80 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400/20"
                                :class="{
                                    'border-rose-500 focus:ring-rose-500/20 dark:border-rose-500 dark:focus:border-rose-400 dark:focus:ring-rose-400/20':
                                        form.errors.username,
                                }"
                            />
                        </div>
                        <p
                            v-if="form.errors.username"
                            class="mt-1 text-xs text-rose-600 dark:text-rose-400"
                        >
                            {{ form.errors.username }}
                        </p>
                        <p
                            v-else
                            class="mt-1 text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            Letters, numbers, and underscores (3–30 chars).
                        </p>
                    </div>

                    <!-- School / Institution -->
                    <div>
                        <label
                            for="school"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            School / College / Institution
                            <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-gray-500"
                            >
                                <GraduationCap class="h-4 w-4" />
                            </div>
                            <input
                                v-model="form.school"
                                type="text"
                                id="school"
                                required
                                placeholder="e.g., Notre Dame College, Dhaka College, BUET"
                                :disabled="form.processing"
                                class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pr-3.5 pl-10 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 disabled:bg-slate-50 dark:border-gray-700 dark:bg-gray-800/80 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400/20"
                                :class="{
                                    'border-rose-500 focus:ring-rose-500/20 dark:border-rose-500 dark:focus:border-rose-400 dark:focus:ring-rose-400/20':
                                        form.errors.school,
                                }"
                            />
                        </div>
                        <p
                            v-if="form.errors.school"
                            class="mt-1 text-xs text-rose-600 dark:text-rose-400"
                        >
                            {{ form.errors.school }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-4 py-3.5 text-sm font-bold text-white shadow-xs transition-all hover:bg-indigo-700 hover:shadow-md active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                        >
                            <Loader2
                                v-if="form.processing"
                                class="h-4 w-4 animate-spin"
                            />
                            <span>{{
                                form.processing
                                    ? 'Creating Account...'
                                    : 'Create Account & Get Started'
                            }}</span>
                        </button>
                    </div>
                </form>

                <!-- Terms & Privacy subtext -->
                <div
                    class="mt-6 border-t border-slate-100 pt-4 text-center dark:border-gray-800"
                >
                    <p
                        class="text-[11px] leading-relaxed text-slate-400 dark:text-gray-500"
                    >
                        অ্যাকাউন্ট তৈরির মাধ্যমে আপনি আমাদের
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
                            Privacy Policy </Link
                        >-তে সম্মতি দিচ্ছেন।
                    </p>
                </div>
            </div>

            <!-- Cancel and Back link -->
            <div class="mt-6 text-center">
                <Link
                    href="/login"
                    class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 transition-colors hover:text-slate-900 dark:text-gray-500 dark:hover:text-gray-200"
                >
                    <ArrowLeft class="h-3.5 w-3.5" />
                    <span>Back to Sign In</span>
                </Link>
            </div>
        </div>
    </div>
</template>
