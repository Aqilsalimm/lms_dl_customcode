<script setup>
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import axios from 'axios';
import { 
  FolderPlus, Tag, UserPlus, Settings, Save, Check, X, 
  Edit3, Trash2, Plus, Layers, Grid, BookOpen, Clock, 
  ExternalLink, Eye, ChevronRight, AlertCircle, ShieldAlert,
  Bold, Italic, Underline, AlignLeft, AlignCenter, AlignRight, 
  List, ListOrdered, Link2, Image as ImageIcon, Video as VideoIcon, Upload
} from 'lucide-vue-next';

const props = defineProps({
  blogSettings: Object,
  blogs: Array
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const role = computed(() => user.value?.role || 'student');
const isAdmin = computed(() => role.value === 'admin');

const activeTab = ref(typeof window !== 'undefined' ? (new URLSearchParams(window.location.search).get('tab') || 'articles') : 'articles');

// --- Layouts, Categories, Tags, Authors Local Copy ---
const categories = ref(JSON.parse(JSON.stringify(props.blogSettings.categories || [])));
const tags = ref(JSON.parse(JSON.stringify(props.blogSettings.tags || [])));
const authors = ref(JSON.parse(JSON.stringify(props.blogSettings.authors || [])));
const selectedTemplate = ref(props.blogSettings.template || '1');
const pendingRequest = ref(props.blogSettings.pending_request || null);
const allowedInstructors = ref(JSON.parse(JSON.stringify(props.blogSettings.allowed_instructors || [])));

// --- Settings Form (for updating settings) ---
const settingsForm = useForm({
  categories: categories.value,
  tags: tags.value,
  authors: authors.value,
  template: selectedTemplate.value,
  allowed_instructors: allowedInstructors.value
});

// --- Settings Actions ---
const saveSettings = () => {
  settingsForm.categories = categories.value;
  settingsForm.tags = tags.value;
  settingsForm.authors = authors.value;
  settingsForm.template = selectedTemplate.value;
  settingsForm.allowed_instructors = allowedInstructors.value;
  
  settingsForm.post(route('dashboard.settings.blog.update'), {
    preserveScroll: true,
    onSuccess: () => {
      if (isAdmin.value) {
        Swal.fire({
          title: 'Berhasil!',
          text: 'Pengaturan blog berhasil disimpan secara langsung!',
          icon: 'success',
          timer: 2000,
          showConfirmButton: false,
          customClass: { popup: 'rounded-[2rem]' }
        });
      } else {
        Swal.fire({
          title: 'Berhasil!',
          text: 'Pengaturan blog berhasil diusulkan! Menunggu persetujuan Super Admin.',
          icon: 'success',
          timer: 3000,
          showConfirmButton: false,
          customClass: { popup: 'rounded-[2rem]' }
        });
      }
    }
  });
};

const toggleInstructorAccess = (instructorId) => {
  if (allowedInstructors.value.includes(instructorId)) {
    allowedInstructors.value = allowedInstructors.value.filter(id => id !== instructorId);
  } else {
    allowedInstructors.value.push(instructorId);
  }
};

const approveSettings = () => {
  Swal.fire({
    title: 'Setujui Pengaturan?',
    text: 'Apakah Anda yakin ingin menyetujui usulan perubahan pengaturan blog ini?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#10b981',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Ya, Setujui!',
    cancelButtonText: 'Batal',
    customClass: { popup: 'rounded-[2rem]' }
  }).then((result) => {
    if (result.isConfirmed) {
      router.post(route('dashboard.settings.blog.approve-settings'), {}, {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire({
            title: 'Berhasil!',
            text: 'Usulan perubahan pengaturan blog berhasil disetujui!',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
            customClass: { popup: 'rounded-[2rem]' }
          }).then(() => {
            window.location.reload();
          });
        }
      });
    }
  });
};

const rejectSettings = () => {
  Swal.fire({
    title: 'Tolak Pengaturan?',
    text: 'Apakah Anda yakin ingin menolak usulan perubahan pengaturan blog ini?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Ya, Tolak!',
    cancelButtonText: 'Batal',
    customClass: { popup: 'rounded-[2rem]' }
  }).then((result) => {
    if (result.isConfirmed) {
      router.post(route('dashboard.settings.blog.reject-settings'), {}, {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire({
            title: 'Ditolak!',
            text: 'Usulan perubahan pengaturan blog ditolak.',
            icon: 'info',
            timer: 2000,
            showConfirmButton: false,
            customClass: { popup: 'rounded-[2rem]' }
          }).then(() => {
            window.location.reload();
          });
        }
      });
    }
  });
};

// --- Category Management Helpers ---
const newCategoryName = ref('');
const newCategoryParent = ref('');
const editingCategory = ref(null);
const editCategoryName = ref('');
const editCategoryParent = ref('');

const addCategory = () => {
  if (!newCategoryName.value.trim()) return;
  const newCat = {
    id: 'cat-' + Date.now(),
    name: newCategoryName.value.trim(),
    parent_id: newCategoryParent.value || null
  };
  categories.value.push(newCat);
  newCategoryName.value = '';
  newCategoryParent.value = '';
};

const deleteCategory = (id) => {
  categories.value = categories.value.filter(cat => cat.id !== id && cat.parent_id !== id);
};

const startEditCategory = (cat) => {
  editingCategory.value = cat.id;
  editCategoryName.value = cat.name;
  editCategoryParent.value = cat.parent_id || '';
};

const saveEditCategory = (id) => {
  const cat = categories.value.find(c => c.id === id);
  if (cat && editCategoryName.value.trim()) {
    cat.name = editCategoryName.value.trim();
    cat.parent_id = editCategoryParent.value || null;
  }
  editingCategory.value = null;
};

// --- Tag Management Helpers ---
const newTagName = ref('');
const addTag = () => {
  if (!newTagName.value.trim()) return;
  if (!tags.value.includes(newTagName.value.trim())) {
    tags.value.push(newTagName.value.trim());
  }
  newTagName.value = '';
};
const deleteTag = (tagName) => {
  tags.value = tags.value.filter(t => t !== tagName);
};

// --- Author Management Helpers ---
const newAuthorName = ref('');
const addAuthor = () => {
  if (!newAuthorName.value.trim()) return;
  if (!authors.value.includes(newAuthorName.value.trim())) {
    authors.value.push(newAuthorName.value.trim());
  }
  newAuthorName.value = '';
};
const deleteAuthor = (authorName) => {
  authors.value = authors.value.filter(a => a !== authorName);
};

// --- Article CRUD state ---
const isArticleModalOpen = ref(false);
const editingArticle = ref(null);
const articleForm = useForm({
  title: '',
  content: '',
  category: '',
  excerpt: '',
  image: '',
  tags: [],
  author_name: ''
});

// Editor Mode & WYSIWYG ref
const editorMode = ref('visual'); // 'visual' or 'text'
const visualEditorRef = ref(null);
const coverSourceMode = ref('upload');

const handleCoverUpload = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  if (file.size > 5 * 1024 * 1024) {
    Swal.fire({
      title: 'Ukuran Terlalu Besar',
      text: 'Ukuran file gambar tidak boleh melebihi 5MB.',
      icon: 'error',
      customClass: { popup: 'rounded-[2rem]' }
    });
    return;
  }

  const formData = new FormData();
  formData.append('image', file);

  Swal.fire({
    title: 'Mengunggah...',
    text: 'Mohon tunggu sebentar.',
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
    customClass: { popup: 'rounded-[2rem]' }
  });

  axios.post(route('dashboard.settings.blog.upload-image'), formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  }).then(response => {
    Swal.close();
    if (response.data && response.data.success) {
      articleForm.image = response.data.url;
      Swal.fire({
        title: 'Berhasil!',
        text: 'Gambar sampul berhasil diunggah.',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false,
        customClass: { popup: 'rounded-[2rem]' }
      });
    } else {
      Swal.fire({
        title: 'Gagal',
        text: response.data.message || 'Gagal mengunggah gambar.',
        icon: 'error',
        customClass: { popup: 'rounded-[2rem]' }
      });
    }
  }).catch(err => {
    Swal.close();
    Swal.fire({
      title: 'Error',
      text: err.response?.data?.message || 'Terjadi kesalahan saat mengunggah gambar.',
      icon: 'error',
      customClass: { popup: 'rounded-[2rem]' }
    });
  });
};

const syncContentFromVisual = () => {
  if (visualEditorRef.value) {
    articleForm.content = visualEditorRef.value.innerHTML;
  }
};

const executeCommand = (command, value = null) => {
  if (editorMode.value !== 'visual') return;
  document.execCommand(command, false, value);
  syncContentFromVisual();
};

