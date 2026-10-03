<template>
  <CaNhanLayout>
    <!-- Tiêu đề trang -->
    <div class="page-heading mb-4">
      <div>
        <div class="eyebrow">
          <i class="bi bi-person-gear me-1" aria-hidden="true"></i>
          <span>HỒ SƠ CỦA TÔI</span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
          <h1 class="h2 fw-bold mb-0">Cập nhật hồ sơ</h1>
          <span class="badge-role" :class="layClassHuyHieu">
            <i :class="layBieuTuongVaiTro"></i>
            <span>{{ nhanVaiTro }}</span>
          </span>
        </div>
        <p class="text-muted small mb-0">
          {{
            laKhach
              ? 'Thông tin giúp huấn luyện viên hiểu rõ mục tiêu và sắp xếp lịch tập tối ưu cho bạn.'
              : laPt
                ? 'Chia sẻ chuyên môn, chứng chỉ và phong cách huấn luyện của bạn với học viên.'
                : 'Cập nhật tên hiển thị cho tài khoản quản trị viên của bạn.'
          }}
        </p>
      </div>
      <RouterLink :to="duongDanHoSo" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Về hồ sơ
      </RouterLink>
    </div>

    <!-- Bố cục chỉnh sửa hồ sơ: Form chính + Sidebar -->
    <div class="ho-so-editor">
      <!-- Cột trái: Form chỉnh sửa -->
      <form
        class="profile-panel shadow-sm"
        :aria-busy="dangLuu || dangTai"
        @submit.prevent="luuHoSo"
      >
        <!-- Thông báo kết quả -->
        <div
          v-if="thongBao"
          class="alert d-flex align-items-center gap-2 p-3 mb-4 rounded-3"
          :class="
            coLoi ? 'alert-danger border-danger-subtle' : 'alert-success border-success-subtle'
          "
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

        <!-- Cảnh báo xung đột phiên bản (409) -->
        <div
          v-if="xungDot"
          class="alert alert-warning p-3 mb-4 rounded-3 border-warning-subtle"
          role="alert"
        >
          <div class="d-flex align-items-start gap-2">
            <i
              class="bi bi-exclamation-circle-fill fs-5 text-warning flex-shrink-0"
              aria-hidden="true"
            ></i>
            <div>
              <div class="fw-semibold small">Phát hiện dữ liệu hồ sơ mới hơn trên hệ thống</div>
              <p class="small text-muted mb-2">
                Bản nhập của bạn được giữ lại. Tải lại sẽ thay bản nhập bằng hồ sơ mới nhất.
              </p>
              <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                :disabled="dangTai"
                @click="taiLai"
              >
                <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Tải lại hồ sơ
              </button>
            </div>
          </div>
        </div>

        <!-- Cảnh báo phiên đăng nhập hết hạn -->
        <div v-if="canDangNhap" class="alert alert-danger p-3 mb-4 rounded-3" role="alert">
          <div class="d-flex align-items-center justify-content-between gap-2">
            <span class="small fw-semibold">
              <i class="bi bi-shield-exclamation me-1" aria-hidden="true"></i>
              Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại để tiếp tục.
            </span>
            <RouterLink to="/dang-nhap" class="btn btn-primary btn-sm">Đăng nhập lại</RouterLink>
          </div>
        </div>

        <fieldset :disabled="dangLuu || dangTai || canDangNhap" class="border-0 p-0 m-0">
          <!-- Phần 1: Thông tin cơ bản -->
          <div class="section-block mb-4">
            <div class="section-header mb-3">
              <i class="bi bi-person-lines-fill text-emerald me-2" aria-hidden="true"></i>
              <h2 class="h6 fw-bold mb-0">Thông tin cơ bản</h2>
            </div>

            <TruongNhap
              id="ho_ten"
              v-model="bieuMau.ho_ten"
              nhan="Họ và tên"
              tu-dong-dien="name"
              bieu-tuong="bi bi-person"
              :loi="loiTruong.ho_ten"
            />

            <!-- Ngày sinh & Giới tính dành cho Khách hàng -->
            <div v-if="laKhach" class="truong-doi">
              <TruongNhap
                id="ngay_sinh"
                v-model="bieuMau.ngay_sinh"
                nhan="Ngày sinh"
                loai="date"
                :bat-buoc="false"
                bieu-tuong="bi bi-calendar-event"
                :loi="loiTruong.ngay_sinh"
              />

              <div class="mb-3">
                <label for="gioi_tinh" class="form-label d-flex align-items-center gap-1">
                  <span>Giới tính</span>
                </label>
                <div class="input-group-modern">
                  <span class="input-icon-prefix" aria-hidden="true">
                    <i class="bi bi-gender-ambiguous"></i>
                  </span>
                  <select
                    id="gioi_tinh"
                    v-model="bieuMau.gioi_tinh"
                    class="form-select has-prefix"
                    :class="{ 'is-invalid': !!loiTruong.gioi_tinh }"
                    :aria-invalid="!!loiTruong.gioi_tinh"
                    :aria-describedby="loiTruong.gioi_tinh ? 'gioi-tinh-loi' : undefined"
                  >
                    <option value="">Chưa cung cấp</option>
                    <option value="NAM">Nam</option>
                    <option value="NU">Nữ</option>
                    <option value="KHAC">Khác</option>
                  </select>
                </div>
                <p
                  v-if="loiTruong.gioi_tinh"
                  id="gioi-tinh-loi"
                  class="invalid-feedback d-block mt-1"
                >
                  <i class="bi bi-exclamation-circle-fill me-1" aria-hidden="true"></i>
                  {{ loiTruong.gioi_tinh[0] }}
                </p>
              </div>
            </div>
          </div>

          <!-- Phần 2: Dành riêng cho Khách hàng (Mục tiêu & Lịch tập) -->
          <template v-if="laKhach">
            <div class="section-block mb-4">
              <div class="section-header mb-3">
                <i class="bi bi-bullseye text-emerald me-2" aria-hidden="true"></i>
                <h2 class="h6 fw-bold mb-0">Mục tiêu & Kế hoạch tập luyện</h2>
              </div>

              <TruongNhap
                id="muc_tieu"
                v-model="bieuMau.muc_tieu"
                nhan="Mục tiêu tập luyện"
                :bat-buoc="false"
                goi-y-nhap="Ví dụ: Giảm mỡ, tăng cơ, cải thiện sức bền tim mạch"
                bieu-tuong="bi bi-flag"
                :loi="loiTruong.muc_tieu"
              />

              <TruongNhap
                id="kinh_nghiem"
                v-model="bieuMau.kinh_nghiem"
                nhan="Kinh nghiệm tập luyện"
                :bat-buoc="false"
                goi-y-nhap="Ví dụ: Chưa từng tập, đã tập gym 6 tháng, quen dùng tạ tự do..."
                bieu-tuong="bi bi-trophy"
                :loi="loiTruong.kinh_nghiem"
              />

              <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <label for="thoi_gian" class="form-label mb-0 fw-semibold">
                    Khung thời gian có thể tập
                  </label>
                  <span class="badge bg-light text-muted border small font-monospace">
                    {{ soDongThoiGian }} / 14 khung giờ
                  </span>
                </div>
                <textarea
                  id="thoi_gian"
                  v-model="thoiGianNhap"
                  class="form-control font-monospace"
                  :class="{ 'is-invalid': !!loiThoiGian.length }"
                  rows="4"
                  :aria-invalid="!!loiThoiGian.length"
                  aria-describedby="thoi-gian-goi-y thoi-gian-loi"
                  placeholder="Thứ hai 18:00 - 19:30&#10;Thứ tư 18:00 - 19:30&#10;Thứ bảy 07:00 - 08:30"
                ></textarea>
                <p id="thoi-gian-goi-y" class="form-text text-muted small mt-1">
                  <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                  Mỗi dòng một khung giờ, tối đa 14 dòng và 120 ký tự mỗi dòng. Ví dụ: Thứ hai,
                  18:00–19:00.
                </p>
                <p
                  v-if="loiThoiGian.length"
                  id="thoi-gian-loi"
                  class="invalid-feedback d-block mt-1"
                >
                  <i class="bi bi-exclamation-circle-fill me-1" aria-hidden="true"></i>
                  {{ loiThoiGian[0] }}
                </p>
              </div>
            </div>
          </template>

          <!-- Phần 3: Dành riêng cho PT (Chuyên môn & Giới thiệu) -->
          <template v-if="laPt">
            <div class="section-block mb-4">
              <div class="section-header mb-3">
                <i class="bi bi-award text-emerald me-2" aria-hidden="true"></i>
                <h2 class="h6 fw-bold mb-0">Hồ sơ chuyên môn & Năng lực</h2>
              </div>

              <TruongNhap
                id="chuyen_mon"
                v-model="bieuMau.chuyen_mon"
                nhan="Chuyên môn huấn luyện"
                :bat-buoc="false"
                goi-y-nhap="Ví dụ: Huấn luyện thể hình chuyên sâu, phục hồi chức năng, cardio"
                bieu-tuong="bi bi-patch-check"
                :loi="loiTruong.chuyen_mon"
              />

              <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <label for="gioi_thieu" class="form-label mb-0 fw-semibold">
                    Giới thiệu bản thân
                  </label>
                  <span class="badge bg-light text-muted border small">
                    {{ (bieuMau.gioi_thieu || '').length }} / 5000 ký tự
                  </span>
                </div>
                <textarea
                  id="gioi_thieu"
                  v-model="bieuMau.gioi_thieu"
                  rows="6"
                  maxlength="5000"
                  class="form-control"
                  :class="{ 'is-invalid': !!loiTruong.gioi_thieu }"
                  :aria-invalid="!!loiTruong.gioi_thieu"
                  :aria-describedby="loiTruong.gioi_thieu ? 'gioi-thieu-loi' : undefined"
                  placeholder="Chia sẻ kinh nghiệm làm việc, bằng cấp, chứng chỉ và phương pháp huấn luyện để học viên an tâm lựa chọn..."
                ></textarea>
                <p
                  v-if="loiTruong.gioi_thieu"
                  id="gioi-thieu-loi"
                  class="invalid-feedback d-block mt-1"
                >
                  <i class="bi bi-exclamation-circle-fill me-1" aria-hidden="true"></i>
                  {{ loiTruong.gioi_thieu[0] }}
                </p>
              </div>
            </div>
          </template>
        </fieldset>

        <!-- Thao tác lưu / hủy -->
        <div class="d-flex gap-2 flex-wrap pt-3 border-top mt-4">
          <button
            class="btn btn-primary shadow-sm"
            type="submit"
            :disabled="dangLuu || dangTai || xungDot || canDangNhap"
          >
            <span v-if="dangLuu" class="spinner-border spinner-border-sm me-1" role="status"></span>
            <i v-else class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>
            <span>{{ dangLuu ? 'Đang lưu…' : 'Lưu hồ sơ' }}</span>
          </button>
          <RouterLink :to="duongDanHoSo" class="btn btn-outline-secondary"> Hủy </RouterLink>
        </div>
      </form>

      <!-- Cột phải: Sidebar thẻ tài khoản & Lưu ý an toàn -->
      <aside class="profile-sidebar">
        <!-- Thẻ thông tin tài khoản -->
        <div class="profile-panel shadow-sm mb-3">
          <div class="eyebrow mb-2">
            <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
            <span>TÀI KHOẢN HỆ THỐNG</span>
          </div>

          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="sidebar-avatar" :class="layClassAvatar">
              {{ chuCaiDau }}
            </div>
            <div class="sidebar-user-info">
              <h2 class="h6 fw-bold mb-1 text-body">
                {{ xacThuc.taiKhoan?.ho_ten || 'Chưa đặt tên' }}
              </h2>
              <span class="status-pill status-active">
                <span class="status-dot dot-active"></span>
                <span>Hoạt động</span>
              </span>
            </div>
          </div>

          <div class="sidebar-detail-item mb-2">
            <span class="detail-label"><i class="bi bi-envelope me-1 text-muted"></i>Email:</span>
            <span class="detail-value email-ho-so">{{ xacThuc.taiKhoan?.email }}</span>
          </div>

          <div class="sidebar-detail-item mb-3">
            <span class="detail-label"
              ><i class="bi bi-person-badge me-1 text-muted"></i>Vai trò:</span
            >
            <span class="detail-value">{{ nhanVaiTro }}</span>
          </div>

          <hr class="my-3 opacity-25" />

          <div class="alert alert-light border p-2 rounded-2 small text-muted mb-0">
            <i class="bi bi-info-circle text-emerald me-1" aria-hidden="true"></i>
            Email và vai trò do hệ thống quản lý. Thay đổi hồ sơ không làm thay đổi gói tập hoặc
            lịch sử của bạn.
          </div>
        </div>

        <!-- Thẻ mẹo hoàn thiện hồ sơ -->
        <div class="profile-panel shadow-sm">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-lightbulb-fill text-amber" aria-hidden="true"></i>
            <h3 class="h6 fw-bold mb-0">Mẹo hoàn thiện hồ sơ</h3>
          </div>
          <p class="small text-muted mb-0">
            {{
              laKhach
                ? 'Ghi rõ mục tiêu thể hình và các khung giờ rảnh để huấn luyện viên dễ dàng thiết kế giáo án phù hợp với thể trạng của bạn.'
                : laPt
                  ? 'Bổ sung đầy đủ chứng chỉ và kinh nghiệm giúp bạn tạo dựng sự uy tín và thu hút học viên đăng ký gói tập nhanh chóng.'
                  : 'Tên hiển thị sẽ xuất hiện trong các tác vụ kiểm duyệt giáo án, bài tập và quản lý toàn bộ hệ thống.'
            }}
          </p>
        </div>
      </aside>
    </div>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../../layouts/CaNhanLayout.vue'
