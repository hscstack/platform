<template>
    
    <div
        class="min-h-screen bg-[#FAFAFB] font-sans text-slate-900 antialiased selection:bg-indigo-600 selection:text-white"
    >
        <div
            class="pointer-events-none fixed inset-0 -z-10 overflow-hidden opacity-70"
        >
            <div
                class="absolute -top-[10%] -left-[10%] h-[400px] w-[400px] rounded-full bg-indigo-100/60 blur-[100px]"
            ></div>
            <div
                class="absolute top-[30%] -right-[10%] h-[350px] w-[350px] rounded-full bg-blue-100/50 blur-[90px]"
            ></div>
        </div>

        <nav
            class="sticky top-0 z-50 border-b border-slate-200/60 bg-white/80 backdrop-blur-md"
        >
            <div
                class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6"
            >
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 shadow-sm"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="h-4 w-4 text-white"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"
                            />
                        </svg>
                    </div>
                    <span
                        class="text-lg font-extrabold tracking-tight text-slate-900"
                        >HSC<span class="text-indigo-600">Stack</span></span
                    >
                </div>
            </div>
        </nav>

        <header class="mx-auto max-w-3xl px-4 pt-12 pb-10 text-center sm:pt-16">
            <h1
                class="mb-4 text-4xl leading-tight font-black tracking-tight text-slate-950 sm:text-5xl"
            >
                Your knowledge, <br />
                <span class="text-indigo-600">perfectly organized.</span>
            </h1>
            <p
                class="mx-auto mb-8 max-w-md text-sm font-medium text-slate-500 sm:text-base"
            >
                Premium study materials, curated for HSC students. Fast, clean,
                and distraction-free.
            </p>

            <div class="relative mx-auto max-w-md">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search for a subject..."
                    class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-3.5 pr-4 pl-11 text-sm font-medium shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 focus:outline-none"
                />
                <Search
                    class="pointer-events-none absolute top-[15px] left-4 h-4 w-4 text-slate-400"
                />
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 pb-20 sm:px-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="subject in filteredSubjects"
                    :key="subject.name"
                    class="group relative flex touch-manipulation flex-col justify-between overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-200 hover:border-indigo-300 hover:shadow-md active:scale-[0.98] sm:active:scale-100"
                >
                    <div class="flex items-start gap-4 lg:block">
                        <div
                            :class="[
                                subject.color,
                                'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition-colors duration-200 lg:mb-5',
                            ]"
                        >
                            <component
                                :is="subject.icon"
                                class="h-5 w-5 stroke-[2.2]"
                            />
                        </div>

                        <div class="min-w-0">
                            <h3
                                class="truncate text-base font-bold text-slate-900 transition-colors group-hover:text-indigo-600"
                            >
                                {{ subject.name }}
                            </h3>
                            <p
                                class="mt-0.5 text-xs font-semibold text-slate-400"
                            >
                                {{ subject.chapters }} Chapters
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-5 flex items-center justify-between border-t border-slate-100 pt-3 lg:mt-6"
                    >
                        <span
                            class="text-xs font-bold text-slate-500 transition-colors group-hover:text-indigo-600"
                        >
                            View Materials
                        </span>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 text-slate-400 transition-transform duration-200 group-hover:translate-x-1 group-hover:text-indigo-600"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <div
                v-if="filteredSubjects.length === 0"
                class="rounded-xl border border-dashed border-slate-200 bg-white/50 py-12 text-center"
            >
                <p class="text-sm font-semibold text-slate-400">
                    No subjects found matching "{{ searchQuery }}"
                </p>
                <button
                    @click="searchQuery = ''"
                    class="mt-2 text-xs font-bold text-indigo-600 hover:underline"
                >
                    Show all subjects
                </button>
            </div>
        </main>

