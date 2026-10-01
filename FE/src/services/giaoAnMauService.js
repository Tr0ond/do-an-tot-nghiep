import http from '../utils/http'
import xacThucService from './xacThucService'

export default {
  async taiDanhSach(params, signal, quanTri = false) {
    return (await http.get(quanTri ? '/admin/giao-an-mau' : '/pt/giao-an-mau', { params, signal }))
      .data
  },
  async taiChiTiet(id, signal, quanTri = false) {
    return (
      await http.get(
        (quanTri ? '/admin/giao-an-mau/' : '/pt/giao-an-mau/') + encodeURIComponent(id),
        { signal },
      )
    ).data
  },
  async taoGiaoAn(duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.post('/admin/giao-an-mau', duLieu)).data
  },
  async suaGiaoAn(id, duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.put('/admin/giao-an-mau/' + encodeURIComponent(id), duLieu)).data
  },
  async datTrangThai(id, duLieu) {
    await xacThucService.layCsrfCookie()
    return (
      await http.patch('/admin/giao-an-mau/' + encodeURIComponent(id) + '/trang-thai', duLieu)
    ).data
  },
}
