export function tinhBmi(can, cao) {
  const kg = Number(can)
  const cm = Number(cao)
  return kg >= 10 && kg <= 500 && cm >= 50 && cm <= 250
    ? Math.round((kg / (cm / 100) ** 2 + Number.EPSILON) * 100) / 100
    : null
}

export function diemChiSo(cacMoc, cot) {
  const moc = cacMoc.filter((x) => x[cot] !== null && Number.isFinite(Number(x[cot])))
  if (!moc.length) return { diem: [], duong: '', min: 0, max: 0 }
  const giaTri = moc.map((x) => Number(x[cot]))
  const nho = Math.min(...giaTri)
  const lon = Math.max(...giaTri)
  const le = Math.max((lon - nho) * 0.15, cot === 'bmi' ? 0.5 : 1)
  const min = Math.max(0, nho - le)
  const max = lon + le
  const ngay = moc.map((x) => Date.parse(`${x.ngay_ghi}T00:00:00Z`))
  const dau = ngay[0]
  const cuoi = ngay[ngay.length - 1]
  const diem = moc.map((x, i) => ({
    ...x,
    x: cuoi === dau ? 320 : 60 + ((ngay[i] - dau) / (cuoi - dau)) * 550,
    y: 200 - ((Number(x[cot]) - min) / (max - min)) * 165,
    giaTri: Number(x[cot]),
  }))
  return { diem, duong: diem.map((x, i) => `${i ? 'L' : 'M'} ${x.x} ${x.y}`).join(' '), min, max }
}

export function noiDungChiSo(form, id) {
  return {
    ngay_ghi: form.ngay_ghi,
    can_nang_kg: form.can_nang_kg,
    chieu_cao_cm: form.chieu_cao_cm,
    ghi_chu: form.ghi_chu.trim() || null,
    ...(id ? { updated_at: form.updated_at } : {}),
  }
}
