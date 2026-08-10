import { ref, type Ref } from 'vue'

export interface PhotoTransform {
  zoom: number
  posX: number
  posY: number
}

export const PHOTO_ZOOM_MIN = 0.2
export const PHOTO_ZOOM_MAX = 4

export function clampPhotoZoom(z: number): number {
  return Math.min(PHOTO_ZOOM_MAX, Math.max(PHOTO_ZOOM_MIN, z))
}

export function defaultPhotoTransform(): PhotoTransform {
  return { zoom: 1, posX: 50, posY: 50 }
}

export function photoCropStyle(t: PhotoTransform) {
  const tx = (t.posX - 50) * 2.8
  const ty = (t.posY - 50) * 2.8
  return {
    transform: `translate(${tx}%, ${ty}%) scale(${t.zoom})`,
  }
}

export function usePhotoEditor(
  transform: Ref<PhotoTransform>,
  onChange: (t: PhotoTransform) => void,
) {
  const dragging = ref(false)
  let startX = 0
  let startY = 0
  let startPosX = 0
  let startPosY = 0

  function patch(p: Partial<PhotoTransform>) {
    onChange({ ...transform.value, ...p })
  }

  function onPointerDown(e: PointerEvent) {
    if (e.button !== 0) return
    dragging.value = true
    startX = e.clientX
    startY = e.clientY
    startPosX = transform.value.posX
    startPosY = transform.value.posY
    ;(e.currentTarget as HTMLElement).setPointerCapture(e.pointerId)
  }

  function onPointerMove(e: PointerEvent) {
    if (!dragging.value) return
    const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
    const dx = ((e.clientX - startX) / rect.width) * 110
    const dy = ((e.clientY - startY) / rect.height) * 110
    patch({
      posX: Math.min(100, Math.max(0, startPosX + dx)),
      posY: Math.min(100, Math.max(0, startPosY + dy)),
    })
  }

  function onPointerUp(e: PointerEvent) {
    dragging.value = false
    try {
      ;(e.currentTarget as HTMLElement).releasePointerCapture(e.pointerId)
    } catch {
      /* capture already released */
    }
  }

  function onWheel(e: WheelEvent) {
    const delta = e.deltaY > 0 ? -0.06 : 0.06
    patch({ zoom: clampPhotoZoom(transform.value.zoom + delta) })
  }

  function zoomBy(delta: number) {
    patch({ zoom: clampPhotoZoom(transform.value.zoom + delta) })
  }

  function reset() {
    onChange(defaultPhotoTransform())
  }

  return {
    dragging,
    onPointerDown,
    onPointerMove,
    onPointerUp,
    onWheel,
    zoomBy,
    reset,
  }
}
