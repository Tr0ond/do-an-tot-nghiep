<template>
  <CaNhanLayout>
    <section class="ga">
      <!-- Nút điều hướng quay lại -->
      <div class="mb-4 d-flex align-items-center justify-content-between">
        <RouterLink to="/pt/giao-an-mau" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left" aria-hidden="true"></i> Thư viện giáo án
        </RouterLink>
        <span v-if="giaoAn" class="ga-status DA_DUYET">
          <i class="bi bi-shield-check me-1"></i> Giáo án đã duyệt
        </span>
      </div>

      <!-- Trạng thái Đang tải -->
      <div v-if="dangTai" class="ga-panel ga-empty" role="status">
        <div class="spinner-border text-success mb-3" role="status"></div>
        <h2>Đang tải giáo án…</h2>
        <p>Vui lòng đợi trong giây lát.</p>
      </div>

      <!-- Trạng thái Báo lỗi -->
      <div v-else-if="loi" class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
        {{ loi }}
        <div class="mt-3">
          <RouterLink v-if="hetPhien" to="/dang-nhap" class="btn btn-outline-secondary">
            Đăng nhập lại
          </RouterLink>
          <button v-else class="btn btn-outline-secondary" @click="taiGiaoAn">Thử lại</button>
        </div>
      </div>

      <!-- Chi tiết giáo án mẫu (Phong cách Ảnh 2 với màu sắc thương hiệu) -->
      <template v-else-if="giaoAn">
        <div class="ga-detail-container">
          <!-- Hero Banner màu xanh lục thể thao đậm chất dự án (Ảnh 2) -->
          <header class="ga-hero-dark">
            <div>
              <span class="ga-eyebrow" style="color: #34d399">
                <i class="bi bi-award-fill"></i> Giáo án chuẩn huấn luyện
              </span>
              <h1>{{ giaoAn.ten_giao_an }}</h1>
              <div class="ga-hero-sub">
                <span>{{ giaoAn.muc_tieu || 'Giáo án huấn luyện thể hình chuẩn khoa học' }}</span>
                <span>·</span>
                <span
                  >Chu kỳ {{ giaoAn.so_ngay_tap }} ngày
                  <i class="bi bi-clipboard2-check text-success"></i
                ></span>
              </div>

              <!-- Stats Pill Row (Ảnh 2: "6 exercises · 20 sets") -->
              <div class="ga-stats-pill-row">
                <span class="ga-stats-pill">
                  <i class="bi bi-calendar3"></i>
                  {{ giaoAn.so_ngay_tap }} ngày tập
                </span>
                <span class="ga-stats-pill">
                  <i class="bi bi-fire"></i>
                  {{ giaoAn.so_bai_tap }} bài tập
                </span>
                <span class="ga-stats-pill">
                  <i class="bi bi-layers-half"></i>
                  {{ tinhTongHiep() }} tổng hiệp
                </span>
              </div>

              <div class="d-flex align-items-center gap-3 flex-wrap">
                <button type="button" class="btn btn-start-action" @click="ngayChon = 1">
                  <i class="bi bi-lightning-charge-fill me-1"></i> Bắt đầu xem bài tập
                </button>
                <small style="color: var(--mau-phu)">
                  Hãy điều chỉnh theo thể trạng học viên khi áp dụng thực tế.
                </small>
              </div>
            </div>

            <!-- Minh họa giải phẫu cơ thể / Muscle Silhouette Highlight (Ảnh 2) -->
          </header>

          <!-- Day Switcher Navigation Bar (Ảnh 1 & 2) -->
          <nav class="ga-day-switcher-bar" aria-label="Chọn ngày xem bài tập">
            <button
              v-for="ngay in giaoAn.so_ngay_tap"
              :key="ngay"
              type="button"
              class="ga-day-tab"
              :class="{ 'is-active': ngayChon === ngay }"
              @click="ngayChon = ngay"
            >
              <span>Ngày {{ ngay }}</span>
              <span class="tab-badge">{{ cacBaiTheoNgay(ngay).length }} bài</span>
            </button>
          </nav>

          <!-- Exercise Sheet (Ảnh 2: Khung bài tập tối màu với thẻ từng bài) -->
          <section class="ga-exercise-sheet">
            <div class="ga-exercise-sheet-header">
              <h2>
                <i class="bi bi-calendar2-day text-success me-2"></i>
                Lịch tập Ngày {{ ngayChon }}
              </h2>
              <span class="sheet-meta">
                {{ cacBaiTheoNgay(ngayChon).length }} bài tập · {{ tongHiepNgay(ngayChon) }} hiệp
              </span>
            </div>

            <!-- Danh sách bài tập Ngày đang chọn -->
            <div v-if="!cacBaiTheoNgay(ngayChon).length" class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
              Ngày này chưa có bài tập nào được phân công.
            </div>

            <div v-else class="ga-exercise-list">
              <article
                v-for="bai in cacBaiTheoNgay(ngayChon)"
                :key="bai.ngay_thu + '-' + bai.thu_tu"
                class="ga-exercise-card"
                @click="moHuongDan(bai)"
              >
                <div class="ga-exercise-card-left">
                  <!-- Thumbnail hình ảnh / icon bài tập -->
                  <div class="ga-exercise-thumb">
                    <img
                      v-if="bai.anh_url"
                      :src="baiTapService.urlMedia(bai.anh_url)"
                      :alt="bai.ten_bai_tap"
                      loading="lazy"
                    />
                    <i v-else class="bi bi-activity thumb-fallback-icon"></i>
                  </div>

                  <!-- Thông tin bài tập -->
                  <div class="ga-exercise-info">
                    <h3>{{ bai.thu_tu }}. {{ bai.ten_bai_tap }}</h3>
                    <div class="ga-exercise-meta">
                      <span class="meta-tag">{{ bai.dung_cu || bai.nhom_co }}</span>
                      <span>·</span>
                      <span>{{ bai.so_hiep }} hiệp × {{ bai.so_lan_lap }} lần</span>
                      <span>·</span>
                      <span>Nghỉ {{ bai.nghi_giay }}s</span>
                    </div>

                    <!-- Ghi chú nếu có -->
                    <p v-if="bai.ghi_chu" class="ga-note mt-2 mb-0">
                      <i class="bi bi-pencil-square me-1"></i> {{ bai.ghi_chu }}
                    </p>

                    <!-- Cảnh báo nếu ngừng sử dụng -->
                    <p v-if="!bai.kha_dung" class="ga-warning mt-2 mb-0">
                      Bài tập hoặc nhóm cơ đã ngừng sử dụng. Cần chọn bài thay thế khi lập kế hoạch
                      mới.
                    </p>
                  </div>
                </div>

                <div class="ga-exercise-card-right">
                  <button type="button" class="ga-btn-guide" @click.stop="moHuongDan(bai)">
                    <i class="bi bi-play-circle-fill me-1"></i>
                    <span>Xem hướng dẫn</span>
                  </button>
                </div>
              </article>
            </div>
          </section>

          <!-- Danh sách toàn bộ ngày tập để tham khảo tổng quan (Legacy fallback) -->
          <div class="mt-4">
            <details class="ga-panel">
              <summary class="fw-bold text-muted cursor-pointer py-2">
                <i class="bi bi-list-nested me-1"></i> Xem danh sách toàn bộ
                {{ giaoAn.so_ngay_tap }} ngày dạng rút gọn
              </summary>
              <div class="pt-3">
                <article
                  v-for="ngay in giaoAn.so_ngay_tap"
                  :key="'full-' + ngay"
                  class="ga-day-detail mb-3"
                >
                  <header>
                    <h2>Ngày {{ ngay }}</h2>
                    <span class="ga-meta">{{ cacBaiTheoNgay(ngay).length }} bài</span>
                  </header>
                  <div
                    v-for="bai in cacBaiTheoNgay(ngay)"
                    :key="'row-' + bai.ngay_thu + '-' + bai.thu_tu"
                    class="ga-detail-row"
                  >
                    <strong>{{ bai.thu_tu }}. {{ bai.ten_bai_tap }}</strong>
                    <div class="ga-meta">
                      <span>{{ bai.nhom_co }}</span>
                      <span>{{ bai.so_hiep }} hiệp × {{ bai.so_lan_lap }} lần</span>
                      <span>Nghỉ {{ bai.nghi_giay }} giây</span>
                    </div>
                    <p v-if="bai.ghi_chu" class="ga-note mb-0">{{ bai.ghi_chu }}</p>
                    <RouterLink
                      v-if="bai.kha_dung"
                      :to="'/bai-tap/' + bai.bai_tap_id"
                      class="btn btn-outline-secondary mt-2 btn-sm"
                    >
                      Mở trang bài tập <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </RouterLink>
                  </div>
                </article>
              </div>
            </details>
          </div>
        </div>
      </template>

      <!-- Exercise Instructions Modal / Drawer (Ảnh 3 & Sửa lỗi GIF) -->
      <div
        v-if="baiTapHuongDan"
        class="ga-modal-backdrop"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-exercise-title"
        @click.self="dongHuongDan"
      >
        <div class="ga-modal-container">
          <!-- Top bar (Close - Avatar - Chi tiết) -->
          <div class="ga-modal-top-bar">
            <button type="button" class="ga-modal-close-btn" @click="dongHuongDan">
              <i class="bi bi-x-lg"></i>
              <span>Đóng</span>
            </button>

            <!-- Round Avatar Thumbnail -->
            <div class="ga-modal-avatar">
              <img
                v-if="baiTapHuongDan.anh_url"
                :src="baiTapService.urlMedia(baiTapHuongDan.anh_url)"
                :alt="baiTapHuongDan.ten_bai_tap"
              />
              <i v-else class="bi bi-activity text-success fs-5"></i>
            </div>

            <RouterLink
              :to="'/bai-tap/' + baiTapHuongDan.bai_tap_id"
              class="ga-modal-close-btn text-decoration-none"
              title="Xem trang chi tiết bài tập đầy đủ"
            >
              <span>Chi tiết</span>
              <i class="bi bi-box-arrow-up-right"></i>
            </RouterLink>
          </div>

          <!-- Navigation tabs: Info | Instructions | History -->
          <nav class="ga-modal-nav-tabs">
            <button
              type="button"
              class="ga-modal-tab-btn"
              :class="{ 'is-active': tabHuongDan === 'huong_dan' }"
              @click="tabHuongDan = 'huong_dan'"
            >
              Hướng dẫn (Instructions)
            </button>
            <button
              type="button"
              class="ga-modal-tab-btn"
              :class="{ 'is-active': tabHuongDan === 'info' }"
              @click="tabHuongDan = 'info'"
            >
              Thông tin
            </button>
            <button
              type="button"
              class="ga-modal-tab-btn"
              :class="{ 'is-active': tabHuongDan === 'khoi_luong' }"
              @click="tabHuongDan = 'khoi_luong'"
            >
              Khối lượng tập
            </button>
          </nav>

          <!-- Modal Body Content -->
          <div class="ga-modal-content">
            <!-- Media Preview Showcase với GIF chuyển động hoàn chỉnh (Ảnh 3) -->
            <div
              class="ga-media-showcase"
              :title="
                dangXemGif ? 'Bấm để tạm dừng chuyển động' : 'Bấm để xem chuyển động mô phỏng (GIF)'
              "
              @click="doiTrangThaiGif"
            >
              <!-- Live indicator khi đang phát chuyển động GIF -->
              <div v-if="dangXemGif && chiTietBaiTap?.gif_url" class="ga-media-live-badge">
                <span class="ga-pulse-dot"></span>
                <span>Đang phát chuyển động (GIF)</span>
              </div>

              <!-- Hình ảnh hoặc GIF -->
              <img
                v-if="dangXemGif && chiTietBaiTap?.gif_url"
                :src="baiTapService.urlMedia(chiTietBaiTap.gif_url)"
                :alt="baiTapHuongDan.ten_bai_tap + ' — minh họa chuyển động'"
                loading="eager"
              />
              <img
                v-else-if="chiTietBaiTap?.anh_url || baiTapHuongDan.anh_url"
                :src="baiTapService.urlMedia(chiTietBaiTap?.anh_url || baiTapHuongDan.anh_url)"
                :alt="baiTapHuongDan.ten_bai_tap + ' — hình ảnh tĩnh'"
                loading="eager"
              />
              <div v-else class="p-4 text-center text-muted">
                <i class="bi bi-play-circle fs-1 text-secondary"></i>
                <p class="small mb-0 mt-2">Đang tải minh họa…</p>
              </div>

              <!-- Play button icon overlay khi tạm dừng -->
              <div
                v-if="!dangXemGif && chiTietBaiTap?.gif_url"
                class="ga-play-btn-circle position-absolute"
              >
                <i class="bi bi-play-fill"></i>
              </div>
            </div>

            <!-- Nút bật/dừng GIF rõ ràng bên dưới -->
            <button
              v-if="chiTietBaiTap?.gif_url"
              type="button"
              class="btn btn-sm w-100"
              :class="dangXemGif ? 'btn-outline-secondary' : 'btn-success'"
              @click="doiTrangThaiGif"
            >
              <i :class="dangXemGif ? 'bi bi-pause-circle-fill' : 'bi bi-play-circle-fill'"></i>
              <span>{{
                dangXemGif ? 'Tạm dừng xem chuyển động (GIF)' : 'Xem chuyển động mô phỏng (GIF)'
              }}</span>
            </button>

            <!-- Tiêu đề bài tập & Huy hiệu khối lượng -->
            <div class="d-flex align-items-center justify-content-between pt-1">
              <div>
                <h3 id="modal-exercise-title" class="fw-bold mb-1">
                  {{ baiTapHuongDan.ten_bai_tap }}
                </h3>
                <span class="text-success small fw-bold">
                  {{ baiTapHuongDan.dung_cu || baiTapHuongDan.nhom_co }}
                </span>
              </div>
              <span
                class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-bold"
              >
                {{ baiTapHuongDan.so_hiep }} hiệp × {{ baiTapHuongDan.so_lan_lap }} lần
              </span>
            </div>

            <!-- Tab 1: Hướng dẫn thực hiện (Setup & Execution - Ảnh 3) -->
            <div v-if="tabHuongDan === 'huong_dan'" class="ga-instructions-section">
              <!-- Loading detail status -->
              <div v-if="dangTaiChiTiet" class="text-center py-2 text-muted small">
                <div class="spinner-border spinner-border-sm text-success me-2"></div>
                Đang nạp hướng dẫn chuẩn form…
              </div>

              <!-- Setup Steps -->
              <div>
                <h4 class="mb-2">
                  <i class="bi bi-check-circle-fill text-success"></i>
                  Tư thế chuẩn bị (Setup)
                </h4>
                <ul v-if="cacBuocSetup.length">
                  <li v-for="(buoc, idx) in cacBuocSetup" :key="'setup-' + idx">
                    {{ buoc }}
                  </li>
                </ul>
                <ul v-else>
                  <li>
                    Đặt hai chân rộng bằng vai, mũi chân hướng nhẹ ra ngoài theo trục đầu gối.
                  </li>
                  <li>Lưng giữ thẳng tự nhiên, siết chặt cơ bụng và mở ngực, hai vai hạ lỏng.</li>
                  <li>
                    Cầm tạ chắc chắn ở hai bên người, giữ trọng tâm ổn định trước khi chuyển động.
                  </li>
                </ul>
              </div>

              <!-- Execution Steps -->
              <div class="mt-2">
                <h4 class="mb-2">
                  <i class="bi bi-arrow-repeat text-success"></i>
                  Thực hiện động tác (Execution)
                </h4>
                <ul v-if="cacBuocExecution.length">
                  <li v-for="(buoc, idx) in cacBuocExecution" :key="'exec-' + idx">
                    {{ buoc }}
                  </li>
                </ul>
                <ul v-else>
                  <li>
                    Hít sâu, chậm rãi đẩy hông ra sau và hạ đùi xuống cho đến khi đùi song song với
                    sàn.
                  </li>
                  <li>Dừng lại 1 nhịp ở đáy chuyển động để cảm nhận cơ bắp căng hết biên độ.</li>
                  <li>
                    Thở dứt khoát, dùng lực gót chân đạp mạnh đẩy thân người đứng thẳng về tư thế
                    ban đầu.
                  </li>
                </ul>
              </div>
            </div>

            <!-- Tab 2: Thông tin bài tập -->
            <div v-else-if="tabHuongDan === 'info'" class="ga-instructions-section">
              <div class="p-3 rounded-3 ga-modal-info-card">
                <h4 class="mb-2">Tổng quan động tác</h4>
                <p class="text-muted small mb-2">
                  Bài tập tác động chủ đạo vào nhóm cơ <strong>{{ baiTapHuongDan.nhom_co }}</strong
                  >, sử dụng dụng cụ
                  <strong>{{ baiTapHuongDan.dung_cu || 'Tiêu chuẩn phòng gym' }}</strong
                  >.
                </p>
                <p v-if="chiTietBaiTap?.huong_dan" class="small mb-0">
                  {{ chiTietBaiTap.huong_dan }}
                </p>
              </div>
            </div>

            <!-- Tab 3: Khối lượng tập trong giáo án -->
            <div v-else class="ga-instructions-section">
              <div class="p-3 rounded-3 ga-modal-info-card">
                <h4 class="mb-3">Chỉ định của giáo án</h4>
                <div class="d-flex flex-column gap-2">
                  <div class="d-flex justify-content-between border-bottom pb-2 ga-info-divider">
                    <span class="text-muted">Số hiệp thực hiện:</span>
                    <strong class="text-success">{{ baiTapHuongDan.so_hiep }} hiệp</strong>
                  </div>
                  <div class="d-flex justify-content-between border-bottom pb-2 ga-info-divider">
                    <span class="text-muted">Số lần lặp mỗi hiệp:</span>
                    <strong class="text-success">{{ baiTapHuongDan.so_lan_lap }} lần (reps)</strong>
                  </div>
                  <div class="d-flex justify-content-between border-bottom pb-2 ga-info-divider">
                    <span class="text-muted">Thời gian nghỉ:</span>
                    <strong class="text-success">{{ baiTapHuongDan.nghi_giay }} giây</strong>
                  </div>
                  <div v-if="baiTapHuongDan.ghi_chu" class="pt-2">
                    <span class="text-muted d-block mb-1">Ghi chú riêng của HLV:</span>
                    <p class="text-info small mb-0">{{ baiTapHuongDan.ghi_chu }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../../../layouts/CaNhanLayout.vue'
import giaoAnMauService from '../../../../services/giaoAnMauService'
import baiTapService from '../../../../services/baiTapService'
import { layLoiApi } from '../../../../utils/loiApi'
import '../../../../assets/giaoAnMau.css'

export default {
  name: 'ChiTietGiaoAnPT',
  components: { CaNhanLayout },
  data() {
    return {
      giaoAn: null,
      dangTai: false,
      loi: '',
      huyTai: null,
      hetPhien: false,
      ngayChon: 1,
      baiTapHuongDan: null,
      chiTietBaiTap: null,
      dangTaiChiTiet: false,
      dangXemGif: true,
      tabHuongDan: 'huong_dan',
    }
  },
  computed: {
    baiTapService() {
      return baiTapService
    },
    cacBuocSetup() {
      if (!this.chiTietBaiTap?.cac_buoc?.length) return []
      // Phân chia 1-2 bước đầu tiên làm setup
      return this.chiTietBaiTap.cac_buoc.slice(
        0,
        Math.min(2, Math.ceil(this.chiTietBaiTap.cac_buoc.length / 2)),
      )
    },
    cacBuocExecution() {
      if (!this.chiTietBaiTap?.cac_buoc?.length) return []
      // Các bước còn lại làm execution
      return this.chiTietBaiTap.cac_buoc.slice(
        Math.min(2, Math.ceil(this.chiTietBaiTap.cac_buoc.length / 2)),
      )
    },
  },
  mounted() {
    this.taiGiaoAn()
  },
  watch: {
    '$route.params.id'() {
      this.taiGiaoAn()
    },
  },
  beforeUnmount() {
    this.huyTai?.abort()
  },
  methods: {
    cacBaiTheoNgay(ngay) {
      if (!this.giaoAn?.bai_tap) return []
      return this.giaoAn.bai_tap.filter((bai) => Number(bai.ngay_thu) === ngay)
    },
    tinhTongHiep() {
      if (!this.giaoAn?.bai_tap) return 0
      return this.giaoAn.bai_tap.reduce((tong, b) => tong + Number(b.so_hiep || 0), 0)
    },
    tongHiepNgay(ngay) {
      return this.cacBaiTheoNgay(ngay).reduce((tong, b) => tong + Number(b.so_hiep || 0), 0)
    },
    doiTrangThaiGif() {
      this.dangXemGif = !this.dangXemGif
    },
    async moHuongDan(bai) {
      this.baiTapHuongDan = bai
      this.chiTietBaiTap = null
      this.tabHuongDan = 'huong_dan'
      this.dangXemGif = true
      this.dangTaiChiTiet = true
      try {
        const ketQua = await baiTapService.taiChiTiet(bai.bai_tap_id)
        this.chiTietBaiTap = ketQua.data
      } catch {
        // Fallback to basic exercise info already in bai
      } finally {
        this.dangTaiChiTiet = false
      }
    },
    dongHuongDan() {
      this.baiTapHuongDan = null
      this.chiTietBaiTap = null
    },
    async taiGiaoAn() {
      this.huyTai?.abort()
      const huy = new AbortController()
      this.huyTai = huy
      this.dangTai = true
      this.giaoAn = null
      this.loi = ''
      this.hetPhien = false
      try {
        const ketQua = await giaoAnMauService.taiChiTiet(this.$route.params.id, huy.signal)
        if (!huy.signal.aborted) {
          this.giaoAn = ketQua.data
          this.ngayChon = 1
        }
      } catch (loi) {
        if (!huy.signal.aborted) {
          this.hetPhien = layLoiApi(loi).hetPhien
          this.loi =
            loi.response?.status === 404
              ? 'Giáo án không tồn tại hoặc không còn được duyệt để sử dụng.'
              : layLoiApi(loi).thongBao
        }
      } finally {
        if (this.huyTai === huy) this.dangTai = false
      }
    },
  },
}
</script>
