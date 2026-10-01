import http from '../utils/http'
import xacThucService from './xacThucService'

export default {
  async taiDanhSach(params, signal) {
    return (await http.get('/admin/nhom-co', { params, signal })).data
  },
  async taiChiTiet(id, signal) {
    return (await http.get('/admin/nhom-co/' + encodeURIComponent(id), { signal })).data
  },
  async taoNhomCo(duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.post('/admin/nhom-co', duLieu)).data
  },
  async suaNhomCo(id, duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.put('/admin/nhom-co/' + encodeURIComponent(id), duLieu)).data
  },
  async datTrangThai(id, duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.patch('/admin/nhom-co/' + encodeURIComponent(id) + '/trang-thai', duLieu))
      .data
  },
}
