import { useEffect, useRef, useState } from 'react'
import { usePreventRemove } from '@react-navigation/native'

export function useThaoTac() {
  const khoa = useRef(false)
  const hien = useRef(true)
  const [dangGui, datDangGui] = useState(false)
  const [loiGui, datLoiGui] = useState(null)
  useEffect(() => {
    hien.current = true
    return () => {
      hien.current = false
    }
  }, [])
  usePreventRemove(dangGui, () => {})
  async function gui(hanhDong, thanhCong) {
    if (khoa.current) return
    khoa.current = true
    datDangGui(true)
    datLoiGui(null)
    try {
      const ketQua = await hanhDong()
      if (hien.current && ketQua) await thanhCong?.(ketQua)
    } catch (loi) {
      if (hien.current) datLoiGui(loi)
    } finally {
      khoa.current = false
      if (hien.current) datDangGui(false)
    }
  }
  return { dangGui, loiGui, datLoiGui, gui }
}
