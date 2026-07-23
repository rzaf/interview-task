<!-- src/App.vue -->
<script setup>
import Orders from './components/Orders.vue'
</script>

<template>
  <main class="mx-auto max-w-6xl p-8">
    <h1 class="mb-6 text-3xl font-bold">
      Orders (Candidate {{ TOKEN }})
    </h1>

    <Orders />
  </main>
</template>