import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { createSSRApp } from 'vue'
import { renderToString } from '@vue/server-renderer'
import { createRouter, createMemoryHistory } from 'vue-router'
import PhanCong from '../src/views/Admin/PhanCong/index.vue'
import { useXacThucStore } from '../src/stores/xacThuc'
import ChiTietGoi from '../src/views/GoiTap/ChiTiet/index.vue'
import DonHang from '../src/views/DonHang/index.vue'
import GoiCuaToi from '../src/views/KhachHang/GoiCuaToi/index.vue'
import muaGoiService from '../src/services/muaGoiService'
import { conChoThanhToan, linkPayosHopLe, dinhDangLuc } from '../src/utils/donHang'

vi.mock('../src/services/xacThucService', () => ({
  default: { taiTaiKhoan: vi.fn(), dangXuat: vi.fn() },
}))

vi.mock('../src/services/muaGoiService', () => ({
  default: {
    taoDon: vi.fn(),
    taiChiTiet: vi.fn(),
    taiDonHang: vi.fn(),
    thaoTacDon: vi.fn(),
    taiGoiCuaToi: vi.fn(),
    doiSoat: vi.fn(),
  },
}))

function taoTrang(component, route = { params: {}, query: {} }) {
  const trang = { ...component.data(), $route: route, $router: { push: vi.fn() } }
  for (const [ten, ham] of Object.entries(component.methods)) trang[ten] = ham.bind(trang)
  for (const [ten, ham] of Object.entries(component.computed || {}))
    Object.defineProperty(trang, ten, { get: () => ham.call(trang) })
  return trang
}
function choKetQua() {
  let resolve
  const promise = new Promise((r) => {
    resolve = r
  })
  return { resolve, promise }
}

describe('Mua gói và thanh toán', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    setActivePinia(createPinia())
    useXacThucStore().taiKhoan = { id: 1, vai_tro: 'KHACH_HANG' }
  })
  it('chặn thanh toán tại đúng mốc hết hạn và không mở link ngoài payOS', () => {
    const don = { trang_thai: 'CHO_THANH_TOAN', han_thanh_toan: '2026-10-02T04:00:00Z' }
    expect(conChoThanhToan(don, Date.parse('2026-10-02T03:59:59Z'))).toBe(true)
    expect(conChoThanhToan(don, Date.parse(don.han_thanh_toan))).toBe(false)
    expect(linkPayosHopLe('https://pay.payos.vn/web/abc')).toBe(true)
    for (const url of [
      'javascript:alert(1)',
      'https://pay.payos.vn.evil.test/abc',
      'https://x:pass@pay.payos.vn/web/abc',
      'http://pay.payos.vn/web/abc',
    ])
      expect(linkPayosHopLe(url)).toBe(false)
    expect(dinhDangLuc('bad')).toBe('—')
  })
  it('double-submit chỉ tạo một yêu cầu; retry lỗi mạng giữ UUID và không gửi giá', async () => {
    const trang = taoTrang(ChiTietGoi)
    trang.goi = { id: 4, gia: 200000 }
    const cho = choKetQua()
    muaGoiService.taoDon.mockReturnValueOnce(cho.promise).mockResolvedValueOnce({ data: { id: 9 } })
    const p = trang.datMua()
    await trang.datMua()
    expect(muaGoiService.taoDon).toHaveBeenCalledTimes(1)
    const lanDau = muaGoiService.taoDon.mock.calls[0][0]
    expect(Object.keys(lanDau).sort()).toEqual(['client_request_id', 'goi_tap_id'])
    cho.resolve(Promise.reject(new Error('network')))
    await p
    await trang.datMua()
    expect(muaGoiService.taoDon.mock.calls[1][0].client_request_id).toBe(lanDau.client_request_id)
    expect(trang.$router.push).toHaveBeenCalledWith('/khach-hang/don-hang/9')
  })
  it('query return PAID không tự xác nhận tiền hoặc gọi API đồng bộ', async () => {
    const trang = taoTrang(DonHang, { params: { id: '5' }, query: { status: 'PAID', code: '00' } })
    muaGoiService.taiChiTiet.mockResolvedValue({ data: { id: 5, trang_thai: 'CHO_THANH_TOAN' } })
    await trang.taiDuLieu()
    expect(trang.don.trang_thai).toBe('CHO_THANH_TOAN')
    expect(muaGoiService.thaoTacDon).not.toHaveBeenCalled()
  })
  it('response chi tiết cũ không ghi đè đơn mới', async () => {
    const cho = choKetQua()
    const trang = taoTrang(DonHang, { params: { id: '5' }, query: {} })
    muaGoiService.taiChiTiet
      .mockReturnValueOnce(cho.promise)
      .mockResolvedValueOnce({ data: { id: 6 } })
    const p = trang.taiDuLieu()
    trang.$route.params.id = '6'
    await trang.taiDuLieu()
    cho.resolve({ data: { id: 5 } })
    await p
    expect(trang.don.id).toBe(6)
  })
  it('đăng xuất xóa đơn và bỏ request còn đang chạy', async () => {
    const cho = choKetQua()
    const trang = taoTrang(DonHang, { params: { id: '5' }, query: {} })
    muaGoiService.taiChiTiet.mockReturnValueOnce(cho.promise)
    const p = trang.taiDuLieu()
    useXacThucStore().taiKhoan = null
    await trang.taiDuLieu()
    cho.resolve({ data: { id: 5 } })
    await p
    expect(trang.don).toBeNull()
    expect(trang.danhSach).toEqual([])
  })
  it('chống gửi đồng bộ hai lần và bỏ kết quả sau chuyển trang', async () => {
    const cho = choKetQua()
    const trang = taoTrang(DonHang)
    trang.don = { id: 8 }
    muaGoiService.thaoTacDon.mockReturnValueOnce(cho.promise)
    const p = trang.thaoTac('dong-bo')
    await trang.thaoTac('dong-bo')
    expect(muaGoiService.thaoTacDon).toHaveBeenCalledTimes(1)
    trang.lanTai++
    trang.don = null
    cho.resolve({ data: { id: 8, trang_thai: 'DANG_SU_DUNG' } })
    await p
    expect(trang.don).toBeNull()
  })
  it('gói của tôi xóa dữ liệu khi mất phiên và bỏ kết quả tới muộn', async () => {
    const cho = choKetQua()
    const trang = taoTrang(GoiCuaToi)
    muaGoiService.taiGoiCuaToi.mockReturnValueOnce(cho.promise)
    const p = trang.taiDuLieu()
    useXacThucStore().taiKhoan = null
    await trang.taiDuLieu()
    cho.resolve({ data: { goi: { id: 8 }, pt: { ho_ten: 'Demo' } } })
    await p
    expect(trang.goi).toBeNull()
    expect(trang.pt).toBeNull()
  })
})

