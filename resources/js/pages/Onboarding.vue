<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Sparkles,
    User as UserIcon,
    AtSign,
    ArrowRight,
    Loader2,
    CheckCircle2,
    XCircle,
    Building2,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import AppLogo from '@/components/AppLogo.vue';

const props = defineProps<{
    user: {
        name: string;
        email: string;
        suggested_username: string;
        institution: string;
    };
}>();

const form = useForm({
    name: props.user?.name || '',
    username: props.user?.suggested_username || '',
    institution: props.user?.institution || '',
});

// Live username availability state
const isCheckingUsername = ref(false);
const usernameStatus = ref<{
    checked: boolean;
    available: boolean;
    message: string;
}>({
    checked: false,
    available: false,
    message: '',
});

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

const checkAvailability = (val: string) => {
    const cleanVal = val.trim().toLowerCase().replace(/\s+/g, '_');
    form.username = cleanVal;

    if (!cleanVal || cleanVal.length < 3) {
        usernameStatus.value = {
            checked: false,
            available: false,
            message: '',
        };

        return;
    }

    if (!/^[a-zA-Z0-9_]+$/.test(cleanVal)) {
        usernameStatus.value = {
            checked: true,
            available: false,
            message: 'Only letters, numbers, and underscores are allowed.',
        };

        return;
    }

    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    isCheckingUsername.value = true;

    debounceTimer = setTimeout(async () => {
        try {
            const res = await fetch(
                `/api/check-username?username=${encodeURIComponent(cleanVal)}`,
                {
                    headers: { Accept: 'application/json' },
                },
            );

            if (res.ok) {
                const data = await res.json();
                usernameStatus.value = {
                    checked: true,
                    available: data.available,
                    message: data.message,
                };
            }
        } catch {
            // Ignore background error
        } finally {
            isCheckingUsername.value = false;
        }
    }, 350);
};

watch(
    () => form.username,
    (newVal) => {
        checkAvailability(newVal);
    },
    { immediate: true },
);

// Quick suggestions for college / institution
const quickInstitutions = [
    'Notre Dame College',
    'Dhaka College',
    'Rajuk Uttara Model College',
    'Adamjee Cantonment College',
    'Viqarunnisa Noon College',
    'Holy Cross College',
    'Chittagong College',
];

const selectInstitution = (name: string) => {
    form.institution = name;
};

