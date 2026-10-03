export const nhanTrangThaiTap = {
  DA_LEN_LICH: 'Đã lên lịch',
  DANG_TAP: 'Đang ghi kết quả',
  HOAN_THANH: 'Hoàn thành',
  DA_HUY: 'Đã hủy',
}

export function ngayVietNam(ngay = new Date()) {
  return new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Ho_Chi_Minh',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(ngay)
}

export function khoangMacDinh() {
  const ngay = new Date()
  const tu = new Date(ngay)
  tu.setUTCDate(tu.getUTCDate() - 29)
  const den = new Date(ngay)
  den.setUTCDate(den.getUTCDate() + 30)
  return { tu_ngay: ngayVietNam(tu), den_ngay: ngayVietNam(den) }
}

export function taoBanNhap(baiTap) {
  return baiTap.map((b) => ({
    id: b.id,
    hiep_tap: b.hiep_tap.map((h) => ({
      so_lan_lap: h.so_lan_lap,
      khoi_luong_kg: h.khoi_luong_kg ?? '',
      nghi_giay: h.nghi_giay,
    })),
  }))
}

export function noiDungNhatKy(banNhap, ghiChu) {
  return {
    ghi_chu: ghiChu.trim() || null,
    bai_tap: banNhap.map((b) => ({
      id: b.id,
      hiep_tap: b.hiep_tap.map((h) => ({
        so_lan_lap: h.so_lan_lap === '' ? null : Number(h.so_lan_lap),
        khoi_luong_kg:
          h.khoi_luong_kg === '' || h.khoi_luong_kg === null ? null : Number(h.khoi_luong_kg),
        nghi_giay: h.nghi_giay === '' ? null : Number(h.nghi_giay),
      })),
    })),
  }
}
