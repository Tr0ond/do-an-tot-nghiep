<template>
  <DanhMucLayout>
    <!-- Nút quay lại danh sách -->
    <div class="mb-4">
      <RouterLink class="btn-back-library" :to="{ name: 'bai-tap', query: $route.query }">
        <i class="bi bi-arrow-left"></i>
        <span>Quay lại danh sách bài tập</span>
      </RouterLink>
    </div>

    <!-- Trạng thái Đang tải -->
    <div v-if="dangTai" class="detail-state-card animate__animated animate__fadeIn" role="status">
      <div
        class="spinner-border text-success mb-3"
        style="width: 3rem; height: 3rem"
        role="status"
      ></div>
      <h2 class="h5 fw-bold mb-1">Đang tải thông tin bài tập…</h2>
      <p class="text-muted small mb-0">Hệ thống đang chuẩn bị giáo án và hình ảnh minh họa.</p>
    </div>

    <!-- Trạng thái Báo lỗi hoặc Không tìm thấy -->
    <div
      v-else-if="thongBao"
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
        {{ khongTimThay ? 'Bài tập không còn hiển thị' : 'Chưa tải được bài tập' }}
      </h1>
      <p class="text-muted small mb-4 mx-auto" style="max-width: 480px">
        {{
          khongTimThay
            ? 'Bài tập này không tồn tại trên hệ thống hoặc đã được điều chỉnh ngừng hiển thị.'
            : thongBao
        }}
      </p>
      <div class="d-flex gap-2 justify-content-center flex-wrap">
        <button v-if="!khongTimThay" class="btn btn-primary btn-sm" @click="taiChiTiet">
          <i class="bi bi-arrow-clockwise me-1"></i>Thử lại
        </button>
        <RouterLink class="btn btn-outline-secondary btn-sm" :to="{ name: 'bai-tap' }">
          <i class="bi bi-grid me-1"></i>Xem danh mục bài tập
        </RouterLink>
      </div>
    </div>

    <!-- Chi tiết bài tập -->
    <article v-else-if="baiTap" class="exercise-detail-layout animate__animated animate__fadeIn">
      <!-- Cột trái: Media hình ảnh / GIF minh họa chuyển động -->
      <aside class="detail-visual-col">
        <div class="media-showcase-panel shadow-sm">
          <div id="minh-hoa-bai-tap" class="detail-media-container">
            <AnhBaiTap
              :src="urlMedia(dangXemGif ? baiTap.gif_url : baiTap.anh_url)"
              :alt="tenHienThi + (dangXemGif ? ' — minh họa chuyển động' : ' — ảnh minh họa')"
              loading="eager"
              @loi="loiMedia = true"
            />
            <!-- Huy hiệu trạng thái media -->
            <div v-if="dangXemGif" class="media-live-badge">
              <span class="status-dot"></span>
              <span>Đang phát chuyển động</span>
            </div>
          </div>

          <!-- Nút chuyển đổi chế độ xem GIF / Ảnh tĩnh -->
          <div class="media-actions-bar p-3 border-top bg-light">
            <button
              v-if="baiTap.gif_url"
              class="btn w-100 shadow-sm"
              :class="dangXemGif ? 'btn-danger-soft' : 'btn-primary'"
              :aria-pressed="dangXemGif"
              aria-controls="minh-hoa-bai-tap"
              @click="doiMinhHoa"
            >
              <i :class="dangXemGif ? 'bi bi-stop-circle-fill' : 'bi bi-play-circle-fill'"></i>
              <span>{{
                dangXemGif ? 'Dừng xem chuyển động (GIF)' : 'Xem chuyển động mô phỏng (GIF)'
              }}</span>
            </button>

            <div
              v-if="loiMedia"
              class="media-notice-alert mt-2 p-2 alert alert-warning small mb-0"
              role="status"
            >
              <i class="bi bi-info-circle me-1"></i>Minh họa này chưa tải được. Bạn có thể đổi sang
              chế độ xem còn lại.
            </div>

            <div v-if="baiTap.ghi_cong_media" class="text-center mt-2">
              <a
                class="media-credit-link"
                href="https://gymvisual.com/"
                target="_blank"
                rel="noopener noreferrer"
              >
                <i class="bi bi-camera me-1"></i>{{ baiTap.ghi_cong_media || '© Gym visual' }}
              </a>
            </div>
          </div>
        </div>
      </aside>

      <!-- Cột phải: Thông tin kỹ thuật & Hướng dẫn từng bước -->
      <section class="detail-content-col">
        <!-- Huy hiệu nhóm cơ chính -->
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="badge-role badge-role-pt">
            <i class="bi bi-fire"></i>
            <span>{{ baiTap.nhom_co.ten_nhom_co }}</span>
          </span>
          <span class="badge bg-secondary-subtle text-muted border"
            >Mã: #{{ baiTap.ma_nguon || baiTap.id }}</span
          >
        </div>

        <!-- Tên bài tập -->
        <h1 class="exercise-main-title fw-bold mb-1">{{ tenHienThi }}</h1>
        <p
          v-if="baiTap.ten_tieng_viet && baiTap.ten_tieng_viet !== baiTap.ten_bai_tap"
          class="original-title text-muted mb-4"
          lang="en"
        >
          <i class="bi bi-globe me-1"></i>Tên gốc tiếng Anh:
          <strong>{{ baiTap.ten_bai_tap }}</strong>
        </p>

        <!-- Lưới 4 thông số kỹ thuật then chốt -->
        <div class="facts-grid mb-4 contai">
          <div class="fact-box">
            <div class="fact-icon-wrap stat-icon-emerald">
              <i class="bi bi-person-arms-up"></i>
            </div>
            <div class="fact-content">
              <span class="fact-label">Nhóm cơ chính</span>
              <strong class="fact-value text-success">{{ baiTap.nhom_co.ten_nhom_co }}</strong>
            </div>
          </div>

          <div class="fact-box">
            <div class="fact-icon-wrap stat-icon-amber">
              <i class="bi bi-tools"></i>
            </div>
            <div class="fact-content">
              <span class="fact-label">Dụng cụ tập</span>
              <strong class="fact-value">{{ baiTap.dung_cu || 'Chưa cung cấp' }}</strong>
            </div>
          </div>

          <div class="fact-box">
            <div class="fact-icon-wrap stat-icon-blue">
              <i class="bi bi-diagram-2"></i>
            </div>
            <div class="fact-content">
              <span class="fact-label">Cơ phụ bổ trợ</span>
              <strong class="fact-value" lang="en">
                {{ baiTap.co_phu.length ? baiTap.co_phu.join(', ') : 'Chưa cung cấp' }}
              </strong>
            </div>
          </div>

          <div class="fact-box">
            <div class="fact-icon-wrap stat-icon-purple">
              <i class="bi bi-upc-scan"></i>
            </div>
            <div class="fact-content">
              <span class="fact-label">Mã kỹ thuật</span>
              <strong class="fact-value font-monospace">{{ baiTap.ma_nguon || 'CHƯA CÓ' }}</strong>
            </div>
          </div>
        </div>

        <!-- Phần hướng dẫn thực hiện từng bước -->
        <section class="instructions-panel card-modern mb-4" aria-labelledby="tieu-de-huong-dan">
          <div class="panel-heading">
            <h2 id="tieu-de-huong-dan" class="h5 fw-bold mb-0">
              <i class="bi bi-list-ol text-success"></i>
              <span>Hướng dẫn kỹ thuật thực hiện</span>
            </h2>
            <span class="badge bg-secondary-subtle text-body border">
              <i class="bi bi-translate me-1"></i>
              {{ baiTap.ngon_ngu_huong_dan === 'vi' ? 'Tiếng Việt' : 'Bản gốc tiếng Anh' }}
            </span>
          </div>

          <div v-if="baiTap.ngon_ngu_huong_dan !== 'vi'" class="alert alert-info p-2 small mb-3">
            <i class="bi bi-info-circle me-1"></i>Bài tập này hiện đang hiển thị bản hướng dẫn chuẩn
            gốc tiếng Anh.
          </div>

          <!-- Các bước thực hiện dạng timeline trực quan -->
          <div
            v-if="baiTap.cac_buoc.length"
            class="timeline-steps"
            :lang="baiTap.ngon_ngu_huong_dan"
          >
            <div v-for="(buoc, thuTu) in baiTap.cac_buoc" :key="thuTu" class="timeline-step-item">
              <div class="step-badge-number">
                {{ thuTu + 1 < 10 ? '0' + (thuTu + 1) : thuTu + 1 }}
              </div>
              <div class="step-text-content">
                <div class="step-label">Bước {{ thuTu + 1 }}</div>
                <p class="step-desc mb-0">{{ buoc }}</p>
              </div>
            </div>
          </div>

          <div
            v-else-if="baiTap.huong_dan"
            class="p-3 bg-light rounded-3 border"
            :lang="baiTap.ngon_ngu_huong_dan"
          >
            <p class="mb-0 text-muted instruction-raw-text">{{ baiTap.huong_dan }}</p>
          </div>

          <p v-else class="text-muted small mb-0">
            Chưa có bản hướng dẫn chi tiết cho bài tập này.
          </p>
        </section>

        <!-- Thẻ lưu ý an toàn từ Huấn luyện viên -->
        <div class="coach-tip-card p-3 rounded-3 border mb-4">
          <div class="d-flex align-items-center gap-2 mb-2">
            <div class="coach-icon-badge">
              <i class="bi bi-award-fill"></i>
            </div>
            <strong class="text-body small">Lưu ý chuẩn form từ Huấn Luyện Viên:</strong>
          </div>
          <p class="small text-muted mb-0">
            Hãy kiểm soát nhịp thở đều đặn (hít sâu khi hạ tạ và thở dứt khoát khi phát lực). Không
            vội vàng tăng mức tạ khi tư thế động tác chưa hoàn toàn chuẩn xác.
          </p>
        </div>

        <!-- Chân ghi công nguồn dữ liệu -->
        <p
          v-if="baiTap.nguon_du_lieu === 'exercises-dataset'"
          class="dataset-credit text-muted small border-top pt-3"
        >
          Dữ liệu bài tập được tổng hợp và tham khảo từ
          <a
            href="https://github.com/hasaneyldrm/exercises-dataset"
            target="_blank"
            rel="noopener noreferrer"
          >
            exercises-dataset </a
          >. Số hiệp (sets), số lần (reps) và thời gian nghỉ cụ thể sẽ được Huấn luyện viên thiết
          lập trong kế hoạch tập luyện cá nhân của bạn.
        </p>
      </section>
    </article>
  </DanhMucLayout>
