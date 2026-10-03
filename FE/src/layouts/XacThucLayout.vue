<template>
  <main class="auth-shell">
    <!-- Cột bên trái: Giới thiệu thương hiệu và thông điệp truyền cảm hứng -->
    <aside class="auth-intro">
      <div class="auth-top">
        <RouterLink to="/" class="brand brand-light">
          <LogoThuongHieu />
          <span>HUẤN LUYỆN CÁ NHÂN</span>
        </RouterLink>
      </div>

      <div class="auth-story">
        <div class="eyebrow animate__animated animate__fadeInDown">
          <i class="bi bi-lightning-charge-fill me-1"></i>
          <span>TẬP LUYỆN CÓ ĐỊNH HƯỚNG</span>
        </div>
        <h2 class="animate__animated animate__fadeInUp">
          Mỗi buổi tập, <br />
          <span>một bước chuyển mình.</span>
        </h2>
        <p class="animate__animated animate__fadeInUp animate__delay-1s">
          Đồng hành cùng huấn luyện viên riêng, theo dõi sát sao chế độ tập luyện và dinh dưỡng khoa
          học.
        </p>

        <!-- Thẻ trích dẫn động viên -->
        <div class="auth-quotes-card animate__animated animate__fadeInUp animate__delay-1s">
          <div class="quote-rating mb-2">
            <i class="bi bi-star-fill text-warning me-1"></i>
            <i class="bi bi-star-fill text-warning me-1"></i>
            <i class="bi bi-star-fill text-warning me-1"></i>
            <i class="bi bi-star-fill text-warning me-1"></i>
            <i class="bi bi-star-fill text-warning"></i>
            <span class="ms-2 small text-light fw-bold">4.9 / 5.0</span>
          </div>
          <p class="mb-2 text-light fst-italic small">
            "Có HLV kèm riêng giúp tôi tập đúng form, tránh chấn thương và đạt mục tiêu nhanh hơn
            hẳn việc tự mò mẫm."
          </p>
          <div class="d-flex align-items-center gap-2">
            <div class="badge-role badge-role-pt">
              <i class="bi bi-person-check-fill"></i>
              <span>Học viên khóa 3 tháng</span>
            </div>
          </div>
        </div>
      </div>

      <div class="auth-foot">
        <span>Bắt đầu từ mục tiêu thể chất của bạn.</span>
        <RouterLink to="/" class="text-light opacity-75 small text-decoration-none">
          <i class="bi bi-house-door me-1"></i>Về trang chủ
        </RouterLink>
      </div>
    </aside>

    <!-- Cột bên phải: Khu vực chứa biểu mẫu -->
    <section class="auth-form-area">
      <div class="auth-top-action">
        <NutChuyenChuDe kich-thuoc="sm" />
      </div>
      <div class="auth-form-card animate__animated animate__fadeIn">
        <slot />
      </div>
      <p class="auth-note text-center mt-3 text-muted small">
        <i class="bi bi-shield-check me-1"></i>Thông tin của bạn được bảo mật tuyệt đối theo tiêu
        chuẩn hệ thống.
      </p>
    </section>
  </main>
</template>

<script>
import NutChuyenChuDe from '../components/NutChuyenChuDe.vue'
import LogoThuongHieu from '../components/LogoThuongHieu.vue'

export default {
  name: 'XacThucLayout',
  components: {
    NutChuyenChuDe,
    LogoThuongHieu,
  },
}
</script>

<style scoped>
.auth-top-action {
  position: absolute;
  top: 24px;
  right: 28px;
  z-index: 10;
}

.auth-top {
  position: relative;
  z-index: 2;
}

.auth-intro {
  background: #0a0a0a !important;
}

.auth-intro::before {
  background: radial-gradient(circle, rgba(244, 91, 32, 0.28) 0%, transparent 70%) !important;
}

.auth-story h2 span {
  background: linear-gradient(135deg, #f45b20 0%, #ff8c5a 100%) !important;
  -webkit-background-clip: text !important;
  -webkit-text-fill-color: transparent !important;
}

.auth-quotes-card {
  background: rgba(26, 26, 26, 0.7);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  padding: 18px 20px;
  margin-top: 28px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
}
</style>
