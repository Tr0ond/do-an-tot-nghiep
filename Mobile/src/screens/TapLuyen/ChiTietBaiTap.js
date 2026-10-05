import { View } from 'react-native'
import { useGiaoDien } from '../../theme'
import { ManHinh, Chu, The } from '../../components/GiaoDien'
import { TrangThaiTai } from '../../components/HuanLuyen'
import { AnhBaiTap } from '../../components/TapLuyen'
import { useDuLieu } from '../../hooks/useDuLieu'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { tapLuyenService as api } from '../../services/tapLuyenService'

export function HuongDanBai({ bai, coAnh = true }) {
  const { mau } = useGiaoDien()
  const cacBuoc = bai.cac_buoc?.length
    ? bai.cac_buoc
    : Array.isArray(bai.huong_dan)
      ? bai.huong_dan
      : bai.huong_dan
        ? [bai.huong_dan]
        : []
  return (
    <View style={{ gap: 20 }}>
      {coAnh && <AnhBaiTap bai={bai} huongDan />}
      <View>
        <Chu size={16} dam="dam" style={{ marginBottom: 12 }}>
          Hướng dẫn thực hiện
          {bai.ngon_ngu_huong_dan === 'en' ? ' · Tiếng Anh' : ''}
        </Chu>
        <View style={{ gap: 12 }}>
          {cacBuoc.map((buoc, i) => (
            <View key={i} style={{ flexDirection: 'row', gap: 12 }}>
              <View
                style={{
                  width: 28,
                  height: 28,
                  borderRadius: 14,
                  backgroundColor: mau.chinh,
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                <Chu size={13} dam="dam" color={mau.trenChinh}>
                  {i + 1}
                </Chu>
              </View>
              <Chu
                size={14.5}
                style={{ flex: 1, paddingTop: 2, lineHeight: 23.5625 }}
              >
                {buoc}
              </Chu>
            </View>
          ))}
          {!cacBuoc.length && <Chu size={14.5}>Chưa có mô tả.</Chu>}
        </View>
      </View>
      <View
        style={{
          backgroundColor: mau.chinhNhat,
          borderRadius: 16,
          padding: 16,
        }}
      >
        <Chu size={13} style={{ lineHeight: 21.125 }}>
          <Chu size={13} dam="dam" color={mau.chinh}>
            Lưu ý an toàn.{' '}
          </Chu>
          Khởi động kỹ, chọn mức tạ phù hợp và dừng ngay nếu thấy đau bất
          thường.
        </Chu>
      </View>
    </View>
  )
}
export default function ChiTietBaiTap({ navigation, route }) {
  const { goiDichVu } = useXemTruoc()
  const tai = useDuLieu(
    (s) => goiDichVu((t) => api.taiBai(t, route.params.id, s)),
    route.params.id,
  )
  const { mau } = useGiaoDien()
  const b = tai.duLieu?.data
  return (
    <ManHinh
      bas
      tieuDe="Chi tiết bài tập"
      contentStyle={{ gap: 20 }}
      onBack={() => navigation.goBack()}
    >
      <TrangThaiTai {...tai} />
      {b && (
        <>
          <AnhBaiTap bai={b} huongDan />
          <View>
            <Chu
              size={24}
              dam="ratDam"
              style={{ lineHeight: 30, letterSpacing: -0.6 }}
            >
              {b.ten_tieng_viet || b.ten_bai_tap}
            </Chu>
            <View style={{ flexDirection: 'row', gap: 8, marginTop: 12 }}>
              {[
                ['Nhóm cơ', b.nhom_co?.ten_nhom_co],
                ['Dụng cụ', b.dung_cu],
              ].map(([ten, giaTri]) => (
                <View
                  key={ten}
                  style={{
                    flex: 1,
                    borderRadius: 12,
                    borderWidth: 1,
                    borderColor: mau.vien,
                    backgroundColor: mau.the,
                    padding: 12,
                  }}
                >
                  <Chu size={11} color={mau.chuPhu}>
                    {ten}
                  </Chu>
                  <Chu
                    size={13.5}
                    dam="dam"
                    style={{ marginTop: 2, lineHeight: 16.875 }}
                  >
                    {giaTri}
                  </Chu>
                </View>
              ))}
            </View>
          </View>
          <HuongDanBai bai={b} coAnh={false} />
          <Chu size={12}>
            Nguồn dữ liệu: {b.nguon_du_lieu || 'Catalog hệ thống'}
          </Chu>
        </>
      )}
    </ManHinh>
  )
}
