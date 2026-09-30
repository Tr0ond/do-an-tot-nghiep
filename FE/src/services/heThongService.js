import http from '../utils/http'

export default {
  async kiemTraKetNoi() {
    const { data } = await http.get('/health')
    return data
  },
}
