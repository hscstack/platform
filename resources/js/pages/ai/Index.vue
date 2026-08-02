<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Sparkles,
    Bot,
    HeartHandshake,
    Send,
    Zap,
    ArrowRight,
    Lock,
    ChevronRight,
    CheckCircle2,
    Layers,
    MessageSquareText,
    Atom,
    FlaskConical,
    Sigma,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

// Mock Data for Pure STEM Subjects (Emojis removed, Lucide Icons added)
const mockSubjects = [
    {
        id: 'physics',
        name: 'Physics',
        icon: Atom,
        topics: [
            'Vector',
            'Dynamics',
            'Work, Energy & Power',
            'Gravitation',
            'Thermodynamics',
        ],
    },
    {
        id: 'chemistry',
        name: 'Chemistry',
        icon: FlaskConical,
        topics: [
            'Qualitative Chemistry',
            'Periodic Properties',
            'Chemical Change',
            'Electrochemistry',
        ],
    },
    {
        id: 'math',
        name: 'Higher Math',
        icon: Sigma,
        topics: [
            'Matrices & Determinants',
            'Straight Lines',
            'Circles',
            'Calculus (Differentiation)',
        ],
    },
];

// Interactive Flow State
const selectedSubject = ref(mockSubjects[0]); // Default to Physics
const selectedTopic = ref('Vector'); // Default to Vector
const userPrompt = ref('');

const sampleQuestions: Record<string, string[]> = {
    Vector: [
        'ভেক্টরের ডট গুণন ও ক্রস গুণনের মধ্যে মূল পার্থক্য কী?',
        'সামান্তরিকের সূত্রের সাহায্যে কীভাবে লব্ধির মান ও দিক নির্ণয় করা যায়?',
        'নদী ও নৌকার ক্ষেত্রে সর্বনিম্ন সময়ে নদী পার হওয়ার শর্ত ব্যাখ্যা করো।',
    ],
    'Qualitative Chemistry': [
        'কোয়ান্টাম সংখ্যা কীভাবে ইলেকট্রনের অবস্থান নির্দেশ করে?',
        'আউফবাউ নীতি ও হুন্ডের নীতির মধ্যে পার্থক্য কী?',
    ],
    'Calculus (Differentiation)': [
        'মূল নিয়মে $\sin(x)$ এর অন্তরীজ নির্ণয় করার ধাপগুলো কী কী?',
        'পরম মান এবং গুরুমান-লঘুমান নির্ণয়ের সহজ উপায় বুঝিয়ে বলো।',
    ],
};

const currentSamples = computed(() => {
    return (
        sampleQuestions[selectedTopic.value] || [
            `${selectedSubject.value.name} এর ${selectedTopic.value} টপিক থেকে গুরুত্বপূর্ণ বোর্ড প্রশ্নগুলো দেখাও।`,
            `${selectedTopic.value} সহজে মনে রাখার কোনো শর্টকাট কৌশল আছে?`,
        ]
    );
});

const handleTopicSelect = (topic: string) => {
    selectedTopic.value = topic;
    userPrompt.value = '';
};

const handleSubjectSelect = (subject: (typeof mockSubjects)[0]) => {
    selectedSubject.value = subject;
    selectedTopic.value = subject.topics[0];
    userPrompt.value = '';
};

const handleMockSubmit = (question?: string) => {
    if (question) {
        userPrompt.value = question;
    }
};
</script>

