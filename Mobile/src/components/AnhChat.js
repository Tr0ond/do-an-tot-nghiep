import { useEffect, useState } from 'react'
import { ActivityIndicator, Modal, Pressable, View } from 'react-native'
import { Image } from 'expo-image'
import { useXemTruoc } from '../contexts/XemTruocContext'
import { traoDoiService as api } from '../services/traoDoiService'
import { anhDataUri } from '../utils/traoDoi'
import { Chu, Nut } from './GiaoDien'
import { useGiaoDien } from '../theme'

export default function AnhChat({ hoiId, tinId, anh, hien, onMatQuyen }) {
  const { goiDichVu } = useXemTruoc()
  const { mau } = useGiaoDien()
  const [uri, datUri] = useState(null)
  const [loi, datLoi] = useState('')
  const [thu, datThu] = useState(0)
  const [mo, datMo] = useState(false)
  useEffect(() => {
    let song = true
    const c = new AbortController()
    datUri(null)
    datMo(false)
    datLoi('')
    if (hien)
      goiDichVu((t) => api.taiAnh(t, hoiId, tinId, anh.vi_tri, c.signal))
        .then((d) => {
          if (song && d && !c.signal.aborted) datUri(anhDataUri(d))
        })
        .catch((e) => {
          if (song && !c.signal.aborted) {
            datLoi(e.message)
            if ([403, 404].includes(e.status)) onMatQuyen()
          }
        })
    return () => {
      song = false
      c.abort()
    }
  }, [hien, hoiId, tinId, anh.vi_tri, goiDichVu, thu])
  return (
    <>
      <Pressable
        accessibilityRole="button"
        accessibilityLabel={`Xem ảnh ${anh.ten}`}
        onPress={() => (uri ? datMo(true) : datThu((n) => n + 1))}
        style={{
          width: 200,
          maxWidth: '100%',
          minHeight: 120,
          borderRadius: 12,
          overflow: 'hidden',
          backgroundColor: mau.nenPhu,
          alignItems: 'center',
          justifyContent: 'center',
        }}
      >
        {uri && hien ? (
          <Image
            source={{ uri }}
            cachePolicy="none"
            recyclingKey={`${hoiId}-${tinId}-${anh.vi_tri}`}
            contentFit="contain"
            style={{ width: '100%', height: 180 }}
          />
        ) : loi ? (
          <Chu size={12} style={{ padding: 12 }}>
            Chưa tải được ảnh. Chạm để thử lại.
          </Chu>
        ) : (
          <ActivityIndicator color={mau.chinh} />
        )}
      </Pressable>
      <Modal visible={mo && hien && !!uri} onRequestClose={() => datMo(false)}>
        <View
          style={{
            flex: 1,
            backgroundColor: mau.nen,
            padding: 24,
            gap: 16,
            justifyContent: 'center',
          }}
        >
          <Chu>{anh.ten}</Chu>
          <Image
            source={{ uri }}
            cachePolicy="none"
            contentFit="contain"
            style={{ flex: 1 }}
          />
          <Nut onPress={() => datMo(false)}>Đóng ảnh</Nut>
        </View>
      </Modal>
    </>
  )
}
