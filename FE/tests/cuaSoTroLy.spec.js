import { afterEach, expect, it, vi } from 'vitest'
import CuaSo from '../src/components/CuaSoTroLy.vue'
import GiaoAn from '../src/components/GiaoAnAi.vue'
vi.mock('../src/services/baiTapService', () => ({ default: { urlMedia: vi.fn() } }))
vi.mock('../src/services/xacThucService', () => ({ default: {} }))

it('thu gọn giữ nội dung hội thoại và trả focus về mascot', async () => {
  const focus = vi.fn()
  const p = {
    ...CuaSo.data(),
    mo: true,
    daMo: true,
    dungKeo: vi.fn(),
    huyKeoMascot: vi.fn(),
    giuTrongManHinh: vi.fn(),
    $nextTick: (f) => Promise.resolve().then(f),
    $refs: { nutMo: { focus }, khung: { yeuCauCho: { client_request_id: 'uuid' } } },
  }
  CuaSo.methods.dong.call(p)
  await Promise.resolve()
  expect(p.mo).toBe(false)
  expect(p.daMo).toBe(true)
  expect(p.$refs.khung.yeuCauCho.client_request_id).toBe('uuid')
  expect(focus).toHaveBeenCalledOnce()
})
it('đổi tài khoản dọn hội thoại giao diện; KH và khách vãng lai có widget, PT/Admin không có', () => {
  const p = {
    ...CuaSo.data(),
    mo: true,
    daMo: true,
    phongTo: true,
    dungKeo: vi.fn(),
    huyKeoMascot: vi.fn(),
  }
  CuaSo.watch['xacThuc.taiKhoan.id'].call(p)
  expect(p).toMatchObject({ mo: false, daMo: false, phongTo: false })
  for (const vaiTro of [null, 'KHACH_HANG', 'HUAN_LUYEN_VIEN', 'ADMIN']) {
    const ctx = {
      xacThuc: { daKhoiTao: true, taiKhoan: vaiTro ? { vai_tro: vaiTro } : null },
      laKhachHang: vaiTro === 'KHACH_HANG',
    }
    expect(CuaSo.computed.duocHienThi.call(ctx)).toBe(vaiTro === null || vaiTro === 'KHACH_HANG')
  }
})

