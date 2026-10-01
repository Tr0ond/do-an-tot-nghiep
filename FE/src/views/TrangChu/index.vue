<template>
  <div class="welcome-page landing-container">
    <!-- Thanh điều hướng cố định phong cách Glassmorphism -->
    <header class="landing-header">
      <RouterLink to="/" class="brand">
        <span class="brand-mark" aria-hidden="true">H</span>
        <span class="brand-title">HUẤN LUYỆN CÁ NHÂN</span>
      </RouterLink>

      <nav class="landing-nav d-none d-md-flex" aria-label="Điều hướng chính">
        <RouterLink to="/bai-tap" class="nav-item-link">Bài tập</RouterLink>
        <RouterLink to="/goi-tap" class="nav-item-link">Bảng giá</RouterLink>
        <a href="#tinh-nang" class="nav-item-link">Tính năng</a>
        <a href="#quy-trinh" class="nav-item-link">Quy trình</a>
        <a href="#loi-ich" class="nav-item-link">Lợi ích</a>
      </nav>

      <div class="landing-header-actions">
        <RouterLink class="btn btn-outline-secondary btn-sm" to="/dang-nhap">
          <i class="bi bi-box-arrow-in-right"></i>
          <span>Đăng nhập</span>
        </RouterLink>
        <RouterLink class="btn btn-primary btn-sm" to="/dang-ky">
          <span>Bắt đầu ngay</span>
          <i class="bi bi-arrow-right"></i>
        </RouterLink>
      </div>
    </header>

    <!-- Phần Hero chính với hiệu ứng bắt mắt -->
    <section class="landing-hero">
      <div class="hero-content">
        <div class="hero-badge animate__animated animate__fadeInDown">
          <i class="bi bi-stars"></i>
          <span>NỀN TẢNG HUẤN LUYỆN THỂ HÌNH 1:1 CHUYÊN NGHIỆP</span>
        </div>

        <h1 class="hero-title animate__animated animate__fadeInUp">
          Bứt phá giới hạn, <br />
          <span class="text-gradient">kiến tạo thể hình</span> cùng HLV riêng.
        </h1>

        <p class="hero-desc animate__animated animate__fadeInUp animate__delay-1s">
          Lộ trình tập luyện và dinh dưỡng được cá nhân hóa hoàn toàn theo thể trạng của bạn. Kết
          nối trực tiếp cùng đội ngũ huấn luyện viên đạt chuẩn quốc tế để chinh phục mục tiêu nhanh
          nhất.
        </p>

        <div class="hero-actions animate__animated animate__fadeInUp animate__delay-1s">
          <RouterLink class="btn btn-outline-secondary btn-lg" to="/goi-tap"
            >Xem gói tập</RouterLink
          >
          <RouterLink class="btn btn-outline-secondary btn-lg" to="/bai-tap">
            <span>Khám phá bài tập</span>
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
          </RouterLink>
          <RouterLink class="btn btn-primary btn-lg" to="/dang-ky">
            <span>Đăng ký khách hàng ngay</span>
            <i class="bi bi-arrow-up-right-circle-fill"></i>
          </RouterLink>
          <RouterLink class="btn btn-outline-secondary btn-lg" to="/dang-nhap">
            <i class="bi bi-shield-lock"></i>
            <span>Đã có tài khoản</span>
          </RouterLink>
        </div>

        <!-- Cam kết nhanh -->
        <div class="hero-proof-list">
          <div class="proof-item">
            <i class="bi bi-check-circle-fill text-success"></i>
            <span>Giáo án độc bản theo mục tiêu</span>
          </div>
          <div class="proof-item">
            <i class="bi bi-check-circle-fill text-success"></i>
            <span>Theo dõi chỉ số liên tục</span>
          </div>
          <div class="proof-item">
            <i class="bi bi-check-circle-fill text-success"></i>
            <span>HLV 1-kèm-1 tận tâm</span>
          </div>
        </div>
      </div>

      <!-- Thẻ mô phỏng trực quan bảng theo dõi thể hình -->
      <div class="hero-visual animate__animated animate__fadeInRight">
        <div class="mockup-card glass-panel">
          <div class="mockup-header">
            <div class="user-pill">
              <div class="avatar-ring">
                <i class="bi bi-person-fill"></i>
              </div>
              <div>
                <strong>Học viên tiêu biểu</strong>
                <span class="d-block small text-muted">Lộ trình: Tăng cơ giảm mỡ</span>
              </div>
            </div>
            <span class="status-pill">
              <span class="status-dot"></span>
              Đang tập luyện
            </span>
          </div>

          <!-- Chỉ số tiến độ giả lập -->
          <div class="mockup-metric-box">
            <div class="metric-top">
              <span>Mục tiêu 90 ngày</span>
              <span class="fw-bold text-success">82% Hoàn thành</span>
            </div>
            <div class="progress" style="height: 10px">
              <div
                class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                role="progressbar"
                style="width: 82%"
              ></div>
            </div>
          </div>

          <!-- Các thẻ tiện ích nổi bật -->
          <div class="mockup-badges-grid">
            <div class="mini-stat-card">
              <i class="bi bi-fire text-danger"></i>
              <div>
                <strong>2,450 kcal</strong>
                <small>Tiêu hao / tuần</small>
              </div>
            </div>
            <div class="mini-stat-card">
              <i class="bi bi-calendar-check text-primary"></i>
              <div>
                <strong>24 buổi</strong>
                <small>Đã hoàn thành</small>
              </div>
            </div>
          </div>

          <!-- Huy hiệu HLV đồng hành -->
          <div class="trainer-attached">
            <div class="trainer-avatar">
              <i class="bi bi-award-fill"></i>
            </div>
            <div>
              <p class="mb-0 fw-bold">HLV Chuyên Nghiệp Đồng Hành</p>
              <small class="text-muted">Đánh giá 5.0 ★ · Chứng chỉ NASM</small>
            </div>
          </div>
        </div>

        <!-- Thẻ floating trang trí -->
        <div class="floating-badge badge-1 anim-float">
          <i class="bi bi-lightning-charge-fill text-warning"></i>
          <span>100% Cá nhân hóa</span>
        </div>
        <div class="floating-badge badge-2 anim-float" style="animation-delay: -3s">
          <i class="bi bi-heart-pulse-fill text-danger"></i>
          <span>Sức khỏe & Vóc dáng</span>
        </div>
      </div>
    </section>

    <!-- Thanh số liệu thống kê tạo độ tin cậy -->
    <section class="stats-banner">
      <div class="stats-container">
        <div class="stat-col">
          <h2 class="stat-num">1,200+</h2>
          <p class="stat-sub">Học viên đã đồng hành</p>
        </div>
        <div class="stat-col">
          <h2 class="stat-num">50+</h2>
          <p class="stat-sub">HLV chứng chỉ quốc tế</p>
        </div>
        <div class="stat-col">
          <h2 class="stat-num">98.5%</h2>
          <p class="stat-sub">Hài lòng về kết quả</p>
        </div>
        <div class="stat-col">
          <h2 class="stat-num">1 - 1</h2>
          <p class="stat-sub">Chăm sóc & theo sát</p>
        </div>
      </div>
    </section>

    <!-- Lợi ích cốt lõi -->
    <section id="tinh-nang" class="features-section">
      <div class="section-title-wrap">
        <span class="eyebrow">TẠI SAO CHỌN CHÚNG TÔI</span>
        <h2 class="section-heading">Trải nghiệm tập luyện chuẩn khoa học</h2>
        <p class="section-subheading">
          Mỗi tính năng được tối ưu để bạn kiên trì và đạt hiệu quả thể chất cao nhất.
        </p>
      </div>

      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon bg-emerald">
            <i class="bi bi-person-lines-fill"></i>
          </div>
          <h3>Lộ trình chuyên biệt</h3>
          <p>
            Thiết kế kế hoạch tập luyện và chế độ ăn uống chuẩn xác theo thể trạng, cơ địa và mục
            tiêu riêng của từng cá nhân.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon bg-blue">
            <i class="bi bi-award-fill"></i>
          </div>
          <h3>Đội ngũ PT kiểm duyệt</h3>
          <p>
            Tất cả huấn luyện viên đều có chứng chỉ nghề nghiệp uy tín, chuyên môn cao và kinh
            nghiệm thực chiến phong phú.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon bg-amber">
            <i class="bi bi-graph-up-arrow"></i>
          </div>
          <h3>Theo dõi tiến độ trực quan</h3>
          <p>
            Cập nhật số đo, cân nặng và nhật ký buổi tập. Nhìn thấy sự tiến bộ rõ rệt qua từng giai
            đoạn tập luyện.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon bg-purple">
            <i class="bi bi-chat-heart-fill"></i>
          </div>
          <h3>Tương tác linh hoạt</h3>
          <p>
            Dễ dàng trao đổi cùng huấn luyện viên để điều chỉnh giáo án, giải đáp thắc mắc và giữ
            vững ngọn lửa động lực.
          </p>
        </div>
      </div>
    </section>

    <!-- Quy trình 3 bước -->
    <section id="quy-trinh" class="process-section">
      <div class="section-title-wrap">
        <span class="eyebrow">QUY TRÌNH THỰC HIỆN</span>
        <h2 class="section-heading">Bắt đầu hành trình chỉ với 3 bước</h2>
      </div>

      <div class="steps-grid">
        <div class="step-card">
          <div class="step-number">01</div>
          <h3>Đăng ký tài khoản</h3>
          <p>
            Điền thông tin và cập nhật mục tiêu thể hình bạn mong muốn đạt được (tăng cơ, giảm mỡ,
            cải thiện sức bền).
          </p>
        </div>
        <div class="step-card">
          <div class="step-number">02</div>
          <h3>Ghép nối Huấn luyện viên</h3>
          <p>
            Nhận phân tích thể trạng và lộ trình từ HLV chuyên môn phù hợp nhất với quỹ thời gian
            của bạn.
          </p>
        </div>
        <div class="step-card">
          <div class="step-number">03</div>
          <h3>Tập luyện & Bứt phá</h3>
          <p>
            Thực hành theo giáo án, ghi nhận tiến độ từng ngày và tận hưởng sự thay đổi tích cực của
            vóc dáng.
          </p>
        </div>
      </div>
    </section>

    <!-- Banner kêu gọi hành động CTA cuối trang -->
    <section class="landing-cta-banner">
      <div class="cta-inner glass-panel">
        <div class="cta-text">
          <h2>Sẵn sàng kiến tạo vóc dáng lý tưởng?</h2>
          <p>
            Đăng ký tài khoản ngay hôm nay để bắt đầu hành trình nâng cao thể chất và sức khỏe cùng
            huấn luyện viên riêng.
          </p>
        </div>
        <div class="cta-action">
          <RouterLink class="btn btn-primary btn-lg" to="/dang-ky">
            <span>Đăng ký khách hàng ngay</span>
            <i class="bi bi-arrow-right"></i>
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- Chân trang -->
    <footer class="landing-footer">
      <div class="footer-brand">
        <div class="brand">
          <span class="brand-mark" aria-hidden="true">H</span>
          <span>HUẤN LUYỆN CÁ NHÂN</span>
        </div>
        <p class="small text-muted mt-2">
          Đồng hành cùng học viên trên con đường chinh phục thể hình và lối sống năng động.
        </p>
      </div>
      <div class="footer-copy">
        <p class="mb-0">© 2026 Quản lý huấn luyện cá nhân. Mọi quyền được bảo lưu.</p>
      </div>
    </footer>
  </div>
