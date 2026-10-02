export const nhanTrangThai = {
  CHO_THANH_TOAN: 'Chờ thanh toán',
  DANG_SU_DUNG: 'Đang sử dụng',
  HET_HAN: 'Gói đã hết hạn',
  HET_HAN_THANH_TOAN: 'Hết hạn thanh toán',
  DA_HUY: 'Đã hủy',
  CAN_DOI_SOAT: 'Cần đối soát',
  DA_XAC_MINH: 'Đã xác minh',
  DA_HOAN_TIEN: 'Đã ghi nhận hoàn tiền',
}

export function dinhDangLuc(luc) {
  if (!luc) return '—'
  const ngay = new Date(luc)
  return Number.isNaN(ngay.getTime())
    ? '—'
    : new Intl.DateTimeFormat('vi-VN', {
        timeZone: 'Asia/Ho_Chi_Minh',
        dateStyle: 'short',
        timeStyle: 'short',
      }).format(ngay)
}

export function conChoThanhToan(don, moc = Date.now()) {
  return don?.trang_thai === 'CHO_THANH_TOAN' && new Date(don.han_thanh_toan).getTime() > moc
}

export function linkPayosHopLe(url) {
  try {
    const lienKet = new URL(url)
    return (
      lienKet.protocol === 'https:' &&
      lienKet.hostname === 'pay.payos.vn' &&
      !lienKet.username &&
      !lienKet.password &&
      !lienKet.port
    )
  } catch {
    return false
  }
}
