import { useState } from 'react'
import { View, Pressable } from 'react-native'
import { useGiaoDien } from '../../theme'
import MinhHoaBaiTap from '../../components/MinhHoaBaiTap'
import {
  ManHinh,
  Chu,
  The,
  Nut,
  Nhan,
  BieuTuong,
  NutIcon,
} from '../../components/GiaoDien'
import { TrangThaiTai, HopXacNhan } from '../../components/HuanLuyen'
import { LoiGhi } from '../../components/TapLuyen'
import { HuongDanBai } from './ChiTietBaiTap'
import { useDuLieu } from '../../hooks/useDuLieu'
import { useThaoTac } from '../../hooks/useThaoTac'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { tapLuyenService as api } from '../../services/tapLuyenService'
import { nhanTrangThai } from '../../utils/tapLuyen'
import { thoiDiem } from '../../utils/lich'

const cacThaoTac = [
  ['gui', 'Gửi đề xuất'],
  ['xac-nhan', 'Xác nhận giáo án'],
  ['ap-dung', 'Áp dụng giáo án'],
  ['luu-tru', 'Ngừng áp dụng'],
  ['huy', 'Hủy giáo án'],
  ['an', 'Ẩn giáo án'],
  ['hien-lai', 'Hiện lại giáo án'],
]
export default function ChiTietGiaoAn({ navigation, route }) {
  const { goiDichVu, vaiTro, datThongBao } = useXemTruoc()
  const tai = useDuLieu(
    (s) => goiDichVu((t) => api.taiKeHoach(t, vaiTro, route.params.id, s)),
    route.params.id,
  )
  const { mau } = useGiaoDien()
  const [ngayMo, datNgayMo] = useState(1)
  const ghi = useThaoTac()
  const [chon, datChon] = useState(null)
  const [huongDan, datHuongDan] = useState(null)
  const k = tai.duLieu?.data
  const [tuyChon, datTuyChon] = useState(false)
  const hanhDongPhu = cacThaoTac.filter(
    ([h]) =>
      ['huy', 'luu-tru', 'an', 'hien-lai'].includes(h) &&
      k?.[`co_the_${h.replace('-', '_')}`],
  )
  return (
    <ManHinh
      bas
      tieuDe="Chi tiết giáo án"
      tacVu={
        hanhDongPhu.length > 0 && (
          <NutIcon
            icon="SlidersHorizontal"
            size={18}
            nhan="Tùy chọn giáo án"
            onPress={() => datTuyChon(true)}
            style={{
              width: 40,
              height: 40,
              borderWidth: 0,
              backgroundColor: 'transparent',
            }}
          />
        )
      }
      contentStyle={{ gap: 12 }}
      onBack={() => navigation.goBack()}
      footer={
        k &&
        (k.co_the_sua ||
          k.co_the_gui ||
          k.co_the_xac_nhan ||
          k.co_the_ap_dung ||
          k.trang_thai === 'DANG_AP_DUNG') && (
          <View style={{ gap: 8 }}>
            {k.co_the_sua && (
              <Nut
                disabled={ghi.dangGui}
                onPress={() =>
                  navigation.navigate('SoanGiaoAn', {
                    id: k.id,
                    khachId: k.khach_hang_id,
                  })
                }
              >
                Chỉnh sửa bản nháp
              </Nut>
            )}
            {cacThaoTac
              .filter(
                ([h]) =>
                  !['huy', 'luu-tru', 'an', 'hien-lai'].includes(h) &&
                  k[`co_the_${h.replace('-', '_')}`],
              )
              .map(([h, ten]) => (
                <Nut
                  key={h}
                  disabled={ghi.dangGui}
                  loai={['huy', 'luu-tru'].includes(h) ? 'loi' : 'chinh'}
                  onPress={() => {
                    ghi.datLoiGui(null)
                    datChon([h, ten, k.updated_at])
                  }}
                >
                  {ten}
                </Nut>
              ))}
            {k.trang_thai === 'DANG_AP_DUNG' && (
              <Nut
                loai="soft"
                icon="Dumbbell"
                onPress={() =>
                  navigation.navigate('LichTuTap', {
                    khachId:
                      vaiTro === 'KHACH_HANG' ? undefined : k.khach_hang_id,
                  })
                }
              >
                Lên lịch tự tập
              </Nut>
            )}
          </View>
        )
      }
    >
      <TrangThaiTai {...tai} />
      <LoiGhi loi={ghi.loiGui} />
      {k && (
        <>
          <View
            style={{
              borderRadius: 24,
              backgroundColor: mau.chinh,
              padding: 20,
            }}
          >
            <View
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                justifyContent: 'space-between',
                gap: 12,
              }}
            >
              <Chu
                size={12}
                dam="damVua"
                color={mau.trenChinh}
                style={{
                  flex: 1,
                  opacity: 0.8,
                  textTransform: 'uppercase',
                  letterSpacing: 0.6,
                }}
              >
                {k.muc_tieu || 'Giáo án tập luyện'}
              </Chu>
              <Nhan trangThai={k.trang_thai}>
                {nhanTrangThai(k.trang_thai_hien_thi)}
              </Nhan>
            </View>
            <Chu
              size={22}
              dam="ratDam"
              color={mau.trenChinh}
              style={{ marginTop: 8, lineHeight: 27.5, letterSpacing: -0.55 }}
            >
              {k.ten_ke_hoach}
            </Chu>
            <Chu
              size={13}
              color={mau.trenChinh}
              style={{ marginTop: 6, opacity: 0.85 }}
            >
              {k.so_ngay_tap} buổi · {k.bai_tap?.length || 0} bài tập ·{' '}
              {k.nguon_tao === 'PT' ? 'HLV đề xuất' : 'Tự tạo'}
            </Chu>
            {k.han_duyet && (
              <Chu size={13} color={mau.trenChinh} style={{ marginTop: 6 }}>
                Hạn xác nhận: {thoiDiem(k.han_duyet)}
              </Chu>
            )}
          </View>
          {Array.from({ length: k.so_ngay_tap }, (_, i) => i + 1).map(
            (ngay) => {
              const cacBai = k.bai_tap?.filter((b) => b.ngay_thu === ngay) || []
              return (
                <View
                  key={ngay}
                  style={{
                    borderRadius: 16,
                    borderWidth: 1,
                    borderColor: mau.vien,
                    backgroundColor: mau.the,
                    overflow: 'hidden',
                  }}
                >
                  <Pressable
                    accessibilityRole="button"
                    accessibilityState={{ expanded: ngayMo === ngay }}
                    onPress={() => datNgayMo(ngayMo === ngay ? null : ngay)}
                    style={{
                      flexDirection: 'row',
                      alignItems: 'center',
                      gap: 12,
                      padding: 16,
                    }}
                  >
                    <View
                      style={{
                        width: 36,
                        height: 36,
                        borderRadius: 18,
                        backgroundColor: mau.chinhNhat,
                        alignItems: 'center',
                        justifyContent: 'center',
                      }}
                    >
                      <Chu size={14} dam="ratDam" color={mau.chinh}>
                        {ngay}
                      </Chu>
                    </View>
                    <View style={{ flex: 1 }}>
                      <Chu size={15} dam="dam">
                        Buổi {ngay}
                      </Chu>
                      <Chu size={12.5} color={mau.chuPhu}>
                        {cacBai.length} bài tập
                      </Chu>
                    </View>
                    <BieuTuong
                      ten={ngayMo === ngay ? 'ChevronUp' : 'ChevronDown'}
                      size={18}
                      color={mau.chuPhu}
                    />
                  </Pressable>
                  {ngayMo === ngay &&
                    cacBai.map((b) => (
                      <Pressable
                        key={b.id}
                        accessibilityRole="button"
                        onPress={() => datHuongDan(b)}
                        style={{
                          paddingHorizontal: 16,
                          paddingVertical: 12,
                          borderTopWidth: 1,
                          borderColor: mau.vien,
                          flexDirection: 'row',
                          alignItems: 'center',
                          gap: 12,
                        }}
                      >
                        <MinhHoaBaiTap />
                        <View style={{ flex: 1 }}>
                          <Chu size={14} dam="dam" numberOfLines={1}>
                            {b.ten_bai_tap}
                          </Chu>
                          <Chu size={12.5} color={mau.chuPhu}>
                            {b.so_hiep} × {b.so_lan_lap} ·{' '}
                            {b.muc_ta_kg != null ? `${b.muc_ta_kg} kg · ` : ''}
                            nghỉ {b.nghi_giay}s
                          </Chu>
                        </View>
                      </Pressable>
                    ))}
                </View>
              )
            },
          )}
          <Chu size={12}>
            Mỗi khách chỉ có một giáo án đang áp dụng. Chọn giáo án mới sẽ lưu
            trữ bản đang dùng và giữ lịch sử tập.
          </Chu>
        </>
      )}
      <HopXacNhan
        visible={!!chon}
        tieuDe={chon?.[1]}
        moTa={
          ['ap-dung', 'xac-nhan'].includes(chon?.[0])
            ? 'Giáo án đang dùng sẽ được lưu trữ. Lịch sử tập vẫn được giữ.'
            : chon?.[0] === 'gui'
              ? 'Khách có 24 giờ để xác nhận. Nội dung đã gửi sẽ không thể chỉnh sửa.'
              : 'Xác nhận thay đổi trạng thái giáo án?'
        }
        dangGui={ghi.dangGui}
        loi={ghi.loiGui?.message}
        onDong={() => datChon(null)}
        onGui={() =>
          ghi.gui(
            () =>
              goiDichVu((t) =>
                api.doiKeHoach(t, vaiTro, route.params.id, chon[0], chon[2]),
              ),
            async () => {
              datChon(null)
              await tai.taiLai()
              datThongBao({
                tieuDe: 'Đã cập nhật giáo án',
                noiDung: 'Trạng thái được lưu trên hệ thống.',
              })
            },
          )
        }
      />
      {ghi.loiGui?.status === 409 && (
        <Nut
          loai="phu"
          disabled={ghi.dangGui}
          onPress={() => {
            datChon(null)
            ghi.datLoiGui(null)
            tai.taiLai()
          }}
        >
          Tải lại trạng thái mới nhất
        </Nut>
      )}
      <HopXacNhan
        visible={tuyChon}
        tieuDe="Tùy chọn giáo án"
        onDong={() => datTuyChon(false)}
      >
        <View style={{ gap: 12 }}>
          {hanhDongPhu.map(([h, ten]) => (
            <Nut
              key={h}
              loai={['huy', 'luu-tru'].includes(h) ? 'loi' : 'phu'}
              disabled={ghi.dangGui}
              onPress={() => {
                datTuyChon(false)
                ghi.datLoiGui(null)
                datChon([h, ten, k.updated_at])
              }}
            >
              {ten}
            </Nut>
          ))}
        </View>
      </HopXacNhan>
      <HopXacNhan
        visible={!!huongDan}
        tieuDe={huongDan?.ten_bai_tap}
        onDong={() => datHuongDan(null)}
      >
        {huongDan && <HuongDanBai bai={huongDan} />}
        {!!huongDan?.ghi_chu && <Chu>{huongDan.ghi_chu}</Chu>}
      </HopXacNhan>
    </ManHinh>
  )
}
