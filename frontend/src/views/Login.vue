<template>
  <div class="login-wrap">
    <div class="login-box">
      <h2>Admin Login</h2>
      <p v-if="error" class="error">{{ error }}</p>
      <form @submit.prevent="login">
        <label>Email</label>
        <input v-model="form.email" type="email" required placeholder="admin@example.com" />
        <label>Password</label>
        <input v-model="form.password" type="password" required placeholder="password" />
        <button type="submit" :disabled="loading">
          {{ loading ? 'Logging in...' : 'Login' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const form = ref({ email: '', password: '' })
const error = ref('')
const loading = ref(false)

async function login() {
  error.value = ''
  loading.value = true
  try {
    const { data } = await axios.post('/login', form.value)
    localStorage.setItem('token', data.token)
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message || 'Login failed.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-wrap { display: flex; justify-content: center; align-items: center; min-height: 100vh; }
.login-box { background: #fff; padding: 2rem; border-radius: 8px; width: 360px; box-shadow: 0 2px 12px rgba(0,0,0,.1); }
h2 { margin-bottom: 1.5rem; font-size: 1.4rem; }
label { display: block; margin-bottom: .3rem; font-size: .85rem; font-weight: 600; }
input { width: 100%; padding: .6rem .8rem; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 1rem; font-size: .95rem; }
button { width: 100%; padding: .7rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; font-size: 1rem; }
button:disabled { opacity: .6; }
.error { color: #dc2626; margin-bottom: 1rem; font-size: .9rem; }
</style>
