import { goiApi } from '../utils/http'

const khuVuc = (vaiTro) => (vaiTro === 'KHACH_HANG' ? 'khach-hang' : 'pt')
const ma = (id) => encodeURIComponent(id)
const thamSo = (params = {}) => {
  const query = Object.entries(params)
    .filter(([, value]) => value !== '' && value != null)
    .map(
      ([key, value]) =>
        `${encodeURIComponent(key)}=${encodeURIComponent(value)}`,
    )
    .join('&')
  return query ? `?${query}` : ''
}
const doc = (url, token, signal) => goiApi(url, { token, signal, dayDu: true })
const ghi = (url, token, duLieu, method = 'POST') =>
  goiApi(url, { token, duLieu, method, dayDu: true })

export const huanLuyenService = {
  taiTongQuan: (token, vaiTro, signal) =>
    doc(`/${khuVuc(vaiTro)}/tong-quan`, token, signal),
  taiLich: (token, vaiTro, params, signal) =>
    doc(`/${khuVuc(vaiTro)}/lich-hen${thamSo(params)}`, token, signal),
  taiChiTiet: (token, vaiTro, id, signal) =>
    doc(`/${khuVuc(vaiTro)}/lich-hen/${ma(id)}`, token, signal),
  taiKhung: (token, vaiTro, params, signal) =>
    doc(`/${khuVuc(vaiTro)}/khung-gio${thamSo(params)}`, token, signal),
  datLich: (token, payload) => ghi('/khach-hang/lich-hen', token, payload),
  thaoTac: (token, vaiTro, id, hanhDong, lyDo) =>
    ghi(
      `/${khuVuc(vaiTro)}/lich-hen/${ma(id)}/${ma(hanhDong)}`,
      token,
      lyDo ? { ly_do: lyDo } : {},
    ),
  taoKhung: (token, batDau) =>
    ghi('/pt/khung-gio', token, { bat_dau_luc: batDau }),
  doiKhung: (token, id, trangThai) =>
    ghi(`/pt/khung-gio/${ma(id)}`, token, { trang_thai: trangThai }, 'PATCH'),
  taiHocVien: (token, params, signal) =>
    doc(`/pt/hoc-vien${thamSo(params)}`, token, signal),
  taiHoSoHocVien: (token, id, signal) =>
    doc(`/khach-hang/ho-so/${ma(id)}`, token, signal),
}