const formatDoc = (tag) => {
  if (!tag) return;
  restoreSelection();
  const formattedTag = tag.startsWith('<') ? tag : `<${tag}>`;
  executeCommand('formatBlock', formattedTag);
  if (visualEditorRef.value) {
    visualEditorRef.value.focus();
  }
  saveSelection();
};

const changeFontSize = (size) => {
  if (!size) return;
  restoreSelection();
  
  document.execCommand('fontSize', false, '7');
  const fontElements = visualEditorRef.value.querySelectorAll('font[size="7"]');
  fontElements.forEach(el => {
    el.removeAttribute('size');
    const span = document.createElement('span');
    span.style.fontSize = size;
    span.style.fontFamily = "'Montserrat', sans-serif";
    span.innerHTML = el.innerHTML;
    el.parentNode.replaceChild(span, el);
  });
  
  if (visualEditorRef.value) {
    visualEditorRef.value.focus();
  }
  syncContentFromVisual();
  saveSelection();
};

const insertLink = () => {
  const sel = window.getSelection();
  let range = null;
  if (sel.rangeCount > 0) {
    range = sel.getRangeAt(0);
  }

  Swal.fire({
    title: 'Sisipkan Link',
    html: `
      <div class="text-left">
        <label class="block text-xs font-bold text-slate-500 mb-1">URL Link</label>
        <input id="swal-link-url" type="url" placeholder="https://example.com" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-semibold focus:border-blue-500 outline-none" />
        <label class="block text-xs font-bold text-slate-500 mt-3 mb-1">Teks Tautan (Opsional)</label>
        <input id="swal-link-text" type="text" placeholder="Kunjungi situs" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-semibold focus:border-blue-500 outline-none" />
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'Sisipkan',
    cancelButtonText: 'Batal',
    customClass: { popup: 'rounded-[2rem]' },
    preConfirm: () => {
      const url = document.getElementById('swal-link-url').value;
      const text = document.getElementById('swal-link-text').value;
      if (!url) {
        Swal.showValidationMessage('URL tidak boleh kosong');
        return false;
      }
      return { url, text };
    }
  }).then((result) => {
    if (result.isConfirmed) {
      if (range) {
        sel.removeAllRanges();
        sel.addRange(range);
      } else {
        if (visualEditorRef.value) {
          visualEditorRef.value.focus();
        }
      }

      const { url, text } = result.value;
      const linkText = text || url;
      const linkHtml = `<a href="${url}" target="_blank" class="text-blue-600 hover:underline font-bold">${linkText}</a>`;
      
      document.execCommand('insertHTML', false, linkHtml);
      syncContentFromVisual();
    }
  });
};

const insertImage = () => {
  const sel = window.getSelection();
  let range = null;
  if (sel.rangeCount > 0) {
    range = sel.getRangeAt(0);
  }

  Swal.fire({
    title: 'Sisipkan Gambar',
    html: `
      <div class="text-left flex flex-col gap-4">
        <div class="flex bg-slate-100 p-0.5 rounded-lg text-[10px] font-black uppercase mb-2">
          <button type="button" id="swal-img-src-upload" class="flex-1 py-1.5 rounded-md bg-white text-slate-800 shadow-sm transition-all duration-200">UPLOAD FILE</button>
          <button type="button" id="swal-img-src-url" class="flex-1 py-1.5 rounded-md text-slate-500 hover:text-slate-700 transition-all duration-200">EXTERNAL URL</button>
        </div>

        <div id="swal-img-upload-sec">
          <label class="block text-xs font-bold text-slate-500 mb-1">Pilih Gambar dari Perangkat</label>
          <div id="swal-img-dropzone" class="border-2 border-dashed border-slate-200 hover:border-blue-400 rounded-xl p-4 text-center cursor-pointer bg-slate-50/50">
            <svg class="mx-auto text-slate-400 mb-1 w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
            <span class="text-[10px] font-bold text-slate-500">Klik untuk upload (Max 5MB)</span>
            <input type="file" id="swal-img-file-input" class="hidden" accept="image/*" />
          </div>
          <div id="swal-img-upload-preview" class="hidden mt-2 rounded-xl overflow-hidden border border-slate-200 aspect-video bg-slate-100 flex items-center justify-center">
            <img id="swal-img-uploaded-view" src="" class="w-full h-full object-cover" />
          </div>
        </div>

        <div id="swal-img-url-sec" class="hidden">
          <label class="block text-xs font-bold text-slate-500 mb-1">URL Gambar</label>
          <input id="swal-img-url-input" type="url" placeholder="https://example.com/image.jpg" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-semibold focus:border-blue-500 outline-none" />
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-500 mb-1">Keterangan Gambar / Caption (Opsional)</label>
          <input id="swal-img-caption-input" type="text" placeholder="Deskripsi gambar..." class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-semibold focus:border-blue-500 outline-none" />
        </div>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'Sisipkan',
    cancelButtonText: 'Batal',
    customClass: { popup: 'rounded-[2rem]' },
    didOpen: () => {
      const btnUpload = document.getElementById('swal-img-src-upload');
      const btnUrl = document.getElementById('swal-img-src-url');
      const secUpload = document.getElementById('swal-img-upload-sec');
      const secUrl = document.getElementById('swal-img-url-sec');
      
      let mode = 'upload';
      let uploadedUrl = '';

      btnUpload.addEventListener('click', () => {
        mode = 'upload';
        btnUpload.className = "flex-1 py-1.5 rounded-md bg-white text-slate-800 shadow-sm transition-all duration-200";
        btnUrl.className = "flex-1 py-1.5 rounded-md text-slate-500 hover:text-slate-700 transition-all duration-200";
        secUpload.classList.remove('hidden');
        secUrl.classList.add('hidden');
      });

      btnUrl.addEventListener('click', () => {
        mode = 'url';
        btnUrl.className = "flex-1 py-1.5 rounded-md bg-white text-slate-800 shadow-sm transition-all duration-200";
        btnUpload.className = "flex-1 py-1.5 rounded-md text-slate-500 hover:text-slate-700 transition-all duration-200";
        secUrl.classList.remove('hidden');
        secUpload.classList.add('hidden');
      });

      const dropzone = document.getElementById('swal-img-dropzone');
      const fileInput = document.getElementById('swal-img-file-input');
      const preview = document.getElementById('swal-img-upload-preview');
      const imgView = document.getElementById('swal-img-uploaded-view');

      dropzone.addEventListener('click', () => fileInput.click());

      fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
          alert('Ukuran file gambar tidak boleh melebihi 5MB.');
          return;
        }

        const formData = new FormData();
        formData.append('image', file);

        const confirmBtn = Swal.getConfirmButton();
        confirmBtn.disabled = true;
        confirmBtn.innerText = 'Mengunggah...';

        axios.post(route('dashboard.settings.blog.upload-image'), formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        }).then(res => {
          confirmBtn.disabled = false;
          confirmBtn.innerText = 'Sisipkan';
          if (res.data && res.data.success) {
            uploadedUrl = res.data.url;
            imgView.src = res.data.url;
            preview.classList.remove('hidden');
            dropzone.classList.add('hidden');
          } else {
            alert('Gagal mengunggah.');
          }
        }).catch(err => {
          confirmBtn.disabled = false;
          confirmBtn.innerText = 'Sisipkan';
          alert(err.response?.data?.message || 'Terjadi kesalahan saat mengunggah.');
        });
      });

      window.swalImgState = {
        getMode: () => mode,
        getUploadedUrl: () => uploadedUrl
      };
    },
    preConfirm: () => {
      const state = window.swalImgState;
      const mode = state.getMode();
      const caption = document.getElementById('swal-img-caption-input').value;
      let url = '';

      if (mode === 'upload') {
        url = state.getUploadedUrl();
        if (!url) {
          Swal.showValidationMessage('Silakan pilih dan unggah file gambar terlebih dahulu.');
          return false;
        }
      } else {
        url = document.getElementById('swal-img-url-input').value;
        if (!url) {
          Swal.showValidationMessage('URL gambar tidak boleh kosong');
          return false;
        }
      }

      return { url, caption };
    }
  }).then((result) => {
    if (result.isConfirmed) {
      if (range) {
        sel.removeAllRanges();
        sel.addRange(range);
      } else {
        if (visualEditorRef.value) {
          visualEditorRef.value.focus();
        }
      }

      const { url, caption } = result.value;
      const figureHtml = `
        <figure class="my-6 rounded-2xl overflow-hidden border border-slate-100 shadow-sm text-center" contenteditable="false">
          <img class="w-full h-auto object-cover max-h-[400px]" src="${url}" alt="${caption}" />
          ${caption ? `<figcaption class="bg-slate-50 px-4 py-2.5 text-center text-xs font-bold text-slate-500 border-t border-slate-100">${caption}</figcaption>` : ''}
        </figure>
        <p><br></p>
      `;
      document.execCommand('insertHTML', false, figureHtml);
      syncContentFromVisual();
    }
  });
};

