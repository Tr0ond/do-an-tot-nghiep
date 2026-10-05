const vaiTroHopLe = ['KHACH_HANG', 'HUAN_LUYEN_VIEN']

export function taoQuanLyPhien({ kho, api, onChange }) {
  let theHe = 0
  let token = null
  let taiKhoan = null
  let dangThaoTac = false
  let hangKho = Promise.resolve()
  const ghiKho = (hanhDong) => {
    const ketQua = hangKho.then(hanhDong, hanhDong)
    hangKho = ketQua.catch(() => {})
    return ketQua
  }
  const phat = (duLieu) => onChange({ taiKhoan, dangThaoTac, ...duLieu })
  const kiemTraTaiKhoan = (duLieu) => {
    if (
      !duLieu ||
      !vaiTroHopLe.includes(duLieu.vai_tro) ||
      duLieu.trang_thai !== 'HOAT_DONG'
    )
      throw new Error('Tài khoản không được phép dùng app.')
    return duLieu
  }
  async function matPhien() {
    theHe++
    api.huyYeuCau()
    token = null
    taiKhoan = null
    phat({
      loiPhien:
        'Phiên đăng nhập đã hết hạn hoặc bị thu hồi. Vui lòng đăng nhập lại.',
    })
    await ghiKho(kho.xoa).catch(() =>
      phat({
        loiPhien:
          'Phiên đã mất hiệu lực. Chưa xóa được phiên trong kho bảo mật; vui lòng đăng nhập lại.',
      }),
    )
  }
  async function khoiPhuc() {
    const lan = ++theHe
    dangThaoTac = true
    phat({ dangKhoiPhuc: true, loiPhien: '', loiKhoiPhuc: '' })
    try {
      const chuoi = await kho.doc()
      if (lan !== theHe) return
      if (!chuoi) return
      let daLuu
      try {
        daLuu = JSON.parse(chuoi)
      } catch {
        await ghiKho(kho.xoa)
        return
      }
      if (
        typeof daLuu.access_token !== 'string' ||
        !Number.isFinite(Date.parse(daLuu.expires_at)) ||
        Date.parse(daLuu.expires_at) <= Date.now()
      ) {
        await ghiKho(kho.xoa)
        phat({ loiPhien: 'Phiên đã hết hạn. Vui lòng đăng nhập lại.' })
        return
      }
      const hoSo = kiemTraTaiKhoan(await api.taiHoSo(daLuu.access_token))
      if (lan !== theHe) return
      token = daLuu.access_token
      taiKhoan = hoSo
    } catch (loi) {
      if (lan !== theHe) return
      if (loi.status === 401 || loi.status === 403) await ghiKho(kho.xoa)
      else phat({ loiKhoiPhuc: loi.message })
    } finally {
      if (lan === theHe) {
        dangThaoTac = false
        phat({ dangKhoiPhuc: false })
      }
    }
  }
  async function dangNhap(duLieu) {
    if (dangThaoTac) return
    const lan = ++theHe
    dangThaoTac = true
    phat({ loiPhien: '', loiKhoiPhuc: '' })
    let moi
    try {
      moi = await api.dangNhap(duLieu)
      kiemTraTaiKhoan(moi.tai_khoan)
      if (
        typeof moi.access_token !== 'string' ||
        !Number.isFinite(Date.parse(moi.expires_at)) ||
        Date.parse(moi.expires_at) <= Date.now()
      )
        throw new Error('Hệ thống trả phiên không hợp lệ.')
      if (lan !== theHe) {
        await api.dangXuat(moi.access_token).catch(() => {})
        return
      }
      await ghiKho(() =>
        kho.luu({ access_token: moi.access_token, expires_at: moi.expires_at }),
      )
      if (lan !== theHe) {
        await api.dangXuat(moi.access_token).catch(() => {})
        return
      }
      token = moi.access_token
      taiKhoan = moi.tai_khoan
    } catch (loi) {
      if (moi?.access_token)
        await api.dangXuat(moi.access_token).catch(() => {})
      if (lan === theHe) throw loi
    } finally {
      if (lan === theHe) {
        dangThaoTac = false
        phat({})
      }
    }
  }
  async function dangXuat() {
    const cu = token
    const lan = ++theHe
    api.huyYeuCau()
    token = null
    taiKhoan = null
    dangThaoTac = true
    phat({ loiKhoiPhuc: '', loiPhien: '' })
    let thongBao = 'Đã đăng xuất thiết bị này.'
    try {
      const ketQua = await Promise.allSettled([
        ghiKho(kho.xoa),
        cu ? api.dangXuat(cu) : Promise.resolve(),
      ])
      const loiKho = ketQua[0].status === 'rejected'
      const loiMayChu =
        ketQua[1].status === 'rejected' && ketQua[1].reason.status !== 401
      if (loiKho)
        thongBao = loiMayChu
          ? 'Đã đóng giao diện nhưng chưa xóa được phiên lưu và chưa xác nhận thu hồi trên hệ thống. Vui lòng thử lại khi có mạng.'
          : 'Đã thu hồi phiên trên hệ thống nhưng chưa xóa được bản lưu trong kho bảo mật.'
      else if (loiMayChu)
        thongBao =
          'Đã thoát trên máy này nhưng chưa xác nhận thu hồi phiên trên hệ thống. Phiên còn chịu hạn 30 ngày.'
    } finally {
      if (lan === theHe) {
        dangThaoTac = false
        phat({ loiPhien: thongBao })
      }
    }
  }
  async function voiPhien(hanhDong, laHoSo = true) {
    const lan = theHe
    const cu = token
    if (!cu) throw new Error('Bạn cần đăng nhập lại.')
    try {
      const duLieu = await hanhDong(cu)
      if (lan !== theHe) return null
      if (laHoSo) {
        taiKhoan = kiemTraTaiKhoan(duLieu)
        phat({})
      }
      return duLieu
    } catch (loi) {
      if (lan !== theHe) return null
      if (
        loi.status === 401 ||
        (loi.status === 403 && /khóa|ngừng hoạt động/.test(loi.message))
      )
        await matPhien()
      throw loi
    }
  }
  return {
    khoiPhuc,
    dangNhap,
    dangXuat,
    goiDichVu: (hanhDong) => voiPhien(hanhDong, false),
    taiHoSo: () => voiPhien(api.taiHoSo),
    luuHoSo: (duLieu) => voiPhien((cu) => api.luuHoSo(cu, duLieu)),
  }
}
