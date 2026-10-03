<template>
  <CaNhanLayout>
    <!-- Nút quay lại danh sách -->
    <RouterLink to="/admin/nhom-co" class="nhom-back">
      <i class="bi bi-arrow-left" aria-hidden="true"></i>
      <span>Danh sách nhóm cơ</span>
    </RouterLink>

    <!-- Tiêu đề trang -->
    <header class="nhom-form-heading">
      <div class="nhom-form-eyebrow">
        <i class="bi bi-shield-check" aria-hidden="true"></i>
        <span>QUẢN TRỊ VIÊN · DANH MỤC THỂ HÌNH</span>
      </div>
      <h1 class="h2 fw-bold mb-1">{{ laSua ? 'Chỉnh sửa nhóm cơ' : 'Thêm nhóm cơ mới' }}</h1>
      <p class="text-muted small mb-0">
        {{
          laSua
            ? 'Cập nhật tên hiển thị của nhóm cơ. Dữ liệu bài tập, lịch sử tập luyện và giáo án liên quan được giữ nguyên vẹn.'
            : 'Khởi tạo nhóm cơ vận động mới để phân loại và tổ chức bài tập thể hình khoa học.'
        }}
      </p>
    </header>

    <!-- Trạng thái đang tải chi tiết -->
    <div v-if="dangTai" class="nhom-form-state p-5 text-center" role="status">
      <div class="spinner-border text-success mb-3" role="status"></div>
      <p class="text-muted mb-0 small">Đang tải nhóm cơ…</p>
    </div>

    <!-- Trạng thái báo lỗi tải -->
    <div v-else-if="loiTai" class="alert alert-danger p-4 rounded-3 shadow-sm" role="alert">
      <div class="d-flex align-items-start gap-3">
        <i
          class="bi bi-exclamation-triangle-fill fs-3 text-danger flex-shrink-0"
          aria-hidden="true"
        ></i>
        <div class="flex-grow-1">
          <h2 class="h6 fw-bold mb-1">Không thể tải thông tin nhóm cơ</h2>
          <p class="mb-3 small">{{ loiTai }}</p>
          <div class="d-flex gap-2">
            <RouterLink v-if="hetPhien" to="/dang-nhap" class="btn btn-primary btn-sm">
              Đăng nhập lại
            </RouterLink>
            <button class="btn btn-outline-danger btn-sm" @click="taiChiTiet">
              <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Thử lại
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Form chính -->
    <form v-else class="nhom-form-layout" @submit.prevent="luuNhomCo">
      <!-- Cột trái: Form nhập liệu -->
      <div class="nhom-form-main shadow-sm">
        <!-- Thông báo kết quả tác vụ -->
        <div
          v-if="thongBao"
          class="alert d-flex align-items-center gap-2 p-3 mb-4 rounded-3"
          :class="
            coLoi ? 'alert-danger border-danger-subtle' : 'alert-success border-success-subtle'
          "
          :role="coLoi ? 'alert' : 'status'"
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
          <div class="flex-grow-1 small fw-semibold">
            {{ thongBao }}
            <RouterLink
              v-if="hetPhien"
              to="/dang-nhap"
              class="ms-1 fw-bold text-decoration-underline"
            >
              Đăng nhập lại
            </RouterLink>
          </div>
          <button
            type="button"
            class="btn-close"
            aria-label="Đóng thông báo"
            @click="thongBao = ''"
          ></button>
        </div>

        <!-- Banner cảnh báo xung đột phiên bản (409) -->
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
              <div class="fw-semibold small">Phát hiện phiên bản mới hơn trên hệ thống</div>
              <p class="small text-muted mb-2">
                Nội dung đang nhập được giữ nguyên. Tải lại sẽ thay bằng bản mới nhất trên hệ thống.
              </p>
              <button class="btn btn-outline-secondary btn-sm" type="button" @click="taiChiTiet">
                <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Tải lại bản mới
              </button>
            </div>
          </div>
        </div>

        <!-- Trường: Mã nhóm cơ -->
        <div class="nhom-field">
          <label
            for="ma-nhom-co"
            class="form-label d-flex align-items-center justify-content-between"
          >
            <span class="fw-bold">
              Mã nhóm cơ (Slug) <span v-if="!laSua" class="text-danger">*</span>
            </span>
            <span v-if="laSua" class="badge bg-secondary-subtle text-body border small"
              >Cố định</span
            >
          </label>
          <div class="input-group-modern">
            <span class="input-icon-prefix font-monospace text-emerald">#</span>
            <input
              id="ma-nhom-co"
              v-model.trim="bieuMau.ma_nhom_co"
              class="form-control nhom-code-input has-prefix"
              :class="{ 'is-invalid': loiTruong.ma_nhom_co }"
              :readonly="laSua"
              :disabled="dangLuu"
              required
              maxlength="64"
              pattern="[a-zA-Z][a-zA-Z0-9_]*"
              :aria-invalid="Boolean(loiTruong.ma_nhom_co)"
              aria-describedby="ma-nhom-goi-y ma-nhom-loi"
              autocomplete="off"
              placeholder="Ví dụ: co_nguc, co_lung_xo..."
            />
          </div>
          <p id="ma-nhom-goi-y" class="nhom-hint">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
            {{
              laSua
                ? 'Mã được giữ nguyên để các bài tập và dữ liệu nguồn tiếp tục liên kết đúng.'
                : 'Bắt đầu bằng chữ, dùng chữ a–z, số và gạch dưới. Ví dụ: co_trung_tam.'
            }}
          </p>
          <p v-if="loiTruong.ma_nhom_co" id="ma-nhom-loi" class="invalid-feedback d-block">
            {{ loiTruong.ma_nhom_co[0] }}
          </p>
        </div>

        <!-- Trường: Tên nhóm cơ -->
        <div class="nhom-field">
          <label for="ten-nhom-co" class="form-label fw-bold">
            Tên nhóm cơ tiếng Việt <span class="text-danger">*</span>
          </label>
          <input
            id="ten-nhom-co"
            ref="tenNhom"
            v-model.trim="bieuMau.ten_nhom_co"
            class="form-control"
            :class="{ 'is-invalid': loiTruong.ten_nhom_co }"
            :disabled="dangLuu"
            required
            maxlength="255"
            :aria-invalid="Boolean(loiTruong.ten_nhom_co)"
            aria-describedby="ten-nhom-loi"
            placeholder="Ví dụ: Cơ ngực, Cơ vai, Cơ trung tâm..."
          />
          <p v-if="loiTruong.ten_nhom_co" id="ten-nhom-loi" class="invalid-feedback d-block">
            {{ loiTruong.ten_nhom_co[0] }}
          </p>
        </div>

        <!-- Thanh thao tác gửi form -->
        <div class="nhom-form-actions">
          <RouterLink to="/admin/nhom-co" class="btn btn-outline-secondary"> Quay lại </RouterLink>
          <button
            class="btn btn-primary"
            :disabled="dangLuu || xungDot || !coThayDoi"
            type="submit"
          >
            <span v-if="dangLuu" class="spinner-border spinner-border-sm me-1" role="status"></span>
            <i
              v-else
              :class="laSua ? 'bi bi-check-circle-fill me-1' : 'bi bi-plus-circle-fill me-1'"
              aria-hidden="true"
            ></i>
            <span>{{ dangLuu ? 'Đang lưu…' : laSua ? 'Lưu thay đổi' : 'Thêm nhóm cơ' }}</span>
          </button>
        </div>
      </div>

      <!-- Cột phải: Thông tin ngữ cảnh & Hướng dẫn -->
      <aside class="nhom-form-sidebar">
        <!-- Nếu là trang sửa -->
        <div v-if="laSua" class="sidebar-card shadow-sm">
          <div class="sidebar-card-header">
            <i class="bi bi-info-circle-fill text-emerald me-2" aria-hidden="true"></i>
            <h3 class="h6 fw-bold mb-0">Hiện trạng nhóm cơ</h3>
          </div>
          <div class="sidebar-card-body">
            <div class="mb-3">
              <span class="text-muted small d-block mb-1">Trạng thái:</span>
              <span
                class="nhom-state-label"
                :class="trangThai === 'HOAT_DONG' ? 'badge-state-active' : 'badge-state-inactive'"
              >
                <span class="status-dot me-1"></span>
                {{ trangThai === 'HOAT_DONG' ? 'Đang hoạt động' : 'Ngừng sử dụng' }}
              </span>
            </div>

            <div class="mb-3">
              <span class="text-muted small d-block mb-1">Bài tập liên kết:</span>
              <div class="d-flex align-items-center gap-2">
                <span class="fw-bold fs-5 text-body">{{ soBai }}</span>
                <span class="small text-muted">bài tập thuộc nhóm này</span>
              </div>
            </div>

            <div v-if="tenNguon" class="mb-3">
              <span class="text-muted small d-block mb-1">Tên nguồn gốc:</span>
              <code class="small text-muted bg-light px-2 py-1 rounded border">{{ tenNguon }}</code>
            </div>

            <div class="callout-guarantee p-3 rounded-3 mt-3">
              <div class="fw-semibold small text-emerald-dam mb-1">
                <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Bảo toàn dữ liệu
              </div>
              <p class="small text-muted mb-0">
                Đổi tên không thay đổi bài tập hoặc lịch sử tập luyện. Hệ thống sẽ tự động cập nhật
                tên mới trên toàn bộ giáo án.
              </p>
            </div>
          </div>
        </div>

        <!-- Nếu là trang thêm mới -->
        <div v-else class="sidebar-card shadow-sm">
          <div class="sidebar-card-header">
            <i class="bi bi-lightbulb-fill text-amber me-2" aria-hidden="true"></i>
            <h3 class="h6 fw-bold mb-0">Lưu ý khi tạo nhóm</h3>
          </div>
          <div class="sidebar-card-body">
            <ul class="feature-notes mb-3">
              <li>
                <i class="bi bi-check2 text-emerald me-1" aria-hidden="true"></i>
                <span>Nhóm mới sẽ mặc định ở trạng thái <strong>Đang hoạt động</strong>.</span>
              </li>
              <li>
                <i class="bi bi-check2 text-emerald me-1" aria-hidden="true"></i>
                <span>Mã nhóm cơ là định danh duy nhất (slug), không thể sửa đổi sau khi tạo.</span>
              </li>
              <li>
                <i class="bi bi-check2 text-emerald me-1" aria-hidden="true"></i>
                <span
                  >Bạn có thể gán các bài tập mới vào nhóm cơ này ngay sau khi lưu thành công.</span
                >
              </li>
            </ul>

            <div class="callout-guide p-3 rounded-3">
              <div class="fw-semibold small text-body mb-1">Gợi ý phân loại:</div>
              <p class="small text-muted mb-0">
                Ưu tiên đặt tên theo các nhóm cơ vận động chính như Cơ ngực, Cơ lưng xô, Cơ đùi
                trước, Cơ vai... để huấn luyện viên dễ dàng thiết kế giáo án.
              </p>
            </div>
          </div>
        </div>
      </aside>
    </form>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../../../layouts/CaNhanLayout.vue'
