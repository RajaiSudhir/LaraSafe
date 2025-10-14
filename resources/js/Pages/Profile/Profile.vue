<script setup>
import { ref, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
  user: Object,
  errors: Object,
})

const page = usePage()

const avatarPreview = ref(
  props.user.avatar
    ? `/storage/${props.user.avatar}`
    : '/assets/images/profile/user1.jpg'
)

// Separate form for profile info (no files)
const profileForm = useForm({
  name: props.user.name,
  email: props.user.email,
})

// Separate form for avatar upload
const avatarForm = useForm({
  avatar: null,
})

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

// Watch for flash messages
watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) {
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: flash.success,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      })
    }
    
    if (flash?.error) {
      Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: flash.error,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      })
    }
  },
  { deep: true, immediate: true }
)

const handleAvatarChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    avatarForm.avatar = file
    avatarPreview.value = URL.createObjectURL(file)
    
    // Auto-upload when file is selected
    uploadAvatar()
  }
}

// Upload avatar immediately when selected
const uploadAvatar = () => {
  avatarForm.post('/profile/avatar', {
    preserveScroll: true,
    onSuccess: () => {
      avatarPreview.value = props.user.avatar
        ? `/storage/${props.user.avatar}?t=${Date.now()}`
        : '/assets/images/profile/user1.jpg'
      
      avatarForm.avatar = null
    },
    onError: (errors) => {
      const errorMessages = Object.values(errors).flat().join('<br>')
      Swal.fire({
        icon: 'error',
        title: 'Upload Error',
        html: errorMessages,
      })
      
      // Revert preview on error
      avatarPreview.value = props.user.avatar
        ? `/storage/${props.user.avatar}`
        : '/assets/images/profile/user1.jpg'
    },
  })
}

// Update profile info only (no files)
const updateProfile = () => {
  profileForm.post('/profile/update', {
    preserveScroll: true,
    onSuccess: () => {
      profileForm.name = props.user.name
      profileForm.email = props.user.email
    },
    onError: (errors) => {
      const errorMessages = Object.values(errors).flat().join('<br>')
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        html: errorMessages,
      })
    },
  })
}

const updatePassword = () => {
  passwordForm.put('/profile/password', {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset()
    },
    onError: (errors) => {
      const errorMessages = Object.values(errors).flat().join('<br>')
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        html: errorMessages,
      })
    },
  })
}
</script>

<template>
  <MainLayout>
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-8 mx-auto">
          <!-- Profile Information -->
          <div class="card mb-4">
            <div class="card-header">
              <h5 class="card-title mb-0">Profile Information</h5>
            </div>
            <div class="card-body">
              <!-- Avatar Upload (Separate) -->
              <div class="text-center mb-4">
                <div class="position-relative d-inline-block">
                  <img
                    :src="avatarPreview"
                    alt="Avatar"
                    class="rounded-circle"
                    style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #e9ecef;"
                  >
                  <label
                    for="avatar-upload"
                    class="position-absolute bottom-0 end-0 btn btn-primary btn-sm rounded-circle d-flex justify-content-center align-items-center"
                    style="width: 40px; height: 40px; cursor: pointer;"
                    :class="{ 'disabled': avatarForm.processing }"
                  >
                    <span v-if="avatarForm.processing" class="spinner-border spinner-border-sm"></span>
                    <i v-else class="ti ti-camera fs-5"></i>
                  </label>
                  <input
                    type="file"
                    id="avatar-upload"
                    class="d-none"
                    accept="image/*"
                    @input="handleAvatarChange"
                    :disabled="avatarForm.processing"
                  >
                </div>
                <p class="text-muted mt-2 mb-0">Click camera icon to change avatar</p>
                <small class="text-muted">JPG, PNG, or WEBP (max 2 MB)</small>
                <div v-if="avatarForm.errors.avatar" class="text-danger mt-2">
                  {{ avatarForm.errors.avatar }}
                </div>
              </div>

              <!-- Profile Form (No files) -->
              <form @submit.prevent="updateProfile">
                <div class="mb-3">
                  <label for="name" class="form-label">Name</label>
                  <input
                    type="text"
                    v-model="profileForm.name"
                    class="form-control"
                    :class="{ 'is-invalid': profileForm.errors.name }"
                    id="name"
                    required
                  >
                  <div v-if="profileForm.errors.name" class="invalid-feedback">
                    {{ profileForm.errors.name }}
                  </div>
                </div>

                <div class="mb-4">
                  <label for="email" class="form-label">Email</label>
                  <input
                    type="email"
                    v-model="profileForm.email"
                    class="form-control"
                    :class="{ 'is-invalid': profileForm.errors.email }"
                    id="email"
                    required
                  >
                  <div v-if="profileForm.errors.email" class="invalid-feedback">
                    {{ profileForm.errors.email }}
                  </div>
                </div>

                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="profileForm.processing"
                >
                  <span v-if="profileForm.processing">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Updating...
                  </span>
                  <span v-else>Update Profile</span>
                </button>
              </form>
            </div>
          </div>

          <!-- Password Change (remains the same) -->
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">Change Password</h5>
            </div>
            <div class="card-body">
              <form @submit.prevent="updatePassword">
                <div class="mb-3">
                  <label for="current_password" class="form-label">Current Password</label>
                  <input
                    type="password"
                    v-model="passwordForm.current_password"
                    class="form-control"
                    :class="{ 'is-invalid': passwordForm.errors.current_password }"
                    id="current_password"
                    required
                  >
                  <div v-if="passwordForm.errors.current_password" class="invalid-feedback">
                    {{ passwordForm.errors.current_password }}
                  </div>
                </div>

                <div class="mb-3">
                  <label for="password" class="form-label">New Password</label>
                  <input
                    type="password"
                    v-model="passwordForm.password"
                    class="form-control"
                    :class="{ 'is-invalid': passwordForm.errors.password }"
                    id="password"
                    required
                  >
                  <div v-if="passwordForm.errors.password" class="invalid-feedback">
                    {{ passwordForm.errors.password }}
                  </div>
                  <small class="text-muted">Minimum 8 characters</small>
                </div>

                <div class="mb-4">
                  <label for="password_confirmation" class="form-label">Confirm New Password</label>
                  <input
                    type="password"
                    v-model="passwordForm.password_confirmation"
                    class="form-control"
                    id="password_confirmation"
                    required
                  >
                </div>

                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="passwordForm.processing"
                >
                  <span v-if="passwordForm.processing">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Changing...
                  </span>
                  <span v-else">Change Password</span>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>