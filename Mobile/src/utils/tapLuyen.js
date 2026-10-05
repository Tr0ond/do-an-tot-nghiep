export const nhanTrangThai = (s) =>
  ({
    NHAP: 'Bản nháp',
    CHO_DUYET: 'Chờ xác nhận',
    QUA_HAN: 'Quá hạn',
    DANG_AP_DUNG: 'Đang áp dụng',
    LUU_TRU: 'Lưu trữ',
    DA_HUY: 'Đã hủy',
    DA_LEN_LICH: 'Đã lên lịch',
    DANG_TAP: 'Đang tập',
    HOAN_THANH: 'Hoàn thành',
  })[s] || s

export function soNhap(giaTri, min, max, nguyen = false, boTrong = false) {
  const chu = String(giaTri ?? '')
    .trim()
    .replace(',', '.')
  if (boTrong && chu === '') return null
  if (!(nguyen ? /^\d+$/ : /^\d+(\.\d{1,2})?$/).test(chu))
    throw new Error(
      nguyen ? 'Nhập số nguyên.' : 'Nhập số với tối đa 2 chữ số thập phân.',
    )
  const so = Number(chu)
  if (so < min || so > max)
    throw new Error(`Nhập giá trị từ ${min} đến ${max}.`)
  return so
}

export function noiDungKeHoach(d) {
  if (!d.ten_ke_hoach.trim()) throw new Error('Nhập tên giáo án.')
  const soNgay = soNhap(d.so_ngay_tap, 1, 30, true)
  const thuTu = {}
  return {
    ten_ke_hoach: d.ten_ke_hoach.trim(),
    muc_tieu: d.muc_tieu.trim() || null,
    so_ngay_tap: soNgay,
    giao_an_mau_id: d.giao_an_mau_id ?? null,
    bai_tap: d.bai_tap.map((b, i) => {
      try {
        const ngay = soNhap(b.ngay_thu, 1, soNgay, true)
        thuTu[ngay] = (thuTu[ngay] || 0) + 1
        return {
          bai_tap_id: b.bai_tap_id,
          ngay_thu: ngay,
          thu_tu: thuTu[ngay],
          so_hiep: soNhap(b.so_hiep, 1, 100, true),
          so_lan_lap: soNhap(b.so_lan_lap, 1, 1000, true),
          nghi_giay: soNhap(b.nghi_giay, 0, 3600, true),
          muc_ta_kg: soNhap(b.muc_ta_kg, 0, 1000, false, true),
          ghi_chu: b.ghi_chu?.trim() || null,
        }
      } catch (e) {
        throw new Error(`Bài ${i + 1}: ${e.message}`)
      }
    }),
  }
}

export function noiDungBuoiTap(d, updated_at) {
  return {
    updated_at,
    ghi_chu: d.ghi_chu?.trim() || null,
    bai_tap: d.bai_tap.map((b, i) => ({
      id: b.id,
      hiep_tap: b.hiep_tap.map((h, j) => {
        try {
          return {
            so_lan_lap: soNhap(h.so_lan_lap, 1, 1000, true),
            khoi_luong_kg: soNhap(h.khoi_luong_kg, 0, 1000, false, true),
            nghi_giay: soNhap(h.nghi_giay, 0, 3600, true),
          }
        } catch (e) {
          throw new Error(`Bài ${i + 1}, hiệp ${j + 1}: ${e.message}`)
        }
      }),
    })),
  }
}

// Một ý định gửi giữ nguyên UUID và nội dung khi chưa biết máy chủ đã nhận hay chưa.
export function taoYeuCauGhi(taoMa) {
  let cho = null
  return {
    coCho: () => cho !== null,
    lay: (d) => {
      if (cho && JSON.stringify(d) !== JSON.stringify(cho.noiDung))
        throw new Error(
          'Yêu cầu trước chưa rõ kết quả. Hãy thử lại nội dung đã gửi trước khi thay đổi.',
        )
      if (!cho) cho = { noiDung: JSON.parse(JSON.stringify(d)), ma: taoMa() }
      return { ...cho.noiDung, client_request_id: cho.ma }
    },
    xong: () => {
      cho = null
    },
  }
}
