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
    projects: {
        type: Array,
        default: () => [],
    }
})

const deleting = ref(null)

const handleDelete = (projectId) => {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            deleting.value = projectId
            router.delete(`/projects/delete-project/${projectId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Deleted!', 'Project has been deleted.', 'success')
                    deleting.value = null
                },
                onError: () => {
                    Swal.fire('Error!', 'Failed to delete the project.', 'error')
                    deleting.value = null
                },
            })
        }
    })
}
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card w-100">
                <div class="card-body p-4">
                    <div class="d-flex mb-4 justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Projects List</h5>
                        <Link href="/projects/create-project" class="btn btn-primary">Add New Project</Link>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Project Name</th>
                                    <th scope="col">Project Description</th>
                                    <th scope="col">Project Path</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!projects.length">
                                    <td colspan="5" class="text-center">No projects found.</td>
                                </tr>
                                <tr v-for="project in projects" :key="project.id">
                                    <td>{{ project.id }}</td>
                                    <td>{{ project.name }}</td>
                                    <td>{{ project.description }}</td>
                                    <td>{{ project.path }}</td>
                                    <td>
                                        <div class="d-flex">
                                            <Link :href="`/projects/view-project/${project.id}`" class="btn btn-sm btn-light-info text-info me-1" title="View"><i class="ti ti-eye"></i></Link>
                                            <Link :href="`/projects/${project.id}/edit`" class="btn btn-sm btn-light-warning text-warning me-1" title="Edit"><i class="ti ti-edit"></i></Link>
                                            <button
                                                @click.prevent="handleDelete(project.id)"
                                                class="btn btn-sm btn-light-danger text-danger"
                                                :disabled="deleting === project.id"
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