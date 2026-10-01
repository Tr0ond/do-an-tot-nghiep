import http from '../utils/http'
import xacThucService from './xacThucService'

export default {
  async taiDanhSach(params, signal, quanTri = false) {
    return (await http.get(quanTri ? '/admin/goi-tap' : '/goi-tap', { params, signal })).data
  },
  async taiChiTiet(id, signal, quanTri = false) {
    return (
      await http.get((quanTri ? '/admin/goi-tap/' : '/goi-tap/') + encodeURIComponent(id), {
        signal,
      })
    ).data
  },
  async taoGoiTap(duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.post('/admin/goi-tap', duLieu)).data
  },
  async suaGoiTap(id, duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.put('/admin/goi-tap/' + encodeURIComponent(id), duLieu)).data
  },
  async datTrangThai(id, duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.patch('/admin/goi-tap/' + encodeURIComponent(id) + '/trang-thai', duLieu))
      .data
  },
}
