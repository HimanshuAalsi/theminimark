<script setup lang="ts">
import CharacterCount from '@tiptap/extension-character-count'
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight'
import Highlight from '@tiptap/extension-highlight'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'
import TextAlign from '@tiptap/extension-text-align'
import Underline from '@tiptap/extension-underline'
import StarterKit from '@tiptap/starter-kit'
import { Editor, EditorContent } from '@tiptap/vue-3'
import { common, createLowlight } from 'lowlight'
import {
  AlignCenter,
  AlignLeft,
  AlignRight,
  Bold,
  Code,
  Columns3,
  Heading1,
  Heading2,
  Heading3,
  Highlighter,
  ImagePlus,
  Italic,
  Link2,
  List,
  ListOrdered,
  Minus,
  Quote,
  Redo2,
  Strikethrough,
  Underline as UnderlineIcon,
  Undo2,
} from 'lucide-vue-next'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { LayoutBlock, LayoutColumn } from '@/admin/tiptap/LayoutBlock'
import { ResizableImage, type BlogImageSize } from '@/admin/tiptap/ResizableImage'

const props = withDefaults(
  defineProps<{
    modelValue: string
    placeholder?: string
    onUploadImage?: (file: File) => Promise<string | null>
  }>(),
  { placeholder: 'Write your article…' },
)

const emit = defineEmits<{ 'update:modelValue': [string] }>()

const lowlight = createLowlight(common)
const editor = ref<Editor | null>(null)
const imageInput = ref<HTMLInputElement | null>(null)
const uploading = ref(false)

const imageSize = ref<BlogImageSize>('lg')
const layoutCols = ref(2)

const imageSizes: { id: BlogImageSize; label: string }[] = [
  { id: 'sm', label: 'S' },
  { id: 'md', label: 'M' },
  { id: 'lg', label: 'L' },
  { id: 'full', label: 'Full' },
]

const imageActive = computed(() => editor.value?.isActive('image') ?? false)

onMounted(() => {
  editor.value = new Editor({
    extensions: [
      StarterKit.configure({
        codeBlock: false,
        heading: { levels: [1, 2, 3] },
      }),
      Underline,
      Highlight.configure({ multicolor: false }),
      Link.configure({ openOnClick: false, autolink: true, linkOnPaste: true }),
      ResizableImage.configure({ inline: false, allowBase64: false }),
      LayoutBlock,
      LayoutColumn,
      Placeholder.configure({ placeholder: props.placeholder }),
      TextAlign.configure({ types: ['heading', 'paragraph'] }),
      CodeBlockLowlight.configure({ lowlight }),
      CharacterCount.configure(),
    ],
    content: props.modelValue || '',
    editorProps: {
      attributes: {
        class: 'blog-editor__content',
      },
    },
    onUpdate: ({ editor: ed }) => {
      emit('update:modelValue', ed.getHTML())
    },
  })
})

watch(
  () => props.modelValue,
  (html) => {
    const ed = editor.value
    if (!ed || ed.getHTML() === html) return
    ed.commands.setContent(html || '', { emitUpdate: false })
  },
)

onBeforeUnmount(() => {
  editor.value?.destroy()
})

function cmd(fn: () => boolean) {
  fn()
}

function setLink() {
  const ed = editor.value
  if (!ed) return
  const prev = ed.getAttributes('link').href as string | undefined
  const url = window.prompt('Link URL', prev ?? 'https://')
  if (url === null) return
  if (url === '') {
    ed.chain().focus().extendMarkRange('link').unsetLink().run()
    return
  }
  ed.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}

async function onImageSelected(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  ;(e.target as HTMLInputElement).value = ''
  if (!file || !editor.value) return

  if (props.onUploadImage) {
    uploading.value = true
    try {
      const url = await props.onUploadImage(file)
      if (url) {
        editor.value
          .chain()
          .focus()
          .setImage({ src: url, alt: file.name })
          .updateAttributes('image', { size: imageSize.value })
          .run()
      }
    } finally {
      uploading.value = false
    }
    return
  }

  const url = window.prompt('Image URL')
  if (url) editor.value.chain().focus().setImage({ src: url }).run()
}

