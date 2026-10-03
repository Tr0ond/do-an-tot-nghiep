<template>
  <RouterView v-slot="{ Component }">
    <component :is="Component" v-if="Component" />
    <main v-else class="container py-5 text-center" role="status">Đang tải trang…</main>
  </RouterView>
</template>

<script>
import { useChuDeStore } from './stores/chuDe'
import { useXacThucStore } from './stores/xacThuc'
import { useChatStore } from './stores/chat'
import { useThongBaoStore } from './stores/thongBao'

export default {
  name: 'App',
  computed: {
    xacThuc() {
      return useXacThucStore()
    },
  },
  watch: {
    'xacThuc.taiKhoan': {
      immediate: true,
      handler(nguoi) {
        useThongBaoStore().khoiTao(nguoi?.id)
        const chat = useChatStore()
        if (['KHACH_HANG', 'HUAN_LUYEN_VIEN'].includes(nguoi?.vai_tro)) {
          chat.ketNoi(nguoi.id)
        } else {
          chat.dongKetNoi()
        }
      },
    },
  },
  mounted() {
    // Khởi tạo chủ đề giao diện (Dark / Light) khi ứng dụng tải
    const chuDeStore = useChuDeStore()
    chuDeStore.khoiTaoChuDe()
  },
  beforeUnmount() {
    useThongBaoStore().dong()
    useChatStore().dongKetNoi()
  },
}
</script>