</template>

<script>
import DanhMucLayout from '../../../layouts/DanhMucLayout.vue'
import AnhBaiTap from '../../../components/AnhBaiTap.vue'
import baiTapService from '../../../services/baiTapService'
import { layLoiApi } from '../../../utils/loiApi'

export default {
  name: 'ChiTietBaiTap',
  components: { DanhMucLayout, AnhBaiTap },
  data() {
    return {
      baiTap: null,
      dangTai: false,
      thongBao: '',
      khongTimThay: false,
      dangXemGif: false,
      loiMedia: false,
      soLanTai: 0,
      huyYeuCau: null,
    }
  },
  computed: {
    tenHienThi() {
      return this.baiTap?.ten_tieng_viet || this.baiTap?.ten_bai_tap || ''
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
    this.soLanTai++
    this.huyYeuCau?.abort()
  },
  methods: {
    urlMedia: baiTapService.urlMedia,
    doiMinhHoa() {
      this.dangXemGif = !this.dangXemGif
      this.loiMedia = false
    },
    async taiChiTiet() {
      const lanTai = ++this.soLanTai
      this.huyYeuCau?.abort()
      this.huyYeuCau = new AbortController()
      this.dangTai = true
      this.thongBao = ''
      this.khongTimThay = false
      this.dangXemGif = false
      this.loiMedia = false
      this.baiTap = null
      try {
        const ketQua = await baiTapService.taiChiTiet(this.$route.params.id, this.huyYeuCau.signal)
        if (lanTai === this.soLanTai) this.baiTap = ketQua.data
      } catch (loi) {
        if (lanTai !== this.soLanTai) return
        this.thongBao = layLoiApi(loi).thongBao
        this.khongTimThay = loi.response?.status === 404
      } finally {
        if (lanTai === this.soLanTai) this.dangTai = false
      }
    },
  },
}
</script>

<style scoped>
.btn-back-library {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: var(--mau-the-sub, #181818);
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-tron);
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--mau-chu);
  text-decoration: none;
  box-shadow: var(--bong-nhe);
  transition: all 0.2s ease;
}

