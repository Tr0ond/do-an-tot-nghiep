import http from '../utils/http'
import xacThucService from './xacThucService'

export default {
  async taiBoLoc(signal) {
    return (await http.get('/admin/bai-tap/bo-loc', { signal })).data
  },
  async taiDanhSach(params, signal) {
    return (await http.get('/admin/bai-tap', { params, signal })).data
  },
  async taiChiTiet(id, signal) {
    return (await http.get('/admin/bai-tap/' + encodeURIComponent(id), { signal })).data
  },
  async taoBaiTap(duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.post('/admin/bai-tap', duLieu)).data
  },
  async suaBaiTap(id, duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.put('/admin/bai-tap/' + encodeURIComponent(id), duLieu)).data
  },
  async datTrangThai(id, duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.patch('/admin/bai-tap/' + encodeURIComponent(id) + '/trang-thai', duLieu))
      .data
  },
}
