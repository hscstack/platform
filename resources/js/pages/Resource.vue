<script setup>
import { 
    FileText, 
    Image as ImageIcon, 
    Download, 
    AlertCircle,
    ArrowLeft 
} from 'lucide-vue-next';

defineProps({
    resource: {
        type: Object,
        required: true,
    },
});

// Safe browser back trigger
const handleBack = () => {
    if (typeof window !== 'undefined') {
        window.history.back();
    }
};
</script>

<template>
    <div class="mx-auto max-w-4xl px-4 pt-8 pb-20 sm:px-6 sm:pt-12 min-h-[75vh] flex flex-col justify-start">
        
        <div class="mb-6">
            <button 
                @click="handleBack" 
                class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 transition-colors hover:text-indigo-600 group"
            >
                <ArrowLeft class="h-4 w-4 transition-transform duration-200 group-hover:-translate-x-0.5" />
                Back
            </button>
        </div>

        <div class="w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            
            <div class="border-b border-slate-100 bg-slate-50/50 p-5 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2 py-1 text-xs font-bold uppercase tracking-wider text-slate-600">
                            <FileText v-if="resource.resource_type === 'note'" class="h-3 w-3" />
                            <ImageIcon v-else-if="resource.resource_type === 'image'" class="h-3 w-3" />
                            <Download v-else class="h-3 w-3" />
                            {{ resource.resource_type }}
                        </span>
                        <h1 class="mt-2 text-xl font-black tracking-tight text-slate-950 sm:text-2xl">
                            {{ resource.title }}
                        </h1>
                    </div>
                </div>
            </div>

            <div v-if="resource.resource_type === 'note'" class="p-6 sm:p-8">
                <div class="prose max-w-none text-sm font-medium leading-relaxed text-slate-700 sm:text-base">
                    <p class="whitespace-pre-line selection:bg-indigo-100 selection:text-indigo-900">
                        {{ resource.content }}
                    </p>
                </div>
            </div>

            <div v-else-if="resource.resource_type === 'image'" class="bg-slate-950/5 p-4 sm:p-8 flex justify-center">
                <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-shadow duration-300 hover:shadow-md">
                    <img 
                        :src="resource.file_url" 
                        :alt="resource.title" 
                        class="max-h-[70vh] w-auto object-contain select-none"
                    />
                </div>
            </div>

            <div v-else class="p-6 sm:p-10 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 border border-amber-200">
                    <AlertCircle class="h-6 w-6 stroke-[2.2]" />
                </div>
                
                <h3 class="mt-4 text-base font-bold text-slate-900">
                    Unsupported Preview: <span class="capitalize text-indigo-600">{{ resource.resource_type }}</span>
                </h3>
                <p class="mx-auto mt-2 max-w-sm text-xs font-medium text-slate-500 sm:text-sm">
                    This file configuration isn't built for inline web reading. Grab a direct copy to view it on your system.
                </p>

                <div class="mt-6 flex justify-center">
                    <a 
                        v-if="resource.file_url"
                        :href="resource.file_url" 
                        download
                        target="_blank"
                        class="inline-flex touch-manipulation items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-indigo-700 active:scale-[0.98]"
                    >
                        <Download class="h-4 w-4 stroke-[2.5]" />
                        Download File Resource
                    </a>
                    <div 
                        v-else 
                        class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-400"
                    >
                        No download target generated for this asset.
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>