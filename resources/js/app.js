import './bootstrap'
import './sidebar-init'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'
import axios from 'axios'

// 🧩 1. Add CSRF setup before createInertiaApp
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

const token = document
  .querySelector('meta[name="csrf-token"]')
  ?.getAttribute('content')

if (token) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = token
} else {
  console.error('⚠️ CSRF token not found in meta tag')
}

// 🧩 2. Then continue as normal
createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
    return pages[`./Pages/${name}.vue`]
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue)
      .mount(el)
  },
})
