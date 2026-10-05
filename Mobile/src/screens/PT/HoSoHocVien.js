import { useState } from 'react'
import { RefreshControl, View, Pressable } from 'react-native'
import {
  ManHinh,
  Chu,
  The,
  AnhDaiDien,
  Nut,
  BieuTuong,
  Nhan,
} from '../../components/GiaoDien'
import { TrangThaiTai, PhanTrang } from '../../components/HuanLuyen'
import { ChonPhan } from '../../components/FigmaElements'
import { useDuLieu } from '../../hooks/useDuLieu'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { huanLuyenService as api } from '../../services/huanLuyenService'
import { tapLuyenService } from '../../services/tapLuyenService'
import { traoDoiService } from '../../services/traoDoiService'
import { useThaoTac } from '../../hooks/useThaoTac'
import { useGiaoDien } from '../../theme'
import { nhanTrangThai } from '../../utils/tapLuyen'
import { nhanNgay, homNay, doiNgay } from '../../utils/lich'

export default function HoSoHocVien({ navigation, route }) {
  const { goiDichVu } = useXemTruoc()
  const { mau } = useGiaoDien()
  const [muc, datMuc] = useState('plans')
  const [page, datPage] = useState(1)
  const ghi = useThaoTac()
  const { duLieu, dangTai, loi, taiLai } = useDuLieu(
    (signal) =>
      goiDichVu((token) => api.taiHoSoHocVien(token, route.params.id, signal)),
    route.params.id,
  )
  const h = duLieu?.data
  const danhSach = useDuLieu(
    (s) =>
      goiDichVu((t) =>
        muc === 'plans'
          ? tapLuyenService.taiGiaoAn(
              t,
              'HUAN_LUYEN_VIEN',
              route.params.id,
              { page },
              s,
            )
          : muc === 'self'
            ? tapLuyenService.taiLichTap(
                t,
                'HUAN_LUYEN_VIEN',
                route.params.id,
                {
                  tu_ngay: doiNgay(homNay(), -180),
                  den_ngay: doiNgay(homNay(), 180),
                  page,
                },
                s,
              )
            : Promise.resolve(null),
      ),
    `${route.params.id}|${muc}|${page}`,
  )
  function nhanTin() {
    ghi.gui(
      () =>
        goiDichVu(async (t) => {
          let trang = 1
          do {
            const r = await traoDoiService.taiHoiThoai(t, {
              page: trang,
              tu_khoa: h.ho_ten,
            })
            const hoi = r.data.find((x) => x.doi_phuong.id === h.tai_khoan_id)
            if (hoi) return hoi
            if (trang >= r.meta.last_page) break
            trang++
          } while (true)
          throw new Error(
            'Chưa có hội thoại với học viên trong phân công hiện tại.',
          )
        }),
      (r) => navigation.navigate('HoiThoai', { id: r.id }),
    )
  }
  const thoiGian = Array.isArray(h?.thoi_gian_co_the_tap)
    ? h.thoi_gian_co_the_tap.filter((t) => typeof t === 'string').join(', ')
    : h?.thoi_gian_co_the_tap
  return (
    <ManHinh
      bas
      tieuDe="Hồ sơ học viên"
      onBack={() => navigation.goBack()}
      footer={
        h && (
          <Nut
            icon="Plus"
            onPress={() =>
              navigation.navigate('SoanGiaoAn', { khachId: route.params.id })
            }
          >
            Tạo giáo án cho học viên
          </Nut>
        )
      }
      refreshControl={
        <RefreshControl refreshing={dangTai} onRefresh={taiLai} />
      }
    >
      <TrangThaiTai {...{ dangTai, loi, taiLai }} />
      {h && (
        <>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 16 }}>
            <AnhDaiDien ten={h.ho_ten} size={64} />
            <View style={{ flex: 1 }}>
              <Chu size={20} dam="ratDam" style={{ letterSpacing: -0.5 }}>
                {h.ho_ten}
              </Chu>
              <Chu size={13}>{h.muc_tieu || 'Chưa cập nhật mục tiêu'}</Chu>
            </View>
          </View>
          <View style={{ flexDirection: 'row', gap: 8 }}>
            <Nut
              sm
              loai="phu"
              icon="MessageCircle"
              style={{ flex: 1 }}
              disabled={ghi.dangGui}
              onPress={nhanTin}
            >
              Nhắn tin
            </Nut>
            <Nut
              sm
              loai="phu"
              icon="ChartNoAxesCombined"
              style={{ flex: 1 }}
              onPress={() =>
                navigation.navigate('ChiSo', { khachId: route.params.id })
              }
            >
              Chỉ số
            </Nut>
          </View>
          {!!ghi.loiGui && <Chu color={mau.loi}>{ghi.loiGui.message}</Chu>}
          <ChonPhan
            value={muc}
            onChange={(v) => {
              datMuc(v)
              datPage(1)
            }}
            options={[
              ['plans', 'Giáo án'],
              ['self', 'Tự tập'],
              ['cal', 'Lịch'],
            ]}
          />
          {muc !== 'cal' && (
            <>
              <TrangThaiTai
                {...danhSach}
                rong={!!danhSach.duLieu && !danhSach.duLieu.data.length}
                tieuDeRong={
                  muc === 'plans' ? 'Chưa có giáo án' : 'Chưa có buổi tự tập'
                }
              />
              {danhSach.duLieu?.data.map((x) => (
                <Pressable
                  key={x.id}
                  accessibilityRole="button"
                  onPress={() =>
                    navigation.navigate(
                      muc === 'plans' ? 'ChiTietGiaoAn' : 'BuoiTuTap',
                      { id: x.id, khachId: route.params.id },
                    )
                  }
                >
                  <The
                    style={{
                      flexDirection: 'row',
                      alignItems: 'center',
                      gap: 12,
                    }}
                  >
                    <View
                      style={{
                        width: 44,
                        height: 44,
                        borderRadius: 12,
                        backgroundColor: mau.chinhNhat,
                        alignItems: 'center',
                        justifyContent: 'center',
                      }}
                    >
                      <BieuTuong
                        ten={muc === 'plans' ? 'ClipboardList' : 'Dumbbell'}
                        size={19}
                      />
                    </View>
                    <View style={{ flex: 1 }}>
                      <Chu size={14.5} dam="dam">
                        {x.ten_ke_hoach}
                      </Chu>
                      <Chu size={12.5} color={mau.chuPhu}>
                        {muc === 'plans'
                          ? `${x.so_ngay_tap} buổi · ${x.muc_tieu || 'Giáo án'}`
                          : nhanNgay(x.ngay_tap)}
                      </Chu>
                    </View>
                    <Nhan trangThai={x.trang_thai}>
                      {nhanTrangThai(x.trang_thai_hien_thi || x.trang_thai)}
                    </Nhan>
                  </The>
                </Pressable>
              ))}
              <PhanTrang
                meta={danhSach.duLieu?.meta}
                dangTai={danhSach.dangTai}
                onChange={datPage}
              />
              <Nut
                sm
                loai="ghost"
                onPress={() =>
                  navigation.navigate(
                    muc === 'plans' ? 'GiaoAnHocVien' : 'LichTuTap',
                    { khachId: route.params.id },
                  )
                }
              >
                Xem tất cả & bộ lọc
              </Nut>
            </>
          )}
          {muc === 'cal' && (
            <The>
              <Chu size={13} color={mau.chuPhu}>
                Xem lịch dạy để kiểm tra và xử lý các buổi hẹn với học viên.
              </Chu>
              <Nut
                sm
                loai="soft"
                icon="CalendarClock"
                onPress={() =>
                  navigation.navigate('BanXemTruoc', { screen: 'Lich' })
                }
              >
                Mở lịch dạy
              </Nut>
            </The>
          )}
          <The>
            <Chu dam="dam">Thông tin tập luyện</Chu>
            <Chu>Mục tiêu: {h.muc_tieu || 'Chưa cập nhật'}</Chu>
            <Chu>Kinh nghiệm: {h.kinh_nghiem || 'Chưa cập nhật'}</Chu>
            <Chu>Thời gian có thể tập: {thoiGian || 'Chưa cập nhật'}</Chu>
            <Chu>
              Ngày sinh:{' '}
              {h.ngay_sinh
                ? nhanNgay(h.ngay_sinh.slice(0, 10))
                : 'Chưa cập nhật'}
            </Chu>
            <Chu>
              Giới tính:{' '}
              {{ NAM: 'Nam', NU: 'Nữ', KHAC: 'Khác' }[h.gioi_tinh] ||
                h.gioi_tinh ||
                'Chưa cập nhật'}
            </Chu>
          </The>
        </>
      )}
    </ManHinh>
  )
}
