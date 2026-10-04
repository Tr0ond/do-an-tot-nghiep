import http from '../utils/http'
import xacThucService from './xacThucService'

export default {
  async tai(khachId, params, signal) {
    const url = khachId
      ? `/pt/hoc-vien/${encodeURIComponent(khachId)}/chi-so-co-the`
      : '/khach-hang/chi-so-co-the'
    return (await http.get(url, { params, signal })).data
  },
  async luu(id, duLieu) {
    await xacThucService.layCsrfCookie()
    const url = '/khach-hang/chi-so-co-the'
    return (
      await (id ? http.put(`${url}/${encodeURIComponent(id)}`, duLieu) : http.post(url, duLieu))
    ).data
  },
}
