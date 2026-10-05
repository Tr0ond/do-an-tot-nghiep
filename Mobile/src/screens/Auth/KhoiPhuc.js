import { useState } from 'react'
import { View } from 'react-native'
import KhungTaiKhoan from '../../components/KhungTaiKhoan'
import { Chu, The, TruongNhap, Nut, BieuTuong } from '../../components/GiaoDien'
import { useThaoTac } from '../../hooks/useThaoTac'
import { hoanThienService as api } from '../../services/hoanThienService'
import { kiemTraTaiKhoan, layMaKhoiPhuc } from '../../utils/hoanThien'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { useGiaoDien } from '../../theme'

export default function KhoiPhuc({ navigation }) {
  const [d, dat] = useState({
    email: '',
    token: '',
    password: '',
    password_confirmation: '',
  })
  const [datLai, datDatLai] = useState(false)
  const [loi, datLoi] = useState({})
  const [thongBao, datThongBao] = useState('')
  const { mau } = useGiaoDien()
  const { datThongBao: hienThongBao } = useXemTruoc()
  const { gui, dangGui, loiGui, datLoiGui } = useThaoTac()
  function guiForm() {
    const e = kiemTraTaiKhoan(datLai ? d : { email: d.email })
    const token = layMaKhoiPhuc(d.token)
    if (datLai && !token)
      e.token = 'Dán mã 64 ký tự hoặc liên kết đặt lại từ email.'
    datLoi(e)
    if (Object.keys(e).length) return
    gui(
      () =>
        datLai
          ? api.datLaiMatKhau({
              ...d,
              email: d.email.trim().toLowerCase(),
              token,
            })
          : api.quenMatKhau(d.email.trim().toLowerCase()),
      (r) => {
        datThongBao(r.message)
        dat((cu) => ({
          ...cu,
          token: '',
          password: '',
          password_confirmation: '',
        }))
        if (datLai) {
          datDatLai(false)
          navigation.popTo('DangNhap')
          hienThongBao({ tieuDe: 'Đã đặt lại mật khẩu', noiDung: r.message })
        }
      },
    )
  }
  return (
    <KhungTaiKhoan
      tieuDe={
        datLai
          ? 'Đặt mật khẩu mới'
          : thongBao
            ? 'Kiểm tra email'
            : 'Quên mật khẩu?'
      }
      moTa={
        datLai
          ? 'Mật khẩu mới sẽ đăng xuất tài khoản khỏi mọi thiết bị.'
          : thongBao
            ? 'Hướng dẫn đặt lại mật khẩu đã được gửi.'
            : 'Nhập email, chúng tôi sẽ gửi hướng dẫn đặt lại mật khẩu.'
      }
      onBack={() => navigation.goBack()}
    >
      {thongBao && !datLai ? (
        <>
          <View
            style={{
              flexDirection: 'row',
              gap: 16,
              alignItems: 'center',
              borderRadius: 16,
              padding: 16,
              backgroundColor: mau.chinhNhat,
              marginBottom: 24,
            }}
          >
            <BieuTuong ten="Mail" size={24} />
            <Chu size={14} style={{ flex: 1, lineHeight: 22.75 }}>
              {thongBao} Liên kết có hiệu lực trong 60 phút.
            </Chu>
          </View>
          <View style={{ gap: 12 }}>
            <Nut
              disabled={dangGui}
              onPress={() => {
                datDatLai(true)
                datThongBao('')
                datLoi({})
                datLoiGui(null)
              }}
            >
              Tôi đã mở liên kết
            </Nut>
            <Nut loai="phu" disabled={dangGui} onPress={guiForm}>
              Gửi lại email
            </Nut>
            <Nut
              loai="ghost"
              disabled={dangGui}
              onPress={() => navigation.popTo('DangNhap')}
            >
              Về đăng nhập
            </Nut>
          </View>
        </>
      ) : (
        <View style={{ gap: 16 }}>
          {datLai && (
            <>
              <TruongNhap
                nhan="Mật khẩu mới"
                value={d.password}
                onChangeText={(v) => dat({ ...d, password: v })}
                hienAnMatKhau
                maxLength={72}
                autoCapitalize="none"
                autoCorrect={false}
                editable={!dangGui}
                loi={loi.password || loiGui?.errors?.password?.[0]}
              />
              <TruongNhap
                nhan="Xác nhận mật khẩu"
                value={d.password_confirmation}
                onChangeText={(v) => dat({ ...d, password_confirmation: v })}
                hienAnMatKhau
                maxLength={72}
                autoCapitalize="none"
                autoCorrect={false}
                editable={!dangGui}
                loi={
                  loi.password_confirmation ||
                  loiGui?.errors?.password_confirmation?.[0]
                }
              />
            </>
          )}
          <TruongNhap
            nhan="Email"
            placeholder="ban@email.com"
            value={d.email}
            onChangeText={(v) => dat({ ...d, email: v })}
            keyboardType="email-address"
            autoCapitalize="none"
            autoCorrect={false}
            maxLength={191}
            editable={!dangGui}
            loi={loi.email || loiGui?.errors?.email?.[0]}
          />
          {datLai && (
            <TruongNhap
              nhan="Mã hoặc liên kết khôi phục"
              value={d.token}
              onChangeText={(v) => dat({ ...d, token: v })}
              maxLength={2048}
              autoCapitalize="none"
              autoCorrect={false}
              editable={!dangGui}
              loi={loi.token || loiGui?.errors?.token?.[0]}
            />
          )}
          {!!loiGui && (
            <Chu color={mau.loi} accessibilityLiveRegion="polite">
              {loiGui.message}
            </Chu>
          )}
          <Nut disabled={dangGui} onPress={guiForm}>
            {dangGui
              ? 'Đang xử lý…'
              : datLai
                ? 'Đặt lại mật khẩu'
                : 'Gửi hướng dẫn'}
          </Nut>
          <Nut
            loai="ghost"
            disabled={dangGui}
            onPress={() => {
              datDatLai(!datLai)
              datLoi({})
              datLoiGui(null)
              datThongBao('')
              dat((cu) => ({
                ...cu,
                token: '',
                password: '',
                password_confirmation: '',
              }))
            }}
          >
            {datLai
              ? 'Yêu cầu liên kết mới'
              : 'Đã có liên kết · Đặt lại trong app'}
          </Nut>
        </View>
      )}
    </KhungTaiKhoan>
  )
}
