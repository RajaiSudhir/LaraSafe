// resources/js/sidebar-init.js
import { router } from '@inertiajs/vue3'

const SELECTOR_TOGGLE = '.js-side-toggle' // use this on your header/menu X buttons
const SIDEBAR_SELECTOR = '.left-sidebar'  // your sidebar element
const WRAPPER_ID = 'main-wrapper'

function openSidebar(wrapper) {
  wrapper.classList.add('show-sidebar')
  document.body.classList.add('sidebar-open')
  // create overlay if not exists
  if (!document.getElementById('sidebar-overlay')) {
    const ov = document.createElement('div')
    ov.id = 'sidebar-overlay'
    ov.className = 'sidebar-overlay'
    document.body.appendChild(ov)
  }
}

function closeSidebar(wrapper) {
  wrapper.classList.remove('show-sidebar')
  document.body.classList.remove('sidebar-open')
  const ov = document.getElementById('sidebar-overlay')
  if (ov) ov.remove()
}

// Delegated clicks
document.addEventListener('click', (e) => {
  const wrapper = document.getElementById(WRAPPER_ID)
  if (!wrapper) return

  // Toggle if a toggle button is clicked
  const toggleBtn = e.target.closest(SELECTOR_TOGGLE)
  if (toggleBtn) {
    e.preventDefault()
    if (wrapper.classList.contains('show-sidebar')) {
      closeSidebar(wrapper)
    } else {
      openSidebar(wrapper)
    }
    return
  }

  // Close when clicking outside the sidebar while open
  if (wrapper.classList.contains('show-sidebar')) {
    const sidebar = document.querySelector(SIDEBAR_SELECTOR)
    const clickInsideSidebar = sidebar && (sidebar === e.target || sidebar.contains(e.target))
    if (!clickInsideSidebar) {
      closeSidebar(wrapper)
    }
  }
})

// ESC to close
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    const wrapper = document.getElementById(WRAPPER_ID)
    if (wrapper?.classList.contains('show-sidebar')) closeSidebar(wrapper)
  }
})

// Ensure closed on route changes (Inertia SPA)
router.on('navigate', () => {
  const wrapper = document.getElementById(WRAPPER_ID)
  if (wrapper?.classList.contains('show-sidebar')) closeSidebar(wrapper)
})
