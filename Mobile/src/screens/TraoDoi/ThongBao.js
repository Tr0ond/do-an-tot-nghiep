import { useEffect, useState } from 'react'
import { Pressable, View } from 'react-native'
import { useIsFocused } from '@react-navigation/native'
import {
  ManHinh,
  Chu,
  The,
  Nut,
  BieuTuong,
  NutIcon,
} from '../../components/GiaoDien'
import { TrangThaiTai, PhanTrang } from '../../components/HuanLuyen'
import { useDuLieu } from '../../hooks/useDuLieu'
import { useThaoTac } from '../../hooks/useThaoTac'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { useTraoDoi } from '../../contexts/TraoDoiContext'
import { traoDoiService as api } from '../../services/traoDoiService'
import { dichThongBao } from '../../utils/traoDoi'
import { thoiDiem } from '../../utils/lich'
import { useGiaoDien } from '../../theme'

export default function ThongBao({ navigation }) {
  const { goiDichVu, vaiTro, datThongBao } = useXemTruoc()
  const { dongBo, capNhat, hoatDong } = useTraoDoi()
  const { mau } = useGiaoDien()
  const focused = useIsFocused()
  const [page, datPage] = useState(1)
  const tai = useDuLieu(
    (s) => goiDichVu((t) => api.taiThongBao(t, { page }, s)),
    page,
  )
  const ghi = useThaoTac()
  useEffect(() => {
    if (focused && hoatDong) tai.taiLai()
  }, [dongBo])
  function mo(b) {
    if (ghi.dangGui) return
    ghi.gui(
      () => goiDichVu((t) => api.docThongBao(t, b.id)),
      () => {
        capNhat()
        const dich = dichThongBao(b.duong_dan, vaiTro)
        if (dich) {
          if (['CaNhan', 'HocVien'].includes(dich.name))
            navigation.navigate('BanXemTruoc', { screen: dich.name })
          else navigation.navigate(dich.name, dich.params)
        } else {
          tai.taiLai()
          datThongBao({
            tieuDe: 'Đã đọc thông báo',
            noiDung:
              'Nội dung này chưa có màn hình trên app. Bạn có thể xem trên website.',
          })
        }
      },
    )
  }
  return (
    <ManHinh
      bas
      tieuDe="Thông báo"
      contentStyle={{ gap: 12 }}
      tacVu={
        <NutIcon
          icon="CheckCheck"
          size={18}
          nhan={`Đánh dấu tất cả đã đọc · ${tai.duLieu?.meta.so_chua_doc ?? 0} chưa đọc`}
          disabled={ghi.dangGui || !tai.duLieu?.meta.so_chua_doc}
          onPress={() =>
            ghi.gui(
              () => goiDichVu((t) => api.docTatCa(t)),
              () => {
                capNhat()
                tai.taiLai()
              },
            )
          }
          style={{
            width: 40,
            height: 40,
            borderWidth: 0,
            backgroundColor: 'transparent',
          }}
        />
      }
      onBack={() => navigation.goBack()}
    >
      {!!ghi.loiGui && (
        <Chu color={mau.loi} accessibilityLiveRegion="polite">
          {ghi.loiGui.message}
        </Chu>
      )}
      <TrangThaiTai
        {...tai}
        rong={!tai.duLieu?.data.length}
        tieuDeRong="Chưa có thông báo"
        moTaRong="Lịch hẹn, giáo án và nhận xét mới sẽ xuất hiện tại đây."
      />
      {tai.duLieu?.data.map((b) => (
        <Pressable
          key={b.id}
          accessibilityRole="button"
          accessibilityLabel={`${b.tieu_de}. ${b.noi_dung}. ${thoiDiem(b.tao_luc)}`}
          disabled={ghi.dangGui}
          onPress={() => mo(b)}
        >
          <The
            style={{
              flexDirection: 'row',
              alignItems: 'flex-start',
              gap: 12,
            }}
          >
            <View
              style={{
                width: 40,
                height: 40,
                borderRadius: 20,
                backgroundColor: mau.chinhNhat,
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <BieuTuong
                ten={
                  {
                    ChiTietGiaoAn: 'ClipboardList',
                    ChiTietLich: 'CalendarClock',
                    BuoiTuTap: 'Dumbbell',
                    ChiTietDon: 'Package',
                  }[dichThongBao(b.duong_dan, vaiTro)?.name] || 'Bell'
                }
                size={18}
              />
            </View>
            <View style={{ flex: 1 }}>
              <View
                style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}
              >
                <Chu size={14.5} dam="dam" style={{ flex: 1 }}>
                  {b.tieu_de}
                </Chu>
                {!b.da_doc_luc && (
                  <View
                    accessibilityLabel="Chưa đọc"
                    style={{
                      width: 6,
                      height: 6,
                      borderRadius: 3,
                      backgroundColor: mau.nangLuong,
                    }}
                  />
                )}
              </View>
              <Chu size={13} color={mau.chuPhu}>
                {b.noi_dung}
              </Chu>
            </View>
          </The>
        </Pressable>
      ))}
      <PhanTrang
        meta={tai.duLieu?.meta}
        dangTai={tai.dangTai}
        onChange={datPage}
      />
    </ManHinh>
  )
}
