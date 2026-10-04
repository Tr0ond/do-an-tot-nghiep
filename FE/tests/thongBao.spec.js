import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { createSSRApp } from 'vue'
import { renderToString } from '@vue/server-renderer'
import { createMemoryHistory, createRouter } from 'vue-router'
import { useThongBaoStore } from '../src/stores/thongBao'
import { useXacThucStore } from '../src/stores/xacThuc'
import { useChatStore } from '../src/stores/chat'
import thongBaoService from '../src/services/thongBaoService'
import chatService from '../src/services/chatService'
import ThongBaoHeader from '../src/components/ThongBaoHeader.vue'
import routerUngDung from '../src/router'

vi.mock('../src/services/xacThucService', () => ({ default: {} }))
// Kiểm tra bảng route thật trong môi trường Node, dùng history bộ nhớ để không cần cửa sổ.
vi.mock('vue-router', async (importOriginal) => {
  const goc = await importOriginal()
  return { ...goc, createWebHistory: goc.createMemoryHistory }
})
vi.mock('../src/services/chatService', () => ({
  default: { taiHoiThoai: vi.fn(), daDoc: vi.fn() },
}))
vi.mock('../src/services/thongBaoService', () => ({
  default: { taiDanhSach: vi.fn(), daDoc: vi.fn(), daDocTatCa: vi.fn() },
}))

const duLieu = (so = 1) => ({
  data: [{ id: 'mot', tieu_de: '<script>bad()</script>', noi_dung: 'Riêng tư', da_doc_luc: null }],
  meta: { so_chua_doc: so, current_page: 1, last_page: 1 },
})
function choKetQua() {
  let resolve
  const promise = new Promise((r) => {
    resolve = r
  })
  return { resolve, promise }
}

