<template>
  <XacThucLayout class="khoi-phuc-layout">
    <!-- Header khôi phục mật khẩu -->
    <div class="auth-card-header mb-3">
      <div class="eyebrow mb-2">
        <i class="bi bi-shield-lock-fill me-1" aria-hidden="true"></i>
        <span>BẢO MẬT TÀI KHOẢN</span>
      </div>
      <h1 class="h2 fw-bold mb-2">{{ laDatLai ? 'Đặt lại mật khẩu' : 'Quên mật khẩu?' }}</h1>
      <p class="text-muted small mb-0">
        {{
          laDatLai
            ? 'Chọn mật khẩu mới để tiếp tục hành trình tập luyện.'
            : 'Nhập email đăng ký để nhận liên kết khôi phục.'
        }}
      </p>
    </div>

    <!-- Thông báo kết quả tác vụ -->
    <div
      v-if="thongBao"
      class="alert d-flex align-items-center gap-2 p-3 mb-4 rounded-3"
      :class="coLoi ? 'alert-danger border-danger-subtle' : 'alert-success border-success-subtle'"
      role="status"
    >
      <i
        :class="
          coLoi
            ? 'bi bi-exclamation-triangle-fill text-danger'
            : 'bi bi-check-circle-fill text-success'
        "
        class="fs-5 flex-shrink-0"
        aria-hidden="true"
      ></i>
      <div class="small fw-semibold flex-grow-1">{{ thongBao }}</div>
      <button
        type="button"
        class="btn-close"
        aria-label="Đóng thông báo"
        @click="thongBao = ''"
      ></button>
    </div>

    <!-- Cảnh báo khi liên kết đặt lại mật khẩu không hợp lệ -->
    <div
      v-if="laDatLai && !tokenHopLe && !daXong"
      class="alert alert-warning p-3 mb-4 rounded-3 border-warning-subtle"
      role="alert"
    >
      <div class="d-flex align-items-start gap-2">
        <i
          class="bi bi-exclamation-circle-fill fs-5 text-warning flex-shrink-0"
          aria-hidden="true"
        ></i>
        <div>
          <div class="fw-semibold small">Liên kết không hợp lệ hoặc đã hết hạn</div>
          <p class="small text-muted mb-0">
            Liên kết không hợp lệ hoặc thiếu thông tin. Hãy yêu cầu liên kết mới.
          </p>
        </div>
      </div>
    </div>

    <!-- Màn hình thành công khi đã đặt lại mật khẩu -->
    <div v-if="daXong" class="success-reset-card text-center p-4 mb-4 rounded-3">
      <div class="success-icon-ring mx-auto mb-3">
        <i class="bi bi-check2-circle fs-1 text-emerald" aria-hidden="true"></i>
      </div>
      <h2 class="h5 fw-bold mb-2">Đã cập nhật mật khẩu thành công!</h2>
      <p class="small text-muted mb-4">
        Mật khẩu tài khoản của bạn đã được thay đổi an toàn. Bạn có thể đăng nhập ngay để tiếp tục
        buổi tập.
      </p>
      <RouterLink to="/dang-nhap" class="btn btn-primary w-100 shadow-sm">
        <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
        <span>Đăng nhập bằng mật khẩu mới</span>
      </RouterLink>
    </div>

    <!-- Biểu mẫu: Quên mật khẩu hoặc Đặt lại mật khẩu -->
    <form v-if="!daXong && (!laDatLai || tokenHopLe)" :aria-busy="dangGui" @submit.prevent="gui">
      <!-- Trường Email -->
      <TruongNhap
        id="email_khoi_phuc"
        v-model="email"
        nhan="Email đăng ký"
        loai="email"
        tu-dong-dien="email"
        bieu-tuong="bi bi-envelope"
        goi-y-nhap="email.cua.ban@domain.com"
        :toi-da="191"
        :loi="loiTruong.email"
        :vo-hieu="dangGui"
      />

      <!-- Gợi ý bảo mật khi ở màn Quên mật khẩu -->
      <div v-if="!laDatLai" class="alert alert-light border p-3 rounded-3 small text-muted mb-3">
        <i class="bi bi-info-circle text-emerald me-1" aria-hidden="true"></i>
        Hệ thống sẽ gửi một liên kết bảo mật có thời hạn đến hòm thư của bạn để thiết lập lại mật
        khẩu an toàn.
      </div>

      <!-- Các trường khi Đặt lại mật khẩu -->
      <template v-if="laDatLai">
        <TruongNhap
          id="mat_khau_moi"
          v-model="password"
          nhan="Mật khẩu mới"
          :loai="hienMatKhau ? 'text' : 'password'"
          tu-dong-dien="new-password"
          bieu-tuong="bi bi-lock"
          :toi-thieu="8"
          :toi-da="72"
          :loi="loiTruong.password"
          :vo-hieu="dangGui"
          goi-y="Ít nhất 8 ký tự, không quá 72 byte."
        />

        <TruongNhap
          id="xac_nhan_mat_khau"
          v-model="password_confirmation"
          nhan="Nhập lại mật khẩu mới"
          :loai="hienMatKhau ? 'text' : 'password'"
          tu-dong-dien="new-password"
          bieu-tuong="bi bi-shield-check"
          :toi-thieu="8"
          :toi-da="72"
          :loi="loiTruong.password_confirmation"
          :vo-hieu="dangGui"
        />

        <label class="hien-mat-khau mb-3 user-select-none">
          <input
            v-model="hienMatKhau"
            type="checkbox"
            class="form-check-input me-2 mt-0"
            :disabled="dangGui"
          />
          <span class="small text-muted">Hiện mật khẩu</span>
        </label>

        <p v-if="loiTruong.token" class="text-danger small" role="alert">
          <i class="bi bi-exclamation-circle-fill me-1" aria-hidden="true"></i>
          {{ loiTruong.token[0] }}
        </p>
      </template>

      <!-- Nút gửi yêu cầu -->
      <button class="btn btn-primary w-100 shadow-sm mb-3" type="submit" :disabled="dangGui">
        <span v-if="dangGui" class="spinner-border spinner-border-sm me-1" role="status"></span>
        <i
          v-else
          :class="laDatLai ? 'bi bi-shield-check me-1' : 'bi bi-send-fill me-1'"
          aria-hidden="true"
        ></i>
        <span>{{
          dangGui ? 'Đang xử lý…' : laDatLai ? 'Lưu mật khẩu mới' : 'Gửi liên kết khôi phục'
        }}</span>
      </button>
    </form>

    <!-- Điều hướng chân form -->
    <div class="auth-foot-links pt-2 border-top">
      <RouterLink
        v-if="laDatLai && !daXong"
        to="/quen-mat-khau"
        class="auth-link mb-2 text-decoration-none"
      >
        <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>
        <span>Yêu cầu liên kết mới</span>
      </RouterLink>

      <RouterLink v-if="!daXong" to="/dang-nhap" class="auth-link text-decoration-none">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
        <span>Về đăng nhập</span>
      </RouterLink>
    </div>
  </XacThucLayout>
