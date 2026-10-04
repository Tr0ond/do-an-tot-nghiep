<template>
  <CaNhanLayout>
    <!-- Nút quay lại danh sách quản trị gói tập -->
    <div class="mb-3">
      <RouterLink to="/admin/goi-tap" class="btn-back-link">
        <i class="bi bi-arrow-left"></i>
        <span>Quay lại danh sách gói tập</span>
      </RouterLink>
    </div>

    <!-- Tiêu đề trang biên tập -->
    <header class="editor-heading mb-4 animate__animated animate__fadeIn">
      <div class="eyebrow mb-2">
        <i :class="laThem ? 'bi-plus-circle-fill' : 'bi-pencil-square'"></i>
        <span>{{ laThem ? 'QUẢN TRỊ · THÊM GÓI TẬP MỚI' : 'QUẢN TRỊ · CẬP NHẬT GÓI TẬP' }}</span>
      </div>
      <h1 class="h2 fw-bold mb-1">
        {{ laThem ? 'Thêm gói tập mới' : 'Chỉnh sửa cấu hình gói tập' }}
      </h1>
      <p class="text-muted small mb-0">
        Đơn giá và quyền lợi khi thay đổi chỉ áp dụng cho những lượt đăng ký mới phát sinh về sau.
      </p>
    </header>

    <!-- Trạng thái Đang tải -->
    <div
      v-if="dangTai"
      class="editor-state p-5 text-center bg-white rounded-4 border shadow-sm"
      role="status"
    >
      <div class="spinner-border text-success mb-3" role="status"></div>
      <h2 class="h5 fw-bold mb-1">Đang tải cấu hình gói tập…</h2>
      <p class="text-muted small mb-0">Hệ thống đang chuẩn bị dữ liệu biểu mẫu.</p>
    </div>

    <!-- Trạng thái Báo lỗi tải -->
    <div
      v-else-if="loiTai"
      class="alert alert-danger p-4 rounded-4 border-danger-subtle shadow-sm text-center"
      role="alert"
    >
      <i class="bi bi-exclamation-triangle-fill fs-1 text-danger mb-2"></i>
      <h2 class="h5 fw-bold text-danger mb-2">Chưa thể tải dữ liệu gói tập</h2>
      <p class="mb-3 small">{{ loiTai }}</p>
      <button class="btn btn-outline-danger btn-sm" @click="taiBieuMau">
        <i class="bi bi-arrow-clockwise me-1"></i>Thử tải lại
      </button>
    </div>

    <!-- Biểu mẫu biên tập 2 cột -->
    <form v-else class="package-editor" @submit.prevent="luuGoiTap" novalidate :aria-busy="dangLuu">
      <!-- Cột chính: Nhập liệu cấu hình -->
      <div class="editor-main-col">
        <!-- Thông báo kết quả tác vụ / Xung đột phiên bản 409 -->
        <div
          v-if="thongBao"
          ref="phanHoi"
          tabindex="-1"
          class="alert d-flex flex-column gap-2 p-3 mb-4 rounded-3 animate__animated animate__fadeIn"
          :class="
            coLoi ? 'alert-danger border-danger-subtle' : 'alert-success border-success-subtle'
          "
          role="alert"
        >
          <div class="d-flex align-items-center gap-2">
            <i
              :class="
                coLoi
                  ? 'bi bi-exclamation-triangle-fill text-danger'
                  : 'bi bi-check-circle-fill text-success'
              "
              class="fs-5 flex-shrink-0"
            ></i>
            <strong class="small">{{ thongBao }}</strong>
          </div>
          <ul v-if="coLoi && Object.keys(loiTruong).length" class="mb-0 ps-4 small text-danger">
            <li v-for="(loi, truong) in loiTruong" :key="truong">{{ loi[0] }}</li>
          </ul>
          <div v-if="xungDot" class="mt-2 pt-2 border-top border-danger-subtle">
            <button
              v-if="!laThem"
              class="btn btn-danger btn-sm shadow-sm"
              type="button"
              @click="taiBieuMau"
            >
              <i class="bi bi-arrow-clockwise me-1"></i>Tải bản mới nhất từ máy chủ
            </button>
            <RouterLink v-else class="btn btn-outline-secondary btn-sm" to="/admin/goi-tap">
              Kiểm tra danh sách gói tập
            </RouterLink>
          </div>
        </div>

        <!-- Panel cấu hình: Thông tin gói & Định giá -->
        <fieldset class="editor-panel card-modern mb-4" :disabled="dangLuu || xungDot">
          <div class="panel-heading">
            <h2 class="h5 fw-bold mb-0">
              <i class="bi bi-sliders text-success"></i>
              <span>Thông tin gói & Quyền lợi dịch vụ</span>
            </h2>
            <span class="badge bg-light text-muted border">Bắt buộc</span>
          </div>

          <!-- Tên gói -->
          <TruongNhap
            v-model="duLieu.ten_goi"
            id="ten_goi"
            nhan="Tên gói tập"
            goi-y-nhap="Ví dụ: Gói Cơ Bản 1 Tháng, Gói VIP Kèm PT 1:1…"
            bieu-tuong="bi bi-tag-fill"
            :loi="loiTruong.ten_goi"
          />

          <!-- Cặp trường: Giá toàn gói & Thời hạn -->
          <div class="row g-3">
            <div class="col-md-6">
              <TruongNhap
                v-model="duLieu.gia"
                id="gia"
                nhan="Giá toàn gói (VND)"
                loai="number"
                goi-y-nhap="Ví dụ: 500000"
                bieu-tuong="bi bi-cash-stack"
                :toi-thieu="1"
                :toi-da="9007199254740991"
                goi-y="Nhập số tiền nguyên, không dấu chấm phẩy."
                :loi="loiTruong.gia"
              />
            </div>

            <div class="col-md-6">
              <TruongNhap
                v-model="duLieu.thoi_han_ngay"
                id="thoi_han_ngay"
                nhan="Thời hạn sử dụng (ngày)"
                loai="number"
                goi-y-nhap="30, 90, 180…"
                bieu-tuong="bi bi-calendar-event"
                :toi-thieu="1"
                :toi-da="36500"
                goi-y="Mỗi ngày tính đủ 24 giờ kể từ khi kích hoạt."
                :loi="loiTruong.thoi_han_ngay"
              />
            </div>
          </div>

          <!-- Loại gói dịch vụ -->
          <div class="mb-3">
            <label for="loai_goi" class="form-label d-flex align-items-center gap-1">
              <i class="bi bi-boxes text-primary"></i>
              <span>Phân loại gói dịch vụ</span>
              <span class="text-danger" aria-hidden="true">*</span>
            </label>
            <select id="loai_goi" v-model="duLieu.loai_goi" class="form-select">
              <option value="CHATBOT">Chatbot riêng (Tự học & Tư vấn AI)</option>
              <option value="PT_CHATBOT">PT theo buổi kèm Chatbot (Toàn diện)</option>
            </select>
            <p class="form-text mt-1 text-muted small">
              <i class="bi bi-info-circle me-1"></i>Cả hai loại đều có quyền hỏi Chatbot AI. Gói kết
              hợp dùng chung một thời hạn cho cả buổi tập PT và chatbot.
            </p>
            <p v-if="loiTruong.co_chatbot" class="text-danger small mt-1">
              {{ loiTruong.co_chatbot[0] }}
            </p>
          </div>

          <!-- Cặp trường: Số buổi PT & Lượt chatbot mỗi ngày -->
          <div class="row g-3">
            <div class="col-md-6" v-if="duLieu.loai_goi === 'PT_CHATBOT'">
              <TruongNhap
                v-model="duLieu.so_buoi_pt"
                id="so_buoi_pt"
                nhan="Số buổi tập cùng PT (1-kèm-1)"
                loai="number"
                goi-y-nhap="Ví dụ: 12"
                bieu-tuong="bi bi-person-video3"
                :toi-thieu="1"
                :toi-da="4294967295"
                :loi="loiTruong.so_buoi_pt"
              />
            </div>

            <div :class="duLieu.loai_goi === 'PT_CHATBOT' ? 'col-md-6' : 'col-12'">
              <TruongNhap
                v-model="duLieu.so_luot_chatbot_moi_ngay"
                id="so_luot_chatbot_moi_ngay"
                nhan="Lượt truy vấn Chatbot mỗi ngày"
                loai="number"
                goi-y-nhap="Ví dụ: 50"
                bieu-tuong="bi bi-robot"
                :toi-thieu="1"
                :toi-da="4294967295"
                goi-y="Hạn mức làm mới tự động lúc 00:00 hàng ngày (giờ Việt Nam)."
                :loi="loiTruong.so_luot_chatbot_moi_ngay"
              />
            </div>
          </div>

          <!-- Trạng thái bán ban đầu (chỉ khi thêm mới) -->
          <div v-if="laThem" class="mb-3 mt-2">
            <label for="trang_thai" class="form-label d-flex align-items-center gap-1">
              <i class="bi bi-toggle-on text-amber"></i>
              <span>Trạng thái phát hành ban đầu</span>
            </label>
            <select
              id="trang_thai"
              v-model="duLieu.trang_thai"
              class="form-select"
              :aria-invalid="!!loiTruong.trang_thai"
              aria-describedby="loi-trang-thai"
            >
              <option value="NGUNG_SU_DUNG">Ngừng bán — Kiểm tra nội bộ trước khi công khai</option>
              <option value="HOAT_DONG">Đang bán — Mở bán ngay trên Bảng giá công khai</option>
            </select>
            <p
              v-if="loiTruong.trang_thai"
              id="loi-trang-thai"
              class="invalid-feedback d-block mt-1"
            >
              {{ loiTruong.trang_thai[0] }}
            </p>
          </div>
        </fieldset>

        <!-- Thanh nút Lưu / Quay lại -->
        <div class="editor-actions d-flex justify-content-end gap-2 mb-4">
          <RouterLink class="btn btn-outline-secondary" to="/admin/goi-tap">
            Quay lại danh sách
          </RouterLink>
          <button class="btn btn-primary shadow-sm" type="submit" :disabled="dangLuu || xungDot">
            <span v-if="dangLuu" class="spinner-border spinner-border-sm me-1" role="status"></span>
            <i v-else :class="laThem ? 'bi bi-plus-circle' : 'bi bi-check2-circle'"></i>
            <span>{{
              dangLuu
                ? 'Đang lưu gói tập…'
                : laThem
                  ? 'Xác nhận thêm gói tập'
                  : 'Lưu thay đổi gói tập'
            }}</span>
          </button>
        </div>
      </div>

      <!-- Cột phụ: Xem trước Thẻ giá Real-time -->
      <aside class="preview-aside-col">
        <div class="preview-panel card-modern shadow-sm">
          <div class="eyebrow mb-2">
            <i class="bi bi-eye-fill"></i>
            <span>XEM TRƯỚC BẢNG GIÁ</span>
          </div>

          <div
            class="package-type-pill mb-2"
            :class="duLieu.loai_goi === 'PT_CHATBOT' ? 'pill-pt' : 'pill-bot'"
          >
            <i
              :class="
                duLieu.loai_goi === 'PT_CHATBOT'
                  ? 'bi bi-person-check-fill'
                  : 'bi bi-chat-square-text-fill'
              "
            ></i>
            <span>{{
              duLieu.loai_goi === 'PT_CHATBOT' ? 'HUẤN LUYỆN 1:1 & AI' : 'TRỢ LÝ CHATBOT AI'
            }}</span>
          </div>

          <h2 class="h4 fw-bold mb-2">{{ duLieu.ten_goi || 'Tên gói tập xem trước' }}</h2>
          <div class="preview-price-highlight mb-3">
            <span class="price-value">{{ dinhDangGia(duLieu.gia) }}</span>
            <span class="text-muted small">/ {{ duLieu.thoi_han_ngay || '30' }} ngày</span>
          </div>

          <!-- Danh sách quyền lợi xem trước -->
          <div class="preview-facts-wrap p-3 bg-light rounded-3 border mb-3">
            <QuyenLoiGoiTap :goi="goiXemTruoc" />
          </div>

          <div class="preview-note-box p-3 rounded-3 small mb-3">
            <i class="bi bi-shield-check text-success me-1"></i>
            {{
              duLieu.loai_goi === 'PT_CHATBOT'
                ? 'Khi học viên dùng hết số buổi PT, tài khoản vẫn tiếp tục được truy vấn Chatbot AI cho đến ngày hết hạn gói.'
                : 'Gói Chatbot riêng cung cấp quyền truy vấn không giới hạn kiến thức thể hình và thực đơn dinh dưỡng.'
            }}
          </div>

          <div v-if="goi" class="small text-muted border-top pt-2">
            <i class="bi bi-info-circle me-1"></i>Gói #{{ goi.id }} · Trạng thái:
            <strong>{{ goi.trang_thai === 'HOAT_DONG' ? 'Đang mở bán' : 'Ngừng bán' }}</strong
            >.
          </div>
        </div>
      </aside>
    </form>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../../../layouts/CaNhanLayout.vue'
