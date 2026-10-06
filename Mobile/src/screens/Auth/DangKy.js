import { useState } from 'react'
import { View, Pressable } from 'react-native'
import KhungTaiKhoan from '../../components/KhungTaiKhoan'
import { Chu, The, TruongNhap, Nut } from '../../components/GiaoDien'
import { useThaoTac } from '../../hooks/useThaoTac'
import { hoanThienService as api } from '../../services/hoanThienService'
import { kiemTraTaiKhoan } from '../../utils/hoanThien'
import { useGiaoDien } from '../../theme'

export default function DangKy({ navigation }) {
  const [d, dat] = useState({
    ho_ten: '',
    email: '',
    password: '',
    password_confirmation: '',
  })
  const [loi, datLoi] = useState({})
  const [xong, datXong] = useState(false)
  const { mau } = useGiaoDien()
  const { gui, dangGui, loiGui } = useThaoTac()
  function dangKy() {
    const e = kiemTraTaiKhoan(d, true)
    datLoi(e)
    if (Object.keys(e).length) return
    gui(
      () =>
        api.dangKy({
          ...d,
          ho_ten: d.ho_ten.trim(),
          email: d.email.trim().toLowerCase(),
        }),
      () => {
        dat({ ho_ten: '', email: '', password: '', password_confirmation: '' })
        datXong(true)
      },
    )
  }
  return (
    <KhungTaiKhoan
      tieuDe="Tạo tài khoản"
      moTa="Bắt đầu hành trình tập luyện cùng huấn luyện viên."
      onBack={() => navigation.goBack()}
    >
      {xong ? (
        <The>
          <Chu size={23} dam="dam">
            Tài khoản đã được tạo
          </Chu>
          <Chu>Đăng nhập để bắt đầu tập luyện cùng FitForge.</Chu>
          <Nut onPress={() => navigation.popTo('DangNhap')}>Đến đăng nhập</Nut>
        </The>
      ) : (
        <View style={{ gap: 16 }}>
          {[
            ['ho_ten', 'Họ và tên', 'user', 255],
            ['email', 'Email', 'mail', 191],
            ['password', 'Mật khẩu', 'lock', 72],
            ['password_confirmation', 'Xác nhận mật khẩu', 'lock', 72],
          ].map(([k, n, i, max]) => (
            <TruongNhap
              key={k}
              nhan={n}
              hint={
                k === 'password'
                  ? 'Tối thiểu 8 ký tự, tối đa 72 byte'
                  : undefined
              }
              placeholder={
                k === 'ho_ten'
                  ? 'Nguyễn Văn A'
                  : k === 'email'
                    ? 'ban@email.com'
                    : undefined
              }
              hienAnMatKhau={k.startsWith('password')}
              value={d[k]}
              onChangeText={(v) => dat({ ...d, [k]: v })}
              editable={!dangGui}
              secureTextEntry={k.startsWith('password')}
              autoCapitalize={k === 'ho_ten' ? 'words' : 'none'}
              autoCorrect={false}
              keyboardType={k === 'email' ? 'email-address' : 'default'}
              maxLength={max}
              loi={loi[k] || loiGui?.errors?.[k]?.[0]}
            />
          ))}
          {!!loiGui && (
            <Chu color={mau.loi} accessibilityLiveRegion="polite">
              {loiGui.message}
            </Chu>
          )}
          <Nut disabled={dangGui} onPress={dangKy}>
            {dangGui ? 'Đang tạo tài khoản…' : 'Tạo tài khoản'}
          </Nut>
          <Chu
            size={12.5}
            color={mau.chuPhu}
            style={{ marginTop: 4, textAlign: 'center', lineHeight: 20.3125 }}
          >
            Tài khoản đăng ký là tài khoản học viên. Huấn luyện viên được cấp
            bởi quản trị viên.
          </Chu>
        </View>
      )}
    </KhungTaiKhoan>
  )
}