describe('Thông báo trên header', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    setActivePinia(createPinia())
  })
  afterEach(() => {
    useThongBaoStore().dong()
    useChatStore().dongKetNoi()
  })

  it.each([
    ['KHACH_HANG', '/khach-hang/tin-nhan'],
    ['HUAN_LUYEN_VIEN', '/pt/tin-nhan'],
    ['ADMIN', null],
  ])('header %s có chuông và đúng quyền chat', async (vai_tro, duongDan) => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/:pathMatch(.*)*', component: { template: '<div />' } }],
    })
    await router.push('/')
    const pinia = createPinia()
    const app = createSSRApp(ThongBaoHeader).use(pinia).use(router)
    useXacThucStore(pinia).taiKhoan = { id: 1, vai_tro }
    useChatStore(pinia).soChuaDoc = 108
    useThongBaoStore(pinia).soChuaDoc = 7
    const html = await renderToString(app)
    expect(html).toContain('Thông báo, 7 chưa đọc')
    if (duongDan) {
      expect(html).toContain('aria-controls="header-tin-nhan"')
      expect(html).not.toContain(`href="${duongDan}"`)
      expect(html).toContain('99+')
    } else expect(html).not.toContain('bi-chat-left-text')
  })

  it.each(['KHACH_HANG', 'HUAN_LUYEN_VIEN'])(
    'bảng xem nhanh %s mở đúng hội thoại và escape nội dung',
    async (vai_tro) => {
      const pinia = createPinia()
      useXacThucStore(pinia).taiKhoan = { id: 1, vai_tro }
      useChatStore(pinia).hoiThoaiMoi = [
        {
          id: 9,
          doi_phuong: { ho_ten: '<script>Tên</script>' },
          tin_cuoi: '<img src=x onerror=alert(1)>',
          tin_cuoi_luc: '2026-10-03T00:00:00Z',
          so_chua_doc: 2,
        },
      ]
      const router = createRouter({
        history: createMemoryHistory(),
        routes: [{ path: '/:pathMatch(.*)*', component: { template: '<div />' } }],
      })
      await router.push('/')
      const app = createSSRApp({
        ...ThongBaoHeader,
        data: () => ({ dangMo: false, dangMoChat: true }),
      })
        .use(pinia)
        .use(router)
      const html = await renderToString(app)
      const goc = vai_tro === 'KHACH_HANG' ? '/khach-hang/tin-nhan' : '/pt/tin-nhan'
      expect(html).toContain(`href="${goc}/9"`)
      expect(html).toContain(`href="${goc}"`)
      expect(html).toContain('Xem tất cả tin nhắn')
      expect(html).toContain('&lt;script&gt;Tên&lt;/script&gt;')
      expect(html).toContain('&lt;img src=x onerror=alert(1)&gt;')
      expect(html).not.toContain('<img src=x')
    },
  )

  it('bấm biểu tượng mở bảng xem nhanh, đóng chuông, chưa điều hướng hoặc đánh dấu đọc', async () => {
    const focus = vi.fn()
    const tai = vi.fn()
    const trang = {
      dangMo: true,
      dangMoChat: false,
      chat: { taiSoChuaDoc: tai },
      $emit: vi.fn(),
      $nextTick: () => Promise.resolve(),
      $refs: { dongChat: { focus } },
      $router: { push: vi.fn() },
    }
    await ThongBaoHeader.methods.doiTrangThaiChat.call(trang)
    expect(trang.dangMo).toBe(false)
    expect(trang.dangMoChat).toBe(true)
    expect(tai).toHaveBeenCalledOnce()
    expect(trang.$emit).toHaveBeenCalledWith('mo-bang')
    expect(focus).toHaveBeenCalledOnce()
    expect(trang.$router.push).not.toHaveBeenCalled()
    expect(chatService.daDoc).not.toHaveBeenCalled()
  })

  it('Escape bảng tin nhắn trả focus về đúng biểu tượng', () => {
    const focus = vi.fn()
    const trang = {
      dangMo: false,
      dangMoChat: true,
      $refs: { tinNhan: { focus }, chuong: { focus: vi.fn() } },
    }
    trang.dong = ThongBaoHeader.methods.dong.bind(trang)
    ThongBaoHeader.methods.bamPhim.call(trang, { key: 'Escape', preventDefault: vi.fn() })
    expect(trang.dangMoChat).toBe(false)
    expect(focus).toHaveBeenCalledOnce()
    expect(trang.$refs.chuong.focus).not.toHaveBeenCalled()
  })

  it('nội dung thông báo được escape khi hiển thị', async () => {
    const pinia = createPinia()
    useThongBaoStore(pinia).danhSach = duLieu().data
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/', component: { template: '<div />' } }],
    })
    await router.push('/')
    const app = createSSRApp({ ...ThongBaoHeader, data: () => ({ dangMo: true }) })
      .use(pinia)
      .use(router)
    const html = await renderToString(app)
    expect(html).toContain('&lt;script&gt;bad()&lt;/script&gt;')
    expect(html).not.toContain('<script>bad()')
  })

  it.each([
    ['KHACH_HANG', '/khach-hang/don-hang/7'],
    ['KHACH_HANG', '/khach-hang/ho-so'],
    ['KHACH_HANG', '/khach-hang/lich-hen/8'],
    ['KHACH_HANG', '/khach-hang/ke-hoach/9'],
    ['KHACH_HANG', '/khach-hang/lich-tap/10'],
    ['HUAN_LUYEN_VIEN', '/pt/hoc-vien/2/ke-hoach'],
    ['HUAN_LUYEN_VIEN', '/pt/hoc-vien'],
    ['HUAN_LUYEN_VIEN', '/pt/lich-hen/8'],
    ['HUAN_LUYEN_VIEN', '/pt/ke-hoach/9'],
    ['HUAN_LUYEN_VIEN', '/pt/lich-tap/10'],
    ['ADMIN', '/admin/phan-cong'],
    ['ADMIN', '/admin/don-hang/7'],
    ['ADMIN', '/admin/lich-hen/8'],
    ['ADMIN', '/admin/giao-an-mau/6/sua'],
  ])('thông báo %s mở tài nguyên %s sau khi server đánh dấu đọc', async (vaiTro, duongDan) => {
    const route = routerUngDung.resolve(duongDan)
    expect(route.meta.vaiTro).toBe(vaiTro)
    const trinhTu = []
    const trang = {
      thongBao: {
        maPhien: 1,
        danhDauDoc: vi.fn(async () => {
          trinhTu.push('doc')
          return true
        }),
      },
      dong: vi.fn(),
      $router: { push: vi.fn(async () => trinhTu.push('mo')) },
    }
    await ThongBaoHeader.methods.moThongBao.call(trang, { id: 'uuid', duong_dan: duongDan })
    expect(trinhTu).toEqual(['doc', 'mo'])
    expect(trang.$router.push).toHaveBeenCalledWith(duongDan)
    expect(trang.dong).toHaveBeenCalledOnce()
  })

  it('không chuyển trang khi đọc lỗi hoặc session đổi trong lúc đánh dấu đọc', async () => {
    const trang = {
      thongBao: { maPhien: 1, danhDauDoc: vi.fn(async () => false) },
      dong: vi.fn(),
      $router: { push: vi.fn() },
    }
    const tin = { id: 'uuid', duong_dan: '/pt/lich-hen/8' }
    await ThongBaoHeader.methods.moThongBao.call(trang, tin)
    expect(trang.$router.push).not.toHaveBeenCalled()
    trang.thongBao.danhDauDoc.mockImplementation(async () => {
      trang.thongBao.maPhien++
      return true
    })
    await ThongBaoHeader.methods.moThongBao.call(trang, tin)
    expect(trang.$router.push).not.toHaveBeenCalled()
    expect(trang.dong).not.toHaveBeenCalled()
  })

  it('bỏ response cũ sau khi đổi tài khoản', async () => {
    const store = useThongBaoStore()
    store.taiKhoanId = 1
    const cho = choKetQua()
    thongBaoService.taiDanhSach.mockReturnValue(cho.promise)
    const tai = store.taiDanhSach()
    store.dong()
    store.taiKhoanId = 2
    cho.resolve(duLieu(8))
    await tai
    expect(store.danhSach).toEqual([])
    expect(store.soChuaDoc).toBe(0)
  })

  it('không báo đã đọc khi server lỗi và chặn bấm trùng', async () => {
    const store = useThongBaoStore()
    store.taiKhoanId = 1
    store.soChuaDoc = 5
    const cho = choKetQua()
    thongBaoService.daDoc.mockReturnValue(cho.promise)
    const doc = store.danhDauDoc('mot')
    expect(await store.danhDauDoc('mot')).toBe(false)
    cho.resolve({})
    thongBaoService.taiDanhSach.mockResolvedValue(duLieu(4))
    expect(await doc).toBe(true)
    expect(thongBaoService.daDoc).toHaveBeenCalledTimes(1)
    expect(store.soChuaDoc).toBe(4)
    thongBaoService.daDoc.mockRejectedValue({
      response: { status: 500, data: { message: 'Lỗi lưu' } },
    })
    expect(await store.danhDauDoc('hai')).toBe(false)
    expect(store.soChuaDoc).toBe(4)
    expect(store.loi).toBe('Lỗi lưu')
  })

  it('session hết hạn xóa dữ liệu riêng và tài khoản', async () => {
    const store = useThongBaoStore()
    store.taiKhoanId = 1
    store.danhSach = duLieu().data
    useXacThucStore().taiKhoan = { id: 1 }
    thongBaoService.taiDanhSach.mockRejectedValue({ response: { status: 401 } })
    await store.taiDanhSach()
    expect(store.danhSach).toEqual([])
    expect(useXacThucStore().taiKhoan).toBeNull()
  })

  it('kết quả đánh dấu đọc của phiên cũ không tải hoặc ghi đè tài khoản mới', async () => {
    const store = useThongBaoStore()
    store.taiKhoanId = 1
    const cho = choKetQua()
    thongBaoService.daDocTatCa.mockReturnValue(cho.promise)
    const doc = store.danhDauDoc()
    store.dong()
    store.taiKhoanId = 2
    store.soChuaDoc = 3
    cho.resolve({})
    expect(await doc).toBe(false)
    expect(store.soChuaDoc).toBe(3)
    expect(thongBaoService.taiDanhSach).not.toHaveBeenCalled()
  })

  it('Escape đóng bảng và trả focus về chuông', () => {
    const focus = vi.fn()
    const trang = { dangMo: true, $refs: { chuong: { focus } } }
    trang.dong = ThongBaoHeader.methods.dong.bind(trang)
    const event = { key: 'Escape', preventDefault: vi.fn() }
    ThongBaoHeader.methods.bamPhim.call(trang, event)
    expect(trang.dangMo).toBe(false)
    expect(focus).toHaveBeenCalledOnce()
  })
})
