<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

// Load SweetAlert2 from CDN
const Swal = window.Swal

defineOptions({
  layout: MainLayout
})

// ✅ Correctly define props
const props = defineProps({
  project: {
    type: Object,
    required: true
  }
})

// ✅ Reactive form
const form = useForm({
  name: '',
  description: '',
  path: ''
})

// Track which fields are touched
const touched = ref({
  name: false,
  description: false,
  path: false
})

// ✅ Pre-fill form when component loads
onMounted(() => {
  if (props.project) {
    form.name = props.project.name
    form.description = props.project.description || ''
    form.path = props.project.path
  }
})

// ✅ Validation logic
const errors = computed(() => ({
  name: touched.value.name && !form.name ? 'Project name is required' : '',
  description: '', // Description optional
  path: touched.value.path && !form.path ? 'Project path is required' : ''
}))

const isValid = computed(() =>
  !Object.values(errors.value).some(error => error !== '')
)

// ✅ Handle form submission
const handleSubmit = () => {
  Object.keys(touched.value).forEach(key => (touched.value[key] = true))

  if (!isValid.value) {
    const errorList = Object.values(errors.value)
      .filter(error => error !== '')
      .map(error => `<li>${error}</li>`)
      .join('')
    Swal.fire({
      icon: 'error',
      title: 'Validation Error',
      html: `<ul style="text-align:left;">${errorList}</ul>`
    })
    return
  }

  form.put(`/projects/update-project/${props.project.id}`, {
    onSuccess: (page) => {
      const successMessage = page.props.flash?.success
      Swal.fire({
        icon: 'success',
        title: 'Success',
        text: successMessage || 'Project updated successfully!'
      }).then(() => {
        router.visit('/projects/manage-projects')
      })
      form.reset()
      Object.keys(touched.value).forEach(key => (touched.value[key] = false))
    },
    onError: (serverErrors) => {
      const errorList = Object.values(serverErrors)
        .map(error => `<li>${error}</li>`)
        .join('')
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        html: `<ul style="text-align:left;">${errorList}</ul>`
      })
    }
  })
}
</script>

<template>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title fw-semibold mb-4">Edit Project</h5>
      <div class="card">
        <div class="card-body">
          <form @submit.prevent="handleSubmit">
            <!-- Project Name -->
            <div class="mb-3">
              <label for="projectName" class="form-label">Project Name</label>
              <input
                type="text"
                class="form-control"
                id="projectName"
                v-model="form.name"
                @blur="touched.name = true"
                :class="{ 'is-invalid': errors.name && touched.name }"
              />
              <div v-if="errors.name && touched.name" class="invalid-feedback">
                {{ errors.name }}
              </div>
            </div>

            <!-- Project Description -->
            <div class="mb-3">
              <label for="projectDescription" class="form-label">
                Project Description (Optional)
              </label>
              <input
                type="text"
                class="form-control"
                id="projectDescription"
                v-model="form.description"
                @blur="touched.description = true"
              />
            </div>

            <!-- Project Path -->
            <div class="mb-3">
              <label for="projectPath" class="form-label">Project Path</label>
              <input
                type="text"
                class="form-control"
                id="projectPath"
                v-model="form.path"
                @blur="touched.path = true"
                :class="{ 'is-invalid': errors.path && touched.path }"
              />
              <span class="form-text">
                Example: /var/www/html/project (ensure to enter correct path)
              </span>
              <div v-if="errors.path && touched.path" class="invalid-feedback">
                {{ errors.path }}
              </div>
            </div>

            <!-- Buttons -->
            <div class="d-flex gap-2">
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="form.processing"
              >
                <i class="ti ti-device-floppy me-1"></i>
                {{ form.processing ? 'Updating...' : 'Update Project' }}
              </button>
              <button
                type="button"
                class="btn btn-secondary"
                @click="router.visit('/projects/manage-projects')"
              >
                <i class="ti ti-x me-1"></i>
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.is-invalid {
  border-color: #dc3545;
}

.invalid-feedback {
  color: #dc3545;
  font-size: 0.875rem;
}

.form-text {
  color: #6c757d;
  font-size: 0.875rem;
}
</style>