<footer class="mt-16 border-t border-slate-100 bg-white py-12 sm:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <!-- Main Footer Content Grid -->
        <div class="grid grid-cols-1 gap-y-10 md:grid-cols-12 md:gap-x-12">
            
            <!-- Brand Column -->
            <div class="flex flex-col items-center text-center md:col-span-5 md:items-start md:text-left">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 shadow-sm">
                        <svg viewBox="0 0 24 24" class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                        </svg>
                    </div>
                    <span class="text-lg font-black tracking-tight text-slate-900">
                        HSC<span class="text-indigo-600">Stack</span>
                    </span>
                </div>
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-slate-500">
                    Empowering the next generation of students with high-quality, direct, and accessible academic resources.
                </p>
                <!-- Concern attribution for desktop/tablet -->
                <p class="mt-4 hidden text-xs font-medium text-slate-400 md:block">
                    A concern of <a href="https://tajimz.xyz" target="_blank" rel="noopener noreferrer" class="text-slate-600 hover:text-indigo-600 transition-colors underline">Tajim</a>
                </p>
            </div>

            <!-- Links Columns -->
            <div class="grid grid-cols-2 gap-x-8 gap-y-8 border-t border-slate-100 pt-10 sm:gap-x-12 md:col-span-7 md:grid-cols-2 md:border-t-0 md:pt-0">
                
                <!-- Platform Group -->
                <div class="text-center md:text-left">
                    <h4 class="text-xs font-bold tracking-widest text-slate-900 uppercase">
                        Platform
                    </h4>
                    <ul class="mt-5 space-y-3.5">
                        <li>
                            <a href="#" class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600">
                                Browse Subjects
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600">
                                Contribute
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600">
                                Community
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Legal Group -->
                <div class="text-center md:text-left">
                    <h4 class="text-xs font-bold tracking-widest text-slate-900 uppercase">
                        Legal
                    </h4>
                    <ul class="mt-5 space-y-3.5">
                        <li>
                            <a href="#" class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600">
                                Privacy Policy
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600">
                                Terms of Service
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        
        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-slate-100 pt-8 sm:flex-row">

            <p class="text-center text-xs font-medium text-slate-400 md:hidden">
                A concern of <a href="https://tajimz.xyz" target="_blank" rel="noopener noreferrer" class="text-slate-600 hover:text-indigo-600 transition-colors underline">Tajim</a>
            </p>
            
            <p class="text-center text-xs font-medium text-slate-400 sm:text-left">
                &copy; 2026 HSCStack. Built for the future of learning.
            </p>
        </div>
    </div>
</footer>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import {
    Search,
    Atom,
    FlaskConical,
    Dna,
    Sigma,
    Laptop,
    BookOpen,
    PenTool,
    BarChart3,
} from 'lucide-vue-next';

const searchQuery = ref('');

const subjects = [
    {
        name: 'Physics',
        chapters: 12,
        icon: Atom,
        color: 'bg-blue-50 text-blue-600',
    },
    {
        name: 'Chemistry',
        chapters: 10,
        icon: FlaskConical,
        color: 'bg-emerald-50 text-emerald-600',
    },
    {
        name: 'Biology',
        chapters: 8,
        icon: Dna,
        color: 'bg-rose-50 text-rose-600',
    },
    {
        name: 'Mathematics',
        chapters: 14,
        icon: Sigma,
        color: 'bg-indigo-50 text-indigo-600',
    },
    {
        name: 'ICT',
        chapters: 6,
        icon: Laptop,
        color: 'bg-amber-50 text-amber-600',
    },
    {
        name: 'Bangla',
        chapters: 5,
        icon: BookOpen,
        color: 'bg-violet-50 text-violet-600',
    },
    {
        name: 'English',
        chapters: 9,
        icon: PenTool,
        color: 'bg-orange-50 text-orange-600',
    },
    {
        name: 'Accounting',
        chapters: 7,
        icon: BarChart3,
        color: 'bg-cyan-50 text-cyan-600',
    },
];

const filteredSubjects = computed(() => {
    return subjects.filter((subject) =>
        subject.name.toLowerCase().includes(searchQuery.value.toLowerCase()),
    );
});
</script>