import TruongNhap from '../../../../components/TruongNhap.vue'
import QuyenLoiGoiTap from '../../../../components/QuyenLoiGoiTap.vue'
import goiTapService from '../../../../services/goiTapService'
import { dinhDangGia, taoBieuMauGoiTap, taoPayloadGoiTap } from '../../../../utils/goiTap'
import { layLoiApi } from '../../../../utils/loiApi'

export default {
  name: 'BieuMauGoiTap',
  components: { CaNhanLayout, TruongNhap, QuyenLoiGoiTap },
  data() {
    return {
      goi: null,
      duLieu: taoBieuMauGoiTap(),
      maYeuCau: null,
      dangTai: false,
      dangLuu: false,
      loiTai: '',
      thongBao: '',
      coLoi: false,
      loiTruong: {},
      xungDot: false,
      boHuy: null,
      lanTai: 0,
      daDong: false,
    }
  },
  computed: {
    laThem() {
      return !this.$route.params.id
    },
    goiXemTruoc() {
      return {
        ...this.duLieu,
        so_buoi_pt: this.duLieu.loai_goi === 'CHATBOT' ? 0 : this.duLieu.so_buoi_pt,
      }
    },
  },
  watch: {
    '$route.params.id': 'taiBieuMau',
    'duLieu.loai_goi'(loai) {
      if (loai === 'PT_CHATBOT' && Number(this.duLieu.so_buoi_pt) < 1) this.duLieu.so_buoi_pt = '1'
    },
  },
  mounted() {
    this.taiBieuMau()
  },
  beforeUnmount() {
    this.daDong = true
    this.lanTai++
    this.boHuy?.abort()
  },
  methods: {
    dinhDangGia,
    async taiBieuMau() {
      this.boHuy?.abort()
      const boHuy = new AbortController()
      this.boHuy = boHuy
      const lanTai = ++this.lanTai
      this.goi = null
      this.loiTai = ''
      this.thongBao = ''
      this.loiTruong = {}
      this.xungDot = false
      this.dangLuu = false
      this.duLieu = taoBieuMauGoiTap()
      this.maYeuCau = crypto.randomUUID()
      if (this.laThem) {
        this.dangTai = false
        return
      }
      this.dangTai = true
      try {
        const phanHoi = await goiTapService.taiChiTiet(this.$route.params.id, boHuy.signal, true)
        if (this.daDong || lanTai !== this.lanTai) return
        this.goi = phanHoi.data
        this.duLieu = taoBieuMauGoiTap(this.goi)
      } catch (loi) {
        if (this.daDong || lanTai !== this.lanTai || boHuy.signal.aborted) return
        this.loiTai =
          loi.response?.status === 404 ? 'Gói tập không tồn tại.' : layLoiApi(loi).thongBao
      } finally {
        if (!this.daDong && lanTai === this.lanTai) this.dangTai = false
      }
    },
    async luuGoiTap() {
      if (this.dangLuu || this.dangTai || this.xungDot || this.loiTai) return
      this.dangLuu = true
      this.thongBao = ''
      this.loiTruong = {}
      const lanTai = this.lanTai
      const id = this.goi?.id
      const duLieu = taoPayloadGoiTap(this.duLieu, this.goi, this.maYeuCau)
      try {
        const phanHoi = id
          ? await goiTapService.suaGoiTap(id, duLieu)
          : await goiTapService.taoGoiTap(duLieu)
        if (this.daDong || lanTai !== this.lanTai) return
        if (!id) {
          await this.$router.replace('/admin/goi-tap/' + phanHoi.data.id + '/sua')
          return
        }
        this.goi = phanHoi.data
        this.duLieu = taoBieuMauGoiTap(this.goi)
        this.thongBao = phanHoi.message
        this.coLoi = false
      } catch (loi) {
        if (this.daDong || lanTai !== this.lanTai) return
        const chiTiet = layLoiApi(loi)
        this.thongBao = chiTiet.thongBao
        this.loiTruong = chiTiet.loiTruong
        this.coLoi = true
        this.xungDot = loi.response?.status === 409
      } finally {
        if (!this.daDong && lanTai === this.lanTai) {
          this.dangLuu = false
          await this.$nextTick()
          this.$refs.phanHoi?.focus()
        }
      }
    },
  },
}
</script>

