import { useRef, useState } from 'react'
import { RefreshControl, View, Pressable } from 'react-native'
import { randomUUID } from 'expo-crypto'
import {
  Chu,
  ManHinh,
  The,
  Nhan,
  Nut,
  BieuTuong,
} from '../../components/GiaoDien'
import { QuyenLoiGoi } from '../../components/GoiTap'
import { TienDo } from '../../components/FigmaElements'
import { TrangThaiTai, PhanTrang, HopXacNhan } from '../../components/HuanLuyen'
import { useDuLieu } from '../../hooks/useDuLieu'
import { useThaoTac } from '../../hooks/useThaoTac'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { useGiaoDien } from '../../theme'
import { hoanThienService as api } from '../../services/hoanThienService'
import { taoYeuCauGhi } from '../../utils/tapLuyen'
import { tien } from '../../utils/hoanThien'
import { ngayThangVietNam } from '../../utils/lich'

export default function GoiTap({ navigation, route }) {
  const [page, datPage] = useState(1)
  const [chon, datChon] = useState(null)
  const cho = useRef(taoYeuCauGhi(randomUUID))
  const { vaiTro, goiDichVu, dangXemTruoc } = useXemTruoc()
  const { mau } = useGiaoDien()
  const { gui, dangGui, loiGui, datLoiGui } = useThaoTac()
  const id = route.params?.id
  const { duLieu, dangTai, loi, taiLai } = useDuLieu(
    (s) => (id ? api.taiChiTietGoi(id, s) : api.taiGoi(page, s)),
    `${id || ''}-${page}`,
  )
  const ds = duLieu ? (id ? [duLieu.data] : duLieu.data) : []
  const tongQuan = useDuLieu(
    (s) =>
      !id && vaiTro === 'KHACH_HANG' && !dangXemTruoc
        ? goiDichVu(async (t) => {
            const [goi, don] = await Promise.all([
              api.goiCuaToi(t, s),
              api.taiDon(t, 1, s, 'CHO_THANH_TOAN'),
            ])
            return { ...goi.data, cho: don.data[0] || null }
          })
        : Promise.resolve(null),
    `${id || ''}|${vaiTro}|${dangXemTruoc}`,
  )
  const goi = tongQuan.duLieu?.goi
  const donCho = tongQuan.duLieu?.cho
  function taoDon() {
    gui(
      () =>
        goiDichVu((t) =>
          api.taoDon(t, cho.current.lay({ goi_tap_id: chon.id })),
        ),
      (r) => {
        cho.current.xong()
        datChon(null)
        navigation.navigate('ChiTietDon', { id: r.data.id })
      },
    )
  }
  return (
    <ManHinh
      bas
      tieuDe={id && ds[0] ? ds[0].ten_goi : 'Gói tập'}
      tacVu={
        vaiTro === 'KHACH_HANG' ? (
          <Nut
            sm
            loai="soft"
            icon="Receipt"
            onPress={() => navigation.navigate('DonHang')}
          >
            Đơn hàng
          </Nut>
        ) : undefined
      }
      footer={
        id &&
        ds[0] && (
          <View style={{ gap: 8 }}>
            <Nut
              disabled={dangGui}
              onPress={() =>
                vaiTro === 'KHACH_HANG'
                  ? (datLoiGui(null), datChon(ds[0]))
                  : navigation.navigate('DangNhap')
              }
            >
              {vaiTro === 'KHACH_HANG'
                ? `Tạo đơn · ${tien(ds[0].gia)}`
                : 'Đăng nhập để mua gói'}
            </Nut>
            <Chu size={12} color={mau.chuPhu} style={{ textAlign: 'center' }}>
              Thanh toán an toàn qua payOS, thời hạn 15 phút.
            </Chu>
          </View>
        )
      }
      onBack={() => navigation.goBack()}
      refreshControl={
        <RefreshControl
          refreshing={dangTai || tongQuan.dangTai}
          onRefresh={() => {
            taiLai()
            tongQuan.taiLai()
          }}
        />
      }
    >
      {!id && goi && (
        <Pressable
          accessibilityRole="button"
          accessibilityLabel={`Gói đang hoạt động ${goi.ten_goi}. Xem quyền lợi và thời hạn`}
          onPress={() => navigation.navigate('ChiTietDon', { id: goi.id })}
        >
          <View
            style={{
              backgroundColor: mau.chinh,
              padding: 20,
              borderRadius: 24,
            }}
          >
            <View
              style={{
                flexDirection: 'row',
                justifyContent: 'space-between',
                alignItems: 'center',
              }}
            >
              <Chu
                size={12}
                dam="damVua"
                color={mau.trenChinh}
                style={{
                  opacity: 0.8,
                  textTransform: 'uppercase',
                  letterSpacing: 0.6,
                }}
              >
                Gói đang hoạt động
              </Chu>
              <View
                style={{
                  backgroundColor: mau.nangLuong,
                  borderRadius: 999,
                  paddingHorizontal: 10,
                  paddingVertical: 4,
                }}
              >
                <Chu size={11} dam="dam" color={mau.trenNangLuong}>
                  Đang dùng
                </Chu>
              </View>
            </View>
            <Chu
              size={22}
              dam="ratDam"
              color={mau.trenChinh}
              style={{ marginTop: 8, letterSpacing: -0.55 }}
            >
              {goi.ten_goi}
            </Chu>
            <Chu
              size={34}
              dam="ratDam"
              color={mau.trenChinh}
              style={{ marginTop: 12, lineHeight: 34 }}
            >
              {goi.so_buoi_con_lai}
              <Chu
                size={15}
                dam="damVua"
                color={mau.trenChinh}
                style={{ opacity: 0.85 }}
              >
                {' '}
                / {goi.so_buoi_pt} buổi còn lại
              </Chu>
            </Chu>
            <View style={{ marginTop: 12 }}>
              <TienDo
                value={goi.so_buoi_pt - goi.so_buoi_con_lai}
                max={goi.so_buoi_pt}
                lime
              />
            </View>
            <Chu
              size={12.5}
              color={mau.trenChinh}
              style={{ marginTop: 12, opacity: 0.85 }}
            >
              Hết hạn ngày {ngayThangVietNam(goi.het_han_luc)}
            </Chu>
          </View>
        </Pressable>
      )}
      {!id && !goi && !tongQuan.dangTai && !tongQuan.loi && (
        <View
          style={{
            flexDirection: 'row',
            gap: 12,
            padding: 16,
            borderRadius: 16,
            backgroundColor: mau.chinhNhat,
          }}
        >
          <BieuTuong ten="Info" size={18} />
          <Chu size={13.5} style={{ flex: 1, lineHeight: 21.9375 }}>
            Chọn một gói bên dưới để tập luyện cùng PT hoặc trợ lý AI.
          </Chu>
        </View>
      )}
      {!id && !!tongQuan.loi && <TrangThaiTai {...tongQuan} />}
      {!id && donCho && (
        <Pressable
          accessibilityRole="button"
          onPress={() => navigation.navigate('ChiTietDon', { id: donCho.id })}
        >
          <The
            style={{
              flexDirection: 'row',
              gap: 12,
              alignItems: 'center',
              borderColor: mau.vang,
            }}
          >
            <BieuTuong ten="Clock" size={24} color={mau.vang} />
            <View style={{ flex: 1 }}>
              <Chu size={14} dam="dam">
                Bạn có đơn chờ thanh toán
              </Chu>
              <Chu size={12.5} color={mau.chuPhu}>
                {donCho.ma_don_payos} · {tien(donCho.gia)}
              </Chu>
            </View>
            <Chu size={13} dam="damVua" color={mau.chinh}>
              Tiếp tục
            </Chu>
          </The>
        </Pressable>
      )}
      <TrangThaiTai
        {...{ dangTai, loi, taiLai }}
        rong={duLieu && !ds.length}
        tieuDeRong="Chưa có gói đang bán"
      />
      {!id && (
        <Chu
          size={12}
          dam="dam"
          color={mau.chuPhu}
          style={{ paddingTop: 4, letterSpacing: 0.6 }}
        >
          CÁC GÓI TẬP
        </Chu>
      )}
      {ds.map((g) =>
        id ? (
          <View key={g.id} style={{ gap: 16 }}>
            <View
              style={{
                backgroundColor: mau.chinh,
                borderRadius: 24,
                padding: 24,
              }}
            >
              <View
                style={{
                  backgroundColor: mau.nangLuong,
                  paddingHorizontal: 10,
                  paddingVertical: 4,
                  borderRadius: 999,
                  alignSelf: 'flex-start',
                }}
              >
                <Chu size={11} dam="dam" color={mau.trenNangLuong}>
                  {g.so_buoi_pt > 0 ? 'PT đồng hành' : 'Tự tập cùng AI'}
                </Chu>
              </View>
              <Chu
                size={44}
                dam="ratDam"
                color={mau.trenChinh}
                style={{ lineHeight: 44, letterSpacing: -1.1, marginTop: 12 }}
              >
                {g.so_buoi_pt}
                <Chu size={18} dam="damVua" color={mau.trenChinh}>
                  {' '}
                  buổi PT
                </Chu>
              </Chu>
              <Chu
                size={22}
                dam="dam"
                color={mau.trenChinh}
                style={{ marginTop: 8 }}
              >
                {tien(g.gia)}
              </Chu>
              <Chu
                size={13}
                color={mau.trenChinh}
                style={{ opacity: 0.85, marginTop: 4 }}
              >
                Hiệu lực {g.thoi_han_ngay} ngày
                {g.so_buoi_pt > 0
                  ? ` · ${tien(Math.round(g.gia / g.so_buoi_pt))} mỗi buổi`
                  : ''}
              </Chu>
            </View>
            <The style={{ gap: 12 }}>
              <Chu size={16} dam="dam">
                Quyền lợi
              </Chu>
              <QuyenLoiGoi goi={g} />
            </The>
            <The style={{ gap: 8 }}>
              <Chu size={13.5} dam="dam">
                Điều khoản
              </Chu>
              <Chu size={13.5} color={mau.chuPhu}>
                Đặt lịch trước giờ tập ít nhất 4 giờ, hủy trước ít nhất 2 giờ.
              </Chu>
              <Chu size={13.5} color={mau.chuPhu}>
                Buổi chỉ bị trừ khi HLV đánh dấu hoàn thành.
              </Chu>
              <Chu size={13.5} color={mau.chuPhu}>
                Gói hết hạn sẽ không còn đặt lịch PT được.
              </Chu>
            </The>
          </View>
        ) : (
          <Pressable
            key={g.id}
            accessibilityRole="button"
            onPress={() => navigation.push('GoiTap', { id: g.id })}
          >
            <The style={{ gap: 0 }}>
              <View
                style={{
                  flexDirection: 'row',
                  alignItems: 'flex-start',
                  justifyContent: 'space-between',
                  gap: 8,
                }}
              >
                <View style={{ flex: 1 }}>
                  <Chu size={18} dam="ratDam" style={{ letterSpacing: -0.45 }}>
                    {g.ten_goi}
                  </Chu>
                  <Chu size={12.5} color={mau.chuPhu}>
                    Hiệu lực {g.thoi_han_ngay} ngày
                  </Chu>
                </View>
                <Nhan>
                  {g.so_buoi_pt > 0 ? `${g.so_buoi_pt} buổi PT` : 'AI'}
                </Nhan>
              </View>
              <View
                style={{
                  marginTop: 16,
                  flexDirection: 'row',
                  alignItems: 'flex-end',
                  justifyContent: 'space-between',
                }}
              >
                <Chu size={24} dam="ratDam" style={{ letterSpacing: -0.6 }}>
                  {tien(g.gia)}
                </Chu>
                {g.so_buoi_pt > 0 && (
                  <Chu size={12.5} color={mau.chuPhu}>
                    {tien(Math.round(g.gia / g.so_buoi_pt))}/buổi
                  </Chu>
                )}
              </View>
            </The>
          </Pressable>
        ),
      )}
      {!id && (
        <Chu
          size={12}
          color={mau.chuPhu}
          style={{ textAlign: 'center', lineHeight: 19.5 }}
        >
          Mỗi tài khoản chỉ có một gói hoạt động tại một thời điểm.
        </Chu>
      )}
      {!id && (
        <PhanTrang meta={duLieu?.meta} dangTai={dangTai} onChange={datPage} />
      )}
      <HopXacNhan
        visible={!!chon}
        tieuDe={
          cho.current.coCho()
            ? 'Thử lại đơn đang chờ kết quả'
            : 'Tạo đơn mua gói?'
        }
        moTa="Giá và quyền lợi được chốt trong đơn do hệ thống trả về. Chỉ tiếp tục thanh toán sau khi bạn kiểm tra đơn."
        dangGui={dangGui}
        loi={loiGui?.message}
        onDong={() => datChon(null)}
        onGui={taoDon}
        nhanGui={cho.current.coCho() ? 'Thử lại cùng yêu cầu' : 'Tạo đơn'}
      >
        {chon && (
          <>
            <Chu dam="dam">
              {chon.ten_goi} · {tien(chon.gia)}
            </Chu>
            <QuyenLoiGoi goi={chon} />
          </>
        )}
        {!!loiGui && (
          <Nut
            loai="phu"
            disabled={dangGui}
            onPress={() => {
              datChon(null)
              navigation.navigate('DonHang')
            }}
          >
            Kiểm tra đơn đã tạo
          </Nut>
        )}
      </HopXacNhan>
    </ManHinh>
  )
}
