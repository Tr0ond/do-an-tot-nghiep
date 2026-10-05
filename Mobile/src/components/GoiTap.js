import { View, Pressable } from 'react-native'
import { Chu, The, Nhan, BieuTuong } from './GiaoDien'
import { tien, trangThaiDon } from '../utils/hoanThien'
import { thoiDiem, ngayThangVietNam } from '../utils/lich'
import { useGiaoDien } from '../theme'

export function QuyenLoiGoi({ goi: g }) {
  const { mau } = useGiaoDien()
  const cacDong = [
    `${g.thoi_han_ngay} ngày sử dụng`,
    g.so_buoi_pt > 0
      ? `${g.so_buoi_pt} buổi huấn luyện cùng PT`
      : 'Gói tự tập cùng AI · Không có buổi PT',
    g.co_chatbot
      ? `${g.so_luot_chatbot_moi_ngay} lượt chatbot mỗi ngày`
      : 'Không có quyền chatbot',
  ]
  return (
    <View style={{ gap: 12 }}>
      {cacDong.map((dong) => (
        <View key={dong} style={{ flexDirection: 'row', gap: 12 }}>
          <View
            style={{
              width: 20,
              height: 20,
              borderRadius: 10,
              backgroundColor: mau.chinhNhat,
              alignItems: 'center',
              justifyContent: 'center',
              marginTop: 2,
            }}
          >
            <BieuTuong ten="Check" size={12} strokeWidth={3} />
          </View>
          <Chu size={14.5} style={{ flex: 1 }}>
            {dong}
          </Chu>
        </View>
      ))}
    </View>
  )
}

export function TheDon({ don: d, children, gon = false, onPress }) {
  const { mau } = useGiaoDien()
  if (gon)
    return (
      <Pressable
        accessibilityRole="button"
        accessibilityLabel={`${d.ten_goi}. Mã đơn ${d.ma_don_payos}. ${thoiDiem(d.created_at)}. ${tien(d.gia)}. ${trangThaiDon[d.trang_thai] || d.trang_thai}`}
        onPress={onPress}
      >
        <The style={{ flexDirection: 'row', alignItems: 'center', gap: 12 }}>
          <View
            style={{
              width: 48,
              height: 48,
              borderRadius: 12,
              backgroundColor: mau.chinhNhat,
              alignItems: 'center',
              justifyContent: 'center',
            }}
          >
            <BieuTuong ten="Package" size={20} />
          </View>
          <View style={{ flex: 1 }}>
            <Chu size={15} dam="dam">
              {d.ten_goi}
            </Chu>
            <Chu size={12.5} color={mau.chuPhu}>
              {d.ma_don_payos}
              {d.created_at ? ` · ${ngayThangVietNam(d.created_at)}` : ''}
            </Chu>
          </View>
          <View style={{ alignItems: 'flex-end', gap: 4 }}>
            <Chu size={13.5} dam="dam">
              {tien(d.gia)}
            </Chu>
            <Nhan
              trangThai={
                d.trang_thai === 'DANG_SU_DUNG' ? 'DA_THANH_TOAN' : d.trang_thai
              }
              loai={d.trang_thai === 'CHO_THANH_TOAN' ? 'vang' : 'chinh'}
            >
              {d.trang_thai === 'DANG_SU_DUNG'
                ? 'Thành công'
                : trangThaiDon[d.trang_thai] || d.trang_thai}
            </Nhan>
          </View>
        </The>
      </Pressable>
    )
  return (
    <The>
      <Nhan
        trangThai={d.trang_thai}
        loai={d.trang_thai === 'CHO_THANH_TOAN' ? 'vang' : 'chinh'}
      >
        {trangThaiDon[d.trang_thai] || d.trang_thai}
      </Nhan>
      <Chu size={20} dam="dam">
        {d.ten_goi}
      </Chu>
      <Chu size={23} dam="dam" color={mau.chinh}>
        {tien(d.gia)}
      </Chu>
      <Chu size={12} color={mau.chuPhu}>
        Mã đơn: {d.ma_don_payos}
      </Chu>
      <QuyenLoiGoi goi={d} />
      {!!d.kich_hoat_luc && <Chu>Kích hoạt: {thoiDiem(d.kich_hoat_luc)}</Chu>}
      {!!d.het_han_luc && <Chu>Hết hạn: {thoiDiem(d.het_han_luc)}</Chu>}
      {d.trang_thai === 'DANG_SU_DUNG' && (
        <Chu dam="damVua">Còn {d.so_buoi_con_lai} buổi PT</Chu>
      )}
      {children}
    </The>
  )
}
