import http from '../utils/http'
import xacThucService from './xacThucService'

export default {
  async taiDanhSach(page = 1, signal) {
    return (await http.get('/thong-bao', { params: { page }, signal })).data
  },
  async daDoc(id, signal) {
    await xacThucService.layCsrfCookie()
    return (await http.post(`/thong-bao/${encodeURIComponent(id)}/da-doc`, {}, { signal })).data
  },
  async daDocTatCa(signal) {
    await xacThucService.layCsrfCookie()
    return (await http.post('/thong-bao/da-doc-tat-ca', {}, { signal })).data
  },
}
