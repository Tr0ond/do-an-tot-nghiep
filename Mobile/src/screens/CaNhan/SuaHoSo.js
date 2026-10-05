import { HopXacNhan } from '../../components/HuanLuyen'
import { useEffect, useRef, useState } from 'react'
import { View, Pressable, Modal } from 'react-native'
import { usePreventRemove } from '@react-navigation/native'
import {
  AnhDaiDien,
  Chu,
  ManHinh,
  The,
  TruongNhap,
  Nut,
  Nhan,
  TieuDeMuc,
  NutIcon,
} from '../../components/GiaoDien'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { useGiaoDien } from '../../theme'
import { Chip } from '../../components/FigmaElements'
import { KHACH_HANG } from '../../data/minhHoa'

export default function SuaHoSo({ navigation }) {
  const {
    hoSo,
    datHoSo,
    datThongBao,
    dangXemTruoc,
    luuHoSo: guiHoSo,
    taiHoSo,
  } = useXemTruoc()
  const { mau } = useGiaoDien()
  const [duLieu, datDuLieu] = useState(() => ({
    ...hoSo,
    thoi_gian_co_the_tap: [...(hoSo.thoi_gian_co_the_tap || [])],
  }))
  const [loiTruong, datLoiTruong] = useState({})
  const [hanhDongRoi, datHanhDongRoi] = useState(null)
  const [choPhepRoi, datChoPhepRoi] = useState(false)
  const [banGoc, datBanGoc] = useState(() => ({
    ...hoSo,
    thoi_gian_co_the_tap: [...(hoSo.thoi_gian_co_the_tap || [])],
  }))
  const [dangLuu, datDangLuu] = useState(false)
  const [loiChung, datLoiChung] = useState('')
  const [them, datThem] = useState(false)
  const [canTaiLai, datCanTaiLai] = useState(false)
  const khoaGui = useRef(false)
  const laKhach = hoSo.vai_tro === KHACH_HANG
  const coThayDoi = JSON.stringify(duLieu) !== JSON.stringify(banGoc)
  usePreventRemove((coThayDoi || dangLuu) && !choPhepRoi, ({ data }) => {
    if (!khoaGui.current) datHanhDongRoi(data.action)
  })

  useEffect(() => {
    if (choPhepRoi && hanhDongRoi) navigation.dispatch(hanhDongRoi)
  }, [choPhepRoi, hanhDongRoi, navigation])

  function sua(truong, giaTri) {
    if (khoaGui.current) return
    datDuLieu((cu) => ({ ...cu, [truong]: giaTri }))
  }
  async function taiLaiHoSo() {
    if (khoaGui.current) return
    khoaGui.current = true
    datDangLuu(true)
    try {
      const moi = await taiHoSo()
      if (!moi) return
      datDuLieu(moi)
      datBanGoc(moi)
      datLoiChung('')
      datLoiTruong({})
      datCanTaiLai(false)
    } catch (loi) {
      datLoiChung(loi.message)
    } finally {
      khoaGui.current = false
      datDangLuu(false)
    }
  }
  async function luuHoSo() {
    if (khoaGui.current) return
    const loi = {}
    if (!duLieu.ho_ten.trim()) loi.ho_ten = 'Vui lòng nhập họ và tên.'
    if (laKhach && duLieu.ngay_sinh) {
      const ngay = new Date(`${duLieu.ngay_sinh}T00:00:00Z`)
      const homNay = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Ho_Chi_Minh',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
      }).format(new Date())
      if (
        !/^\d{4}-\d{2}-\d{2}$/.test(duLieu.ngay_sinh) ||
        Number.isNaN(ngay.getTime()) ||
        ngay.toISOString().slice(0, 10) !== duLieu.ngay_sinh ||
        duLieu.ngay_sinh > homNay
      )
        loi.ngay_sinh =
          'Nhập ngày hợp lệ theo năm-tháng-ngày, không ở tương lai.'
    }
    if (duLieu.thoi_gian_co_the_tap?.length > 14)
      loi.thoi_gian_co_the_tap = 'Tối đa 14 dòng thời gian tập.'
    if (duLieu.thoi_gian_co_the_tap?.some((dong) => dong.length > 120))
      loi.thoi_gian_co_the_tap = 'Mỗi dòng tối đa 120 ký tự.'
    datLoiTruong(loi)
    if (Object.keys(loi).length) return
    const daSua = {
      ...duLieu,
      ho_ten: duLieu.ho_ten.trim(),
      thoi_gian_co_the_tap: duLieu.thoi_gian_co_the_tap
        .map((dong) => dong.trim())
        .filter(Boolean),
    }
    khoaGui.current = true
    datDangLuu(true)
    datLoiChung('')
    try {
      if (dangXemTruoc) datHoSo(daSua)
      else {
        const payload = { ho_ten: daSua.ho_ten, updated_at: banGoc.updated_at }
        for (const truong of laKhach
          ? [
              'muc_tieu',
              'kinh_nghiem',
              'gioi_tinh',
              'ngay_sinh',
              'thoi_gian_co_the_tap',
            ]
          : ['chuyen_mon', 'gioi_thieu'])
          payload[truong] = daSua[truong] === '' ? null : daSua[truong]
        if (!(await guiHoSo(payload))) return
      }
      datThongBao({
        tieuDe: dangXemTruoc
          ? 'Đã cập nhật bản xem trước'
          : 'Đã cập nhật hồ sơ',
        noiDung: dangXemTruoc
          ? 'Thay đổi chỉ có trong lần xem giao diện này. Dữ liệu trên hệ thống chưa được cập nhật.'
          : 'Thông tin đã được lưu trên hệ thống.',
      })
      datChoPhepRoi(true)
      datHanhDongRoi({ type: 'GO_BACK' })
    } catch (loi) {
      datLoiChung(loi.message)
      datLoiTruong(
        Object.fromEntries(
          Object.entries(loi.errors || {}).map(([truong, messages]) => [
            truong,
            messages[0],
          ]),
        ),
      )
      if (loi.status === 409) datCanTaiLai(true)
    } finally {
      khoaGui.current = false
      datDangLuu(false)
    }
  }
  return (
    <ManHinh
      tieuDe="Chỉnh sửa hồ sơ"
      tacVu={
        <NutIcon
          icon="SlidersHorizontal"
          size={18}
          nhan="Thông tin hồ sơ bổ sung"
          disabled={dangLuu}
          onPress={() => datThem(true)}
          style={{
            width: 40,
            height: 40,
            borderWidth: 0,
            backgroundColor: 'transparent',
          }}
        />
      }
      onBack={() => navigation.goBack()}
      bas
      footer={
        <Nut onPress={luuHoSo} disabled={dangLuu || canTaiLai}>
          {dangLuu ? 'Đang xử lý…' : 'Lưu thay đổi'}
        </Nut>
      }
    >
      <View style={{ alignItems: 'center', paddingBottom: 8 }}>
        <AnhDaiDien ten={duLieu.ho_ten || 'A'} size={88} />
      </View>
      <View style={{ gap: 16 }}>
        <TruongNhap
          nhan="Họ và tên"
          value={duLieu.ho_ten}
          onChangeText={(text) => sua('ho_ten', text)}
          maxLength={255}
          loi={loiTruong.ho_ten}
          autoComplete="name"
        />
        <TruongNhap nhan="Email" value={hoSo.email} editable={false} />
        <Chu size={12} color={mau.chuPhu} style={{ marginTop: -12 }}>
          Email không thể thay đổi
        </Chu>
      </View>
      {laKhach && (
        <View style={{ gap: 6 }}>
          <Chu size={13} dam="damVua">
            Mục tiêu
          </Chu>
          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8 }}>
            {['Giảm mỡ', 'Tăng cơ', 'Sức bền', 'Sức khỏe chung'].map((g) => (
              <Chip
                key={g}
                chon={duLieu.muc_tieu === g}
                disabled={dangLuu}
                onPress={() => sua('muc_tieu', g)}
              >
                {g}
              </Chip>
            ))}
          </View>
          {!!duLieu.muc_tieu &&
            !['Giảm mỡ', 'Tăng cơ', 'Sức bền', 'Sức khỏe chung'].includes(
              duLieu.muc_tieu,
            ) && (
              <Chu size={12.5} color={mau.chuPhu}>
                {duLieu.muc_tieu}
              </Chu>
            )}
        </View>
      )}

      <Chu size={12} color={mau.chuPhu}>
        {dangXemTruoc
          ? 'Bạn đang chỉnh sửa dữ liệu minh họa.'
          : 'Thông tin sẽ được cập nhật trên hệ thống.'}{' '}
        Số đo cơ thể được quản lý ở màn hình riêng.
      </Chu>
      {!!loiChung && (
        <Chu color={mau.loi} accessibilityLiveRegion="polite">
          {loiChung}
        </Chu>
      )}
      {canTaiLai && (
        <Nut loai="phu" disabled={dangLuu} onPress={taiLaiHoSo}>
          Thay bản đang nhập bằng hồ sơ mới
        </Nut>
      )}
      <HopXacNhan
        visible={them}
        tieuDe="Thông tin bổ sung"
        onDong={() => datThem(false)}
      >
        <View style={{ gap: 16 }}>
          {laKhach && (
            <>
              <TruongNhap
                nhan="Ngày sinh (YYYY-MM-DD)"
                value={duLieu.ngay_sinh}
                onChangeText={(text) => sua('ngay_sinh', text)}
                placeholder="1998-05-15"
                maxLength={10}
                loi={loiTruong.ngay_sinh}
              />
              <Chu dam="damVua">Giới tính</Chu>
              <View style={{ flexDirection: 'row', gap: 8 }}>
                {[
                  ['NAM', 'Nam'],
                  ['NU', 'Nữ'],
                  ['KHAC', 'Khác'],
                ].map(([giaTri, nhan]) => (
                  <Pressable
                    key={giaTri}
                    accessibilityRole="radio"
                    accessibilityLabel={nhan}
                    accessibilityState={{
                      checked: duLieu.gioi_tinh === giaTri,
                    }}
                    onPress={() => sua('gioi_tinh', giaTri)}
                    style={({ pressed }) => ({
                      flex: 1,
                      borderRadius: 999,
                      paddingVertical: 9,
                      borderWidth: 1,
                      borderColor:
                        duLieu.gioi_tinh === giaTri ? mau.chinh : mau.vien,
                      backgroundColor:
                        duLieu.gioi_tinh === giaTri ? mau.chinhNhat : mau.nen,
                      opacity: pressed ? 0.7 : 1,
                    })}
                  >
                    <Chu
                      dam="damVua"
                      color={
                        duLieu.gioi_tinh === giaTri ? mau.chinh : mau.chuPhu
                      }
                      style={{ textAlign: 'center' }}
                    >
                      {nhan}
                    </Chu>
                  </Pressable>
                ))}
              </View>
            </>
          )}
          {laKhach ? (
            <The>
              <TieuDeMuc icon="activity">Mục tiêu & thói quen</TieuDeMuc>
              <TruongNhap
                nhan="Mục tiêu tập luyện"
                value={duLieu.muc_tieu}
                onChangeText={(text) => sua('muc_tieu', text)}
                maxLength={255}
                multiline
              />
              <TruongNhap
                nhan="Kinh nghiệm tập luyện"
                value={duLieu.kinh_nghiem}
                onChangeText={(text) => sua('kinh_nghiem', text)}
                maxLength={255}
              />
              <TruongNhap
                nhan="Thời gian có thể tập (mỗi dòng một khung)"
                value={duLieu.thoi_gian_co_the_tap.join('\n')}
                onChangeText={(text) =>
                  sua('thoi_gian_co_the_tap', text.split('\n'))
                }
                multiline
                maxLength={1694}
                loi={loiTruong.thoi_gian_co_the_tap}
              />
            </The>
          ) : (
            <The>
              <TieuDeMuc icon="award">Chuyên môn & giới thiệu</TieuDeMuc>
              <TruongNhap
                nhan="Chuyên môn"
                value={duLieu.chuyen_mon}
                onChangeText={(text) => sua('chuyen_mon', text)}
                maxLength={255}
              />
              <TruongNhap
                nhan="Giới thiệu bản thân"
                value={duLieu.gioi_thieu}
                onChangeText={(text) => sua('gioi_thieu', text)}
                multiline
                maxLength={5000}
              />
            </The>
          )}
          <Nut onPress={() => datThem(false)}>Xong</Nut>
        </View>
      </HopXacNhan>
      <HopXacNhan
        visible={!!hanhDongRoi && !choPhepRoi}
        tieuDe="Bỏ thay đổi chưa lưu?"
        moTa="Thông tin bạn vừa nhập sẽ không được giữ lại."
        nhanGui="Bỏ thay đổi"
        loai="loi"
        onDong={() => datHanhDongRoi(null)}
        onGui={() => datChoPhepRoi(true)}
      />
    </ManHinh>
  )
}
