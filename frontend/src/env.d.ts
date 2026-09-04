/// <reference types="vite/client" />

declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<{}, {}, any>
  export default component
}

interface ImportMetaEnv {
  /** URL base de la API. Única definición de dónde vive el backend. */
  readonly VITE_API_URL: string
  readonly VITE_APP_NAME: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
