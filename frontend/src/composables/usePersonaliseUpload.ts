import { onBeforeUnmount, ref } from 'vue'
import { uploadPersonalisePhoto } from '@/lib/personaliseUpload'

export interface UploadedPhoto {
  path: string
  url: string
}

export function usePersonaliseUpload() {
  const previewUrl = ref<string | null>(null)
  const photoPath = ref<string | null>(null)
  const localBlobUrl = ref<string | null>(null)
  const uploading = ref(false)
  const error = ref('')
  const dragOver = ref(false)

  function revokeLocal() {
    if (localBlobUrl.value) {
      URL.revokeObjectURL(localBlobUrl.value)
      localBlobUrl.value = null
    }
  }

  onBeforeUnmount(revokeLocal)

  function clear() {
    revokeLocal()
    previewUrl.value = null
    photoPath.value = null
    error.value = ''
  }

  async function acceptFile(file: File | undefined | null): Promise<UploadedPhoto | null> {
    error.value = ''
    if (!file) return null
    if (!file.type.startsWith('image/')) {
      error.value = 'Please choose a JPG, PNG, or WebP image.'
      return null
    }
    if (file.size > 12 * 1024 * 1024) {
      error.value = 'Image must be under 12 MB.'
      return null
    }

    revokeLocal()
    localBlobUrl.value = URL.createObjectURL(file)
    previewUrl.value = localBlobUrl.value
    uploading.value = true
    try {
      const uploaded = await uploadPersonalisePhoto(file)
      photoPath.value = uploaded.path
      previewUrl.value = uploaded.url
      revokeLocal()
      return { path: uploaded.path, url: uploaded.url }
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Upload failed. Try again.'
      photoPath.value = null
      return null
    } finally {
      uploading.value = false
    }
  }

  function onFileInput(e: Event) {
    const input = e.target as HTMLInputElement
    void acceptFile(input.files?.[0])
    input.value = ''
  }

  function onDrop(e: DragEvent) {
    dragOver.value = false
    void acceptFile(e.dataTransfer?.files?.[0])
  }

  const ready = () => Boolean(previewUrl.value && photoPath.value)

  return {
    previewUrl,
    photoPath,
    uploading,
    error,
    dragOver,
    clear,
    acceptFile,
    onFileInput,
    onDrop,
    ready,
  }
}
