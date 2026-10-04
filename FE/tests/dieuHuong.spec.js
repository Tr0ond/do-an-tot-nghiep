import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { createSSRApp } from 'vue'
import { renderToString } from '@vue/server-renderer'
import { createMemoryHistory, createRouter } from 'vue-router'
import { useDieuHuongStore } from '../src/stores/dieuHuong'
import MenuCaNhan from '../src/components/MenuCaNhan.vue'
import CaNhanLayout from '../src/layouts/CaNhanLayout.vue'

vi.mock('../src/services/xacThucService', () => ({ default: {} }))

describe('Menu dọc theo vai trò', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.unstubAllGlobals()
  })

  it.each([
    ['KHACH_HANG', '/khach-hang/tin-nhan', '/khach-hang/goi-cua-toi', '/admin/tai-khoan'],
    ['HUAN_LUYEN_VIEN', '/pt/tin-nhan', '/pt/khung-gio', '/khach-hang/don-hang'],
    ['ADMIN', '/admin/tai-khoan', '/admin/phan-cong', '/pt/tin-nhan'],
    ['', null, null, '/admin/tai-khoan'],
  ])('menu %s có đúng liên kết và nhãn khi thu gọn', async (vaiTro, mot, hai, cam) => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/:pathMatch(.*)*', component: { template: '<div />' } }],
    })
    await router.push('/bai-tap')
    const html = await renderToString(
      createSSRApp(MenuCaNhan, { vaiTro, thuGon: true }).use(createPinia()).use(router),
    )
    if (mot) {
      expect(html).toContain(`href="${mot}"`)
      expect(html).toContain(`href="${hai}"`)
    }
    expect(html).not.toContain(`href="${cam}"`)
    if (vaiTro && vaiTro !== 'ADMIN')
      expect(html).toContain('aria-label="Thư viện bài tập" title="Thư viện bài tập"')
  })

  it('đánh dấu mục cha ở trang chi tiết và đặt lịch nhưng không khớp tiền tố sai', () => {
    const chon = (path, muc) => MenuCaNhan.methods.dangChon.call({ $route: { path } }, muc)
    expect(chon('/admin/goi-tap/7/sua', { duongDan: '/admin/goi-tap' })).toBe(true)
    expect(
      chon('/khach-hang/dat-lich', {
        duongDan: '/khach-hang/lich-hen',
        lienQuan: '/khach-hang/dat-lich',
      }),
    ).toBe(true)
    expect(chon('/bai-tap-khac', { duongDan: '/bai-tap' })).toBe(false)
  })

  it.each(['KHACH_HANG', 'HUAN_LUYEN_VIEN', 'ADMIN'])(
    'chia nhóm menu %s không mất hoặc lặp liên kết',
    (vaiTro) => {
      const danhSach = MenuCaNhan.computed.danhSach.call({ vaiTro })
      const nhom = MenuCaNhan.computed.cacNhom.call({ vaiTro, danhSach })
      expect(nhom).toHaveLength(3)
      expect(nhom.every((n) => n.cacMuc.length > 0)).toBe(true)
      expect(
        nhom
          .flatMap((n) => n.cacMuc)
          .map((m) => m.duongDan)
          .sort(),
      ).toEqual(danhSach.map((m) => m.duongDan).sort())
    },
  )

  it.each([
    ['KHACH_HANG', '/khach-hang/ho-so', '/khach-hang/lich-tap'],
    ['HUAN_LUYEN_VIEN', '/pt/ho-so', '/pt/hoc-vien'],
    ['ADMIN', '/admin/ho-so', '/admin/phan-cong'],
  ])('điều hướng nhanh %s giữ đúng khu vực', (vaiTro, duongDanHoSo, muc) => {
    const menu = CaNhanLayout.computed.menuNhanh.call({ vaiTro, duongDanHoSo })
    expect(menu).toHaveLength(4)
    expect(menu.map((m) => m.to)).toContain(muc)
    expect(menu.every((m) => m.to.startsWith(duongDanHoSo.replace('/ho-so', '/')))).toBe(true)
  })

  it('giữ tùy chọn thu gọn qua tải lại, chỉ khởi tạo một lần', () => {
    const getItem = vi.fn(() => 'true'),
      setItem = vi.fn()
    vi.stubGlobal('localStorage', { getItem, setItem })
    const store = useDieuHuongStore()
    store.khoiTao()
    store.khoiTao()
    expect(store.thuGon).toBe(true)
    expect(getItem).toHaveBeenCalledTimes(1)
    store.doiTrangThai()
    expect(setItem).toHaveBeenCalledWith('gym_sidebar_collapsed', 'false')
  })

  it('vẫn thu gọn được khi trình duyệt chặn lưu trữ', () => {
    vi.stubGlobal('localStorage', {
      getItem() {
        throw new Error('blocked')
      },
      setItem() {
        throw new Error('blocked')
      },
    })
    const store = useDieuHuongStore()
    expect(() => store.khoiTao()).not.toThrow()
    store.doiTrangThai()
    expect(store.thuGon).toBe(true)
  })

  it('đóng ngăn di động và trả focus khi đổi màn hình', () => {
    const focus = vi.fn(),
      close = vi.fn()
    const vm = {
      dangMoMenu: true,
      $refs: { menuDiDong: { open: true, close }, nutMenu: { focus } },
    }
    CaNhanLayout.methods.dongMenuDiDong.call(vm)
    expect(vm.dangMoMenu).toBe(false)
    expect(close).toHaveBeenCalledOnce()
    expect(focus).toHaveBeenCalledOnce()
  })
  it('trả focus về nút Thêm khi mở menu từ thanh dưới', () => {
    const focus = vi.fn()
    const vm = {
      dangMoMenu: true,
      nutMoMenu: { focus },
      $refs: { menuDiDong: { open: true, close: vi.fn() }, nutMenu: { focus: vi.fn() } },
    }
    CaNhanLayout.methods.dongMenuDiDong.call(vm)
    expect(focus).toHaveBeenCalledOnce()
    expect(vm.$refs.nutMenu.focus).not.toHaveBeenCalled()
  })

  it('lỗi đăng xuất được báo và mở lại khả năng thử, không điều hướng', async () => {
    const replace = vi.fn(),
      dongTaiKhoan = vi.fn()
    const vm = {
      dangXuat: false,
      thongBao: '',
      xacThuc: { dangXuat: vi.fn().mockRejectedValue(new Error('network')) },
      $router: { replace },
      dongTaiKhoan,
    }
    await CaNhanLayout.methods.thoat.call(vm)
    expect(vm.dangXuat).toBe(false)
    expect(vm.thongBao).toBeTruthy()
    expect(replace).not.toHaveBeenCalled()
    expect(dongTaiKhoan).toHaveBeenCalledWith(true)
  })
})
