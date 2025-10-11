import { router } from '@inertiajs/vue3'

document.addEventListener('click', (e) => {
  const btn = e.target.closest('.js-side-toggle')
  if (!btn) return
  const wrapper = document.getElementById('main-wrapper')
  if (!wrapper) return
  e.preventDefault()
  wrapper.classList.toggle('show-sidebar')
  document.body.classList.toggle('sidebar-open', wrapper.classList.contains('show-sidebar'))
})

router.on('navigate', () => {})
