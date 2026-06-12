<script setup>
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Tulis sesuatu di sini...'
  }
});

const emit = defineEmits(['update:modelValue']);

const editorRef = ref(null);

// Update contenteditable innerHTML when modelValue changes from outside,
// but only if it's different to prevent cursor jumping!
watch(() => props.modelValue, (newVal) => {
  if (editorRef.value && editorRef.value.innerHTML !== newVal) {
    editorRef.value.innerHTML = newVal || '';
  }
});

onMounted(() => {
  if (editorRef.value) {
    editorRef.value.innerHTML = props.modelValue || '';
  }
});

const onInput = (e) => {
  emit('update:modelValue', e.target.innerHTML);
};

const onBlur = (e) => {
  emit('update:modelValue', e.target.innerHTML);
};

// Rich text exec command
const execCommand = (command, value = null) => {
  document.execCommand(command, false, value);
  if (editorRef.value) {
    emit('update:modelValue', editorRef.value.innerHTML);
  }
};

// Selection saving & restoring
const saveSelection = () => {
  if (window.getSelection) {
    const sel = window.getSelection();
    if (sel.getRangeAt && sel.rangeCount) {
      return sel.getRangeAt(0);
    }
  }
  return null;
};

const restoreSelection = (range) => {
  if (range && window.getSelection) {
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
  }
};

// Custom Prompt state
const customPrompt = ref({
  show: false,
  title: '',
  placeholder: '',
  value: '',
  onConfirm: null,
  onCancel: null
});

const showCustomPrompt = (title, placeholder = 'Masukkan URL...') => {
  return new Promise((resolve) => {
    customPrompt.value = {
      show: true,
      title,
      placeholder,
      value: '',
      onConfirm: (val) => {
        customPrompt.value.show = false;
        resolve(val);
      },
      onCancel: () => {
        customPrompt.value.show = false;
        resolve(null);
      }
    };
  });
};

const addLinkPrompt = async () => {
  const range = saveSelection();
  const url = await showCustomPrompt('Masukkan URL Link:', 'https://example.com');
  if (url) {
    restoreSelection(range);
    execCommand('createLink', url);
  }
};

const addImagePrompt = async () => {
  const range = saveSelection();
  const url = await showCustomPrompt('Masukkan URL Gambar:', 'https://example.com/image.png');
  if (url) {
    restoreSelection(range);
    execCommand('insertImage', url);
  }
};

const insertCodeBlock = async () => {
  const range = saveSelection();
  const code = await showCustomPrompt('Masukkan baris kode sumber:', 'console.log("Hello World");');
  if (code) {
    restoreSelection(range);
    const formatted = `<pre class="bg-slate-800 text-slate-100 p-3 rounded-lg font-mono text-xs my-2 overflow-x-auto"><code>${code}</code></pre>`;
    execCommand('insertHTML', formatted);
  }
};
</script>

