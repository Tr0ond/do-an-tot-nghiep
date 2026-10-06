import { useCallback, useEffect, useState } from 'react'
import { Linking, Pressable, View } from 'react-native'
import { Image } from 'expo-image'
import { useFocusEffect } from '@react-navigation/native'
import { Chu, Nut, The } from './GiaoDien'
import { layApiUrl } from '../config/moiTruong'
import { useGiaoDien } from '../theme'
import { Chip } from './FigmaElements'
import { mediaBaiTap, taoUrlMedia } from '../utils/media'
import { useChuyenDong } from '../hooks/useChuyenDong'

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

export function AnhBaiTap({ bai, huongDan = false, size }) {
  const { mau } = useGiaoDien()
  const [phat, datPhat] = useState(false)
  const { giam: giamChuyenDong, choPhep } = useChuyenDong()
  const [loi, datLoi] = useState(false)
  const media = mediaBaiTap(bai)
  useFocusEffect(useCallback(() => () => datPhat(false), []))
  useEffect(() => {
    datLoi(false)
    datPhat(false)
  }, [media.anh_url, media.gif_url])
  const dangPhat = phat && choPhep
  const duongDan = dangPhat ? media.gif_url : media.anh_url || media.gif_url
  let url = null
  try {
    url = taoUrlMedia(duongDan, layApiUrl())
  } catch {}
  // Giáo án mẫu chỉ trả anh_url; media catalog hợp lệ thuộc bộ Gym visual.
  const ghiCong =
    media.ghi_cong_media ||
    (url ? '© Gym visual — https://gymvisual.com/' : null)
  return (
    <View style={{ gap: size ? 2 : 8, ...(size ? { width: size } : {}) }}>
      <View
        style={{
          height: size || (huongDan ? 224 : 110),
          backgroundColor: url && !loi ? '#FFFFFF' : mau.chinhNhat,
          borderRadius: huongDan ? 24 : 12,
          overflow: 'hidden',
          justifyContent: 'center',
        }}
      >
        {url && !loi ? (
          <Image
            key={`${url}-${dangPhat}`}
            source={{ uri: url }}
            accessible
            accessibilityLabel={bai.ten_tieng_viet || bai.ten_bai_tap}
            style={{ width: '100%', height: '100%' }}
            contentFit="contain"
            cachePolicy="disk"
            autoplay={dangPhat}
            onError={() => datLoi(true)}
          />
        ) : (
          <Chu
            size={size ? 10 : 13}
            color={mau.chuPhu}
            style={{ textAlign: 'center', paddingHorizontal: 4 }}
          >
            {loi ? 'Ảnh chưa tải được' : 'Chưa có ảnh'}
          </Chu>
        )}
      </View>
      {ghiCong && (
        <Pressable
          accessibilityRole="link"
          accessibilityLabel={ghiCong}
          onPress={() =>
            Linking.openURL('https://gymvisual.com/').catch(() => {})
          }
        >
          <Chu
            size={size ? 8 : 11}
            color={mau.chuPhu}
            style={size ? { textAlign: 'center' } : undefined}
          >
            {size ? '© Gym visual' : ghiCong}
          </Chu>
        </Pressable>
      )}
      {huongDan && loi && (
        <Nut loai="phu" onPress={() => datLoi(false)}>
          Tải lại ảnh
        </Nut>
      )}
      {huongDan && media.gif_url && (
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
