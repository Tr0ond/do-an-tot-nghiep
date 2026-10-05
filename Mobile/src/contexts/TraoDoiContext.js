import { createContext, useContext, useEffect, useRef, useState } from 'react'
import { AppState } from 'react-native'
import NetInfo from '@react-native-community/netinfo'
import { useXemTruoc } from './XemTruocContext'
import { traoDoiService as api } from '../services/traoDoiService'
import { taoKenhChat } from '../services/kenhChat'

const NguCanh = createContext(null)

export function TraoDoiProvider({ children }) {
  const { taiKhoan, dangXemTruoc } = useXemTruoc()
  return (
    <PhienTraoDoi key={dangXemTruoc ? 'mau' : taiKhoan?.id || 'khong-phien'}>
      {children}
    </PhienTraoDoi>
  )
}

function PhienTraoDoi({ children }) {
  const { taiKhoan, dangXemTruoc, goiDichVu } = useXemTruoc()
  const [soTin, datSoTin] = useState(0)
  const [soThongBao, datSoThongBao] = useState(0)
  const [dongBo, datDongBo] = useState(0)
  const [ketNoi, datKetNoi] = useState('cho')
  const [hoatDong, datHoatDong] = useState(AppState.currentState === 'active')
  const capNhat = useRef(() => {})
  useEffect(() => {
    if (!taiKhoan || dangXemTruoc) return
    let hien = true
    let active = AppState.currentState === 'active'
    let online = true
    let dangTai = false
    let cho = false
    let controller = null
    async function tai() {
      if (!hien || !active || !online) return
      if (dangTai) {
        cho = true
        return
      }
      dangTai = true
      controller = new AbortController()
      const s = controller.signal
      // Mỗi lần đồng bộ đều đọc lại quyền qua HTTP, kể cả khi không có tin mới.
      datDongBo((n) => n + 1)
      const ds = await Promise.allSettled([
        goiDichVu((t) => api.taiHoiThoai(t, {}, s)),
        goiDichVu((t) => api.taiThongBao(t, {}, s)),
      ])
      if (hien && active && !s.aborted) {
        if (ds[0].status === 'fulfilled' && ds[0].value)
          datSoTin(ds[0].value.meta.so_chua_doc)
        if (ds[1].status === 'fulfilled' && ds[1].value)
          datSoThongBao(ds[1].value.meta.so_chua_doc)
      }
      dangTai = false
      if (cho) {
        cho = false
        tai()
      }
    }
    const url = process.env.EXPO_PUBLIC_REVERB_URL?.trim()
    const hopLe =
      /^wss?:\/\/[^\s/?#]+\/?$/.test(url || '') &&
      (__DEV__ || url.startsWith('wss://'))
    const kenh = taoKenhChat({
      url: hopLe ? url : null,
      appKey: process.env.EXPO_PUBLIC_REVERB_APP_KEY,
      taiKhoanId: taiKhoan.id,
      taoSocket: (url) =>
        new WebSocket(url, [], {
          headers: {
            origin: process.env.EXPO_PUBLIC_REVERB_ORIGIN || 'http://localhost',
          },
        }),
      xacThuc: (id, ten, s) => goiDichVu((t) => api.xacThucKenh(t, id, ten, s)),
      dongBo: tai,
      trangThai: (d) => {
        if (hien) datKetNoi(d)
      },
    })
    const doi = () => {
      if (active && online) {
        kenh.ketNoi()
        tai()
      } else {
        controller?.abort()
        kenh.dong()
      }
    }
    capNhat.current = tai
    doi()
    const app = AppState.addEventListener('change', (d) => {
      active = d === 'active'
      datHoatDong(active)
      doi()
    })
    const net = NetInfo.addEventListener((d) => {
      const moi = d.isConnected !== false && d.isInternetReachable !== false
      if (moi !== online) {
        online = moi
        doi()
      }
    })
    const timer = setInterval(tai, 45000)
    return () => {
      hien = false
      capNhat.current = () => {}
      controller?.abort()
      clearInterval(timer)
      app.remove()
      net()
      kenh.dong()
    }
  }, [taiKhoan?.id, dangXemTruoc, goiDichVu])
  return (
    <NguCanh.Provider
      value={{
        soTin,
        soThongBao,
        dongBo,
        ketNoi,
        hoatDong,
        capNhat: () => capNhat.current(),
      }}
    >
      {children}
    </NguCanh.Provider>
  )
}

export function useTraoDoi() {
  return useContext(NguCanh)
}
