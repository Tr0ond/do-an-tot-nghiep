export function docBoLoc(query = {}) {
  const chuoi = (giaTri) => (typeof giaTri === 'string' ? giaTri : '')
  const trang = Number(chuoi(query.page))
  return {
    tu_khoa: chuoi(query.tu_khoa),
    nhom_co_id: chuoi(query.nhom_co_id),
    dung_cu_nguon: chuoi(query.dung_cu_nguon),
    page: Number.isInteger(trang) && trang > 0 && trang <= 100000 ? trang : 1,
  }
}

export function taoQueryBoLoc(boLoc, page = 1) {
  const query = {}
  for (const truong of ['tu_khoa', 'nhom_co_id', 'dung_cu_nguon']) {
    const giaTri = String(boLoc[truong] || '').trim()
    if (giaTri) query[truong] = giaTri
  }
  if (page > 1) query.page = String(page)
  return query
}

export function taoUrlMedia(duongDan, gocBackend) {
  if (!/^\/media\/bai-tap\/(images|animations)\/[a-zA-Z0-9_-]+\.(jpg|gif)$/.test(duongDan || '')) {
    return ''
  }
  return new URL(duongDan, gocBackend).href
}
