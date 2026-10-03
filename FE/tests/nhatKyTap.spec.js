import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createSSRApp, h } from 'vue'
import { renderToString } from '@vue/server-renderer'
import DanhSach from '../src/views/NhatKyTap/index.vue'
import ChiTiet from '../src/views/NhatKyTap/ChiTiet/index.vue'
import nhatKyTapService from '../src/services/nhatKyTapService'
import { ngayVietNam, noiDungNhatKy, taoBanNhap } from '../src/utils/nhatKyTap'

vi.mock('../src/services/nhatKyTapService', () => ({
  default: {
    taiDanhSach: vi.fn(),
    taiChiTiet: vi.fn(),
    tao: vi.fn(),
    luu: vi.fn(),
    thaoTac: vi.fn(),
  },
}))
vi.mock('../src/services/xacThucService', () => ({ default: {} }))
vi.mock('../src/services/baiTapService', () => ({
  default: { urlMedia: (p) => (p ? `http://localhost:8000${p}` : '') },
}))
function trang(component, them = {}) {
  const p = {
    ...component.data(),
    $route: { params: { id: '1' }, meta: { vaiTro: 'KHACH_HANG' } },
    $router: { push: vi.fn() },
    ...them,
  }
  for (const [ten, ham] of Object.entries(component.methods)) p[ten] = ham.bind(p)
  for (const [ten, ham] of Object.entries(component.computed || {}))
    Object.defineProperty(p, ten, { get: () => ham.call(p) })
  return p
}
const lich = () => ({
  id: 1,
  khach_hang_id: 2,
  ten_ke_hoach: 'Toàn thân',
  ngay_tap: '2026-10-03',
  ngay_thu: 1,
  nguon_tao: 'PT',
  trang_thai: 'DANG_TAP',
  updated_at: 'v1',
  co_the_ghi: true,
  co_the_huy: true,
  phien: { ghi_chu: null },
  nhan_xet: [],
  bai_tap: [
    {
      id: 4,
      bai_tap_id: 7,
      ten_bai_tap: 'Squat',
      noi_dung: {
        anh_url: '/media/squat.jpg',
        du_kien: { so_hiep: 3, so_lan_lap: 12, nghi_giay: 60 },
      },
      hiep_tap: [],
    },
  ],
})
const cho = () => {
  let resolve
  const promise = new Promise((r) => {
    resolve = r
  })
  return { resolve, promise }
}
describe('Lịch và nhật ký tập', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    vi.stubGlobal('window', { confirm: vi.fn(() => true) })
  })
  afterEach(() => vi.unstubAllGlobals())
  it('xác nhận ngay trong trang không gửi thao tác trước khi đồng ý', async () => {
    const focus = vi.fn()
    const p = trang(ChiTiet, {
      $nextTick: vi.fn().mockResolvedValue(),
      $refs: { xacNhan: { focus } },
    })
    p.nhanDuLieu(lich())
    await p.deNghiThaoTac('hoan-thanh')
    expect(p.hanhDongCho).toBe('hoan-thanh')
    expect(focus).toHaveBeenCalledOnce()
    expect(nhatKyTapService.luu).not.toHaveBeenCalled()
    expect(nhatKyTapService.thaoTac).not.toHaveBeenCalled()
    expect(window.confirm).not.toHaveBeenCalled()
    p.hanhDongCho = ''
    expect(nhatKyTapService.thaoTac).not.toHaveBeenCalled()
  })
  it('ngày lịch dùng giờ Việt Nam và không lấy chỉ tiêu làm kết quả', () => {
    expect(ngayVietNam(new Date('2026-10-02T18:00:00Z'))).toBe('2026-10-03')
    expect(taoBanNhap(lich().bai_tap)[0].hiep_tap).toEqual([])
    expect(
      noiDungNhatKy(
        [
          {
            id: 1,
            hiep_tap: [
              { so_lan_lap: '12', khoi_luong_kg: '', nghi_giay: 0 },
              { so_lan_lap: '', khoi_luong_kg: 0, nghi_giay: 0 },
            ],
          },
        ],
        ' ghi chú ',
      ),
    ).toEqual({
      ghi_chu: 'ghi chú',
      bai_tap: [
        {
          id: 1,
          hiep_tap: [
            { so_lan_lap: 12, khoi_luong_kg: null, nghi_giay: 0 },
            { so_lan_lap: null, khoi_luong_kg: 0, nghi_giay: 0 },
          ],
        },
      ],
    })
  })
  it('gửi thất bại giữ kết quả và không hoàn thành buổi', async () => {
    const p = trang(ChiTiet)
    p.nhanDuLieu(lich())
    p.themHiep(0)
    p.banNhap[0].hiep_tap[0].so_lan_lap = 12
    nhatKyTapService.luu.mockRejectedValue({
      response: { data: { message: 'Phiên bản cũ', errors: {} } },
    })
    await p.hoanThanh()
    expect(p.banNhap[0].hiep_tap[0].so_lan_lap).toBe(12)
    expect(p.loi).toBe('Phiên bản cũ')
    expect(nhatKyTapService.thaoTac).not.toHaveBeenCalled()
    expect(p.coThayDoi).toBe(true)
  })
  it('hoàn thành luôn lưu trước và dùng phiên bản mới', async () => {
    const p = trang(ChiTiet)
    p.nhanDuLieu(lich())
    nhatKyTapService.luu.mockResolvedValue({
      data: { ...lich(), updated_at: 'v2' },
      message: 'Đã lưu',
    })
    nhatKyTapService.thaoTac.mockResolvedValue({
      data: { ...lich(), updated_at: 'v3', co_the_ghi: false, trang_thai: 'HOAN_THANH' },
      message: 'Hoàn thành',
    })
    await p.hoanThanh()
    expect(nhatKyTapService.thaoTac).toHaveBeenCalledWith(1, false, 'hoan-thanh', {
      updated_at: 'v2',
    })
    expect(p.lich.co_the_ghi).toBe(false)
  })
  it('chặn gửi kép và cảnh báo khi rời trang có thay đổi', async () => {
    const p = trang(ChiTiet)
    p.nhanDuLieu(lich())
    p.themHiep(0)
    p.banNhap[0].hiep_tap[0].so_lan_lap = 10
    window.confirm.mockReturnValue(false)
    expect(p.choRoiTrang()).toBe(false)
    const d = cho()
    nhatKyTapService.luu.mockReturnValue(d.promise)
    const a = p.luuNhap()
    await p.luuNhap()
    expect(nhatKyTapService.luu).toHaveBeenCalledTimes(1)
    expect(p.choRoiTrang()).toBe(false)
    d.resolve({ data: lich(), message: 'Đã lưu' })
    await a
  })
  it('tải lại chỉ bỏ nháp khi đồng ý; phản hồi tải cũ không ghi đè', async () => {
    const p = trang(ChiTiet)
    p.nhanDuLieu(lich())
    p.themHiep(0)
    window.confirm.mockReturnValue(false)
    await p.taiLai()
    expect(nhatKyTapService.taiChiTiet).not.toHaveBeenCalled()
    const a = cho()
    const b = cho()
    nhatKyTapService.taiChiTiet.mockReturnValueOnce(a.promise).mockReturnValueOnce(b.promise)
    const first = p.taiChiTiet()
    const last = p.taiChiTiet()
    b.resolve({ data: { ...lich(), id: 2 } })
    await last
    a.resolve({ data: lich() })
    await first
    expect(p.lich.id).toBe(2)
  })
  it('lịch retry sau mất phản hồi giữ UUID, nội dung mới dùng UUID mới', async () => {
    const p = trang(DanhSach, {
      meta: { giao_an_dang_dung: { id: 3 } },
      lichMoi: { ngay_thu: 1, ngay_tap: '2026-10-03' },
    })
    nhatKyTapService.tao.mockRejectedValue(new Error('mất mạng'))
    await p.taoLich()
    await p.taoLich()
    expect(nhatKyTapService.tao.mock.calls[0][1].client_request_id).toBe(
      nhatKyTapService.tao.mock.calls[1][1].client_request_id,
    )
    p.lichMoi.ngay_tap = '2026-10-04'
    await p.taoLich()
    expect(nhatKyTapService.tao.mock.calls[2][1].client_request_id).not.toBe(
      nhatKyTapService.tao.mock.calls[0][1].client_request_id,
    )
  })
  it('PT retry nhận xét dùng UUID ổn định; KH không gửi nhận xét', async () => {
    const p = trang(ChiTiet, {
      $route: { params: { id: '1' }, meta: { vaiTro: 'HUAN_LUYEN_VIEN' } },
    })
    p.nhanDuLieu({ ...lich(), co_the_ghi: false, co_the_nhan_xet: true })
    p.nhanXet = ' Giữ tư thế '
    nhatKyTapService.thaoTac.mockRejectedValue(new Error('mất mạng'))
    await p.guiNhanXet()
    await p.guiNhanXet()
    expect(nhatKyTapService.thaoTac.mock.calls[0][3].client_request_id).toBe(
      nhatKyTapService.thaoTac.mock.calls[1][3].client_request_id,
    )
    expect(p.nhanXet).toBe(' Giữ tư thế ')
    p.lich.co_the_nhan_xet = false
    await p.guiNhanXet()
    expect(nhatKyTapService.thaoTac).toHaveBeenCalledTimes(2)
  })
  it.each(['KHACH_HANG', 'HUAN_LUYEN_VIEN'])(
    'kết quả hoàn thành %s chỉ đọc, media dùng origin BE',
    async (vaiTro) => {
      const c = {
        ...ChiTiet,
        components: {
          ...ChiTiet.components,
          CaNhanLayout: {
            render() {
              return h('main', this.$slots.default())
            },
          },
        },
        data() {
          return {
            ...ChiTiet.data(),
            lich: {
              ...lich(),
              co_the_ghi: false,
              co_the_huy: false,
              trang_thai: 'HOAN_THANH',
              co_the_nhan_xet: vaiTro === 'HUAN_LUYEN_VIEN',
            },
          }
        },
      }
      const app = createSSRApp(c)
      app.config.globalProperties.$route = { params: { id: '1' }, meta: { vaiTro } }
      app.component('RouterLink', {
        render() {
          return h('a', this.$slots.default())
        },
      })
      const html = await renderToString(app)
      expect(html).not.toContain('Thêm hiệp')
      expect(html).not.toContain('Lưu nháp')
      expect(html).toContain('http://localhost:8000/media/squat.jpg')
      expect(html.includes('Gửi nhận xét')).toBe(vaiTro === 'HUAN_LUYEN_VIEN')
    },
  )
})
