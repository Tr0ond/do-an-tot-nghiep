import { useState } from 'react'
import { RefreshControl, View, Pressable } from 'react-native'
import {
  ManHinh,
  Chu,
  The,
  Nut,
  TruongNhap,
  HangMenu,
} from '../../components/GiaoDien'
import { TieuDeTab, TimKiem } from '../../components/FigmaElements'
import { useGiaoDien } from '../../theme'
import { AnhDaiDien, BieuTuong } from '../../components/GiaoDien'
import { PhanTrang, TrangThaiTai } from '../../components/HuanLuyen'
import { useDuLieu } from '../../hooks/useDuLieu'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { huanLuyenService as api } from '../../services/huanLuyenService'

export default function HocVien({ navigation }) {
  const { goiDichVu } = useXemTruoc()
  const { mau } = useGiaoDien()
  const [nhap, datNhap] = useState('')
  const [tuKhoa, datTuKhoa] = useState('')
  const [page, datTrang] = useState(1)
  const { duLieu, dangTai, loi, taiLai } = useDuLieu(
    (signal) =>
      goiDichVu((token) =>
        api.taiHocVien(token, { page, tu_khoa: tuKhoa }, signal),
      ),
    `${tuKhoa}-${page}`,
  )
  function tim() {
    datTuKhoa(nhap.trim())
    datTrang(1)
    if (tuKhoa === nhap.trim() && page === 1) taiLai()
  }
  return (
    <ManHinh
      contentStyle={{ gap: 12, paddingTop: 0 }}
      refreshControl={
        <RefreshControl refreshing={dangTai} onRefresh={taiLai} />
      }
    >
      <TieuDeTab
        tacVu={
          <Nut
            sm
            loai="soft"
            icon="Library"
            onPress={() => navigation.navigate('GiaoAnMau')}
          >
            Mẫu giáo án
          </Nut>
        }
      >
        Học viên
      </TieuDeTab>
      <TimKiem
        value={nhap}
        onChangeText={datNhap}
        placeholder="Tìm học viên…"
        onSubmitEditing={tim}
      />
      {duLieu?.meta && (
        <Chu size={12.5} color={mau.chuPhu}>
          {duLieu.meta.total} học viên
        </Chu>
      )}
      <TrangThaiTai
        {...{ dangTai, loi, taiLai }}
        rong={duLieu?.data?.length === 0}
        tieuDeRong="Chưa tìm thấy học viên"
        moTaRong={
          tuKhoa
            ? 'Thử tên khác hoặc xóa từ khóa tìm kiếm.'
            : 'Các học viên được phân công cho bạn sẽ xuất hiện tại đây.'
        }
      />
      {duLieu?.data?.map((h) => (
        <Pressable
          key={h.id}
          accessibilityRole="button"
          onPress={() => navigation.navigate('HoSoHocVien', { id: h.id })}
        >
          <The style={{ flexDirection: 'row', gap: 12, alignItems: 'center' }}>
            <AnhDaiDien ten={h.ho_ten} size={46} />
            <View style={{ flex: 1 }}>
              <Chu size={15} dam="dam">
                {h.ho_ten}
              </Chu>
              <Chu size={12.5} color={mau.chuPhu}>
                {h.muc_tieu || 'Xem hồ sơ học viên'}
              </Chu>
            </View>
            <BieuTuong ten="ChevronRight" size={18} color={mau.chuPhu} />
          </The>
        </Pressable>
      ))}
      <PhanTrang meta={duLieu?.meta} dangTai={dangTai} onChange={datTrang} />
    </ManHinh>
  )
}
