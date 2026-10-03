<template>
  <div ref="khungAnh" class="chat-image">
    <button
      v-if="srcTam || nguonAnh"
      type="button"
      class="chat-image-open"
      :aria-label="`Xem ảnh ${anh.ten}`"
      @click="$emit('xem-anh', { src: srcTam || nguonAnh, ten: anh.ten })"
    >
      <img :src="srcTam || nguonAnh" :alt="anh.ten" @load="$emit('da-tai')" />
      <span class="chat-image-overlay" aria-hidden="true">
        <i class="bi bi-arrows-fullscreen"></i>
      </span>
    </button>
    <div v-else class="chat-image-state" :role="loiAnh ? 'alert' : 'status'">
      <i class="bi bi-image" aria-hidden="true"></i>
      <span>{{ loiAnh || 'Đang tải ảnh…' }}</span>
      <button v-if="loiAnh" type="button" class="chat-retry" @click="taiAnh">Thử lại</button>
    </div>
  </div>
</template>

<script>
import chatService from '../services/chatService'

export default {
  name: 'AnhTinNhan',
  props: {
    anh: { type: Object, required: true },
    hoiThoaiId: { type: Number, required: true },
    tinId: { type: Number, default: null },
    srcTam: { type: String, default: '' },
  },
  emits: ['xem-anh', 'da-tai', 'mat-quyen'],
  data() {
    return { nguonAnh: '', loiAnh: '', dangTai: false, boHuy: null, theoDoi: null }
  },
  mounted() {
    if (this.srcTam) return
    // Chỉ tải ảnh gần vùng đang xem; lịch sử dài không tải đồng loạt hàng trăm tệp.
    this.theoDoi = new IntersectionObserver(
      (muc) => {
        if (muc.some((m) => m.isIntersecting)) {
          this.theoDoi.disconnect()
          void this.taiAnh()
        }
      },
      { rootMargin: '150px' },
    )
    this.theoDoi.observe(this.$refs.khungAnh)
  },
  beforeUnmount() {
    this.theoDoi?.disconnect()
    this.boHuy?.abort()
    if (this.nguonAnh) URL.revokeObjectURL(this.nguonAnh)
  },
  methods: {
    async taiAnh() {
      if (!this.tinId || this.dangTai) return
      this.dangTai = true
      this.loiAnh = ''
      this.boHuy = new AbortController()
      const signal = this.boHuy.signal
      try {
        const blob = await chatService.taiAnh(this.hoiThoaiId, this.tinId, this.anh.vi_tri, signal)
        if (signal.aborted) return
        this.nguonAnh = URL.createObjectURL(blob)
      } catch (loi) {
        if (signal.aborted) return
        this.loiAnh = 'Không tải được ảnh.'
        if ([401, 403, 404, 419].includes(loi.response?.status)) this.$emit('mat-quyen', loi)
      } finally {
        if (!signal.aborted) this.dangTai = false
      }
    },
  },
}
</script>