const submit = () => {
    form.post('/onboarding', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head>
        <title>Welcome to HSCStack - Complete Your Profile</title>
        <meta
            name="description"
            content="Complete your profile to join the HSCStack community."
        />
    </Head>

    <div
        class="relative flex min-h-screen flex-col justify-between bg-slate-50 px-4 py-8 font-sans antialiased sm:px-6 lg:px-8 dark:bg-zinc-950"
    >
        <!-- Background Ambient Glow -->
        <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden">
            <div
                class="absolute -top-[20%] left-1/2 h-[700px] w-[1000px] -translate-x-1/2 rounded-full bg-[radial-gradient(ellipse_at_center,rgba(99,102,241,0.15)_0%,rgba(165,180,252,0.03)_50%,transparent_70%)] blur-[100px] dark:bg-[radial-gradient(ellipse_at_center,rgba(99,102,241,0.08)_0%,transparent_70%)]"
            ></div>
        </div>

        <!-- Top Minimal Nav -->
        <header class="relative z-10 mx-auto w-full max-w-xl">
            <div class="flex items-center justify-between">
                <AppLogo />
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200/80 bg-indigo-50/80 px-3 py-1 text-xs font-semibold text-indigo-700 shadow-2xs dark:border-indigo-900/60 dark:bg-indigo-950/60 dark:text-indigo-300"
                >
                    <Sparkles class="h-3.5 w-3.5" />
                    Quick Profile Setup
                </span>
            </div>
        </header>

        <!-- Main Form Card -->
        <main class="relative z-10 my-auto py-8">
            <div
                class="mx-auto w-full max-w-xl overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xl shadow-slate-200/40 sm:p-10 dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-none"
            >
                <!-- Title Header -->
                <div class="mb-8">
                    <h1
                        class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl dark:text-zinc-100"
                    >
                        Welcome to HSCStack 👋
                    </h1>
                    <p
                        class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-zinc-400"
                    >
                        Let's set up your profile so you can join discussions,
                        ask questions in Global Chat, and share resources.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- 1. Full Name -->
                    <div>
                        <label
                            for="name"
                            class="mb-1.5 flex items-center justify-between text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-zinc-300"
                        >
                            <span>Full Name</span>
                            <span
                                class="text-[11px] font-normal text-slate-400 dark:text-zinc-500"
                            >
                                Display Name
                            </span>
                        </label>
                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-zinc-500"
                            >
                                <UserIcon class="h-4 w-4" />
                            </div>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Your full name"
                                :disabled="form.processing"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pr-4 pl-10 text-sm font-medium text-slate-900 transition outline-none focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 disabled:opacity-60 dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100 dark:focus:border-indigo-400 dark:focus:bg-zinc-800"
                                :class="
                                    form.errors.name ? 'border-rose-500!' : ''
                                "
                            />
                        </div>
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-xs text-rose-600 dark:text-rose-400"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- 2. Username / Handle -->
                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label
                                for="username"
                                class="text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-zinc-300"
                            >
                                Username
                            </label>

                            <!-- Live Availability Indicator -->
                            <div
                                class="flex items-center gap-1 text-xs font-medium"
                            >
                                <span
                                    v-if="isCheckingUsername"
                                    class="flex items-center gap-1 text-slate-400 dark:text-zinc-500"
                                >
                                    <Loader2
                                        class="h-3 w-3 animate-spin text-indigo-500"
                                    />
                                    Checking...
                                </span>
                                <span
                                    v-else-if="
                                        usernameStatus.checked &&
                                        usernameStatus.available
                                    "
                                    class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400"
                                >
                                    <CheckCircle2 class="h-3.5 w-3.5" />
                                    Available
                                </span>
                                <span
                                    v-else-if="
                                        usernameStatus.checked &&
                                        !usernameStatus.available
                                    "
                                    class="flex items-center gap-1 text-rose-600 dark:text-rose-400"
                                >
                                    <XCircle class="h-3.5 w-3.5" />
                                    {{ usernameStatus.message }}
                                </span>
                            </div>
                        </div>

                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-zinc-500"
                            >
                                <AtSign class="h-4 w-4" />
                            </div>
                            <input
                                id="username"
                                v-model="form.username"
                                type="text"
                                required
                                minlength="3"
                                maxlength="30"
                                placeholder="your_handle"
                                :disabled="form.processing"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pr-4 pl-10 font-mono text-sm text-slate-900 transition outline-none focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 disabled:opacity-60 dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100 dark:focus:border-indigo-400 dark:focus:bg-zinc-800"
                                :class="
                                    form.errors.username ||
                                    (usernameStatus.checked &&
                                        !usernameStatus.available)
                                        ? 'border-rose-500!'
                                        : ''
                                "
                            />
                        </div>

                        <!-- Live Link Preview -->
                        <div class="mt-1.5 flex items-center justify-between">
                            <p
                                class="text-[11px] text-slate-400 dark:text-zinc-500"
                            >
                                Public Profile:
                                <span
                                    class="font-mono text-indigo-600 dark:text-indigo-400"
                                >
                                    hscstack.com/u/{{
                                        form.username || 'username'
                                    }}
                                </span>
                            </p>
                        </div>
                        <p
                            v-if="form.errors.username"
                            class="mt-1 text-xs text-rose-600 dark:text-rose-400"
                        >
                            {{ form.errors.username }}
                        </p>
                    </div>

                    <!-- 3. College / School Name -->
                    <div>
                        <label
                            for="institution"
                            class="mb-1.5 flex items-center justify-between text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-zinc-300"
                        >
                            <span>College / School</span>
                            <span
                                class="text-[11px] font-normal text-slate-400 dark:text-zinc-500"
                            >
                                Institution
                            </span>
                        </label>
                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-zinc-500"
                            >
                                <Building2 class="h-4 w-4" />
                            </div>
                            <input
                                id="institution"
                                v-model="form.institution"
                                type="text"
                                required
                                placeholder="e.g. Notre Dame College"
                                :disabled="form.processing"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pr-4 pl-10 text-sm font-medium text-slate-900 transition outline-none focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 disabled:opacity-60 dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100 dark:focus:border-indigo-400 dark:focus:bg-zinc-800"
                                :class="
                                    form.errors.institution
                                        ? 'border-rose-500!'
                                        : ''
                                "
                            />
                        </div>

                        <!-- Quick Choice Chips -->
                        <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                            <span
                                class="text-[10px] font-semibold text-slate-400 dark:text-zinc-500"
                            >
                                Popular:
                            </span>
                            <button
                                v-for="inst in quickInstitutions"
                                :key="inst"
                                type="button"
                                @click="selectInstitution(inst)"
                                class="cursor-pointer rounded-lg border border-slate-200/80 bg-slate-100/70 px-2 py-0.5 text-[11px] font-medium text-slate-600 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 active:scale-95 dark:border-zinc-700/80 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:border-indigo-500/50 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-300"
                            >
                                {{ inst }}
                            </button>
                        </div>
                        <p
                            v-if="form.errors.institution"
                            class="mt-1 text-xs text-rose-600 dark:text-rose-400"
                        >
                            {{ form.errors.institution }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="
                                form.processing ||
                                isCheckingUsername ||
                                (usernameStatus.checked &&
                                    !usernameStatus.available)
                            "
                            class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-indigo-600 py-3 text-sm font-bold text-white shadow-md shadow-indigo-600/20 transition-all hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-600/30 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Loader2
                                v-if="form.processing"
                                class="h-4 w-4 animate-spin"
                            />
                            <template v-else>
                                <span>Complete Setup & Enter</span>
                                <ArrowRight class="h-4 w-4" />
                            </template>
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <!-- Footer -->
        <footer class="relative z-10 mx-auto text-center">
            <p class="text-xs text-slate-400 dark:text-zinc-500">
                Logged in as
                <span class="font-medium text-slate-600 dark:text-zinc-300">{{
                    user.email
                }}</span>
                • You can edit additional profile details later in Settings.
            </p>
        </footer>
    </div>
</template>
