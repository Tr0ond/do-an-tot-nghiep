import { layApiUrl } from '../config/moiTruong'

const yeuCauDangChay = new Set()

export function huyYeuCau() {
  for (const controller of yeuCauDangChay) controller.abort()
  yeuCauDangChay.clear()
}

export async function goiApi(
  duongDan,
  {
    method = 'GET',
    duLieu,
    formData,
    token,
    signal,
    dayDu = false,
    raw = false,
    anh = false,
    timeout = 15000,
  } = {},
) {
  const baseUrl = layApiUrl()
  const controller = new AbortController()
  const huyNgoai = () => controller.abort()
  signal?.addEventListener('abort', huyNgoai)
  if (signal?.aborted) controller.abort()
  yeuCauDangChay.add(controller)
  const han = setTimeout(() => controller.abort(), timeout)
  try {
    const response = await fetch(`${baseUrl}${duongDan}`, {
      method,
      signal: controller.signal,
      credentials: 'omit',
      headers: {
        Accept: anh ? 'image/jpeg,image/png,image/webp' : 'application/json',
        ...(duLieu && !formData ? { 'Content-Type': 'application/json' } : {}),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      ...(formData
        ? { body: formData }
        : duLieu
          ? { body: JSON.stringify(duLieu) }
          : {}),
    })
    if (anh && response.ok) {
      const mime = response.headers.get('Content-Type')?.split(';')[0]
      if (!['image/jpeg', 'image/png', 'image/webp'].includes(mime))
        throw new Error('Ảnh không đúng định dạng được hỗ trợ.')
      const bytes = new Uint8Array(await response.arrayBuffer())
      if (bytes.length > 5 * 1024 * 1024) throw new Error('Ảnh vượt quá 5 MB.')
      return { mime, bytes }
    }
    const data = await response.json().catch(() => null)
    if (!response.ok || (!raw && data?.status !== true)) {
      const loi = new Error(
        data?.message || 'Hệ thống chưa xử lý được yêu cầu. Vui lòng thử lại.',
      )
      loi.status = response.status
      loi.errors = data?.errors || {}
      loi.retryAfter = Number(response.headers.get('Retry-After') || 0)
      throw loi
    }
    return raw || dayDu ? data : data.data
  } catch (loi) {
    if (loi.status) throw loi
    throw new Error(
      loi.name === 'AbortError'
        ? 'Yêu cầu đã dừng hoặc quá thời gian chờ. Vui lòng thử lại.'
        : 'Không kết nối được hệ thống. Kiểm tra mạng và thử lại.',
    )
  } finally {
    clearTimeout(han)
    signal?.removeEventListener('abort', huyNgoai)
    yeuCauDangChay.delete(controller)
  }
}
