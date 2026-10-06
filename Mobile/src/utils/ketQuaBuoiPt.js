export function banNhapKetQuaPt(ketQua) {
  return {
    ghi_chu: ketQua?.ghi_chu || '',
    nhan_xet: ketQua?.nhan_xet || '',
    bai_tap: (ketQua?.bai_tap || []).map((b) => ({
      ...b,
      hiep_tap: b.hiep_tap.map((h) =>
        Object.fromEntries(
          Object.entries(h).map(([k, v]) => [k, String(v ?? '')]),
        ),
      ),
    })),
  }
}

export function noiDungKetQuaPt(ban, updatedAt) {
  if (
    ban.bai_tap.length > 30 ||
    ban.ghi_chu.trim().length > 2000 ||
    ban.nhan_xet.trim().length > 2000
  )
    throw new Error('Tối đa 30 bài; ghi chú và nhận xét tối đa 2.000 ký tự.')
  const ids = new Set()
  return {
    updated_at: updatedAt,
    ghi_chu: ban.ghi_chu.trim() || null,
    nhan_xet: ban.nhan_xet.trim() || null,
    bai_tap: ban.bai_tap.map((b, i) => {
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
          const ta = String(h.khoi_luong_kg ?? '')
            .trim()
            .replace(',', '.')
          if (
            !/^\d+$/.test(lan) ||
            Number(lan) < 1 ||
            Number(lan) > 1000 ||
            !/^\d+$/.test(nghi) ||
            Number(nghi) > 3600 ||
            (ta !== '' && (!/^\d+(\.\d{1,2})?$/.test(ta) || Number(ta) > 1000))
          )
            throw new Error(
              `Bài ${i + 1}, hiệp ${j + 1}: số lần 1–1.000; nghỉ 0–3.600 giây; tạ 0–1.000 kg (tối đa 2 số lẻ) hoặc để trống.`,
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

export const duHiepDeChotPt = (ban) =>
  !!ban?.bai_tap.length && ban.bai_tap.every((b) => b.hiep_tap.length > 0)

// Giữ đúng ý định đã gửi khi mất phản hồi; không thay phiên bản/payload khi retry.
export function taoLanGuiKetQuaPt() {
  let dangGui = false
  let cho = null
  return {
    lay: () => cho,
    async gui(loai, taoDuLieu, guiApi) {
      if (dangGui) return null
      if (cho && cho.loai !== loai)
        throw new Error('Hãy thử lại thao tác đang chờ trước.')
      dangGui = true
      try {
        cho ||= { loai, duLieu: JSON.parse(JSON.stringify(taoDuLieu())) }
        const ketQua = await guiApi(cho.duLieu)
        if (ketQua) cho = null
        return ketQua
      } catch (e) {
        // 2xx sai envelope cũng có thể đã ghi: cần retry như lỗi mạng/5xx.
        if (e.status >= 400 && e.status < 500) cho = null
        throw e
      } finally {
        dangGui = false
      }
    },
  }
}
