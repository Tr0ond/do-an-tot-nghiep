import { useEffect, useState } from 'react'
import { Platform, View } from 'react-native'
import { ManHinh, Chu, The, BieuTuong } from '../../components/GiaoDien'
import { useGiaoDien } from '../../theme'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { khoPhien } from '../../services/khoPhien'

export default function PhienDangNhap({ navigation }) {
  const { mau } = useGiaoDien()
  const { dangXemTruoc } = useXemTruoc()
  const [hetHan, datHetHan] = useState(null)
  useEffect(() => {
    let conMo = true
    if (!dangXemTruoc)
      khoPhien
        .doc()
        .then((chuoi) => {
          if (conMo && chuoi) datHetHan(JSON.parse(chuoi).expires_at)
        })
        .catch(() => {})
    return () => {
      conMo = false
    }
  }, [dangXemTruoc])
  return (
    <ManHinh
      tieuDe="Phiên đăng nhập"
      onBack={() => navigation.goBack()}
      contentStyle={{ gap: 0 }}
    >
      <Chu size={13} color={mau.chuPhu} style={{ lineHeight: 21.125 }}>
        Mỗi phiên có hiệu lực 30 ngày và có thể dùng song song trên nhiều thiết
        bị. Khi hết hạn, bạn cần đăng nhập lại.
      </Chu>
      <Chu
        size={12}
        dam="dam"
        color={mau.chuPhu}
        style={{ letterSpacing: 1.2, marginTop: 20, marginBottom: 8 }}
      >
        THIẾT BỊ NÀY
      </Chu>
      <The style={{ flexDirection: 'row', alignItems: 'center', gap: 12 }}>
        <View
          style={{
            width: 40,
            height: 40,
            borderRadius: 12,
            backgroundColor: mau.chinhNhat,
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <BieuTuong
            ten={Platform.OS === 'web' ? 'Monitor' : 'Smartphone'}
            size={20}
          />
        </View>
        <View style={{ flex: 1 }}>
          <Chu size={15} dam="damVua">
            {Platform.OS === 'web' ? 'Trình duyệt' : 'Android'} · Tr0ond Fitness
          </Chu>
          <Chu size={12.5} color={mau.chuPhu}>
            {dangXemTruoc
              ? 'Bản xem trước · chưa đăng nhập'
              : hetHan
                ? `Hết hạn ${new Date(hetHan).toLocaleString('vi-VN')}`
                : 'Đang hoạt động'}
          </Chu>
        </View>
        {!dangXemTruoc && (
          <View
            style={{
              backgroundColor: mau.nangLuong,
              paddingHorizontal: 8,
              paddingVertical: 4,
              borderRadius: 999,
            }}
          >
            <Chu size={11} dam="dam" color={mau.trenNangLuong}>
              Hiện tại
            </Chu>
          </View>
        )}
      </The>
      <Chu
        size={13}
        color={mau.chuPhu}
        style={{ marginTop: 20, lineHeight: 21.125 }}
      >
        Đăng xuất tại Hồ sơ chỉ thu hồi phiên trên thiết bị này.
      </Chu>
    </ManHinh>
  )
}
