<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

// Load SweetAlert2 from CDN
const Swal = window.Swal

defineOptions({
    layout: MainLayout
})

defineProps({
    projects: {
        type: Array,
        default: () => [],
    }
})

const form = useForm({
    project_id: '',
    file_name: '', // Matches backend expectation
    storage_disk: 'local',
})

const touched = ref({
    project_id: false,
    file_name: false,
    storage_disk: false
})

const errors = computed(() => ({
    project_id: touched.value.project_id && !form.project_id ? 'Project is required' : '',
    file_name: touched.value.file_name && !form.file_name ? 'File name is required' : '',
    storage_disk: touched.value.storage_disk && !form.storage_disk ? 'Storage disk is required' : ''
}))

const isValid = computed(() => {
    return !Object.values(errors.value).some(error => error !== '')
})

const handleSubmit = () => {
    // Mark all fields as touched
    Object.keys(touched.value).forEach(key => touched.value[key] = true)

    if (!isValid.value) {
        const errorList = Object.values(errors.value)
            .filter(error => error !== '')
            .map(error => `<li>${error}</li>`)
            .join("")

        Swal.fire({
            icon: "error",
            title: "Validation Error",
            html: `<ul style="text-align:left;">${errorList}</ul>`,
        })
        return
    }

    form.post('/backups/store-backup', {
        onSuccess: (page) => {
            const successMessage = page.props.flash?.status; // Updated to match 'status' from backend

            Swal.fire({
                icon: "success",
                title: "Success",
                text: successMessage || "Backup created successfully!",
            }).then(() => {
                router.visit('/backups/manage-backups')
            })

            form.reset()
            Object.keys(touched.value).forEach(key => touched.value[key] = false)
        },
        onError: (serverErrors) => {
            const errorList = Object.values(serverErrors.file_name || serverErrors.filename || {})
                .map(error => `<li>${error}</li>`)
                .join("")

            Swal.fire({
                icon: "error",
                title: "Validation Error",
                html: errorList ? `<ul style="text-align:left;">${errorList}</ul>` : 'An unexpected error occurred.',
            })
        }
    })
}
</script>

<template>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title fw-semibold mb-4">Create Backup</h5>
            <div class="card">
                <div class="card-body">
                    <form @submit.prevent="handleSubmit">
                        <div class="mb-3">
                            <label for="projectSelect" class="form-label">Select Project</label>
                            <select
                                class="form-select"
                                id="projectSelect"
                                v-model="form.project_id"
                                @blur="touched.project_id = true"
                                :class="{ 'is-invalid': errors.project_id && touched.project_id }"
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
                        <div class="mb-3">
                            <label for="backupFileName" class="form-label">File Name</label>
                            <input
                                type="text"
                                class="form-control"
                                id="backupFileName"
                                v-model="form.file_name"
                                @blur="touched.file_name = true"
                                :class="{ 'is-invalid': errors.file_name && touched.file_name }"
                            >
                            <div v-if="errors.file_name && touched.file_name" class="invalid-feedback">
                                {{ errors.file_name }}
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="storageDisk" class="form-label">Storage Disk</label>
                            <select
                                class="form-select"
                                id="storageDisk"
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
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            {{ form.processing ? 'Creating...' : 'Create Backup' }}
                        </button>&nbsp;
                        <button type="button" class="btn btn-secondary" @click="router.visit('/backups/manage-backups')">Cancel</button>
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