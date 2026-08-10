import { createPinia } from 'pinia'
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { useThemeStore } from './stores/theme'
import './style.css'
import './styles/blog-prose.css'
import './admin/styles/admin.css'

const pinia = createPinia()
const app = createApp(App)
app.use(pinia)
app.use(router)
useThemeStore(pinia).init()
app.mount('#app')
