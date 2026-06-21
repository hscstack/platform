<script setup lang="ts">

defineProps({
    totalSubjects: {
        type: Number,
        required: true,
    },
    totalResources: {
        type: Number,
        required: true,
    },
    totalUsers: {
        type: Number,
        required: true,
    },
});


</script>

<template>
    <section class="mx-auto max-w-7xl px-4 pb-20 sm:px-6">
        <div class="mb-10 text-center">

            <h2 class="mt-1 text-2xl font-black tracking-tight text-indigo-600 sm:text-3xl">
                Repository Statistics
            </h2>
            <p class="mx-auto mt-2 max-w-sm text-sm text-slate-600">
                অ্যাক্টিভ সাবজেক্ট, স্টাডি রিসোর্স এবং ইউজার অ্যাক্টিভিটির লাইভ আপডেট।
            </p>
        </div>

        <div
            class="grid grid-cols-1 divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-[0_2px_8px_-3px_rgba(0,0,0,0.05)] sm:grid-cols-3 sm:divide-x sm:divide-y-0"
        >
            <div class="group flex items-center justify-between p-6 transition-colors duration-300 hover:bg-slate-50/50">
                <div>
                    <p class="text-xs font-bold tracking-wider text-slate-400 uppercase transition-colors group-hover:text-slate-500">
                        Total Subjects
                    </p>
                    <p class="mt-2 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">
                        {{ totalSubjects }}
                    </p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 transition-all duration-300 group-hover:scale-110 group-hover:bg-indigo-600 group-hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
            </div>

            <div class="group flex items-center justify-between p-6 transition-colors duration-300 hover:bg-slate-50/50">
                <div>
                    <p class="text-xs font-bold tracking-wider text-slate-400 uppercase transition-colors group-hover:text-slate-500">
                        Total Resources
                    </p>
                    <p class="mt-2 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">
                        {{ totalResources }}
                    </p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-50 text-violet-600 transition-all duration-300 group-hover:scale-110 group-hover:bg-violet-600 group-hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
            </div>

            <div class="group flex items-center justify-between p-6 transition-colors duration-300 hover:bg-slate-50/50">
                <div>
                    <p class="text-xs font-bold tracking-wider text-slate-400 uppercase transition-colors group-hover:text-slate-500">
                        Total Contributors
                    </p>
                    <p class="mt-2 bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-3xl font-black tracking-tight text-transparent sm:text-4xl">
                        {{ totalUsers }}
                    </p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-fuchsia-50 text-fuchsia-600 transition-all duration-300 group-hover:scale-110 group-hover:bg-fuchsia-600 group-hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.281m5.94 2.28l-2.28 5.941" />
                    </svg>
                </div>
            </div>
        </div>
    </section>
</template>