import TruongNhap from '../../../components/TruongNhap.vue'
import { useXacThucStore } from '../../../stores/xacThuc'
import xacThucService from '../../../services/xacThucService'
import { layLoiApi } from '../../../utils/loiApi'

export default {
  name: 'SuaHoSoCaNhan',
  components: { CaNhanLayout, TruongNhap },
  data() {
    return {
      bieuMau: {},
      thoiGianNhap: '',
      phienBan: null,
      dangLuu: false,
      dangTai: false,
      thongBao: '',
      loiTruong: {},
      coLoi: false,
      xungDot: false,
      canDangNhap: false,
      daHuy: false,
      lanTai: 0,
    }
  },
  computed: {
    xacThuc() {
      return useXacThucStore()
    },
    laKhach() {
      return this.xacThuc.taiKhoan?.vai_tro === 'KHACH_HANG'
    },
    laPt() {
      return this.xacThuc.taiKhoan?.vai_tro === 'HUAN_LUYEN_VIEN'
    },
    duongDanHoSo() {
      return this.$route.path.replace(/\/sua$/, '')
    },
    loiThoiGian() {
      return Object.entries(this.loiTruong)
        .filter(([ten]) => ten.startsWith('thoi_gian_co_the_tap'))
        .flatMap(([, loi]) => loi)
    },
    nhanVaiTro() {
      return (
        {
          ADMIN: 'Quản trị viên',
          HUAN_LUYEN_VIEN: 'Huấn luyện viên',
          KHACH_HANG: 'Khách hàng',
        }[this.xacThuc.taiKhoan?.vai_tro] || 'Thành viên'
      )
    },
    chuCaiDau() {
      return this.xacThuc.taiKhoan?.ho_ten
        ? this.xacThuc.taiKhoan.ho_ten.charAt(0).toUpperCase()
        : 'U'
    },
    soDongThoiGian() {
      return this.thoiGianNhap
        ? this.thoiGianNhap
            .split('\n')
            .map((d) => d.trim())
            .filter(Boolean).length
        : 0
    },
    layBieuTuongVaiTro() {
      return (
        {
          ADMIN: 'bi bi-shield-check',
          HUAN_LUYEN_VIEN: 'bi bi-award-fill',
          KHACH_HANG: 'bi bi-person-fill',
        }[this.xacThuc.taiKhoan?.vai_tro] || 'bi bi-person'
      )
    },
    layClassHuyHieu() {
      return (
        {
          ADMIN: 'badge-role-admin',
          HUAN_LUYEN_VIEN: 'badge-role-pt',
          KHACH_HANG: 'badge-role-khach',
        }[this.xacThuc.taiKhoan?.vai_tro] || ''
      )
    },
    layClassAvatar() {
      return (
        {
          ADMIN: 'avatar-purple',
          HUAN_LUYEN_VIEN: 'avatar-emerald',
          KHACH_HANG: 'avatar-blue',
        }[this.xacThuc.taiKhoan?.vai_tro] || 'avatar-gray'
      )
    },
  },
  mounted() {
    this.napHoSo(this.xacThuc.taiKhoan)
  },
  beforeUnmount() {
    this.daHuy = true
    this.lanTai++
  },
  methods: {
    napHoSo(taiKhoan) {
      this.bieuMau = { ho_ten: taiKhoan?.ho_ten || '' }
      const hoSo = taiKhoan?.[this.laKhach ? 'ho_so_khach_hang' : 'ho_so_huan_luyen_vien'] || {}
      const truong = this.laKhach
        ? ['muc_tieu', 'kinh_nghiem', 'ngay_sinh', 'gioi_tinh']
        : this.laPt
          ? ['chuyen_mon', 'gioi_thieu']
          : []
      for (const ten of truong) this.bieuMau[ten] = hoSo[ten] || ''
      this.thoiGianNhap = Array.isArray(hoSo.thoi_gian_co_the_tap)
        ? hoSo.thoi_gian_co_the_tap.join('\n')
        : ''
      this.phienBan = taiKhoan?.updated_at ?? null
      this.xungDot = false
      this.canDangNhap = false
      this.loiTruong = {}
    },
    duLieuGui() {
      const duLieu = { ho_ten: this.bieuMau.ho_ten.trim(), updated_at: this.phienBan }
      const truong = this.laKhach
        ? ['muc_tieu', 'kinh_nghiem', 'gioi_tinh', 'ngay_sinh']
        : this.laPt
          ? ['chuyen_mon', 'gioi_thieu']
          : []
      for (const ten of truong) duLieu[ten] = this.bieuMau[ten]?.trim() || null
      if (this.laKhach)
        duLieu.thoi_gian_co_the_tap = this.thoiGianNhap
          .split('\n')
          .map((dong) => dong.trim())
          .filter(Boolean)
      return duLieu
    },
    async luuHoSo() {
      if (this.dangLuu || this.dangTai || this.xungDot || this.canDangNhap) return
      this.dangLuu = true
      this.thongBao = ''
      this.loiTruong = {}
      try {
        const phanHoi = await xacThucService.suaHoSo(this.duLieuGui())
        if (this.daHuy) return
        this.xacThuc.taiKhoan = phanHoi.data
        this.napHoSo(phanHoi.data)
        this.thongBao = phanHoi.message
        this.coLoi = false
      } catch (loi) {
        if (!this.daHuy) this.baoLoi(loi)
      } finally {
        if (!this.daHuy) this.dangLuu = false
      }
    },
    baoLoi(loi) {
      const phanHoi = layLoiApi(loi)
      this.thongBao = phanHoi.thongBao
      this.loiTruong = phanHoi.loiTruong
      this.coLoi = true
      this.xungDot = loi.response?.status === 409
      this.canDangNhap = [401, 403, 419].includes(loi.response?.status)
    },
    async taiLai() {
      if (this.dangTai || this.dangLuu) return
      const lan = ++this.lanTai
      this.dangTai = true
      try {
        const phanHoi = await xacThucService.taiTaiKhoan()
        if (this.daHuy || lan !== this.lanTai) return
        this.xacThuc.taiKhoan = phanHoi.data
        this.napHoSo(phanHoi.data)
        this.thongBao = 'Đã tải hồ sơ mới nhất.'
        this.coLoi = false
      } catch (loi) {
        if (!this.daHuy && lan === this.lanTai) this.baoLoi(loi)
      } finally {
        if (!this.daHuy && lan === this.lanTai) this.dangTai = false
      }
    },
  },
}
</script>

