export function hopNhatTin(...cacDanhSach) {
  const cacTin = new Map()
  for (const tin of cacDanhSach.flat()) {
    const khoa = `${tin.hoi_thoai_id}:${tin.nguoi_gui_id}:${tin.client_message_id}`
    const cu = cacTin.get(khoa)
    if (!cu?.id || tin.id) {
      const hopNhat = { ...cu, ...tin }
      if (hopNhat.id) {
        delete hopNhat.trang_thai
        delete hopNhat.loi
        delete hopNhat.tep_anh
      }
      cacTin.set(khoa, hopNhat)
    }
  }
  return [...cacTin.values()].sort((a, b) => {
    if (a.id && b.id) return a.id - b.id
    if (a.id) return -1
    if (b.id) return 1
    return String(a.created_at).localeCompare(String(b.created_at))
  })
}

export function kiemTraAnhChat(cacTep, soHienTai = 0) {
  if (cacTep.length + soHienTai > 4) return 'Mỗi tin nhắn tối đa 4 ảnh.'
  for (const tep of cacTep) {
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(tep.type))
      return 'Chỉ nhận ảnh JPG, PNG hoặc WebP.'
    if (tep.size > 5 * 1024 * 1024) return 'Mỗi ảnh tối đa 5 MB.'
    if (!tep.size) return 'Ảnh không có dữ liệu.'
  }
  return ''
}

export function dinhDangGioChat(luc) {
  return new Intl.DateTimeFormat('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
    timeZone: 'Asia/Ho_Chi_Minh',
  }).format(new Date(luc))
}
