<template>
  <img
    :src="
      chuyenDong && !giamChuyenDong
        ? '/images/fitforge-ai-gundam-slow.gif'
        : '/images/fitforge-ai-gundam-slow-still.png'
    "
    alt="FitForge AI — robot tập luyện"
    class="ai-mascot"
    width="512"
    height="512"
  />
</template>
<script>
export default {
  name: 'MascotTroLy',
  props: { chuyenDong: { type: Boolean, default: false } },
  data() {
    return { giamChuyenDong: true, media: null }
  },
  mounted() {
    this.media = window.matchMedia('(prefers-reduced-motion: reduce)')
    this.capNhatChuyenDong(this.media)
    this.media.addEventListener('change', this.capNhatChuyenDong)
  },
  beforeUnmount() {
    this.media?.removeEventListener('change', this.capNhatChuyenDong)
  },
  methods: {
    capNhatChuyenDong(e) {
      this.giamChuyenDong = e.matches
    },
  },
}
</script>
<style scoped>
.ai-mascot {
  display: block;
  width: var(--kich-thuoc-mascot, 56px);
  height: var(--kich-thuoc-mascot, 56px);
  object-fit: contain;
  flex-shrink: 0;
}
</style>