</template>

<script>
export default {
  name: 'TrangChu',
}
</script>

<style scoped>
.landing-container {
  overflow-x: hidden;
  background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 25%, #f8fafc 100%);
}

.landing-header {
  padding: 16px 6vw;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  position: sticky;
  top: 0;
  z-index: 100;
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}

.landing-nav {
  gap: 28px;
}

.nav-item-link {
  font-size: 0.95rem;
  font-weight: 600;
  color: #475569;
  text-decoration: none;
  transition: color 0.2s ease;
}

.nav-item-link:hover {
  color: var(--mau-chinh);
}

.landing-header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

/* Hero Section */
.landing-hero {
  padding: 60px 6vw 80px;
  display: grid;
  grid-template-columns: 1.15fr 0.85fr;
  align-items: center;
  gap: 48px;
  position: relative;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  padding: 6px 14px;
  border-radius: var(--bo-goc-tron);
  margin-bottom: 20px;
}

.hero-title {
  font-size: 3.25rem;
  line-height: 1.15;
  font-weight: 800;
  margin-bottom: 20px;
  color: #0f172a;
}

.text-gradient {
  background: linear-gradient(135deg, #059669 0%, #10b981 50%, #06b6d4 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-desc {
  font-size: 1.12rem;
  color: #475569;
  line-height: 1.7;
  margin-bottom: 32px;
  max-width: 580px;
}

.hero-actions {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 36px;
}

.hero-proof-list {
  display: flex;
  gap: 24px;
  flex-wrap: wrap;
}

.proof-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  color: #334155;
}

