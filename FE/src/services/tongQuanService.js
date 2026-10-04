import http from '../utils/http'

export default {
  async taiBaoCao(params, signal) {
    return (await http.get('/admin/bao-cao', { params, signal })).data
  },
  async taiTongQuan(vaiTro, signal, params) {
    const khuVuc = { ADMIN: 'admin', HUAN_LUYEN_VIEN: 'pt', KHACH_HANG: 'khach-hang' }[vaiTro]
    if (!khuVuc) throw new Error('Vai trò không hợp lệ.')
    return (await http.get(`/${khuVuc}/tong-quan`, { signal, params })).data
  },
}
