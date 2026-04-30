/// <reference types="vite/client" />

interface ImportMetaEnv {
  /** Base URL for PHP API (no trailing slash). Empty = same origin. */
  readonly VITE_API_BASE_URL?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