const charCount = () => editor.value?.storage.characterCount.characters() ?? 0
const wordCount = () => editor.value?.storage.characterCount.words() ?? 0

function insertLayout(cols: number) {
  const ed = editor.value
  if (!ed) return
  const n = Math.min(4, Math.max(1, cols))
  const columns = Array.from({ length: n }, () => ({
    type: 'layoutColumn',
    content: [{ type: 'paragraph' }],
  }))
  ed.chain()
    .focus()
    .insertContent({ type: 'layoutBlock', attrs: { cols: n }, content: columns })
    .run()
}

function applyImageSize(size: BlogImageSize) {
  imageSize.value = size
  editor.value?.chain().focus().updateAttributes('image', { size }).run()
}
</script>

<template>
  <div class="blog-editor">
    <div v-if="editor" class="blog-editor__toolbar" role="toolbar" aria-label="Formatting">
      <div class="blog-editor__group">
        <button type="button" title="Undo" @click="cmd(() => editor!.chain().focus().undo().run())">
          <Undo2 :size="15" />
        </button>
        <button type="button" title="Redo" @click="cmd(() => editor!.chain().focus().redo().run())">
          <Redo2 :size="15" />
        </button>
      </div>
      <div class="blog-editor__group">
        <button
          type="button"
          title="Heading 1"
          :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }"
          @click="cmd(() => editor!.chain().focus().toggleHeading({ level: 1 }).run())"
        >
          <Heading1 :size="15" />
        </button>
        <button
          type="button"
          title="Heading 2"
          :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
          @click="cmd(() => editor!.chain().focus().toggleHeading({ level: 2 }).run())"
        >
          <Heading2 :size="15" />
        </button>
        <button
          type="button"
          title="Heading 3"
          :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }"
          @click="cmd(() => editor!.chain().focus().toggleHeading({ level: 3 }).run())"
        >
          <Heading3 :size="15" />
        </button>
      </div>
      <div class="blog-editor__group">
        <button
          type="button"
          title="Bold"
          :class="{ 'is-active': editor.isActive('bold') }"
          @click="cmd(() => editor!.chain().focus().toggleBold().run())"
        >
          <Bold :size="15" />
        </button>
        <button
          type="button"
          title="Italic"
          :class="{ 'is-active': editor.isActive('italic') }"
          @click="cmd(() => editor!.chain().focus().toggleItalic().run())"
        >
          <Italic :size="15" />
        </button>
        <button
          type="button"
          title="Underline"
          :class="{ 'is-active': editor.isActive('underline') }"
          @click="cmd(() => editor!.chain().focus().toggleUnderline().run())"
        >
          <UnderlineIcon :size="15" />
        </button>
        <button
          type="button"
          title="Strikethrough"
          :class="{ 'is-active': editor.isActive('strike') }"
          @click="cmd(() => editor!.chain().focus().toggleStrike().run())"
        >
          <Strikethrough :size="15" />
        </button>
        <button
          type="button"
          title="Highlight"
          :class="{ 'is-active': editor.isActive('highlight') }"
          @click="cmd(() => editor!.chain().focus().toggleHighlight().run())"
        >
          <Highlighter :size="15" />
        </button>
        <button
          type="button"
          title="Inline code"
          :class="{ 'is-active': editor.isActive('code') }"
          @click="cmd(() => editor!.chain().focus().toggleCode().run())"
        >
          <Code :size="15" />
        </button>
      </div>
      <div class="blog-editor__group">
        <button
          type="button"
          title="Bullet list"
          :class="{ 'is-active': editor.isActive('bulletList') }"
          @click="cmd(() => editor!.chain().focus().toggleBulletList().run())"
        >
          <List :size="15" />
        </button>
        <button
          type="button"
          title="Numbered list"
          :class="{ 'is-active': editor.isActive('orderedList') }"
          @click="cmd(() => editor!.chain().focus().toggleOrderedList().run())"
        >
          <ListOrdered :size="15" />
        </button>
        <button
          type="button"
          title="Quote"
          :class="{ 'is-active': editor.isActive('blockquote') }"
          @click="cmd(() => editor!.chain().focus().toggleBlockquote().run())"
        >
          <Quote :size="15" />
        </button>
        <button
          type="button"
          title="Code block"
          :class="{ 'is-active': editor.isActive('codeBlock') }"
          @click="cmd(() => editor!.chain().focus().toggleCodeBlock().run())"
        >
          <Code :size="15" />
        </button>
        <button type="button" title="Horizontal rule" @click="cmd(() => editor!.chain().focus().setHorizontalRule().run())">
          <Minus :size="15" />
        </button>
      </div>
      <div class="blog-editor__group">
        <button
          type="button"
          title="Align left"
          :class="{ 'is-active': editor.isActive({ textAlign: 'left' }) }"
          @click="cmd(() => editor!.chain().focus().setTextAlign('left').run())"
        >
          <AlignLeft :size="15" />
        </button>
        <button
          type="button"
          title="Align center"
          :class="{ 'is-active': editor.isActive({ textAlign: 'center' }) }"
          @click="cmd(() => editor!.chain().focus().setTextAlign('center').run())"
        >
          <AlignCenter :size="15" />
        </button>
        <button
          type="button"
          title="Align right"
          :class="{ 'is-active': editor.isActive({ textAlign: 'right' }) }"
          @click="cmd(() => editor!.chain().focus().setTextAlign('right').run())"
        >
          <AlignRight :size="15" />
        </button>
      </div>
      <div class="blog-editor__group blog-editor__group--layout">
        <Columns3 :size="15" aria-hidden="true" />
        <select v-model.number="layoutCols" class="blog-editor__select" title="Columns per block (12-grid)">
          <option :value="1">1 col</option>
          <option :value="2">2 cols</option>
          <option :value="3">3 cols</option>
          <option :value="4">4 cols</option>
        </select>
        <button type="button" title="Insert layout block" @click="insertLayout(layoutCols)">+ Block</button>
      </div>
      <div v-if="imageActive" class="blog-editor__group blog-editor__group--sizes">
        <span class="blog-editor__mini-label">Image</span>
        <button
          v-for="s in imageSizes"
          :key="s.id"
          type="button"
          :class="{ 'is-active': editor.isActive('image', { size: s.id }) }"
          @click="applyImageSize(s.id)"
        >
          {{ s.label }}
        </button>
      </div>
      <div class="blog-editor__group">
        <button type="button" title="Link" :class="{ 'is-active': editor.isActive('link') }" @click="setLink">
          <Link2 :size="15" />
        </button>
        <button type="button" title="Insert image" :disabled="uploading" @click="imageInput?.click()">
          <ImagePlus :size="15" />
        </button>
        <input ref="imageInput" type="file" accept="image/*" hidden @change="onImageSelected" />
      </div>
    </div>

    <EditorContent v-if="editor" :editor="editor as Editor" class="blog-editor__body" />

    <p v-if="editor" class="blog-editor__stats">
      {{ wordCount() }} words · {{ charCount() }} characters
      <span v-if="uploading"> · Uploading image…</span>
    </p>
  </div>
