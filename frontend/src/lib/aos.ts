type AosApi = Aos.Aos

let aos: AosApi | null = null
let ready = false

function motionOk(): boolean {
  return !window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

/** Stagger helper — cap delay so long lists stay snappy */
export function aosDelay(index: number, step = 75, max = 375): number {
  return Math.min(index * step, max)
}

async function loadAos(): Promise<AosApi> {
  const mod = await import('aos')
  await import('aos/dist/aos.css')
  return ('default' in mod ? mod.default : mod) as AosApi
}

export async function initAos(): Promise<void> {
  if (ready || !motionOk()) return
  aos = await loadAos()
  aos.init({
    once: true,
    offset: 72,
    duration: 650,
    easing: 'ease-out-cubic',
    delay: 0,
    anchorPlacement: 'top-bottom',
    disable: false,
  })
  ready = true
}

export function refreshAos(): void {
  if (!ready || !aos) return
  aos.refresh()
}

export function destroyAos(): void {
  if (!ready || !aos) return
  aos.refreshHard()
  aos = null
  ready = false
}
