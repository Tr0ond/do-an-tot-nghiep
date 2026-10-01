<template>
  <RouterView v-slot="{ Component }">
    <component :is="Component" v-if="Component" />
    <main v-else class="container py-5 text-center" role="status">Đang tải trang…</main>
  </RouterView>
</template>

<script>
export default {
  name: 'App',
}
</script>
