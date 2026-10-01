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
