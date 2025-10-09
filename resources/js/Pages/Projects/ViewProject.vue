<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts'
import { computed } from 'vue'

defineOptions({
  layout: MainLayout
})

const props = defineProps({
  project: Object,
  backups: Array
})

// Filter backups related to this project
const projectBackups = computed(() =>
  props.backups.filter(b => b.project_id === props.project.id)
)

// Prepare chart data
const chartData = computed(() =>
  projectBackups.value.map(b => ({
    date: new Date(b.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
    size: (b.size / (1024 * 1024)).toFixed(2), // MB
    status: b.status
  }))
)

// Helpers
const formatBytes = (bytes) => {
  if (!bytes && bytes !== 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A'
  const date = new Date(dateStr)
  return date.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold mb-0">{{ project.name }}</h4>
      <a href="/projects" class="btn btn-outline-primary btn-sm">
        <i class="ti ti-arrow-left me-1"></i> Back to Projects
      </a>
    </div>

    <!-- Project Info -->
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title fw-semibold mb-3">Project Information</h5>
        <div class="row">
          <div class="col-md-6">
            <p><strong>Path:</strong> {{ project.path }}</p>
            <p><strong>Created:</strong> {{ formatDate(project.created_at) }}</p>
          </div>
          <div class="col-md-6">
            <p><strong>Total Backups:</strong> {{ projectBackups.length }}</p>
            <p><strong>Last Backup:</strong>
              {{ projectBackups.length ? formatDate(projectBackups.at(-1).created_at) : 'N/A' }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Backup Chart -->
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title fw-semibold mb-3">Backup Size Over Time</h5>

        <div v-if="projectBackups.length" style="width: 100%; height: 300px;">
          <ResponsiveContainer>
            <BarChart :data="chartData">
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="date" />
              <YAxis :label="{ value: 'MB', angle: -90, position: 'insideLeft' }" />
              <Tooltip />
              <Legend />
              <Bar dataKey="size" fill="#4f46e5" name="Backup Size (MB)" />
            </BarChart>
          </ResponsiveContainer>
        </div>

        <div v-else class="text-center text-muted py-4">
          <i class="ti ti-database-off fs-2 mb-2"></i>
          <p>No backups found for this project</p>
        </div>
      </div>
    </div>

    <!-- Backup List -->
    <div class="card">
      <div class="card-body">
        <h5 class="card-title fw-semibold mb-3">Backups List</h5>

        <div class="table-responsive">
          <table class="table table-borderless align-middle text-nowrap">
            <thead>
              <tr>
                <th scope="col">File Name</th>
                <th scope="col">Size</th>
                <th scope="col">Status</th>
                <th scope="col">Created At</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!projectBackups.length">
                <td colspan="5" class="text-center py-4 text-muted">
                  No backups available
                </td>
              </tr>

              <tr v-for="backup in projectBackups" :key="backup.id">
                <td>{{ backup.file_name }}</td>
                <td>{{ formatBytes(backup.size) }}</td>
                <td>
                  <span :class="`badge rounded-pill px-3 py-2 fs-3 ${backup.status === 'success' ? 'bg-light-success text-success' : 'bg-light-danger text-danger'}`">
                    {{ backup.status }}
                  </span>
                </td>
                <td>{{ formatDate(backup.created_at) }}</td>
                <td>
                  <a :href="`/backups/download/${backup.id}`" class="btn btn-sm btn-light-primary text-primary" title="Download">
                    <i class="ti ti-download"></i>
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.card {
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  border-radius: 0.5rem;
  border: none;
}

.card-title {
  font-size: 1.1rem;
}

.badge {
  font-size: 0.75rem !important;
}
</style>