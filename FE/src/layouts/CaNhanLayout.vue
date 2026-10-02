<template>
  <div class="member-shell">
    <!-- Thanh Header chuẩn Dashboard hiện đại -->
    <header
      class="member-header"
      :class="{ 'co-menu-header': coMenuHeader, 'header-admin': laAdmin }"
    >
      <div class="d-flex align-items-center gap-3">
        <RouterLink to="/" class="brand" aria-label="Huấn luyện cá nhân — trang chủ">
          <span class="brand-mark" aria-hidden="true">H</span>
          <span class="brand-title d-none d-sm-inline">HUẤN LUYỆN CÁ NHÂN</span>
        </RouterLink>
      </div>

      <nav v-if="laAdmin" class="header-navigation" aria-label="Quản trị">
        <RouterLink to="/admin/tong-quan">Tổng quan</RouterLink>
        <RouterLink to="/admin/tai-khoan">Tài khoản</RouterLink>
        <RouterLink to="/admin/ho-so">Hồ sơ của tôi</RouterLink>
        <RouterLink to="/admin/bai-tap">Bài tập</RouterLink>
        <RouterLink to="/admin/nhom-co">Nhóm cơ</RouterLink>
        <RouterLink to="/admin/goi-tap">Gói tập</RouterLink>
        <RouterLink to="/admin/giao-an-mau">Giáo án mẫu</RouterLink>
        <RouterLink
          to="/admin/don-hang"
          :class="{ 'router-link-active': $route.path.startsWith('/admin/don-hang/') }"
          >Đơn hàng</RouterLink
        >
        <RouterLink to="/admin/phan-cong">Phân công PT</RouterLink>
      </nav>
      <nav v-if="laKhach" class="header-navigation" aria-label="Khách hàng">
        <RouterLink to="/khach-hang/tong-quan">Tổng quan</RouterLink>
        <RouterLink to="/khach-hang/ho-so">Hồ sơ của tôi</RouterLink>
        <RouterLink to="/khach-hang/goi-cua-toi">Gói của tôi</RouterLink>
        <RouterLink
          to="/khach-hang/don-hang"
          :class="{ 'router-link-active': $route.path.startsWith('/khach-hang/don-hang/') }"
          >Đơn hàng</RouterLink
        >
        <RouterLink to="/goi-tap">Gói tập</RouterLink>
        <RouterLink to="/bai-tap">Thư viện bài tập</RouterLink>
      </nav>

      <nav aria-label="Tài khoản" class="account-nav">
        <RouterLink v-if="!coMenuHeader" to="/bai-tap" class="btn btn-outline-secondary btn-sm"
          >Bài tập</RouterLink
        >
        <!-- Huy hiệu tài khoản và vai trò -->
        <div class="user-badge-header" v-if="xacThuc.taiKhoan">
          <div class="user-avatar-circle" :class="avatarRoleClass">
            {{ chuCaiDau }}
          </div>
          <div class="d-none d-md-block text-start">
            <div class="fw-bold lh-1 text-truncate" style="max-width: 160px">
              {{ xacThuc.taiKhoan.ho_ten }}
            </div>
            <small class="badge-role mt-1" :class="badgeRoleClass">
              <i :class="bieuTuongVaiTro"></i>
              <span>{{ nhanVaiTro }}</span>
            </small>
          </div>
        </div>

        <!-- Nút điều hướng về khu vực chính -->
        <RouterLink
          v-if="!coMenuHeader"
          :to="xacThuc.duongDanCaNhan"
          class="btn btn-outline-secondary btn-sm d-none d-sm-inline-flex"
        >
          <i class="bi bi-grid-1x2" aria-hidden="true"></i>
          <span>Trang chủ</span>
        </RouterLink>

        <!-- Nút đăng xuất -->
        <button
          class="btn btn-outline-secondary btn-sm"
          :disabled="dangXuat"
          @click="thoat"
          title="Đăng xuất khỏi hệ thống"
        >
          <span v-if="dangXuat" class="spinner-border spinner-border-sm me-1" role="status"></span>
          <i v-else class="bi bi-box-arrow-right me-1"></i>
          <span>{{ dangXuat ? 'Đang thoát…' : 'Đăng xuất' }}</span>
        </button>
      </nav>
    </header>

    <!-- Nội dung chính -->
    <main class="member-content">
      <nav
        v-if="xacThuc.taiKhoan?.vai_tro === 'HUAN_LUYEN_VIEN'"
        class="admin-navigation"
        aria-label="Huấn luyện viên"
      >
        <RouterLink to="/pt/tong-quan">Tổng quan</RouterLink>
        <RouterLink to="/pt/ho-so">Hồ sơ PT</RouterLink>
        <RouterLink to="/pt/giao-an-mau">Giáo án mẫu</RouterLink>
      </nav>
      <div
        v-if="thongBao"
        class="alert alert-danger d-flex align-items-center gap-2 mb-4 rounded-3 animate__animated animate__shakeX"
        role="alert"
      >
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <span>{{ thongBao }}</span>
      </div>
      <slot />
    </main>

    <!-- Chân trang thành viên -->
    <footer class="member-footer">
      <div class="d-flex align-items-center gap-2">
        <span class="status-dot"></span>
        <span>Hệ thống huấn luyện thể hình cá nhân trực tuyến</span>
      </div>
      <div class="text-muted small">Hành trình tập luyện của bạn · Phiên bản 2.0</div>
    </footer>
  </div>
</template>

