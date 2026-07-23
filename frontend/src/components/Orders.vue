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
    <div class="flex flex-wrap gap-3 mb-4 items-center">
      <label class="flex items-center gap-2">
        User:
        <input type="number" v-model="userId" min="1" class="w-20 rounded border border-slate-300 px-2 py-1 text-sm focus:border-slate-500 focus:outline-none" />
      </label>
      <label class="flex items-center gap-2">
        Page:
        <input type="number" v-model="page" min="1" class="w-20 rounded border border-slate-300 px-2 py-1 text-sm focus:border-slate-500 focus:outline-none" />
      </label>
      <label class="flex items-center gap-2">
        Per:
        <input type="number" v-model="per" min="1" max="100" class="w-20 rounded border border-slate-300 px-2 py-1 text-sm focus:border-slate-500 focus:outline-none" />
      </label>
      <button
        @click="fetchOrders"
        :disabled="loading"
        class="rounded bg-slate-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:bg-slate-400"
      >
        Reload
      </button>
    </div>

    <div v-if="loading" class="mt-2">
      <div class="mb-3 text-sm text-slate-500">Loading orders…</div>
      <table class="min-w-full border-separate border-spacing-0 border border-slate-200">
        <thead>
          <tr class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
            <th class="border border-slate-200 px-3 py-2">ID</th>
            <th class="border border-slate-200 px-3 py-2">Total</th>
            <th class="border border-slate-200 px-3 py-2">Created At</th>
            <th class="border border-slate-200 px-3 py-2">Payment</th>
            <th class="border border-slate-200 px-3 py-2">Items</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="n in 5" :key="n">
            <td class="border border-slate-200 px-3 py-2"><div class="relative h-4 w-full overflow-hidden rounded-lg bg-slate-200"></div></td>
            <td class="border border-slate-200 px-3 py-2"><div class="relative h-4 w-full overflow-hidden rounded-lg bg-slate-200"></div></td>
            <td class="border border-slate-200 px-3 py-2"><div class="relative h-4 w-full overflow-hidden rounded-lg bg-slate-200"></div></td>
            <td class="border border-slate-200 px-3 py-2"><div class="relative h-4 w-full overflow-hidden rounded-lg bg-slate-200"></div></td>
            <td class="border border-slate-200 px-3 py-2"><div class="relative h-4 w-full overflow-hidden rounded-lg bg-slate-200"></div></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else-if="error" class="my-2 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
      Error: {{ error }}
    </div>

    <div v-else>
      <div v-if="isEmpty" class="my-2 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
        <strong class="block text-base text-slate-900">No orders found.</strong>
        <p class="mt-2 text-slate-600">Try a different user, page, or per-page value.</p>
      </div>

      <div v-if="hasOrders">
        <div class="mb-3 text-sm text-slate-600">Showing {{ data.length }} of {{ count }} orders for user #{{ userId }}</div>
        <table class="min-w-full border-separate border-spacing-0 border border-slate-200 text-sm">
          <thead>
            <tr class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
              <th class="border border-slate-200 px-3 py-2">ID</th>
              <th class="border border-slate-200 px-3 py-2">Total</th>
              <th class="border border-slate-200 px-3 py-2">Created At</th>
              <th class="border border-slate-200 px-3 py-2">Payment</th>
              <th class="border border-slate-200 px-3 py-2">Items</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in data" :key="r.id" class="hover:bg-slate-50">
              <td class="border border-slate-200 px-3 py-2">{{ r.id }}</td>
              <td class="border border-slate-200 px-3 py-2">{{ r.total }}</td>
              <td class="border border-slate-200 px-3 py-2">{{ r.created_at }}</td>
              <td class="border border-slate-200 px-3 py-2">{{ r.payment?.method }} / {{ r.payment?.status }}</td>
              <td class="border border-slate-200 px-3 py-2">{{ r.items_count }}</td>
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