// Dữ liệu đầy đủ giúp phát hiện lỗi template mà kiểm tra trạng thái rỗng không thấy.
describe('Render màn hình mua gói có dữ liệu', () => {
  const don = {
    id: 7,
    ma_don_payos: 100000000000007,
    ten_goi: 'Gói PT demo',
    gia: 990000,
    so_buoi_pt: 8,
    so_buoi_con_lai: 6,
    co_chatbot: true,
    so_luot_chatbot_moi_ngay: 20,
    thoi_han_ngay: 30,
    trang_thai: 'CHO_THANH_TOAN',
    han_thanh_toan: new Date(Date.now() + 600000).toISOString(),
    thanh_toan: [],
  }
  async function render(component, path, data, role = 'KHACH_HANG') {
    const pinia = createPinia()
    useXacThucStore(pinia).taiKhoan = { id: 1, ho_ten: 'Tài khoản demo', vai_tro: role }
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/:pathMatch(.*)*', component: { render: () => null } }],
    })
    await router.push(path)
    const app = createSSRApp({
      ...component,
      data() {
        return { ...component.data(), ...data }
      },
    })
    app.use(pinia).use(router)
    return renderToString(app)
  }
  it('chi tiết đơn hiển thị trạng thái và nút thanh toán, header đánh dấu Đơn hàng', async () => {
    const html = await render(DonHang, '/khach-hang/don-hang/7', { don })
    expect(html).toContain('Chờ thanh toán')
    expect(html).toContain('Tạo liên kết thanh toán')
    expect(html).toMatch(/href="\/khach-hang\/don-hang"[^>]*is-active/)
  })
  it('danh sách có đơn hiển thị nhãn trạng thái', async () => {
    const html = await render(DonHang, '/khach-hang/don-hang', { danhSach: [don] })
    expect(html).toContain('Gói PT demo')
    expect(html).toContain('Chờ thanh toán')
    expect(html).toContain('Xem đơn')
  })
  it('Admin hiển thị biểu mẫu đối soát và không có nút tạo link', async () => {
    const t = { id: 4, so_tien: 1000000, trang_thai: 'CAN_DOI_SOAT', ma_giao_dich: 'DEMO' }
    const html = await render(
      DonHang,
      '/admin/don-hang/7',
      {
        don: { ...don, trang_thai: 'CAN_DOI_SOAT', thanh_toan: [t] },
        doiSoat: { id: 4, so_tien_hoan: 1000000, ma_hoan_tien: '', ly_do: '' },
      },
      'ADMIN',
    )
    expect(html).toContain('Số tiền đã hoàn (VND)')
    expect(html).toContain('Lưu kết quả')
    expect(html).not.toContain('Tạo liên kết thanh toán')
  })
  it('gói của tôi hiển thị số buổi và PT phụ trách', async () => {
    const html = await render(GoiCuaToi, '/khach-hang/goi-cua-toi', {
      goi: { ...don, trang_thai: 'DANG_SU_DUNG' },
      pt: { ho_ten: 'PT demo' },
    })
    expect(html).toContain('6 / 8 buổi')
    expect(html).toContain('PT demo')
  })
  it('đổi PT hiển thị lý do và lựa chọn PT đang hoạt động', async () => {
    const html = await render(
      PhanCong,
      '/admin/phan-cong',
      {
        khachDaChon: {
          khach_hang_id: 1,
          ho_ten: 'Học viên demo',
          phan_cong_hien_tai_id: 2,
          pt: { id: 2, ho_ten: 'PT cũ' },
        },
        danhSachPT: [{ id: 3, ho_ten: 'PT demo', chuyen_mon: 'Sức mạnh' }],
      },
      'ADMIN',
    )
    expect(html).toContain('Lý do đổi PT')
    expect(html).toContain('PT demo')
  })
})