import nhomCoService from '../../../../services/nhomCoService'
import { layLoiApi } from '../../../../utils/loiApi'

export default {
  name: 'BieuMauNhomCo',
  components: { CaNhanLayout },
  data() {
    return {
      bieuMau: { ma_nhom_co: '', ten_nhom_co: '' },
      tenBanDau: '',
      phienBan: null,
      trangThai: '',
      soBai: 0,
      tenNguon: '',
      dangTai: false,
      dangLuu: false,
      loiTai: '',
      loiTruong: {},
      thongBao: '',
      coLoi: false,
      hetPhien: false,
      xungDot: false,
      lanTai: 0,
      huyTai: null,
      daDong: false,
    }
  },
  computed: {
    laSua() {
      return Boolean(this.$route.params.id)
    },
    coThayDoi() {
      return this.laSua
        ? this.bieuMau.ten_nhom_co !== this.tenBanDau
        : Boolean(this.bieuMau.ma_nhom_co && this.bieuMau.ten_nhom_co)
    },
  },
  watch: {
    '$route.params.id': {
      immediate: true,
      handler() {
        this.taiChiTiet()
      },
    },
  },
  beforeUnmount() {
    this.daDong = true
    this.lanTai++
    this.huyTai?.abort()
  },
  methods: {
    napDuLieu(nhom) {
      this.bieuMau = { ma_nhom_co: nhom.ma_nhom_co, ten_nhom_co: nhom.ten_nhom_co }
      this.tenBanDau = nhom.ten_nhom_co
      this.phienBan = nhom.updated_at
      this.trangThai = nhom.trang_thai
      this.soBai = nhom.so_bai_tap
      this.tenNguon = nhom.ten_nguon
      this.xungDot = false
      this.loiTruong = {}
    },
    async taiChiTiet() {
      const lanTai = ++this.lanTai
      this.huyTai?.abort()
      this.huyTai = new AbortController()
      this.loiTai = ''
      this.thongBao = ''
      this.xungDot = false
      this.hetPhien = false
      this.dangLuu = false
      if (!this.laSua) {
        this.bieuMau = { ma_nhom_co: '', ten_nhom_co: '' }
        this.tenBanDau = ''
        this.loiTruong = {}
        this.dangTai = false
        return
      }
      this.dangTai = true
      try {
        const phanHoi = await nhomCoService.taiChiTiet(this.$route.params.id, this.huyTai.signal)
        if (this.daDong || lanTai !== this.lanTai) return
        this.napDuLieu(phanHoi.data)
      } catch (loi) {
        if (this.daDong || lanTai !== this.lanTai || loi.code === 'ERR_CANCELED') return
        const ketQua = layLoiApi(loi)
        this.loiTai = ketQua.thongBao
        this.hetPhien = ketQua.hetPhien
      } finally {
        if (!this.daDong && lanTai === this.lanTai) this.dangTai = false
      }
    },
    async luuNhomCo() {
      if (this.dangLuu || this.dangTai || this.loiTai || this.xungDot || !this.coThayDoi) return
      const duongDan = this.$route.path
      const lanTai = this.lanTai
      const laSua = this.laSua
      this.dangLuu = true
      this.thongBao = ''
      this.loiTruong = {}
      this.hetPhien = false
      try {
        const noiDung = { ten_nhom_co: this.bieuMau.ten_nhom_co.trim() }
        const phanHoi = laSua
          ? await nhomCoService.suaNhomCo(this.$route.params.id, {
              ...noiDung,
              updated_at: this.phienBan,
            })
          : await nhomCoService.taoNhomCo({
              ...noiDung,
              ma_nhom_co: this.bieuMau.ma_nhom_co.trim().toLowerCase(),
            })
        if (this.daDong || duongDan !== this.$route.path || lanTai !== this.lanTai) return
        this.napDuLieu(phanHoi.data)
        this.coLoi = false
        this.thongBao = phanHoi.message
        if (!laSua) {
          await this.$router.replace(`/admin/nhom-co/${phanHoi.data.id}/sua`)
          if (!this.daDong && String(this.$route.params.id) === String(phanHoi.data.id)) {
            this.coLoi = false
            this.thongBao = phanHoi.message
          }
        }
      } catch (loi) {
        if (this.daDong || duongDan !== this.$route.path || lanTai !== this.lanTai) return
        const ketQua = layLoiApi(loi)
        this.coLoi = true
        this.thongBao = ketQua.thongBao
        this.loiTruong = ketQua.loiTruong
        this.hetPhien = ketQua.hetPhien
        this.xungDot = loi.response?.status === 409
      } finally {
        if (!this.daDong && duongDan === this.$route.path && lanTai === this.lanTai)
          this.dangLuu = false
      }
    },
  },
}
</script>

