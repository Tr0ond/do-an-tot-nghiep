export function dinhDangSo(giaTri) {
  if (giaTri === '' || giaTri === null || giaTri === undefined) return '—'
  const so = Number(giaTri)
  return Number.isSafeInteger(so) && so >= 0 ? new Intl.NumberFormat('vi-VN').format(so) : '—'
}

export function dinhDangGia(giaTri) {
  return dinhDangSo(giaTri) + ' ₫'
}

export function docBoLocGoiTap(query = {}, quanTri = false) {
  const trang = typeof query.page === 'string' && /^\d+$/.test(query.page) ? Number(query.page) : 1
  return {
    tu_khoa: typeof query.tu_khoa === 'string' ? query.tu_khoa.slice(0, 100) : '',
    loai_goi: ['CHATBOT', 'PT_CHATBOT'].includes(query.loai_goi) ? query.loai_goi : '',
    ...(quanTri
      ? {
          trang_thai: ['HOAT_DONG', 'NGUNG_SU_DUNG'].includes(query.trang_thai)
            ? query.trang_thai
            : '',
        }
      : {}),
    page: Number.isInteger(trang) && trang >= 1 && trang <= 100000 ? trang : 1,
  }
}

export function taoQueryGoiTap(boLoc, page = 1, quanTri = false) {
  return {
    ...(boLoc.tu_khoa.trim() ? { tu_khoa: boLoc.tu_khoa.trim() } : {}),
    ...(boLoc.loai_goi ? { loai_goi: boLoc.loai_goi } : {}),
    ...(quanTri && boLoc.trang_thai ? { trang_thai: boLoc.trang_thai } : {}),
    ...(page > 1 ? { page: String(page) } : {}),
  }
}

export function taoBieuMauGoiTap(goi = {}) {
  return {
    ten_goi: goi.ten_goi || '',
    gia: String(goi.gia ?? ''),
    loai_goi: goi.so_buoi_pt > 0 ? 'PT_CHATBOT' : 'CHATBOT',
    so_buoi_pt: String(goi.so_buoi_pt ?? 0),
    so_luot_chatbot_moi_ngay: String(goi.so_luot_chatbot_moi_ngay ?? ''),
    thoi_han_ngay: String(goi.thoi_han_ngay ?? ''),
    trang_thai: goi.trang_thai || 'NGUNG_SU_DUNG',
  }
}

export function taoPayloadGoiTap(bieuMau, goi, maYeuCau) {
  return {
    ten_goi: bieuMau.ten_goi.trim(),
    gia: bieuMau.gia,
    co_chatbot: true,
    so_buoi_pt: bieuMau.loai_goi === 'CHATBOT' ? 0 : bieuMau.so_buoi_pt,
    so_luot_chatbot_moi_ngay: bieuMau.so_luot_chatbot_moi_ngay,
    thoi_han_ngay: bieuMau.thoi_han_ngay,
    ...(goi
      ? { updated_at: goi.updated_at }
      : { trang_thai: bieuMau.trang_thai, client_request_id: maYeuCau }),
  }
}
