<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown, HelpCircle, Plus, Minus } from 'lucide-vue-next';
import { ref, computed } from 'vue';

// Single active accordion index
const openIndex = ref(0);

// Two-step expand toggle
const INITIAL_COUNT = 4;
const isExpanded = ref(false);

const faqs = [
    {
        question: 'HSCStack কী এবং এটি কাদের জন্য?',
        answer: 'HSCStack হলো বাংলাদেশের HSC ও SSC শিক্ষার্থীদের জন্য তৈরি একটি ওপেন রিসোর্স প্ল্যাটফর্ম। এখানে চ্যাপ্টার ও সাবজেক্ট অনুযায়ী প্রয়োজনীয় ক্লাস নোট, পিডিএফ, ছবি, ভিডিও এবং পরীক্ষার প্রশ্নপত্র একসাথে ফ্রি-তে খুঁজে পাওয়া যায়।',
        type: 'text',
    },
    {
        question: 'ওয়েবসাইট সম্পর্কিত নতুন আপডেট বা ঘোষণা কোথায় পাওয়া যাবে?',
        answer: 'প্ল্যাটফর্মের সাম্প্রতিক ফিচার, কনটেন্ট আপডেট এবং গুরুত্বপূর্ণ সকল ঘোষণা পেতে ফলো করুন আমাদের অফিশিয়াল ',
        linkText: 'Facebook পেজ',
        linkUrl: 'https://facebook.com/hscstackbd',
        answerAfter: '।',
        type: 'externalLink',
    },
    {
        question: 'এটি কে বা কারা পরিচালনা করে?',
        answer: 'এটি কোনো বাণিজ্যিক প্রতিষ্ঠান নয়; দেশজুড়ে ছড়িয়ে থাকা একদল উদ্যমী শিক্ষার্থী স্বেচ্ছাশ্রমে প্ল্যাটফর্মটি পরিচালনা ও কনটেন্ট কিউরেট করে থাকে। আমাদের টিম সম্পর্কে আরও জানতে পড়ুন ',
        linkText: 'আমাদের কথা',
        linkUrl: '/about-us',
        answerAfter: '।',
        type: 'link',
    },
    {
        question: 'HSCStack কি ওপেন সোর্স?',
        answer: 'হ্যাঁ! HSCStack সম্পূর্ণ ওপেন সোর্স একটি প্ল্যাটফর্ম। কোডবেসে কাজ করা, বাক ফিক্স করা কিংবা নতুন ফিচার যোগ করার জন্য আমাদের ',
        linkText: 'GitHub রিপোজিটরি',
        linkUrl: 'https://github.com/trtajim/platform',
        answerAfter: ' ভিজিট করতে পারো।',
        type: 'externalLink',
    },
    {
        question: 'রিসোর্স দেখার জন্য কি কোনো অ্যাকাউন্ট তৈরি করতে হবে?',
        answer: 'না, রিসোর্স খোঁজা, পড়া বা ডাউনলোড করার জন্য কোনো অ্যাকাউন্ট খোলার প্রয়োজন নেই। যেকোনো শিক্ষার্থী ফ্রিতে সরাসরি রিসোর্সগুলো ব্রাউজ ও ব্যবহার করতে পারবে।',
        type: 'text',
    },
    {
        question: 'আমি কি আমার তৈরি নোট বা রিসোর্স আপলোড করতে পারব?',
        answer: 'কনটেন্টের মান এবং নির্ভুলতা নিশ্চিত করতে রিসোর্স আপলোডের সুবিধাটি শুধুমাত্র ভেরিফাইড মেম্বারদের জন্য নির্ধারিত। মেম্বারশিপের আবেদন করতে ',
        linkText: 'এখানে ক্লিক করুন',
        linkUrl: '/join',
        answerAfter: '।',
        type: 'link',
    },
    {
        question: 'এখানে কী কী ধরনের স্টাডি মেটেরিয়াল পাওয়া যাবে?',
        answer: 'এখানে অধ্যায়ভিত্তিক হ্যান্ডরিটেন নোটস, লেকচার শিট, সাজেস্টভ প্রশ্ন ব্যাঙ্ক, ডায়াগ্রাম এবং বিভিন্ন প্রয়োজনীয় টিউটোরিয়াল ও প্র্যাক্টিক্যাল গাইড পাওয়া যাবে।',
        type: 'text',
    },
    {
        question: 'ওয়েবসাইটটি ফোনে অ্যাপ হিসেবে ব্যবহার করা যাবে?',
        answer: 'হ্যাঁ, প্ল্যাটফর্মটি PWA (Progressive Web App) সাপোর্টেড। ব্রাউজারের "Add to Home Screen" বা ইনস্টল পপআপ থেকে এক ক্লিকেই অ্যাপের মতো ফোনে ইনস্টল করে নেওয়া যায়।',
        type: 'text',
    },
    {
        question:
            'ডেভেলপমেন্টে সাহায্য করতে বা কোড কন্ট্রিবিউট করতে চাইলে করণীয় কী?',
        answer: 'HSCStack-এর ওপেন ডেভেলপমেন্ট টিমে যোগ দিতে আমাদের ',
        linkText: 'আবেদন ফর্মে',
        linkUrl: '/join',
        answerAfter:
            ' অ্যাপ্লাই করতে পারো। আবেদন গৃহীত হলে কোর ডেভেলপার হিসেবে কোডবেসে কাজ করার সুযোগ মিলবে।',
        type: 'link',
    },
];

const visibleFaqs = computed(() => {
    return isExpanded.value ? faqs : faqs.slice(0, INITIAL_COUNT);
});

