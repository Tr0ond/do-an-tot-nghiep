import { useEffect, useState } from 'react'
import { AccessibilityInfo, AppState } from 'react-native'
import { useIsFocused } from '@react-navigation/native'

export function useChuyenDong() {
  const hien = useIsFocused()
  const [giam, datGiam] = useState(true)
  const [dangMo, datDangMo] = useState(AppState.currentState === 'active')
  useEffect(() => {
    let conHien = true
    AccessibilityInfo.isReduceMotionEnabled()
      .then((v) => {
        if (conHien) datGiam(v)
      })
      .catch(() => {})
    const giamDong = AccessibilityInfo.addEventListener(
      'reduceMotionChanged',
      datGiam,
    )
    const trangThai = AppState.addEventListener('change', (v) =>
      datDangMo(v === 'active'),
    )
    return () => {
      conHien = false
      giamDong.remove()
      trangThai.remove()
    }
  }, [])
  return { giam, choPhep: hien && dangMo && !giam }
}
