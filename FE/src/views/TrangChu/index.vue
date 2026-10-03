<template>
  <div class="cinematic-site">
    <!-- Thanh điều hướng cố định chuẩn Cinematic Dark (Cao: 64px) -->
    <header class="cinematic-header">
      <div class="d-flex align-items-center gap-3">
        <RouterLink
          to="/"
          class="d-flex align-items-center gap-2 text-decoration-none"
          aria-label="Huấn luyện cá nhân — trang chủ"
        >
          <LogoThuongHieu />
          <span class="cinematic-headline fs-6 d-none d-sm-inline"> HUẤN LUYỆN CÁ NHÂN </span>
        </RouterLink>
      </div>

      <nav class="cinematic-nav-links d-none d-lg-flex" aria-label="Điều hướng chính">
        <RouterLink to="/bai-tap" class="cinematic-link">Bài tập</RouterLink>
        <RouterLink to="/goi-tap" class="cinematic-link">Bảng giá</RouterLink>
        <a href="#tinh-nang" class="cinematic-link">Tính năng</a>
        <a href="#quy-trinh" class="cinematic-link">Quy trình</a>
      </nav>

      <div class="d-flex align-items-center gap-3">
        <!-- Nút chuyển đổi giao diện Sáng / Tối -->
        <NutChuyenChuDe kich-thuoc="sm" />

        <RouterLink to="/dang-nhap" class="cinematic-link d-none d-sm-inline">
          Đăng nhập
        </RouterLink>
        <RouterLink to="/dang-ky" class="cinematic-btn-primary">
          <span>Bắt đầu ngay</span>
          <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </RouterLink>
      </div>
    </header>

    <!-- Phân đoạn Hero 50/50 với Mô phỏng điện thoại 3D và các huy hiệu nổi -->
    <section class="cinematic-hero">
      <!-- Cột trái: Tiêu đề, thông điệp và hành động CTA -->
      <div class="hero-left-content">
        <div class="cinematic-tagline">
          <i class="bi bi-fire me-2" aria-hidden="true"></i>
          <span>NỀN TẢNG HUẤN LUYỆN THỂ HÌNH 1:1 ĐỈNH CAO</span>
        </div>

        <h1 class="cinematic-headline cinematic-hero-h1">
          Bứt phá giới hạn, <br />
          <span class="text-orange-glow">kiến tạo thể hình</span> cùng HLV riêng.
        </h1>

        <p class="cinematic-hero-desc">
          Lộ trình tập luyện và dinh dưỡng được cá nhân hóa hoàn toàn theo thể trạng của bạn. Kết
          nối trực tiếp cùng đội ngũ huấn luyện viên đạt chuẩn quốc tế để chinh phục mục tiêu nhanh
          nhất.
        </p>

        <!-- Cụm nút bấm CTA -->
        <div class="cinematic-cta-cluster">
          <RouterLink to="/dang-ky" class="cinematic-btn-primary cinematic-btn-large">
            <span>Đăng ký khách hàng ngay</span>
            <i class="bi bi-arrow-right fs-5" aria-hidden="true"></i>
          </RouterLink>

          <RouterLink
            to="/bai-tap"
            class="d-flex align-items-center gap-3 text-decoration-none ms-sm-2"
          >
            <div class="cinematic-play-btn">
              <i class="bi bi-play-fill ms-1" aria-hidden="true"></i>
            </div>
            <span class="small fw-semibold cinematic-muted-text">Khám phá bài tập</span>
          </RouterLink>
        </div>

        <!-- Chỉ số cam kết nhanh -->
        <div
          class="d-flex align-items-center gap-4 flex-wrap pt-3 border-top border-secondary-subtle"
        >
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-shield-check text-orange-glow fs-5" aria-hidden="true"></i>
            <span class="small cinematic-muted-text">100% HLV chứng chỉ quốc tế</span>
          </div>
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill text-orange-glow fs-5" aria-hidden="true"></i>
            <span class="small cinematic-muted-text">Theo dõi 1-kèm-1 khoa học</span>
          </div>
        </div>
      </div>

      <!-- Cột phải: Mô phỏng điện thoại 3D tương tác với hình ảnh giải phẫu -->
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

    <!-- Thanh số liệu ấn tượng -->
    <section
      class="py-4 border-top border-bottom border-white-5"
      style="background: rgba(18, 18, 18, 0.6)"
    >
      <div class="container-fluid px-lg-5">
        <div class="row text-center g-4">
          <div class="col-6 col-md-3">
            <div class="cinematic-headline fs-2 text-orange-glow">1,200+</div>
            <p class="small cinematic-muted-text mb-0">Học viên đã đồng hành</p>
          </div>
          <div class="col-6 col-md-3">
            <div class="cinematic-headline fs-2">50+</div>
            <p class="small cinematic-muted-text mb-0">HLV chứng chỉ quốc tế</p>
          </div>
          <div class="col-6 col-md-3">
            <div class="cinematic-headline fs-2 text-orange-glow">98.5%</div>
            <p class="small cinematic-muted-text mb-0">Hài lòng về kết quả</p>
          </div>
          <div class="col-6 col-md-3">
            <div class="cinematic-headline fs-2">1 - 1</div>
            <p class="small cinematic-muted-text mb-0">Kèm cặp & điều chỉnh form</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Lưới tính năng 3x2 với thẻ tương tác nghiêng 3D (3D Interactive Cards) -->
    <section id="tinh-nang" class="cinematic-features-section">
      <div class="text-center max-w-700 mx-auto mb-5">
        <span class="cinematic-tagline mb-2">TẠI SAO CHỌN CHÚNG TÔI</span>
        <h2 class="cinematic-headline fs-1 mt-2">Trải nghiệm tập luyện chuẩn khoa học</h2>
        <p class="cinematic-muted-text">
          Mỗi tính năng được thiết kế tối ưu bằng chuyển động 3D để bạn kiểm soát thể chất toàn
          diện.
        </p>
      </div>

      <div class="cinematic-grid-3x2">
        <!-- Thẻ 1 -->
        <div class="cinematic-card-3d" @mousemove="xuLyCardMouseMove" @mouseleave="resetCard">
          <div class="cinematic-icon-box">
            <i class="bi bi-person-lines-fill" aria-hidden="true"></i>
          </div>
          <h3 class="cinematic-card-title">Lộ trình cá nhân hóa 1:1</h3>
          <p class="cinematic-card-desc">
            Thiết kế giáo án độc bản theo cơ địa, khả năng phục hồi và mục tiêu tăng cơ, giảm mỡ của
            từng học viên.
          </p>
        </div>

        <!-- Thẻ 2 -->
        <div class="cinematic-card-3d" @mousemove="xuLyCardMouseMove" @mouseleave="resetCard">
          <div class="cinematic-icon-box">
            <i class="bi bi-award-fill" aria-hidden="true"></i>
          </div>
          <h3 class="cinematic-card-title">Đội ngũ PT kiểm duyệt</h3>
          <p class="cinematic-card-desc">
            100% huấn luyện viên sở hữu bằng cấp uy tín quốc tế (NASM, ACE), giàu kinh nghiệm chỉnh
            form và động viên kiên trì.
          </p>
        </div>

        <!-- Thẻ 3 -->
        <div class="cinematic-card-3d" @mousemove="xuLyCardMouseMove" @mouseleave="resetCard">
          <div class="cinematic-icon-box">
            <i class="bi bi-diagram-3-fill" aria-hidden="true"></i>
          </div>
          <h3 class="cinematic-card-title">Phân tích giải phẫu nhóm cơ</h3>
          <p class="cinematic-card-desc">
            Minh họa trực quan các nhóm cơ tác động chính và phụ trong từng bài tập giúp tập chuẩn
            xác, tránh chấn thương.
          </p>
        </div>

        <!-- Thẻ 4 -->
        <div class="cinematic-card-3d" @mousemove="xuLyCardMouseMove" @mouseleave="resetCard">
          <div class="cinematic-icon-box">
            <i class="bi bi-activity" aria-hidden="true"></i>
          </div>
          <h3 class="cinematic-card-title">Theo dõi Calo & Nhịp tim</h3>
          <p class="cinematic-card-desc">
            Ghi nhận chính xác lượng calo tiêu thụ, nhịp tim mục tiêu và chuỗi ngày tập luyện streak
            bền vững.
          </p>
        </div>

        <!-- Thẻ 5 -->
        <div class="cinematic-card-3d" @mousemove="xuLyCardMouseMove" @mouseleave="resetCard">
          <div class="cinematic-icon-box">
            <i class="bi bi-calendar2-check-fill" aria-hidden="true"></i>
          </div>
          <h3 class="cinematic-card-title">Lịch hẹn huấn luyện linh hoạt</h3>
          <p class="cinematic-card-desc">
            Chủ động chọn giờ tập 60 phút với PT phụ trách, gửi yêu cầu đặt lịch và cập nhật trạng
            thái ngay trong tài khoản.
          </p>
        </div>

        <!-- Thẻ 6 -->
        <div class="cinematic-card-3d" @mousemove="xuLyCardMouseMove" @mouseleave="resetCard">
          <div class="cinematic-icon-box">
            <i class="bi bi-robot" aria-hidden="true"></i>
          </div>
          <h3 class="cinematic-card-title">Trợ lý AI thể hình 24/7</h3>
          <p class="cinematic-card-desc">
            Giải đáp dinh dưỡng, tính toán macro khẩu phần ăn và tư vấn điều chỉnh thói quen sinh
            hoạt bất kỳ lúc nào.
          </p>
        </div>
      </div>
    </section>

    <!-- Thư viện giao diện ứng dụng xếp vòng cung 3D (App Screenshot Gallery) -->
    <section id="thu-vien-3d" class="cinematic-gallery-section">
      <div class="text-center max-w-700 mx-auto mb-4">
        <span class="cinematic-tagline mb-2">GIAO DIỆN ỨNG DỤNG</span>
        <h2 class="cinematic-headline fs-1">Hệ sinh thái huấn luyện toàn diện</h2>
        <p class="cinematic-muted-text">
          Quan sát trực quan lộ trình, lịch hẹn và bài tập qua giao diện thiết kế chuyên biệt.
        </p>
      </div>

      <!-- Vòng cung 5 màn hình 3D -->
      <div class="gallery-arc-container">
        <!-- Màn 1: Cực trái -->
        <div class="gallery-screen-card screen-arc-far-left">
          <div
            class="p-3 border-bottom border-white-5 d-flex align-items-center justify-content-between"
          >
            <span class="small fw-bold cinematic-muted-text">Thư viện bài tập</span>
            <i class="bi bi-collection-play text-orange-glow"></i>
          </div>
          <div class="p-3">
            <div class="p-2 mb-2 rounded cinematic-screen-inner-card border">
              <span class="d-block small fw-bold">Kéo xô hẹp tay</span>
              <span class="small cinematic-muted-text font-monospace">4 hiệp · 12 lần</span>
            </div>
            <div class="p-2 rounded cinematic-screen-inner-card border">
              <span class="d-block small fw-bold">Chèo tạ đơn</span>
              <span class="small cinematic-muted-text font-monospace">4 hiệp · 10 lần</span>
            </div>
          </div>
        </div>

        <!-- Màn 2: Trái -->
        <div class="gallery-screen-card screen-arc-left">
          <div
            class="p-3 border-bottom border-white-5 d-flex align-items-center justify-content-between"
          >
            <span class="small fw-bold cinematic-muted-text">Giáo án mẫu</span>
            <i class="bi bi-journal-check text-orange-glow"></i>
          </div>
          <div class="p-3">
            <span class="badge bg-danger-subtle text-danger mb-2">Nâng cao</span>
            <h4 class="fs-6 fw-bold mb-1">Hypertrophy Upper</h4>
            <p class="small cinematic-muted-text">
              Phát triển cơ lưng xô & ngực toàn diện trong 8 tuần.
            </p>
          </div>
        </div>

        <!-- Màn 3: Trung tâm nổi bật -->
        <div class="gallery-screen-card screen-arc-center">
          <div
            class="p-3 border-bottom border-warning-subtle d-flex align-items-center justify-content-between"
          >
            <span class="small fw-bold text-orange-glow">Đang tập luyện</span>
            <span class="spinner-grow spinner-grow-sm text-danger" role="status"></span>
          </div>
          <div
            class="p-3 text-center flex-grow-1 d-flex flex-direction-column justify-content-center"
          >
            <div class="my-auto">
              <i class="bi bi-heart-pulse-fill text-danger fs-1 mb-2 d-block"></i>
              <div class="cinematic-headline fs-2">124 BPM</div>
              <p class="small text-orange-glow font-monospace mb-3">847 KCAL TIÊU HAO</p>
              <div
                class="p-2 rounded cinematic-screen-inner-card border small cinematic-muted-text"
              >
                Hiệp 3/4 · Nghỉ 60s
              </div>
            </div>
          </div>
        </div>

        <!-- Màn 4: Phải -->
        <div class="gallery-screen-card screen-arc-right">
          <div
            class="p-3 border-bottom border-white-5 d-flex align-items-center justify-content-between"
          >
            <span class="small fw-bold cinematic-muted-text">Lịch huấn luyện</span>
            <i class="bi bi-calendar-event text-orange-glow"></i>
          </div>
          <div class="p-3">
            <div class="p-2 mb-2 rounded cinematic-screen-inner-card border">
              <span class="badge bg-success-subtle text-success small mb-1">Đã xác nhận</span>
              <strong class="d-block small">08:00 - 09:00</strong>
              <span class="small cinematic-muted-text">HLV Nguyễn Minh Tuấn</span>
            </div>
          </div>
        </div>

        <!-- Màn 5: Cực phải -->
        <div class="gallery-screen-card screen-arc-far-right">
          <div
            class="p-3 border-bottom border-white-5 d-flex align-items-center justify-content-between"
          >
            <span class="small fw-bold cinematic-muted-text">Gói tập cá nhân</span>
            <i class="bi bi-award text-orange-glow"></i>
          </div>
          <div class="p-3">
            <span class="badge bg-warning-subtle text-warning small mb-1">12 Buổi PT</span>
            <h4 class="fs-6 fw-bold mb-1">Gói Chiến Binh</h4>
            <p class="small cinematic-muted-text">Kèm chatbot AI 24/7 và đối soát payOS tức thì.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Quy trình 3 bước -->
    <section id="quy-trinh" class="py-5 cinematic-steps-section">
      <div class="container py-4">
        <div class="text-center mb-5">
          <span class="cinematic-tagline mb-2">QUY TRÌNH TINH GỌN</span>
          <h2 class="cinematic-headline fs-1">Bắt đầu chỉ trong 3 bước</h2>
        </div>
        <div class="row g-4">
          <div class="col-md-4">
            <div class="p-4 rounded-4 border cinematic-card-box h-100 position-relative">
              <span class="cinematic-headline fs-1 text-orange-glow opacity-50 d-block mb-3"
                >01</span
              >
              <h3 class="fs-5 fw-bold mb-2">Đăng ký & Chọn mục tiêu</h3>
              <p class="small cinematic-muted-text mb-0">
                Tạo tài khoản và cập nhật mong muốn thể chất: Tăng cơ bắp, giảm mỡ thừa hoặc nâng
                cao sức bền.
              </p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-4 rounded-4 border cinematic-card-box h-100 position-relative">
              <span class="cinematic-headline fs-1 text-orange-glow opacity-50 d-block mb-3"
                >02</span
              >
              <h3 class="fs-5 fw-bold mb-2">Kết nối Huấn luyện viên</h3>
              <p class="small cinematic-muted-text mb-0">
                Admin phân công PT phù hợp nhất, xây dựng lộ trình tập luyện và lên lịch rảnh 60
                phút mỗi buổi.
              </p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-4 rounded-4 border cinematic-card-box h-100 position-relative">
              <span class="cinematic-headline fs-1 text-orange-glow opacity-50 d-block mb-3"
                >03</span
              >
              <h3 class="fs-5 fw-bold mb-2">Tập luyện & Bứt phá</h3>
              <p class="small cinematic-muted-text mb-0">
                Thực hiện từng bài tập theo đúng form giải phẫu, kiểm soát calo và đạt hình thể mơ
                ước.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Phân đoạn Kêu gọi Hành động (CTA Transformation Section với Glow 600px) -->
    <section class="cinematic-cta-section">
      <div class="cta-glow-backdrop" aria-hidden="true"></div>

      <span class="cinematic-tagline position-relative z-1 mb-3">SẴN SÀNG CHINH PHỤC</span>
      <h2 class="cinematic-headline cinematic-cta-h2">
        Bắt đầu hành trình <br />
        <span class="text-orange-glow">chuyển mình vượt bậc</span> ngay hôm nay.
      </h2>
      <p class="cinematic-muted-text position-relative z-1 max-w-600 mx-auto">
        Trở thành phiên bản mạnh mẽ, săn chắc và khỏe khoắn hơn với sự đồng hành 1:1 từ các chuyên
        gia thể hình hàng đầu.
      </p>

      <div class="cinematic-cta-buttons">
        <RouterLink to="/dang-ky" class="cinematic-btn-primary cinematic-btn-large">
          <i class="bi bi-fire fs-5" aria-hidden="true"></i>
          <span>Bắt đầu ngay hôm nay</span>
        </RouterLink>
        <RouterLink to="/goi-tap" class="cinematic-btn-secondary cinematic-btn-large">
          <i class="bi bi-tag-fill me-1" aria-hidden="true"></i>
          <span>Bảng giá gói tập</span>
        </RouterLink>
        <RouterLink to="/dang-nhap" class="cinematic-btn-secondary cinematic-btn-large">
          <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
          <span>Đăng nhập</span>
        </RouterLink>
      </div>
    </section>

    <!-- Chân trang phong cách Dark Cinematic -->
    <footer class="cinematic-footer">
      <div class="d-flex align-items-center gap-2">
        <LogoThuongHieu />
        <span class="cinematic-headline fs-6">HUẤN LUYỆN CÁ NHÂN</span>
      </div>
      <div class="d-flex align-items-center gap-4 text-muted small flex-wrap">
        <RouterLink to="/bai-tap" class="cinematic-link">Thư viện bài tập</RouterLink>
        <RouterLink to="/goi-tap" class="cinematic-link">Bảng giá dịch vụ</RouterLink>
        <RouterLink to="/dang-nhap" class="cinematic-link">Đăng nhập</RouterLink>
        <RouterLink to="/dang-ky" class="cinematic-link">Đăng ký khách hàng</RouterLink>
      </div>
      <p class="small text-muted mb-0 w-100 text-center text-md-end mt-2 mt-md-0">
        © 2026 Quản lý huấn luyện cá nhân · Cinematic Dark 3D Edition.
      </p>
    </footer>
  </div>
</template>

<script>
import '../../assets/styles/cinematicDark.css'
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
