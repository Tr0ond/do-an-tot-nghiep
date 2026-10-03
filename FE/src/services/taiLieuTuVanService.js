import http from '../utils/http'
import xacThuc from './xacThucService'
const goc = '/admin/tai-lieu-tu-van'
export default {
  async taiDanhSach(params = {}, signal) {
    return (await http.get(goc, { params, signal })).data
  },
  async faq(params = {}, signal) {
    return (await http.get('/faq', { params, signal })).data
  },
  async thongKe(signal) {
    return (await http.get('/admin/chatbot/thong-ke', { signal })).data
  },
  async tao(d) {
    await xacThuc.layCsrfCookie()
    return (await http.post(goc, d)).data
  },
  async sua(id, d) {
    await xacThuc.layCsrfCookie()
    return (await http.put(`${goc}/${encodeURIComponent(id)}`, d)).data
  },
  async thaoTac(id, hanhDong, phienBan) {
    await xacThuc.layCsrfCookie()
    return (
      await http.post(`${goc}/${encodeURIComponent(id)}/${hanhDong}`, { phien_ban: phienBan })
    ).data
  },
}
