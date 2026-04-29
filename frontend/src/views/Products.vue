<template>
  <div class="dashboard">
    <!-- Header -->
    <header>
      <h1>Product Dashboard</h1>
      <button class="btn-logout" @click="logout">Logout</button>
    </header>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="filters">
        <select v-model="filters.category_id" @change="fetchProducts(1)">
          <option value="">All Categories</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <select v-model="filters.enabled" @change="fetchProducts(1)">
          <option value="">All Status</option>
          <option value="true">Enabled</option>
          <option value="false">Disabled</option>
        </select>
      </div>
      <div class="actions">
        <button class="btn-danger" :disabled="selected.length === 0" @click="bulkDelete">
          Delete Selected ({{ selected.length }})
        </button>
        <button class="btn-secondary" @click="exportProducts">Export Excel</button>
        <button class="btn-primary" @click="openCreate">+ New Product</button>
      </div>
    </div>

    <!-- Table -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th><input type="checkbox" @change="toggleAll" :checked="allSelected" /></th>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="8" class="center">Loading...</td></tr>
          <tr v-else-if="products.length === 0"><td colspan="8" class="center">No products found.</td></tr>
          <tr v-for="p in products" :key="p.id">
            <td><input type="checkbox" :value="p.id" v-model="selected" /></td>
            <td>{{ p.id }}</td>
            <td>{{ p.name }}</td>
            <td>{{ p.category?.name }}</td>
            <td>${{ p.price }}</td>
            <td>{{ p.stock }}</td>
            <td><span :class="p.enabled ? 'badge-on' : 'badge-off'">{{ p.enabled ? 'Enabled' : 'Disabled' }}</span></td>
            <td>
              <button class="btn-sm btn-edit" @click="openEdit(p)">Edit</button>
              <button class="btn-sm btn-del" @click="deleteOne(p.id)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pagination" v-if="meta">
      <button :disabled="meta.current_page <= 1" @click="fetchProducts(meta.current_page - 1)">‹ Prev</button>
      <span>Page {{ meta.current_page }} of {{ meta.last_page }}</span>
      <button :disabled="meta.current_page >= meta.last_page" @click="fetchProducts(meta.current_page + 1)">Next ›</button>
    </div>

    <!-- Modal -->
    <div class="modal-overlay" v-if="modal.show" @click.self="closeModal">
      <div class="modal">
        <h3>{{ modal.editing ? 'Edit Product' : 'New Product' }}</h3>
        <p v-if="modal.error" class="error">{{ modal.error }}</p>
        <form @submit.prevent="saveProduct">
          <label>Name *</label>
          <input v-model="modal.form.name" required />
          <p class="field-error" v-if="modal.errors.name">{{ modal.errors.name[0] }}</p>

          <label>Category *</label>
          <select v-model="modal.form.category_id" required>
            <option value="">Select category</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <p class="field-error" v-if="modal.errors.category_id">{{ modal.errors.category_id[0] }}</p>

          <label>Description</label>
          <textarea v-model="modal.form.description" rows="3"></textarea>

          <label>Price *</label>
          <input v-model="modal.form.price" type="number" step="0.01" min="0" required />
          <p class="field-error" v-if="modal.errors.price">{{ modal.errors.price[0] }}</p>

          <label>Stock *</label>
          <input v-model="modal.form.stock" type="number" min="0" required />
          <p class="field-error" v-if="modal.errors.stock">{{ modal.errors.stock[0] }}</p>

          <label class="checkbox-label">
            <input type="checkbox" v-model="modal.form.enabled" /> Enabled
          </label>

          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="closeModal">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="modal.saving">
              {{ modal.saving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const products = ref([])
const categories = ref([])
const meta = ref(null)
const loading = ref(false)
const selected = ref([])
const filters = ref({ category_id: '', enabled: '' })

const modal = ref({
  show: false, editing: null, saving: false, error: '', errors: {},
  form: { name: '', category_id: '', description: '', price: '', stock: '', enabled: true }
})

const allSelected = computed(() =>
  products.value.length > 0 && selected.value.length === products.value.length
)

function toggleAll(e) {
  selected.value = e.target.checked ? products.value.map(p => p.id) : []
}

async function fetchProducts(page = 1) {
  loading.value = true
  selected.value = []
  try {
    const params = { page, ...Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v !== '')) }
    const { data } = await axios.get('/products', { params })
    products.value = data.data
    meta.value = data.meta
  } catch (e) {
    if (e.response?.status !== 401) alert('Failed to load products.')
  } finally {
    loading.value = false
  }
}

async function fetchCategories() {
  try {
    const { data } = await axios.get('/categories')
    categories.value = data.data
  } catch {}
}

function openCreate() {
  modal.value = { show: true, editing: null, saving: false, error: '', errors: {},
    form: { name: '', category_id: '', description: '', price: '', stock: '', enabled: true } }
}

function openEdit(p) {
  modal.value = { show: true, editing: p.id, saving: false, error: '', errors: {},
    form: { name: p.name, category_id: p.category?.id, description: p.description || '', price: p.price, stock: p.stock, enabled: p.enabled } }
}

