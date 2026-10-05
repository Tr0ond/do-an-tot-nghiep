export function gopTin(cu, moi) {
  const ds = new Map(cu.map((t) => [t.id, t]))
  for (const t of moi) ds.set(t.id, t)
  return [...ds.values()].sort((a, b) => a.id - b.id)
}

// Theo after_id tới hết các trang: một lần reconnect có thể thiếu hơn 50 tin.
export async function taiTinMoi(tai, cursor, nhan) {
  do {
    const d = await tai(cursor)
    if (!d) return
    nhan(d)
    const max = Math.max(cursor, ...d.tin_nhan.map((t) => t.id))
    if (!d.con_tin || max === cursor) return
    cursor = max
  } while (true)
}

export function kiemTraTin(noiDung, anh) {
  if (!noiDung.trim() && !anh.length)
    throw new Error('Nhập tin nhắn hoặc chọn ảnh.')
  if (noiDung.length > 4000) throw new Error('Tin nhắn tối đa 4.000 ký tự.')
  if (anh.length > 4) throw new Error('Mỗi tin được gửi tối đa 4 ảnh.')
  for (const a of anh) {
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(a.mime))
      throw new Error('Chọn ảnh JPG, PNG hoặc WebP.')
    if (!(a.size > 0 && a.size <= 5 * 1024 * 1024))
      throw new Error('Mỗi ảnh tối đa 5 MB; không đọc được ảnh này.')
    if (!(a.width > 0 && a.height > 0 && a.width <= 8000 && a.height <= 8000))
      throw new Error('Ảnh tối đa 8.000 × 8.000 pixel.')
  }
}

export function taoTinCho(uuid) {
  let cho = null
  return {
    lay(noiDung, anh) {
      kiemTraTin(noiDung, anh)
      if (
        cho &&
        JSON.stringify([cho.noi_dung, cho.anh]) !==
          JSON.stringify([noiDung, anh])
      )
        throw new Error(
          'Chưa rõ kết quả gửi. Hãy thử lại tin đang chờ trước khi sửa nội dung.',
        )
      if (!cho)
        cho = {
          client_message_id: uuid(),
          noi_dung: noiDung,
          anh: anh.map((a) => ({ ...a })),
        }
      return cho
    },
    xong() {
      cho = null
    },
  }
}

export function anhDataUri({ mime, bytes }) {
  const bang =
    'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/'
  const doan = []
  let s = ''
  for (let i = 0; i < bytes.length; i += 3) {
    const n =
      (bytes[i] << 16) | ((bytes[i + 1] || 0) << 8) | (bytes[i + 2] || 0)
    s +=
      bang[(n >>> 18) & 63] +
      bang[(n >>> 12) & 63] +
      (i + 1 < bytes.length ? bang[(n >>> 6) & 63] : '=') +
      (i + 2 < bytes.length ? bang[n & 63] : '=')
    if (s.length >= 8192) {
      doan.push(s)
      s = ''
    }
  }
  doan.push(s)
  return `data:${mime};base64,${doan.join('')}`
}

export function dichThongBao(duongDan, vaiTro) {
  if (typeof duongDan !== 'string') return null
  const khuVuc =
    vaiTro === 'KHACH_HANG'
      ? 'khach-hang'
      : vaiTro === 'HUAN_LUYEN_VIEN'
        ? 'pt'
        : null
  if (!khuVuc) return null
  if (duongDan === `/${khuVuc}/ho-so`) return { name: 'CaNhan' }
  const m = duongDan.match(
    new RegExp(
      `^/${khuVuc}/(lich-hen|ke-hoach|lich-tap${vaiTro === 'KHACH_HANG' ? '|don-hang' : ''})/([1-9][0-9]*)$`,
    ),
  )
  if (m && Number.isSafeInteger(Number(m[2])))
    return {
      name: {
        'lich-hen': 'ChiTietLich',
        'ke-hoach': 'ChiTietGiaoAn',
        'lich-tap': 'BuoiTuTap',
        'don-hang': 'ChiTietDon',
      }[m[1]],
      params: { id: Number(m[2]) },
    }
  if (vaiTro !== 'HUAN_LUYEN_VIEN') return null
  if (duongDan === '/pt/hoc-vien') return { name: 'HocVien' }
  const h = duongDan.match(/^\/pt\/hoc-vien\/([1-9][0-9]*)\/ke-hoach$/)
  return h && Number.isSafeInteger(Number(h[1]))
    ? { name: 'GiaoAnHocVien', params: { khachId: Number(h[1]) } }
    : null
}
