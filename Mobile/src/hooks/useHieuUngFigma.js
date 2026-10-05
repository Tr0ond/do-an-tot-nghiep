import { useEffect, useRef } from 'react'
import {
  AccessibilityInfo,
  Animated,
  Easing,
  useWindowDimensions,
} from 'react-native'

export function useHieuUngFigma(hien, loai = 'fade') {
  const muc = useRef(new Animated.Value(0)).current
  const { height } = useWindowDimensions()
  useEffect(() => {
    let song = true
    let chuyen
    // Màn hình phía dưới vẫn phải hiện khi mở bảng nhập liệu trong suốt.
    muc.setValue(hien ? 0 : 1)
    if (hien) {
      AccessibilityInfo.isReduceMotionEnabled().then((giam) => {
        if (!song) return
        if (giam) muc.setValue(1)
        else {
          chuyen = Animated.timing(muc, {
            toValue: 1,
            duration: loai === 'sheet' ? 250 : 200,
            easing:
              loai === 'sheet'
                ? Easing.bezier(0.2, 0.8, 0.2, 1)
                : Easing.out(Easing.ease),
            useNativeDriver: true,
          })
          chuyen.start()
        }
      })
    }
    return () => {
      song = false
      chuyen?.stop()
    }
  }, [hien, loai, muc])
  return loai === 'sheet'
    ? {
        transform: [
          {
            translateY: muc.interpolate({
              inputRange: [0, 1],
              outputRange: [height, 0],
            }),
          },
        ],
      }
    : { opacity: muc }
}
