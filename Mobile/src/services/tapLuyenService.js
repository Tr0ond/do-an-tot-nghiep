import { goiApi } from '../utils/http'

const ma = (id) => encodeURIComponent(id)
const khuVuc = (vaiTro) => (vaiTro === 'KHACH_HANG' ? 'khach-hang' : 'pt')
const danhSach = (vaiTro, khachId, module) =>
  vaiTro === 'KHACH_HANG'
    ? `/khach-hang/${module}`
    : `/pt/hoc-vien/${ma(khachId)}/${module}`
const query = (d = {}) => {
  const q = Object.entries(d)
    .filter(([, v]) => v !== '' && v != null)
    .map(([k, v]) => `${ma(k)}=${ma(v)}`)
    .join('&')
  return q ? `?${q}` : ''
}
const doc = (url, token, signal) => goiApi(url, { token, signal, dayDu: true })
const ghi = (url, token, duLieu, method = 'POST') =>
  goiApi(url, { token, duLieu, method, dayDu: true })

export const tapLuyenService = {
  taiBaiTap: (t, q, s) => doc(`/bai-tap${query(q)}`, t, s),
  taiBoLoc: (t, s) => doc('/bai-tap/bo-loc', t, s),
  taiBai: (t, id, s) => doc(`/bai-tap/${ma(id)}`, t, s),
  taiMau: (t, q, s) => doc(`/pt/giao-an-mau${query(q)}`, t, s),
  taiChiTietMau: (t, id, s) => doc(`/pt/giao-an-mau/${ma(id)}`, t, s),
  taiGiaoAn: (t, v, kh, q, s) =>
    doc(`${danhSach(v, kh, 'ke-hoach')}${query(q)}`, t, s),
  taiKeHoach: (t, v, id, s) => doc(`/${khuVuc(v)}/ke-hoach/${ma(id)}`, t, s),
  luuKeHoach: (t, v, kh, id, d) =>
    ghi(
      id ? `/${khuVuc(v)}/ke-hoach/${ma(id)}` : danhSach(v, kh, 'ke-hoach'),
      t,
      d,
      id ? 'PUT' : 'POST',
    ),
  doiKeHoach: (t, v, id, h, updated_at) =>
    ghi(`/${khuVuc(v)}/ke-hoach/${ma(id)}/${ma(h)}`, t, { updated_at }),
  taiLichTap: (t, v, kh, q, s) =>
    doc(`${danhSach(v, kh, 'lich-tap')}${query(q)}`, t, s),
  taoLichTap: (t, v, kh, d) => ghi(danhSach(v, kh, 'lich-tap'), t, d),
  taiBuoiTap: (t, v, id, s) => doc(`/${khuVuc(v)}/lich-tap/${ma(id)}`, t, s),
  luuBuoiTap: (t, id, d) => ghi(`/khach-hang/lich-tap/${ma(id)}`, t, d, 'PUT'),
  doiBuoiTap: (t, v, id, h, d) =>
    ghi(`/${khuVuc(v)}/lich-tap/${ma(id)}/${ma(h)}`, t, d),
  taiChiSo: (t, v, kh, q, s) =>
    doc(`${danhSach(v, kh, 'chi-so-co-the')}${query(q)}`, t, s),
  luuChiSo: (t, id, d) =>
    ghi(
      `/khach-hang/chi-so-co-the${id ? `/${ma(id)}` : ''}`,
      t,
      d,
      id ? 'PUT' : 'POST',
    ),
}
