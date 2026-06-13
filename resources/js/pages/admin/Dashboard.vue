<script setup>
import { LayoutGrid, Sparkles } from 'lucide-vue-next';
</script>

<template>
  <div class="space-y-6 max-w-7xl mx-auto p-1 animate-fade-in">
    
    <div class="relative overflow-hidden rounded-2xl border border-slate-300 bg-gradient-to-r from-white via-slate-50 to-white p-6 shadow-sm sm:p-8">
      <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>
      
      <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
          <div class="hidden h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-b from-white to-slate-100 text-indigo-600 sm:flex shrink-0 border border-slate-300 shadow-sm">
            <Sparkles class="h-5 w-5" />
          </div>
          <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Dashboard</h1>
            <p class="text-sm text-slate-600 mt-0.5">Welcome to your administration workspace. Real-time operational overviews populate here.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="relative overflow-hidden rounded-2xl border-2 border-dashed border-slate-400 bg-gradient-to-b from-slate-50 to-slate-100/70 p-12 text-center flex flex-col items-center justify-center min-h-[450px]">
      
      <div class="absolute inset-0 bg-[linear-gradient(to_right,#cbd5e1_1px,transparent_1px),linear-gradient(to_bottom,#cbd5e1_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_at_center,white,transparent_80%)] opacity-70 pointer-events-none"></div>
      
      <div class="relative space-y-4 max-w-sm">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm border border-slate-300">
          <LayoutGrid class="h-5 w-5 text-indigo-500" />
        </div>
        <div>
          <h3 class="text-base font-semibold text-slate-900">Analytics Workspace</h3>
          <p class="mt-1.5 text-sm text-slate-600 leading-relaxed font-medium">
            This is the main system dashboard. Application overview charts, database activities, and platform statistics will be shown here soon.
          </p>
        </div>
      </div>
    </div>

  </div>
</template>