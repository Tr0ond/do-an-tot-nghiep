import { defineStore } from 'pinia'
import xacThucService from '../services/xacThucService'

export const useXacThucStore = defineStore('xacThuc', {
  state: () => ({
    taiKhoan: null,
    daKhoiTao: false,
  }),
  getters: {
    daDangNhap: (state) => Boolean(state.taiKhoan),
    duongDanCaNhan: (state) =>
      ({ ADMIN: '/admin/tong-quan', HUAN_LUYEN_VIEN: '/pt/tong-quan' })[state.taiKhoan?.vai_tro] ||
      '/khach-hang/tong-quan',
  },
  actions: {
    async taiTaiKhoan() {
      try {
        this.taiKhoan = (await xacThucService.taiTaiKhoan()).data
      } catch (loi) {
        if (![401, 403, 419].includes(loi.response?.status)) throw loi
        this.taiKhoan = null
      } finally {
        this.daKhoiTao = true
      }
    },
    async dangKy(duLieu) {
      this.taiKhoan = (await xacThucService.dangKy(duLieu)).data
      this.daKhoiTao = true
    },
    async dangNhap(duLieu) {
      this.taiKhoan = (await xacThucService.dangNhap(duLieu)).data
      this.daKhoiTao = true
    },
    async dangXuat() {
      try {
        await xacThucService.dangXuat()
      } catch (loi) {
        // CSRF lỗi hoặc mất mạng chưa chứng minh server đã hủy session.
        if (loi.response?.status !== 401) throw loi
      }
      this.taiKhoan = null
      this.daKhoiTao = true
    },
  },
})
