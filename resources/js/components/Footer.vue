<script setup lang="ts">
import AppLogo from './AppLogo.vue';
</script>

<template>
    <footer class="mt-16 border-t border-slate-100 bg-white py-12 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="grid grid-cols-1 gap-y-10 md:grid-cols-12 md:gap-x-12">
                <div
                    class="flex flex-col items-center text-center md:col-span-5 md:items-start md:text-left"
                >
                    <AppLogo />
                    <p
                        class="mt-4 max-w-sm text-sm leading-relaxed text-slate-500"
                    >
                        Empowering the next generation of students with high
                        quality, direct, and accessible academic resources.
                    </p>

                    <a
                        href="https://github.com/hsc-stack"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-4 text-slate-400 transition-colors duration-150 hover:text-slate-900"
                        aria-label="GitHub Repository"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </a>

                    <p
                        class="mt-4 hidden text-xs font-medium text-slate-400 md:block"
                    >
                        A concern of
                        <a
                            href="https://tajimz.xyz"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-slate-600 underline transition-colors hover:text-indigo-600"
                            >Tajim</a
                        >
                    </p>
                </div>

                <div
                    class="grid grid-cols-2 gap-x-8 gap-y-8 border-t border-slate-100 pt-10 sm:gap-x-12 md:col-span-7 md:grid-cols-2 md:border-t-0 md:pt-0"
                >
                    <div class="text-center md:text-left">
                        <h4
                            class="text-xs font-bold tracking-widest text-slate-900 uppercase"
                        >
                            Platform
                        </h4>
                        <ul class="mt-5 space-y-3.5">
                            <li>
                                <a
                                    href="https://tajimz.xyz/projects"
                                    class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600"
                                >
                                    More From Us
                                </a>
                            </li>
                            <li>
                                <a
                                    href="/join"
                                    target="_blank"
                                    class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600"
                                >
                                    Join Our Team
                                </a>
                            </li>
                            <li>
                                <a
                                    href="/about-us"
                                    target="_blank"
                                    class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600"
                                >
                                    About us
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="text-center md:text-left">
                        <h4
                            class="text-xs font-bold tracking-widest text-slate-900 uppercase"
                        >
                            Legal
                        </h4>
                        <ul class="mt-5 space-y-3.5">
                            <li>
                                <a
                                    href="/privacy-policy"
                                    target="_blank"
                                    class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600"
                                >
                                    Privacy Policy
                                </a>
                            </li>
                            <li>
                                <a
                                    href="/terms-service"
                                    target="_blank"
                                    class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600"
                                >
                                    Terms of Service
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div
                class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-slate-100 pt-8 sm:flex-row"
            >
                <p
                    class="text-center text-xs font-medium text-slate-400 md:hidden"
                >
                    A concern of
                    <a
                        href="https://tajimz.xyz"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-slate-600 underline transition-colors hover:text-indigo-600"
                        >Tajim</a
                    >
                </p>

                <p
                    class="text-center text-xs font-medium text-slate-400 sm:text-left"
                >
                    &copy; 2026 HSCStack. Built for the future of learning.
                </p>
            </div>
        </div>
    </footer>
</template>
