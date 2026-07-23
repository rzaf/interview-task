<script setup>
import { ref, watch, onMounted, onBeforeUnmount, computed } from 'vue'
const userId = ref(1)
const page = ref(1)
const per = ref(20)
const loading = ref(false)
const data = ref([])
const count = ref(0)
const error = ref('')
let currentController = null
let debounceTimer = null

const isEmpty = computed(() => !loading.value && !error.value && count.value === 0)
const hasOrders = computed(() => !loading.value && !error.value && count.value > 0)

async function fetchOrders() {
  if (currentController) {
    currentController.abort()
  }

  const controller = new AbortController()
  currentController = controller

  loading.value = true
  error.value = ''

  try {
    const q = new URLSearchParams({ user_id: String(userId.value), page: String(page.value), per_page: String(per.value) })
    const res = await fetch(`http://127.0.0.1:8080/api/orders?${q.toString()}`, {
      signal: controller.signal,
    })

    if (!res.ok) {
      throw new Error(`Request failed with ${res.status}`)
    }

    const [json] = await Promise.all([res.json()])

    if (controller.signal.aborted) {
      return
    }

    data.value = json.data
    count.value = json.count
  } catch (e) {
    if (e.name === 'AbortError') {
      return
    }

    error.value = String(e)
    data.value = []
    count.value = 0
  } finally {
    if (currentController === controller) {
      loading.value = false
      currentController = null
    }
  }
}

onMounted(fetchOrders)
watch([userId, page, per], () => {
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }

  if (currentController) {
    currentController.abort()
  }

  debounceTimer = setTimeout(() => {
    fetchOrders()
  }, 600) // 600 ms
})

onBeforeUnmount(() => {
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }
  if (currentController) {
    currentController.abort()
  }
})
</script>

<template>
  <section>
    <div class="flex flex-wrap items-center gap-3 mb-4">
      <label class="flex items-center gap-2">
        User:
        <input type="number" v-model="userId" min="1" class="w-20 px-2 py-1 text-sm border rounded border-slate-300 focus:border-slate-500 focus:outline-none" />
      </label>
      <label class="flex items-center gap-2">
        Page:
        <input type="number" v-model="page" min="1" class="w-20 px-2 py-1 text-sm border rounded border-slate-300 focus:border-slate-500 focus:outline-none" />
      </label>
      <label class="flex items-center gap-2">
        Per:
        <input type="number" v-model="per" min="1" max="100" class="w-20 px-2 py-1 text-sm border rounded border-slate-300 focus:border-slate-500 focus:outline-none" />
      </label>
      <button
        @click="fetchOrders"
        :disabled="loading"
        class="px-4 py-2 text-sm font-medium text-white transition rounded bg-slate-800 hover:bg-slate-700 disabled:cursor-not-allowed disabled:bg-slate-400"
      >
        Reload
      </button>
    </div>

    <div v-if="loading" class="mt-2">
      <div class="mb-3 text-sm text-slate-500">Loading orders…</div>
      <table class="min-w-full border border-separate border-spacing-0 border-slate-200">
        <thead>
          <tr class="text-xs tracking-wider text-left uppercase bg-slate-50 text-slate-500">
            <th class="px-3 py-2 border border-slate-200">ID</th>
            <th class="px-3 py-2 border border-slate-200">Total</th>
            <th class="px-3 py-2 border border-slate-200">Created At</th>
            <th class="px-3 py-2 border border-slate-200">Payment</th>
            <th class="px-3 py-2 border border-slate-200">Items</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="n in 5" :key="n">
            <td class="px-3 py-2 border border-slate-200"><div class="relative w-full h-4 overflow-hidden rounded-lg skeleton-cell bg-slate-200"></div></td>
            <td class="px-3 py-2 border border-slate-200"><div class="relative w-full h-4 overflow-hidden rounded-lg skeleton-cell bg-slate-200"></div></td>
            <td class="px-3 py-2 border border-slate-200"><div class="relative w-full h-4 overflow-hidden rounded-lg skeleton-cell bg-slate-200"></div></td>
            <td class="px-3 py-2 border border-slate-200"><div class="relative w-full h-4 overflow-hidden rounded-lg skeleton-cell bg-slate-200"></div></td>
            <td class="px-3 py-2 border border-slate-200"><div class="relative w-full h-4 overflow-hidden rounded-lg skeleton-cell bg-slate-200"></div></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else-if="error" class="p-4 my-2 text-sm border rounded-xl border-slate-200 bg-slate-50 text-slate-700">
      Error: {{ error }}
    </div>

    <div v-else>
      <div v-if="isEmpty" class="p-4 my-2 text-sm border rounded-xl border-slate-200 bg-slate-50 text-slate-700">
        <strong class="block text-base text-slate-900">No orders found.</strong>
        <p class="mt-2 text-slate-600">Try a different user, page, or per-page value.</p>
      </div>

      <div v-if="hasOrders">
        <div class="mb-3 text-sm text-slate-600">Showing {{ data.length }} of {{ count }} orders for user #{{ userId }}</div>
        <table class="min-w-full text-sm border border-separate border-spacing-0 border-slate-200">
          <thead>
            <tr class="text-xs tracking-wider text-left uppercase bg-slate-50 text-slate-500">
              <th class="px-3 py-2 border border-slate-200">ID</th>
              <th class="px-3 py-2 border border-slate-200">Total</th>
              <th class="px-3 py-2 border border-slate-200">Created At</th>
              <th class="px-3 py-2 border border-slate-200">Payment</th>
              <th class="px-3 py-2 border border-slate-200">Items</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in data" :key="r.id" class="hover:bg-slate-50">
              <td class="px-3 py-2 border border-slate-200">{{ r.id }}</td>
              <td class="px-3 py-2 border border-slate-200">{{ r.total }}</td>
              <td class="px-3 py-2 border border-slate-200">{{ r.created_at }}</td>
              <td class="px-3 py-2 border border-slate-200">{{ r.payment?.method }} / {{ r.payment?.status }}</td>
              <td class="px-3 py-2 border border-slate-200">{{ r.items_count }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>

<style scoped>
.skeleton-cell::after {
  content: '';
  position: absolute;
  inset: 0;
  transform: translateX(-100%);
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.7), transparent);
  animation: shimmer 1.2s infinite;
}
@keyframes shimmer {
  100% {
    transform: translateX(100%);
  }
}
</style>
