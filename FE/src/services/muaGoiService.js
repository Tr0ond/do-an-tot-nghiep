import http from '../utils/http'
import xacThucService from './xacThucService'

export default {
  async taiDonHang(params, signal, admin = false) {
    return (await http.get(`${admin ? '/admin' : '/khach-hang'}/don-hang`, { params, signal })).data
  },
  async taiChiTiet(id, signal, admin = false) {
    return (
      await http.get(`${admin ? '/admin' : '/khach-hang'}/don-hang/${encodeURIComponent(id)}`, {
        signal,
      })
    ).data
  },
  async taoDon(duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.post('/khach-hang/don-hang', duLieu)).data
  },
  async thaoTacDon(id, hanhDong) {
    await xacThucService.layCsrfCookie()
    return (
      await http.post(
        `/khach-hang/don-hang/${encodeURIComponent(id)}/${hanhDong}`,
        {},
        { timeout: 18000 },
      )
    ).data
  },
  async taiGoiCuaToi(signal) {
    return (await http.get('/khach-hang/goi-cua-toi', { signal })).data
  },
  async taiPhanCong(page, signal) {
    return (await http.get('/admin/phan-cong', { params: { page }, signal })).data
  },
  async taiPT(page, signal) {
    return (await http.get('/admin/phan-cong/pt', { params: { page }, signal })).data
  },
  async phanCong(duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.post('/admin/phan-cong', duLieu)).data
  },
  async doiSoat(id, duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.patch(`/admin/thanh-toan/${encodeURIComponent(id)}/doi-soat`, duLieu)).data
  },
}
