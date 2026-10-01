import http from '../utils/http'
import { taoUrlMedia } from '../utils/baiTap'

const gocBackend = new URL(http.defaults.baseURL, window.location.origin).origin

export default {
  async taiBoLoc() {
    return (await http.get('/bai-tap/bo-loc')).data
  },
  async taiDanhSach(boLoc, signal) {
    return (await http.get('/bai-tap', { params: { ...boLoc, per_page: 12 }, signal })).data
  },
  async taiChiTiet(id, signal) {
    return (await http.get('/bai-tap/' + encodeURIComponent(id), { signal })).data
  },
  urlMedia(duongDan) {
    return taoUrlMedia(duongDan, gocBackend)
  },
}