</template>

<script>
import XacThucLayout from '../../layouts/XacThucLayout.vue'
import TruongNhap from '../../components/TruongNhap.vue'
import xacThucService from '../../services/xacThucService'
import { layLoiApi } from '../../utils/loiApi'

export default {
  name: 'KhoiPhucMatKhau',
  components: { XacThucLayout, TruongNhap },
  data() {
    return {
      email: '',
      token: '',
      password: '',
      password_confirmation: '',
      hienMatKhau: false,
      dangGui: false,
      thongBao: '',
      loiTruong: {},
      coLoi: false,
      daXong: false,
      lanGui: 0,
      daHuy: false,
    }
  },
  computed: {
    laDatLai() {
      return this.$route.path === '/dat-lai-mat-khau'
    },
    tokenHopLe() {
      return /^[a-f0-9]{64}$/.test(this.token) && !!this.email
    },
  },
  watch: {
    '$route.path'() {
      this.khoiTao()
    },
  },
  mounted() {
    this.khoiTao()
  },
  beforeUnmount() {
    this.daHuy = true
    this.lanGui++
    this.token = ''
    this.password = ''
    this.password_confirmation = ''
  },
  methods: {
    khoiTao() {
      this.lanGui++
      this.dangGui = false
      this.daXong = false
      this.thongBao = ''
      this.loiTruong = {}
      this.password = ''
      this.password_confirmation = ''
      this.token = ''
      this.email = ''
      if (this.laDatLai) {
        const duLieu = new URLSearchParams(this.$route.hash.slice(1))
        this.token = duLieu.get('token') || ''
        this.email = duLieu.get('email') || ''
        // Chỉ giữ token trong bộ nhớ của biểu mẫu, xóa khỏi thanh địa chỉ/lịch sử hiện tại.
        if (this.$route.hash) this.$router.replace({ path: this.$route.path, hash: '', query: {} })
      }
    },
    async gui() {
      if (this.dangGui || this.daXong || (this.laDatLai && !this.tokenHopLe)) return
      const lan = ++this.lanGui
      this.dangGui = true
      this.thongBao = ''
      this.loiTruong = {}
      const datLai = this.laDatLai
      try {
        const duLieu = { email: this.email.trim().toLowerCase() }
        if (datLai)
          Object.assign(duLieu, {
            token: this.token,
            password: this.password,
            password_confirmation: this.password_confirmation,
          })
        const phanHoi = await (datLai
          ? xacThucService.datLaiMatKhau(duLieu)
          : xacThucService.guiLienKet(duLieu))
        if (this.daHuy || lan !== this.lanGui) return
        this.thongBao = phanHoi.message
        this.coLoi = false
        if (datLai) {
          this.daXong = true
          this.token = ''
          this.password = ''
          this.password_confirmation = ''
        }
      } catch (loi) {
        if (this.daHuy || lan !== this.lanGui) return
        const phanHoi = layLoiApi(loi)
        this.thongBao = phanHoi.thongBao
        this.loiTruong = phanHoi.loiTruong
        this.coLoi = true
      } finally {
        if (!this.daHuy && lan === this.lanGui) this.dangGui = false
      }
    },
  },
}
</script>

<style scoped>
.text-emerald {
  color: var(--mau-chinh);
}

.success-reset-card {
  background: var(--mau-chinh-nhat);
  border: 1px solid rgba(5, 150, 105, 0.2);
}

.success-icon-ring {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: rgba(5, 150, 105, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
}

.hien-mat-khau {
  display: inline-flex;
  align-items: center;
  cursor: pointer;
  min-height: 38px;
}

.auth-foot-links {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.auth-link {
  min-height: 40px;
  display: inline-flex;
  align-items: center;
  font-size: 0.88rem;
  color: var(--mau-phu);
  transition: color 0.15s ease;
}

.auth-link:hover {
  color: var(--mau-chinh);
}

.btn-primary {
  background: var(--mau-chinh);
  border-color: var(--mau-chinh);
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background: var(--mau-chinh-dam);
  border-color: var(--mau-chinh-dam);
}

.btn {
  min-height: 46px;
  border-radius: 8px;
  font-weight: 650;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

@media (max-width: 992px) {
  .khoi-phuc-layout {
    grid-template-rows: auto minmax(0, 1fr);
  }
  :deep(.auth-story),
  :deep(.auth-foot) {
    display: none;
  }
  :deep(.auth-intro) {
    padding: 20px 24px;
  }
}
</style>
