<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  Filler
} from 'chart.js'
import { computed, ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, Filler)

defineOptions({ layout: MainLayout })

const props = defineProps({
  project: Object,
  createdBackups: Array
})

const chartRef = ref(null)

// Get all created backups for this project
const projectBackups = computed(() => props.createdBackups || [])

const chartData = computed(() => {
  // Create gradient function that will be called by Chart.js
  const createGradient = (ctx, chartArea) => {
    if (!chartArea) return '#5D87FF'
    
    const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top)
    gradient.addColorStop(0, '#5D87FF')
    gradient.addColorStop(0.5, '#7B99FF')
    gradient.addColorStop(1, '#A2B9FF')
    return gradient
  }

  return {
    labels: projectBackups.value.map(b =>
      new Date(b.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    ),
    datasets: [
      {
        label: 'Backup Size',
        backgroundColor: function(context) {
          const chart = context.chart
          const {ctx, chartArea} = chart
          if (!chartArea) return '#5D87FF'
          return createGradient(ctx, chartArea)
        },
        borderRadius: 8,
        borderSkipped: false,
        barThickness: 40,
        maxBarThickness: 50,
        data: projectBackups.value.map(b => Number((b.size / (1024 * 1024)).toFixed(2))),
        hoverBackgroundColor: '#4A6FE8'
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  animation: {
    duration: 1000,
    easing: 'easeInOutQuart'
  },
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      enabled: true,
      backgroundColor: 'rgba(0, 0, 0, 0.85)',
      padding: 14,
      titleColor: '#fff',
      titleFont: {
        size: 14,
        weight: '600'
      },
      bodyColor: '#fff',
      bodyFont: {
        size: 13
      },
      cornerRadius: 8,
      displayColors: false,
      callbacks: {
        title: function(context) {
          return context[0].label
        },
        label: function(context) {
          return 'Size: ' + context.parsed.y.toFixed(2) + ' MB'
        }
      }
    }
  },
  interaction: {
    intersect: false,
    mode: 'index'
  },
  scales: {
    x: {
      grid: {
        display: false,
        drawBorder: false
      },
      ticks: {
        font: { 
          size: 13,
          weight: '500'
        },
        color: '#7C8FAC',
        padding: 10
      }
    },
    y: {
      beginAtZero: true,
      grid: {
        borderDash: [5, 5],
        color: 'rgba(0, 0, 0, 0.06)',
        drawBorder: false,
        lineWidth: 1
      },
      ticks: {
        font: { 
          size: 12,
          weight: '500'
        },
        color: '#7C8FAC',
        padding: 12,
        callback: function(value) {
          return value + ' MB'
        }
      }
    }
  }
}

// Calculate total storage used by this project
const totalStorage = computed(() => {
  return projectBackups.value.reduce((sum, backup) => sum + (backup.size || 0), 0)
})

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

const getStorageDisk = (disk) => {
  const disks = {
    'local': 'Local Storage',
    'public': 'Public Storage',
    's3': 'AWS S3',
    'backups': 'Backup Storage'
  }
  return disks[disk] || disk
}
</script>

<template>
  <div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold mb-1 text-dark">{{ project.name }}</h4>
        <p class="text-muted mb-0 fs-6">Project Details & Backup Analytics</p>
      </div>
      <button @click="router.visit('/projects/manage-projects')" class="btn btn-outline-primary btn-sm px-3 py-2">
        <i class="ti ti-arrow-left me-2"></i> Back to Projects
      </button>
    </div>

    <div class="row g-4">
      <!-- Project Info Card -->
      <div class="col-lg-5">
        <div class="info-card">
          <div class="card-header-custom">
            <div class="d-flex align-items-center">
              <div class="icon-wrapper info">
                <i class="ti ti-info-circle"></i>
              </div>
              <h5 class="mb-0 ms-3 fw-bold">Project Information</h5>
            </div>
          </div>
          <div class="card-body p-4">
            <div class="info-item mb-4">
              <div class="d-flex align-items-start">
                <div class="info-icon">
                  <i class="ti ti-folder"></i>
                </div>
                <div class="flex-grow-1">
                  <label class="info-label">Project Path</label>
                  <p class="info-value">{{ project.path }}</p>
                </div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-6">
                <div class="stat-box">
                  <div class="stat-icon created">
                    <i class="ti ti-calendar"></i>
                  </div>
                  <label class="stat-label">Created</label>
                  <p class="stat-value">{{ formatDate(project.created_at) }}</p>
                </div>
              </div>
              <div class="col-6">
                <div class="stat-box">
                  <div class="stat-icon backups">
                    <i class="ti ti-database"></i>
                  </div>
                  <label class="stat-label">Total Backups</label>
                  <p class="stat-value">{{ projectBackups.length }}</p>
                </div>
              </div>
              <div class="col-6">
                <div class="stat-box">
                  <div class="stat-icon last-backup">
                    <i class="ti ti-clock"></i>
                  </div>
                  <label class="stat-label">Last Backup</label>
                  <p class="stat-value">{{ projectBackups.length ? formatDate(projectBackups[0].created_at) : 'N/A' }}</p>
                </div>
              </div>
              <div class="col-6">
                <div class="stat-box">
                  <div class="stat-icon storage">
                    <i class="ti ti-server"></i>
                  </div>
                  <label class="stat-label">Total Storage</label>
                  <p class="stat-value">{{ formatBytes(totalStorage) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Chart Card -->
      <div class="col-lg-7">
        <div class="chart-card">
          <div class="card-header-custom">
            <div class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center">
                <div class="icon-wrapper chart">
                  <i class="ti ti-chart-bar"></i>
                </div>
                <h5 class="mb-0 ms-3 fw-bold">Backup Size Over Time</h5>
              </div>
              <span class="badge badge-custom">{{ projectBackups.length }} Backups</span>
            </div>
          </div>
          <div class="card-body p-4">
            <div v-if="projectBackups.length" class="chart-container">
              <Bar ref="chartRef" :data="chartData" :options="chartOptions" />
            </div>
            <div v-else class="empty-state">
              <div class="empty-icon">
                <i class="ti ti-database-off"></i>
              </div>
              <h6 class="empty-title">No Backups Available</h6>
              <p class="empty-text">Create your first backup to see analytics</p>
              <Link href="/backups/create-backup" class="btn btn-primary btn-sm mt-3">
                <i class="ti ti-plus me-2"></i>Create Backup
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Backup List Card -->
    <div class="list-card mt-4">
      <div class="card-header-custom">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <div class="icon-wrapper list">
              <i class="ti ti-list"></i>
            </div>
            <h5 class="mb-0 ms-3 fw-bold">Backups History</h5>
          </div>
          <Link href="/backups/manage-backups" class="btn btn-sm btn-outline-primary">
            View All Backups
          </Link>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="custom-table">
            <thead>
              <tr>
                <th>File Name</th>
                <th>Size</th>
                <th>Storage</th>
                <th>Created At</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!projectBackups.length">
                <td colspan="5" class="text-center py-5">
                  <div class="empty-table">
                    <i class="ti ti-inbox"></i>
                    <p>No backups available for this project</p>
                    <Link href="/backups/create-backup" class="btn btn-sm btn-primary mt-3">
                      <i class="ti ti-plus me-2"></i>Create Backup
                    </Link>
                  </div>
                </td>
              </tr>
              <tr v-for="backup in projectBackups" :key="backup.id" class="table-row-hover">
                <td>
                  <div class="d-flex align-items-center">
                    <div class="file-icon">
                      <i class="ti ti-file-zip"></i>
                    </div>
                    <span class="file-name">{{ backup.file_name }}</span>
                  </div>
                </td>
                <td>
                  <span class="size-badge">{{ formatBytes(backup.size) }}</span>
                </td>
                <td>
                  <span class="storage-badge">
                    <i class="ti ti-server me-1"></i>
                    {{ getStorageDisk(backup.storage_disk) }}
                  </span>
                </td>
                <td class="text-muted">{{ formatDate(backup.created_at) }}</td>
                <td class="text-center">
                  <a :href="`/backups/download/${backup.id}`" class="action-btn" title="Download">
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
/* Cards */
.info-card,
.chart-card,
.list-card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.06);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  transition: all 0.3s ease;
  overflow: hidden;
}

.info-card:hover,
.chart-card:hover,
.list-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.card-header-custom {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
}

/* Icon Wrappers */
.icon-wrapper {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.icon-wrapper.info {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.icon-wrapper.chart {
  background: linear-gradient(135deg, #5D87FF 0%, #4A6FE8 100%);
  color: white;
}

.icon-wrapper.list {
  background: linear-gradient(135deg, #13C2C2 0%, #0891B2 100%);
  color: white;
}

/* Info Items */
.info-item {
  padding: 1rem;
  background: #f8f9ff;
  border-radius: 10px;
  border-left: 3px solid #5D87FF;
}

.info-icon {
  width: 38px;
  height: 38px;
  background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #5D87FF;
  font-size: 18px;
  margin-right: 12px;
  flex-shrink: 0;
}

.info-label {
  font-size: 12px;
  font-weight: 600;
  color: #7C8FAC;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
  display: block;
}

.info-value {
  font-size: 14px;
  font-weight: 600;
  color: #2D3748;
  margin: 0;
  word-break: break-all;
}

/* Stat Boxes */
.stat-box {
  padding: 1.25rem;
  background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
  border-radius: 10px;
  border: 1px solid rgba(93, 135, 255, 0.1);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.stat-box:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(93, 135, 255, 0.15);
  border-color: rgba(93, 135, 255, 0.3);
}

.stat-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  margin-bottom: 8px;
}

.stat-icon.created {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  color: #d97706;
}

.stat-icon.backups {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  color: #2563eb;
}

.stat-icon.last-backup {
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  color: #059669;
}

.stat-icon.storage {
  background: linear-gradient(135deg, #e9d5ff 0%, #d8b4fe 100%);
  color: #9333ea;
}

.stat-label {
  font-size: 11px;
  font-weight: 600;
  color: #7C8FAC;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 6px;
  display: block;
}

.stat-value {
  font-size: 13px;
  font-weight: 700;
  color: #2D3748;
  margin: 0;
}

/* Chart */
.chart-container {
  position: relative;
  height: 300px;
  width: 100%;
}

.badge-custom {
  background: linear-gradient(135deg, #5D87FF 0%, #4A6FE8 100%);
  color: white;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

/* Empty States */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}

.empty-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 36px;
  color: #94a3b8;
  margin-bottom: 1rem;
}

.empty-title {
  font-size: 18px;
  font-weight: 700;
  color: #475569;
  margin-bottom: 8px;
}

.empty-text {
  font-size: 14px;
  color: #94a3b8;
  margin: 0;
}

/* Table */
.custom-table {
  width: 100%;
  margin: 0;
}

.custom-table thead {
  background: linear-gradient(135deg, #f8f9ff 0%, #f1f5f9 100%);
}

.custom-table thead th {
  padding: 1rem 1.5rem;
  font-size: 12px;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border: none;
}

.custom-table tbody td {
  padding: 1.25rem 1.5rem;
  font-size: 14px;
  color: #2D3748;
  border-top: 1px solid rgba(0, 0, 0, 0.06);
  vertical-align: middle;
}

.table-row-hover {
  transition: all 0.2s ease;
}

.table-row-hover:hover {
  background: #f8f9ff;
}

.file-icon {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #5D87FF;
  font-size: 16px;
  margin-right: 12px;
}

.file-name {
  font-weight: 600;
  color: #2D3748;
}

.size-badge {
  display: inline-block;
  padding: 6px 12px;
  background: #f1f5f9;
  color: #475569;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
}

.storage-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  background: #ede9fe;
  color: #7c3aed;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, #5D87FF 0%, #4A6FE8 100%);
  color: white;
  border-radius: 8px;
  font-size: 16px;
  transition: all 0.2s ease;
  text-decoration: none;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(93, 135, 255, 0.3);
  color: white;
}

.empty-table i {
  font-size: 48px;
  color: #cbd5e1;
  margin-bottom: 12px;
  display: block;
}

.empty-table p {
  color: #94a3b8;
  font-size: 14px;
  margin: 0;
}

/* Responsive */
@media (max-width: 991px) {
  .chart-container {
    height: 260px;
  }
}
</style>