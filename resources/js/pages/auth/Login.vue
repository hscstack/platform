<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import { kInput, kButton } from 'konsta/vue';

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post('/login', {
        forceFormData: true,
        onError: (errors) => alert(Object.values(errors)[0]),
    });
};
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
        class="relative z-10 flex min-h-[70vh] items-center justify-center px-6 py-16"
    >
        <div class="w-full max-w-sm">
            <div class="mb-10 text-center">
                <h1
                    class="text-3xl font-black tracking-tight text-slate-900 dark:text-gray-100"
                >
                    Member Portal
                </h1>
                <p
                    class="mt-2 text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase dark:text-gray-500"
                >
                    Restricted Access
                </p>
            </div>

            <!-- Deep Shadow Login Card -->
            <form
                @submit.prevent="submit"
                class="rounded-3xl border border-slate-100 bg-white/80 p-8 shadow-[0_20px_50px_rgba(8,11,46,0.15)] backdrop-blur-xl dark:border-gray-800 dark:bg-gray-900/80"
            >
                <div class="space-y-6">
                    <div>
                        <k-input
                            type="email"
                            :value="form.email"
                            @input="form.email = $event.target.value"
                            required
                            placeholder="admin@example.com"
                            outline
                            label="Email"
                        />
                    </div>

                    <div>
                        <k-input
                            type="password"
                            :value="form.password"
                            @input="form.password = $event.target.value"
                            required
                            placeholder="••••••••"
                            outline
                            label="Password"
                        />
                    </div>
                </div>
                <div
                    class="mt-6 rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 dark:border-indigo-500/30 dark:bg-indigo-500/10"
                >
                    <p
                        class="mb-1 text-center text-[9px] font-bold tracking-widest text-indigo-400 uppercase dark:text-indigo-300"
                    >
                        Read-only Access For Viewing Admin Panel
                    </p>
                    <div
                        class="flex justify-center gap-4 font-mono text-[10px] text-slate-600 dark:text-gray-400"
                    >
                        <p>
                            Email:
                            <span
                                class="font-bold text-slate-900 dark:text-gray-100"
                                >check@example.com</span
                            >
                        </p>
                        <p>
                            Pass:
                            <span
                                class="font-bold text-slate-900 dark:text-gray-100"
                                >check123</span
                            >
                        </p>
                    </div>
                </div>
                <k-button type="submit" fill :disabled="form.processing">
                    {{ form.processing ? 'Authenticating...' : 'Enter System' }}
                </k-button>

                <div class="mt-6 text-center">
                    <p
                        class="text-[10px] font-black tracking-widest text-slate-400 uppercase dark:text-gray-500"
                    >
                        Want to be a member?
                        <Link
                            href="/join"
                            class="ml-1 text-indigo-600 transition-colors hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            Join here
                        </Link>
                    </p>
                </div>
            </form>

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
