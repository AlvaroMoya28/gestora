import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import { router } from './router'
import { bindSessionExpiry } from './stores/auth'
import './assets/styles/base.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)

// Si la renovación del token falla, el interceptor cierra la sesión y aquí se
// decide a dónde llevar al usuario. Evita que services y router se importen entre sí.
bindSessionExpiry(() => router.push({ name: 'login' }))

app.mount('#app')
