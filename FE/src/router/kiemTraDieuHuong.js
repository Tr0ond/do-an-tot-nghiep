// Chờ server xác nhận session trước khi chọn trang chủ, kể cả lúc mở URL trực tiếp.
export async function kiemTraDieuHuong(to, xacThuc) {
  const laTrangChu = to.path === '/'
  if (to.meta.vaiTro || to.meta.khach || laTrangChu) {
    try {
      await xacThuc.taiTaiKhoan()
    } catch {
      if (to.meta.vaiTro || laTrangChu) return '/khong-ket-noi'
      return true
    }
  }
  if (to.meta.vaiTro && !xacThuc.daDangNhap) return '/dang-nhap'
  if (to.meta.vaiTro && to.meta.vaiTro !== xacThuc.taiKhoan.vai_tro) return '/khong-co-quyen'
  if ((to.meta.khach || laTrangChu) && xacThuc.daDangNhap)
    return { path: xacThuc.duongDanCaNhan, replace: true }
}
