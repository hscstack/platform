<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const page = usePage();
const isSsc = computed(() => page.url.startsWith('/ssc'));
</script>

<template>
    <div class="px-6 pt-8 pb-8 text-center sm:pt-14 sm:pb-12">
        <div class="mx-auto max-w-3xl">
            <h1
                class="mb-4 text-4xl font-black tracking-tight text-slate-900 sm:text-6xl lg:leading-[1.15] dark:text-gray-100"
            >
                Everything You Need, <br />
                <span
                    class="bg-gradient-to-r bg-clip-text text-transparent"
                    :class="
                        isSsc
                            ? 'from-emerald-600 via-teal-600 to-cyan-600'
                            : 'from-indigo-600 via-violet-600 to-fuchsia-600'
                    "
                >
                    Together with Peers.
                </span>
            </h1>

            <p
                class="mx-auto max-w-xl text-base leading-relaxed font-medium text-slate-700/90 sm:text-lg dark:text-gray-400"
            >
                সাবজেক্ট অনুযায়ী খুঁজে নিন প্রয়োজনীয় সব ক্লাস ও রিসোর্স, যেকোনো
                ডাউট নিয়ে প্রশ্ন করুন ফোরামে, আর লাইভ চ্যাটে যুক্ত থাকুন সারা
                দেশের সহপাঠীদের সাথে।
            </p>
        </div>
    </div>
</template>
