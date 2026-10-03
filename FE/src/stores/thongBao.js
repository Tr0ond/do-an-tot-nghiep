import { defineStore } from 'pinia'
import thongBaoService from '../services/thongBaoService'
import { layLoiApi } from '../utils/loiApi'
import { useXacThucStore } from './xacThuc'

let boHenGio
let huySuKien
let boHuy
let boHuyDoc

export const useThongBaoStore = defineStore('thongBao', {
  state: () => ({
    taiKhoanId: null,
    danhSach: [],
    soChuaDoc: 0,
    trang: 0,
    trangCuoi: 1,
    dangTai: false,
    dangDoc: false,
    loi: '',
    maPhien: 0,
  }),
  actions: {
    khoiTao(id) {
      if (this.taiKhoanId === id) return
      this.dong()
      if (!id) return
      this.taiKhoanId = id
      const capNhat = () => {
        if (!document.hidden && !this.dangTai && !this.dangDoc) void this.taiDanhSach()
      }
      window.addEventListener('online', capNhat)
      document.addEventListener('visibilitychange', capNhat)
      huySuKien = () => {
        window.removeEventListener('online', capNhat)
        document.removeEventListener('visibilitychange', capNhat)
      }
      boHenGio = setInterval(capNhat, 45000)
      void this.taiDanhSach()
    },
    async taiDanhSach(taiThem = false) {
      if (!this.taiKhoanId || this.dangDoc || (taiThem && this.dangTai)) return
      boHuy?.abort()
      boHuy = new AbortController()
      const { signal } = boHuy
      const phien = this.maPhien
      this.dangTai = true
      this.loi = ''
      try {
        const response = await thongBaoService.taiDanhSach(taiThem ? this.trang + 1 : 1, signal)
        if (signal.aborted || phien !== this.maPhien) return
        this.danhSach = taiThem
          ? [...new Map([...this.danhSach, ...response.data].map((tin) => [tin.id, tin])).values()]
          : response.data
        this.soChuaDoc = response.meta.so_chua_doc
        this.trang = response.meta.current_page
        this.trangCuoi = response.meta.last_page
      } catch (loi) {
        if (signal.aborted || phien !== this.maPhien) return
        this.xuLyLoi(loi)
      } finally {
        if (!signal.aborted && phien === this.maPhien) this.dangTai = false
      }
    },
    async danhDauDoc(id = null) {
      if (!this.taiKhoanId || this.dangDoc) return false
      boHuy?.abort()
      this.dangTai = false
      boHuyDoc = new AbortController()
      const { signal } = boHuyDoc
      const phien = this.maPhien
      this.dangDoc = true
      this.loi = ''
      let thanhCong = false
      try {
        if (id) await thongBaoService.daDoc(id, signal)
        else await thongBaoService.daDocTatCa(signal)
        thanhCong = !signal.aborted && phien === this.maPhien
      } catch (loi) {
        if (!signal.aborted && phien === this.maPhien) this.xuLyLoi(loi)
      } finally {
        if (phien === this.maPhien) this.dangDoc = false
      }
      if (thanhCong) await this.taiDanhSach()
      return thanhCong && phien === this.maPhien
    },
    xuLyLoi(loi) {
      const ketQua = layLoiApi(loi)
      if (ketQua.hetPhien) {
        this.dong()
        useXacThucStore().taiKhoan = null
      } else this.loi = ketQua.thongBao
    },
    dong() {
      this.maPhien++
      boHuy?.abort()
      boHuyDoc?.abort()
      huySuKien?.()
      clearInterval(boHenGio)
      boHuy = boHuyDoc = huySuKien = boHenGio = undefined
      this.taiKhoanId = null
      this.danhSach = []
      this.soChuaDoc = this.trang = 0
      this.trangCuoi = 1
      this.dangTai = this.dangDoc = false
      this.loi = ''
    },
  },
})
