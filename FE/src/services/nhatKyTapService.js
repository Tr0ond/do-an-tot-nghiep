import http from '../utils/http'
import xacThucService from './xacThucService'

const ma = (id) => encodeURIComponent(id)
const goc = (laPt) => `/${laPt ? 'pt' : 'khach-hang'}/lich-tap`
export default {
  async taiDanhSach(khachId, params, signal) {
    return (
      await http.get(khachId ? `/pt/hoc-vien/${ma(khachId)}/lich-tap` : goc(false), {
        params,
        signal,
      })
    ).data
  },
  async taiChiTiet(id, laPt, signal) {
    return (await http.get(`${goc(laPt)}/${ma(id)}`, { signal })).data
  },
  async tao(khachId, duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.post(khachId ? `/pt/hoc-vien/${ma(khachId)}/lich-tap` : goc(false), duLieu))
      .data
  },
  async luu(id, duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.put(`${goc(false)}/${ma(id)}`, duLieu)).data
  },
  async thaoTac(id, laPt, hanhDong, duLieu) {
    await xacThucService.layCsrfCookie()
    return (await http.post(`${goc(laPt)}/${ma(id)}/${hanhDong}`, duLieu)).data
  },
}
