import { useState } from 'react'
import {
  ActivityIndicator,
  Animated,
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  View,
  StyleSheet,
} from 'react-native'
import { Chu, The, Nut, NutIcon, Nhan, BieuTuong } from './GiaoDien'
import { useGiaoDien } from '../theme'
import { Trong } from './FigmaElements'
import { useHieuUngFigma } from '../hooks/useHieuUngFigma'
import {
  doiNgay,
  homNay,
  nhanNgay,
  thoiDiem,
  gioVietNam,
  trangThaiLich,
} from '../utils/lich'

export function TrangThaiTai({
  dangTai,
  loi,
  taiLai,
  rong,
  tieuDeRong = 'Chưa có dữ liệu',
  moTaRong,
}) {
  const { mau } = useGiaoDien()
  if (dangTai)
    return (
      <The>
        <ActivityIndicator color={mau.chinh} />
        <Chu style={{ textAlign: 'center' }}>Đang tải…</Chu>
      </The>
    )
  if (loi)
    return (
      <The>
        <Chu dam="dam" color={mau.loi}>
          Chưa tải được dữ liệu
        </Chu>
        <Chu accessibilityLiveRegion="polite">{loi}</Chu>
        <Nut onPress={taiLai} icon="refresh-cw">
          Thử lại
        </Nut>
      </The>
    )
  if (rong) return <Trong tieuDe={tieuDeRong} moTa={moTaRong} />
  return null
}
export function PhanTrang({ meta, dangTai, onChange }) {
  if (!meta || meta.last_page <= 1) return null
  return (
    <View style={styles.phanTrang}>
      <Nut
        loai="phu"
        icon="chevron-left"
        disabled={dangTai || meta.current_page <= 1}
        onPress={() => onChange(meta.current_page - 1)}
      >
        Trước
      </Nut>
      <Chu size={12}>
        Trang {meta.current_page}/{meta.last_page}
      </Chu>
      <Nut
        loai="phu"
        icon="chevron-right"
        disabled={dangTai || meta.current_page >= meta.last_page}
        onPress={() => onChange(meta.current_page + 1)}
      >
        Sau
      </Nut>
    </View>
  )
}
export function TheLich({ lich, onPress, laKhach }) {
  const { mau } = useGiaoDien()
  const trangThai = trangThaiLich[lich.trang_thai] || lich.trang_thai
  const ngay = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Ho_Chi_Minh',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(new Date(lich.bat_dau_luc))
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`${laKhach ? lich.pt : lich.khach_hang || lich.ho_ten}, ${thoiDiem(lich.bat_dau_luc)}, ${trangThai}`}
      onPress={onPress}
      style={({ pressed }) => ({ transform: [{ scale: pressed ? 0.99 : 1 }] })}
    >
      <The style={{ flexDirection: 'row', alignItems: 'center', gap: 12 }}>
        <View
          style={{
            width: 56,
            height: 56,
            borderRadius: 12,
            backgroundColor: mau.chinhNhat,
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <Chu size={11} dam="damVua" color={mau.chinh}>
            {
              ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][
                new Date(`${ngay}T00:00:00Z`).getUTCDay()
              ]
            }
          </Chu>
          <Chu size={17} dam="ratDam" color={mau.chinh}>
            {ngay.slice(-2)}
          </Chu>
        </View>
        <View style={{ flex: 1, minWidth: 0 }}>
          <Chu size={15} dam="dam">
            {gioVietNam(lich.bat_dau_luc)} – {gioVietNam(lich.ket_thuc_luc)}
          </Chu>
          <Chu size={13} color={mau.chuPhu} numberOfLines={1}>
            {laKhach ? `PT ${lich.pt}` : lich.khach_hang || lich.ho_ten} · 60
            phút
          </Chu>
        </View>
        <Nhan
          trangThai={lich.trang_thai}
          loai={lich.trang_thai === 'CHO_XAC_NHAN' ? 'vang' : 'chinh'}
        >
          {trangThai}
        </Nhan>
      </The>
    </Pressable>
  )
}
export function HopXacNhan({
  visible,
  tieuDe,
  moTa,
  children,
  dangGui,
  onDong,
  onGui,
  nhanGui = 'Xác nhận',
  loi,
  loai = 'chinh',
  chiNutGui = false,
  khoaGui = false,
  tacVu,
}) {
  const { mau } = useGiaoDien()
  const hieuUng = useHieuUngFigma(visible, 'sheet')
  return (
    <Modal
      statusBarTranslucent
      visible={visible}
      transparent
      onRequestClose={() => !dangGui && onDong()}
    >
      <KeyboardAvoidingView
        style={[styles.manChe, { backgroundColor: mau.manChe }]}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      >
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Đóng hộp thoại"
          disabled={dangGui}
          onPress={onDong}
          style={StyleSheet.absoluteFill}
        />
        <Animated.View
          accessibilityViewIsModal
          style={[styles.hop, { backgroundColor: mau.the }, hieuUng]}
        >
          <ScrollView
            keyboardShouldPersistTaps="handled"
            contentContainerStyle={{ gap: 16 }}
          >
            <View
              style={{
                width: 40,
                height: 4,
                borderRadius: 2,
                backgroundColor: mau.vien,
                alignSelf: 'center',
                marginBottom: -4,
              }}
            />
            <View
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                gap: 8,
                marginBottom: -4,
              }}
            >
              <Chu
                size={18}
                dam="dam"
                style={{ flex: 1, letterSpacing: -0.45 }}
              >
                {tieuDe}
              </Chu>
              {tacVu}
            </View>
            {moTa && (
              <Chu size={14} color={mau.chuPhu} style={{ lineHeight: 22.75 }}>
                {moTa}
              </Chu>
            )}
            {children}
            {!!loi && (
              <Chu color={mau.loi} accessibilityLiveRegion="polite">
                {loi}
              </Chu>
            )}
            {onGui && (
              <View
                style={{
                  flexDirection: 'row',
                  gap: 12,
                  marginTop: chiNutGui ? 0 : 4,
                }}
              >
                {!chiNutGui && (
                  <Nut
                    loai="phu"
                    style={{ flex: 1 }}
                    disabled={dangGui}
                    onPress={onDong}
                  >
                    {onGui ? 'Quay lại' : 'Đóng'}
                  </Nut>
                )}
                {onGui && (
                  <Nut
                    style={{ flex: 1 }}
                    disabled={dangGui || khoaGui}
                    loai={loai}
                    onPress={onGui}
                  >
                    {dangGui ? 'Đang xử lý…' : nhanGui}
                  </Nut>
                )}
              </View>
            )}
          </ScrollView>
        </Animated.View>
      </KeyboardAvoidingView>
    </Modal>
  )
}
export function ChonTrangThai({ value, onChange }) {
  const [mo, datMo] = useState(false)
  return (
    <>
      <Nut loai="phu" icon="filter" onPress={() => datMo(true)}>
        {trangThaiLich[value] || 'Tất cả trạng thái'}
      </Nut>
      <HopXacNhan
        visible={mo}
        tieuDe="Lọc trạng thái"
        onDong={() => datMo(false)}
      >
        {[['', 'Tất cả trạng thái'], ...Object.entries(trangThaiLich)].map(
          ([ma, nhan]) => (
            <Nut
              key={ma}
              loai={value === ma ? 'chinh' : 'phu'}
              onPress={() => {
                onChange(ma)
                datMo(false)
              }}
            >
              {nhan}
            </Nut>
          ),
        )}
      </HopXacNhan>
    </>
  )
}
export function ChonNgay({ value, onChange, disabled = false }) {
  const [mo, datMo] = useState(false)
  const [thang, datThang] = useState((value || homNay()).slice(0, 7))
  const ngay = value || homNay()
  const { mau } = useGiaoDien()
  const dau = `${thang}-01`
  const batDau = doiNgay(
    dau,
    -((new Date(`${dau}T00:00:00Z`).getUTCDay() + 6) % 7),
  )
  const cacNgay = Array.from({ length: 42 }, (_, i) => doiNgay(batDau, i))
  function doiThang(so) {
    const d = new Date(`${dau}T00:00:00Z`)
    d.setUTCMonth(d.getUTCMonth() + so)
    datThang(d.toISOString().slice(0, 7))
  }
  return (
    <>
      <View style={styles.hang}>
        <Nut
          loai="phu"
          disabled={disabled}
          accessibilityLabel="Ngày trước"
          onPress={() => onChange(doiNgay(ngay, -1))}
          icon="chevron-left"
          style={{ paddingHorizontal: 10 }}
        />
        <Nut
          loai="phu"
          disabled={disabled}
          icon="calendar"
          style={{ flex: 1 }}
          onPress={() => {
            datThang(ngay.slice(0, 7))
            datMo(true)
          }}
        >
          {value ? nhanNgay(value) : 'Tất cả ngày'}
        </Nut>
        <Nut
          loai="phu"
          disabled={disabled}
          accessibilityLabel="Ngày tiếp"
          onPress={() => onChange(doiNgay(ngay, 1))}
          icon="chevron-right"
          style={{ paddingHorizontal: 10 }}
        />
      </View>
      <HopXacNhan visible={mo} tieuDe="Chọn ngày" onDong={() => datMo(false)}>
        <View style={styles.hang}>
          <NutIcon
            icon="chevron-left"
            nhan="Tháng trước"
            onPress={() => doiThang(-1)}
          />
          <Chu dam="dam">
            Tháng {thang.slice(5)} / {thang.slice(0, 4)}
          </Chu>
          <NutIcon
            icon="chevron-right"
            nhan="Tháng tiếp"
            onPress={() => doiThang(1)}
          />
        </View>
        <View style={styles.lichLuoi}>
          {['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'].map((n) => (
            <View key={n} style={styles.oNgay}>
              <Chu size={12} color={mau.chuPhu}>
                {n}
              </Chu>
            </View>
          ))}
          {cacNgay.map((n) => (
            <Pressable
              key={n}
              accessibilityRole="button"
              accessibilityLabel={nhanNgay(n)}
              accessibilityState={{ selected: n === value }}
              onPress={() => {
                onChange(n)
                datMo(false)
              }}
              style={({ pressed }) => [
                styles.oNgay,
                {
                  borderRadius: 8,
                  backgroundColor: n === value ? mau.chinh : 'transparent',
                  opacity: n.slice(0, 7) !== thang ? 0.45 : pressed ? 0.65 : 1,
                },
              ]}
            >
              <Chu
                dam={n === value ? 'dam' : 'thuong'}
                color={n === value ? mau.trenChinh : mau.chu}
              >
                {Number(n.slice(8))}
              </Chu>
            </Pressable>
          ))}
        </View>
        <Nut
          loai="phu"
          onPress={() => {
            onChange(homNay())
            datMo(false)
          }}
        >
          Hôm nay
        </Nut>
      </HopXacNhan>
    </>
  )
}
const styles = StyleSheet.create({
  hang: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 8,
  },
  phanTrang: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    alignItems: 'center',
    gap: 8,
  },
  manChe: {
    flex: 1,
    padding: 0,
    alignItems: 'center',
    justifyContent: 'flex-end',
  },
  hop: {
    width: '100%',
    maxHeight: '88%',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 24,
  },
  lichLuoi: { flexDirection: 'row', flexWrap: 'wrap' },
  oNgay: {
    width: '14.285714%',
    minHeight: 48,
    alignItems: 'center',
    justifyContent: 'center',
  },
})
