<template>
  <div class="cinematic-site motion-home">
    <header class="cinematic-header">
      <RouterLink to="/" class="home-brand" aria-label="Tr0ond Fitness, trang chủ"
        ><LogoThuongHieu /><strong>Tr0ond<span>Fitness</span></strong></RouterLink
      >
      <nav class="cinematic-nav-links d-none d-lg-flex" aria-label="Điều hướng chính">
        <RouterLink to="/bai-tap" class="cinematic-link">Thư viện bài tập</RouterLink>
        <RouterLink to="/goi-tap" class="cinematic-link">Gói tập</RouterLink>
        <a href="#hanh-trinh" class="cinematic-link">Hành trình</a>
        <RouterLink to="/faq" class="cinematic-link">Hỗ trợ</RouterLink>
      </nav>
      <div class="home-header-actions">
        <NutChuyenChuDe /><RouterLink to="/dang-nhap" class="cinematic-link">Đăng nhập</RouterLink
        ><RouterLink to="/dang-ky" class="cinematic-btn-primary d-none d-sm-inline-flex"
          >Đăng ký<i class="bi bi-arrow-right" aria-hidden="true"></i
        ></RouterLink>
      </div>
    </header>
    <section class="home-hero">
      <div class="home-hero-copy">
        <span class="home-eyebrow"
          ><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i> MOVE. TRACK. GROW.</span
        >
        <h1>Tr0ond Fitness<span>Huấn luyện cá nhân.</span></h1>
        <p>
          Mỗi buổi tập là một bước tiến. Xây dựng giáo án, ghi lại hành trình và kết nối cùng huấn
          luyện viên của bạn.
        </p>
        <div class="home-hero-actions">
          <RouterLink to="/dang-ky" class="cinematic-btn-primary"
            >Bắt đầu hành trình<i class="bi bi-arrow-up-right" aria-hidden="true"></i></RouterLink
          ><RouterLink to="/bai-tap" class="cinematic-btn-secondary"
            ><i class="bi bi-collection-play" aria-hidden="true"></i> Khám phá bài tập</RouterLink
          >
        </div>
        <div class="home-hero-note">
          <i class="bi bi-check2-circle" aria-hidden="true"></i> Tự tập theo nhịp của bạn
          <span>·</span> Đồng hành cùng PT
        </div>
      </div>
      <div
        class="phone-perspective-stage"
        @mousemove="xuLyHoverPhone"
        @mouseleave="resetHoverPhone"
      >
        <div
          class="phone-tilt-rig"
          :style="{
            transform: `rotateY(${phoneRotateY}deg) rotateX(${phoneRotateX}deg) translateY(${phoneTranslateY}px)`,
          }"
        >
          <!-- Khung viền điện thoại 3D -->
          <div class="phone-frame">
            <!-- Dynamic Island -->
            <div class="phone-dynamic-island"></div>

            <!-- Màn hình bên trong hiển thị ảnh giải phẫu nhóm cơ do người dùng cung cấp -->
            <div class="phone-screen">
              <img
                src="/images/exercise-anatomy.png"
                alt="Phân tích giải phẫu bài tập thể hình"
                class="phone-screen-img"
              />
            </div>
          </div>

          <!-- Huy hiệu nổi 1: Calories (Top-Left, Z: 50px) -->
          <div class="floating-ui-badge badge-calories">
            <div class="badge-icon-box badge-icon-calories">
              <i class="bi bi-fire" aria-hidden="true"></i>
            </div>
            <div class="badge-text-group">
              <span class="badge-label">Calories</span>
              <span class="badge-val">847 kcal</span>
            </div>
          </div>

          <!-- Huy hiệu nổi 2: Heart Rate (Bottom-Left, Z: 40px) -->
          <div class="floating-ui-badge badge-heart">
            <i class="bi bi-heart-pulse-fill text-danger fs-5" aria-hidden="true"></i>
            <span class="badge-val">124 bpm</span>
          </div>

          <!-- Huy hiệu nổi 3: Streak (Right, Z: 60px) -->
          <div class="floating-ui-badge badge-streak">
            <div class="badge-icon-box badge-icon-streak">
              <i class="bi bi-trophy-fill" aria-hidden="true"></i>
            </div>
            <div class="badge-text-group">
              <span class="badge-label">Streak</span>
              <span class="badge-val">32 Days 🔥</span>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section id="hanh-trinh" class="home-journey">
      <div class="home-section-title">
        <span class="home-eyebrow">01 / HÀNH TRÌNH TẬP LUYỆN</span>
        <h2>Chủ động từng bước.</h2>
        <RouterLink to="/bai-tap"
          >Thư viện bài tập <i class="bi bi-arrow-up-right" aria-hidden="true"></i
        ></RouterLink>
      </div>
      <div class="home-service-grid">
        <RouterLink v-for="muc in dichVu" :key="muc.ten" :to="muc.duongDan" class="home-service"
          ><i :class="'bi bi-' + muc.icon" aria-hidden="true"></i>
          <h3>{{ muc.ten }}</h3>
          <p>{{ muc.moTa }}</p>
          <i class="bi bi-arrow-up-right home-service-arrow" aria-hidden="true"></i
        ></RouterLink>
      </div>
    </section>
    <section class="home-start">
      <div>
        <span class="home-eyebrow">02 / BẮT ĐẦU</span>
        <h2>Một mục tiêu.<br />Một hành trình của riêng bạn.</h2>
        <RouterLink to="/dang-ky" class="cinematic-btn-primary"
          >Tạo tài khoản<i class="bi bi-arrow-right" aria-hidden="true"></i
        ></RouterLink>
      </div>
      <ol class="home-steps">
        <li>
          <span>01</span>
          <div>
            <h3>Tạo tài khoản</h3>
            <p>Cập nhật hồ sơ và mục tiêu tập luyện của bạn.</p>
          </div>
        </li>
        <li>
          <span>02</span>
          <div>
            <h3>Chọn cách tập</h3>
            <p>Tự xây dựng giáo án hoặc chọn gói để đồng hành cùng PT.</p>
          </div>
        </li>
        <li>
          <span>03</span>
          <div>
            <h3>Theo dõi tiến bộ</h3>
            <p>Ghi nhật ký từng hiệp, cập nhật chỉ số và trao đổi cùng PT phụ trách.</p>
          </div>
        </li>
      </ol>
    </section>
    <footer class="cinematic-footer">
      <RouterLink to="/" class="home-brand"
        ><LogoThuongHieu /><strong>Tr0ond<span>Fitness</span></strong></RouterLink
      >
      <nav aria-label="Liên kết cuối trang">
        <RouterLink to="/bai-tap">Bài tập</RouterLink><RouterLink to="/goi-tap">Gói tập</RouterLink
        ><RouterLink to="/faq">Hỗ trợ</RouterLink>
      </nav>
      <span>© 2026 Tr0ond Fitness</span>
    </footer>
  </div>
