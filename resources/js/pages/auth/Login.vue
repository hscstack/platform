<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';

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
            class="absolute top-[-10%] left-[-10%] h-[50%] w-[50%] rounded-full bg-indigo-200/40 blur-[120px]"
        ></div>
        <div
            class="absolute right-[-5%] bottom-[10%] h-[40%] w-[40%] rounded-full bg-violet-200/30 blur-[100px]"
        ></div>
    </div>

    <div
        class="relative z-10 flex min-h-[70vh] items-center justify-center px-6 py-16"
    >
        <div class="w-full max-w-sm">
            <div class="mb-10 text-center">
                <h1 class="text-3xl font-black tracking-tight text-slate-900">
                    Member Portal
                </h1>
                <p
                    class="mt-2 text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase"
                >
                    Restricted Access
                </p>
            </div>

            <!-- Deep Shadow Login Card -->
            <form
                @submit.prevent="submit"
                class="rounded-3xl border border-slate-100 bg-white/80 p-8 shadow-[0_20px_50px_rgba(8,11,46,0.15)] backdrop-blur-xl"
            >
                <div class="space-y-6">
                    <div>
                        <label
                            class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                            >Email</label
                        >
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            class="w-full rounded-2xl border-0 bg-slate-50 px-5 py-4 transition-all placeholder:text-slate-300 focus:ring-2 focus:ring-slate-900"
                            placeholder="admin@example.com"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                            >Password</label
                        >
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            class="w-full rounded-2xl border-0 bg-slate-50 px-5 py-4 transition-all placeholder:text-slate-300 focus:ring-2 focus:ring-slate-900"
                            placeholder="••••••••"
                        />
                    </div>
                </div>
                <div
                    class="mt-6 rounded-xl border border-indigo-100 bg-indigo-50/50 p-4"
                >
                    <p
                        class="mb-1 text-center text-[9px] font-bold tracking-widest text-indigo-400 uppercase"
                    >
                        Read-only Access For Viewing Admin Panel
                    </p>
                    <div
                        class="flex justify-center gap-4 font-mono text-[10px] text-slate-600"
                    >
                        <p>
                            Email:
                            <span class="font-bold text-slate-900"
                                >check@example.com</span
                            >
                        </p>
                        <p>
                            Pass:
                            <span class="font-bold text-slate-900"
                                >check123</span
                            >
                        </p>
                    </div>
                </div>
                <button
                    :disabled="form.processing"
                    class="mt-8 w-full rounded-2xl bg-slate-900 py-4 text-xs font-black tracking-widest text-white uppercase transition-all hover:scale-[1.02] hover:bg-indigo-600 active:scale-[0.98]"
                >
                    {{ form.processing ? 'Authenticating...' : 'Enter System' }}
                </button>

                <div class="mt-6 text-center">
                    <p
                        class="text-[10px] font-black tracking-widest text-slate-400 uppercase"
                    >
                        Want to be a member?
                        <Link
                            href="/join"
                            class="ml-1 text-indigo-600 transition-colors hover:text-indigo-800"
                        >
                            Join here
                        </Link>
                    </p>
                </div>
            </form>

            <div class="mt-8 text-center">
                <Link
                    href="/"
                    class="text-[10px] font-black tracking-widest text-slate-400 uppercase transition-colors hover:text-slate-900"
                >
                    ← Back to Site
                </Link>
            </div>
        </div>
    </div>
</template>
