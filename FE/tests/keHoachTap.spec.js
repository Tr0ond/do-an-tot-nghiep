import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createSSRApp, h } from 'vue'
import { renderToString } from '@vue/server-renderer'
import { noiDungKeHoach } from '../src/utils/keHoachTap'
import BieuMau from '../src/views/PT/KeHoachTap/BieuMau/index.vue'
import ChiTiet from '../src/views/KeHoachTap/ChiTiet/index.vue'
import DanhSach from '../src/views/KeHoachTap/index.vue'
import keHoachTapService from '../src/services/keHoachTapService'

vi.mock('../src/services/keHoachTapService', () => ({
  default: {
    taiDanhSach: vi.fn(),
    taiChiTiet: vi.fn(),
    tao: vi.fn(),
    sua: vi.fn(),
    thaoTac: vi.fn(),
  },
}))
vi.mock('../src/services/giaoAnMauService', () => ({ default: {} }))
vi.mock('../src/services/xacThucService', () => ({ default: {} }))
vi.mock('../src/services/baiTapService', async () => {
  const { taoUrlMedia } = await import('../src/utils/baiTap')
  return { default: { urlMedia: (duongDan) => taoUrlMedia(duongDan, 'http://localhost:8000') } }
})
const cho = () => {
  let resolve
  const promise = new Promise((r) => {
    resolve = r
  })
  return { resolve, promise }
}
function trang(component, them = {}) {
  const p = {
    ...component.data(),
    $route: { params: {}, meta: { vaiTro: 'HUAN_LUYEN_VIEN' } },
    $router: { push: vi.fn() },
    ...them,
  }
  for (const [ten, ham] of Object.entries(component.methods)) p[ten] = ham.bind(p)
  for (const [ten, ham] of Object.entries(component.computed || {}))
    Object.defineProperty(p, ten, { get: () => ham.call(p) })
  return p
}
describe('Giáo án cá nhân', () => {
  afterEach(() => vi.unstubAllGlobals())
  beforeEach(() => {
    vi.resetAllMocks()
    vi.stubGlobal('window', { confirm: vi.fn(() => true) })
  })
  it.each(['HUAN_LUYEN_VIEN', 'KHACH_HANG'])(
    'ảnh/GIF snapshot dùng origin Backend ở trang chi tiết %s',
    async (vaiTro) => {
      const trangChiTiet = {
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
            keHoach: {
              id: 1,
              khach_hang_id: 2,
              ten_ke_hoach: 'Kiểm tra ảnh',
              trang_thai_hien_thi: 'NHAP',
              so_ngay_tap: 1,
              so_bai_tap: 1,
              bai_tap: [
                {
                  id: 1,
                  ngay_thu: 1,
                  thu_tu: 1,
                  ten_bai_tap: '3/4 sit-up',
                  anh_url: '/media/bai-tap/images/0001-2gPfomN.jpg',
                  gif_url: '/media/bai-tap/animations/0001-2gPfomN.gif',
                },
              ],
            },
          }
        },
      }
      const app = createSSRApp(trangChiTiet)
      app.config.globalProperties.$route = { params: { id: '1' }, meta: { vaiTro } }
      app.component('RouterLink', {
        render() {
          return h('a', this.$slots.default())
        },
      })
      const html = await renderToString(app)
      expect(html).toContain('src="http://localhost:8000/media/bai-tap/images/0001-2gPfomN.jpg"')
      expect(html).toContain(
        'src="http://localhost:8000/media/bai-tap/animations/0001-2gPfomN.gif"',
      )
      expect(html).not.toContain('src="/media/')
    },
  )
  it('KH ngừng giáo án PT qua action chung và giữ phiên bản', async () => {
    keHoachTapService.thaoTac.mockResolvedValue({
      data: { id: 4, nguon_tao: 'PT', trang_thai: 'LUU_TRU', updated_at: 'v2' },
    })
    const p = trang(ChiTiet, {
      keHoach: { id: 4, nguon_tao: 'PT', updated_at: 'v1' },
      $route: { params: { id: '4' }, meta: { vaiTro: 'KHACH_HANG' } },
    })
    await p.thaoTac('luu-tru')
    expect(keHoachTapService.thaoTac).toHaveBeenCalledWith(4, false, 'luu-tru', 'v1')
    expect(p.keHoach.trang_thai).toBe('LUU_TRU')
    expect(p.thanhCong).toBe('Đã ngừng áp dụng và lưu trữ giáo án.')
    expect(p.thongBaoHanhDong['ap-dung']).toContain('kể cả giáo án PT giao')
  })
  it.each([true, false])(
    'nút áp dụng lại bản PT lưu trữ phản ánh quyền API %s',
    async (coTheApDung) => {
      const trangChiTiet = {
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
            keHoach: {
              id: 4,
              nguon_tao: 'PT',
              ten_ke_hoach: 'PT giao',
              trang_thai: 'LUU_TRU',
              trang_thai_hien_thi: 'LUU_TRU',
              co_the_ap_dung: coTheApDung,
              so_ngay_tap: 1,
              so_bai_tap: 0,
              bai_tap: [],
            },
          }
        },
      }
      const app = createSSRApp(trangChiTiet)
      app.config.globalProperties.$route = { params: { id: '4' }, meta: { vaiTro: 'KHACH_HANG' } }
      app.component('RouterLink', {
        render() {
          return h('a', this.$slots.default())
        },
      })
      const html = await renderToString(app)
      expect(html.includes('Áp dụng lại giáo án')).toBe(coTheApDung)
    },
  )
  it('KH áp dụng lại bản PT qua endpoint KH và xác nhận thay bản hiện tại', async () => {
    keHoachTapService.thaoTac.mockResolvedValue({
      data: { id: 4, nguon_tao: 'PT', trang_thai: 'DANG_AP_DUNG', updated_at: 'v2' },
    })
    const p = trang(ChiTiet, {
      keHoach: { id: 4, nguon_tao: 'PT', trang_thai: 'LUU_TRU', updated_at: 'v1' },
      $route: { params: { id: '4' }, meta: { vaiTro: 'KHACH_HANG' } },
    })
    expect(p.nhanApDung).toBe('Áp dụng lại giáo án')
    expect(p.nhanHanhDong['ap-dung']).toBe('Áp dụng giáo án này?')
    expect(p.thongBaoHanhDong['ap-dung']).toContain('được lưu trữ')
    expect(p.thongBaoHanhDong['luu-tru']).toContain('bản PT đã xác nhận trước đây')
    await p.thaoTac('ap-dung')
    expect(keHoachTapService.thaoTac).toHaveBeenCalledWith(4, false, 'ap-dung', 'v1')
    expect(p.keHoach.trang_thai).toBe('DANG_AP_DUNG')
    expect(p.thanhCong).toBe('Đã áp dụng giáo án.')
  })
  it('payload bỏ snapshot/quyền và giữ mức tạ 0 khác với chưa chỉ định', () => {
    const form = {
      ten_ke_hoach: ' Cá nhân ',
      muc_tieu: ' ',
      so_ngay_tap: '1',
      giao_an_mau_id: '',
      trang_thai: 'DANG_AP_DUNG',
    }
    const dong = {
      bai_tap_id: '3',
      ngay_thu: '1',
      thu_tu: 9,
      so_hiep: '3',
      so_lan_lap: '12',
      nghi_giay: '0',
      ghi_chu: ' 0 ',
      ten_bai_tap: 'Giả',
      muc_ta_kg: '0',
    }
    const k = noiDungKeHoach(form, [dong, { ...dong, muc_ta_kg: '' }])
    expect(k).toMatchObject({
      ten_ke_hoach: 'Cá nhân',
      muc_tieu: null,
      giao_an_mau_id: null,
      bai_tap: [
        { thu_tu: 1, muc_ta_kg: 0, ghi_chu: '0' },
        { thu_tu: 2, muc_ta_kg: null },
      ],
    })
    expect(k).not.toHaveProperty('trang_thai')
    expect(k.bai_tap[0]).not.toHaveProperty('ten_bai_tap')
  })
  it('KH mở trình soạn mà không cần tải phân công PT hoặc gói', async () => {
    const p = trang(BieuMau, { $route: { params: {}, meta: { vaiTro: 'KHACH_HANG' } } })
    await p.taiDuLieu()
    expect(p.sanSang).toBe(true)
    expect(p.quayLai).toBe('/khach-hang/ke-hoach')
    expect(keHoachTapService.taiDanhSach).not.toHaveBeenCalled()
  })
  it('KH tạo nháp chuyển đúng khu vực và chặn gửi hai lần', async () => {
    const c = cho()
    keHoachTapService.tao.mockReturnValue(c.promise)
    const p = trang(BieuMau, {
      sanSang: true,
      $route: { params: {}, meta: { vaiTro: 'KHACH_HANG' } },
    })
    p.form.ten_ke_hoach = 'Tự tập'
    const ghi = p.luu()
    await p.luu()
    expect(keHoachTapService.tao).toHaveBeenCalledTimes(1)
    expect(keHoachTapService.tao.mock.calls[0][0]).toBeNull()
    c.resolve({ data: { id: 4 } })
    await ghi
    expect(p.$router.push).toHaveBeenCalledWith('/khach-hang/ke-hoach/4')
  })
  it('KH sửa nháp bằng endpoint KH và giữ version', async () => {
    keHoachTapService.sua.mockResolvedValue({ data: { id: 4 } })
    const p = trang(BieuMau, {
      sanSang: true,
      updatedAt: 'v1',
      $route: { params: { id: '4' }, meta: { vaiTro: 'KHACH_HANG' } },
    })
    await p.luu()
    expect(keHoachTapService.sua).toHaveBeenCalledWith(
      '4',
      expect.objectContaining({ updated_at: 'v1' }),
      false,
    )
  })
  it('áp dụng tự tạo dùng đúng hành động và không gọi xác nhận PT', async () => {
    keHoachTapService.thaoTac.mockResolvedValue({ data: { id: 4, trang_thai: 'DANG_AP_DUNG' } })
    const p = trang(ChiTiet, {
      keHoach: { id: 4, updated_at: 'v1' },
      $route: { params: { id: '4' }, meta: { vaiTro: 'KHACH_HANG' } },
    })
    await p.thaoTac('ap-dung')
    expect(keHoachTapService.thaoTac).toHaveBeenCalledWith(4, false, 'ap-dung', 'v1')
    expect(p.thanhCong).toBe('Đã áp dụng giáo án.')
  })
  it('chặn double-submit và retry mất mạng giữ đúng UUID/payload', async () => {
    const c = cho()
    keHoachTapService.tao.mockReturnValueOnce(c.promise).mockResolvedValueOnce({ data: { id: 8 } })
    const p = trang(BieuMau, { sanSang: true, khachId: 1 })
    p.form.ten_ke_hoach = 'Ban đầu'
    const lanDau = p.luu()
    await p.luu()
    expect(keHoachTapService.tao).toHaveBeenCalledTimes(1)
    c.resolve(Promise.reject(new Error('Mất mạng')))
    await lanDau
    expect(p.choThuLai).toBe(true)
    const body = structuredClone(p.yeuCauCho)
    p.form.ten_ke_hoach = 'Không được đổi yêu cầu retry'
    await p.luu()
    expect(keHoachTapService.tao.mock.calls[1][1]).toEqual(body)
    expect(p.$router.push).toHaveBeenCalledWith('/pt/ke-hoach/8')
  })
  it('409 giữ nội dung đang soạn và khóa ghi tiếp', async () => {
    keHoachTapService.sua.mockRejectedValue({
      response: { status: 409, data: { message: 'Phiên bản cũ.' } },
    })
    const p = trang(BieuMau, { sanSang: true, $route: { params: { id: '8' } }, updatedAt: 'cũ' })
    p.form.ten_ke_hoach = 'Giữ nội dung'
    await p.luu()
    await p.luu()
    expect(p.xungDot).toBe(true)
    expect(p.form.ten_ke_hoach).toBe('Giữ nội dung')
    expect(keHoachTapService.sua).toHaveBeenCalledTimes(1)
  })
  it('422 cho sửa lại field, không khóa retry mạng', async () => {
    keHoachTapService.tao.mockRejectedValue({
      response: {
        status: 422,
        data: { message: 'Sai dữ liệu.', errors: { ten_ke_hoach: ['Bắt buộc.'] } },
      },
    })
    const p = trang(BieuMau, { sanSang: true })
    await p.luu()
    expect(p.loiTruong.ten_ke_hoach).toEqual(['Bắt buộc.'])
    expect(p.yeuCauCho).toBeNull()
    expect(p.voHieu).toBe(false)
  })
  it('bỏ ngày có bài cần xác nhận, giữ bài khi từ chối', () => {
    const p = trang(BieuMau)
    p.form.so_ngay_tap = 2
    p.ngayChon = 2
    p.cacBai = [{ ngay_thu: 2, khoa: 'a' }]
    window.confirm.mockReturnValueOnce(false)
    p.botNgay()
    expect(p.cacBai).toHaveLength(1)
    expect(p.form.so_ngay_tap).toBe(2)
    p.botNgay()
    expect(p.cacBai).toHaveLength(0)
    expect(p.ngayChon).toBe(1)
  })
  it('danh sách không bị response cũ ghi đè sau đổi học viên', async () => {
    const c = cho()
    keHoachTapService.taiDanhSach
      .mockReturnValueOnce(c.promise)
      .mockResolvedValueOnce({ data: [{ id: 9 }], meta: { hoc_vien: { id: 2 } } })
    const p = trang(DanhSach, {
      $route: { params: { khachId: 1 }, meta: { vaiTro: 'HUAN_LUYEN_VIEN' } },
    })
    const cu = p.taiDanhSach()
    p.$route.params.khachId = 2
    await p.taiDanhSach()
    c.resolve({ data: [{ id: 8 }], meta: {} })
    await cu
    expect(p.danhSach).toEqual([{ id: 9 }])
    expect(p.hocVien.id).toBe(2)
  })
  it('xác nhận gửi đúng version, chặn double-submit và bỏ response sau chuyển giáo án', async () => {
    const c = cho()
    keHoachTapService.thaoTac.mockReturnValue(c.promise)
    const p = trang(ChiTiet, {
      keHoach: { id: 8, updated_at: 'phiên bản' },
      $route: { params: { id: '8' }, meta: { vaiTro: 'KHACH_HANG' } },
    })
    const ghi = p.thaoTac('xac-nhan')
    await p.thaoTac('xac-nhan')
    expect(keHoachTapService.thaoTac).toHaveBeenCalledTimes(1)
    expect(keHoachTapService.thaoTac).toHaveBeenCalledWith(8, false, 'xac-nhan', 'phiên bản')
    p.$route.params.id = '9'
    p.keHoach = { id: 9 }
    c.resolve({ data: { id: 8 } })
    await ghi
    expect(p.keHoach.id).toBe(9)
  })
  it('404 xóa nội dung trước đó để không hiển thị giáo án đã mất quyền', async () => {
    keHoachTapService.taiChiTiet.mockRejectedValue({
      response: { status: 404, data: { message: 'Không tìm thấy.' } },
    })
    const p = trang(ChiTiet, {
      keHoach: { id: 8 },
      $route: { params: { id: '8' }, meta: { vaiTro: 'HUAN_LUYEN_VIEN' } },
    })
    await p.taiChiTiet()
    expect(p.keHoach).toBeNull()
    expect(p.loi).toBe('Không tìm thấy.')
  })
  it('mở xác nhận đưa focus tới nội dung và chưa gọi API', async () => {
    const focus = vi.fn()
    const p = trang(ChiTiet, { $nextTick: async () => {}, $refs: { xacNhan: { focus } } })
    await p.deNghiThaoTac('gui')
    expect(p.hanhDongCho).toBe('gui')
    expect(focus).toHaveBeenCalledOnce()
    expect(keHoachTapService.thaoTac).not.toHaveBeenCalled()
  })
  it('KH lọc Đã ẩn và PT vẫn tải toàn bộ, không gửi bộ lọc ẩn', async () => {
    keHoachTapService.taiDanhSach.mockResolvedValue({ data: [], meta: { last_page: 1 } })
    const kh = trang(DanhSach, {
      $route: { params: {}, query: { da_an: '1' }, meta: { vaiTro: 'KHACH_HANG' } },
    })
    await kh.taiDanhSach()
    expect(keHoachTapService.taiDanhSach).toHaveBeenLastCalledWith(
      null,
      { page: 1, da_an: 1 },
      expect.any(AbortSignal),
    )
    kh.$route.query = {}
    await kh.taiDanhSach()
    expect(keHoachTapService.taiDanhSach).toHaveBeenLastCalledWith(
      null,
      { page: 1, da_an: 0 },
      expect.any(AbortSignal),
    )
    const pt = trang(DanhSach, {
      $route: {
        params: { khachId: '2' },
        query: { da_an: '1' },
        meta: { vaiTro: 'HUAN_LUYEN_VIEN' },
      },
    })
    await pt.taiDanhSach()
    expect(keHoachTapService.taiDanhSach).toHaveBeenLastCalledWith(
      '2',
      { page: 1 },
      expect.any(AbortSignal),
    )
  })
  it('ẩn/hiện lại giữ version, chống gửi đôi và backlink đúng danh sách', async () => {
    const c = cho()
    keHoachTapService.thaoTac
      .mockReturnValueOnce(c.promise)
      .mockResolvedValueOnce({ data: { id: 4, da_an: false, updated_at: 'v3' } })
    const p = trang(ChiTiet, {
      keHoach: { id: 4, updated_at: 'v1' },
      $route: { params: { id: '4' }, meta: { vaiTro: 'KHACH_HANG' } },
    })
    const ghi = p.thaoTac('an')
    await p.thaoTac('an')
    expect(keHoachTapService.thaoTac).toHaveBeenCalledTimes(1)
    c.resolve({ data: { id: 4, da_an: true, updated_at: 'v2' } })
    await ghi
    expect(p.quayLai).toBe('/khach-hang/ke-hoach?da_an=1')
    expect(p.thanhCong).toContain('Đã ẩn')
    await p.thaoTac('hien-lai')
    expect(keHoachTapService.thaoTac).toHaveBeenLastCalledWith(4, false, 'hien-lai', 'v2')
    expect(p.quayLai).toBe('/khach-hang/ke-hoach')
  })
  it('đổi bộ lọc hiển thị giữ query khác và xóa da_an khi trở lại', () => {
    const p = trang(DanhSach, {
      $route: { params: {}, query: { khac: 'x' }, meta: { vaiTro: 'KHACH_HANG' } },
    })
    p.doiHienThi({ target: { value: '1' } })
    expect(p.$router.push).toHaveBeenLastCalledWith({ query: { khac: 'x', da_an: '1' } })
    p.$route.query.da_an = '1'
    p.doiHienThi({ target: { value: '0' } })
    expect(p.$router.push).toHaveBeenLastCalledWith({ query: { khac: 'x' } })
  })
})
