<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Plus, Trash2, X } from 'lucide-vue-next'
import AdminDateTimePicker from '@/admin/components/AdminDateTimePicker.vue'
import AdminField from '@/admin/components/AdminField.vue'
import {
  adminCreateCoupon,
  adminDeleteCoupon,
  adminListCoupons,
  adminUpdateCoupon,
  type AdminCoupon,
} from '@/admin/lib/adminApi'

const items = ref<AdminCoupon[]>([])
const error = ref('')
const busy = ref(false)
const showForm = ref(false)

const form = ref({
  code: '',
  description: '',
  discountType: 'percent' as 'percent' | 'fixed',
  discountValue: 10,
  minOrderInr: 0,
  maxUses: '' as string | number,
  firstOrderOnly: false,
  isActive: true,
  startsAt: null as Date | null,
  endsAt: null as Date | null,
})

function resetForm() {
  form.value = {
    code: '',
    description: '',
    discountType: 'percent',
    discountValue: 10,
    minOrderInr: 0,
    maxUses: '',
    firstOrderOnly: false,
    isActive: true,
    startsAt: null,
    endsAt: null,
  }
}

function formatApiDate(d: Date | null): string | null {
  if (!d) return null
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${d.getUTCFullYear()}-${pad(d.getUTCMonth() + 1)}-${pad(d.getUTCDate())} ${pad(d.getUTCHours())}:${pad(d.getUTCMinutes())}:${pad(d.getUTCSeconds())}`
}

async function load() {
  error.value = ''
  try {
    const res = await adminListCoupons()
    items.value = res.items
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load coupons'
  }
}

async function create() {
  if (!form.value.code.trim()) {
    error.value = 'Coupon code is required'
    return
  }
  busy.value = true
  error.value = ''
  try {
    const res = await adminCreateCoupon({
      code: form.value.code.trim().toUpperCase(),
      description: form.value.description.trim(),
      discountType: form.value.discountType,
      discountValue: form.value.discountValue,
      minOrderInr: form.value.minOrderInr,
      maxUses: form.value.maxUses === '' ? null : Number(form.value.maxUses),
      firstOrderOnly: form.value.firstOrderOnly,
      isActive: form.value.isActive,
      startsAt: formatApiDate(form.value.startsAt),
      endsAt: formatApiDate(form.value.endsAt),
    })
    if (!res.ok) {
      error.value = res.message ?? 'Create failed'
      return
    }
    showForm.value = false
    resetForm()
    await load()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Create failed'
  } finally {
    busy.value = false
  }
}

async function toggleActive(c: AdminCoupon) {
  await adminUpdateCoupon(c.id, { isActive: !c.isActive })
  await load()
}

async function remove(c: AdminCoupon) {
  if (!confirm(`Delete coupon ${c.code}?`)) return
  await adminDeleteCoupon(c.id)
  await load()
}

function openForm() {
  resetForm()
  error.value = ''
  showForm.value = true
}

onMounted(load)
</script>

<template>
  <div>
    <div class="admin-page-head">
      <h1 class="admin-page-title">Coupons</h1>
      <button type="button" class="admin-btn" @click="openForm">
        <Plus :size="16" />
        New coupon
      </button>
    </div>
    <p v-if="error && !showForm" class="admin-error">{{ error }}</p>

    <Teleport to="body">
      <div v-if="showForm" class="admin-sheet-backdrop" @click.self="showForm = false">
        <div class="admin-sheet" role="dialog" aria-modal="true" aria-labelledby="coupon-form-title">
          <div class="admin-sheet__head">
            <div>
              <h2 id="coupon-form-title" class="admin-sheet__title">Create coupon</h2>
              <p class="admin-meta" style="margin: 0.25rem 0 0">Set up a discount code for checkout</p>
            </div>
            <button type="button" class="admin-sheet__close" aria-label="Close" @click="showForm = false">
              <X :size="18" />
            </button>
          </div>

          <div class="admin-sheet__body">
            <p v-if="error" class="admin-error">{{ error }}</p>

            <div class="admin-form-grid">
              <AdminField label="Coupon code" required hint="Shown at checkout — e.g. MINIFIRST10" :span="12">
                <input
                  v-model="form.code"
                  class="admin-input"
                  type="text"
                  placeholder="MINIFIRST10"
                  autocapitalize="characters"
                  autocomplete="off"
                />
              </AdminField>

              <AdminField label="Description" hint="Internal note for your team" :span="12">
                <input
                  v-model="form.description"
                  class="admin-input"
                  type="text"
                  placeholder="First order 10% off"
                />
              </AdminField>

              <AdminField label="Discount settings" :span="12">
                <div class="admin-form-row-3">
                  <div>
                    <span class="admin-field__hint" style="display: block; margin-bottom: 0.35rem">Type</span>
                    <select v-model="form.discountType" class="admin-select">
                      <option value="percent">Percent (%)</option>
                      <option value="fixed">Fixed (₹)</option>
                    </select>
                  </div>
                  <div>
                    <span class="admin-field__hint" style="display: block; margin-bottom: 0.35rem">
                      {{ form.discountType === 'percent' ? 'Amount (%)' : 'Amount (₹)' }}
                    </span>
                    <input v-model.number="form.discountValue" class="admin-input" type="number" min="1" step="0.01" />
                  </div>
                  <div>
                    <span class="admin-field__hint" style="display: block; margin-bottom: 0.35rem">Min order (₹)</span>
                    <input v-model.number="form.minOrderInr" class="admin-input" type="number" min="0" step="1" />
                  </div>
                </div>
              </AdminField>

              <AdminField label="Max redemptions" hint="Leave empty for unlimited uses" :span="12">
                <input v-model="form.maxUses" class="admin-input" type="number" min="1" placeholder="Unlimited" />
              </AdminField>

              <AdminField label="Valid from" hint="Optional — leave empty to start immediately" :span="12">
                <AdminDateTimePicker v-model="form.startsAt" placeholder="Starts immediately" />
              </AdminField>

              <AdminField label="Valid until" hint="Optional — leave empty for no expiry" :span="12">
                <AdminDateTimePicker v-model="form.endsAt" placeholder="No expiry date" />
              </AdminField>

              <AdminField :span="12">
                <div class="admin-check-panel">
                  <label class="admin-check">
                    <input v-model="form.firstOrderOnly" type="checkbox" />
                    First order only
                  </label>
                  <label class="admin-check">
                    <input v-model="form.isActive" type="checkbox" />
                    Active on checkout
                  </label>
                </div>
              </AdminField>
            </div>
          </div>

          <div class="admin-sheet__foot">
            <button type="button" class="admin-btn" :disabled="busy" @click="create">
              {{ busy ? 'Creating…' : 'Create coupon' }}
            </button>
            <button type="button" class="admin-btn admin-btn--ghost" @click="showForm = false">Cancel</button>
          </div>
        </div>
      </div>
    </Teleport>

    <div class="admin-card admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Code</th>
            <th>Discount</th>
            <th>Uses</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in items" :key="c.id">
            <td>
              <strong>{{ c.code }}</strong>
              <span v-if="c.description" class="admin-cell-muted" style="display: block">{{ c.description }}</span>
            </td>
            <td>
              {{ c.discountType === 'percent' ? `${c.discountValue}%` : `₹${c.discountValue}` }}
              <span v-if="c.firstOrderOnly" class="admin-badge">1st order</span>
            </td>
            <td>{{ c.usedCount }}{{ c.maxUses ? ` / ${c.maxUses}` : '' }}</td>
            <td>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" @click="toggleActive(c)">
                {{ c.isActive ? 'Active' : 'Inactive' }}
              </button>
            </td>
            <td>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm admin-btn--danger" @click="remove(c)">
                <Trash2 :size="14" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="!items.length" class="admin-meta" style="padding: 1rem">No coupons yet. Create your first promo code.</p>
    </div>
  </div>
</template>