<template>
    <Head title="HSCStack AI - Coming Soon" />

    <main class="mx-auto max-w-7xl px-4 pt-10 pb-20 sm:px-6">


        <!-- Hero Header -->
        <div class="mx-auto max-w-3xl text-center">
            <!-- Launching Badge -->
            <div
                class="mb-4 inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50/80 px-3.5 py-1.5 text-xs font-bold text-indigo-700 shadow-xs backdrop-blur-md"
            >
                <Sparkles class="h-3.5 w-3.5 animate-pulse text-indigo-600" />
                <span>HSCStack AI • Coming Soon</span>
            </div>

            <h1
                class="mb-4 text-3xl font-black tracking-tight text-slate-900 sm:text-5xl lg:leading-[1.15]"
            >
                তোমার বিষয় ও টপিক অনুযায়ী <br />
                <span
                    class="bg-gradient-to-r from-indigo-600 via-violet-600 to-fuchsia-600 bg-clip-text text-transparent"
                >
                    স্মার্ট AI লার্নিং অ্যাসিস্ট্যান্ট।
                </span>
            </h1>

            <p
                class="mx-auto mb-8 max-w-xl text-sm leading-relaxed font-medium text-slate-600 sm:text-base"
            >
                পছন্দের বিষয় ও অধ্যায় সিলেক্ট করে সরাসরি প্রশ্ন করুন। NCTB
                কারিকুলাম অনুযায়ী প্রতিটি জটিল কনসেপ্ট সহজ ভাষায় বুঝিয়ে দেবে
                HSCStack AI।
            </p>
        </div>

        <!-- Funding Banner / Callout -->
        <div
            class="relative mx-auto mb-10 max-w-3xl overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50/90 via-white to-violet-50/50 p-5 shadow-xs sm:p-6"
        >
            <div
                class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-3.5">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-500/20"
                    >
                        <HeartHandshake class="h-5.5 w-5.5" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-bold text-slate-900 sm:text-base"
                        >
                            এই সার্ভিসটি চালু করতে আমাদের ফান্ডিং প্রয়োজন!
                        </h3>
                        <p
                            class="mt-0.5 text-xs leading-relaxed font-medium text-slate-600"
                        >
                            HSCStack একটি অলাভজনক উদ্যোগ। AI সার্ভিস সম্পূর্ণ
                            ফ্রিতে দিতে এবং সার্ভার ও API কস্ট বহন করতে আমাদের
                            ফান্ডিং প্রয়োজন।
                        </p>
                    </div>
                </div>

                <Link
                    href="/support"
                    class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-500/20 transition-all duration-200 hover:bg-indigo-700 hover:shadow-lg active:scale-95"
                >
                    <span>ফান্ডিং এ সাহায্য করুন</span>
                    <ArrowRight class="h-3.5 w-3.5" />
                </Link>
            </div>
        </div>

        <!-- AI Mockup Flow Container -->
        <div
            class="mx-auto max-w-3xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/50"
        >
            <!-- Top Bar / Breadcrumb Flow -->
            <div
                class="border-b border-slate-100 bg-slate-50/80 px-5 py-3.5 backdrop-blur-md"
            >
                <div
                    class="flex flex-wrap items-center gap-2 text-xs font-bold text-slate-500"
                >
                    <span class="flex items-center gap-1.5 text-slate-700">
                        <component
                            :is="selectedSubject.icon"
                            class="h-4 w-4 stroke-[2.2] text-indigo-600"
                        />
                        {{ selectedSubject.name }}
                    </span>
                    <ChevronRight class="h-3.5 w-3.5 text-slate-300" />
                    <span class="flex items-center gap-1 text-indigo-600">
                        <Layers class="h-3.5 w-3.5" />
                        {{ selectedTopic }}
                    </span>
                    <span
                        class="ml-auto inline-flex items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] text-amber-700"
                    >
                        <Lock class="h-3 w-3" /> Coming Soon
                    </span>
                </div>
            </div>

            <div class="space-y-6 p-5 sm:p-6">
                <!-- STEP 1: Select Subject -->
                <div>
                    <label
                        class="mb-2.5 flex items-center gap-1.5 text-xs font-extrabold tracking-wider text-slate-400 uppercase"
                    >
                        <span
                            class="flex h-4 w-4 items-center justify-center rounded-full bg-indigo-100 text-[10px] text-indigo-600"
                            >1</span
                        >
                        বিষয় সিলেক্ট করুন:
                    </label>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <button
                            v-for="sub in mockSubjects"
                            :key="sub.id"
                            @click="handleSubjectSelect(sub)"
                            type="button"
                            :class="[
                                'inline-flex items-center justify-between rounded-xl border px-3.5 py-2.5 text-xs font-bold transition-all duration-200 active:scale-95',
                                selectedSubject.id === sub.id
                                    ? 'border-indigo-600 bg-indigo-50/80 text-indigo-700 shadow-xs ring-2 ring-indigo-600/10'
                                    : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50',
                            ]"
                        >
                            <div class="flex items-center gap-2.5">
                                <div
                                    :class="[
                                        'flex h-7 w-7 items-center justify-center rounded-lg transition-colors',
                                        selectedSubject.id === sub.id
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-slate-100 text-slate-500',
                                    ]"
                                >
                                    <component
                                        :is="sub.icon"
                                        class="h-4 w-4 stroke-[2.2]"
                                    />
                                </div>
                                <span>{{ sub.name }}</span>
                            </div>
                            <CheckCircle2
                                v-if="selectedSubject.id === sub.id"
                                class="h-4 w-4 text-indigo-600"
                            />
                        </button>
                    </div>
                </div>

                <!-- STEP 2: Select Topic / Chapter -->
                <div>
                    <label
                        class="mb-2.5 flex items-center gap-1.5 text-xs font-extrabold tracking-wider text-slate-400 uppercase"
                    >
                        <span
                            class="flex h-4 w-4 items-center justify-center rounded-full bg-indigo-100 text-[10px] text-indigo-600"
                            >2</span
                        >
                        টপিক / অধ্যায় পছন্দ করুন:
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="topic in selectedSubject.topics"
                            :key="topic"
                            @click="handleTopicSelect(topic)"
                            type="button"
                            :class="[
                                'rounded-xl border px-3 py-1.5 text-xs font-semibold transition-all duration-200 active:scale-95',
                                selectedTopic === topic
                                    ? 'border-indigo-500 bg-indigo-600 text-white shadow-xs'
                                    : 'border-slate-200 bg-slate-50/80 text-slate-600 hover:border-slate-300 hover:bg-white',
                            ]"
                        >
                            {{ topic }}
                        </button>
                    </div>
                </div>

                <!-- Chat Box Screen Simulation -->
                <div
                    class="space-y-4 rounded-xl border border-slate-200/80 bg-slate-50/50 p-4"
                >
                    <!-- AI Greeting Message -->
                    <div class="flex gap-3">
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 text-white shadow-xs"
                        >
                            <Bot class="h-4 w-4" />
                        </div>
                        <div
                            class="max-w-xl rounded-2xl rounded-tl-xs border border-slate-200/60 bg-white p-3.5 text-xs leading-relaxed font-medium text-slate-700 shadow-xs sm:text-sm"
                        >
                            তুমি
                            <strong>{{ selectedSubject.name }}</strong> বিষয়ের
                            <strong>{{ selectedTopic }}</strong> টপিক সিলেক্ট
                            করেছ। এই টপিকের যেকোনো গাণিতিক সমস্যা, সংজ্ঞা বা
                            বোর্ড প্রশ্ন নিয়ে আমাকে জিজ্ঞেস করতে পারো!
                        </div>
                    </div>

                    <!-- Suggested Prompts for this topic -->
                    <div>
                        <p
                            class="mb-2 flex items-center gap-1 text-[11px] font-bold tracking-wider text-slate-400 uppercase"
                        >
                            <MessageSquareText class="h-3 w-3" />
                            নমুনা প্রশ্নসমূহ (Sample Queries):
                        </p>
                        <div class="space-y-1.5">
                            <button
                                v-for="(q, idx) in currentSamples"
                                :key="idx"
                                @click="handleMockSubmit(q)"
                                type="button"
                                class="group flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white p-2.5 text-left transition-all duration-200 hover:border-indigo-300 hover:shadow-xs active:scale-[0.99]"
                            >
                                <span
                                    class="text-xs font-semibold text-slate-700 group-hover:text-indigo-600"
                                >
                                    "{{ q }}"
                                </span>
                                <Zap
                                    class="h-3.5 w-3.5 shrink-0 text-slate-300 group-hover:text-indigo-500"
                                />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lock Overlay Footer -->
            <div class="relative border-t border-slate-100 bg-slate-50/80 p-4">
                <!-- Disabled Lock Screen Overlay -->
                <div
                    class="absolute inset-0 z-10 flex items-center justify-center bg-white/85 backdrop-blur-[2px]"
                >
                    <div
                        class="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 shadow-xs"
                    >
                        <Lock class="h-3.5 w-3.5 text-amber-500" />
                        <span
                            >AI চালু করতে সাহায্য করুন 👉
                            <Link
                                href="/support"
                                class="text-indigo-600 hover:underline"
                                >Support Page</Link
                            ></span
                        >
                    </div>
                </div>

                <form
                    @submit.prevent
                    class="flex items-center gap-2 opacity-40"
                >
                    <input
                        v-model="userPrompt"
                        type="text"
                        disabled
                        :placeholder="`${selectedTopic} নিয়ে প্রশ্নটি লিখুন...`"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs font-semibold text-slate-700"
                    />
                    <button
                        type="button"
                        disabled
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white"
                    >
                        <Send class="h-4 w-4" />
                    </button>
                </form>
            </div>
        </div>
    </main>
</template>
