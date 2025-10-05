<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const Swal = window.Swal
defineOptions({ layout: MainLayout })
defineProps({ projects: { type: Array, default: () => [] } })

const form = useForm({
  project_id: '',
  file_name: '',
  storage_disk: 'local',
  frequency: null,
  time: null,
  include_database: false,
  db_source: 'env',
  db_host: '',
  db_port: '3306',
  db_name: '',
  db_username: '',
  db_password: '',
  db_tables: 'all',
  selected_tables: [],
})

const touched = ref({
  project_id: false,
  file_name: false,
  storage_disk: false,
  frequency: false,
  time: false,
  db_host: false,
  db_name: false,
  db_username: false,
  db_password: false,
})

const autoBackupEnabled = ref(false)
const showAdvancedDb      = ref(false)
const creating            = ref(false)

const errors = computed(() => {
  const base = {
    project_id: touched.value.project_id && !form.project_id ? 'Project is required' : '',
    file_name:  touched.value.file_name  && !form.file_name  ? 'File name is required'  : '',
    storage_disk: touched.value.storage_disk && !form.storage_disk ? 'Storage disk is required' : '',
    frequency: touched.value.frequency && autoBackupEnabled.value && !form.frequency ? 'Frequency is required' : '',
    time:      touched.value.time      && autoBackupEnabled.value && !form.time      ? 'Backup time is required' : '',
  }
  if (form.include_database && form.db_source === 'custom') {
    return {
      ...base,
      db_host:     touched.value.db_host     && !form.db_host     ? 'Database host is required'     : '',
      db_name:     touched.value.db_name     && !form.db_name     ? 'Database name is required'     : '',
      db_username: touched.value.db_username && !form.db_username ? 'Database username is required' : '',
    }
  }
  return base
})

const isValid = computed(() => !Object.values(errors.value).some(e => e))

const dbSourceInfo = computed(() => {
  switch (form.db_source) {
    case 'env': return 'Will use database credentials from project’s .env file'
    case 'project_config': return 'Will use database credentials stored in project configuration'
    case 'custom': return 'Use custom database credentials for this backup'
    default: return ''
  }
})

const handleSubmit = () => {
  Object.keys(touched.value).forEach(k => touched.value[k] = true)

  if (!isValid.value) {
    const list = Object.values(errors.value).filter(e => e).map(e => `<li>${e}</li>`).join('')
    Swal.fire({ icon:'error', title:'Validation Error', html:`<ul style="text-align:left;">${list}</ul>` })
    return
  }

  const data = {
    project_id: form.project_id,
    file_name:  form.file_name,
    storage_disk: form.storage_disk,
    include_database: form.include_database,
    ...(autoBackupEnabled.value && { frequency: form.frequency, time: form.time }),
  }
  if (form.include_database) {
    Object.assign(data, {
      db_source: form.db_source,
      db_tables: form.db_tables,
      ...(form.db_source === 'custom' && {
        db_host:     form.db_host,
        db_port:     form.db_port,
        db_name:     form.db_name,
        db_username: form.db_username,
        db_password: form.db_password,
      }),
      ...(form.db_tables === 'selected' && form.selected_tables.length > 0 && { selected_tables: form.selected_tables }),
    })
  }

  creating.value = true
  form.post('/backups/store-backup', {
    data,
    onSuccess: page => {
      const msg = page.props.flash?.status || 'Backup created successfully!'
      Swal.fire({ icon:'success', title:'Success', text: msg }).then(() => {
        router.visit('/backups/manage-backups')
      })
      form.reset()
      Object.keys(touched.value).forEach(k => touched.value[k] = false)
    },
    onError: errs => {
      const list = Object.values(errs).flat().map(e => `<li>${e}</li>`).join('')
      Swal.fire({
        icon:'error',
        title:'Validation Error',
        html: list ? `<ul style="text-align:left;">${list}</ul>` : 'An unexpected error occurred.',
      })
    },
    onFinish: () => {
      creating.value = false
    }
  })
}

const testDatabaseConnection = () => {
  if (form.db_source !== 'custom') return
  const payload = {
    db_host: form.db_host === 'localhost' ? '127.0.0.1' : form.db_host,
    db_port: form.db_port,
    db_name: form.db_name,
    db_username: form.db_username,
    db_password: form.db_password,
  }
  axios.post('/backups/test-db-connection', payload)
    .then(res => {
      Swal.fire({
        icon: res.data.success ? 'success' : 'error',
        title: res.data.success ? 'Connection Successful' : 'Connection Failed',
        text: res.data.message || ''
      })
    })
    .catch(err => {
      Swal.fire({ icon:'error', title:'Connection Failed', text: err.response?.data?.message||'' })
    })
}
</script>