const insertVideo = () => {
  const sel = window.getSelection();
  let range = null;
  if (sel.rangeCount > 0) {
    range = sel.getRangeAt(0);
  }

  Swal.fire({
    title: 'Sisipkan Video Player',
    html: `
      <div class="text-left">
        <label class="block text-xs font-bold text-slate-500 mb-1">URL Video (YouTube / Link Video Langsung)</label>
        <input id="swal-video-url" type="url" placeholder="https://www.youtube.com/watch?v=..." class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-semibold focus:border-blue-500 outline-none" />
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'Sisipkan',
    cancelButtonText: 'Batal',
    customClass: { popup: 'rounded-[2rem]' },
    preConfirm: () => {
      const url = document.getElementById('swal-video-url').value;
      if (!url) {
        Swal.showValidationMessage('URL video tidak boleh kosong');
        return false;
      }
      return url;
    }
  }).then((result) => {
    if (result.isConfirmed) {
      if (range) {
        sel.removeAllRanges();
        sel.addRange(range);
      } else {
        if (visualEditorRef.value) {
          visualEditorRef.value.focus();
        }
      }

      const url = result.value;
      let videoHtml = '';
      const youtubeRegExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
      const match = url.match(youtubeRegExp);
      if (match && match[2].length === 11) {
        const videoId = match[2];
        videoHtml = `
          <div class="video-container my-6 aspect-video rounded-2xl overflow-hidden border border-slate-100 shadow-sm" contenteditable="false">
            <iframe class="w-full h-full" src="https://www.youtube.com/embed/${videoId}" frameborder="0" allowfullscreen></iframe>
          </div>
          <p><br></p>
        `;
      } else {
        videoHtml = `
          <div class="video-container my-6 rounded-2xl overflow-hidden border border-slate-100 shadow-sm" contenteditable="false">
            <video class="w-full h-auto" src="${url}" controls></video>
          </div>
          <p><br></p>
        `;
      }
      document.execCommand('insertHTML', false, videoHtml);
      syncContentFromVisual();
    }
  });
};

const savedRange = ref(null);
const activeFormat = ref('p');
const activeFontSize = ref('14px');

const updateActiveStyleState = () => {
  const sel = window.getSelection();
  if (!sel || sel.rangeCount === 0) return;
  
  let node = sel.anchorNode;
  if (!node) return;
  
  let formatVal = '';
  let fontSizeVal = '';
  
  let curr = node.nodeType === 3 ? node.parentNode : node;
  
  while (curr && curr !== visualEditorRef.value) {
    const tagName = curr.tagName ? curr.tagName.toLowerCase() : '';
    
    if (['h2', 'h3', 'h4', 'blockquote', 'p'].includes(tagName)) {
      if (!formatVal) {
        formatVal = tagName;
      }
    }
    
    if (curr.style && curr.style.fontSize) {
      if (!fontSizeVal) {
        fontSizeVal = curr.style.fontSize;
      }
    }
    
    curr = curr.parentNode;
  }
  
  activeFormat.value = formatVal || 'p';
  activeFontSize.value = fontSizeVal || '14px';
};

const saveSelection = () => {
  const sel = window.getSelection();
  if (sel.rangeCount > 0) {
    savedRange.value = sel.getRangeAt(0);
  }
  updateActiveStyleState();
};

const restoreSelection = () => {
  if (savedRange.value) {
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedRange.value);
  }
};

const changeTextColor = (event) => {
  restoreSelection();
  executeCommand('foreColor', event.target.value);
  if (visualEditorRef.value) {
    visualEditorRef.value.focus();
  }
  saveSelection();
};

const changeHighlightColor = (event) => {
  restoreSelection();
  executeCommand('hiliteColor', event.target.value);
  if (visualEditorRef.value) {
    visualEditorRef.value.focus();
  }
  saveSelection();
};

const setEditorMode = (mode) => {
  if (mode === 'visual' && editorMode.value === 'text') {
    editorMode.value = 'visual';
    setTimeout(() => {
      if (visualEditorRef.value) {
        visualEditorRef.value.innerHTML = articleForm.content || '<p><br></p>';
      }
    }, 50);
  } else if (mode === 'text' && editorMode.value === 'visual') {
    syncContentFromVisual();
    editorMode.value = 'text';
  }
};

const openCreateArticleModal = () => {
  editingArticle.value = null;
  articleForm.reset();
  articleForm.title = '';
  articleForm.content = '';
  articleForm.category = categories.value[0]?.name || 'Coding';
  articleForm.excerpt = '';
  articleForm.image = '/images/news-placeholder.jpg';
  articleForm.tags = [];
  articleForm.author_name = authors.value[0] || user.value.name;
  isArticleModalOpen.value = true;
  editorMode.value = 'visual';
  setTimeout(() => {
    if (visualEditorRef.value) {
      visualEditorRef.value.innerHTML = '<p><br></p>';
    }
  }, 50);
};

const openEditArticleModal = (blog) => {
  editingArticle.value = blog;
  articleForm.title = blog.title;
  articleForm.content = blog.content;
  articleForm.category = blog.category;
  articleForm.excerpt = blog.excerpt;
  articleForm.image = blog.image || '/images/news-placeholder.jpg';
  
  let blogTags = [];
  try {
    if (blog.tags) {
      blogTags = typeof blog.tags === 'string' ? JSON.parse(blog.tags) : blog.tags;
    }
  } catch(e) {}
  articleForm.tags = blogTags;
  articleForm.author_name = blog.author_name || blog.user?.name || user.value.name;
  
  isArticleModalOpen.value = true;
  editorMode.value = 'visual';
  
  setTimeout(() => {
    if (visualEditorRef.value) {
      visualEditorRef.value.innerHTML = articleForm.content || '<p><br></p>';
    }
  }, 50);
};

const saveArticle = () => {
  if (editorMode.value === 'visual') {
    syncContentFromVisual();
  }
  if (editingArticle.value) {
    articleForm.put(route('blogs.update', editingArticle.value.id), {
      onSuccess: () => {
        isArticleModalOpen.value = false;
        Swal.fire({
          title: 'Berhasil!',
          text: 'Artikel berhasil diperbarui!',
          icon: 'success',
          timer: 2000,
          showConfirmButton: false,
          customClass: { popup: 'rounded-[2rem]' }
        });
      }
    });
  } else {
    articleForm.post(route('blogs.store'), {
      onSuccess: () => {
        isArticleModalOpen.value = false;
        Swal.fire({
          title: 'Berhasil!',
          text: 'Artikel berhasil diajukan/diterbitkan!',
          icon: 'success',
          timer: 2000,
          showConfirmButton: false,
          customClass: { popup: 'rounded-[2rem]' }
        });
      }
    });
  }
};

const deleteArticle = (id) => {
  Swal.fire({
    title: 'Hapus Artikel?',
    text: 'Apakah Anda yakin ingin menghapus artikel ini? Tindakan ini tidak dapat dibatalkan.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Ya, Hapus!',
    cancelButtonText: 'Batal',
    customClass: { popup: 'rounded-[2rem]' }
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(route('blogs.destroy', id), {
        onSuccess: () => {
          Swal.fire({
            title: 'Terhapus!',
            text: 'Artikel berhasil dihapus.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
            customClass: { popup: 'rounded-[2rem]' }
          });
        }
      });
    }
  });
};

const approveArticle = (id) => {
  Swal.fire({
    title: 'Terbitkan Artikel?',
    text: 'Apakah Anda yakin ingin menyetujui dan menerbitkan artikel ini?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#10b981',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Ya, Terbitkan!',
    cancelButtonText: 'Batal',
    customClass: { popup: 'rounded-[2rem]' }
  }).then((result) => {
    if (result.isConfirmed) {
      router.post(route('blogs.approve', id), {}, {
        onSuccess: () => {
          Swal.fire({
            title: 'Berhasil!',
            text: 'Artikel disetujui & diterbitkan!',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
            customClass: { popup: 'rounded-[2rem]' }
          });
        }
      });
    }
  });
};

const toggleTagSelection = (tagName) => {
  if (articleForm.tags.includes(tagName)) {
    articleForm.tags = articleForm.tags.filter(t => t !== tagName);
  } else {
    articleForm.tags.push(tagName);
  }
};

// Render helpers
const getParentName = (parentId) => {
  const parent = categories.value.find(c => c.id === parentId);
  return parent ? parent.name : '-';
};

const rootCategories = computed(() => {
  return categories.value.filter(c => !c.parent_id);
});

const getChildren = (parentId) => {
  return categories.value.filter(c => c.parent_id === parentId);
};
</script>

<template>
  <Head title="Blog Settings - Dashboard" />

  <DashboardWrapper>
    <div class="flex flex-col gap-8 max-w-6xl mx-auto">
      
      <!-- HEADER BANNER -->
      <div class="bg-gradient-to-r from-[#264790] to-[#44A6D9] rounded-[2.5rem] p-8 md:p-12 text-white relative overflow-hidden shadow-lg select-none">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-white/10 rounded-full blur-2xl"></div>
        <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/20 rounded-full text-xs font-bold uppercase tracking-wider mb-3 backdrop-blur-sm">
              <Settings :size="12" /> Admin Dashboard
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight">Blog & News Settings</h1>
            <p class="text-white/85 text-sm md:text-base font-semibold mt-2 max-w-xl">
              Atur layout template berita, nama redaksi, parent-child kategori, tag, serta kelola penerbitan artikel blog di satu tempat.
            </p>
          </div>
          <div v-if="activeTab !== 'articles'" class="shrink-0">
            <button 
              @click="saveSettings" 
              class="bg-white hover:bg-slate-100 text-[#264790] font-black px-6 py-3.5 rounded-full text-sm shadow-md transition-all duration-300 flex items-center gap-2.5 active:scale-95"
            >
              <Save :size="16" /> Simpan Pengaturan
            </button>
          </div>
        </div>
      </div>

      <!-- PENDING SETTINGS REQUEST WARNING FOR SUPERADMIN -->
      <div 
        v-if="pendingRequest && isAdmin" 
        class="bg-amber-50 border border-amber-200 rounded-3xl p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4.5 shadow-sm"
      >
        <div class="flex items-start gap-4">
          <ShieldAlert :size="24" class="text-amber-500 shrink-0 mt-0.5" />
          <div>
            <h3 class="text-base font-black text-amber-900">Permohonan Perubahan Pengaturan dari Instructor</h3>
            <p class="text-xs font-bold text-amber-700 mt-1">
              Instructor <span class="underline">{{ pendingRequest.requested_by }}</span> mengajukan pembaruan pengaturan Blog (Template, Kategori, Tag, Redaksi) pada {{ pendingRequest.requested_at }}.
            </p>
            <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2.5 text-[11px] font-extrabold text-amber-800 bg-amber-100/50 px-3 py-1.5 rounded-xl border border-amber-200/50 w-fit">
              <span>Layout Template: {{ pendingRequest.template }}</span>
              <span>•</span>
              <span>Kategori: {{ pendingRequest.categories?.length || 0 }}</span>
              <span>•</span>
              <span>Tag: {{ pendingRequest.tags?.length || 0 }}</span>
              <span>•</span>
              <span>Redaksi: {{ pendingRequest.authors?.length || 0 }}</span>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-3 shrink-0 w-full md:w-auto justify-end">
          <button 
            @click="rejectSettings" 
            class="bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 px-5 py-2.5 rounded-full text-xs font-black transition-all shadow-sm flex items-center gap-1.5"
          >
            <X :size="14" /> Tolak
          </button>
          <button 
            @click="approveSettings" 
            class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2.5 rounded-full text-xs font-black transition-all shadow-sm flex items-center gap-1.5"
          >
            <Check :size="14" /> Setujui Perubahan
          </button>
        </div>
      </div>

      <!-- PENDING SETTINGS FOR INSTRUCTOR -->
      <div 
        v-if="pendingRequest && !isAdmin" 
        class="bg-blue-50 border border-blue-100 rounded-3xl p-6 flex items-start gap-3.5 shadow-sm"
      >
        <AlertCircle :size="20" class="text-blue-500 shrink-0 mt-0.5" />
        <div>
          <h3 class="text-sm font-black text-blue-900">Usulan Perubahan Sedang Ditinjau</h3>
          <p class="text-xs font-semibold text-blue-600/90 leading-relaxed mt-0.5">
            Anda telah mengajukan usulan pembaruan pengaturan blog. Saat ini usulan Anda berstatus <span class="bg-blue-200 text-blue-800 font-extrabold px-1.5 py-0.5 rounded text-[10px]">Menunggu Approval Super Admin</span>. Setelah disetujui, perubahan layout dan kategori akan langsung diterapkan secara live.
          </p>
        </div>
      </div>

      <!-- TAB 1: KELOLA ARTIKEL -->
      <div v-if="activeTab === 'articles'" class="bg-white rounded-[2.5rem] p-6 md:p-8 border border-slate-100 shadow-sm">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 border-b border-slate-100 pb-5">
          <div>
            <h2 class="text-xl font-extrabold text-slate-800">Daftar Artikel Blog</h2>
            <p class="text-xs text-slate-500 mt-1">Tambah, edit, hapus, dan tinjau artikel berita pembelajaran.</p>
          </div>
          <button 
            @click="openCreateArticleModal" 
            class="bg-[#264790] hover:bg-[#1A2B49] text-white px-5 py-3 rounded-full text-xs font-black shadow-sm transition-all flex items-center gap-1.5 active:scale-95"
          >
            <Plus :size="14" /> Tambah Artikel Baru
          </button>
        </div>

        <!-- ARTICLES TABLE -->
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-100 text-slate-400 font-extrabold text-xs uppercase">
                <th class="py-4 px-3">Artikel</th>
                <th class="py-4 px-3">Kategori</th>
                <th class="py-4 px-3">Redaktur</th>
                <th class="py-4 px-3">Status</th>
                <th class="py-4 px-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="blogs.length === 0">
                <td colspan="5" class="py-12 text-center text-slate-400 font-bold text-sm">
                  Belum ada artikel. Klik tombol di atas untuk membuat artikel berita pertama Anda.
                </td>
              </tr>
              <tr v-for="blog in blogs" :key="blog.id" class="group hover:bg-slate-50/50 transition-colors">
                <td class="py-4 px-3">
                  <div class="flex items-center gap-3">
                    <img 
                      :src="blog.image || '/images/news-placeholder.jpg'" 
                      class="w-12 h-12 rounded-xl object-cover shrink-0 border border-slate-100" 
                      alt="Thumbnail" 
                    />
                    <div>
                      <h4 class="font-extrabold text-slate-800 text-sm leading-snug line-clamp-1 group-hover:text-blue-600 transition-colors">
                        {{ blog.title }}
                      </h4>
                      <p class="text-[10px] font-bold text-slate-400 mt-0.5 flex items-center gap-1">
                        <Clock :size="10" /> {{ new Date(blog.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'}) }}
                      </p>
                    </div>
                  </div>
                </td>
                <td class="py-4 px-3">
                  <span class="bg-slate-100 text-slate-600 text-[10px] font-extrabold px-2 py-1 rounded-lg border border-slate-200/50">
                    {{ blog.category }}
                  </span>
                </td>
                <td class="py-4 px-3">
                  <span class="text-xs font-bold text-slate-600">
                    {{ blog.author_name || blog.user?.name }}
                  </span>
                </td>
                <td class="py-4 px-3">
                  <span 
                    :class="[
                      blog.status === 'published' 
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-100' 
                        : 'bg-amber-50 text-amber-700 border-amber-100'
                    ]"
                    class="text-[10px] font-black px-2.5 py-1 rounded-full border"
                  >
                    {{ blog.status === 'published' ? 'Published' : 'Pending Approval' }}
                  </span>
                </td>
                <td class="py-4 px-3 text-right">
                  <div class="flex items-center justify-end gap-2">
                    
                    <!-- Approve button for Super Admin -->
                    <button 
                      v-if="blog.status !== 'published' && isAdmin"
                      @click="approveArticle(blog.id)"
                      class="bg-emerald-500 hover:bg-emerald-600 text-white p-2 rounded-lg text-xs font-black transition-all flex items-center gap-1 shadow-sm"
                      title="Setujui & Publikasikan"
                    >
                      <Check :size="14" /> <span class="hidden sm:inline">Approve</span>
                    </button>
                    
                    <a 
                      :href="'/blogs/' + blog.slug" 
                      target="_blank" 
                      class="text-slate-400 hover:text-blue-500 p-2 rounded-lg hover:bg-blue-50 transition-colors border border-transparent hover:border-blue-100/50"
                      title="Preview"
                    >
                      <Eye :size="15" />
                    </a>
                    
                    <button 
                      @click="openEditArticleModal(blog)"
                      class="text-slate-400 hover:text-amber-500 p-2 rounded-lg hover:bg-amber-50 transition-colors border border-transparent hover:border-amber-100/50"
                      title="Edit"
                    >
                      <Edit3 :size="15" />
                    </button>
                    
                    <button 
                      @click="deleteArticle(blog.id)"
                      class="text-slate-400 hover:text-rose-500 p-2 rounded-lg hover:bg-rose-50 transition-colors border border-transparent hover:border-rose-100/50"
                      title="Hapus"
                    >
                      <Trash2 :size="15" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB 2: KATEGORI & TAG -->
      <div v-if="activeTab === 'categories_tags'" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- CATEGORIES PANEL -->
        <div class="bg-white rounded-[2.5rem] p-6 md:p-8 border border-slate-100 shadow-sm flex flex-col gap-6">
          <div>
            <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
              <Layers :size="20" class="text-blue-500" /> Kategori Berita
            </h3>
            <p class="text-xs text-slate-500 mt-1">Konfigurasikan relasi Parent Category dan Child Category.</p>
          </div>

          <!-- Add Category Form -->
          <div class="bg-slate-50 p-4.5 rounded-2xl border border-slate-100 flex flex-col gap-3">
            <h4 class="text-xs font-black text-slate-700 uppercase tracking-wide">Tambah Kategori Baru</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama Kategori</label>
                <input 
                  v-model="newCategoryName" 
                  type="text" 
                  placeholder="Contoh: Web Dev" 
                  class="w-full border-2 border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:border-blue-500 outline-none transition-colors"
                />
              </div>
              <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Parent Category</label>
                <select 
                  v-model="newCategoryParent" 
                  class="w-full border-2 border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:border-blue-500 outline-none transition-colors cursor-pointer"
                >
                  <option value="">Tidak ada (Parent Utama)</option>
                  <option 
                    v-for="cat in categories.filter(c => !c.parent_id)" 
                    :key="cat.id" 
                    :value="cat.id"
                  >
                    {{ cat.name }}
                  </option>
                </select>
              </div>
            </div>
            <button 
              @click="addCategory" 
              class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-black py-2.5 rounded-xl transition-all shadow-sm active:scale-95 mt-1"
            >
              Tambah Kategori
            </button>
          </div>

          <!-- Categories Tree Listing -->
          <div class="flex flex-col gap-3">
            <h4 class="text-xs font-black text-slate-700 uppercase tracking-wide">Struktur Hirarki Kategori</h4>
            <div class="border border-slate-100 rounded-2xl overflow-hidden divide-y divide-slate-100 max-h-80 overflow-y-auto">
              <div v-if="categories.length === 0" class="p-8 text-center text-xs text-slate-400 font-bold">
                Kategori kosong.
              </div>
              
              <!-- Loop Parent categories -->
              <div v-for="parent in rootCategories" :key="parent.id" class="flex flex-col">
                
                <!-- Parent row -->
                <div class="flex items-center justify-between p-3.5 bg-slate-50/50 hover:bg-slate-50 transition-colors">
                  <div class="flex items-center gap-2">
                    <ChevronRight :size="14" class="text-slate-400 rotate-90" />
                    <span v-if="editingCategory !== parent.id" class="text-xs font-black text-slate-800">{{ parent.name }}</span>
                    <input 
                      v-else 
                      v-model="editCategoryName" 
                      type="text" 
                      class="border border-slate-200 rounded px-2 py-0.5 text-xs font-semibold outline-none focus:border-blue-500" 
                    />
                  </div>
                  <div class="flex items-center gap-1">
                    <button 
                      v-if="editingCategory !== parent.id" 
                      @click="startEditCategory(parent)" 
                      class="text-slate-400 hover:text-amber-500 p-1"
                    >
                      <Edit3 :size="12" />
                    </button>
                    <button 
                      v-else 
                      @click="saveEditCategory(parent.id)" 
                      class="text-emerald-500 hover:text-emerald-600 p-1 font-bold"
                    >
                      <Check :size="12" />
                    </button>
                    <button @click="deleteCategory(parent.id)" class="text-slate-400 hover:text-rose-500 p-1">
                      <Trash2 :size="12" />
                    </button>
                  </div>
                </div>

                <!-- Child categories list -->
                <div class="flex flex-col pl-6 border-l-2 border-slate-100 divide-y divide-slate-50 bg-white">
                  <div 
                    v-for="child in getChildren(parent.id)" 
                    :key="child.id" 
                    class="flex items-center justify-between p-2.5 hover:bg-slate-50/30 transition-colors"
                  >
                    <div class="flex items-center gap-2">
                      <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                      <span v-if="editingCategory !== child.id" class="text-xs font-bold text-slate-600">{{ child.name }}</span>
                      <input 
                        v-else 
                        v-model="editCategoryName" 
                        type="text" 
                        class="border border-slate-200 rounded px-2 py-0.5 text-xs font-semibold outline-none focus:border-blue-500" 
                      />
                    </div>
                    <div class="flex items-center gap-1">
                      <button 
                        v-if="editingCategory !== child.id" 
                        @click="startEditCategory(child)" 
                        class="text-slate-400 hover:text-amber-500 p-1"
                      >
                        <Edit3 :size="11" />
                      </button>
                      <button 
                        v-else 
                        @click="saveEditCategory(child.id)" 
                        class="text-emerald-500 hover:text-emerald-600 p-1 font-bold"
                      >
                        <Check :size="11" />
                      </button>
                      <button @click="deleteCategory(child.id)" class="text-slate-400 hover:text-rose-500 p-1">
                        <Trash2 :size="11" />
                      </button>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- TAGS PANEL -->
        <div class="bg-white rounded-[2.5rem] p-6 md:p-8 border border-slate-100 shadow-sm flex flex-col gap-6">
          <div>
            <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
              <Tag :size="20" class="text-blue-500" /> Pengaturan Tag
            </h3>
            <p class="text-xs text-slate-500 mt-1">Kelola daftar kata kunci (tags) yang disematkan pada artikel.</p>
          </div>

          <!-- Add Tag Form -->
          <div class="flex gap-2">
            <input 
              v-model="newTagName" 
              type="text" 
              placeholder="Contoh: CSS Grid" 
              class="flex-1 border-2 border-slate-200 rounded-xl px-4 py-2.5 text-xs font-semibold focus:border-blue-500 outline-none transition-colors"
              @keyup.enter="addTag"
            />
            <button 
              @click="addTag" 
              class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-black px-5 rounded-xl transition-all shadow-sm active:scale-95 flex items-center gap-1 shrink-0"
            >
              <Plus :size="14" /> Tambah
            </button>
          </div>

          <!-- Tags List -->
          <div class="flex flex-col gap-3">
            <h4 class="text-xs font-black text-slate-700 uppercase tracking-wide">Daftar Tag Saat Ini</h4>
            <div class="flex flex-wrap gap-2 max-h-72 overflow-y-auto p-1.5 border border-slate-100 rounded-2xl">
              <span v-if="tags.length === 0" class="p-8 text-center text-xs text-slate-400 font-bold w-full">
                Tag kosong.
              </span>
              <span 
                v-for="tagName in tags" 
                :key="tagName" 
                class="inline-flex items-center gap-1.5 bg-slate-50 text-slate-600 text-xs font-bold px-3 py-1.5 rounded-full border border-slate-200/60 shadow-sm transition-all hover:bg-rose-50 hover:text-rose-600 hover:border-rose-100 group cursor-pointer"
                @click="deleteTag(tagName)"
                title="Klik untuk menghapus"
              >
                #{{ tagName }}
                <X :size="10" class="text-slate-400 group-hover:text-rose-500 transition-colors" />
              </span>
            </div>
          </div>
        </div>

      </div>

      <!-- TAB 3: LAYOUT & REDAKSI -->
      <div v-if="activeTab === 'layout_authors'" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- TEMPLATE LAYOUT SELECTOR (COL-SPAN-8) -->
        <div class="lg:col-span-8 bg-white rounded-[2.5rem] p-6 md:p-8 border border-slate-100 shadow-sm flex flex-col gap-6">
          <div>
            <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
              <Grid :size="20" class="text-blue-500" /> Desain Template Post Blog
            </h3>
            <p class="text-xs text-slate-500 mt-1">Pilih 1 dari 3 layout template premium untuk halaman detail artikel berita.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            
            <!-- Template 1: Clean Layout -->
            <div 
              @click="selectedTemplate = '1'"
              :class="selectedTemplate === '1' ? 'border-[#264790] ring-4 ring-[#264790]/10 bg-slate-50/20' : 'border-slate-100 hover:border-slate-300'"
              class="border-2 rounded-[2rem] p-4 flex flex-col gap-3.5 transition-all duration-300 cursor-pointer shadow-sm relative group"
            >
              <div v-if="selectedTemplate === '1'" class="absolute -top-2 -right-2 bg-[#264790] text-white p-1.5 rounded-full shadow-md z-10">
                <Check :size="14" :stroke-width="3" />
              </div>
              <div class="aspect-[4/3] rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 border border-slate-100 flex items-center justify-center p-3 relative overflow-hidden group-hover:scale-[1.02] transition-transform">
                <!-- Mini layout mock -->
                <div class="w-full flex flex-col gap-2">
                  <div class="w-full h-8 bg-white rounded-lg shadow-sm"></div>
                  <div class="flex gap-2">
                    <div class="w-1/3 h-16 bg-white rounded-lg shadow-sm"></div>
                    <div class="w-2/3 h-16 bg-white rounded-lg shadow-sm"></div>
                  </div>
                </div>
              </div>
              <div>
                <h4 class="font-extrabold text-slate-800 text-sm">Template 1: Clean Minimalist</h4>
                <p class="text-[10px] font-bold text-slate-400 mt-1">Layout klasik premium dua-kolom dengan sidebar navigasi daftar isi modern.</p>
              </div>
            </div>

            <!-- Template 2: Polaroid Creative -->
            <div 
              @click="selectedTemplate = '2'"
              :class="selectedTemplate === '2' ? 'border-[#264790] ring-4 ring-[#264790]/10 bg-slate-50/20' : 'border-slate-100 hover:border-slate-300'"
              class="border-2 rounded-[2rem] p-4 flex flex-col gap-3.5 transition-all duration-300 cursor-pointer shadow-sm relative group"
            >
              <div v-if="selectedTemplate === '2'" class="absolute -top-2 -right-2 bg-[#264790] text-white p-1.5 rounded-full shadow-md z-10">
                <Check :size="14" :stroke-width="3" />
              </div>
              <div class="aspect-[4/3] rounded-2xl bg-gradient-to-br from-amber-50 to-orange-100 border border-slate-100 flex items-center justify-center p-3 relative overflow-hidden group-hover:scale-[1.02] transition-transform">
                <!-- Mini layout mock -->
                <div class="w-full flex flex-col gap-2 rotate-2">
                  <div class="w-full h-8 bg-amber-400/20 rounded border border-amber-300"></div>
                  <div class="flex gap-2 -rotate-3">
                    <div class="w-1/2 h-16 bg-white p-1 border shadow-md flex flex-col gap-1">
                      <div class="w-full h-10 bg-slate-200"></div>
                      <div class="w-6 h-1 bg-slate-300"></div>
                    </div>
                    <div class="w-1/2 h-16 bg-white p-1 border shadow-md flex flex-col gap-1 rotate-1">
                      <div class="w-full h-10 bg-slate-200"></div>
                      <div class="w-6 h-1 bg-slate-300"></div>
                    </div>
                  </div>
                </div>
              </div>
              <div>
                <h4 class="font-extrabold text-slate-800 text-sm">Template 2: Polaroid Collage</h4>
                <p class="text-[10px] font-bold text-slate-400 mt-1">Layout kreatif dengan efek miring, frame polaroid estetik, serta sentuhan dinamis.</p>
              </div>
            </div>

            <!-- Template 3: Cyberpunk Zebra -->
            <div 
              @click="selectedTemplate = '3'"
              :class="selectedTemplate === '3' ? 'border-[#264790] ring-4 ring-[#264790]/10 bg-slate-50/20' : 'border-slate-100 hover:border-slate-300'"
              class="border-2 rounded-[2rem] p-4 flex flex-col gap-3.5 transition-all duration-300 cursor-pointer shadow-sm relative group"
            >
              <div v-if="selectedTemplate === '3'" class="absolute -top-2 -right-2 bg-[#264790] text-white p-1.5 rounded-full shadow-md z-10">
                <Check :size="14" :stroke-width="3" />
              </div>
              <div class="aspect-[4/3] rounded-2xl bg-gradient-to-br from-slate-900 to-[#1A2B49] border border-slate-100 flex items-center justify-center p-3 relative overflow-hidden group-hover:scale-[1.02] transition-transform">
                <!-- Mini layout mock -->
                <div class="w-full flex flex-col gap-1.5">
                  <div class="w-full h-4 bg-yellow-400 rotate-1 flex items-center justify-center overflow-hidden">
                    <span class="text-[5px] text-black font-black tracking-widest">KNOWLEDGE</span>
                  </div>
                  <div class="flex gap-2">
                    <div class="w-2/3 h-14 bg-[#44A6D9] rounded border-2 border-black flex flex-col justify-end p-1">
                      <div class="w-full h-2 bg-black"></div>
                    </div>
                    <div class="w-1/3 h-14 bg-yellow-400 border-2 border-black rounded"></div>
                  </div>
                </div>
              </div>
              <div>
                <h4 class="font-extrabold text-slate-800 text-sm">Template 3: Cyberpunk Zebra</h4>
                <p class="text-[10px] font-bold text-slate-400 mt-1">Desain berani dengan ornamen garis-garis peringatan zebra hitam-kuning, list neon.</p>
              </div>
            </div>

          </div>
        </div>

        <!-- REDAKSI/AUTHOR PANEL (COL-SPAN-4) -->
        <div class="lg:col-span-4 bg-white rounded-[2.5rem] p-6 md:p-8 border border-slate-100 shadow-sm flex flex-col gap-6">
          <div>
            <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
              <UserPlus :size="20" class="text-blue-500" /> Nama Redaksi
            </h3>
            <p class="text-xs text-slate-500 mt-1">Kelola opsi nama penulis/redaksi berita berita.</p>
          </div>

          <!-- Add Author Form -->
          <div class="flex gap-2">
            <input 
              v-model="newAuthorName" 
              type="text" 
              placeholder="Contoh: Agil Salim" 
              class="flex-1 border-2 border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:border-blue-500 outline-none transition-colors"
              @keyup.enter="addAuthor"
            />
            <button 
              @click="addAuthor" 
              class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-black px-4 rounded-xl transition-all shadow-sm active:scale-95 flex items-center gap-1 shrink-0"
            >
              <Plus :size="14" />
            </button>
          </div>

          <!-- Authors List -->
          <div class="flex flex-col gap-3">
            <h4 class="text-xs font-black text-slate-700 uppercase tracking-wide">Daftar Penulis Redaksi</h4>
            <div class="border border-slate-100 rounded-2xl overflow-hidden divide-y divide-slate-100 max-h-60 overflow-y-auto">
              <div v-if="authors.length === 0" class="p-6 text-center text-xs text-slate-400 font-bold">
                Daftar redaksi kosong.
              </div>
              <div 
                v-for="author in authors" 
                :key="author" 
                class="flex items-center justify-between p-3 hover:bg-slate-50 transition-colors"
              >
                <span class="text-xs font-bold text-slate-700">{{ author }}</span>
                <button @click="deleteAuthor(author)" class="text-slate-400 hover:text-rose-500 p-1">
                  <Trash2 :size="12" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- INSTRUCTOR ACCESS CONTROL PANEL (Admin Only, col-span-12) -->
        <div v-if="isAdmin" class="lg:col-span-12 bg-white rounded-[2.5rem] p-6 md:p-8 border border-slate-100 shadow-sm flex flex-col gap-6 mt-4">
          <div>
            <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
              <ShieldAlert :size="20" class="text-blue-500" /> Izin Akses Blog Settings Instruktur
            </h3>
            <p class="text-xs text-slate-500 mt-1">Pilih instruktur mana saja yang diizinkan untuk mengakses halaman "Blog Settings" ini.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <div v-if="!(blogSettings.all_instructors && blogSettings.all_instructors.length)" class="col-span-full p-8 text-center text-xs text-slate-400 font-bold">
              Tidak ada akun Instruktur yang terdaftar.
            </div>
            
            <div 
              v-else
              v-for="inst in blogSettings.all_instructors" 
              :key="inst.id"
              @click="toggleInstructorAccess(inst.id)"
              :class="[
                allowedInstructors.includes(inst.id)
                  ? 'border-blue-500 ring-2 ring-blue-500/10 bg-blue-50/10'
                  : 'border-slate-100 hover:border-slate-200'
              ]"
              class="border-2 rounded-2xl p-4 flex items-center justify-between cursor-pointer transition-all shadow-sm group select-none"
            >
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-slate-100 text-[#264790] font-black text-xs flex items-center justify-center group-hover:bg-blue-50">
                  {{ inst.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <h4 class="font-extrabold text-slate-800 text-xs">{{ inst.name }}</h4>
                  <p class="text-[10px] font-bold text-slate-400 mt-0.5">{{ inst.email }}</p>
                </div>
              </div>
              <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors" :class="allowedInstructors.includes(inst.id) ? 'bg-blue-600 border-blue-600 text-white' : 'border-slate-300'">
                <Check v-if="allowedInstructors.includes(inst.id)" :size="10" :stroke-width="3" />
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>
  </DashboardWrapper>

  <!-- WYSIWYG ARTICLE EDITOR MODAL (CREATE / EDIT) -->
  <div v-if="isArticleModalOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-slate-100 rounded-[2.5rem] w-full max-w-[95vw] h-[95vh] flex flex-col shadow-2xl overflow-hidden border border-slate-200 animate-in fade-in zoom-in duration-300">
      
      <!-- Modal Header -->
      <div class="p-6 md:px-8 border-b border-slate-200/80 flex items-center justify-between bg-white shadow-sm select-none">
        <div>
          <div class="inline-flex items-center gap-1 text-[10px] font-black text-blue-600 uppercase tracking-widest bg-blue-50 px-2 py-0.5 rounded-md">
            Article Editor
          </div>
          <h3 class="text-lg md:text-xl font-extrabold text-slate-800 mt-1">
            {{ editingArticle ? 'Edit Artikel Berita' : 'Tambah Artikel Berita Baru' }}
          </h3>
        </div>
        <div class="flex items-center gap-3">
          <button 
            @click="isArticleModalOpen = false" 
            class="border-2 border-slate-200 hover:border-slate-300 text-slate-700 font-extrabold px-5 py-2.5 rounded-full text-xs shadow-sm transition-all"
          >
            Batal
          </button>
          <button 
            @click="saveArticle" 
            :disabled="articleForm.processing"
            class="bg-[#264790] hover:bg-[#1A2B49] text-white font-black px-6 py-2.5 rounded-full text-xs shadow-sm transition-all flex items-center gap-1.5 active:scale-95"
          >
            <Save :size="14" /> Simpan & Publikasikan
          </button>
          <div class="w-px h-6 bg-slate-200 mx-1"></div>
          <button 
            @click="isArticleModalOpen = false" 
            class="text-slate-400 hover:text-slate-600 bg-slate-50 border border-slate-200 p-2 rounded-full transition-all active:scale-90"
          >
            <X :size="18" />
          </button>
        </div>
      </div>

      <!-- Modal Body (Split Editor & Sidebar) -->
      <div class="flex-1 overflow-hidden flex flex-col md:flex-row">
        
        <!-- Left Side: Main Editor Area -->
        <div class="flex-1 overflow-y-auto p-6 md:p-8 flex flex-col gap-6">
          
          <!-- Title Input Section -->
          <div class="relative">
            <input 
              v-model="articleForm.title" 
              type="text" 
              required
              placeholder="Masukkan Judul Artikel..." 
              class="w-full border-b-2 border-slate-200 focus:border-[#264790] text-2xl md:text-3xl font-black py-2.5 outline-none transition-colors bg-transparent placeholder-slate-300"
            />
          </div>

          <!-- WYSIWYG Editor Container -->
          <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden flex flex-col flex-1 min-h-[500px]">
            
            <!-- Toolbar -->
            <div class="bg-slate-50 border-b border-slate-200/80 p-3 flex flex-wrap items-center justify-between gap-3 select-none">
              <div class="flex flex-wrap items-center gap-1">
                
                <!-- Format Block Selector -->
                <select 
                  v-model="activeFormat"
                  @change="formatDoc($event.target.value)" 
                  class="bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-600 outline-none cursor-pointer hover:border-slate-300 transition-colors"
                >
                  <option value="">Format Paragraf</option>
                  <option value="p">Paragraf (P)</option>
                  <option value="h2">Heading 2 (H2)</option>
                  <option value="h3">Heading 3 (H3)</option>
                  <option value="h4">Heading 4 (H4)</option>
                  <option value="blockquote">Kutipan (Blockquote)</option>
                </select>

                <!-- Font Size Selector -->
                <select 
                  v-model="activeFontSize"
                  @change="changeFontSize($event.target.value)" 
                  class="bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-600 outline-none cursor-pointer hover:border-slate-300 transition-colors"
                >
                  <option value="">Ukuran Font</option>
                  <option value="12px">12px</option>
                  <option value="14px">14px</option>
                  <option value="16px">16px</option>
                  <option value="18px">18px</option>
                  <option value="20px">20px</option>
                  <option value="24px">24px</option>
                  <option value="30px">30px</option>
                </select>
                
                <div class="w-px h-6 bg-slate-200 mx-1"></div>

                <!-- Text Color -->
                <div class="relative flex items-center gap-1.5 bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-600 hover:border-slate-300 transition-colors">
                  <span class="text-[10px] font-black text-slate-400">TEXT</span>
                  <input type="color" @change="changeTextColor" class="w-4 h-4 rounded-sm border border-slate-200 p-0 cursor-pointer bg-transparent" />
                </div>

                <!-- Highlight Color -->
                <div class="relative flex items-center gap-1.5 bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-600 hover:border-slate-300 transition-colors">
                  <span class="text-[10px] font-black text-slate-400">BG</span>
                  <input type="color" @change="changeHighlightColor" class="w-4 h-4 rounded-sm border border-slate-200 p-0 cursor-pointer bg-transparent" />
                </div>

                <div class="w-px h-6 bg-slate-200 mx-1"></div>

                <!-- Inline Styles -->
                <button 
                  type="button" 
                  @mousedown.prevent="executeCommand('bold')" 
                  class="p-2 hover:bg-slate-200 rounded-lg text-slate-600 hover:text-slate-800 transition-colors"
                  title="Tebal (Bold)"
                >
                  <Bold :size="15" />
                </button>
                <button 
                  type="button" 
                  @mousedown.prevent="executeCommand('italic')" 
                  class="p-2 hover:bg-slate-200 rounded-lg text-slate-600 hover:text-slate-800 transition-colors"
                  title="Miring (Italic)"
                >
                  <Italic :size="15" />
                </button>
                <button 
                  type="button" 
                  @mousedown.prevent="executeCommand('underline')" 
                  class="p-2 hover:bg-slate-200 rounded-lg text-slate-600 hover:text-slate-800 transition-colors"
                  title="Garis Bawah (Underline)"
                >
                  <Underline :size="15" />
                </button>

                <div class="w-px h-6 bg-slate-200 mx-1"></div>

                <!-- Alignment -->
                <button 
                  type="button" 
                  @mousedown.prevent="executeCommand('justifyLeft')" 
                  class="p-2 hover:bg-slate-200 rounded-lg text-slate-600 hover:text-slate-800 transition-colors"
                  title="Rata Kiri"
                >
                  <AlignLeft :size="15" />
                </button>
                <button 
                  type="button" 
                  @mousedown.prevent="executeCommand('justifyCenter')" 
                  class="p-2 hover:bg-slate-200 rounded-lg text-slate-600 hover:text-slate-800 transition-colors"
                  title="Rata Tengah"
                >
                  <AlignCenter :size="15" />
                </button>
                <button 
                  type="button" 
                  @mousedown.prevent="executeCommand('justifyRight')" 
                  class="p-2 hover:bg-slate-200 rounded-lg text-slate-600 hover:text-slate-800 transition-colors"
                  title="Rata Kanan"
                >
                  <AlignRight :size="15" />
                </button>

                <div class="w-px h-6 bg-slate-200 mx-1"></div>

                <!-- Lists -->
                <button 
                  type="button" 
                  @mousedown.prevent="executeCommand('insertUnorderedList')" 
                  class="p-2 hover:bg-slate-200 rounded-lg text-slate-600 hover:text-slate-800 transition-colors"
                  title="Bulleted List"
                >
                  <List :size="15" />
                </button>
                <button 
                  type="button" 
                  @mousedown.prevent="executeCommand('insertOrderedList')" 
                  class="p-2 hover:bg-slate-200 rounded-lg text-slate-600 hover:text-slate-800 transition-colors"
                  title="Numbered List"
                >
                  <ListOrdered :size="15" />
                </button>

                <div class="w-px h-6 bg-slate-200 mx-1"></div>

                <!-- Insert Links / Media / Video -->
                <button 
                  type="button" 
                  @mousedown.prevent="insertLink" 
                  class="p-2 hover:bg-blue-50 text-blue-600 rounded-lg transition-colors flex items-center gap-1 font-bold text-xs"
                  title="Sisipkan Link Eksternal"
                >
                  <Link2 :size="15" /> <span class="hidden sm:inline">Link</span>
                </button>
                <button 
                  type="button" 
                  @mousedown.prevent="insertImage" 
                  class="p-2 hover:bg-emerald-50 text-emerald-600 rounded-lg transition-colors flex items-center gap-1 font-bold text-xs"
                  title="Sisipkan Gambar Baru"
                >
                  <ImageIcon :size="15" /> <span class="hidden sm:inline">Image</span>
                </button>
                <button 
                  type="button" 
                  @mousedown.prevent="insertVideo" 
                  class="p-2 hover:bg-rose-50 text-rose-600 rounded-lg transition-colors flex items-center gap-1 font-bold text-xs"
                  title="Sisipkan Video Player"
                >
                  <VideoIcon :size="15" /> <span class="hidden sm:inline">Video</span>
                </button>

              </div>

              <!-- Visual vs HTML Code Editor Mode -->
              <div class="flex bg-slate-200/60 p-0.5 rounded-lg text-[10px] font-black uppercase">
                <button 
                  type="button" 
                  @click="setEditorMode('visual')"
                  :class="editorMode === 'visual' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                  class="px-3 py-1.5 rounded-md transition-all duration-200"
                >
                  Visual
                </button>
                <button 
                  type="button" 
                  @click="setEditorMode('text')"
                  :class="editorMode === 'text' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                  class="px-3 py-1.5 rounded-md transition-all duration-200"
                >
                  HTML
                </button>
              </div>

            </div>

            <!-- Content Area -->
            <div class="flex-1 p-6 md:p-10 overflow-y-auto">
              
              <!-- Visual Editor -->
              <div 
                v-show="editorMode === 'visual'"
                ref="visualEditorRef"
                contenteditable="true"
                @input="syncContentFromVisual"
                @mouseup="saveSelection"
                @keyup="saveSelection"
                class="prose prose-slate max-w-none min-h-[450px] focus:outline-none text-[#1A2B49] font-medium text-sm md:text-base leading-relaxed bg-white"
                placeholder="Tulis artikel berita pembelajaran Anda di sini..."
              ></div>

              <!-- Raw HTML Editor -->
              <textarea 
                v-show="editorMode === 'text'"
                v-model="articleForm.content"
                class="w-full min-h-[450px] border-0 p-0 text-sm font-semibold focus:ring-0 outline-none font-mono text-slate-800 resize-none"
                placeholder="Tulis artikel dengan format kode HTML..."
              ></textarea>

            </div>

          </div>

        </div>

        <!-- Right Side: Sidebar Settings -->
        <div class="w-full md:w-80 border-t md:border-t-0 md:border-l border-slate-200/80 overflow-y-auto p-6 flex flex-col gap-6 bg-white shrink-0">
          
          <!-- Category Card -->
          <div class="bg-slate-50/60 p-4.5 rounded-2xl border border-slate-200/60 flex flex-col gap-4">
            <h4 class="text-xs font-black text-[#1A2B49] uppercase tracking-wider border-b border-slate-200/60 pb-2">
              Kategori & Penulis
            </h4>
            
            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Pilih Kategori *</label>
              <select 
                v-model="articleForm.category" 
                class="w-full border border-slate-200 bg-white rounded-xl px-3 py-2 text-xs font-semibold focus:border-blue-500 outline-none transition-colors cursor-pointer"
              >
                <option 
                  v-for="cat in categories" 
                  :key="cat.id" 
                  :value="cat.name"
                >
                  {{ cat.parent_id ? '↳ ' + cat.name : cat.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Penulis Redaksi *</label>
              <select 
                v-model="articleForm.author_name" 
                class="w-full border border-slate-200 bg-white rounded-xl px-3 py-2 text-xs font-semibold focus:border-blue-500 outline-none transition-colors cursor-pointer"
              >
                <option 
                  v-for="author in authors" 
                  :key="author" 
                  :value="author"
                >
                  {{ author }}
                </option>
              </select>
            </div>
          </div>

          <!-- Cover Image Card -->
          <div class="bg-slate-50/60 p-4.5 rounded-2xl border border-slate-200/60 flex flex-col gap-3">
            <h4 class="text-xs font-black text-[#1A2B49] uppercase tracking-wider border-b border-slate-200/60 pb-2">
              Gambar Sampul (Cover)
            </h4>
            
            <div class="flex bg-slate-200/60 p-0.5 rounded-lg text-[9px] font-black uppercase">
              <button 
                type="button" 
                @click="coverSourceMode = 'upload'"
                :class="coverSourceMode === 'upload' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                class="flex-1 py-1 rounded-md transition-all duration-200"
              >
                Upload File
              </button>
              <button 
                type="button" 
                @click="coverSourceMode = 'url'"
                :class="coverSourceMode === 'url' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                class="flex-1 py-1 rounded-md transition-all duration-200"
              >
                External URL
              </button>
            </div>

            <!-- Upload File Mode -->
            <div v-show="coverSourceMode === 'upload'" class="flex flex-col gap-2">
              <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Upload dari Perangkat</label>
              <div 
                @click="$refs.coverFileInput.click()"
                class="border-2 border-dashed border-slate-200 hover:border-blue-400 rounded-xl p-4 text-center cursor-pointer transition-colors bg-white/50"
              >
                <Upload :size="20" class="mx-auto text-slate-400 mb-1" />
                <span class="text-[10px] font-bold text-slate-500">Pilih gambar (Max 5MB)</span>
                <input 
                  type="file" 
                  ref="coverFileInput" 
                  @change="handleCoverUpload" 
                  class="hidden" 
                  accept="image/*" 
                />
              </div>
            </div>

            <!-- External URL Mode -->
            <div v-show="coverSourceMode === 'url'">
              <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">URL Gambar *</label>
              <input 
                v-model="articleForm.image" 
                type="text" 
                placeholder="/images/news-placeholder.jpg" 
                class="w-full border border-slate-200 bg-white rounded-xl px-3 py-2 text-xs font-semibold focus:border-blue-500 outline-none transition-colors"
              />
            </div>

            <div class="mt-2 rounded-xl overflow-hidden border border-slate-200 aspect-video bg-slate-100 flex items-center justify-center relative group">
              <img 
                v-if="articleForm.image" 
                :src="articleForm.image" 
                class="w-full h-full object-cover" 
                alt="Preview Cover" 
              />
              <span v-else class="text-[10px] font-bold text-slate-400">Belum ada cover</span>
            </div>
          </div>

          <!-- Excerpt Card -->
          <div class="bg-slate-50/60 p-4.5 rounded-2xl border border-slate-200/60 flex flex-col gap-3">
            <h4 class="text-xs font-black text-[#1A2B49] uppercase tracking-wider border-b border-slate-200/60 pb-2">
              Kutipan / Excerpt
            </h4>
            <textarea 
              v-model="articleForm.excerpt" 
              rows="3" 
              placeholder="Tulis kutipan artikel singkat..."
              class="w-full border border-slate-200 bg-white rounded-xl px-3 py-2 text-xs font-semibold focus:border-blue-500 outline-none transition-colors resize-none"
            ></textarea>
          </div>

          <!-- Tags Card -->
          <div class="bg-slate-50/60 p-4.5 rounded-2xl border border-slate-200/60 flex flex-col gap-3">
            <h4 class="text-xs font-black text-[#1A2B49] uppercase tracking-wider border-b border-slate-200/60 pb-2">
              Tag Berita
            </h4>
            <div class="flex flex-col gap-2 max-h-40 overflow-y-auto">
              <span v-if="tags.length === 0" class="text-xs font-bold text-slate-400">Tidak ada tag</span>
              <label 
                v-for="tagName in tags" 
                :key="tagName"
                class="flex items-center gap-2 cursor-pointer select-none group"
              >
                <input 
                  type="checkbox" 
                  :checked="articleForm.tags.includes(tagName)"
                  @change="toggleTagSelection(tagName)"
                  class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-3.5 h-3.5"
                />
                <span class="text-xs font-semibold text-slate-600 group-hover:text-slate-800 transition-colors">#{{ tagName }}</span>
              </label>
            </div>
          </div>

        </div>

      </div>

    </div>
  </div>
</template>

<style scoped>
/* Scoped custom styling for the WYSIWYG contenteditable editor */
:deep(.prose) {
  min-height: 450px;
  font-family: 'Montserrat', sans-serif !important;
}
:deep(.prose *) {
  font-family: 'Montserrat', sans-serif !important;
}
:deep(.prose:empty::before) {
  content: attr(placeholder);
  color: #cbd5e1;
  font-weight: 500;
  pointer-events: none;
}
:deep(.prose figure) {
  margin: 1.5rem 0;
  border-radius: 1rem;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  background-color: #f8fafc;
  display: inline-block;
  width: 100%;
}
:deep(.prose figure img) {
  margin: 0 auto !important;
  max-height: 400px;
  object-fit: cover;
}
:deep(.prose figure figcaption) {
  background-color: #f1f5f9;
  padding: 0.5rem 1rem;
  text-align: center;
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 700;
  border-top: 1px solid #e2e8f0;
}
:deep(.prose .video-container) {
  margin: 1.5rem 0;
  aspect-ratio: 16 / 9;
  border-radius: 1rem;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  background-color: #000;
}
</style>
