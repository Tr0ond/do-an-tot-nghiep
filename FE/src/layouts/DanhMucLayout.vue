<template>
  <CaNhanLayout v-if="coMenuHeader">
    <slot />
    <p v-if="!laGoiTap" class="member-catalog-credit">
      Minh họa bản quyền:
      <a href="https://gymvisual.com/" target="_blank" rel="noopener noreferrer">© Gym visual</a>
    </p>
  </CaNhanLayout>
  <div v-else class="catalog-shell">
    <!-- Header danh mục chuẩn Glassmorphism đồng bộ toàn hệ thống -->
    <header class="catalog-header">
      <div class="d-flex align-items-center gap-3">
        <RouterLink to="/" class="brand" aria-label="Huấn luyện cá nhân — trang chủ">
          <LogoThuongHieu />
          <span class="brand-title">HUẤN LUYỆN CÁ NHÂN</span>
        </RouterLink>
      </div>

      <nav class="catalog-nav" aria-label="Điều hướng danh mục">
        <RouterLink to="/" class="catalog-nav-item d-none d-md-inline-flex">
          <i class="bi bi-house"></i>
          <span>Trang chủ</span>
        </RouterLink>
        <RouterLink to="/bai-tap" class="catalog-nav-item" :class="{ active: !laGoiTap }">
          <i class="bi bi-collection-play-fill"></i>
          <span class="d-none d-lg-inline">Thư viện bài tập</span>
          <span class="d-lg-none">Bài tập</span>
        </RouterLink>
        <RouterLink to="/goi-tap" class="catalog-nav-item" :class="{ active: laGoiTap }"
          >Bảng giá</RouterLink
        >
        <!-- Nút chuyển đổi giao diện Sáng / Tối -->
        <NutChuyenChuDe kich-thuoc="sm" />

        <RouterLink
          class="btn btn-outline-secondary btn-sm"
          :to="xacThuc.daDangNhap ? xacThuc.duongDanCaNhan : '/dang-nhap'"
        >
          <i :class="xacThuc.daDangNhap ? 'bi bi-grid-1x2' : 'bi bi-box-arrow-in-right'"></i>
          <span class="d-none d-lg-inline">{{
            xacThuc.daDangNhap ? 'Tổng quan' : 'Đăng nhập'
          }}</span>
          <span class="d-lg-none">{{ xacThuc.daDangNhap ? 'Tổng quan' : 'Đăng nhập' }}</span>
        </RouterLink>
      </nav>
    </header>

    <!-- Khung nội dung chính -->
    <main id="noi-dung-danh-muc" class="catalog-main">
      <slot />
    </main>

    <!-- Chân trang danh mục -->
    <footer class="catalog-footer">
      <div class="d-flex align-items-center gap-2">
        <span class="status-dot"></span>
        <span>{{
          laGoiTap
            ? 'Dịch vụ huấn luyện cá nhân & tư vấn tập luyện'
            : 'Thư viện vận động & bài tập thể hình trực quan'
        }}</span>
      </div>
      <div v-if="!laGoiTap" class="footer-links-group">
        <span class="text-muted">Minh họa bản quyền:</span>
        <a
          href="https://gymvisual.com/"
          target="_blank"
          rel="noopener noreferrer"
          class="credit-link"
        >
          © Gym visual
        </a>
      </div>
    </footer>
  </div>
</template>

<script>
import { useXacThucStore } from '../stores/xacThuc'
import CaNhanLayout from './CaNhanLayout.vue'
import NutChuyenChuDe from '../components/NutChuyenChuDe.vue'
import LogoThuongHieu from '../components/LogoThuongHieu.vue'

export default {
  name: 'DanhMucLayout',
  components: { CaNhanLayout, NutChuyenChuDe, LogoThuongHieu },
  computed: {
    coMenuHeader() {
      return ['KHACH_HANG', 'HUAN_LUYEN_VIEN', 'ADMIN'].includes(this.xacThuc.taiKhoan?.vai_tro)
    },
    laGoiTap() {
      return this.$route.path.startsWith('/goi-tap')
    },
    xacThuc() {
      return useXacThucStore()
    },
  },
  async mounted() {
    if (!this.xacThuc.daKhoiTao) {
      try {
        await this.xacThuc.taiTaiKhoan()
      } catch {
        // Danh mục vẫn là trang công khai khi không xác minh được session.
      }
    }
  },
}
</script>

<style scoped>
.member-catalog-credit {
  margin: 32px 0 0;
  font-size: 0.85rem;
  color: var(--mau-phu);
}
.member-catalog-credit a {
  color: var(--mau-chinh-dam);
  font-weight: 600;
}
.catalog-shell {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: var(--mau-nen);
}

.catalog-header {
  border-bottom: 1px solid var(--mau-vien);
  background: var(--mau-header-bg, rgba(10, 10, 10, 0.88));
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  position: sticky;
  top: 0;
  z-index: 100;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px max(24px, calc((100vw - 1280px) / 2));
  gap: 20px;
  box-shadow: var(--bong-nhe);
}

.catalog-nav {
  display: flex;
  align-items: center;
  gap: 16px;
}

.catalog-nav-item {
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: var(--bo-goc-md);
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--mau-phu);
  text-decoration: none;
  transition: var(--chuyen-canh-nhanh);
}

.catalog-nav-item:hover {
  color: var(--mau-chu);
  background: var(--mau-the-hover);
}

.catalog-nav-item.active {
  color: var(--mau-chinh);
  background: rgba(244, 91, 32, 0.12);
}

.catalog-main {
  width: min(1280px, calc(100% - 48px));
  margin: 0 auto;
  padding: 36px 0 64px;
  flex: 1;
}

.catalog-footer {
  border-top: 1px solid var(--mau-vien);
  background: var(--mau-footer-bg, #0a0a0a);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 20px max(24px, calc((100vw - 1280px) / 2));
  color: var(--mau-phu);
  font-size: 0.85rem;
}

.footer-links-group {
  display: flex;
  align-items: center;
  gap: 6px;
}

.credit-link {
  color: var(--mau-chinh);
  font-weight: 600;
}

@media (max-width: 1024px) {
  .brand-title {
    display: none;
  }
}

@media (max-width: 640px) {
  .catalog-nav-item i {
    display: none;
  }
  .catalog-nav .btn {
    white-space: nowrap;
  }
  .catalog-nav {
    gap: 6px;
  }
  .catalog-nav-item {
    padding: 8px;
    min-height: 44px;
  }
  .catalog-nav .btn {
    padding: 8px;
    min-height: 44px;
  }
  .catalog-header {
    padding: 14px 20px;
    gap: 8px;
  }
  .brand-title {
    display: none;
  }
  .catalog-main {
    width: calc(100% - 32px);
    padding: 24px 0 48px;
  }
  .catalog-footer {
    flex-direction: column;
    text-align: center;
    padding: 20px;
  }
}
</style>