function closeModal() { modal.value.show = false }

async function saveProduct() {
  modal.value.saving = true
  modal.value.error = ''
  modal.value.errors = {}
  try {
    const payload = { ...modal.value.form, price: Number(modal.value.form.price), stock: Number(modal.value.form.stock) }
    if (modal.value.editing) {
      await axios.put(`/products/${modal.value.editing}`, payload)
    } else {
      await axios.post('/products', payload)
    }
    closeModal()
    fetchProducts(meta.value?.current_page || 1)
    fetchCategories()
  } catch (e) {
    if (e.response?.status === 422) {
      modal.value.errors = e.response.data.errors || {}
      modal.value.error = e.response.data.message
    } else {
      modal.value.error = e.response?.data?.message || 'Save failed.'
    }
  } finally {
    modal.value.saving = false
  }
}

async function deleteOne(id) {
  if (!confirm('Delete this product?')) return
  try {
    await axios.delete(`/products/${id}`)
    fetchProducts(meta.value?.current_page || 1)
  } catch (e) {
    alert(e.response?.data?.message || 'Delete failed.')
  }
}

async function bulkDelete() {
  if (!confirm(`Delete ${selected.value.length} products?`)) return
  try {
    await axios.delete('/products/bulk', { data: { ids: selected.value } })
    selected.value = []
    fetchProducts(meta.value?.current_page || 1)
  } catch (e) {
    alert(e.response?.data?.message || 'Bulk delete failed.')
  }
}

async function exportProducts() {
  try {
    const { data } = await axios.get('/products/export-link')
    window.open(data.url, '_blank')
  } catch (e) {
    alert(e.response?.data?.message || 'Export failed.')
  }
}

function logout() {
  axios.post('/logout').finally(() => {
    localStorage.removeItem('token')
    router.push('/login')
  })
}

onMounted(() => {
  fetchProducts()
  fetchCategories()
})
</script>

<style scoped>
.dashboard { max-width: 1100px; margin: 0 auto; padding: 1.5rem; }
header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
h1 { font-size: 1.5rem; }
.toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: .5rem; }
.filters, .actions { display: flex; gap: .5rem; flex-wrap: wrap; }
select { padding: .5rem .7rem; border: 1px solid #ddd; border-radius: 4px; font-size: .9rem; }
.table-wrap { background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: .75rem 1rem; text-align: left; border-bottom: 1px solid #f0f0f0; font-size: .9rem; }
th { background: #f8f8f8; font-weight: 600; }
.center { text-align: center; color: #888; padding: 2rem; }
.pagination { display: flex; justify-content: center; align-items: center; gap: 1rem; margin-top: 1rem; }
.pagination button { padding: .4rem .9rem; border: 1px solid #ddd; border-radius: 4px; background: #fff; }
.pagination button:disabled { opacity: .4; }
.badge-on { background: #dcfce7; color: #16a34a; padding: .2rem .6rem; border-radius: 99px; font-size: .8rem; }
.badge-off { background: #fee2e2; color: #dc2626; padding: .2rem .6rem; border-radius: 99px; font-size: .8rem; }
.btn-primary { padding: .5rem 1rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; font-size: .9rem; }
.btn-secondary { padding: .5rem 1rem; background: #e5e7eb; color: #333; border: none; border-radius: 4px; font-size: .9rem; }
.btn-danger { padding: .5rem 1rem; background: #dc2626; color: #fff; border: none; border-radius: 4px; font-size: .9rem; }
.btn-danger:disabled { opacity: .4; }
.btn-logout { padding: .4rem .9rem; background: #6b7280; color: #fff; border: none; border-radius: 4px; }
.btn-sm { padding: .3rem .6rem; border: none; border-radius: 4px; font-size: .8rem; margin-right: .3rem; }
.btn-edit { background: #dbeafe; color: #1d4ed8; }
.btn-del { background: #fee2e2; color: #dc2626; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.4); display: flex; justify-content: center; align-items: center; z-index: 100; }
.modal { background: #fff; border-radius: 8px; padding: 2rem; width: 480px; max-width: 95vw; max-height: 90vh; overflow-y: auto; }
.modal h3 { margin-bottom: 1.2rem; font-size: 1.2rem; }
.modal label { display: block; margin-bottom: .3rem; font-size: .85rem; font-weight: 600; }
.modal input, .modal select, .modal textarea { width: 100%; padding: .6rem .8rem; border: 1px solid #ddd; border-radius: 4px; margin-bottom: .8rem; font-size: .9rem; }
.checkbox-label { display: flex; align-items: center; gap: .5rem; margin-bottom: 1rem; font-weight: 600; }
.checkbox-label input { width: auto; margin: 0; }
.modal-actions { display: flex; justify-content: flex-end; gap: .5rem; margin-top: .5rem; }
.error { color: #dc2626; margin-bottom: 1rem; font-size: .9rem; }
.field-error { color: #dc2626; font-size: .8rem; margin-top: -.6rem; margin-bottom: .6rem; }
</style>
