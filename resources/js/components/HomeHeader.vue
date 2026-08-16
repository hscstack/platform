<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { kSearchbar } from 'konsta/vue';
import { computed } from 'vue';
const searchQuery = defineModel();
const page = usePage();
const isSsc = computed(() => page.url.startsWith('/ssc'));
</script>

<template>
    <div class="px-6 pt-16 pb-3 text-center sm:pt-24 sm:pb-6">
        <div class="mx-auto max-w-3xl">
            <h1
                class="mb-4 text-4xl font-black tracking-tight text-slate-900 sm:text-6xl lg:leading-[1.15] dark:text-gray-100"
            >
                Your knowledge, <br />
                <span
                    class="bg-gradient-to-r bg-clip-text text-transparent"
                    :class="
                        isSsc
                            ? 'from-emerald-600 via-teal-600 to-cyan-600'
                            : 'from-indigo-600 via-violet-600 to-fuchsia-600'
                    "
                >
                    perfectly organized.
                </span>
            </h1>

            <p
                class="mx-auto mb-10 max-w-xl text-base leading-relaxed font-medium text-slate-700/90 sm:text-lg dark:text-gray-400"
            >
                HSC & SSC স্টুডেন্টদের জন্য একদম ক্লিন ও ফাস্ট রিসোর্স
                রিপোজিটরি। সাবজেক্ট এবং চ্যাপ্টার অনুযায়ী খুঁজে নিন প্রয়োজনীয়
                ক্লাস, নোটস এবং প্র্যাক্টিক্যাল ।
            </p>

            <div class="mx-auto max-w-lg">
                <k-searchbar
                    :value="searchQuery"
                    @input="searchQuery = $event.target.value"
                    placeholder="Search subjects, resources..."
                    :disable-button="false"
                    @click:clear="searchQuery = ''"
                />
            </div>
        </div>
    </div>
</template>
