export const trangThaiDon = {
  CHO_THANH_TOAN: 'Chờ thanh toán',
  DANG_SU_DUNG: 'Đang sử dụng',
  HET_HAN: 'Gói đã hết hạn',
  HET_HAN_THANH_TOAN: 'Hết hạn thanh toán',
  DA_HUY: 'Đã hủy',
  CAN_DOI_SOAT: 'Cần đối soát',
  DA_XAC_MINH: 'Đã xác minh',
  DA_HOAN_TIEN: 'Đã ghi nhận hoàn tiền',
}

export const tien = (gia) =>
  new Intl.NumberFormat('vi-VN', {
    maximumFractionDigits: 0,
  }).format(gia) + 'đ'

export function linkPayosHopLe(url) {
  try {
    const u = new URL(url)
    return (
      u.protocol === 'https:' &&
      u.hostname === 'pay.payos.vn' &&
      !u.username &&
      !u.password &&
      !u.port
    )
  } catch {
    return false
  }
}

export function conChoThanhToan(d, moc = Date.now()) {
  return (
    d?.trang_thai === 'CHO_THANH_TOAN' &&
    new Date(d.han_thanh_toan).getTime() > moc
  )
}

export function kiemTraTaiKhoan(d, dangKy = false) {
  const loi = {}
  if (
    !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(d.email.trim()) ||
    d.email.trim().length > 191
  )
    loi.email = 'Nhập email hợp lệ, tối đa 191 ký tự.'
  if (dangKy && !d.ho_ten.trim()) loi.ho_ten = 'Nhập họ tên.'
  if (dangKy && d.ho_ten.trim().length > 255)
    loi.ho_ten = 'Họ tên tối đa 255 ký tự.'
  if (d.password !== undefined) {
    if (
      [...d.password].length < 8 ||
      new TextEncoder().encode(d.password).length > 72
    )
      loi.password = 'Mật khẩu từ 8 ký tự và tối đa 72 byte.'
    if (d.password_confirmation !== d.password)
      loi.password_confirmation = 'Mật khẩu nhập lại chưa khớp.'
  }
  return loi
}

export function layMaKhoiPhuc(chu) {
  if (/^[a-f0-9]{64}$/.test(chu.trim())) return chu.trim()
  try {
    const u = new URL(chu.trim())
    if (
      !['https:', 'http:'].includes(u.protocol) ||
      u.pathname !== '/dat-lai-mat-khau'
    )
      return ''
    const ma = new URLSearchParams(u.hash.slice(1)).get('token')
    return /^[a-f0-9]{64}$/.test(ma || '') ? ma : ''
  } catch {
    return ''
  }
}

export function soanYeuCauAi(buoi, tuan, bai) {
  const ds = [buoi, tuan, bai].map(Number)
  if (
    ds.some((v) => !Number.isInteger(v) || v < 1) ||
    ds[0] > 7 ||
    ds[2] > 8 ||
    ds[0] * ds[1] > 30 ||
    ds.reduce((a, b) => a * b, 1) > 120
  )
    throw new Error(
      'Tối đa 7 buổi/tuần, 8 bài/buổi, 30 buổi và 120 bài mỗi giáo án.',
    )
  return `Tạo cho tôi giáo án ${ds[0]} buổi/tuần trong ${ds[1]} tuần, mỗi buổi ${ds[2]} bài. `
}

export const maDaHoanTat = (tin, ma) =>
  tin.some((t) => t.client_request_id === ma && t.trang_thai === 'THANH_CONG')
