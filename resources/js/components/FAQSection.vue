<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    kBlockTitle,
    kBlock,
    kList,
    kAccordion,
    kAccordionItem,
} from 'konsta/vue';
import { ChevronDown, HelpCircle, Plus, Minus } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const openIndex = ref<number | null>(0);

const INITIAL_COUNT = 4;
const isExpanded = ref(false);

const faqs = [
    {
        question: 'HSCStack কী এবং এটি কাদের জন্য?',
        answer: 'HSCStack হলো বাংলাদেশের HSC ও SSC শিক্ষার্থীদের জন্য তৈরি একটি ওপেন রিসোর্স প্ল্যাটফর্ম। এখানে চ্যাপ্টার ও সাবজেক্ট অনুযায়ী প্রয়োজনীয় ক্লাস নোট, পিডিএফ, ছবি, ভিডিও এবং পরীক্ষার প্রশ্নপত্র একসাথে ফ্রি-তে খুঁজে পাওয়া যায়।',
        type: 'text',
    },
    {
        question: 'ওয়েবসাইট সম্পর্কিত নতুন আপডেট বা ঘোষণা কোথায় পাওয়া যাবে?',
        answer: 'প্ল্যাটফর্মের সাম্প্রতিক ফিচার, কনটেন্ট আপডেট এবং গুরুত্বপূর্ণ সকল ঘোষণা পেতে ফলো করুন আমাদের অফিশিয়াল ',
        linkText: 'Facebook পেজ',
        linkUrl: 'https://facebook.com/hscstackbd',
        answerAfter: '।',
        type: 'externalLink',
    },
    {
        question: 'ওয়েবসাইটটি কীভাবে অর্থায়ন করা হয়?',
        answer: 'এটি একটি অলাভজনক উদ্যোগ। আমাদের কনট্রিবিউটর এবং শিক্ষার্থীরাই মূলত আমাদের আর্থিক অনুদান দিয়ে থাকেন। আমাদের সাপোর্ট করতে ভিজিট করুন ',
        linkText: 'আমাদের সাপোর্ট পেজ',
        linkUrl: '/support',
        answerAfter: '।',
        type: 'link',
    },
    {
        question: 'এটি কে বা কারা পরিচালনা করে?',
        answer: 'এটি কোনো বাণিজ্যিক প্রতিষ্ঠান নয়; দেশজুড়ে ছড়িয়ে থাকা একদল উদ্যমী শিক্ষার্থী স্বেচ্ছাশ্রমে প্ল্যাটফর্মটি পরিচালনা ও কনটেন্ট কিউরেট করে থাকে। আমাদের টিম সম্পর্কে আরও জানতে পড়ুন ',
        linkText: 'আমাদের কথা',
        linkUrl: '/about-us',
        answerAfter: '।',
        type: 'link',
    },
    {
        question: 'HSCStack কি ওপেন সোর্স?',
        answer: 'হ্যাঁ! HSCStack সম্পূর্ণ ওপেন সোর্স একটি প্ল্যাটফর্ম। কোডবেসে কাজ করা, বাক ফিক্স করা কিংবা নতুন ফিচার যোগ করার জন্য আমাদের ',
        linkText: 'GitHub রিপোজিটরি',
        linkUrl: 'https://github.com/hscstack/platform',
        answerAfter: ' ভিজিট করতে পারো।',
        type: 'externalLink',
    },
    {
        question: 'রিসোর্স দেখার জন্য কি কোনো অ্যাকাউন্ট তৈরি করতে হবে?',
        answer: 'না, রিসোর্স খোঁজা, পড়া বা ডাউনলোড করার জন্য কোনো অ্যাকাউন্ট খোলার প্রয়োজন নেই। যেকোনো শিক্ষার্থী ফ্রিতে সরাসরি রিসোর্সগুলো ব্রাউজ ও ব্যবহার করতে পারবে।',
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
        question: 'এখানে কী কী ধরনের স্টাডি মেটেরিয়াল পাওয়া যাবে?',
        answer: 'এখানে অধ্যায়ভিত্তিক হ্যান্ডরিটেন নোটস, লেকচার শিট, সাজেস্টভ প্রশ্ন ব্যাঙ্ক, ডায়াগ্রাম এবং বিভিন্ন প্রয়োজনীয় টিউটোরিয়াল ও প্র্যাক্টিক্যাল গাইড পাওয়া যাবে।',
        type: 'text',
    },
    {
        question: 'ওয়েবসাইটটি ফোনে অ্যাপ হিসেবে ব্যবহার করা যাবে?',
        answer: 'হ্যাঁ, প্ল্যাটফর্মটি PWA (Progressive Web App) সাপোর্টেড। ব্রাউজারের "Add to Home Screen" বা ইনস্টল পপআপ থেকে এক ক্লিকেই অ্যাপের মতো ফোনে ইনস্টল করে নেওয়া যায়।',
        type: 'text',
    },
    {
        question:
            'ডেভেলপমেন্টে সাহায্য করতে বা কোড কন্ট্রিবিউট করতে চাইলে করণীয় কী?',
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

    if (
        !isExpanded.value &&
        openIndex.value !== null &&
        openIndex.value >= INITIAL_COUNT
    ) {
        openIndex.value = null;
    }
};
</script>

<template>
    <kBlock class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 sm:pb-20">
        <div class="mb-8 text-center sm:mb-10">
            <div
                class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 shadow-xs ring-1 ring-indigo-500/10"
            >
                <HelpCircle class="h-5 w-5" />
            </div>
            <kBlockTitle> সাধারণ জিজ্ঞাসা </kBlockTitle>
            <p
                class="mx-auto mt-2 max-w-md text-xs leading-relaxed font-medium text-slate-500 sm:text-sm"
            >
                HSCStack এবং রিসোর্স সম্পর্কিত আপনার বিভিন্ন প্রশ্নের উত্তর এক
                নজরে দেখে নিন।
            </p>
        </div>

        <div class="mx-auto max-w-2xl">
            <k-list strong outline dividers>
                <k-accordion v-for="faq in visibleFaqs" :key="faq.question">
                    <k-accordion-item>
                        <template #header>
                            <div
                                class="font-bold text-slate-900 dark:text-slate-100"
                            >
                                {{ faq.question }}
                            </div>
                        </template>
                        <template #content>
                            <div
                                class="px-4 pb-4 text-slate-600 dark:text-slate-300"
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
                        </template>
                    </k-accordion-item>
                </k-accordion>
            </k-list>

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
    </kBlock>
</template>
