import { useRef, useState } from 'react'
import Constants from 'expo-constants'
import { View, Pressable, Platform } from 'react-native'
import { Chu, Nut, TruongNhap } from '../../components/GiaoDien'
import KhungTaiKhoan from '../../components/KhungTaiKhoan'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { useGiaoDien } from '../../theme'
import { KHACH_HANG, HUAN_LUYEN_VIEN } from '../../data/minhHoa'

export default function DangNhap({ navigation }) {
  const { mau } = useGiaoDien()
  const { moBanXem, dangNhap, dangThaoTac, loiPhien } = useXemTruoc()
  const [email, datEmail] = useState('')
  const [matKhau, datMatKhau] = useState('')
  const [loiTruong, datLoiTruong] = useState({})
  const [loiDangNhap, datLoiDangNhap] = useState('')
  const dangGui = useRef(false)

  async function kiemTraForm() {
    if (dangGui.current || dangThaoTac) return
    const loi = {}
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim()))
      loi.email = 'Vui lòng nhập địa chỉ email hợp lệ.'
    if (!matKhau) loi.matKhau = 'Vui lòng nhập mật khẩu.'
    datLoiTruong(loi)
    if (Object.keys(loi).length) return
    dangGui.current = true
    datLoiDangNhap('')
    try {
      await dangNhap({
        email: email.trim().toLowerCase(),
        password: matKhau,
        ten_thiet_bi:
          `Tr0ond · ${Platform.OS} · ${Constants.deviceName || 'Thiết bị'}`.slice(
            0,
            100,
          ),
      })
    } catch (loi) {
      datLoiDangNhap(loi.message)
      datLoiTruong({
        email: loi.errors?.email?.[0],
        matKhau: loi.errors?.password?.[0],
      })
    } finally {
      datMatKhau('')
      dangGui.current = false
    }
  }

  function xemGiaoDien(vaiTro) {
    datMatKhau('')
    datEmail('')
    moBanXem(vaiTro)
  }

  return (
    <KhungTaiKhoan
      tieuDe="Chào mừng trở lại"
      moTa="Đăng nhập để tiếp tục buổi tập của bạn."
    >
      <View style={{ gap: 16 }}>
        <TruongNhap
          nhan="Email"
          value={email}
          editable={!dangThaoTac}
          onChangeText={datEmail}
          placeholder="ban@email.com"
          keyboardType="email-address"
          autoCapitalize="none"
          autoCorrect={false}
          autoComplete="email"
          textContentType="emailAddress"
          loi={loiTruong.email}
          maxLength={191}
        />
        <TruongNhap
          nhan="Mật khẩu"
          value={matKhau}
          editable={!dangThaoTac}
          onChangeText={datMatKhau}
          hienAnMatKhau
          autoCapitalize="none"
          autoCorrect={false}
          autoComplete="current-password"
          textContentType="password"
          onSubmitEditing={kiemTraForm}
          returnKeyType="done"
          maxLength={72}
          loi={loiTruong.matKhau}
        />
        {!!(loiDangNhap || loiPhien) && (
          <Chu
            size={13}
            color={mau.loi}
            accessibilityLiveRegion="polite"
            style={{
              padding: 12,
              backgroundColor: mau.loiNhat,
              borderRadius: 12,
            }}
          >
            {loiDangNhap || loiPhien}
          </Chu>
        )}
        <Pressable
          accessibilityRole="button"
          onPress={() => navigation.navigate('KhoiPhuc')}
          style={{
            alignSelf: 'flex-start',
            height: 24,
            justifyContent: 'center',
          }}
        >
          <Chu size={13} dam="damVua" color={mau.chinh}>
            Quên mật khẩu?
          </Chu>
        </Pressable>
        <Nut onPress={kiemTraForm} disabled={dangThaoTac}>
          {dangThaoTac ? 'Đang đăng nhập…' : 'Đăng nhập'}
        </Nut>
      </View>
      <View
        style={{
          flexDirection: 'row',
          justifyContent: 'center',
          gap: 4,
          marginTop: 20,
        }}
      >
        <Chu color={mau.chuPhu}>Chưa có tài khoản?</Chu>
        <Pressable
          accessibilityRole="button"
          onPress={() => navigation.navigate('DangKy')}
        >
          <Chu dam="dam" color={mau.chinh}>
            Đăng ký
          </Chu>
        </Pressable>
      </View>
      {!dangThaoTac && (
        <View
          style={{
            marginTop: 32,
            borderRadius: 16,
            borderWidth: 1,
            borderStyle: 'dashed',
            borderColor: mau.vien,
            padding: 16,
          }}
        >
          <Chu
            size={12}
            dam="dam"
            color={mau.chuPhu}
            style={{ letterSpacing: 0.6, marginBottom: 12 }}
          >
            TÀI KHOẢN DEMO
          </Chu>
          <View style={{ flexDirection: 'row', gap: 8 }}>
            <Nut
              loai="soft"
              sm
              style={{ flex: 1 }}
              onPress={() => xemGiaoDien(KHACH_HANG)}
            >
              Học viên
            </Nut>
            <Nut
              loai="soft"
              sm
              style={{ flex: 1 }}
              onPress={() => xemGiaoDien(HUAN_LUYEN_VIEN)}
            >
              Huấn luyện viên
            </Nut>
          </View>
          <Chu size={12} color={mau.chuPhu} style={{ marginTop: 12 }}>
            Bản xem trước dùng dữ liệu minh họa. Đăng ký tài khoản để sử dụng hệ
            thống thật.
          </Chu>
        </View>
      )}
      <View
        style={{
          flexDirection: 'row',
          justifyContent: 'center',
          gap: 24,
          marginTop: 20,
        }}
      >
        <Pressable
          accessibilityRole="button"
          onPress={() => navigation.navigate('GoiTap')}
        >
          <Chu size={12} color={mau.chinh}>
            Xem gói tập
          </Chu>
        </Pressable>
        <Pressable
          accessibilityRole="button"
          onPress={() => navigation.navigate('Faq')}
        >
          <Chu size={12} color={mau.chinh}>
            Trợ giúp
          </Chu>
        </Pressable>
      </View>
    </KhungTaiKhoan>
  )
}
