export const trangThaiLich = {
  CHO_XAC_NHAN: 'Chờ PT xác nhận',
  DA_XAC_NHAN: 'Đã xác nhận',
  HOAN_THANH: 'Hoàn thành',
  VANG_MAT: 'Vắng mặt',
  DA_HUY: 'Đã hủy / từ chối',
  HET_HAN: 'Yêu cầu hết hạn',
  QUA_HAN_XAC_NHAN: 'Quá hạn xác nhận',
}
export const tenHanhDong = {
  huy: 'Hủy lịch',
  'xac-nhan': 'Xác nhận lịch',
  'tu-choi': 'Từ chối yêu cầu',
  'hoan-thanh': 'Xác nhận hoàn thành',
  'vang-mat': 'Ghi nhận vắng mặt',
  'dong-xu-ly': 'Đóng xử lý',
}
export function ngayVietNam(luc = new Date()) {
  return new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Ho_Chi_Minh',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(luc)
}
export function gioVietNam(luc) {
  return new Intl.DateTimeFormat('vi-VN', {
    timeZone: 'Asia/Ho_Chi_Minh',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(luc))
}
// datetime-local hiển thị giờ Việt Nam kể cả khi máy người dùng ở múi giờ khác.
export function tuGioVietNam(ngay, gio) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(ngay) || !/^\d{2}:\d{2}$/.test(gio)) return null
  const luc = new Date(`${ngay}T${gio}:00+07:00`)
  if (!Number.isFinite(luc.getTime()) || ngayVietNam(luc) !== ngay || gioVietNam(luc) !== gio)
    return null
  return luc.toISOString()
}
export function khuVucVaiTro(vaiTro) {
  return { ADMIN: 'admin', HUAN_LUYEN_VIEN: 'pt', KHACH_HANG: 'khach-hang' }[vaiTro] || null
}
