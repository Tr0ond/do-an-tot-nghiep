<template>
  <div class="member-shell">
    <!-- Thanh Header chuẩn Dashboard hiện đại -->
    <header class="member-header">
      <div class="d-flex align-items-center gap-3">
        <RouterLink to="/" class="brand">
          <span class="brand-mark" aria-hidden="true">H</span>
          <span class="brand-title d-none d-sm-inline">HUẤN LUYỆN CÁ NHÂN</span>
        </RouterLink>
      </div>

      <nav aria-label="Tài khoản" class="account-nav">
        <RouterLink to="/bai-tap" class="btn btn-outline-secondary btn-sm">Bài tập</RouterLink>
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
          :to="xacThuc.duongDanCaNhan"
          class="btn btn-outline-secondary btn-sm d-none d-sm-inline-flex"
        >
          <i class="bi bi-person-circle"></i>
          <span>Khu vực cá nhân</span>
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
        v-if="xacThuc.taiKhoan?.vai_tro === 'ADMIN'"
        class="admin-navigation"
        aria-label="Quản trị"
      >
        <RouterLink to="/admin/tai-khoan">Tài khoản</RouterLink>
        <RouterLink to="/admin/bai-tap">Quản lý bài tập</RouterLink>
        <RouterLink to="/admin/goi-tap">Quản lý gói tập</RouterLink>
        <RouterLink to="/admin/giao-an-mau">Giáo án mẫu</RouterLink>
      </nav>
      <nav
        v-if="xacThuc.taiKhoan?.vai_tro === 'HUAN_LUYEN_VIEN'"
        class="admin-navigation"
        aria-label="Huấn luyện viên"
      >
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
