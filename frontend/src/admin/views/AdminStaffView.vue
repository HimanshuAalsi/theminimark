<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { UserPlus } from 'lucide-vue-next'
import {
  adminCreateStaff,
  adminListStaff,
  adminUpdateStaff,
  type AdminStaffUser,
} from '@/admin/lib/adminApi'

const items = ref<AdminStaffUser[]>([])
const error = ref('')
const busy = ref(false)
const showForm = ref(false)

const form = ref({
  email: '',
  fullName: '',
  password: '',
  role: 'staff' as 'admin' | 'manager' | 'staff',
})

async function load() {
  error.value = ''
  try {
    const res = await adminListStaff()
    items.value = res.items
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load staff'
  }
}

async function create() {
  busy.value = true
  error.value = ''
  try {
    const res = await adminCreateStaff(form.value)
    if (!res.ok) {
      error.value = res.message ?? 'Create failed'
      return
    }
    showForm.value = false
    form.value = { email: '', fullName: '', password: '', role: 'staff' }
    await load()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Create failed'
  } finally {
    busy.value = false
  }
}

async function changeRole(user: AdminStaffUser, role: string) {
  await adminUpdateStaff(user.id, { role })
  await load()
}

onMounted(load)
</script>

<template>
  <div>
    <div class="admin-page-head">
      <h1 class="admin-page-title">Staff</h1>
      <button type="button" class="admin-btn" @click="showForm = !showForm">
        <UserPlus :size="16" />
        Add staff
      </button>
    </div>
    <p class="admin-meta">
      Roles: <strong>admin</strong> (full access), <strong>manager</strong> (all except staff management),
      <strong>staff</strong> (orders, catalog, customers).
    </p>
    <p v-if="error" class="admin-error">{{ error }}</p>

    <div v-if="showForm" class="admin-card" style="margin-bottom: 1rem">
      <h2 style="margin: 0 0 0.75rem; font-size: 1rem">New staff account</h2>
      <div class="admin-form-grid">
        <label>
          Email
          <input v-model="form.email" type="email" required />
        </label>
        <label>
          Full name
          <input v-model="form.fullName" type="text" />
        </label>
        <label>
          Password
          <input v-model="form.password" type="password" required />
        </label>
        <label>
          Role
          <select v-model="form.role">
            <option value="staff">Staff</option>
            <option value="manager">Manager</option>
            <option value="admin">Admin</option>
          </select>
        </label>
      </div>
      <button type="button" class="admin-btn" :disabled="busy" @click="create">Create account</button>
    </div>

    <div class="admin-card admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Joined</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in items" :key="u.id">
            <td>{{ u.fullName }}</td>
            <td>{{ u.email }}</td>
            <td>
              <select :value="u.role" @change="changeRole(u, ($event.target as HTMLSelectElement).value)">
                <option value="admin">admin</option>
                <option value="manager">manager</option>
                <option value="staff">staff</option>
              </select>
            </td>
            <td>{{ u.createdAt.slice(0, 10) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
