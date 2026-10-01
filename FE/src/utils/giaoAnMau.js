export const nhanTrangThai = {
  NHAP: 'Bản nháp',
  DA_DUYET: 'Đã duyệt',
  NGUNG_SU_DUNG: 'Ngừng sử dụng',
}

export function danhSoThuTu(cacBai) {
  const thuTu = {}
  return [...cacBai]
    .sort((a, b) => Number(a.ngay_thu) - Number(b.ngay_thu))
    .map((bai) => ({
      ...bai,
      thu_tu: (thuTu[bai.ngay_thu] = (thuTu[bai.ngay_thu] || 0) + 1),
    }))
}

export function doiThuTu(cacBai, khoa, huong) {
  const ketQua = cacBai.map((bai) => ({ ...bai }))
  const viTri = ketQua.findIndex((bai) => bai.khoa === khoa)
  if (viTri < 0) return ketQua
  const cacViTri = ketQua.flatMap((bai, index) =>
    Number(bai.ngay_thu) === Number(ketQua[viTri].ngay_thu) ? [index] : [],
  )
  const viTriDich = cacViTri[cacViTri.indexOf(viTri) + huong]
  if (viTriDich === undefined) return ketQua
  ;[ketQua[viTri], ketQua[viTriDich]] = [ketQua[viTriDich], ketQua[viTri]]
  return danhSoThuTu(ketQua)
}

export function taoNoiDung(bieuMau, cacBai) {
  return {
    ten_giao_an: bieuMau.ten_giao_an.trim(),
    muc_tieu: bieuMau.muc_tieu?.trim() || null,
    so_ngay_tap: Number(bieuMau.so_ngay_tap),
    bai_tap: danhSoThuTu(cacBai).map((bai) => ({
      bai_tap_id: Number(bai.bai_tap_id),
      ngay_thu: Number(bai.ngay_thu),
      thu_tu: bai.thu_tu,
      so_hiep: Number(bai.so_hiep),
      so_lan_lap: Number(bai.so_lan_lap),
      nghi_giay: Number(bai.nghi_giay),
      ghi_chu: bai.ghi_chu?.trim() || null,
    })),
  }
}