afterEach(() => vi.unstubAllGlobals())
function cuaSoKeo(them = {}) {
  vi.stubGlobal('document', { documentElement: { clientWidth: 1440 } })
  vi.stubGlobal('window', {
    innerWidth: 1440,
    innerHeight: 900,
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    visualViewport: {
      width: 1440,
      height: 900,
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
    },
  })
  const p = {
    ...CuaSo.data(),
    mo: true,
    $refs: {
      cuaSo: { getBoundingClientRect: () => ({ left: 900, top: 120, width: 440, height: 580 }) },
    },
    $nextTick: (f) => Promise.resolve().then(f),
    ...them,
  }
  for (const [k, f] of Object.entries(CuaSo.methods)) p[k] = f.bind(p)
  return p
}
function conTro(nut = null) {
  const phanTu = {
    setPointerCapture: vi.fn(),
    hasPointerCapture: () => true,
    releasePointerCapture: vi.fn(),
  }
  return {
    e: {
      isPrimary: true,
      button: 0,
      pointerId: 7,
      clientX: 990,
      clientY: 200,
      target: { closest: () => nut },
      currentTarget: phanTu,
    },
    phanTu,
  }
}
it('kéo theo vị trí con trỏ, giữ capture khi rời header và chặn ra ngoài màn hình', () => {
  const p = cuaSoKeo()
  const { e, phanTu } = conTro()
  p.batDauKeo(e)
  expect(phanTu.setPointerCapture).toHaveBeenCalledWith(7)
  p.keoCuaSo({ pointerId: 8, clientX: 100, clientY: 100 })
  expect(p.viTri).toBeNull()
  p.keoCuaSo({ pointerId: 7, clientX: 650, clientY: 150 })
  expect(p.viTri).toEqual({ x: 560, y: 70 })
  p.keoCuaSo({ pointerId: 7, clientX: -10000, clientY: -10000 })
  expect(p.viTri).toEqual({ x: 12, y: 12 })
  p.keoCuaSo({ pointerId: 7, clientX: 10000, clientY: 10000 })
  expect(p.viTri).toEqual({ x: 988, y: 308 })
  p.dungKeo({ pointerId: 7 })
  expect(p.keo).toBeNull()
  expect(phanTu.releasePointerCapture).toHaveBeenCalledWith(7)
})
it('nút header, chuột phải và con trỏ phụ không khởi động kéo; click tiêu đề vẫn mở điều khiển', () => {
  const p = cuaSoKeo()
  const { e } = conTro({ classList: { contains: () => false } })
  p.batDauKeo(e)
  p.batDauKeo({ ...conTro().e, button: 2 })
  p.batDauKeo({ ...conTro().e, isPrimary: false })
  expect(p.keo).toBeNull()
  const t = conTro()
  p.batDauKeo(t.e)
  p.keoCuaSo({ pointerId: 7, clientX: 991, clientY: 201 })
  p.dungKeo({ pointerId: 7 })
  p.batTatDiChuyen()
  expect(p.viTri).toBeNull()
  expect(p.bangDiChuyen).toBe(true)
  p.batDauKeo(t.e)
  p.keoCuaSo({ pointerId: 7, clientX: 850, clientY: 200 })
  p.dungKeo({ pointerId: 7 })
  p.batTatDiChuyen()
  expect(p.bangDiChuyen).toBe(false)
})
it('di chuyển bằng nút/phím, resize và phóng to giữ cửa sổ trong màn hình; reset về góc', async () => {
  const p = cuaSoKeo()
  p.dichChuyen(-40, 20)
  expect(p.viTri).toEqual({ x: 860, y: 140 })
  p.phongTo = true
  await CuaSo.watch.phongTo.call(p)
  await Promise.resolve()
  expect(p.viTri.x).toBe(668)
  document.documentElement.clientWidth = 375
  window.innerWidth = window.visualViewport.width = 390
  window.innerHeight = window.visualViewport.height = 844
  p.giuTrongManHinh()
  expect(p.viTri.x).toBe(12)
  expect(CuaSo.computed.kieuViTri.call(p).width).toBe('351px')
  p.veGoc()
  expect(p.viTri).toBeNull()
  expect(CuaSo.computed.kieuViTri.call(p)).toBeUndefined()
})
it('thu gọn giữ vị trí, cancel/unmount giải phóng capture và listener', () => {
  const p = cuaSoKeo({ viTri: { x: 100, y: 100 }, daMo: true })
  CuaSo.mounted.call(p)
  const { e, phanTu } = conTro()
  p.batDauKeo(e)
  p.dungKeo({ pointerId: 8 })
  expect(p.keo).not.toBeNull()
  p.dong()
  expect(p.viTri).toEqual({ x: 100, y: 100 })
  expect(p.daMo).toBe(true)
  expect(phanTu.releasePointerCapture).toHaveBeenCalledOnce()
  CuaSo.beforeUnmount.call(p)
  expect(window.removeEventListener).toHaveBeenCalledWith('resize', p.capNhatKichThuoc)
  expect(window.visualViewport.removeEventListener).toHaveBeenCalledWith(
    'resize',
    p.capNhatKichThuoc,
  )
})
it('giáo án 12 buổi hiện đúng 4 tuần và 3 buổi mỗi tuần', () => {
  const buoi = Array.from({ length: 12 }, (_, i) => ({
    tuan: Math.floor(i / 3) + 1,
    buoi: (i % 3) + 1,
    bai_tap: [],
  }))
  const cacTuan = GiaoAn.computed.theoTuan.call({ giaoAn: { buoi_tap: buoi } })
  expect(Object.keys(cacTuan)).toEqual(['1', '2', '3', '4'])
  expect(Object.values(cacTuan).map((t) => t.length)).toEqual([3, 3, 3, 3])
})

