import { defineStore } from 'pinia'

export const useChuDeStore = defineStore('chuDe', {
  state: () => ({
    // Mặc định ban đầu lấy từ localStorage hoặc chế độ tối dark
    chuDeHienTai:
      (typeof localStorage !== 'undefined' && localStorage.getItem('gym_theme')) || 'dark',
  }),

  getters: {
    laChuDeToi: (state) => state.chuDeHienTai === 'dark',
    laChuDeSang: (state) => state.chuDeHienTai === 'light',
  },

  actions: {
    /**
     * Thiết lập chế độ chủ đề (dark hoặc light)
     * @param {'dark' | 'light'} tenChuDe
     */
    thietLapChuDe(tenChuDe) {
      const chuDeHopLe = tenChuDe === 'light' ? 'light' : 'dark'
      this.chuDeHienTai = chuDeHopLe

      if (typeof localStorage !== 'undefined') {
        localStorage.setItem('gym_theme', chuDeHopLe)
      }

      if (typeof document !== 'undefined') {
        document.documentElement.setAttribute('data-theme', chuDeHopLe)
        document.documentElement.setAttribute('data-bs-theme', chuDeHopLe)
        if (chuDeHopLe === 'dark') {
          document.documentElement.classList.add('dark-theme')
          document.documentElement.classList.remove('light-theme')
        } else {
          document.documentElement.classList.add('light-theme')
          document.documentElement.classList.remove('dark-theme')
        }
      }
    },

    /**
     * Chuyển đổi qua lại giữa Dark theme và Light theme
     */
    chuyenDoiChuDe() {
      const chuDeMoi = this.chuDeHienTai === 'dark' ? 'light' : 'dark'
      this.thietLapChuDe(chuDeMoi)
    },

    /**
     * Khởi tạo chủ đề khi ứng dụng tải trang
     */
    khoiTaoChuDe() {
      let chuDeLuu = 'dark'
      if (typeof localStorage !== 'undefined') {
        const daLuu = localStorage.getItem('gym_theme')
        if (daLuu === 'light' || daLuu === 'dark') {
          chuDeLuu = daLuu
        }
      }
      this.thietLapChuDe(chuDeLuu)
    },
  },
})
