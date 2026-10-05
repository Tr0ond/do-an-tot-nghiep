import { goiApi } from '../utils/http'
import { File } from 'expo-file-system'

const ma = encodeURIComponent
const query = (d = {}) => {
  const q = Object.entries(d)
    .filter(([, v]) => v !== '' && v != null)
    .map(([k, v]) => `${ma(k)}=${ma(v)}`)
    .join('&')
  return q ? `?${q}` : ''
}
const doc = (url, token, signal) => goiApi(url, { token, signal, dayDu: true })
const ghi = (url, token, duLieu, signal) =>
  goiApi(url, { token, duLieu, signal, method: 'POST', dayDu: true })

export const traoDoiService = {
  taiHoiThoai: (t, q, s) => doc(`/hoi-thoai${query(q)}`, t, s),
  taiTin: (t, id, q, s) =>
    doc(`/hoi-thoai/${ma(id)}/tin-nhan${query(q)}`, t, s),
  docTin: (t, id, tinId, s) =>
    ghi(`/hoi-thoai/${ma(id)}/da-doc`, t, { tin_nhan_id: tinId }, s),
  guiTin: (t, id, d, s) => {
    if (!d.anh.length)
      return ghi(
        `/hoi-thoai/${ma(id)}/tin-nhan`,
        t,
        {
          client_message_id: d.client_message_id,
          noi_dung: d.noi_dung,
        },
        s,
      )
    const formData = new FormData()
    formData.append('client_message_id', d.client_message_id)
    if (d.noi_dung) formData.append('noi_dung', d.noi_dung)
    for (const a of d.anh) {
      const file = new File(a.uri)
      if (!file.exists)
        throw Object.assign(
          new Error('Ảnh tạm không còn. Bỏ ảnh và chọn lại từ thư viện.'),
          { status: 422 },
        )
      // Expo SDK 57 nhận File/Blob trong multipart; object { uri } không được fetch hỗ trợ.
      formData.append('anh[]', file, a.ten)
    }
    return goiApi(`/hoi-thoai/${ma(id)}/tin-nhan`, {
      token: t,
      formData,
      signal: s,
      method: 'POST',
      dayDu: true,
      timeout: 60000,
    })
  },
  taiAnh: (t, id, tinId, viTri, s) =>
    goiApi(`/hoi-thoai/${ma(id)}/tin-nhan/${ma(tinId)}/anh/${ma(viTri)}`, {
      token: t,
      signal: s,
      anh: true,
      timeout: 30000,
    }),
  xacThucKenh: (t, socket_id, channel_name, s) =>
    goiApi('/broadcasting/auth', {
      token: t,
      signal: s,
      duLieu: { socket_id, channel_name },
      method: 'POST',
      raw: true,
    }),
  taiThongBao: (t, q, s) => doc(`/thong-bao${query(q)}`, t, s),
  docThongBao: (t, id, s) => ghi(`/thong-bao/${ma(id)}/da-doc`, t, {}, s),
  docTatCa: (t, s) => ghi('/thong-bao/da-doc-tat-ca', t, {}, s),
}
