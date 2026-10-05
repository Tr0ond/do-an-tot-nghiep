export function chuyenHoSo(taiKhoan) {
  if (!taiKhoan) return null
  const khach = taiKhoan.ho_so_khach_hang || {}
  const pt = taiKhoan.ho_so_huan_luyen_vien || {}
  return {
    id: taiKhoan.id,
    ho_ten: taiKhoan.ho_ten,
    email: taiKhoan.email,
    vai_tro: taiKhoan.vai_tro,
    updated_at: taiKhoan.updated_at,
    muc_tieu: khach.muc_tieu || '',
    kinh_nghiem: khach.kinh_nghiem || '',
    gioi_tinh: khach.gioi_tinh || null,
    ngay_sinh: khach.ngay_sinh || '',
    thoi_gian_co_the_tap: khach.thoi_gian_co_the_tap || [],
    chuyen_mon: pt.chuyen_mon || '',
    gioi_thieu: pt.gioi_thieu || '',
  }
}
