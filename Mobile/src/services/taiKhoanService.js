import { goiApi, huyYeuCau } from '../utils/http'

export const taiKhoanService = {
  dangNhap: (duLieu) => goiApi('/mobile/dang-nhap', { method: 'POST', duLieu }),
  dangXuat: (token) => goiApi('/mobile/dang-xuat', { method: 'POST', token }),
  taiHoSo: (token) => goiApi('/me', { token }),
  luuHoSo: (token, duLieu) =>
    goiApi('/me/ho-so', { method: 'PUT', token, duLieu }),
  huyYeuCau,
}
