import { goiApi } from '../utils/http'

const lay = (p, token, signal) => goiApi(p, { token, signal, dayDu: true })
const ghi = (p, token, duLieu, timeout = 15000) =>
  goiApi(p, { token, method: 'POST', duLieu, dayDu: true, timeout })

export const hoanThienService = {
  dangKy: (d) => ghi('/mobile/dang-ky', null, d),
  quenMatKhau: (email) => ghi('/mobile/quen-mat-khau', null, { email }),
  datLaiMatKhau: (d) => ghi('/mobile/dat-lai-mat-khau', null, d),
  taiFaq: (page, signal) => lay(`/faq?page=${page}`, null, signal),
  taiGoi: (page, signal) => lay(`/goi-tap?page=${page}`, null, signal),
  taiChiTietGoi: (id, signal) => lay(`/goi-tap/${id}`, null, signal),
  goiCuaToi: (token, signal) => lay('/khach-hang/goi-cua-toi', token, signal),
  taiDon: (token, page, signal, trangThai = '') =>
    lay(
      `/khach-hang/don-hang?page=${page}${trangThai ? `&trang_thai=${encodeURIComponent(trangThai)}` : ''}`,
      token,
      signal,
    ),
  chiTietDon: (token, id, signal) =>
    lay(`/khach-hang/don-hang/${id}`, token, signal),
  taoDon: (token, d) => ghi('/khach-hang/don-hang', token, d),
  taoLink: (token, id) =>
    ghi(`/khach-hang/don-hang/${id}/link-thanh-toan`, token),
  dongBoDon: (token, id) => ghi(`/khach-hang/don-hang/${id}/dong-bo`, token),
  taiHoiAi: (token, page, signal) =>
    lay(`/khach-hang/chatbot/hoi-thoai?page=${page}`, token, signal),
  taoHoiAi: (token, ma) =>
    ghi('/khach-hang/chatbot/hoi-thoai', token, { client_request_id: ma }),
  taiTinAi: (token, id, page, signal) =>
    lay(`/khach-hang/chatbot/hoi-thoai/${id}?page=${page}`, token, signal),
  guiAi: (token, id, d) =>
    ghi(`/khach-hang/chatbot/hoi-thoai/${id}/tin-nhan`, token, d, 90000),
}
