import { Pressable, View } from 'react-native'
import { useGiaoDien } from '../../theme'
import { useState } from 'react'
import { ManHinh, Chu, Nut, The } from '../../components/GiaoDien'
import { TrangThaiTai, PhanTrang, HopXacNhan } from '../../components/HuanLuyen'
import { useDuLieu } from '../../hooks/useDuLieu'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { tapLuyenService as api } from '../../services/tapLuyenService'

export default function GiaoAnMau({ navigation, route }) {
  const { goiDichVu } = useXemTruoc()
  const { mau } = useGiaoDien()
  const [page, datPage] = useState(1)
  const [id, datId] = useState(null)
  const tai = useDuLieu(
    (s) => goiDichVu((t) => api.taiMau(t, { page }, s)),
    page,
  )
  const chiTiet = useDuLieu(
    (s) =>
      id
        ? goiDichVu((t) => api.taiChiTietMau(t, id, s))
        : Promise.resolve(null),
    id,
  )
  const m = chiTiet.duLieu?.data
  return (
    <ManHinh bas tieuDe="Mẫu giáo án" onBack={() => navigation.goBack()}>
      <TrangThaiTai
        {...tai}
        rong={tai.duLieu?.data.length === 0}
        tieuDeRong="Chưa có giáo án mẫu"
      />
      <Chu size={13} color={mau.chuPhu} style={{ lineHeight: 21.125 }}>
        Dùng mẫu để soạn nhanh giáo án. Sao chép mẫu cho học viên rồi tinh chỉnh
        trước khi gửi.
      </Chu>
      {tai.duLieu?.data.map((k) => (
        <Pressable
          key={k.id}
          accessibilityRole="button"
          onPress={() => datId(k.id)}
        >
          <The style={{ gap: 0 }}>
            <Chu size={12} dam="damVua" color={mau.chinh}>
              {k.muc_tieu}
            </Chu>
            <Chu size={16} dam="dam" style={{ marginTop: 2 }}>
              {k.ten_giao_an}
            </Chu>
            <Chu size={13} color={mau.chuPhu} style={{ marginTop: 8 }}>
              {k.so_ngay_tap} buổi · {k.so_bai_tap} bài tập
            </Chu>
          </The>
        </Pressable>
      ))}
      <PhanTrang
        meta={tai.duLieu?.meta}
        dangTai={tai.dangTai}
        onChange={datPage}
      />
      <HopXacNhan
        visible={!!id}
        tieuDe={m?.ten_giao_an || 'Chi tiết mẫu'}
        onDong={() => datId(null)}
      >
        <TrangThaiTai {...chiTiet} />
        {m && (
          <>
            <Chu>{m.muc_tieu}</Chu>
            {m.bai_tap.map((b, i) => (
              <Chu key={i}>
                Ngày {b.ngay_thu} · {b.ten_bai_tap} · {b.so_hiep} ×{' '}
                {b.so_lan_lap}
                {b.kha_dung ? '' : ' · Đã ngừng dùng'}
              </Chu>
            ))}
            <Nut
              disabled={m.bai_tap.some((b) => !b.kha_dung)}
              onPress={() => {
                datId(null)
                navigation.popTo('SoanGiaoAn', {
                  ...route.params.chonCho,
                  mauChon: m,
                  lanChon: Date.now(),
                })
              }}
            >
              Sao chép và thay nội dung nháp
            </Nut>
          </>
        )}
      </HopXacNhan>
    </ManHinh>
  )
}
