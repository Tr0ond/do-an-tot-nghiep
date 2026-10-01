import { createRouter, createWebHistory } from 'vue-router'
import { useXacThucStore } from '../stores/xacThuc'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/admin/bai-tap',
      name: 'admin-bai-tap',
      component: () => import('../views/Admin/BaiTap/index.vue'),
      meta: { vaiTro: 'ADMIN' },
    },
    {
      path: '/admin/bai-tap/them',
      name: 'admin-them-bai-tap',
      component: () => import('../views/Admin/BaiTap/BieuMau/index.vue'),
      meta: { vaiTro: 'ADMIN' },
    },
    {
      path: '/admin/bai-tap/:id/sua',
      name: 'admin-sua-bai-tap',
      component: () => import('../views/Admin/BaiTap/BieuMau/index.vue'),
      meta: { vaiTro: 'ADMIN' },
    },
    { path: '/bai-tap', name: 'bai-tap', component: () => import('../views/BaiTap/index.vue') },
    {
      path: '/bai-tap/:id',
      name: 'chi-tiet-bai-tap',
      component: () => import('../views/BaiTap/ChiTiet/index.vue'),
    },
    { path: '/khong-ket-noi', component: () => import('../views/KhongKetNoi/index.vue') },
    {
      path: '/dang-nhap',
      name: 'dang-nhap',
      component: () => import('../views/XacThuc/index.vue'),
      meta: { khach: true },
    },
    {
      path: '/dang-ky',
      name: 'dang-ky',
      component: () => import('../views/XacThuc/index.vue'),
      meta: { khach: true },
    },
    {
      path: '/khach-hang/ho-so',
      component: () => import('../views/CaNhan/index.vue'),
      meta: { vaiTro: 'KHACH_HANG' },
    },
    {
      path: '/pt/ho-so',
      component: () => import('../views/CaNhan/index.vue'),
      meta: { vaiTro: 'HUAN_LUYEN_VIEN' },
    },
    {
      path: '/admin/tai-khoan',
      component: () => import('../views/Admin/TaiKhoan/index.vue'),
      meta: { vaiTro: 'ADMIN' },
    },
    { path: '/khong-co-quyen', component: () => import('../views/KhongCoQuyen/index.vue') },
    {
      path: '/',
      name: 'trang-chu',
      component: () => import('../views/TrangChu/index.vue'),
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
  ],
})

// Router hỗ trợ điều hướng; mọi API đều kiểm tra session/quyền ở Backend.
router.beforeEach(async (to) => {
  const xacThuc = useXacThucStore()
  if (to.meta.vaiTro || to.meta.khach) {
    try {
      await xacThuc.taiTaiKhoan()
    } catch {
      if (to.meta.vaiTro) return '/khong-ket-noi'
      // Vẫn mở biểu mẫu để người dùng có thể thử lại khi Backend mất kết nối.
      return true
    }
  }
  if (to.meta.vaiTro && !xacThuc.daDangNhap) return '/dang-nhap'
  if (to.meta.vaiTro && to.meta.vaiTro !== xacThuc.taiKhoan.vai_tro) return '/khong-co-quyen'
  if (to.meta.khach && xacThuc.daDangNhap) return xacThuc.duongDanCaNhan
})

export default router
