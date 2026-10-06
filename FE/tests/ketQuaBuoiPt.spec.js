import { beforeEach, afterEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { createSSRApp, h } from 'vue'
import { renderToString } from '@vue/server-renderer'
import TrangKetQua from '../src/views/LichHen/KetQua/index.vue'
import { useXacThucStore } from '../src/stores/xacThuc'
import service from '../src/services/ketQuaBuoiPtService'
import baiTapService from '../src/services/baiTapService'
import { noiDungKetQuaPt } from '../src/utils/ketQuaBuoiPt'

vi.mock('../src/services/ketQuaBuoiPtService', () => ({
  default: { tai: vi.fn(), luu: vi.fn(), chot: vi.fn() },
}))
vi.mock('../src/services/baiTapService', () => ({
  default: { taiDanhSach: vi.fn(), urlMedia: (p) => p || '' },
}))
vi.mock('../src/services/xacThucService', () => ({ default: {} }))
vi.mock('../src/layouts/CaNhanLayout.vue', () => ({
  default: {
    render() {
      return h('main', this.$slots.default?.())
    },
  },
}))

const duLieu = (ghi = true) => ({
  lich: {
    id: 7,
    khach_hang: 'KH kiểm thử',
    pt: 'PT kiểm thử',
    bat_dau_luc: '2026-10-06T01:00:00Z',
    ket_thuc_luc: '2026-10-06T02:00:00Z',
  },
  ket_qua: {
    id: 1,
    updated_at: '2026-10-06 02:05:00.000001',
    chot_luc: null,
    ghi_chu: '',
    nhan_xet: '',
    bai_tap: [
      {
        bai_tap_id: 3,
        ten_bai_tap: 'Chống đẩy',
        hiep_tap: [{ so_lan_lap: 12, khoi_luong_kg: null, nghi_giay: 60 }],
      },
    ],
  },
  co_the_ghi: ghi,
  co_the_chot: ghi,
  ly_do_khoa: ghi ? null : 'Chỉ đọc',
})
function trang() {
  const p = {
    ...TrangKetQua.data(),
    $route: { params: { id: '7' }, fullPath: '/pt/lich-hen/7/ket-qua' },
  }
  for (const [ten, ham] of Object.entries(TrangKetQua.methods)) p[ten] = ham.bind(p)
  for (const [ten, ham] of Object.entries(TrangKetQua.computed))
    Object.defineProperty(p, ten, { get: () => ham.call(p) })
  return p
}
function cho() {
  let resolve, reject
  const promise = new Promise((a, b) => {
    resolve = a
    reject = b
  })
  return { promise, resolve, reject }
}
async function render(ghi) {
  const d = duLieu(ghi)
  const app = createSSRApp({
    ...TrangKetQua,
    data() {
      return { ...TrangKetQua.data(), duLieu: d, baiTap: d.ket_qua.bai_tap, banDaLuu: '' }
    },
  })
  app.config.globalProperties.$route = { params: { id: '7' } }
  app.component('RouterLink', {
    props: ['to'],
    render() {
      return h('a', { href: this.to }, this.$slots.default?.())
    },
  })
  return renderToString(app)
}
describe('Kết quả buổi tập PT C42', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    setActivePinia(createPinia())
    useXacThucStore().taiKhoan = { id: 2, vai_tro: 'HUAN_LUYEN_VIEN' }
    vi.stubGlobal('window', { confirm: vi.fn(() => true) })
  })
  afterEach(() => vi.unstubAllGlobals())
  it('không biến hiệp trống thành kết quả 0, giữ tạ null khác 0 và bỏ trường client không được nhận', () => {
    const b = duLieu().ket_qua.bai_tap
    expect(noiDungKetQuaPt(b, '', '', null).bai_tap[0]).toEqual({
      bai_tap_id: 3,
      hiep_tap: [{ so_lan_lap: 12, khoi_luong_kg: null, nghi_giay: 60 }],
    })
    b[0].hiep_tap[0].khoi_luong_kg = '0'
    expect(noiDungKetQuaPt(b, '', '', null).bai_tap[0].hiep_tap[0].khoi_luong_kg).toBe(0)
    for (const [k, v] of [
      ['so_lan_lap', ''],
      ['nghi_giay', ''],
      ['so_lan_lap', '1.5'],
      ['khoi_luong_kg', '-1'],
      ['khoi_luong_kg', '1.123'],
      ['nghi_giay', 'Infinity'],
    ]) {
      const ds = structuredClone(duLieu().ket_qua.bai_tap)
      ds[0].hiep_tap[0][k] = v
      expect(() => noiDungKetQuaPt(ds, '', '', null)).toThrow()
    }
  })
  it('KH render dữ liệu nhưng không có form ghi/chốt; PT có thao tác theo quyền server', async () => {
    useXacThucStore().taiKhoan = { id: 1, vai_tro: 'KHACH_HANG' }
    const html = await render(false)
    expect(html).toContain('Chống đẩy')
    expect(html).toContain('Chưa ghi')
    expect(html).not.toContain('<input')
    expect(html).not.toContain('<textarea')
    expect(html).not.toContain('Chọn bài tập')
    useXacThucStore().taiKhoan = { id: 2, vai_tro: 'HUAN_LUYEN_VIEN' }
    expect(await render(true)).toContain('Chọn bài tập')
  })
  it('double-submit một request; lỗi sau commit giữ payload cho retry và khóa sửa', async () => {
    const p = trang()
    p.nhanDuLieu(duLieu())
    p.ghiChu = 'Nháp cần lưu'
    const w = cho()
    service.luu
      .mockReturnValueOnce(w.promise)
      .mockResolvedValueOnce({ data: duLieu(), message: 'Đã lưu' })
    const a = p.luuNhap()
    await p.luuNhap()
    expect(service.luu).toHaveBeenCalledTimes(1)
    w.reject({ response: { status: 503 } })
    await a
    expect(p.khoaNhap).toBe(true)
    p.themBai({ id: 4 })
    expect(p.baiTap).toHaveLength(1)
    p.ghiChu = 'Không được đổi yêu cầu cũ'
    await p.luuNhap()
    expect(service.luu.mock.calls[1][1]).toEqual(service.luu.mock.calls[0][1])
    expect(p.noiDungCho).toBeNull()
  })
  it('409 giữ nội dung nhập, chặn ghi tiếp và tải lại phải xác nhận bỏ nháp', async () => {
    const p = trang()
    p.nhanDuLieu(duLieu())
    p.ghiChu = 'Nháp chưa lưu'
    service.luu.mockRejectedValue({ response: { status: 409, data: { message: 'Phiên bản cũ' } } })
    await p.luuNhap()
    expect(p.xungDot).toBe(true)
    expect(p.ghiChu).toBe('Nháp chưa lưu')
    window.confirm.mockReturnValue(false)
    p.taiLai()
    expect(service.tai).not.toHaveBeenCalled()
  })
  it('logout bỏ nháp và không nhận response đọc/ghi tới muộn', async () => {
    const p = trang()
    const w = cho()
    service.tai.mockReturnValueOnce(w.promise)
    const a = p.taiDuLieu()
    useXacThucStore().taiKhoan = null
    p.doiNguCanh()
    w.resolve({ data: duLieu() })
    await a
    expect(p.duLieu).toBeNull()
    expect(p.baiTap).toEqual([])
    useXacThucStore().taiKhoan = { id: 2, vai_tro: 'HUAN_LUYEN_VIEN' }
    p.nhanDuLieu(duLieu())
    p.ghiChu = 'Nháp riêng'
    const w2 = cho()
    service.luu.mockReturnValueOnce(w2.promise)
    const b = p.luuNhap()
    useXacThucStore().taiKhoan = null
    p.doiNguCanh()
    w2.resolve({ data: duLieu(), message: 'Không được hiện lại' })
    await b
    expect(p.duLieu).toBeNull()
    expect(p.ghiChu).toBe('')
    expect(p.thanhCong).toBe('')
  })
  it('chốt cần bản đã lưu, xác nhận và quyền server; retry không đổi phiên bản', async () => {
    const p = trang()
    p.nhanDuLieu(duLieu())
    p.ghiChu = 'Chưa lưu'
    p.deNghiChot()
    expect(p.choChot).toBe(false)
    p.nhanDuLieu(duLieu())
    p.duLieu.co_the_chot = false
    p.deNghiChot()
    expect(p.choChot).toBe(false)
    p.duLieu.co_the_chot = true
    p.deNghiChot()
    expect(p.choChot).toBe(true)
    const w = cho()
    service.chot.mockReturnValueOnce(w.promise).mockResolvedValueOnce({
      data: {
        ...duLieu(false),
        ket_qua: { ...duLieu().ket_qua, chot_luc: '2026-10-06T02:10:00Z' },
      },
      message: 'Đã chốt',
    })
    const a = p.chotKetQua()
    await p.chotKetQua()
    expect(service.chot).toHaveBeenCalledTimes(1)
    w.reject(new Error('network'))
    await a
    await p.chotKetQua()
    expect(service.chot.mock.calls[1][1]).toBe(service.chot.mock.calls[0][1])
    expect(p.coTheGhi).toBe(false)
  })
  it('catalog phân trang ở server, response tìm cũ không ghi đè; không thêm bài trùng', async () => {
    const p = trang()
    p.nhanDuLieu(duLieu())
    const w = cho()
    baiTapService.taiDanhSach
      .mockReturnValueOnce(w.promise)
      .mockResolvedValueOnce({ data: [{ id: 5 }], meta: { last_page: 2 } })
    const a = p.taiCatalog()
    p.tuKhoaLoc = 'lưng'
    await p.taiCatalog()
    w.resolve({ data: [{ id: 4 }], meta: {} })
    await a
    expect(p.catalog).toEqual([{ id: 5 }])
    expect(baiTapService.taiDanhSach.mock.calls[1][0]).toMatchObject({
      tu_khoa: 'lưng',
      per_page: 12,
    })
    p.themBai({ id: 3 })
    expect(p.baiTap).toHaveLength(1)
    p.themBai({ id: 5, ten_bai_tap: 'Bài mới' })
    p.themHiep(1)
    expect(p.baiTap[1].hiep_tap[0]).toEqual({ so_lan_lap: '', khoi_luong_kg: '', nghi_giay: '' })
  })
})
