import http from '../utils/http'
import xacThucService from './xacThucService'

const ma = (id) => encodeURIComponent(id)
export default {
  async taiHocVien(params, signal) {
    return (await http.get('/pt/hoc-vien', { params, signal })).data
  },
  async taiDanhSach(khachId, params, signal) {
    return (
      await http.get(khachId ? `/pt/hoc-vien/${ma(khachId)}/ke-hoach` : '/khach-hang/ke-hoach', {
        params,
        signal,
      })
    ).data
  },
  async taiChiTiet(id, laPt, signal) {
    return (await http.get(`/${laPt ? 'pt' : 'khach-hang'}/ke-hoach/${ma(id)}`, { signal })).data
  },
  async tao(khachId, duLieu) {
    await xacThucService.layCsrfCookie()
    return (
      await http.post(
        khachId ? `/pt/hoc-vien/${ma(khachId)}/ke-hoach` : '/khach-hang/ke-hoach',
        duLieu,
      )
    ).data
  },
  async sua(id, duLieu, laPt = true) {
    await xacThucService.layCsrfCookie()
    return (await http.put(`/${laPt ? 'pt' : 'khach-hang'}/ke-hoach/${ma(id)}`, duLieu)).data
  },
  async thaoTac(id, laPt, hanhDong, updatedAt) {
    await xacThucService.layCsrfCookie()
    return (
      await http.post(`/${laPt ? 'pt' : 'khach-hang'}/ke-hoach/${ma(id)}/${hanhDong}`, {
        updated_at: updatedAt,
      })
    ).data
  },
}
