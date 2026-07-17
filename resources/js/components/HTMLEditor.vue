<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import {
    Bold, Italic, Underline, Strikethrough,
    List, ListOrdered, Heading1, Heading2, Heading3,
    Quote, Link2, Undo2, Redo2, Eraser,
} from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Write your content here...',
    },
    error: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue']);
const editorRef = ref<HTMLElement | null>(null);
const isFocused = ref(false);

// Tracks which commands are "active" for the current selection so toolbar
// buttons can highlight (bold pressed while cursor is inside bold text, etc.)
const activeStates = reactive({
    bold: false,
    italic: false,
    underline: false,
    strikeThrough: false,
    insertUnorderedList: false,
    insertOrderedList: false,
    formatBlockH1: false,
    formatBlockH2: false,
    formatBlockH3: false,
    formatBlockBlockquote: false,
});

const updateActiveStates = () => {
    if (!editorRef.value) return;
    activeStates.bold = document.queryCommandState('bold');
    activeStates.italic = document.queryCommandState('italic');
    activeStates.underline = document.queryCommandState('underline');
    activeStates.strikeThrough = document.queryCommandState('strikeThrough');
    activeStates.insertUnorderedList = document.queryCommandState('insertUnorderedList');
    activeStates.insertOrderedList = document.queryCommandState('insertOrderedList');

    const block = (document.queryCommandValue('formatBlock') || '').toLowerCase();
    activeStates.formatBlockH1 = block === 'h1';
    activeStates.formatBlockH2 = block === 'h2';
    activeStates.formatBlockH3 = block === 'h3';
    activeStates.formatBlockBlockquote = block === 'blockquote';
};

const focusEditor = () => editorRef.value?.focus();

const format = (command: string, value: string = '') => {
    focusEditor();
    document.execCommand(command, false, value);
    updateContent();
    updateActiveStates();
};

const toggleBlock = (tag: string) => {
    focusEditor();
    const current = (document.queryCommandValue('formatBlock') || '').toLowerCase();
    // Clicking an already-active block button turns it back into a paragraph
    document.execCommand('formatBlock', false, current === tag ? '<p>' : `<${tag}>`);
    updateContent();
    updateActiveStates();
};

const insertLink = () => {
    const url = window.prompt('Enter a URL');
    if (!url) return;
    focusEditor();
    document.execCommand('createLink', false, url);
    updateContent();
    updateActiveStates();
};

const clearFormatting = () => {
    focusEditor();
    document.execCommand('removeFormat');
    document.execCommand('formatBlock', false, '<p>');
    updateContent();
    updateActiveStates();
};

const isEmptyHtml = (html: string) => {
    return !html || html === '<br>' || html === '<div><br></div>' || html === '<p></p>' || html === '<p><br></p>';
};

const updateContent = () => {
    if (editorRef.value) {
        const html = editorRef.value.innerHTML;
        emit('update:modelValue', isEmptyHtml(html) ? '' : html);
    }
};

// Paste as plain text to avoid dragging in foreign styles/markup
const handlePaste = (event: ClipboardEvent) => {
    event.preventDefault();
    const text = event.clipboardData?.getData('text/plain') ?? '';
    document.execCommand('insertText', false, text);
    updateContent();
};

const handleKeydown = (event: KeyboardEvent) => {
    const isMod = event.metaKey || event.ctrlKey;
    if (!isMod) return;

    const key = event.key.toLowerCase();
    if (key === 'b') { event.preventDefault(); format('bold'); }
    else if (key === 'i') { event.preventDefault(); format('italic'); }
    else if (key === 'u') { event.preventDefault(); format('underline'); }
};

onMounted(() => {
    if (editorRef.value && props.modelValue) {
        editorRef.value.innerHTML = props.modelValue;
    }
    document.addEventListener('selectionchange', updateActiveStates);
});

onBeforeUnmount(() => {
    document.removeEventListener('selectionchange', updateActiveStates);
});

// Watch for external content updates (e.g., when editing an existing post)
watch(() => props.modelValue, (newValue) => {
    if (editorRef.value && newValue !== editorRef.value.innerHTML) {
        editorRef.value.innerHTML = newValue || '';
    }
});

interface ToolbarButton {
    key: string;
    title: string;
    icon: any;
    active: boolean;
    action: () => void;
}

