import http from '../utils/http'
import xacThucService from './xacThucService'

export default {
  async taiHoiThoai(params = {}, signal) {
    return (await http.get('/hoi-thoai', { params, signal })).data
  },
  async taiTin(id, params = {}, signal) {
    return (await http.get(`/hoi-thoai/${encodeURIComponent(id)}/tin-nhan`, { params, signal }))
      .data
  },
  async gui(id, duLieu, signal) {
    await xacThucService.layCsrfCookie()
    let payload = {
      client_message_id: duLieu.client_message_id,
      noi_dung: duLieu.noi_dung || '',
    }
    if (duLieu.anh?.length) {
      payload = new FormData()
      payload.append('client_message_id', duLieu.client_message_id)
      payload.append('noi_dung', duLieu.noi_dung || '')
      duLieu.anh.forEach((tep) => payload.append('anh[]', tep))
    }
    return (
      await http.post(`/hoi-thoai/${encodeURIComponent(id)}/tin-nhan`, payload, {
        timeout: 60000,
        signal,
      })
    ).data
  },
  async taiAnh(hoiThoaiId, tinId, viTri, signal) {
    return (
      await http.get(
        `/hoi-thoai/${encodeURIComponent(hoiThoaiId)}/tin-nhan/${encodeURIComponent(tinId)}/anh/${encodeURIComponent(viTri)}`,
        { responseType: 'blob', signal, timeout: 30000 },
      )
    ).data
  },
  async daDoc(id, tinNhanId) {
    await xacThucService.layCsrfCookie()
    return (
      await http.post(`/hoi-thoai/${encodeURIComponent(id)}/da-doc`, { tin_nhan_id: tinNhanId })
    ).data
  },
  async xacThucKenh(socketId, channelName) {
    await xacThucService.layCsrfCookie()
    return (
      await http.post('/broadcasting/auth', { socket_id: socketId, channel_name: channelName })
    ).data
  },
}
