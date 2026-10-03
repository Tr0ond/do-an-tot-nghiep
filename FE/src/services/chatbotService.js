import http from '../utils/http'
import xacThuc from './xacThucService'

const goc = '/khach-hang/chatbot/hoi-thoai'
export default {
  async taiDanhSach(page = 1, signal) {
    return (await http.get(goc, { params: { page }, signal })).data
  },
  async taiChiTiet(id, page = 1, signal) {
    return (await http.get(`${goc}/${encodeURIComponent(id)}`, { params: { page }, signal })).data
  },
  async tao(clientRequestId) {
    await xacThuc.layCsrfCookie()
    return (await http.post(goc, { client_request_id: clientRequestId })).data
  },
  async gui(id, body) {
    await xacThuc.layCsrfCookie()
    return (await http.post(`${goc}/${encodeURIComponent(id)}/tin-nhan`, body, { timeout: 75000 }))
      .data
  },
}