</template>

<style scoped>
.blog-editor {
  border: 1px solid var(--admin-border, #e2e8f0);
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
}

.blog-editor__toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  padding: 0.55rem 0.65rem;
  border-bottom: 1px solid var(--admin-border, #e2e8f0);
  background: #f8fafc;
}

.blog-editor__group {
  display: flex;
  gap: 0.15rem;
  padding-right: 0.35rem;
  border-right: 1px solid var(--admin-border, #e2e8f0);
}

.blog-editor__group:last-child {
  border-right: none;
}

.blog-editor__toolbar button {
  display: grid;
  place-items: center;
  width: 1.85rem;
  height: 1.85rem;
  border: none;
  border-radius: 6px;
  background: transparent;
  color: var(--admin-ink, #0f172a);
  cursor: pointer;
}

.blog-editor__toolbar button:hover:not(:disabled) {
  background: rgba(45, 92, 82, 0.08);
  color: var(--admin-accent, #2d5c52);
}

.blog-editor__toolbar button.is-active {
  background: var(--admin-accent, #2d5c52);
  color: #fff;
}

.blog-editor__toolbar button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.blog-editor__body {
  min-height: 22rem;
}

.blog-editor__body :deep(.ProseMirror) {
  min-height: 22rem;
  padding: 1rem 1.15rem;
  outline: none;
  font-size: 0.9375rem;
  line-height: 1.65;
  color: var(--admin-ink, #0f172a);
}

.blog-editor__body :deep(.ProseMirror p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  float: left;
  color: #94a3b8;
  pointer-events: none;
  height: 0;
}

.blog-editor__body :deep(.ProseMirror h1) {
  font-size: 1.75rem;
  margin: 1.25rem 0 0.65rem;
}

.blog-editor__body :deep(.ProseMirror h2) {
  font-size: 1.35rem;
  margin: 1.1rem 0 0.55rem;
}

.blog-editor__body :deep(.ProseMirror h3) {
  font-size: 1.1rem;
  margin: 0.95rem 0 0.45rem;
}

.blog-editor__body :deep(.ProseMirror ul),
.blog-editor__body :deep(.ProseMirror ol) {
  padding-left: 1.35rem;
  margin: 0.65rem 0;
}

.blog-editor__body :deep(.ProseMirror blockquote) {
  margin: 0.85rem 0;
  padding: 0.5rem 0 0.5rem 1rem;
  border-left: 3px solid var(--admin-accent, #2d5c52);
  color: #475569;
}

.blog-editor__body :deep(.ProseMirror pre) {
  background: #0f172a;
  color: #e2e8f0;
  border-radius: 8px;
  padding: 0.85rem 1rem;
  overflow-x: auto;
  font-size: 0.8125rem;
}

.blog-editor__group--layout {
  align-items: center;
  gap: 0.25rem;
  padding-left: 0.25rem;
}

.blog-editor__select {
  border: 1px solid var(--admin-border, #e2e8f0);
  border-radius: 6px;
  font-size: 0.6875rem;
  font-weight: 600;
  padding: 0.2rem 0.35rem;
  background: #fff;
}

.blog-editor__group--layout button {
  width: auto;
  padding: 0 0.45rem;
  font-size: 0.6875rem;
  font-weight: 700;
}

.blog-editor__group--sizes .blog-editor__mini-label {
  font-size: 0.625rem;
  font-weight: 700;
  color: var(--admin-muted);
  padding: 0 0.15rem;
}

.blog-editor__group--sizes button {
  width: auto;
  min-width: 1.85rem;
  font-size: 0.625rem;
  font-weight: 800;
}

.blog-editor__body :deep(.ProseMirror img) {
  max-width: 100%;
  height: auto;
}

.blog-editor__body :deep(.ProseMirror mark) {
  background: #fef08a;
  padding: 0 0.15rem;
}

.blog-editor__body :deep(.ProseMirror a) {
  color: var(--admin-accent, #2d5c52);
  text-decoration: underline;
}

.blog-editor__stats {
  margin: 0;
  padding: 0.45rem 0.85rem;
  font-size: 0.6875rem;
  color: var(--admin-muted, #64748b);
  border-top: 1px solid var(--admin-border, #e2e8f0);
  background: #f8fafc;
}
</style>
