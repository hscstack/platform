<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { 
  Atom, 
  FlaskConical, 
  Dna, 
  Sigma, 
  Laptop, 
  BookOpen, 
  PenTool, 
  BarChart3, 
  Search,
  ArrowLeft,
  Check
} from 'lucide-vue-next';

// Mapping available icons
const icons = {
  Atom,
  FlaskConical,
  Dna,
  Sigma,
  Laptop,
  BookOpen,
  PenTool,
  BarChart3,
  Search,
};

// Inertia Form Helper
const form = useForm({
  name: '',
  tailwind_format: 'bg-red-50 text-red-600', // Defaulting to the first preset choice
  icon: 'BookOpen', 
  sort_order: 0,
});

// Fixed preset styling pairings from your requirements
const tailwindPresets = [
  'bg-red-50 text-red-600',
  'bg-red-100 text-red-700',
  'bg-blue-50 text-blue-600',
  'bg-blue-100 text-blue-700',
  'bg-purple-50 text-purple-600',
  'bg-purple-100 text-purple-700',
  'bg-emerald-50 text-emerald-600',
  'bg-emerald-100 text-emerald-700',
  'bg-amber-50 text-amber-600',
  'bg-amber-100 text-amber-700',
  'bg-green-50 text-green-600',
  'bg-green-100 text-green-700',
];

// Pure layout styling helpers
function getPresetClass(preset) {
  if (form.tailwind_format === preset) {
    return preset + ' border-slate-900 scale-110 shadow-md';
  }
  return preset + ' border-transparent hover:scale-105';
}

// Fixed border colors for the unselected soft background circles to look clean
function getPresetBorder(preset) {
  if (form.tailwind_format === preset) return '';
  if (preset.includes('100')) return 'border-slate-200';
  return 'border-slate-150';
}

function getIconClass(iconKey) {
  if (form.icon === iconKey) {
    return 'border-blue-600 bg-blue-50 text-blue-600 ring-2 ring-blue-600/10';
  }
  return 'border-slate-200 text-slate-600 hover:bg-slate-50';
}

function getNameInputClass() {
  if (form.errors.name) {
    return 'border-rose-500 focus:ring-rose-500/20';
  }
  return 'border-slate-300 focus:ring-blue-500/20 focus:border-blue-500';
}

function getSortInputClass() {
  if (form.errors.sort_order) {
    return 'border-rose-500 focus:ring-rose-500/20';
  }
  return 'border-slate-300 focus:ring-blue-500/20 focus:border-blue-500';
}

const activeIconComponent = computed(() => {
  return icons[form.icon] || BookOpen;
});

// Submit handled via Inertia
const submitForm = () => {
  form.post('/admin/subjects', {
    preserveScroll: true,
  });
};
</script>

<template>
  <!-- Full space container wrapper for integration into parent admin shell layouts -->
  <div class="w-full min-h-full bg-slate-50 p-6 lg:p-10 flex flex-col justify-start">
    <div class="w-full bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 md:p-10">
      
      <!-- Header Area -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 pb-6 border-b border-slate-100">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Create New Subject</h1>
          <p class="text-sm text-slate-500 mt-1">Add a new subject category to the platform management system.</p>
        </div>
        <Link 
          href="/admin/subjects" 
          class="inline-flex items-center self-start sm:self-center px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition"
        >
          <ArrowLeft class="w-4 h-4 mr-2" /> Back to List
        </Link>
      </div>

      <!-- Form Main Box -->
      <form @submit.prevent="submitForm" class="space-y-8">
        
        <!-- Field Row: Name and Sort Order Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="md:col-span-2">
            <label for="name" class="block text-sm font-semibold text-slate-700 mb-1.5">Subject Name</label>
            <input 
              v-model="form.name"
              type="text" 
              id="name" 
              placeholder="e.g. Physics..."
              class="w-full px-4 py-2.5 rounded-lg border transition outline-none"
              :class="getNameInputClass()"
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-rose-600">{{ form.errors.name }}</p>
          </div>

          <div>
            <label for="sort_order" class="block text-sm font-semibold text-slate-700 mb-1.5">Sort Order</label>
            <input 
              v-model.number="form.sort_order"
              type="number" 
              id="sort_order" 
              placeholder="0"
              class="w-full px-4 py-2.5 rounded-lg border transition outline-none"
              :class="getSortInputClass()"
            />
            <p v-if="form.errors.sort_order" class="mt-1 text-sm text-rose-600">{{ form.errors.sort_order }}</p>
          </div>
        </div>

        <!-- Visual Theme Selection (Restored Circle Button Style) -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-3">Visual Theme Style</label>
          <div class="flex flex-wrap gap-3">
            <button 
              type="button"
              v-for="preset in tailwindPresets" 
              :key="preset"
              @click="form.tailwind_format = preset"
              class="w-10 h-10 rounded-full flex items-center justify-center border-2 transition focus:outline-none"
              :class="[getPresetClass(preset), getPresetBorder(preset)]"
            >
              <Check v-if="form.tailwind_format === preset" class="w-4 h-4" />
            </button>
          </div>
          <p v-if="form.errors.tailwind_format" class="mt-1 text-sm text-rose-600">{{ form.errors.tailwind_format }}</p>
        </div>

        <!-- Icon Grid Selection Grid Layout -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-2.5">Subject Icon</label>
          <div class="grid grid-cols-3 gap-3 sm:grid-cols-5 md:grid-cols-9">
            <button 
              type="button"
              v-for="(iconComponent, iconKey) in icons" 
              :key="iconKey"
              @click="form.icon = iconKey"
              class="p-3.5 rounded-xl border flex flex-col items-center justify-center gap-1.5 transition-all"
              :class="getIconClass(iconKey)"
            >
              <component :is="iconComponent" class="w-5 h-5 shrink-0" />
              <span class="text-[11px] font-medium tracking-tight truncate max-w-full">{{ iconKey }}</span>
            </button>
          </div>
          <p v-if="form.errors.icon" class="mt-1 text-sm text-rose-600">{{ form.errors.icon }}</p>
        </div>

        <hr class="border-slate-100 my-4" />

        <!-- Live Layout Preview Widget Dashboard Segment -->
        <div class="bg-slate-50 rounded-xl p-5 border border-slate-200/60">
          <span class="text-xs uppercase font-bold tracking-wider text-slate-400 block mb-3.5">Live Dashboard Card Preview</span>
          <div class="flex items-center space-x-4 p-4 bg-white rounded-lg border border-slate-100 shadow-sm max-w-sm">
            <div :class="form.tailwind_format" class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-all duration-300">
              <component :is="activeIconComponent" class="w-6 h-6" />
            </div>
            <div class="overflow-hidden">
              <h4 class="font-bold text-slate-800 truncate">{{ form.name || 'Untitled Subject' }}</h4>
              <p class="text-xs text-slate-500">0 Chapters</p>
            </div>
          </div>
        </div>

        <!-- Action Control Buttons Frame -->
        <div class="flex justify-end space-x-3 pt-4 border-t border-slate-100">
          <Link 
            href="/admin/subjects"
            class="px-5 py-2.5 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"
          >
            Cancel
          </Link>
          <button 
            type="submit" 
            :disabled="form.processing"
            class="px-6 py-2.5 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-600/20 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ form.processing ? 'Saving...' : 'Save Subject' }}
          </button>
        </div>

      </form>
    </div>
  </div>
</template>