<template>
  <DanhMucLayout>
    <!-- Nút quay lại bảng giá -->
    <div class="mb-4">
      <RouterLink class="btn-back-link" :to="{ name: 'goi-tap', query: queryQuayLai }">
        <i class="bi bi-arrow-left"></i>
        <span>Quay lại bảng giá gói tập</span>
      </RouterLink>
    </div>

    <!-- Trạng thái Đang tải -->
    <div v-if="dangTai" class="detail-state-card animate__animated animate__fadeIn" role="status">
      <div
        class="spinner-border text-success mb-3"
        style="width: 3rem; height: 3rem"
        role="status"
      ></div>
      <h2 class="h5 fw-bold mb-1">Đang tải thông tin quyền lợi gói…</h2>
      <p class="text-muted small mb-0">
        Hệ thống đang chuẩn bị chi tiết dịch vụ và bảng tính năng.
      </p>
    </div>

    <!-- Trạng thái Báo lỗi hoặc Không tìm thấy -->
    <div
      v-else-if="loiTai"
      class="detail-state-card alert alert-warning border-warning-subtle"
      role="alert"
    >
      <div class="error-halo mx-auto mb-3">
        <i
          :class="
            khongTimThay
              ? 'bi bi-slash-circle-fill text-secondary'
              : 'bi bi-exclamation-triangle-fill text-warning'
          "
        ></i>
      </div>
      <h1 class="h4 fw-bold mb-2">
        {{ khongTimThay ? 'Gói tập hiện không có trong bảng giá' : 'Chưa tải được gói tập' }}
      </h1>
      <p class="text-muted small mb-4 mx-auto" style="max-width: 480px">
        {{
          khongTimThay
            ? 'Gói tập này có thể đã kết thúc thời gian mở bán hoặc tạm ngừng phát hành.'
            : loiTai
        }}
      </p>
      <div class="d-flex gap-2 justify-content-center flex-wrap">
        <button v-if="!khongTimThay" class="btn btn-primary btn-sm" @click="taiChiTiet">
          <i class="bi bi-arrow-clockwise me-1"></i>Thử lại
        </button>
        <RouterLink class="btn btn-outline-secondary btn-sm" :to="{ name: 'goi-tap' }">
          <i class="bi bi-grid me-1"></i>Xem các gói khác
        </RouterLink>
      </div>
    </div>

    <!-- Chi tiết gói tập -->
    <div v-else-if="goi" class="package-detail-layout animate__animated animate__fadeIn">
      <!-- Cột trái: Thông tin giới thiệu & Quy định sử dụng -->
      <section class="detail-copy-col">
        <div class="eyebrow mb-2">
          <i :class="goi.so_buoi_pt > 0 ? 'bi-person-check-fill' : 'bi-chat-heart-fill'"></i>
          <span>{{
            goi.so_buoi_pt > 0
              ? 'HUẤN LUYỆN 1:1 ĐỒNG HÀNH & TRỢ LÝ AI'
              : 'TRỢ LÝ CHATBOT AI TƯ VẤN THỂ HÌNH'
          }}</span>
        </div>

        <h1 class="package-detail-title fw-bold mb-3">{{ goi.ten_goi }}</h1>

        <p class="detail-intro text-muted mb-4">
          {{
            goi.so_buoi_pt > 0
              ? 'Tập luyện trực tiếp cùng huấn luyện viên riêng và nhận tư vấn giải đáp thắc mắc liên tục từ trợ lý AI trong cùng một gói dịch vụ.'
              : 'Trợ lý AI thông minh sẵn sàng đồng hành cùng bạn 24/7, cung cấp thực đơn dinh dưỡng và phân tích kỹ thuật các bài tập thể hình.'
          }}
        </p>

        <!-- Thẻ quy định & Cách sử dụng -->
        <section class="usage-note-card card-modern p-4 mb-4" aria-labelledby="tieu-de-su-dung">
          <div class="panel-heading pb-3 mb-3 border-bottom">
            <h2 id="tieu-de-su-dung" class="h5 fw-bold mb-0 d-flex align-items-center gap-2">
              <i class="bi bi-shield-check text-success"></i>
              <span>Thời hạn và quy định sử dụng dịch vụ</span>
            </h2>
          </div>

          <div class="rules-list d-flex flex-column gap-3">
            <div class="rule-item d-flex gap-3">
              <span class="rule-icon-box text-success"><i class="bi bi-clock-history"></i></span>
              <div>
                <strong class="d-block small text-dark"
                  >Thời hạn kích hoạt {{ dinhDangSo(goi.thoi_han_ngay) }} ngày</strong
                >
                <span class="text-muted small"
                  >Bắt đầu tính ngay khi thanh toán được hệ thống ghi nhận thành công; mỗi ngày tính
                  đủ 24 giờ.</span
                >
              </div>
            </div>

            <div class="rule-item d-flex gap-3">
              <span class="rule-icon-box text-purple"><i class="bi bi-robot"></i></span>
              <div>
                <strong class="d-block small text-dark"
                  >Hạn mức {{ dinhDangSo(goi.so_luot_chatbot_moi_ngay) }} lượt hỏi/ngày</strong
                >
                <span class="text-muted small"
                  >Lượt hỏi được làm mới tự động vào lúc 00:00 hàng ngày theo giờ Việt Nam.</span
                >
              </div>
            </div>

            <div v-if="goi.so_buoi_pt > 0" class="rule-item d-flex gap-3">
              <span class="rule-icon-box text-emerald"><i class="bi bi-person-video3"></i></span>
              <div>
                <strong class="d-block small text-dark">Buổi tập cùng PT 1:1</strong>
                <span class="text-muted small"
                  >Buổi tập diễn ra trong thời hạn gói. Khi dùng hết các buổi PT, bạn vẫn tiếp tục
                  được truy vấn Chatbot cho đến khi gói kết thúc.</span
                >
              </div>
            </div>

            <div v-else class="rule-item d-flex gap-3">
              <span class="rule-icon-box text-secondary"><i class="bi bi-info-circle"></i></span>
              <div>
                <strong class="d-block small text-dark">Gói chuyên biệt Chatbot AI</strong>
                <span class="text-muted small"
                  >Gói chatbot độc lập không bao gồm các buổi huấn luyện thể chất trực tiếp cùng
                  PT.</span
                >
              </div>
            </div>

            <div class="rule-item d-flex gap-3">
              <span class="rule-icon-box text-primary"><i class="bi bi-person-badge"></i></span>
              <div>
                <strong class="d-block small text-dark">Một tài khoản - Một gói hiệu lực</strong>
                <span class="text-muted small"
                  >Mỗi học viên duy trì một gói dịch vụ đang hoạt động tại một thời điểm để tối ưu
                  giáo án theo dõi.</span
                >
              </div>
            </div>
          </div>
        </section>
      </section>

      <!-- Cột phải: Tóm tắt đơn giá & Quyền lợi -->
      <aside class="detail-summary-col">
        <div class="detail-summary-card card-modern shadow-sm p-4">
          <div class="eyebrow mb-2">
            <i class="bi bi-receipt"></i>
            <span>BÁO GIÁ TRỌN GÓI</span>
          </div>

          <div class="detail-price-wrap mb-3">
            <span class="detail-price-main">{{ dinhDangGia(goi.gia) }}</span>
            <span class="text-muted small">/ {{ dinhDangSo(goi.thoi_han_ngay) }} ngày</span>
          </div>

          <!-- Quyền lợi gói -->
          <div class="mb-4">
            <QuyenLoiGoiTap :goi="goi" />
          </div>

          <div class="purchase-notice-box p-3 rounded-3 small mb-3">
            <i class="bi bi-info-circle text-primary me-1"></i>
            <span>Cổng thanh toán tự động trực tuyến đang trong giai đoạn kết nối hoàn thiện.</span>
          </div>

          <RouterLink
            class="btn btn-outline-secondary w-100"
            :to="{ name: 'goi-tap', query: queryQuayLai }"
          >
            <i class="bi bi-arrow-left me-1"></i>Xem các gói khác
          </RouterLink>
        </div>
      </aside>
    </div>
  </DanhMucLayout>
