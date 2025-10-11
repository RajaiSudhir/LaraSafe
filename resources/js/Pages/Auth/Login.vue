<script setup>
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';

const toast = useToast();

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/login', {  // Changed from route('login.post')
    onSuccess: () => {
      toast.success('Login successful! Redirecting...');
      form.reset('password');
    },
    onError: (errors) => {
      toast.error('Login failed. Please check your credentials.');
    },
    onFinish: () => {
      form.reset('password');
    },
  });
};

const clearError = (field) => {
  if (form.errors[field]) {
    form.errors[field] = null;
  }
};
</script>

<template>
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <div class="position-relative overflow-hidden text-bg-light min-vh-100 d-flex align-items-center justify-content-center">
      <div class="d-flex align-items-center justify-content-center w-100">
        <div class="row justify-content-center w-100">
          <div class="col-md-8 col-lg-6 col-xxl-3">
            <div class="card mb-0">
              <div class="card-body">
                <a href="/" class="text-nowrap logo-img text-center d-block py-3 w-100">
                  <img src="/public/assets/images/logos/logo.png" alt="Logo" style="width: 300px;">
                </a>
                
                <form @submit.prevent="submit">
                  <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input 
                      type="email" 
                      v-model="form.email"
                      class="form-control" 
                      :class="{ 'is-invalid': form.errors.email }"
                      id="email" 
                      required
                      @input="clearError('email')"
                    >
                    <div v-if="form.errors.email" class="invalid-feedback">
                      {{ form.errors.email }}
                    </div>
                  </div>
                  
                  <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input 
                      type="password" 
                      v-model="form.password"
                      class="form-control" 
                      :class="{ 'is-invalid': form.errors.password }"
                      id="password"
                      required
                      @input="clearError('password')"
                    >
                    <div v-if="form.errors.password" class="invalid-feedback">
                      {{ form.errors.password }}
                    </div>
                  </div>
                  
                  <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check">
                      <input 
                        class="form-check-input primary" 
                        type="checkbox" 
                        v-model="form.remember"
                        id="remember"
                      >
                      <label class="form-check-label text-dark" for="remember">
                        Remember this Device
                      </label>
                    </div>
                    <a class="text-primary fw-bold" href="/forgot-password">Forgot Password?</a>
                  </div>
                  
                  <button 
                    type="submit" 
                    class="btn btn-primary w-100 py-8 fs-4 mb-4"
                    :disabled="form.processing"
                  >
                    <span v-if="form.processing">
                      <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                      Signing In...
                    </span>
                    <span v-else>Sign In</span>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
.spinner-border {
  vertical-align: middle;
}
</style>
