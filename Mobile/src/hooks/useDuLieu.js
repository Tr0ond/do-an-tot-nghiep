import { useCallback, useRef, useState } from 'react'
import { AppState } from 'react-native'
import { useFocusEffect } from '@react-navigation/native'

export function useDuLieu(tai, khoa = '', giuKhiAn = false) {
  const hamTai = useRef(tai)
  hamTai.current = tai
  const lan = useRef(0)
  const controller = useRef(null)
  const hien = useRef(false)
  const [duLieu, datDuLieu] = useState(null)
  const [dangTai, datDangTai] = useState(true)
  const [loi, datLoi] = useState('')
  const taiLai = useCallback(async () => {
    if (!hien.current) return
    const maLan = ++lan.current
    controller.current?.abort()
    const moi = new AbortController()
    controller.current = moi
    datDangTai(true)
    datLoi('')
    // Không giữ danh sách/quyền cũ sau lỗi hoặc thay bộ lọc/phân công.
    datDuLieu(null)
    try {
      const ketQua = await hamTai.current(moi.signal)
      if (hien.current && maLan === lan.current && !moi.signal.aborted)
        datDuLieu(ketQua)
      return ketQua
    } catch (e) {
      if (hien.current && maLan === lan.current && !moi.signal.aborted)
        datLoi(e.message)
    } finally {
      if (hien.current && maLan === lan.current) datDangTai(false)
    }
  }, [])
  useFocusEffect(
    useCallback(() => {
      hien.current = true
      taiLai()
      const listener = AppState.addEventListener('change', (state) => {
        if (state === 'active') taiLai()
      })
      return () => {
        hien.current = false
        lan.current++
        controller.current?.abort()
        listener.remove()
        // Chỉ giữ ảnh nền của màn hình chỉ số khi bảng nhập phủ lên trên.
        // Request vẫn bị hủy; quay lại luôn tải quyền/dữ liệu mới.
        if (!giuKhiAn) datDuLieu(null)
      }
    }, [khoa, taiLai, giuKhiAn]),
  )
  return { duLieu, dangTai, loi, taiLai }
}
