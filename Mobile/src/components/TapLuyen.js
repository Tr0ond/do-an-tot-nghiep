import { useEffect, useState } from 'react'
import { AccessibilityInfo, Image, View } from 'react-native'
import { useFocusEffect } from '@react-navigation/native'
import { useCallback } from 'react'
import { Chu, Nut, The } from './GiaoDien'
import { layApiUrl } from '../config/moiTruong'
import { useGiaoDien } from '../theme'
import { Chip } from './FigmaElements'
import MinhHoaBaiTap from './MinhHoaBaiTap'

export function LoiGhi({ loi }) {
  const { mau } = useGiaoDien()
  if (!loi) return null
  return (
    <The>
      <Chu color={mau.loi} accessibilityLiveRegion="polite">
        {loi.message || loi}
      </Chu>
      {Object.entries(loi.errors || {}).map(([k, v]) => (
        <Chu key={k} color={mau.loi}>
          {v.join(' ')}
        </Chu>
      ))}
    </The>
  )
}

export function LuaChon({ nhan, cacMuc, giaTri, onChon, disabled }) {
  return (
    <View style={{ gap: 8 }}>
      {!!nhan && (
        <Chu size={13} dam="damVua">
          {nhan}
        </Chu>
      )}
      <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8 }}>
        {cacMuc.map(([id, ten]) => (
          <Chip
            key={String(id)}
            disabled={disabled}
            chon={giaTri === id}
            accessibilityLabel={`${ten}${giaTri === id ? ', đang chọn' : ''}`}
            onPress={() => onChon(id)}
          >
            {ten}
          </Chip>
        ))}
      </View>
    </View>
  )
}

export function AnhBaiTap({ bai, huongDan = false }) {
  const { mau } = useGiaoDien()
  const [phat, datPhat] = useState(false)
  const [giamChuyenDong, datGiam] = useState(true)
  const [loi, datLoi] = useState(false)
  useEffect(() => {
    let hien = true
    AccessibilityInfo.isReduceMotionEnabled().then((v) => {
      if (hien) datGiam(v)
    })
    const s = AccessibilityInfo.addEventListener('reduceMotionChanged', (v) => {
      datGiam(v)
      if (v) datPhat(false)
    })
    return () => {
      hien = false
      s.remove()
    }
  }, [])
  useFocusEffect(useCallback(() => () => datPhat(false), []))
  useEffect(() => {
    datLoi(false)
    datPhat(false)
  }, [bai.anh_url, bai.gif_url])
  const duongDan = phat ? bai.gif_url : bai.anh_url
  let url = null
  try {
    const goc = new URL(layApiUrl()).origin
    const u = duongDan ? new URL(duongDan, goc) : null
    if (u?.origin === goc && u.pathname.startsWith('/media/bai-tap/'))
      url = u.href
  } catch {}
  return (
    <View style={{ gap: 8 }}>
      <View
        style={{
          height: huongDan ? 224 : 110,
          backgroundColor: mau.chinhNhat,
          borderRadius: huongDan ? 24 : 12,
          overflow: 'hidden',
          justifyContent: 'center',
        }}
      >
        {url && !loi ? (
          <Image
            source={{ uri: url }}
            accessibilityLabel={bai.ten_tieng_viet || bai.ten_bai_tap}
            style={{ width: '100%', height: '100%' }}
            resizeMode="contain"
            onError={() => datLoi(true)}
          />
        ) : (
          <MinhHoaBaiTap lon={huongDan} />
        )}
      </View>
      {bai.ghi_cong_media && <Chu size={11}>{bai.ghi_cong_media}</Chu>}
      {huongDan && bai.gif_url && (
        <Nut
          loai="phu"
          disabled={giamChuyenDong}
          onPress={() => {
            datLoi(false)
            datPhat(!phat)
          }}
        >
          {giamChuyenDong
            ? 'Minh họa động đang tắt theo cài đặt thiết bị'
            : phat
              ? 'Dừng minh họa động'
              : 'Xem minh họa động'}
        </Nut>
      )}
    </View>
  )
}
