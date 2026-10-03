<template>
  <div class="exercise-image-wrapper">
    <!-- Hiệu ứng tải hình ảnh mượt mà -->
    <div v-if="dangTaiAnh && src && !biLoi" class="image-skeleton-shimmer" aria-hidden="true">
      <div class="skeleton-pulse"></div>
    </div>

    <img
      v-if="src && !biLoi"
      :src="src"
      :alt="alt"
      :loading="loading"
      decoding="async"
      class="exercise-img"
      :class="{ 'is-loaded': !dangTaiAnh }"
      @load="hoanTatTai"
      @error="baoLoi"
    />
    <div v-else class="image-fallback" role="img" :aria-label="alt + ' — ảnh chưa khả dụng'">
      <div class="fallback-icon-ring">
        <i class="bi bi-image" aria-hidden="true"></i>
      </div>
      <span>Hình ảnh chưa khả dụng</span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AnhBaiTap',
  props: {
    src: { type: String, default: '' },
    alt: { type: String, required: true },
    loading: { type: String, default: 'lazy' },
  },
  emits: ['loi'],
  data() {
    return {
      biLoi: false,
      dangTaiAnh: true,
    }
  },
  watch: {
    src() {
      this.biLoi = false
      this.dangTaiAnh = true
    },
  },
  methods: {
    hoanTatTai() {
      this.dangTaiAnh = false
    },
    baoLoi() {
      this.biLoi = true
      this.dangTaiAnh = false
      this.$emit('loi')
    },
  },
}
</script>

<style scoped>
.exercise-image-wrapper {
  aspect-ratio: 4 / 3;
  background: #101010;
  display: grid;
  place-items: center;
  overflow: hidden;
  position: relative;
  width: 100%;
}

.exercise-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  min-height: 0;
  opacity: 0;
  transition:
    opacity 0.3s ease,
    transform 0.4s ease;
}

.exercise-img.is-loaded {
  opacity: 1;
}

.exercise-image-wrapper:hover .exercise-img.is-loaded {
  transform: scale(1.04);
}

.image-skeleton-shimmer {
  position: absolute;
  inset: 0;
  background: var(--mau-the-sub, #181818);
  display: flex;
  align-items: center;
  justify-content: center;
}

.skeleton-pulse {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  border: 3px solid rgba(255, 255, 255, 0.12);
  border-top-color: var(--mau-chinh);
  animation: spin 1s linear infinite;
}

.image-fallback {
  display: flex;
  flex-direction: column;
  gap: 10px;
  align-items: center;
  justify-content: center;
  color: var(--mau-phu);
  padding: 24px;
  font-size: 0.8rem;
  font-weight: 500;
}

.fallback-icon-ring {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--mau-the-hover, #e2e8f0);
  display: grid;
  place-items: center;
  font-size: 1.25rem;
  color: var(--mau-phu, #94a3b8);
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
