<template>
  <button
    type="button"
    class="btn-theme-toggle"
    :class="[`btn-theme-${kichThuoc}`, laChuDeToi ? 'is-dark' : 'is-light']"
    :title="tieuDeGoiY"
    :aria-label="tieuDeGoiY"
    @click="chuyenDoi"
  >
    <span class="icon-toggle-wrapper">
      <i v-if="laChuDeToi" class="bi bi-sun-fill icon-sun" aria-hidden="true"></i>
      <i v-else class="bi bi-moon-stars-fill icon-moon" aria-hidden="true"></i>
    </span>
    <span v-if="hienThiNhan" class="label-theme">
      {{ laChuDeToi ? 'Chế độ tối' : 'Chế độ sáng' }}
    </span>
  </button>
</template>

<script>
import { useChuDeStore } from '../stores/chuDe'

export default {
  name: 'NutChuyenChuDe',
  props: {
    kichThuoc: {
      type: String,
      default: 'md', // 'sm' | 'md' | 'lg'
      validator: (giaTri) => ['sm', 'md', 'lg'].includes(giaTri),
    },
    hienThiNhan: {
      type: Boolean,
      default: false,
    },
  },
  computed: {
    chuDeStore() {
      return useChuDeStore()
    },
    laChuDeToi() {
      return this.chuDeStore.laChuDeToi
    },
    tieuDeGoiY() {
      return this.laChuDeToi ? 'Chuyển sang giao diện sáng' : 'Chuyển sang giao diện tối'
    },
  },
  methods: {
    chuyenDoi() {
      this.chuDeStore.chuyenDoiChuDe()
    },
  },
}
</script>

<style scoped>
.btn-theme-toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: var(--mau-the, var(--mau-the));
  border: 1px solid var(--mau-vien, rgba(255, 255, 255, 0.12));
  color: var(--mau-chu, #ffffff);
  border-radius: var(--bo-goc-tron, 9999px);
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: var(--bong-nhe);
  user-select: none;
  padding: 0;
  position: relative;
  overflow: hidden;
}

.btn-theme-sm {
  width: 34px;
  height: 34px;
  font-size: 0.95rem;
}

.btn-theme-md {
  width: 40px;
  height: 40px;
  font-size: 1.1rem;
}

.btn-theme-lg {
  width: 46px;
  height: 46px;
  font-size: 1.25rem;
}

/* Khi có hiển thị nhãn chữ */
.btn-theme-toggle:has(.label-theme) {
  width: auto;
  padding: 6px 14px;
  border-radius: var(--bo-goc-md, 8px);
}

.icon-toggle-wrapper {
  display: grid;
  place-items: center;
  transition:
    transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1),
    color 0.25s ease;
}

/* Icon mặt trời trong Dark Mode */
.icon-sun {
  color: var(--mau-canh-bao);
  filter: drop-shadow(0 0 6px rgba(251, 191, 36, 0.5));
}

/* Icon mặt trăng trong Light Mode */
.icon-moon {
  color: var(--mau-thong-tin);
  filter: drop-shadow(0 0 6px rgba(99, 102, 241, 0.4));
}

.label-theme {
  font-size: 0.85rem;
  font-weight: 650;
  color: var(--mau-chu);
}

.btn-theme-toggle:hover {
  transform: translateY(-2px) scale(1.04);
  border-color: var(--mau-chinh);
  box-shadow: var(--bong-nhe);
}

.btn-theme-toggle:hover .icon-toggle-wrapper {
  transform: rotate(20deg) scale(1.1);
}

.btn-theme-toggle:active {
  transform: scale(0.96);
}
</style>
