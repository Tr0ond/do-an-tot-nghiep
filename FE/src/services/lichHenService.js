import http from '../utils/http'
import xacThucService from './xacThucService'

export default {
  async taiLich(khuVuc, params, signal) {
    return (await http.get(`/${khuVuc}/lich-hen`, { params, signal })).data
  },
  async taiChiTiet(khuVuc, id, signal) {
    return (await http.get(`/${khuVuc}/lich-hen/${encodeURIComponent(id)}`, { signal })).data
  },
  async taiKhung(khuVuc, params, signal) {
    return (await http.get(`/${khuVuc}/khung-gio`, { params, signal })).data
  },
  async datLich(duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.post('/khach-hang/lich-hen', duLieu)).data
  },
  async taoKhung(duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.post('/pt/khung-gio', duLieu)).data
  },
  async doiKhung(id, trangThai) {
    await xacThucService.layCsrfCookie()
    return (await http.patch(`/pt/khung-gio/${encodeURIComponent(id)}`, { trang_thai: trangThai }))
      .data
  },
  async thaoTac(khuVuc, id, hanhDong, lyDo) {
    await xacThucService.layCsrfCookie()
    return (
      await http.post(`/${khuVuc}/lich-hen/${encodeURIComponent(id)}/${hanhDong}`, { ly_do: lyDo })
    ).data
  },
}
