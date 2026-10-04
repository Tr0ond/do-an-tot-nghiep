export function ngayVietNam(luc = new Date()) {
  const phan = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Ho_Chi_Minh',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(luc)
  const lay = (ten) => phan.find((p) => p.type === ten).value
  return `${lay('year')}-${lay('month')}-${lay('day')}`
}
export function luiNgay(ngay, soNgay) {
  const luc = new Date(`${ngay}T00:00:00Z`)
  luc.setUTCDate(luc.getUTCDate() - soNgay)
  return luc.toISOString().slice(0, 10)
}
export function duongBieuDo(cacMoc, cot = 'thuc_thu', mien = null) {
  const so = cacMoc.map((m) => Number(m[cot] ?? 0))
  const duoi = mien?.duoi ?? Math.min(0, ...so),
    tren = mien?.tren ?? Math.max(0, ...so)
  const khoang = tren - duoi || 1
  const y = (giaTri) => 180 - ((giaTri - duoi) / khoang) * 160
  const diem = so.map((giaTri, i) => ({
    x: so.length === 1 ? 360 : 40 + (i * 640) / Math.max(1, so.length - 1),
    y: y(giaTri),
  }))
  return { diem, points: diem.map((p) => `${p.x},${p.y}`).join(' '), zero: y(0), duoi, tren }
}