/* Mockup Card in Hero */
.hero-visual {
  position: relative;
  display: flex;
  justify-content: center;
}

.mockup-card {
  width: 100%;
  max-width: 440px;
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(226, 232, 240, 0.9);
  padding: 28px;
  border-radius: 24px;
  box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
}

.mockup-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.user-pill {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar-ring {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: #ecfdf5;
  color: #059669;
  display: grid;
  place-items: center;
  font-size: 1.2rem;
}

.mockup-metric-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 16px;
  margin-bottom: 20px;
}

.metric-top {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  margin-bottom: 10px;
}

.mockup-badges-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 20px;
}

.mini-stat-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.mini-stat-card i {
  font-size: 1.4rem;
}

.mini-stat-card strong {
  display: block;
  font-size: 0.95rem;
  line-height: 1.2;
}

.mini-stat-card small {
  color: #64748b;
  font-size: 0.75rem;
}

.trainer-attached {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  padding: 12px 16px;
  border-radius: 14px;
}

.trainer-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #059669;
  color: white;
  display: grid;
  place-items: center;
  font-size: 1rem;
}

.floating-badge {
  position: absolute;
  background: white;
  padding: 10px 18px;
  border-radius: var(--bo-goc-tron);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 700;
  font-size: 0.85rem;
  border: 1px solid #e2e8f0;
}