<style scoped>
.text-emerald {
  color: var(--mau-chinh);
}

.text-amber {
  color: #d97706;
}

.ho-so-editor {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 340px;
  gap: 24px;
  align-items: start;
}

.ho-so-editor > * {
  min-width: 0;
}

.section-block {
  padding-bottom: 20px;
  border-bottom: 1px dashed var(--mau-vien);
}

.section-block:last-of-type {
  border-bottom: none;
  padding-bottom: 0;
}

.section-header {
  display: flex;
  align-items: center;
  font-size: 0.95rem;
}

.truong-doi {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.input-group-modern {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon-prefix {
  position: absolute;
  left: 14px;
  color: var(--mau-phu);
  pointer-events: none;
  font-size: 1rem;
}

.form-select.has-prefix {
  padding-left: 40px;
}

.form-select,
.form-control {
  min-height: 44px;
  border-radius: 8px;
  border-color: var(--mau-vien);
}

.form-select:focus,
.form-control:focus {
  border-color: var(--mau-chinh);
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}

/* Sidebar Styling */
.sidebar-avatar {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  font-size: 1.25rem;
  font-weight: 700;
  color: #ffffff;
  flex-shrink: 0;
}

.avatar-emerald {
  background: linear-gradient(135deg, #f45b20, #d94a15);
}

.avatar-blue {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
}

.avatar-purple {
  background: linear-gradient(135deg, #7c3aed, #8b5cf6);
}

.avatar-gray {
  background: #64748b;
}

.sidebar-user-info {
  min-width: 0;
}

.sidebar-detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  font-size: 0.85rem;
}

.detail-label {
  color: var(--mau-phu);
  white-space: nowrap;
}

.detail-value {
  font-weight: 600;
  color: var(--mau-chu);
  overflow-wrap: anywhere;
}

.email-ho-so {
  overflow-wrap: anywhere;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  font-weight: 650;
  padding: 3px 8px;
  border-radius: 12px;
  background: var(--mau-chinh-nhat);
  color: var(--mau-chinh-dam);
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  display: inline-block;
  background-color: var(--mau-chinh);
}

.dot-active {
  box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.3);
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

button,
.btn {
  min-height: 42px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  font-weight: 600;
}

@media (max-width: 900px) {
  .ho-so-editor {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .truong-doi {
    grid-template-columns: 1fr;
    gap: 0;
  }
}
</style>
