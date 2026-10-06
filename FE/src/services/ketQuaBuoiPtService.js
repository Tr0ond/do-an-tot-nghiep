import http from '../utils/http'
import xacThucService from './xacThucService'

export default {
  async tai(khuVuc, id, signal) {
    return (await http.get(`/${khuVuc}/lich-hen/${encodeURIComponent(id)}/ket-qua`, { signal }))
      .data
  },
  async luu(id, duLieu, signal) {
    await xacThucService.layCsrfCookie()
    return (await http.put(`/pt/lich-hen/${encodeURIComponent(id)}/ket-qua`, duLieu, { signal }))
      .data
  },
  async chot(id, updatedAt, signal) {
    await xacThucService.layCsrfCookie()
    return (
      await http.post(
        `/pt/lich-hen/${encodeURIComponent(id)}/ket-qua/chot`,
        { updated_at: updatedAt },
        { signal },
      )
    ).data
  },
}
