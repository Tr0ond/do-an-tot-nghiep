import http from '../utils/http'

const gocBackend = new URL(http.defaults.baseURL, window.location.origin).origin

export default {
  async layCsrfCookie() {
    await http.get(gocBackend + '/sanctum/csrf-cookie')
  },
  async dangKy(duLieu) {
    await this.layCsrfCookie()
    return (await http.post(gocBackend + '/dang-ky', duLieu)).data
  },
  async dangNhap(duLieu) {
    await this.layCsrfCookie()
    return (await http.post(gocBackend + '/dang-nhap', duLieu)).data
  },
  async dangXuat() {
    await this.layCsrfCookie()
    return (await http.post(gocBackend + '/dang-xuat')).data
  },
  async taiTaiKhoan() {
    return (await http.get('/me')).data
  },
  async taiHoSo(id) {
    return (await http.get('/khach-hang/ho-so/' + id)).data
  },
  async suaHoSo(duLieu) {
    await this.layCsrfCookie()
    return (await http.put('/me/ho-so', duLieu)).data
  },
  async guiLienKet(duLieu) {
    await this.layCsrfCookie()
    return (await http.post(gocBackend + '/quen-mat-khau', duLieu)).data
  },
  async datLaiMatKhau(duLieu) {
    await this.layCsrfCookie()
    return (await http.post(gocBackend + '/dat-lai-mat-khau', duLieu)).data
  },
}
