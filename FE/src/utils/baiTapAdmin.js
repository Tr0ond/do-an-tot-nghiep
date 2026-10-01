import { docBoLoc, taoQueryBoLoc } from './baiTap'

export function docBoLocAdmin(query = {}) {
  const boLoc = docBoLoc(query)
  return {
    tu_khoa: boLoc.tu_khoa,
    nhom_co_id: boLoc.nhom_co_id,
    page: boLoc.page,
    trang_thai: ['HOAT_DONG', 'NGUNG_SU_DUNG'].includes(query.trang_thai) ? query.trang_thai : '',
  }
}

export function taoQueryAdmin(boLoc, page = 1) {
  return {
    ...taoQueryBoLoc({ tu_khoa: boLoc.tu_khoa, nhom_co_id: boLoc.nhom_co_id }, page),
    ...(boLoc.trang_thai ? { trang_thai: boLoc.trang_thai } : {}),
  }
}

export function taoBieuMauBaiTap(baiTap = {}) {
  return {
    ma_nguon: baiTap.ma_nguon || '',
    ten_bai_tap: baiTap.ten_bai_tap || '',
    ten_tieng_viet: baiTap.ten_tieng_viet || '',
    nhom_co_id: String(baiTap.nhom_co_id || ''),
    dung_cu: baiTap.dung_cu || '',
    huong_dan_vi: baiTap.huong_dan_vi || '',
    cac_buoc_vi: (baiTap.cac_buoc_vi || []).join('\n'),
    trang_thai: baiTap.trang_thai || 'HOAT_DONG',
  }
}

export function taoPayloadBaiTap(bieuMau, baiTap = null) {
  const duLieu = {
    ten_tieng_viet: bieuMau.ten_tieng_viet.trim() || null,
    nhom_co_id: bieuMau.nhom_co_id,
    dung_cu: bieuMau.dung_cu.trim() || null,
    huong_dan_vi: bieuMau.huong_dan_vi.trim() || null,
    cac_buoc_vi: bieuMau.cac_buoc_vi
      .split('\n')
      .map((buoc) => buoc.trim())
      .filter(Boolean),
  }
  if (!baiTap || baiTap.nguon_du_lieu === 'admin') duLieu.ten_bai_tap = bieuMau.ten_bai_tap.trim()
  if (baiTap) duLieu.updated_at = baiTap.updated_at
  else {
    duLieu.ma_nguon = bieuMau.ma_nguon.trim().toUpperCase()
    duLieu.trang_thai = bieuMau.trang_thai
  }
  return duLieu
}
