import http from '../utils/http'

export default {
  async taiTongQuan(vaiTro, signal) {
    const khuVuc = { ADMIN: 'admin', HUAN_LUYEN_VIEN: 'pt', KHACH_HANG: 'khach-hang' }[vaiTro]
    if (!khuVuc) throw new Error('Vai trò không hợp lệ.')
    return (await http.get(`/${khuVuc}/tong-quan`, { signal })).data
  },
}