.btn-back-library:hover {
  background: var(--mau-the-hover, #242424);
  color: var(--mau-chinh);
  border-color: var(--mau-chinh);
  transform: translateX(-3px);
}

.detail-state-card {
  min-height: 400px;
  background: var(--mau-the, #141414);
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
  background: rgba(245, 158, 11, 0.15);
  display: grid;
  place-items: center;
  font-size: 2.2rem;
}

/* Bố cục 2 cột chi tiết */
.exercise-detail-layout {
  display: grid;
  grid-template-columns: minmax(320px, 0.95fr) minmax(0, 1.25fr);
  gap: 48px;
  align-items: start;
}

/* Cột trái: Media */
.detail-visual-col {
  position: sticky;
  top: 90px;
}

.media-showcase-panel {
  background: var(--mau-the, #141414);
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-xl);
  overflow: hidden;
}

.detail-media-container {
  position: relative;
  background: #101010;
}

.media-live-badge {
  position: absolute;
  top: 14px;
  right: 14px;
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(8px);
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: var(--bo-goc-tron);
  display: flex;
  align-items: center;
  gap: 6px;
  z-index: 5;
}

.media-credit-link {
  font-size: 0.75rem;
  color: #94a3b8;
  text-decoration: none;
  transition: color 0.2s;
}

.media-credit-link:hover {
  color: var(--mau-chinh);
}

/* Cột phải: Thông tin */
.exercise-main-title {
  font-size: clamp(1.8rem, 3.2vw, 2.6rem);
  line-height: 1.2;
  color: var(--mau-chu);
}

.original-title {
  font-size: 0.95rem;
}

/* Lưới 4 thông số kỹ thuật */
.facts-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.fact-box {
  background: var(--mau-the, #141414);
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-md);
  padding: 14px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: var(--bong-nhe);
}

.fact-icon-wrap {
  width: 42px;
  height: 42px;
  border-radius: var(--bo-goc-sm);
  display: grid;
  place-items: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.fact-label {
  display: block;
  font-size: 0.75rem;
  color: var(--mau-phu);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  font-weight: 600;
}

.fact-value {
  display: block;
  font-size: 0.95rem;
  color: var(--mau-chu);
  overflow-wrap: anywhere;
}

/* Timeline từng bước hướng dẫn */
.timeline-steps {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.timeline-step-item {
  display: flex;
  gap: 16px;
  background: var(--mau-the-sub, #181818);
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-md);
  padding: 16px 20px;
  transition: all 0.2s ease;
}

.timeline-step-item:hover {
  background: var(--mau-the-hover, #202020);
  border-color: rgba(244, 91, 32, 0.35);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
}

.step-badge-number {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: var(--mau-gradient-chinh);
  color: white;
  display: grid;
  place-items: center;
  font-weight: 800;
  font-size: 0.9rem;
  flex-shrink: 0;
  box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
}

.step-label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--mau-chinh);
  margin-bottom: 4px;
}

.step-desc {
  font-size: 0.95rem;
  color: var(--mau-chu);
  line-height: 1.6;
}

.instruction-raw-text {
  white-space: pre-line;
  line-height: 1.7;
}

.coach-tip-card {
  background: rgba(244, 91, 32, 0.1);
  border-color: rgba(244, 91, 32, 0.25) !important;
}

.coach-icon-badge {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--mau-chinh, #f45b20);
  color: white;
  display: grid;
  place-items: center;
  font-size: 0.85rem;
}

@media (max-width: 992px) {
  .exercise-detail-layout {
    grid-template-columns: 1fr;
    gap: 32px;
  }
  .detail-visual-col {
    position: static;
  }
  .media-showcase-panel {
    max-width: 480px;
    margin: 0 auto;
  }
}

@media (max-width: 540px) {
  .facts-grid {
    grid-template-columns: 1fr;
  }
  .timeline-step-item {
    padding: 12px;
    gap: 12px;
  }
  .step-badge-number {
    width: 32px;
    height: 32px;
    font-size: 0.8rem;
  }
}
</style>
