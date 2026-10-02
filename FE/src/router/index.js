import { createRouter, createWebHistory } from 'vue-router'
import { useXacThucStore } from '../stores/xacThuc'
import { kiemTraDieuHuong } from './kiemTraDieuHuong'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to, from, viTriDaLuu) {
    if (viTriDaLuu) return viTriDaLuu
    if (to.path !== from.path) return { top: 0 }
    return false
  },
  routes: [
    ...[
      ['khach-hang', 'KHACH_HANG'],
      ['pt', 'HUAN_LUYEN_VIEN'],
      ['admin', 'ADMIN'],
    ].flatMap(([khuVuc, vaiTro]) => [
      {
        path: `/${khuVuc}/lich-hen`,
        component: () => import('../views/LichHen/index.vue'),
        meta: { vaiTro },
      },
      {
        path: `/${khuVuc}/lich-hen/:id`,
        component: () => import('../views/LichHen/index.vue'),
        meta: { vaiTro },
      },
    ]),
    {
      path: '/khach-hang/dat-lich',
      component: () => import('../views/LichHen/KhungGio/index.vue'),
      meta: { vaiTro: 'KHACH_HANG' },
    },
    {
      path: '/pt/khung-gio',
      component: () => import('../views/LichHen/KhungGio/index.vue'),
      meta: { vaiTro: 'HUAN_LUYEN_VIEN' },
    },
    ...['khach-hang', 'admin'].flatMap((khuVuc) => [
      {
        path: `/${khuVuc}/don-hang`,
        component: () => import('../views/DonHang/index.vue'),
        meta: { vaiTro: khuVuc === 'admin' ? 'ADMIN' : 'KHACH_HANG' },
      },
      {
        path: `/${khuVuc}/don-hang/:id`,
        component: () => import('../views/DonHang/index.vue'),
        meta: { vaiTro: khuVuc === 'admin' ? 'ADMIN' : 'KHACH_HANG' },
      },
    ]),
    {
      path: '/khach-hang/goi-cua-toi',
      component: () => import('../views/KhachHang/GoiCuaToi/index.vue'),
      meta: { vaiTro: 'KHACH_HANG' },
    },
    {
      path: '/admin/phan-cong',
      component: () => import('../views/Admin/PhanCong/index.vue'),
      meta: { vaiTro: 'ADMIN' },
    },
    ...[
      ['khach-hang', 'KHACH_HANG'],
      ['pt', 'HUAN_LUYEN_VIEN'],
      ['admin', 'ADMIN'],
    ].map(([khuVuc, vaiTro]) => ({
      path: `/${khuVuc}/tong-quan`,
      component: () => import('../views/TongQuan/index.vue'),
      meta: { vaiTro },
    })),
    {
      path: '/quen-mat-khau',
      component: () => import('../views/KhoiPhucMatKhau/index.vue'),
      meta: { khach: true },
    },
    {
      path: '/dat-lai-mat-khau',
      component: () => import('../views/KhoiPhucMatKhau/index.vue'),
      meta: { khach: true },
    },
    {
      path: '/khach-hang/ho-so/sua',
      component: () => import('../views/CaNhan/Sua/index.vue'),
      meta: { vaiTro: 'KHACH_HANG' },
    },
    {
      path: '/pt/ho-so/sua',
      component: () => import('../views/CaNhan/Sua/index.vue'),
      meta: { vaiTro: 'HUAN_LUYEN_VIEN' },
    },
    {
      path: '/admin/ho-so',
      component: () => import('../views/CaNhan/index.vue'),
      meta: { vaiTro: 'ADMIN' },
    },
    {
      path: '/admin/ho-so/sua',
      component: () => import('../views/CaNhan/Sua/index.vue'),
      meta: { vaiTro: 'ADMIN' },
    },
    {
      path: '/admin/nhom-co',
      name: 'admin-nhom-co',
      component: () => import('../views/Admin/NhomCo/index.vue'),
      meta: { vaiTro: 'ADMIN' },
    },
    {
      path: '/admin/nhom-co/them',
      component: () => import('../views/Admin/NhomCo/BieuMau/index.vue'),
      meta: { vaiTro: 'ADMIN' },
    },
    {
      path: '/admin/nhom-co/:id/sua',
      component: () => import('../views/Admin/NhomCo/BieuMau/index.vue'),
      meta: { vaiTro: 'ADMIN' },
    },
    {
      path: '/admin/giao-an-mau',
      component: () => import('../views/Admin/GiaoAnMau/index.vue'),
      meta: { vaiTro: 'ADMIN' },
    },
    {
      path: '/admin/giao-an-mau/them',
      component: () => import('../views/Admin/GiaoAnMau/BieuMau/index.vue'),
      meta: { vaiTro: 'ADMIN' },
    },
    {
      path: '/admin/giao-an-mau/:id/sua',
      component: () => import('../views/Admin/GiaoAnMau/BieuMau/index.vue'),
      meta: { vaiTro: 'ADMIN' },
    },
    {
      path: '/pt/giao-an-mau',
      component: () => import('../views/PT/GiaoAnMau/index.vue'),
      meta: { vaiTro: 'HUAN_LUYEN_VIEN' },
    },
    {
      path: '/pt/giao-an-mau/:id',
      component: () => import('../views/PT/GiaoAnMau/ChiTiet/index.vue'),
      meta: { vaiTro: 'HUAN_LUYEN_VIEN' },
    },
    { path: '/goi-tap', name: 'goi-tap', component: () => import('../views/GoiTap/index.vue') },
    {
      path: '/goi-tap/:id',
      name: 'chi-tiet-goi-tap',
      component: () => import('../views/GoiTap/ChiTiet/index.vue'),
    },
    {
      path: '/admin/goi-tap',
      name: 'admin-goi-tap',
      component: () => import('../views/Admin/GoiTap/index.vue'),
      meta: { vaiTro: 'ADMIN' },
    },
    {
      path: '/admin/goi-tap/them',
      name: 'admin-them-goi-tap',
      component: () => import('../views/Admin/GoiTap/BieuMau/index.vue'),
      meta: { vaiTro: 'ADMIN' },
    },
    {
      path: '/admin/goi-tap/:id/sua',
      name: 'admin-sua-goi-tap',
      component: () => import('../views/Admin/GoiTap/BieuMau/index.vue'),
      meta: { vaiTro: 'ADMIN' },
    },
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
router.beforeEach((to) => kiemTraDieuHuong(to, useXacThucStore()))

export default router