<script>
import { useXacThucStore } from '../stores/xacThuc'
import { layLoiApi } from '../utils/loiApi'

export default {
  name: 'CaNhanLayout',
  data() {
    return {
      dangXuat: false,
      thongBao: '',
    }
  },
  computed: {
    xacThuc() {
      return useXacThucStore()
    },
    laAdmin() {
      return this.xacThuc.taiKhoan?.vai_tro === 'ADMIN'
    },
    laKhach() {
      return this.xacThuc.taiKhoan?.vai_tro === 'KHACH_HANG'
    },
    coMenuHeader() {
      return this.laAdmin || this.laKhach
    },
    chuCaiDau() {
      const ten = this.xacThuc.taiKhoan?.ho_ten || 'U'
      return ten.trim().charAt(0).toUpperCase()
    },
    nhanVaiTro() {
      const vaiTro = this.xacThuc.taiKhoan?.vai_tro
      return (
        {
          KHACH_HANG: 'Khách hàng',
          HUAN_LUYEN_VIEN: 'Huấn luyện viên',
          ADMIN: 'Quản trị viên',
        }[vaiTro] || 'Thành viên'
      )
    },
    bieuTuongVaiTro() {
      const vaiTro = this.xacThuc.taiKhoan?.vai_tro
      return (
        {
          KHACH_HANG: 'bi bi-person-fill',
          HUAN_LUYEN_VIEN: 'bi bi-award-fill',
          ADMIN: 'bi bi-shield-check',
        }[vaiTro] || 'bi bi-person'
      )
    },
    avatarRoleClass() {
      const vaiTro = this.xacThuc.taiKhoan?.vai_tro
      return (
        {
          ADMIN: 'bg-purple text-light',
          HUAN_LUYEN_VIEN: 'bg-emerald text-light',
          KHACH_HANG: 'bg-blue text-light',
        }[vaiTro] || ''
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
  methods: {
    async thoat() {
      if (this.dangXuat) return
      this.dangXuat = true
      try {
        await this.xacThuc.dangXuat()
        await this.$router.replace('/dang-nhap')
      } catch (loi) {
        this.thongBao = layLoiApi(loi).thongBao
      } finally {
        this.dangXuat = false
      }
    },
  },
}
</script>

<style scoped>
.member-header.co-menu-header {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 24px;
  padding: 16px 32px;
}
.header-admin .brand-title {
  display: none !important;
}
.header-navigation {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  min-width: 0;
}
.header-navigation a {
  display: inline-flex;
  align-items: center;
  padding: 12px;
  min-height: 44px;
  white-space: nowrap;
  color: #475569;
  font-size: 0.875rem;
  font-weight: 650;
  text-decoration: none;
  border-bottom: 3px solid transparent;
}
.header-navigation a:hover {
  color: #047857;
  background: #f0f8f4;
}
.header-navigation a.router-link-active {
  color: #047857;
  border-color: #047857;
}
.header-navigation a:focus-visible {
  outline: 2px solid #047857;
  outline-offset: -2px;
}
.co-menu-header .account-nav {
  width: auto;
  gap: 12px;
}
@media (max-width: 1500px) {
  .member-header.co-menu-header {
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 12px 20px;
    padding: 14px 24px 0;
  }
  .co-menu-header .account-nav {
    grid-column: 2;
    grid-row: 1;
  }
  .header-navigation {
    grid-column: 1 / -1;
    grid-row: 2;
    justify-content: flex-start;
    overflow-x: auto;
  }
  .header-navigation a {
    flex-shrink: 0;
  }
}
@media (max-width: 600px) {
  .member-header.co-menu-header {
    padding: 12px 16px 0;
    gap: 10px;
  }
  .header-navigation a {
    padding: 12px 10px;
  }
}
.account-nav .btn {
  white-space: nowrap;
  min-height: 44px;
}
@media (max-width: 900px) {
  .member-header {
    flex-wrap: nowrap;
    gap: 8px;
    padding: 14px 16px;
  }
  .member-header .brand-title {
    display: none !important;
  }
  .account-nav {
    width: auto;
    gap: 8px;
  }
  .user-badge-header .text-start {
    display: none !important;
  }
  .account-nav .btn {
    padding: 9px 12px;
  }
}
@media (max-width: 600px) {
  .user-badge-header {
    display: none;
  }
}
.admin-navigation {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 28px;
  border-bottom: 1px solid var(--mau-vien);
}
.admin-navigation a {
  padding: 12px 16px;
  min-height: 44px;
  color: #475569;
  font-weight: 650;
  text-decoration: none;
  border-bottom: 3px solid transparent;
}
.admin-navigation a.router-link-active {
  color: #047857;
  border-color: #047857;
}
.admin-navigation a:focus-visible {
  outline: 2px solid #047857;
  outline-offset: 2px;
}
@media (max-width: 800px) {
  .admin-navigation {
    flex-wrap: nowrap;
    overflow-x: auto;
    gap: 4px;
    padding: 4px;
    margin-bottom: 24px;
  }
  .admin-navigation a {
    white-space: nowrap;
    flex-shrink: 0;
    padding: 12px;
  }
}
.user-avatar-circle {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-weight: 700;
  font-size: 0.95rem;
}

.bg-purple {
  background: linear-gradient(135deg, #7c3aed, #a855f7) !important;
}

.bg-emerald {
  background: linear-gradient(135deg, #059669, #10b981) !important;
}

.bg-blue {
  background: linear-gradient(135deg, #2563eb, #3b82f6) !important;
}
</style>
