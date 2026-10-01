export function layLoiApi(loi) {
  const maHttp = loi.response?.status
  return {
    thongBao:
      loi.response?.data?.message || 'Không thể kết nối. Vui lòng kiểm tra mạng và thử lại.',
    loiTruong: loi.response?.data?.errors || {},
    hetPhien: [401, 403, 419].includes(maHttp),
  }
}