<template>
  <div class="flex flex-col border border-slate-250 rounded-2xl overflow-hidden shadow-sm focus-within:ring-2 focus-within:ring-[#264790]/20 focus-within:border-[#264790] transition-all">
    <!-- Toolbar -->
    <div class="flex items-center gap-1 p-2 bg-slate-50 border-b border-slate-200 flex-wrap select-none">
      <button type="button" @click="execCommand('bold')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 font-bold transition-colors">B</button>
      <button type="button" @click="execCommand('italic')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 italic transition-colors">I</button>
      <button type="button" @click="execCommand('underline')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 underline transition-colors">U</button>
      <button type="button" @click="execCommand('strikeThrough')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 line-through transition-colors">S</button>
      
      <div class="w-px h-5 bg-slate-300 mx-1"></div>
      
      <button type="button" @click="execCommand('insertUnorderedList')" class="px-2 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 text-xs font-bold transition-colors">• List</button>
      <button type="button" @click="execCommand('insertOrderedList')" class="px-2 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 text-xs font-bold transition-colors">1. List</button>
      
      <div class="w-px h-5 bg-slate-300 mx-1"></div>
      
      <button type="button" @click="addLinkPrompt" class="px-2 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 text-xs font-bold transition-colors">Link</button>
      <button type="button" @click="addImagePrompt" class="px-2 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 text-xs font-bold transition-colors">Image</button>
      <button type="button" @click="insertCodeBlock" class="px-2 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 text-xs font-mono font-bold transition-colors">&lt;/&gt;</button>
      
      <div class="w-px h-5 bg-slate-300 mx-1"></div>
      
      <button type="button" @click="execCommand('undo')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 font-bold transition-colors">↶</button>
      <button type="button" @click="execCommand('redo')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 font-bold transition-colors">↷</button>
    </div>

    <!-- Content Area -->
    <div 
      ref="editorRef"
      contenteditable="true"
      @input="onInput"
      @blur="onBlur"
      :data-placeholder="placeholder"
      class="editor-content p-4 min-h-[150px] max-h-[350px] overflow-y-auto outline-none bg-white text-slate-800 text-sm leading-relaxed prose prose-sm max-w-none"
    ></div>

    <!-- Custom Prompt Modal (integrated/self-contained inside the component) -->
    <Transition name="fade">
      <div v-if="customPrompt.show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-md">
        <Transition name="scale">
          <div v-if="customPrompt.show" class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 max-w-md w-full shadow-2xl border border-slate-100 dark:border-slate-800 text-center flex flex-col items-center transform transition-all">
            <!-- Icon Container (Link style) -->
            <div class="mb-5 flex items-center justify-center w-20 h-20 rounded-full bg-indigo-50 text-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-400">
              <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
              </svg>
            </div>

            <!-- Title -->
            <h3 class="text-xl font-black text-slate-800 dark:text-slate-100 mb-2">{{ customPrompt.title }}</h3>
            
            <!-- Input box -->
            <input 
              v-model="customPrompt.value" 
              type="text" 
              :placeholder="customPrompt.placeholder" 
              @keyup.enter="customPrompt.onConfirm(customPrompt.value)"
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl px-4.5 py-3 text-xs outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all font-semibold text-slate-700 dark:text-slate-300 placeholder:text-slate-400 mb-6" 
            />

            <!-- Buttons -->
            <div class="flex items-center gap-3 w-full">
              <!-- Cancel Button -->
              <button 
                type="button"
                @click="customPrompt.onCancel" 
                class="flex-1 py-3 px-6 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-2xl text-sm font-bold transition-all duration-200"
              >
                Batal
              </button>
              
              <!-- OK Button -->
              <button 
                type="button"
                @click="customPrompt.onConfirm(customPrompt.value)" 
                class="flex-1 py-3 px-6 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-sm font-bold shadow-lg shadow-indigo-600/20 transition-all duration-200 transform active:scale-95"
              >
                OK
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.scale-enter-active, .scale-leave-active {
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
}
.scale-enter-from, .scale-leave-to {
  transform: scale(0.9);
  opacity: 0;
}

/* Placeholder styling for contenteditable */
.editor-content:empty:before {
  content: attr(data-placeholder);
  color: #94a3b8;
  font-weight: 500;
  cursor: text;
}

/* Custom styles for rich text editor list formatting */
:deep(ul) {
  list-style-type: disc !important;
  padding-left: 1.5rem !important;
  margin-top: 0.5rem !important;
  margin-bottom: 0.5rem !important;
}

:deep(ol) {
  list-style-type: decimal !important;
  padding-left: 1.5rem !important;
  margin-top: 0.5rem !important;
  margin-bottom: 0.5rem !important;
}

:deep(li) {
  display: list-item !important;
  list-style: inherit !important;
  margin-top: 0.25rem !important;
  margin-bottom: 0.25rem !important;
}
</style>
