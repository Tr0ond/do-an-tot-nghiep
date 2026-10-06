import { useEffect, useState } from 'react'
import { Pressable, View } from 'react-native'
import { useIsFocused } from '@react-navigation/native'
import {
  ManHinh,
  Chu,
  The,
  AnhDaiDien,
  TruongNhap,
  Nut,
  NutIcon,
} from '../../components/GiaoDien'
import { TieuDeTab, TimKiem } from '../../components/FigmaElements'
import { BieuTuong } from '../../components/GiaoDien'
import { TrangThaiTai, PhanTrang, HopXacNhan } from '../../components/HuanLuyen'
import { useDuLieu } from '../../hooks/useDuLieu'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { useTraoDoi } from '../../contexts/TraoDoiContext'
import { traoDoiService as api } from '../../services/traoDoiService'
import { useGiaoDien } from '../../theme'
import MascotTroLy from '../../components/MascotTroLy'
import { thoiDiem, gioVietNam } from '../../utils/lich'

export default function DanhSachHoiThoai({ navigation }) {
  const { goiDichVu, vaiTro } = useXemTruoc()
  const { dongBo, soThongBao, ketNoi, hoatDong } = useTraoDoi()
  const { mau } = useGiaoDien()
  const focused = useIsFocused()
  const [tim, datTim] = useState('')
  const [timMo, datTimMo] = useState(false)
  const [tuKhoa, datTuKhoa] = useState('')
  const [page, datPage] = useState(1)
  const tai = useDuLieu(
    (s) => goiDichVu((t) => api.taiHoiThoai(t, { page, tu_khoa: tuKhoa }, s)),
    `${page}-${tuKhoa}`,
  )
  useEffect(() => {
    if (focused && hoatDong) tai.taiLai()
  }, [dongBo])
  const ds = tai.duLieu?.data || []
  return (
    <ManHinh contentStyle={{ paddingTop: 0, gap: 0 }}>
      <TieuDeTab
        tacVu={
          <View style={{ flexDirection: 'row', gap: 8 }}>
            <NutIcon
              icon="Search"
              size={18}
              nhan="Tìm hội thoại"
              onPress={() => datTimMo(true)}
              style={{
                width: 40,
                height: 40,
                borderWidth: 0,
                backgroundColor: 'transparent',
              }}
            />
            <NutIcon
              icon="Bell"
              nhan={`Thông báo, ${soThongBao} chưa đọc`}
              badge={soThongBao > 0}
              onPress={() => navigation.navigate('ThongBao')}
              style={{ width: 44, height: 44 }}
            />
          </View>
        }
      >
        Tin nhắn
      </TieuDeTab>
      <TrangThaiTai
        {...tai}
        rong={!ds.length}
        tieuDeRong="Chưa có hội thoại"
        moTaRong="Hội thoại xuất hiện khi khách hàng được phân công huấn luyện viên."
      />
      {!!ds.length && (
        <The style={{ gap: 0, padding: 0, overflow: 'hidden' }}>
          {ds.map((h, i) => (
            <Pressable
              key={h.id}
              accessibilityRole="button"
              onPress={() => navigation.navigate('HoiThoai', { id: h.id })}
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                gap: 12,
                padding: 16,
                borderTopWidth: i ? 1 : 0,
                borderColor: mau.vien,
              }}
            >
              <AnhDaiDien ten={h.doi_phuong.ho_ten} size={46} />
              <View style={{ flex: 1, minWidth: 0 }}>
                <Chu size={15} dam="dam" numberOfLines={1}>
                  {h.doi_phuong.ho_ten}
                </Chu>
                <Chu
                  size={13}
                  dam={h.so_chua_doc ? 'damVua' : 'thuong'}
                  color={h.so_chua_doc ? mau.chu : mau.chuPhu}
                  numberOfLines={1}
                >
                  {h.tin_cuoi || 'Chưa có tin nhắn'}
                </Chu>
                {h.da_ket_thuc && (
                  <Chu size={11.5} color={mau.chuPhu}>
                    Đã kết thúc phân công · Chỉ đọc
                  </Chu>
                )}
              </View>
              <View style={{ alignItems: 'flex-end', gap: 6 }}>
                {h.tin_cuoi_luc && (
                  <Chu
                    size={11.5}
                    color={mau.chuPhu}
                    accessibilityLabel={thoiDiem(h.tin_cuoi_luc)}
                  >
                    {gioVietNam(h.tin_cuoi_luc)}
                  </Chu>
                )}
                {h.so_chua_doc > 0 && (
                  <View
                    accessibilityLabel={`${h.so_chua_doc} tin chưa đọc`}
                    style={{
                      width: 10,
                      height: 10,
                      borderRadius: 5,
                      backgroundColor: mau.nangLuong,
                    }}
                  />
                )}
              </View>
            </Pressable>
          ))}
        </The>
      )}
      {vaiTro === 'KHACH_HANG' && (
        <Pressable
          accessibilityRole="button"
          onPress={() => navigation.navigate('TroLy')}
          style={{
            marginTop: 16,
            padding: 16,
            borderRadius: 16,
            backgroundColor: mau.chinhNhat,
            flexDirection: 'row',
            gap: 12,
            alignItems: 'center',
          }}
        >
          <MascotTroLy />
          <Chu size={13.5} style={{ flex: 1 }}>
            <Chu size={13.5} dam="dam">
              Trợ lý AI
            </Chu>{' '}
            · hỏi nhanh về bài tập và giáo án
          </Chu>
        </Pressable>
      )}
      <PhanTrang
        meta={tai.duLieu?.meta}
        dangTai={tai.dangTai}
        onChange={datPage}
      />
      <HopXacNhan
        visible={timMo}
        tieuDe="Tìm hội thoại"
        onDong={() => datTimMo(false)}
      >
        <View style={{ marginBottom: 12 }}>
          <TimKiem
            value={tim}
            onChangeText={datTim}
            placeholder={
              vaiTro === 'KHACH_HANG' ? 'Tìm huấn luyện viên…' : 'Tìm học viên…'
            }
            onSubmitEditing={() => {
              datPage(1)
              datTuKhoa(tim.trim())
              if (tuKhoa === tim.trim()) tai.taiLai()
              datTimMo(false)
            }}
          />
        </View>
        <Chu size={11.5} color={mau.chuPhu} style={{ marginTop: 12 }}>
          {ketNoi === 'da-noi'
            ? 'Đang kết nối trực tiếp'
            : 'Tự cập nhật mỗi 45 giây khi mở app'}
        </Chu>
        <Nut
          sm
          loai="soft"
          onPress={() => {
            datTim('')
            datTuKhoa('')
            datPage(1)
            datTimMo(false)
          }}
        >
          Xóa bộ lọc
        </Nut>
      </HopXacNhan>
    </ManHinh>
  )
}
