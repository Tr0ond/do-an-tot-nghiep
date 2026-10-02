import http from '../utils/http'
import xacThucService from './xacThucService'

export default {
  async taiDanhSach(page = 1, signal) {
    return (await http.get('/admin/tai-khoan', { params: { page }, signal })).data
  },
  async taoTaiKhoan(duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.post('/admin/tai-khoan', duLieu)).data
  },
  async datTrangThai(id, duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.patch('/admin/tai-khoan/' + id + '/trang-thai', duLieu)).data
  },
}
