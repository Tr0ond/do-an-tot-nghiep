<template>
  <div class="error-page-container">
    <div class="error-card-modern animate__animated animate__fadeInUp">
      <!-- Biểu tượng cảnh báo quyền hạn với hiệu ứng phát sáng -->
      <div class="error-icon-halo error-halo-warning mx-auto">
        <i class="bi bi-shield-lock-fill"></i>
      </div>

      <div class="eyebrow mx-auto mb-2 text-warning bg-warning-subtle border-warning-subtle">
        <i class="bi bi-exclamation-octagon-fill me-1"></i>
        <span>LỖI 403 · KHÔNG ĐỦ THẨM QUYỀN</span>
      </div>

      <h1 class="h3 fw-bold mb-2">Truy cập bị từ chối</h1>

      <p class="text-muted small mb-4 mx-auto" style="max-width: 480px">
        Trang bạn đang cố gắng truy cập chỉ dành cho các tài khoản được cấp quyền đặc biệt. Tài
        khoản hiện tại của bạn không có quyền xem hoặc thao tác trên tài nguyên này.
      </p>

      <!-- Thẻ thông tin tài khoản hiện tại -->
      <div v-if="xacThuc.daDangNhap" class="p-3 bg-light rounded-3 border mb-4 text-start">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <span class="small text-muted">Đang đăng nhập với tên:</span>
          <strong class="small">{{ xacThuc.taiKhoan?.ho_ten }}</strong>
        </div>
        <div class="d-flex align-items-center justify-content-between">
          <span class="small text-muted">Vai trò hiện tại:</span>
          <span class="badge-role" :class="badgeRoleClass">
            {{ nhanVaiTro }}
          </span>
        </div>
      </div>

      <!-- Các nút hành động xử lý -->
      <div class="d-flex gap-2 justify-content-center flex-wrap">
        <RouterLink class="btn btn-primary" :to="duongDan">
          <i class="bi bi-person-circle"></i>
          <span>Về trang cá nhân</span>
        </RouterLink>
        <RouterLink class="btn btn-outline-secondary" to="/">
          <i class="bi bi-house-door"></i>
          <span>Về trang chủ</span>
        </RouterLink>
        <RouterLink class="btn btn-outline-secondary" to="/dang-nhap">
          <i class="bi bi-box-arrow-in-right"></i>
          <span>Đăng nhập tài khoản khác</span>
        </RouterLink>
      </div>

      <!-- Chân thẻ thông tin trợ giúp -->
      <div class="mt-4 pt-3 border-top small text-muted">
        <i class="bi bi-info-circle me-1"></i>
        Nếu bạn cho rằng đây là lỗi nhầm lẫn, vui lòng liên hệ Ban quản trị để được hỗ trợ kiểm tra
        phân quyền.
      </div>
    </div>
  </div>
</template>

<script>
import { useXacThucStore } from '../../stores/xacThuc'

export default {
  name: 'TrangKhongCoQuyen',
  computed: {
    xacThuc() {
      return useXacThucStore()
    },
    duongDan() {
      return this.xacThuc.duongDanCaNhan
    },
    nhanVaiTro() {
      const vaiTro = this.xacThuc.taiKhoan?.vai_tro
      return (
        {
          KHACH_HANG: 'Khách hàng',
          HUAN_LUYEN_VIEN: 'Huấn luyện viên',
          ADMIN: 'Quản trị viên',
        }[vaiTro] || 'Chưa xác định'
      )
    },
    badgeRoleClass() {
      const vaiTro = this.xacThuc.taiKhoan?.vai_tro
      return (
        {
          ADMIN: 'badge-role-admin',
          HUAN_LUYEN_VIEN: 'badge-role-pt',
          KHACH_HANG: 'badge-role-khach',
        }[vaiTro] || ''
      )
    },
  },
}
</script>
