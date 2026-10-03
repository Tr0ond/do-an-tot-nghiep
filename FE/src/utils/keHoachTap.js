import { danhSoThuTu } from './giaoAnMau'

export const nhanKeHoach = {
  NHAP: 'Bản nháp',
  CHO_DUYET: 'Chờ bạn xác nhận',
  DANG_AP_DUNG: 'Đang áp dụng',
  LUU_TRU: 'Đã lưu trữ',
  DA_HUY: 'Đã hủy',
  QUA_HAN: 'Quá hạn xác nhận',
}
export function thoiGianKeHoach(giaTri) {
  return giaTri
    ? new Intl.DateTimeFormat('vi-VN', {
        timeZone: 'Asia/Ho_Chi_Minh',
        dateStyle: 'short',
        timeStyle: 'short',
      }).format(new Date(giaTri))
    : '—'
}
export function noiDungKeHoach(form, baiTap) {
  return {
    ten_ke_hoach: form.ten_ke_hoach.trim(),
    muc_tieu: form.muc_tieu?.trim() || null,
    so_ngay_tap: Number(form.so_ngay_tap),
    giao_an_mau_id: form.giao_an_mau_id ? Number(form.giao_an_mau_id) : null,
    bai_tap: danhSoThuTu(baiTap).map((b) => ({
      bai_tap_id: Number(b.bai_tap_id),
      ngay_thu: Number(b.ngay_thu),
      thu_tu: b.thu_tu,
      so_hiep: Number(b.so_hiep),
      so_lan_lap: Number(b.so_lan_lap),
      nghi_giay: Number(b.nghi_giay),
      ghi_chu: b.ghi_chu?.trim() || null,
      muc_ta_kg: b.muc_ta_kg === '' || b.muc_ta_kg == null ? null : Number(b.muc_ta_kg),
    })),
  }
}