</template>

<script>
import '../../assets/styles/cinematicDark.css'
import '../../assets/styles/homeMotion.css'
import NutChuyenChuDe from '../../components/NutChuyenChuDe.vue'
import LogoThuongHieu from '../../components/LogoThuongHieu.vue'

export default {
  name: 'TrangChu',
  components: {
    NutChuyenChuDe,
    LogoThuongHieu,
  },
  data() {
    return {
      phoneRotateX: 8,
      phoneRotateY: -18,
      phoneTranslateY: 0,
      dichVu: [
        {
          ten: 'Giáo án cá nhân',
          moTa: 'Sắp xếp bài tập, số hiệp và số lần theo mục tiêu của bạn.',
          icon: 'journal-check',
          duongDan: '/dang-ky',
        },
        {
          ten: 'Thư viện bài tập',
          moTa: 'Tìm động tác theo nhóm cơ, dụng cụ và hình ảnh minh họa.',
          icon: 'collection-play',
          duongDan: '/bai-tap',
        },
        {
          ten: 'Lịch & nhật ký',
          moTa: 'Lên lịch tự tập và ghi lại kết quả thực tế của từng hiệp.',
          icon: 'calendar2-week',
          duongDan: '/dang-ky',
        },
        {
          ten: 'Đồng hành cùng PT',
          moTa: 'Đặt lịch và trao đổi với huấn luyện viên được phân công.',
          icon: 'people',
          duongDan: '/goi-tap',
        },
        {
          ten: 'Chỉ số cơ thể',
          moTa: 'Theo dõi cân nặng, chiều cao và thay đổi qua từng lần đo.',
          icon: 'activity',
          duongDan: '/dang-ky',
        },
        {
          ten: 'Tr0ond AI',
          moTa: 'Hỏi đáp kiến thức tập luyện theo quyền lợi của gói đang dùng.',
          icon: 'robot',
          duongDan: '/goi-tap',
        },
      ],
    }
  },
  methods: {
    // Xử lý hiệu ứng nghiêng 3D điện thoại khi rê chuột trong vùng Hero
    xuLyHoverPhone(e) {
      const rect = e.currentTarget.getBoundingClientRect()
      const x = e.clientX - rect.left - rect.width / 2
      const y = e.clientY - rect.top - rect.height / 2
      this.phoneRotateY = -18 + (x / (rect.width / 2)) * 10
      this.phoneRotateX = 8 - (y / (rect.height / 2)) * 8
    },
    resetHoverPhone() {
      this.phoneRotateY = -18
      this.phoneRotateX = 8
    },
    // Xử lý hiệu ứng nghiêng 3D từng thẻ tính năng (3D Interactive Cards)
    xuLyCardMouseMove(e) {
      const card = e.currentTarget
      const rect = card.getBoundingClientRect()
      const x = e.clientX - rect.left - rect.width / 2
      const y = e.clientY - rect.top - rect.height / 2
      const rotateX = -(y / (rect.height / 2)) * 8
      const rotateY = (x / (rect.width / 2)) * 8
      card.style.transform = `perspective(1000px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) translateY(-4px)`
    },
    resetCard(e) {
      const card = e.currentTarget
      card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateY(0)'
    },
  },
}
</script>