const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;

    if (!isExpanded.value && openIndex.value >= INITIAL_COUNT) {
        openIndex.value = null;
    }
};

const toggleFaq = (index) => {
    openIndex.value = openIndex.value === index ? null : index;
};

const isOpen = (index) => openIndex.value === index;
</script>

<template>
    <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 sm:pb-20">
        <!-- Section Header -->
        <div class="mb-8 text-center sm:mb-10">
            <div
                class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 shadow-xs ring-1 ring-indigo-500/10"
            >
                <HelpCircle class="h-5 w-5" />
            </div>
            <h2
                class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl lg:text-4xl"
            >
                সাধারণ জিজ্ঞাসা
            </h2>
            <p
                class="mx-auto mt-2 max-w-md text-xs leading-relaxed font-medium text-slate-500 sm:text-sm"
            >
                HSCStack এবং রিসোর্স সম্পর্কিত আপনার বিভিন্ন প্রশ্নের উত্তর এক
                নজরে দেখে নিন।
            </p>
        </div>

        <!-- Accordion Cards Container -->
        <div class="mx-auto max-w-2xl space-y-3">
            <TransitionGroup
                enter-active-class="transition-all duration-500 cubic-bezier(0.16, 1, 0.3, 1)"
                enter-from-class="opacity-0 translate-y-4 scale-95"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition-all duration-300 cubic-bezier(0.7, 0, 0.84, 0)"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 translate-y-2 scale-95"
            >
                <div
                    v-for="(faq, index) in visibleFaqs"
                    :key="faq.question"
                    class="group overflow-hidden rounded-2xl border transition-all duration-300"
                    :class="[
                        isOpen(index)
                            ? 'border-indigo-200 bg-white shadow-lg ring-1 shadow-indigo-500/5 ring-indigo-500/10'
                            : 'border-slate-200/80 bg-white/80 hover:border-slate-300 hover:bg-white',
                    ]"
                >
                    <!-- Header / Trigger Button -->
                    <button
                        type="button"
                        @click="toggleFaq(index)"
                        class="flex w-full items-center justify-between gap-3 p-4 text-left focus:outline-none sm:p-5"
                    >
                        <span
                            class="text-xs leading-snug font-bold transition-colors duration-200 sm:text-sm lg:text-base"
                            :class="
                                isOpen(index)
                                    ? 'text-indigo-600'
                                    : 'text-slate-800 group-hover:text-slate-900'
                            "
                        >
                            {{ faq.question }}
                        </span>

                        <div
                            class="cubic-bezier(0.34, 1.56, 0.64, 1) flex h-7 w-7 shrink-0 items-center justify-center rounded-xl transition-all duration-500 sm:h-8 sm:w-8"
                            :class="[
                                isOpen(index)
                                    ? 'rotate-180 bg-indigo-600 text-white shadow-xs'
                                    : 'bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-slate-600',
                            ]"
                        >
                            <ChevronDown class="h-4 w-4 stroke-[2.5]" />
                        </div>
                    </button>

                    <!-- Ultra-Smooth Accordion Body (CSS Grid Rows Trick) -->
                    <div
                        class="cubic-bezier(0.16, 1, 0.3, 1) grid transition-[grid-template-rows,opacity] duration-500"
                        :class="[
                            isOpen(index)
                                ? 'grid-rows-[1fr] opacity-100'
                                : 'grid-rows-[0fr] opacity-0',
                        ]"
                    >
                        <div class="overflow-hidden">
                            <div
                                class="cubic-bezier(0.16, 1, 0.3, 1) border-t border-slate-100 px-4 pt-3 pb-4 text-xs leading-relaxed font-medium text-slate-600 transition-transform duration-500 sm:px-5 sm:pb-5 sm:text-sm"
                                :class="[
                                    isOpen(index)
                                        ? 'translate-y-0'
                                        : '-translate-y-2',
                                ]"
                            >
                                <!-- Internal Link -->
                                <template v-if="faq.type === 'link'">
                                    {{ faq.answer }}
                                    <Link
                                        :href="faq.linkUrl"
                                        class="font-bold text-indigo-600 hover:underline"
                                    >
                                        {{ faq.linkText }}
                                    </Link>
                                    {{ faq.answerAfter }}
                                </template>

                                <!-- External Link -->
                                <template
                                    v-else-if="faq.type === 'externalLink'"
                                >
                                    {{ faq.answer }}
                                    <a
                                        :href="faq.linkUrl"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-0.5 font-bold text-indigo-600 hover:underline"
                                    >
                                        {{ faq.linkText }}
                                    </a>
                                    {{ faq.answerAfter }}
                                </template>

                                <!-- Plain Text -->
                                <template v-else>
                                    {{ faq.answer }}
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </TransitionGroup>

            <!-- Toggle Expand / Collapse Button -->
            <div v-if="faqs.length > INITIAL_COUNT" class="pt-4 text-center">
                <button
                    type="button"
                    @click="toggleExpand"
                    class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-xs font-bold text-slate-700 shadow-xs transition-all duration-300 hover:border-indigo-300 hover:bg-slate-50 hover:text-indigo-600 hover:shadow-md hover:shadow-indigo-500/5 focus:outline-none active:scale-95 sm:text-sm"
                >
                    <template v-if="!isExpanded">
                        <Plus class="h-4 w-4 stroke-[2.5]" />
                        <span
                            >আরও প্রশ্ন দেখুন ({{
                                faqs.length - INITIAL_COUNT
                            }})</span
                        >
                    </template>
                    <template v-else>
                        <Minus class="h-4 w-4 stroke-[2.5]" />
                        <span>কম প্রশ্ন দেখুন</span>
                    </template>
                </button>
            </div>
        </div>
    </section>
</template>
