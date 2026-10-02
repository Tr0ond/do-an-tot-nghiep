<template>
  <XacThucLayout>
    <!-- Thanh chuyển đổi nhanh giữa Đăng nhập và Đăng ký -->
    <div class="auth-tabs-nav">
      <RouterLink
        to="/dang-nhap"
        class="auth-tab-btn"
        :class="{ active: !laDangKy }"
        role="tab"
        :aria-selected="!laDangKy"
      >
        <i class="bi bi-box-arrow-in-right"></i>
        <span>Đăng nhập</span>
      </RouterLink>
      <RouterLink
        to="/dang-ky"
        class="auth-tab-btn"
        :class="{ active: laDangKy }"
        role="tab"
        :aria-selected="laDangKy"
      >
        <i class="bi bi-person-plus-fill"></i>
        <span>Đăng ký khách hàng</span>
      </RouterLink>
    </div>

    <!-- Tiêu đề biểu mẫu -->
    <div class="mb-4">
      <div class="eyebrow">
        <i :class="laDangKy ? 'bi-person-plus' : 'bi-shield-lock'"></i>
        <span>{{ laDangKy ? 'TẠO TÀI KHOẢN MỚI' : 'CHÀO MỪNG TRỞ LẠI' }}</span>
      </div>
      <h1 class="h3 mb-2 fw-bold">{{ laDangKy ? 'Bắt đầu hành trình' : 'Đăng nhập hệ thống' }}</h1>
      <p class="text-muted small mb-0">
        {{
          laDangKy
            ? 'Đăng ký tài khoản khách hàng để lưu giữ hồ sơ và bắt đầu lộ trình tập luyện.'
            : 'Nhập thông tin xác thực để truy cập hồ sơ và lịch tập của bạn.'
        }}
      </p>
    </div>

    <!-- Thông báo lỗi chung -->
    <div
      v-if="thongBao"
      class="alert alert-danger d-flex align-items-center gap-2 p-3 mb-4 rounded-3 border-danger-subtle animate__animated animate__shakeX"
      role="alert"
      tabindex="-1"
      ref="thongBao"
    >
      <i class="bi bi-exclamation-triangle-fill fs-5 text-danger flex-shrink-0"></i>
      <div class="small fw-semibold">{{ thongBao }}</div>
    </div>

    <!-- Biểu mẫu xác thực -->
    <form @submit.prevent="guiBieuMau" :aria-busy="dangGui" novalidate>
      <!-- Họ và tên (chỉ khi đăng ký) -->
      <TruongNhap
        v-if="laDangKy"
        v-model="duLieu.ho_ten"
        id="ho_ten"
        nhan="Họ và tên học viên"
        goi-y-nhap="Ví dụ: Nguyễn Văn An"
        tu-dong-dien="name"
        bieu-tuong="bi bi-person"
        :loi="loiTruong.ho_ten"
        :vo-hieu="dangGui"
      />

      <!-- Email -->
      <TruongNhap
        v-model="duLieu.email"
        id="email"
        nhan="Địa chỉ Email"
        loai="email"
        goi-y-nhap="name@example.com"
        tu-dong-dien="username"
        bieu-tuong="bi bi-envelope"
        :toi-da="191"
        :loi="loiTruong.email"
        :vo-hieu="dangGui"
      />

      <!-- Mật khẩu -->
      <div class="password-field-wrapper mb-3">
        <TruongNhap
          v-model="duLieu.password"
          id="password"
          nhan="Mật khẩu"
          :loai="hienMatKhau ? 'text' : 'password'"
          goi-y-nhap="Nhập mật khẩu của bạn"
          :tu-dong-dien="laDangKy ? 'new-password' : 'current-password'"
          :toi-thieu="laDangKy ? 8 : undefined"
          :toi-da="72"
          bieu-tuong="bi bi-lock"
          :loi="loiTruong.password"
          :vo-hieu="dangGui"
        />

        <!-- Thanh đánh giá độ mạnh mật khẩu khi đăng ký -->
        <div v-if="laDangKy && duLieu.password" class="password-strength-box mt-2">
          <div class="d-flex justify-content-between small text-muted mb-1">
            <span>Độ mạnh mật khẩu:</span>
            <span :class="mauDoManh">{{ nhanDoManh }}</span>
          </div>
          <div class="progress" style="height: 6px">
            <div
              class="progress-bar transition-all"
              :class="bgDoManh"
              role="progressbar"
              :style="{ width: diemDoManh + '%' }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Nhập lại mật khẩu (chỉ khi đăng ký) -->
      <div v-if="laDangKy" class="mb-3">
        <TruongNhap
          v-model="duLieu.password_confirmation"
          id="password_confirmation"
          nhan="Xác nhận lại mật khẩu"
          :loai="hienMatKhau ? 'text' : 'password'"
          goi-y-nhap="Nhập lại mật khẩu vừa đặt"
          tu-dong-dien="new-password"
          :toi-thieu="8"
          :toi-da="72"
          bieu-tuong="bi bi-shield-check"
          :loi="loiTruong.password_confirmation"
          :vo-hieu="dangGui"
        />
        <!-- Chỉ báo trùng khớp mật khẩu -->
        <div v-if="duLieu.password_confirmation" class="small mt-1">
          <span v-if="matKhauKhop" class="text-success d-flex align-items-center gap-1">
            <i class="bi bi-check-circle-fill"></i> Mật khẩu xác nhận hoàn toàn khớp.
          </span>
          <span v-else class="text-danger d-flex align-items-center gap-1">
            <i class="bi bi-x-circle-fill"></i> Mật khẩu xác nhận chưa khớp.
          </span>
        </div>
      </div>

      <!-- Tùy chọn hiện mật khẩu -->
      <div class="d-flex align-items-center justify-content-between mb-4">
        <label class="form-check m-0">
          <input
            v-model="hienMatKhau"
            class="form-check-input"
            type="checkbox"
            id="hienMatKhauCheck"
          />
          <span class="form-check-label small text-muted">
            <i :class="hienMatKhau ? 'bi-eye-slash' : 'bi-eye'" class="me-1"></i>
            {{ hienMatKhau ? 'Ẩn mật khẩu' : 'Hiện mật khẩu' }}
          </span>
        </label>
      </div>

      <!-- Nút hành động chính -->
      <RouterLink v-if="!laDangKy" to="/quen-mat-khau" class="d-block mb-3"
        >Quên mật khẩu?</RouterLink
      >
      <button class="btn btn-primary w-100 py-2 fs-6 shadow-sm" :disabled="dangGui" type="submit">
        <span
          v-if="dangGui"
          class="spinner-border spinner-border-sm"
          role="status"
          aria-hidden="true"
        ></span>
        <span>{{
          dangGui
            ? 'Đang xử lý thông tin…'
            : laDangKy
              ? 'Tạo tài khoản khách hàng'
              : 'Đăng nhập ngay'
        }}</span>
        <i v-if="!dangGui" class="bi bi-arrow-right"></i>
      </button>
    </form>

    <!-- Chuyển hướng phụ bên dưới -->
    <div class="auth-switch">
      <span>{{ laDangKy ? 'Bạn đã có tài khoản?' : 'Chưa có tài khoản hội viên?' }}</span>
      <RouterLink :to="laDangKy ? '/dang-nhap' : '/dang-ky'">
        {{ laDangKy ? 'Đăng nhập ngay' : 'Đăng ký khách hàng' }}
      </RouterLink>
    </div>
  </XacThucLayout>
