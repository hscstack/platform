<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>429 Too Many Requests</title>
        @vite(['resources/css/app.css'])
    </head>
    <body class="font-sans antialiased bg-background text-foreground flex items-center justify-center min-h-screen">
        <div class="relative flex min-h-[calc(100vh-10rem)] items-center justify-center px-4 py-16 sm:px-6 sm:py-24">
            <!-- Glowing aura behind the card -->
            <div class="absolute top-1/2 left-1/2 -z-10 h-72 w-72 -translate-x-1/2 -translate-y-1/2 animate-pulse rounded-full bg-indigo-400 opacity-15 blur-[100px] transition-all duration-700"></div>

            <!-- Glass card content -->
            <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200/50 bg-white/70 p-8 text-center shadow-xl backdrop-blur-md transition-all duration-300 hover:border-slate-300/40 hover:shadow-2xl sm:p-10 dark:border-gray-700/50 dark:bg-gray-900/70 dark:hover:border-gray-600/40">
                
                <!-- Decorative icon -->
                <div class="relative mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-2xl bg-slate-50 text-slate-800 shadow-inner dark:bg-gray-800 dark:text-gray-200">
                    <div class="absolute inset-0 animate-ping rounded-2xl bg-slate-100 opacity-60 dark:bg-gray-800"></div>
                    <div class="flex items-center justify-center text-slate-700 dark:text-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-12 w-12 text-indigo-600 dark:text-indigo-400"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    </div>
                </div>

                <!-- Gradient Error Code -->
                <h1 class="bg-gradient-to-r from-indigo-600 via-violet-600 to-pink-500 bg-clip-text text-8xl font-black tracking-tight text-transparent select-none sm:text-9xl">
                    429
                </h1>

                <h2 class="mt-4 text-2xl font-bold tracking-tight text-slate-900 dark:text-gray-100">
                    Nice Try Diddy
                </h2>

                <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-slate-500 dark:text-gray-400">
                    You’ve hit the rate limit. Take a little break and try again soon.
                </p>

                
            </div>
        </div>
    </body>
</html>
