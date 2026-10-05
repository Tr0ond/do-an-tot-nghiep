export const trangThaiLich = {
  CHO_XAC_NHAN: 'Chờ xác nhận',
  DA_XAC_NHAN: 'Đã xác nhận',
  HOAN_THANH: 'Hoàn thành',
  VANG_MAT: 'Vắng mặt',
  DA_HUY: 'Đã hủy',
  HET_HAN: 'Hết hạn đặt lịch',
  QUA_HAN_XAC_NHAN: 'Quá hạn ghi kết quả',
}
export const hanhDongLich = {
  huy: {
    nhan: 'Hủy lịch hẹn',
    lyDo: true,
    moTa: 'Hủy lịch này và trả lại khung giờ. Không trừ buổi PT.',
  },
  'tu-choi': {
    nhan: 'Từ chối yêu cầu',
    lyDo: true,
    moTa: 'Từ chối yêu cầu và trả lại khung giờ. Không trừ buổi PT.',
  },
  'xac-nhan': {
    nhan: 'Xác nhận lịch hẹn',
    moTa: 'Xác nhận bạn sẽ huấn luyện học viên trong khung giờ này.',
  },
  'hoan-thanh': {
    nhan: 'Hoàn thành buổi tập',
    moTa: 'Ghi nhận buổi huấn luyện đã hoàn thành và trừ đúng 1 buổi PT trong gói gắn với lịch này.',
  },
  'vang-mat': {
    nhan: 'Đánh dấu vắng mặt',
    lyDo: true,
    moTa: 'Ghi nhận học viên không tham gia. Không trừ buổi PT, không thu phí phạt.',
  },
}
export function ngayHopLe(ngay) {
  return (
    /^\d{4}-\d{2}-\d{2}$/.test(ngay) &&
    Number.isFinite(Date.parse(`${ngay}T00:00:00Z`)) &&
    new Date(`${ngay}T00:00:00Z`).toISOString().slice(0, 10) === ngay
  )
}
export function homNay() {
  const p = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Ho_Chi_Minh',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(new Date())
  return ['year', 'month', 'day']
    .map((k) => p.find((x) => x.type === k).value)
    .join('-')
}
export function doiNgay(ngay, so) {
  const d = new Date(`${ngay}T00:00:00Z`)
  d.setUTCDate(d.getUTCDate() + so)
  return d.toISOString().slice(0, 10)
}
export function nhanNgay(ngay) {
  if (!ngayHopLe(ngay)) return '—'
  return new Intl.DateTimeFormat('vi-VN', {
    timeZone: 'UTC',
    weekday: 'short',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(new Date(`${ngay}T00:00:00Z`))
}
export function gioVietNam(luc) {
  if (!luc || !Number.isFinite(Date.parse(luc))) return '—'
  return new Intl.DateTimeFormat('vi-VN', {
    timeZone: 'Asia/Ho_Chi_Minh',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
  }).format(new Date(luc))
}
export function thoiDiem(luc) {
  if (!luc || !Number.isFinite(Date.parse(luc))) return '—'
  return new Intl.DateTimeFormat('vi-VN', {
    timeZone: 'Asia/Ho_Chi_Minh',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
  }).format(new Date(luc))
}
export function ngayThangVietNam(luc) {
  if (!luc || !Number.isFinite(Date.parse(luc))) return '—'
  const cacPhan = new Intl.DateTimeFormat('vi-VN', {
    timeZone: 'Asia/Ho_Chi_Minh',
    day: '2-digit',
    month: '2-digit',
  }).formatToParts(new Date(luc))
  return ['day', 'month']
    .map((ten) => cacPhan.find((phan) => phan.type === ten).value)
    .join('/')
}
export function gioMoKhung(ngay, gio) {
  if (!ngayHopLe(ngay) || !/^(?:[01]\d|2[0-3]):[0-5]\d$/.test(gio))
    throw new Error('Chọn ngày và nhập giờ hợp lệ, ví dụ 08:00.')
  return new Date(`${ngay}T${gio}:00+07:00`).toISOString()
}
export function taoYeuCauDatLich({ goi, taoMa }) {
  let payload = null
  let dangGui = false
  return {
    chon: (id) => {
      if (dangGui) return
      if (payload?.khung_gio_id !== id)
        payload = { khung_gio_id: id, client_request_id: taoMa() }
    },
    async gui() {
      if (!payload || dangGui) return null
      dangGui = true
      try {
        return await goi({ ...payload })
      } finally {
        dangGui = false
      }
    },
  }
}