<style scoped>
.btn-back-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: var(--mau-the-sub, var(--mau-the-sub));
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-tron);
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--mau-chu);
  text-decoration: none;
  box-shadow: var(--bong-nhe);
  transition: all 0.2s ease;
}

.btn-back-link:hover {
  background: var(--mau-the-hover, var(--mau-the-hover));
  color: var(--mau-chinh);
  border-color: var(--mau-chinh);
  transform: translateX(-3px);
}

.package-editor {
  display: grid;
  grid-template-columns: minmax(0, 1.6fr) minmax(320px, 1fr);
  gap: 32px;
  align-items: start;
}

.editor-panel {
  background: var(--mau-the, var(--mau-the));
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  padding: 28px;
}

.preview-aside-col {
  position: sticky;
  top: 90px;
}

.preview-panel {
  background: var(--mau-the, var(--mau-the));
  border: 1px solid var(--mau-vien);
  border-top: 4px solid var(--mau-chinh);
  border-radius: var(--bo-goc-lg);
  padding: 28px;
}

.package-type-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0;
  padding: 4px 10px;
  border-radius: var(--bo-goc-tron);
}

.pill-pt {
  background: color-mix(in srgb, var(--mau-chinh) 15%, transparent);
  color: var(--mau-chinh);
}

.pill-bot {
  background: rgba(124, 58, 237, 0.15);
  color: var(--mau-thong-tin);
}

.preview-price-highlight {
  display: flex;
  align-items: baseline;
  gap: 6px;
}

.price-value {
  font-size: 1.85rem;
  font-weight: 800;
  color: var(--mau-chu);
  letter-spacing: 0;
}

.preview-note-box {
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  color: #065f46;
}

@media (max-width: 992px) {
  .package-editor {
    grid-template-columns: 1fr;
  }
  .preview-aside-col {
    position: static;
  }
}
</style>
