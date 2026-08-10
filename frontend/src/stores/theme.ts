import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

const STORAGE_KEY = 'tm_theme'

export type ThemeMode = 'light' | 'dark' | 'system'

const THEME_COLORS = {
  light: '#f6f4f0',
  dark: '#0f0e0d',
} as const

function systemPrefersDark(): boolean {
  return typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches
}

function resolveTheme(mode: ThemeMode): 'light' | 'dark' {
  if (mode === 'system') return systemPrefersDark() ? 'dark' : 'light'
  return mode
}

function setMetaThemeColor(theme: 'light' | 'dark') {
  const color = THEME_COLORS[theme]
  let meta = document.querySelector('meta[name="theme-color"]')
  if (!meta) {
    meta = document.createElement('meta')
    meta.setAttribute('name', 'theme-color')
    document.head.appendChild(meta)
  }
  meta.setAttribute('content', color)
}

async function syncNativeChrome(theme: 'light' | 'dark') {
  try {
    const { Capacitor } = await import('@capacitor/core')
    if (!Capacitor.isNativePlatform()) return
    const { StatusBar, Style } = await import('@capacitor/status-bar')
    const isDark = theme === 'dark'
    await StatusBar.setStyle({ style: isDark ? Style.Dark : Style.Light })
    await StatusBar.setBackgroundColor({ color: THEME_COLORS[theme] })
  } catch {
    /* optional */
  }
}

export const useThemeStore = defineStore('theme', () => {
  const mode = ref<ThemeMode>(
    (typeof localStorage !== 'undefined' && (localStorage.getItem(STORAGE_KEY) as ThemeMode)) || 'system',
  )
  const resolved = ref<'light' | 'dark'>(
    typeof document !== 'undefined'
      ? resolveTheme(mode.value)
      : 'light',
  )

  function apply() {
    const next = resolveTheme(mode.value)
    const root = document.documentElement

    root.classList.add('theme-transition')
    window.setTimeout(() => root.classList.remove('theme-transition'), 400)

    resolved.value = next
    root.setAttribute('data-theme', next)
    root.style.colorScheme = next
    setMetaThemeColor(next)
    void syncNativeChrome(next)
  }

  function setMode(next: ThemeMode) {
    mode.value = next
    localStorage.setItem(STORAGE_KEY, next)
    apply()
  }

  function toggle() {
    setMode(resolved.value === 'dark' ? 'light' : 'dark')
  }

  const isDark = computed(() => resolved.value === 'dark')

  function init() {
    apply()
    const mq = window.matchMedia('(prefers-color-scheme: dark)')
    mq.addEventListener('change', () => {
      if (mode.value === 'system') apply()
    })
  }

  return { mode, resolved, isDark, setMode, toggle, init }
})
