import { defineStore } from 'pinia'
import chatService from '../services/chatService'
import { ketNoiChat } from '../services/chatRealtime'
import { useXacThucStore } from './xacThuc'

let huySocket
let boHenGio
let huySuKien
let boHuy

export const useChatStore = defineStore('chat', {
  state: () => ({
    taiKhoanId: null,
    soChuaDoc: 0,
    hoiThoaiMoi: [],
    tongHoiThoai: 0,
    dangTaiXemNhanh: false,
    loiXemNhanh: '',
    phienDongBo: 0,
    trangThai: 'disconnected',
    maPhien: 0,
  }),
  actions: {
    ketNoi(taiKhoanId) {
      if (this.taiKhoanId === taiKhoanId) return
      this.dongKetNoi()
      this.taiKhoanId = taiKhoanId
      const phien = this.maPhien
      const dongBo = () => {
        if (this.maPhien !== phien) return
        this.phienDongBo++
        void this.taiSoChuaDoc()
      }
      huySocket = ketNoiChat(taiKhoanId, dongBo, (trangThai) => {
        if (this.maPhien === phien) this.trangThai = trangThai
      })
      const hienLai = () => {
        if (!document.hidden) dongBo()
      }
      window.addEventListener('online', dongBo)
      document.addEventListener('visibilitychange', hienLai)
      huySuKien = () => {
        window.removeEventListener('online', dongBo)
        document.removeEventListener('visibilitychange', hienLai)
      }
      // HTTP tải bù cả khi server realtime tạm ngừng hoặc tab từng mất mạng.
      boHenGio = setInterval(hienLai, 45000)
      dongBo()
    },
    async taiSoChuaDoc() {
      if (!this.taiKhoanId) return
      boHuy?.abort()
      boHuy = new AbortController()
      const signal = boHuy.signal
      const phien = this.maPhien
      this.dangTaiXemNhanh = true
      this.loiXemNhanh = ''
      try {
        const response = await chatService.taiHoiThoai({}, signal)
        if (!signal.aborted && phien === this.maPhien) {
          this.soChuaDoc = response.meta.so_chua_doc
          this.hoiThoaiMoi = response.data.slice(0, 6)
          this.tongHoiThoai = response.meta.total ?? response.data.length
        }
      } catch (loi) {
        if (signal.aborted || phien !== this.maPhien) return
        this.hoiThoaiMoi = []
        this.loiXemNhanh = 'Chưa tải được tin nhắn. Vui lòng thử lại.'
        if ([401, 403, 419].includes(loi.response?.status)) {
          this.dongKetNoi()
          useXacThucStore().taiKhoan = null
        }
      } finally {
        if (!signal.aborted && phien === this.maPhien) this.dangTaiXemNhanh = false
      }
    },
    dongKetNoi() {
      this.maPhien++
      boHuy?.abort()
      huySocket?.()
      huySuKien?.()
      clearInterval(boHenGio)
      huySocket = huySuKien = boHuy = boHenGio = undefined
      this.taiKhoanId = null
      this.soChuaDoc = 0
      this.hoiThoaiMoi = []
      this.tongHoiThoai = 0
      this.dangTaiXemNhanh = false
      this.loiXemNhanh = ''
      this.phienDongBo = 0
      this.trangThai = 'disconnected'
    },
  },
})