</template>

<script>
import XacThucLayout from '../../layouts/XacThucLayout.vue'
import TruongNhap from '../../components/TruongNhap.vue'
import { useXacThucStore } from '../../stores/xacThuc'
import { layLoiApi } from '../../utils/loiApi'

export default {
  name: 'TrangXacThuc',
  components: { XacThucLayout, TruongNhap },
  data() {
    return {
      duLieu: {
        ho_ten: '',
        email: '',
        password: '',
        password_confirmation: '',
      },
      dangGui: false,
      hienMatKhau: false,
      thongBao: '',
      loiTruong: {},
    }
  },
  computed: {
    laDangKy() {
      return this.$route.name === 'dang-ky'
    },
    matKhauKhop() {
      return (
        this.duLieu.password &&
        this.duLieu.password_confirmation &&
        this.duLieu.password === this.duLieu.password_confirmation
      )
    },
    diemDoManh() {
      const pwd = this.duLieu.password || ''
      if (!pwd) return 0
      let diem = 0
      if (pwd.length >= 8) diem += 25
      if (pwd.length >= 12) diem += 15
      if (/[A-Z]/.test(pwd)) diem += 20
      if (/[0-9]/.test(pwd)) diem += 20
      if (/[^A-Za-z0-9]/.test(pwd)) diem += 20
      return Math.min(diem, 100)
    },
    nhanDoManh() {
      if (this.diemDoManh < 30) return 'Yếu (Nên thêm ký tự)'
      if (this.diemDoManh < 65) return 'Trung bình'
      if (this.diemDoManh < 85) return 'Khá an toàn'
      return 'Rất mạnh'
    },
    mauDoManh() {
      if (this.diemDoManh < 30) return 'text-danger fw-semibold'
      if (this.diemDoManh < 65) return 'text-warning fw-semibold'
      return 'text-success fw-semibold'
    },
    bgDoManh() {
      if (this.diemDoManh < 30) return 'bg-danger'
      if (this.diemDoManh < 65) return 'bg-warning'
      return 'bg-success'
    },
  },
  watch: {
    laDangKy() {
      this.duLieu.password = ''
      this.duLieu.password_confirmation = ''
      this.thongBao = ''
      this.loiTruong = {}
    },
  },
  methods: {
    async guiBieuMau() {
      if (this.dangGui) return
      this.dangGui = true
      this.thongBao = ''
      this.loiTruong = {}
      try {
        const xacThuc = useXacThucStore()
        if (this.laDangKy) {
          await xacThuc.dangKy(this.duLieu)
        } else {
          await xacThuc.dangNhap({
            email: this.duLieu.email,
            password: this.duLieu.password,
          })
        }
        this.duLieu.password = ''
        this.duLieu.password_confirmation = ''
        await this.$router.replace(xacThuc.duongDanCaNhan)
      } catch (loi) {
        const { thongBao, loiTruong } = layLoiApi(loi)
        this.thongBao = thongBao
        this.loiTruong = loiTruong
        await this.$nextTick()
        this.$refs.thongBao?.focus()
      } finally {
        this.dangGui = false
      }
    },
  },
}
</script>

<style scoped>
.password-strength-box {
  background: #f8fafc;
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}
.transition-all {
  transition: all 0.3s ease;
}
</style>
