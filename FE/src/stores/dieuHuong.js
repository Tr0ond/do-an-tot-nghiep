import { defineStore } from 'pinia'

export const useDieuHuongStore = defineStore('dieuHuong', {
  state: () => ({ thuGon: false, daKhoiTao: false }),
  actions: {
    khoiTao() {
      if (this.daKhoiTao) return
      this.daKhoiTao = true
      try {
        this.thuGon = localStorage.getItem('gym_sidebar_collapsed') === 'true'
      } catch {
        // Trình duyệt chặn lưu trữ vẫn cho phép sử dụng menu trong phiên hiện tại.
      }
    },
    doiTrangThai() {
      this.thuGon = !this.thuGon
      try {
        localStorage.setItem('gym_sidebar_collapsed', String(this.thuGon))
      } catch {
        // Chỉ lưu tùy chọn giao diện, không lưu dữ liệu tài khoản.
      }
    },
  },
})
