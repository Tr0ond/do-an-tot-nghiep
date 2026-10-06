export function noiDungKetQuaPt(baiTap, ghiChu, nhanXet, updatedAt) {
  if (baiTap.length > 30 || ghiChu.trim().length > 2000 || nhanXet.trim().length > 2000)
    throw new Error('Tối đa 30 bài; ghi chú và nhận xét tối đa 2.000 ký tự.')
  const ids = new Set()
  return {
    updated_at: updatedAt,
    ghi_chu: ghiChu.trim() || null,
    nhan_xet: nhanXet.trim() || null,
    bai_tap: baiTap.map((b, i) => {
      const id = Number(b.bai_tap_id)
      if (!Number.isSafeInteger(id) || id < 1 || ids.has(id))
        throw new Error('Bài tập không hợp lệ hoặc bị trùng.')
      ids.add(id)
      if (b.hiep_tap.length > 20) throw new Error('Mỗi bài tối đa 20 hiệp.')
      return {
        bai_tap_id: id,
        hiep_tap: b.hiep_tap.map((h, j) => {
          const lan = String(h.so_lan_lap ?? '').trim()
          const nghi = String(h.nghi_giay ?? '').trim()
          const ta = String(h.khoi_luong_kg ?? '').trim()
          if (
            !/^\d+$/.test(lan) ||
            Number(lan) < 1 ||
            Number(lan) > 1000 ||
            !/^\d+$/.test(nghi) ||
            Number(nghi) > 3600 ||
            (ta !== '' && (!/^\d+(\.\d{1,2})?$/.test(ta) || Number(ta) > 1000))
          )
            throw new Error(
              `Bài ${i + 1}, hiệp ${j + 1}: nhập số lần 1–1.000, nghỉ 0–3.600 giây; tạ 0–1.000 kg hoặc để trống.`,
            )
          return {
            so_lan_lap: Number(lan),
            khoi_luong_kg: ta === '' ? null : Number(ta),
            nghi_giay: Number(nghi),
          }
        }),
      }
    }),
  }
}

export function chuKyNhapPt(baiTap, ghiChu, nhanXet) {
  return JSON.stringify({
    ghiChu,
    nhanXet,
    baiTap: baiTap.map((b) => ({
      id: b.bai_tap_id,
      hiep: b.hiep_tap.map((h) =>
        [h.so_lan_lap, h.khoi_luong_kg, h.nghi_giay].map((v) => String(v ?? '')),
      ),
    })),
  })
}