.badge-1 {
  top: -15px;
  left: -20px;
}

.badge-2 {
  bottom: -20px;
  right: -15px;
}

/* Stats Banner */
.stats-banner {
  background: #064e3b;
  color: white;
  padding: 44px 6vw;
}

.stats-container {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
  text-align: center;
}

.stat-col {
  border-right: 1px solid rgba(255, 255, 255, 0.15);
}

.stat-col:last-child {
  border-right: none;
}

.stat-num {
  font-size: 2.5rem;
  font-weight: 800;
  color: #34d399;
  margin-bottom: 4px;
}

.stat-sub {
  color: #cbd5e1;
  font-size: 0.9rem;
  margin: 0;
}

/* Features */
.features-section,
.process-section {
  padding: 80px 6vw;
}

.section-title-wrap {
  text-align: center;
  max-width: 600px;
  margin: 0 auto 52px;
}

.section-heading {
  font-size: 2.25rem;
  margin-top: 10px;
  margin-bottom: 12px;
}

.section-subheading {
  color: #64748b;
  font-size: 1rem;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 28px;
}

.feature-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  padding: 32px 24px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.feature-card:hover {
  transform: translateY(-6px);
  border-color: #cbd5e1;
  box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.08);
}

.feature-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  font-size: 1.5rem;
  margin-bottom: 20px;
}

.bg-emerald {
  background: #ecfdf5;
  color: #059669;
}
.bg-blue {
  background: #eff6ff;
  color: #2563eb;
}
.bg-amber {
  background: #fffbeb;
  color: #d97706;
}
.bg-purple {
  background: #f5f3ff;
  color: #7c3aed;
}

.feature-card h3 {
  font-size: 1.25rem;
  margin-bottom: 12px;
}

.feature-card p {
  color: #64748b;
  font-size: 0.92rem;
  line-height: 1.6;
  margin: 0;
}

/* Steps */
.steps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 32px;
}

.step-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  padding: 36px 28px;
  position: relative;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.step-number {
  font-size: 2.5rem;
  font-weight: 800;
  color: #10b981;
  opacity: 0.3;
  margin-bottom: 12px;
  line-height: 1;
}

.step-card h3 {
  font-size: 1.25rem;
  margin-bottom: 10px;
}

.step-card p {
  color: #64748b;
  font-size: 0.92rem;
  margin: 0;
}

/* CTA */
.landing-cta-banner {
  padding: 40px 6vw 80px;
}

.cta-inner {
  background: linear-gradient(135deg, #064e3b 0%, #047857 100%);
  color: white;
  border-radius: 28px;
  padding: 48px 56px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 32px;
  box-shadow: 0 20px 40px -10px rgba(5, 150, 105, 0.35);
}

.cta-text h2 {
  color: white;
  font-size: 2rem;
  margin-bottom: 8px;
}

.cta-text p {
  color: #a7f3d0;
  font-size: 1.05rem;
  margin: 0;
  max-width: 580px;
}

/* Footer */
.landing-footer {
  padding: 32px 6vw;
  background: #ffffff;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
}

@media (max-width: 992px) {
  .landing-hero {
    grid-template-columns: 1fr;
    padding-top: 40px;
  }
  .hero-visual {
    margin-top: 20px;
  }
  .stats-container {
    grid-template-columns: repeat(2, 1fr);
  }
  .stat-col:nth-child(2) {
    border-right: none;
  }
  .cta-inner {
    flex-direction: column;
    text-align: center;
    padding: 36px 24px;
  }
}

@media (max-width: 640px) {
  .hero-title {
    font-size: 2.25rem;
  }
  .landing-hero {
    padding: 30px 20px 50px;
  }
  .landing-header {
    padding: 14px 20px;
  }
  .stats-container {
    grid-template-columns: 1fr;
  }
  .stat-col {
    border-right: none;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    padding-bottom: 16px;
  }
  .stat-col:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }
  .features-section,
  .process-section {
    padding: 50px 20px;
  }
  .landing-footer {
    flex-direction: column;
    text-align: center;
  }
}
</style>