const toolbarGroups: ToolbarButton[][] = [
    [
        { key: 'undo', title: 'Undo (Ctrl+Z)', icon: Undo2, active: false, action: () => format('undo') },
        { key: 'redo', title: 'Redo (Ctrl+Y)', icon: Redo2, active: false, action: () => format('redo') },
    ],
    [
        { key: 'bold', title: 'Bold (Ctrl+B)', icon: Bold, active: false, action: () => format('bold') },
        { key: 'italic', title: 'Italic (Ctrl+I)', icon: Italic, active: false, action: () => format('italic') },
        { key: 'underline', title: 'Underline (Ctrl+U)', icon: Underline, active: false, action: () => format('underline') },
        { key: 'strikeThrough', title: 'Strikethrough', icon: Strikethrough, active: false, action: () => format('strikeThrough') },
    ],
    [
        { key: 'formatBlockH1', title: 'Heading 1', icon: Heading1, active: false, action: () => toggleBlock('h1') },
        { key: 'formatBlockH2', title: 'Heading 2', icon: Heading2, active: false, action: () => toggleBlock('h2') },
        { key: 'formatBlockH3', title: 'Heading 3', icon: Heading3, active: false, action: () => toggleBlock('h3') },
        { key: 'formatBlockBlockquote', title: 'Quote', icon: Quote, active: false, action: () => toggleBlock('blockquote') },
    ],
    [
        { key: 'insertUnorderedList', title: 'Bullet List', icon: List, active: false, action: () => format('insertUnorderedList') },
        { key: 'insertOrderedList', title: 'Numbered List', icon: ListOrdered, active: false, action: () => format('insertOrderedList') },
    ],
    [
        { key: 'link', title: 'Insert Link', icon: Link2, active: false, action: insertLink },
        { key: 'clear', title: 'Clear Formatting', icon: Eraser, active: false, action: clearFormatting },
    ],
];
</script>

<template>
    <div
        class="rounded-lg border bg-white overflow-hidden transition-shadow"
        :class="[
            error ? 'border-rose-500' : (isFocused ? 'border-blue-500' : 'border-slate-300'),
            isFocused && !error ? 'ring-2 ring-blue-500/20' : '',
        ]"
    >
        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50 p-2 select-none">
            <template v-for="(group, gi) in toolbarGroups" :key="gi">
                <div class="flex items-center gap-0.5">
                    <button
                        v-for="btn in group"
                        :key="btn.key"
                        type="button"
                        @mousedown.prevent
                        @click="btn.action()"
                        :title="btn.title"
                        class="p-1.5 rounded transition"
                        :class="(activeStates as any)[btn.key]
                            ? 'bg-blue-100 text-blue-600'
                            : 'text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
                    >
                        <component :is="btn.icon" class="h-4 w-4" />
                    </button>
                </div>
                <div v-if="gi < toolbarGroups.length - 1" class="h-4 w-[1px] bg-slate-200 mx-1"></div>
            </template>
        </div>

        <!-- Editable Workspace -->
        <div
            ref="editorRef"
            contenteditable="true"
            @input="updateContent"
            @focus="isFocused = true; updateActiveStates()"
            @blur="isFocused = false"
            @keyup="updateActiveStates"
            @mouseup="updateActiveStates"
            @keydown="handleKeydown"
            @paste="handlePaste"
            class="wysiwyg-content min-h-[350px] max-h-[600px] overflow-y-auto p-4 outline-none text-slate-800 text-base font-sans leading-relaxed"
            :placeholder="placeholder"
        ></div>

        <!-- Error message -->
        <p v-if="error" class="px-4 py-2 text-sm text-rose-600 bg-rose-50 border-t border-rose-200">{{ error }}</p>
    </div>
</template>

<style scoped>
.wysiwyg-content:empty:before {
    content: attr(placeholder);
    color: #94a3b8;
    cursor: text;
}
:deep(.wysiwyg-content h1) { font-size: 1.875rem; font-weight: 700; margin-top: 1rem; margin-bottom: 0.5rem; }
:deep(.wysiwyg-content h2) { font-size: 1.5rem; font-weight: 600; margin-top: 0.875rem; margin-bottom: 0.5rem; }
:deep(.wysiwyg-content h3) { font-size: 1.25rem; font-weight: 600; margin-top: 0.75rem; margin-bottom: 0.5rem; }
:deep(.wysiwyg-content p) { margin-bottom: 0.5rem; }
:deep(.wysiwyg-content ul) { list-style-type: disc; padding-left: 1.5rem; margin-bottom: 0.75rem; }
:deep(.wysiwyg-content ol) { list-style-type: decimal; padding-left: 1.5rem; margin-bottom: 0.75rem; }
:deep(.wysiwyg-content blockquote) {
    border-left: 3px solid #cbd5e1;
    padding-left: 1rem;
    color: #475569;
    font-style: italic;
    margin: 0.75rem 0;
}
:deep(.wysiwyg-content a) {
    color: #2563eb;
    text-decoration: underline;
}
</style>