function mascotKeo() {
  const p = cuaSoKeo({ mo: false })
  p.$refs.nutMo = {
    getBoundingClientRect: () => {
      const { x: left, y: top } = p.viTriMascot ?? { x: 1290, y: 740 }
      const height = p.mo ? 52 : 124
      return { left, top, right: left + 120, width: 120, height }
    },
    focus: vi.fn(),
  }
  return p
}
it('kéo mascot không mở chat; click sau đó vẫn mở, không đổi vị trí cửa sổ đã kéo riêng', async () => {
  const p = mascotKeo()
  p.viTri = { x: 100, y: 90 }
  const { e, phanTu } = conTro()
  p.batDauKeoMascot(e)
  p.keoConMascot({ pointerId: 8, clientX: 200, clientY: 100 })
  expect(p.viTriMascot).toBeNull()
  p.keoConMascot({ pointerId: 7, clientX: 290, clientY: 80 })
  p.dungKeoMascot({ pointerId: 7 })
  p.doiCuaSo()
  expect(p.mo).toBe(false)
  expect(p.viTriMascot).toEqual({ x: 590, y: 620 })
  expect(p.viTri).toEqual({ x: 100, y: 90 })
  expect(phanTu.releasePointerCapture).toHaveBeenCalledWith(7)
  p.doiCuaSo()
  await Promise.resolve()
  expect(p.mo).toBe(true)
  expect(p.viTri).toEqual({ x: 100, y: 90 })
})
it('click nhỏ vẫn mở chat gần mascot và đóng/mở giữ vị trí mascot', async () => {
  const p = mascotKeo()
  p.datViTriMascot(500, 600)
  const { e } = conTro()
  p.batDauKeoMascot(e)
  p.keoConMascot({ pointerId: 7, clientX: e.clientX + 1, clientY: e.clientY + 1 })
  p.dungKeoMascot({ pointerId: 7 })
  p.doiCuaSo()
  await Promise.resolve()
  expect(p.mo).toBe(true)
  expect(p.viTri).toEqual({ x: 180, y: 12 })
  p.dong()
  await Promise.resolve()
  expect(p.viTriMascot).toEqual({ x: 500, y: 600 })
})
it('mascot chặn bốn mép, resize sau thu gọn; nút/phím và reset độc lập với cửa sổ', async () => {
  const p = mascotKeo()
  p.datViTriMascot(-100, -100)
  expect(p.viTriMascot).toEqual({ x: 12, y: 12 })
  p.datViTriMascot(10000, 10000)
  expect(p.viTriMascot).toEqual({ x: 1308, y: 764 })
  p.dichChuyenMascot(-40, -20)
  expect(p.viTriMascot).toEqual({ x: 1268, y: 744 })
  document.documentElement.clientWidth = 375
  window.innerHeight = window.visualViewport.height = 400
  p.giuTrongManHinh()
  expect(p.viTriMascot).toEqual({ x: 243, y: 264 })
  p.mo = true
  p.datViTriMascot(10000, 10000)
  expect(p.viTriMascot.y).toBe(336)
  p.dong()
  await Promise.resolve()
  expect(p.viTriMascot.y).toBe(264)
  p.viTri = { x: 40, y: 30 }
  p.bangDiChuyenMascot = true
  const style = CuaSo.computed.kieuBangMascot.call(p)
  expect(Number.parseFloat(style.left)).toBeLessThanOrEqual(99)
  expect(Number.parseFloat(style.top)).toBeLessThanOrEqual(280)
  p.veGocMascot()
  expect(p.viTriMascot).toBeNull()
  expect(p.viTri).toEqual({ x: 40, y: 30 })
  expect(CuaSo.computed.kieuViTriMascot.call(p)).toBeUndefined()
})
it('cancel, đổi tài khoản và unmount dọn kéo mascot, chuột phải/con trỏ phụ không kéo', () => {
  const p = mascotKeo()
  const { e, phanTu } = conTro()
  p.batDauKeoMascot({ ...e, button: 2 })
  p.batDauKeoMascot({ ...e, isPrimary: false })
  expect(p.keoMascot).toBeNull()
  p.batDauKeoMascot(e)
  p.keoConMascot({ pointerId: 7, clientX: 600, clientY: 100 })
  p.huyKeoMascot({ pointerId: 8 })
  expect(p.keoMascot).not.toBeNull()
  p.huyKeoMascot({ pointerId: 7 })
  expect(p.vuaKeoMascot).toBe(false)
  expect(phanTu.releasePointerCapture).toHaveBeenCalledOnce()
  p.batDauKeoMascot(e)
  CuaSo.beforeUnmount.call(p)
  expect(p.keoMascot).toBeNull()
  CuaSo.watch['xacThuc.taiKhoan.id'].call(p)
  expect(p.viTriMascot).toBeNull()
})
it('resize đo lại sau cập nhật bố cục, kể cả khi thanh cuộn xuất hiện sau sự kiện', async () => {
  const p = mascotKeo()
  p.datViTriMascot(1300, 740)
  p.capNhatKichThuoc()
  document.documentElement.clientWidth = 375
  await Promise.resolve()
  expect(p.viTriMascot.x).toBe(243)
})
