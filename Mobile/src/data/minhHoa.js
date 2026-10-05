export const KHACH_HANG = 'KHACH_HANG'
export const HUAN_LUYEN_VIEN = 'HUAN_LUYEN_VIEN'

export function taoHoSoMau(vaiTro) {
  if (vaiTro === HUAN_LUYEN_VIEN)
    return {
      ho_ten: 'Nguyễn Minh Quân',
      email: 'pt.minhhoa@example.test',
      vai_tro: vaiTro,
      chuyen_mon: 'Sức mạnh & thể lực',
      gioi_thieu:
        'Đồng hành cùng học viên xây dựng thói quen tập luyện, cải thiện kỹ thuật và theo dõi tiến độ từng buổi.',
    }
  return {
    ho_ten: 'Nguyễn Văn Minh',
    email: 'kh.minhhoa@example.test',
    vai_tro: KHACH_HANG,
    muc_tieu: 'Tăng sức mạnh và xây dựng thói quen',
    kinh_nghiem: 'Đã tập 3–6 tháng',
    gioi_tinh: 'NAM',
    ngay_sinh: '1998-05-15',
    thoi_gian_co_the_tap: ['Thứ 2, 4, 6 · 18:00–19:00'],
  }
}

export const hocVienMau = [
  {
    id: 'minh',
    ho_ten: 'Nguyễn Văn Minh',
    mo_ta: 'Đã ghi nhật ký buổi tập hôm qua',
    nhan: '5/12 buổi',
    mau: 'chinh',
  },
  {
    id: 'linh',
    ho_ten: 'Trần Thảo Linh',
    mo_ta: 'Chờ xác nhận lịch thứ Bảy',
    nhan: '2/8 buổi',
    mau: 'xanhDuong',
  },
  {
    id: 'bao',
    ho_ten: 'Lê Quốc Bảo',
    mo_ta: 'Gói còn 7 ngày',
    nhan: '10/12 buổi',
    mau: 'vang',
  },
]
export const ngayTrongTuan = [
  { thu: 'T2', trang_thai: 'Xong', icon: 'check', da_tap: true },
  { thu: 'T3', trang_thai: 'Nghỉ', icon: 'minus' },
  { thu: 'T4', trang_thai: 'Xong', icon: 'check', da_tap: true },
  { thu: 'T5', trang_thai: 'Hôm nay', icon: 'activity', hom_nay: true },
  { thu: 'T6', trang_thai: 'Nghỉ', icon: 'minus' },
  { thu: 'T7', trang_thai: 'Lịch', icon: 'circle' },
  { thu: 'CN', trang_thai: 'Nghỉ', icon: 'minus' },
]
