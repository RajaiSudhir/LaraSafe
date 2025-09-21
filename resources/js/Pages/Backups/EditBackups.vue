<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'

// Load SweetAlert2 from CDN
const Swal = window.Swal

defineOptions({
  layout: MainLayout
})

/* ---- Props ---- */
const { backup, projects } = defineProps({
  backup: { type: Object, required: true },
  projects: { type: Array, default: () => [] },
})

/* ---- Form ---- */
const form = useForm({
  project_id: '',
  file_name: '',
  storage_disk: 'local',
  frequency: 'daily',
  time: '02:00',
})

const touched = ref({
  project_id: false,
  file_name: false,
  storage_disk: false,
  frequency: false,
  time: false,
})

const autoBackupEnabled = ref(false) // toggle for auto backup

/* ---- Watch backup prop to pre-populate form ---- */
watch(
  () => backup,
  (b) => {
    if (!b) return
    form.project_id   = b.project_id
    form.file_name    = b.file_name
    form.storage_disk = b.storage_disk
    form.frequency    = b.backup_frequency || 'daily'
    form.time         = b.backup_time?.substr(0, 5) || '02:00'
    autoBackupEnabled.value = !!b.backup_frequency
  },
  { immediate: true }
)

/* ---- Validation ---- */
const errors = computed(() => ({
  project_id:
    touched.value.project_id && !form.project_id ? 'Project is required' : '',
  file_name:
    touched.value.file_name && !form.file_name ? 'File name is required' : '',
  storage_disk:
    touched.value.storage_disk && !form.storage_disk
      ? 'Storage disk is required'
      : '',
  frequency:
    touched.value.frequency && autoBackupEnabled.value && !form.frequency
      ? 'Frequency is required'
      : '',
  time:
    touched.value.time && autoBackupEnabled.value && !form.time
      ? 'Backup time is required'
      : '',
}))

const isValid = computed(() => {
  return !Object.values(errors.value).some((e) => e !== '')
})

/* ---- Submit ---- */
const handleSubmit = () => {
  Object.keys(touched.value).forEach((key) => (touched.value[key] = true))

  if (!isValid.value) {
    const errorList = Object.values(errors.value)
      .filter((e) => e !== '')
      .map((e) => `<li>${e}</li>`)
      .join('')

    Swal.fire({
      icon: 'error',
      title: 'Validation Error',
      html: `<ul style="text-align:left;">${errorList}</ul>`,
    })
    return
  }

  const data = {
    project_id: form.project_id,
    file_name: form.file_name,
    storage_disk: form.storage_disk,
    ...(autoBackupEnabled.value && {
      frequency: form.frequency,
      time: form.time,
    }),
  }

  form.put(`/backups/update-backup/${backup.id}`, {
    data,
    onSuccess: (page) => {
      const successMessage = page.props.flash?.status
      Swal.fire({
        icon: 'success',
        title: 'Success',
        text: successMessage || 'Backup updated successfully!',
      }).then(() => {
        router.visit('/backups/manage-backups')
      })
    },
    onError: (serverErrors) => {
      const fileErrors = serverErrors.file_name || serverErrors.filename || {}
      const errorList = Array.isArray(fileErrors)
        ? fileErrors.map((e) => `<li>${e}</li>`).join('')
        : ''
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        html: errorList
          ? `<ul style="text-align:left;">${errorList}</ul>`
          : 'An unexpected error occurred.',
      })
    },
  })
}
</script>

<template>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title fw-semibold mb-4">Edit Backup</h5>
      <div class="card">
        <div class="card-body">
          <form @submit.prevent="handleSubmit">
            <!-- Project -->
            <div class="mb-3">
              <label for="projectSelect" class="form-label">Select Project</label>
              <select
                id="projectSelect"
                class="form-select"
                v-model="form.project_id"
                @blur="touched.project_id = true"
                :class="{ 'is-invalid': errors.project_id && touched.project_id }"
                :disabled="true"
              >
                <option value="" disabled>Select a project</option>
                <option
                  v-for="project in projects"
                  :key="project.id"
                  :value="project.id"
                >
                  {{ project.name }}
                </option>
              </select>
              <div v-if="errors.project_id && touched.project_id" class="invalid-feedback">
                {{ errors.project_id }}
              </div>
            </div>

            <!-- File Name -->
            <div class="mb-3">
              <label for="backupFileName" class="form-label">File Name</label>
              <input
                id="backupFileName"
                type="text"
                class="form-control"
                v-model="form.file_name"
                @blur="touched.file_name = true"
                :class="{ 'is-invalid': errors.file_name && touched.file_name }"
              />
              <div v-if="errors.file_name && touched.file_name" class="invalid-feedback">
                {{ errors.file_name }}
              </div>
            </div>

            <!-- Storage Disk -->
            <div class="mb-3">
              <label for="storageDisk" class="form-label">Storage Disk</label>
              <select
                id="storageDisk"
                class="form-select"
                v-model="form.storage_disk"
                @blur="touched.storage_disk = true"
                :class="{ 'is-invalid': errors.storage_disk && touched.storage_disk }"
              >
                <option value="local">Local</option>
                <option value="s3">S3</option>
                <option value="other">Other Cloud Storage</option>
              </select>
              <div v-if="errors.storage_disk && touched.storage_disk" class="invalid-feedback">
                {{ errors.storage_disk }}
              </div>
            </div>

            <!-- Auto Backup -->
            <div class="mb-3">
              <div class="form-check form-switch">
                <input
                  id="autoBackupToggle"
                  type="checkbox"
                  class="form-check-input"
                  v-model="autoBackupEnabled"
                  @change="touched.frequency = true; touched.time = true"
                />
                <label class="form-check-label" for="autoBackupToggle">
                  Enable Auto Backups
                </label>
              </div>

              <div v-if="autoBackupEnabled" class="mt-3">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="frequency" class="form-label">Frequency</label>
                    <select
                      id="frequency"
                      class="form-select"
                      v-model="form.frequency"
                      @blur="touched.frequency = true"
                      :class="{ 'is-invalid': errors.frequency && touched.frequency }"
                    >
                      <option value="daily">Daily</option>
                      <option value="weekly">Weekly</option>
                      <option value="monthly">Monthly</option>
                    </select>
                    <div
                      v-if="errors.frequency && touched.frequency"
                      class="invalid-feedback"
                    >
                      {{ errors.frequency }}
                    </div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="backupTime" class="form-label">Backup Time</label>
                    <input
                      id="backupTime"
                      type="time"
                      class="form-control"
                      v-model="form.time"
                      @blur="touched.time = true"
                      :class="{ 'is-invalid': errors.time && touched.time }"
                    />
                    <div v-if="errors.time && touched.time" class="invalid-feedback">
                      {{ errors.time }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Buttons -->
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ form.processing ? 'Updating...' : 'Update Backup' }}
            </button>
            &nbsp;
            <button
              type="button"
              class="btn btn-secondary"
              @click="router.visit('/backups/manage-backups')"
            >
              Cancel
            </button>
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
