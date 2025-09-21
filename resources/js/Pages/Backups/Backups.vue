<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

// Load SweetAlert2 from CDN
const Swal = window.Swal

defineOptions({
    layout: MainLayout
})

defineProps({
    backups: {
        type: Array,
        default: () => [],
    },
})

const deleting = ref(null)
const retrying = ref(null)
const downloading = ref(null)

const handleDelete = (backupId) => {
    Swal.fire({
        title: 'Are you sure you want to delete all the backups of this project?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            deleting.value = backupId
            router.delete(`/backups/delete-backup/${backupId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Deleted!', 'Backup has been deleted.', 'success')
                    deleting.value = null
                },
                onError: () => {
                    Swal.fire('Error!', 'Failed to delete the backup.', 'error')
                    deleting.value = null
                },
            })
        }
    })
}

const handleRetry = (backupId) => {
    Swal.fire({
        title: 'Are you sure you want to retry this backup?',
        text: 'This will reprocess the backup job.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, retry it!',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            retrying.value = backupId
            router.post(`/backups/retry-backup/${backupId}`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Retrying!', 'Backup retry has been initiated.', 'success')
                    retrying.value = null
                },
                onError: () => {
                    Swal.fire('Error!', 'Failed to retry the backup.', 'error')
                    retrying.value = null
                },
            })
        }
    })
}

const handleDownload = (backupId) => {
    downloading.value = backupId
    router.get(`/backups/download/${backupId}`, {
        onSuccess: () => {
            downloading.value = null
        },
        onError: () => {
            Swal.fire('Error!', 'Failed to download the backup.', 'error')
            downloading.value = null
        },
    })
}

// Method to format size in MB or GB
const formatSize = (bytes) => {
    if (!bytes && bytes !== 0) return 'N/A'
    const mb = bytes / (1024 * 1024)
    const gb = bytes / (1024 * 1024 * 1024)
    if (gb >= 1) return `${gb.toFixed(2)} GB`
    return `${mb.toFixed(2)} MB`
}

// Method to format date to human-readable form
const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A'
    const date = new Date(dateStr)
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    })
}
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card w-100">
                <div class="card-body p-4">
                    <div class="d-flex mb-4 justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Backups List</h5>
                        <Link href="/backups/create-backup" class="btn btn-primary">Create New Backup</Link>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Project Name</th>
                                    <th scope="col">Filename</th>
                                    <th scope="col">Storage Path</th>
                                    <th scope="col">Size</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Backup Frequency</th>
                                    <th scope="col">Next Backup</th>
                                    <th scope="col">Created At</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!backups.length">
                                    <td colspan="8" class="text-center">No backups found.</td>
                                </tr>
                                <tr v-for="backup in backups" :key="backup.id">
                                    <td>{{ backup.id }}</td>
                                    <td>{{ backup.project ? backup.project.name : 'N/A' }}</td>
                                    <td>{{ backup.file_name || 'N/A' }}</td>
                                    <td>{{ backup.storage_disk || 'N/A' }}</td>
                                    <td>{{ formatSize(backup.size) }}</td> <!-- Converted to MB/GB -->
                                    <td>{{ backup.status || 'N/A' }}</td>
                                    <td>{{ backup.backup_frequency || 'N/A' }}</td>
                                    <td>{{ formatDate(backup.next_backup_at) || 'N/A' }}</td>
                                    <td>{{ formatDate(backup.created_at) }}</td> <!-- Human-readable date -->
                                    <td>
                                        <div class="d-flex">
                                            <button
                                                @click.prevent="handleRetry(backup.id)"
                                                class="btn btn-sm btn-light-warning text-warning me-1"
                                                :disabled="retrying === backup.id || backup.status === 'completed'"
                                                title="Retry"
                                            >
                                                <i class="ti ti-refresh"></i>
                                            </button>
                                            <Link :href="`/backups/edit-backup/${backup.id}`" class="btn btn-sm btn-light-info text-info me-1" title="Edit"><i class="ti ti-edit"></i></Link>
                                            <a
                                                :href="`/backups/download/${backup.id}`"
                                                class="btn btn-sm btn-light-success text-success me-1"
                                                :disabled="downloading === backup.id"
                                                title="Download"
                                            >
                                                <i class="ti ti-download"></i>
                                            </a>
                                            <button
                                                @click.prevent="handleDelete(backup.id)"
                                                class="btn btn-sm btn-light-danger text-danger"
                                                :disabled="deleting === backup.id"
                                                title="Delete"
                                            >
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* No additional styles needed for this version */
</style>