<template>
    <!-- Full-Page Loader Overlay -->
    <div v-if="creating" class="full-page-loader">
      <div class="loader-content">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 mb-0">Creating backup...</p>
      </div>
    </div>
  
    <div class="card" :class="{ 'opacity-50': creating }">
      <div class="card-body">
        <h5 class="card-title fw-semibold mb-4">Create Backup</h5>
        <form @submit.prevent="handleSubmit">
          <!-- Project Selection -->
          <div class="mb-3">
            <label for="projectSelect" class="form-label">Select Project</label>
            <select
              id="projectSelect"
              class="form-select"
              v-model="form.project_id"
              @blur="touched.project_id = true"
              :class="{ 'is-invalid': errors.project_id && touched.project_id }"
              :disabled="creating"
            >
              <option value="" disabled>Select a project</option>
              <option v-for="project in projects" :key="project.id" :value="project.id">
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
              :disabled="creating"
            >
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
              :disabled="creating"
            >
              <option value="local">Local</option>
              <option value="s3">S3</option>
              <option value="other">Other Cloud Storage</option>
            </select>
            <div v-if="errors.storage_disk && touched.storage_disk" class="invalid-feedback">
              {{ errors.storage_disk }}
            </div>
          </div>
  
          <!-- Include Database Backup Toggle -->
          <div class="mb-4">
            <div class="card border-info">
              <div class="card-header bg-light">
                <div class="form-check form-switch">
                  <input type="hidden" name="include_database" :value="0" />
                  <input
                    id="includeDatabaseToggle"
                    type="checkbox"
                    class="form-check-input"
                    v-model="form.include_database"
                    :disabled="creating"
                  >
                  <label class="form-check-label fw-bold" for="includeDatabaseToggle">
                    <i class="ti ti-database me-2"></i>
                    Include Database Backup
                  </label>
                </div>
              </div>
  
              <div v-if="form.include_database" class="card-body">
                <!-- Database Source Radio Buttons -->
                <div class="mb-3">
                  <label class="form-label">Database Credentials Source</label>
                  <div class="form-check">
                    <input
                      id="dbSourceEnv"
                      class="form-check-input"
                      type="radio"
                      value="env"
                      v-model="form.db_source"
                      :disabled="creating"
                    >
                    <label class="form-check-label" for="dbSourceEnv">
                      <strong>Use Project's .env File</strong>
                    </label>
                  </div>
                  <div class="form-check">
                    <input
                      id="dbSourceCustom"
                      class="form-check-input"
                      type="radio"
                      value="custom"
                      v-model="form.db_source"
                      :disabled="creating"
                    >
                    <label class="form-check-label" for="dbSourceCustom">
                      <strong>Custom Database Credentials</strong>
                    </label>
                  </div>
                  <div class="form-check">
                    <input
                      id="dbSourceProject"
                      class="form-check-input"
                      type="radio"
                      value="project_config"
                      v-model="form.db_source"
                      :disabled="creating"
                    >
                    <label class="form-check-label" for="dbSourceProject">
                      <strong>Project Configuration</strong>
                    </label>
                  </div>
                  <div class="alert alert-info mt-2">
                    <i class="ti ti-info-circle me-2"></i>
                    {{ dbSourceInfo }}
                  </div>
                </div>
  
                <!-- Custom Database Fields -->
                <div v-if="form.db_source === 'custom'" class="border rounded p-3 bg-light mb-3">
                  <h6 class="mb-3">Database Connection Details</h6>
                  <div class="row">
                    <div class="col-md-8 mb-3">
                      <label for="dbHost" class="form-label">Host</label>
                      <input
                        id="dbHost"
                        type="text"
                        class="form-control"
                        v-model="form.db_host"
                        placeholder="localhost"
                        @blur="touched.db_host = true"
                        :class="{ 'is-invalid': errors.db_host && touched.db_host }"
                        :disabled="creating"
                      >
                      <div v-if="errors.db_host && touched.db_host" class="invalid-feedback">
                        {{ errors.db_host }}
                      </div>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label for="dbPort" class="form-label">Port</label>
                      <input
                        id="dbPort"
                        type="number"
                        class="form-control"
                        v-model="form.db_port"
                        :disabled="creating"
                      >
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label for="dbName" class="form-label">Database Name</label>
                      <input
                        id="dbName"
                        type="text"
                        class="form-control"
                        v-model="form.db_name"
                        @blur="touched.db_name = true"
                        :class="{ 'is-invalid': errors.db_name && touched.db_name }"
                        :disabled="creating"
                      >
                      <div v-if="errors.db_name && touched.db_name" class="invalid-feedback">
                        {{ errors.db_name }}
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label for="dbUsername" class="form-label">Username</label>
                      <input
                        id="dbUsername"
                        type="text"
                        class="form-control"
                        v-model="form.db_username"
                        @blur="touched.db_username = true"
                        :class="{ 'is-invalid': errors.db_username && touched.db_username }"
                        :disabled="creating"
                      >
                      <div v-if="errors.db_username && touched.db_username" class="invalid-feedback">
                        {{ errors.db_username }}
                      </div>
                    </div>
                  </div>
                  <div class="mb-3">
                    <label for="dbPassword" class="form-label">Password</label>
                    <input
                      id="dbPassword"
                      type="password"
                      class="form-control"
                      v-model="form.db_password"
                      @blur="touched.db_password = true"
                      :disabled="creating"
                    >
                  </div>
                  <button
                    type="button"
                    class="btn btn-outline-info btn-sm"
                    @click="testDatabaseConnection"
                    :disabled="creating"
                  >
                    <i class="ti ti-plug-connected me-1"></i> Test Connection
                  </button>
                </div>
  
                <!-- Advanced DB Options -->
                <div class="mb-3">
                  <button
                    type="button"
                    class="btn btn-link p-0 text-decoration-none"
                    @click="showAdvancedDb = !showAdvancedDb"
                    :disabled="creating"
                  >
                    <i :class="showAdvancedDb ? 'ti ti-chevron-down' : 'ti ti-chevron-right'"></i>
                    Advanced Database Options
                  </button>
                  <div v-if="showAdvancedDb" class="mt-3 border rounded p-3 bg-light">
                    <label class="form-label">Tables to Backup</label>
                    <div class="form-check">
                      <input
                        id="tablesAll"
                        class="form-check-input"
                        type="radio"
                        value="all"
                        v-model="form.db_tables"
                        :disabled="creating"
                      >
                      <label class="form-check-label" for="tablesAll">
                        All Tables
                      </label>
                    </div>
                    <div class="form-check">
                      <input
                        id="tablesSelected"
                        class="form-check-input"
                        type="radio"
                        value="selected"
                        v-model="form.db_tables"
                        :disabled="creating"
                      >
                      <label class="form-check-label" for="tablesSelected">
                        Selected Tables Only
                      </label>
                    </div>
                    <div v-if="form.db_tables === 'selected'" class="mt-2">
                      <textarea
                        class="form-control"
                        rows="3"
                        placeholder="users, posts, categories"
                        v-model="form.selected_tables"
                        :disabled="creating"
                      ></textarea>
                      <small class="form-text text-muted">
                        Enter table names separated by commas
                      </small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
  
          <!-- Auto Backup Toggle -->
          <div class="mb-3">
            <div class="form-check form-switch">
              <input
                id="autoBackupToggle"
                type="checkbox"
                class="form-check-input"
                v-model="autoBackupEnabled"
                @change="touched.frequency = true; touched.time = true"
                :disabled="creating"
              >
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
                    :disabled="creating"
                  >
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                  </select>
                  <div v-if="errors.frequency && touched.frequency" class="invalid-feedback">
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
                    :disabled="creating"
                  >
                  <div v-if="errors.time && touched.time" class="invalid-feedback">
                    {{ errors.time }}
                  </div>
                </div>
              </div>
            </div>
          </div>
  
          <!-- Actions -->
          <div class="d-flex gap-2">
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="creating || form.processing"
            >
              <i class="ti ti-device-floppy me-1"></i>
              <span v-if="creating || form.processing" class="spinner-border spinner-border-sm" role="status"></span>
              <span v-else>Create Backup</span>
            </button>
            <button
              type="button"
              class="btn btn-secondary"
              @click="router.visit('/backups/manage-backups')"
              :disabled="creating"
            >
              <i class="ti ti-x me-1"></i> Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </template>
  
  <style scoped>
  .full-page-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
  }
  .loader-content {
    background: #fff;
    padding: 2rem;
    border-radius: 0.5rem;
    text-align: center;
  }
  .spinner-border-sm {
    width: 1rem;
    height: 1rem;
  }
  .opacity-50 {
    opacity: 0.5;
  }
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
  .btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
  </style>
  