import http from '../utils/http'
import xacThucService from './xacThucService'

export default {
  async taiDanhSach(page = 1) {
    return (await http.get('/admin/tai-khoan', { params: { page } })).data
  },
  async taoTaiKhoan(duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.post('/admin/tai-khoan', duLieu)).data
  },
}