</template>

<script>
import DanhMucLayout from '../../../layouts/DanhMucLayout.vue'
import QuyenLoiGoiTap from '../../../components/QuyenLoiGoiTap.vue'
import goiTapService from '../../../services/goiTapService'
import { dinhDangGia, dinhDangSo, docBoLocGoiTap, taoQueryGoiTap } from '../../../utils/goiTap'
import { layLoiApi } from '../../../utils/loiApi'

export default {
  name: 'ChiTietGoiTap',
  components: { DanhMucLayout, QuyenLoiGoiTap },
  data() {
    return {
      goi: null,
      dangTai: false,
      loiTai: '',
      khongTimThay: false,
      boHuy: null,
      lanTai: 0,
      daDong: false,
    }
  },
  computed: {
    queryQuayLai() {
      const boLoc = docBoLocGoiTap(this.$route.query)
      return taoQueryGoiTap(boLoc, boLoc.page)
    },
  },
  watch: { '$route.params.id': 'taiChiTiet' },
  mounted() {
    this.taiChiTiet()
  },
  beforeUnmount() {
    this.daDong = true
    this.lanTai++
    this.boHuy?.abort()
  },
  methods: {
    dinhDangGia,
    dinhDangSo,
    async taiChiTiet() {
      this.boHuy?.abort()
      const boHuy = new AbortController()
      this.boHuy = boHuy
      const lanTai = ++this.lanTai
      this.goi = null
      this.loiTai = ''
      this.khongTimThay = false
      this.dangTai = true
      try {
        const phanHoi = await goiTapService.taiChiTiet(this.$route.params.id, boHuy.signal)
        if (!this.daDong && lanTai === this.lanTai) this.goi = phanHoi.data
      } catch (loi) {
        if (this.daDong || lanTai !== this.lanTai || boHuy.signal.aborted) return
        this.khongTimThay = loi.response?.status === 404
        this.loiTai = layLoiApi(loi).thongBao
      } finally {
        if (!this.daDong && lanTai === this.lanTai) this.dangTai = false
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
  background: white;
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
  background: #f8fafc;
  color: var(--mau-chinh);
  border-color: var(--mau-chinh);
  transform: translateX(-3px);
}

.detail-state-card {
  min-height: 380px;
  background: white;
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-xl);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
  text-align: center;
  box-shadow: var(--bong-nhe);
}

.error-halo {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: #fffbeb;
  display: grid;
  place-items: center;
  font-size: 2.2rem;
}

.package-detail-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.45fr) minmax(320px, 1fr);
  gap: 48px;
  align-items: start;
}

.package-detail-title {
  font-size: clamp(2rem, 3.5vw, 2.8rem);
  line-height: 1.2;
  color: var(--mau-chu);
}

.detail-intro {
  font-size: 1.05rem;
  line-height: 1.7;
}

.usage-note-card {
  background: white;
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
}

.rule-icon-box {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #f1f5f9;
  display: grid;
  place-items: center;
  font-size: 1rem;
  flex-shrink: 0;
}

.detail-summary-col {
  position: sticky;
  top: 90px;
}

.detail-summary-card {
  background: white;
  border: 1px solid var(--mau-vien);
  border-top: 4px solid var(--mau-chinh);
  border-radius: var(--bo-goc-lg);
}

.detail-price-wrap {
  display: flex;
  align-items: baseline;
  gap: 8px;
  flex-wrap: wrap;
}

.detail-price-main {
  font-size: 2.2rem;
  font-weight: 800;
  letter-spacing: -0.04em;
  color: var(--mau-chu);
  line-height: 1;
}

.purchase-notice-box {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1e40af;
}

@media (max-width: 992px) {
  .package-detail-layout {
    grid-template-columns: 1fr;
    gap: 32px;
  }
  .detail-summary-col {
    position: static;
  }
}
</style>
