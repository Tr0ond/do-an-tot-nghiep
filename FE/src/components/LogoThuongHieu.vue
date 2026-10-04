<template>
  <img
    class="logo-thuong-hieu"
    :src="duongDanLogo"
    alt=""
    aria-hidden="true"
    width="56"
    height="56"
    decoding="async"
    draggable="false"
  />
</template>

<script>
export default {
  name: 'LogoThuongHieu',
  computed: {
    duongDanLogo() {
      return `${import.meta.env.BASE_URL}images/logo-fitness-neon.png`
    },
  },
}
</script>

<style scoped>
.logo-thuong-hieu {
  display: block;
  width: var(--kich-thuoc-logo, 56px);
  height: var(--kich-thuoc-logo, 56px);
  flex-shrink: 0;
  object-fit: contain;
}
</style>
