// Giáo án/nhật ký dùng ảnh snapshot để không đổi lịch sử theo catalog hiện tại.
export function mediaBaiTap(bai = {}) {
  const duLieu = { ...(bai.noi_dung || {}), ...bai }
  return {
    anh_url: duLieu.anh_url,
    gif_url: duLieu.gif_url,
    ghi_cong_media: duLieu.ghi_cong_media,
  }
}

export function taoUrlMedia(duongDan, apiUrl) {
  if (!duongDan || !apiUrl) return null
  try {
    const goc = new URL(apiUrl).origin
    const url = new URL(duongDan, goc)
    if (
      url.origin !== goc ||
      url.username ||
      url.password ||
      !/^\/media\/bai-tap\/(images\/[A-Za-z0-9_-]+\.jpg|animations\/[A-Za-z0-9_-]+\.gif)$/.test(
        url.pathname,
      )
    )
      return null
    return url.href
  } catch {
    return null
  }
}