<style scoped>
.text-emerald {
  color: var(--mau-chinh);
}

.text-emerald-dam {
  color: var(--mau-chinh-dam);
}

.text-amber {
  color: #d97706;
}

.nhom-back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 40px;
  color: var(--mau-phu);
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
  margin-bottom: 20px;
  transition:
    color 0.15s ease,
    transform 0.15s ease;
}

.nhom-back:hover {
  color: var(--mau-chinh);
  transform: translateX(-2px);
}

.nhom-form-heading {
  margin-bottom: 28px;
}

.nhom-form-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  font-weight: 750;
  letter-spacing: 0.1em;
  color: var(--mau-chinh-dam);
  margin-bottom: 8px;
}

/* Layout 2 cột */
.nhom-form-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 340px;
  gap: 28px;
  align-items: start;
}

.nhom-form-main {
  background: var(--mau-the);
  padding: 32px;
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
}

.nhom-field {
  margin-bottom: 24px;
}

.nhom-field label {
  font-size: 0.9rem;
  color: var(--mau-chu);
  margin-bottom: 8px;
}

.input-group-modern {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon-prefix {
  position: absolute;
  left: 14px;
  font-size: 1rem;
  font-weight: 700;
  pointer-events: none;
}

.form-control.has-prefix {
  padding-left: 36px;
}

.form-control {
  min-height: 46px;
  border-radius: 8px;
  border-color: var(--mau-vien);
  font-size: 0.95rem;
}

.form-control:focus {
  border-color: var(--mau-chinh);
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}

.nhom-code-input {
  font-family: monospace;
}

.nhom-code-input[readonly] {
  background: var(--mau-the-sub, #181818);
  color: var(--mau-phu);
  cursor: not-allowed;
}

.nhom-hint {
  font-size: 0.8rem;
  color: var(--mau-phu);
  margin: 8px 0 0;
}

.nhom-form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  border-top: 1px solid var(--mau-vien);
  padding-top: 24px;
  margin-top: 28px;
}

.nhom-form-actions .btn {
  min-height: 44px;
  padding: 0 22px;
  border-radius: 8px;
  font-weight: 650;
  display: inline-flex;
  align-items: center;
  justify-content: center;
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

/* Sidebar bên phải */
.sidebar-card {
  background: var(--mau-the);
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  overflow: hidden;
}

.sidebar-card-header {
  padding: 16px 20px;
  background: var(--mau-table-header-bg, #181818);
  border-bottom: 1px solid var(--mau-vien);
  display: flex;
  align-items: center;
}

.sidebar-card-body {
  padding: 20px;
}

.nhom-state-label {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 650;
}

.badge-state-active {
  background: var(--mau-chinh-nhat);
  color: var(--mau-chinh-dam);
  border: 1px solid rgba(244, 91, 32, 0.25);
}

.badge-state-inactive {
  background: var(--mau-the-sub, #1e1e1e);
  color: var(--mau-phu);
  border: 1px solid var(--mau-vien, rgba(255, 255, 255, 0.1));
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  display: inline-block;
  background-color: currentColor;
}

.callout-guarantee {
  background: var(--mau-chinh-nhat);
  border: 1px solid rgba(244, 91, 32, 0.25);
}

.callout-guide {
  background: var(--mau-the-sub, #181818);
  border: 1px solid var(--mau-vien);
}

.feature-notes {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
  font-size: 0.85rem;
  color: var(--mau-chu);
}

.feature-notes li {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  line-height: 1.4;
}

/* Responsive */
@media (max-width: 992px) {
  .nhom-form-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .nhom-form-main {
    padding: 20px 16px;
  }

  .nhom-form-actions {
    flex-direction: column;
  }

  .nhom-form-actions .btn {
    width: 100%;
  }
}
</style>
