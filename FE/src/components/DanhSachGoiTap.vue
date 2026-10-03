<template>
  <div class="package-catalog">
    <!-- Header danh mục: Phân biệt giữa Admin và Bảng giá công khai -->
    <header class="package-heading animate__animated animate__fadeIn">
      <div>
        <div class="eyebrow mb-2">
          <i :class="quanTri ? 'bi-shield-lock-fill' : 'bi-stars'"></i>
          <span>{{
            quanTri
              ? 'QUẢN TRỊ VIÊN · DANH MỤC DỊCH VỤ'
              : 'BẢNG GIÁ MINH BẠCH · TẬP LUYỆN TOÀN DIỆN'
          }}</span>
        </div>
        <h1 class="h2 fw-bold mb-2">
          {{ quanTri ? 'Quản lý gói tập & Bảng giá' : 'Bảng giá gói tập & Dịch vụ' }}
        </h1>
        <p class="text-muted small mb-0 max-w-700">
          {{
            quanTri
              ? 'Thiết lập cấu hình đơn giá, thời hạn hiệu lực, quyền lợi buổi tập PT và hạn mức truy vấn AI Chatbot.'
              : 'Chọn gói tập phù hợp với nhu cầu của bạn: Trợ lý AI Chatbot hướng dẫn dinh dưỡng hoặc Huấn luyện viên kèm 1-1.'
          }}
        </p>
      </div>

      <div v-if="quanTri" class="d-flex align-items-center">
        <RouterLink class="btn btn-primary shadow-sm" to="/admin/goi-tap/them">
          <i class="bi bi-plus-circle-fill"></i>
          <span>Thêm gói tập mới</span>
        </RouterLink>
      </div>
    </header>

    <!-- Thống kê nhanh dành cho Admin -->
    <div v-if="quanTri" class="stats-grid mb-4">
      <div class="stat-item-card">
        <div class="stat-icon-wrapper stat-icon-emerald">
          <i class="bi bi-boxes"></i>
        </div>
        <div class="stat-info-content">
          <h3>{{ meta.total || 0 }}</h3>
          <p>Tổng gói trong hệ thống</p>
        </div>
      </div>

      <div class="stat-item-card">
        <div class="stat-icon-wrapper stat-icon-blue">
          <i class="bi bi-check-circle-fill"></i>
        </div>
        <div class="stat-info-content">
          <h3>{{ soLuongDangBan }}</h3>
          <p>Đang mở bán công khai</p>
        </div>
      </div>

      <div class="stat-item-card">
        <div class="stat-icon-wrapper stat-icon-amber">
          <i class="bi bi-pause-circle-fill"></i>
        </div>
        <div class="stat-info-content">
          <h3>{{ soLuongNgungBan }}</h3>
          <p>Tạm ngừng phát hành</p>
        </div>
      </div>

      <div class="stat-item-card">
        <div class="stat-icon-wrapper stat-icon-purple">
          <i class="bi bi-award-fill"></i>
        </div>
        <div class="stat-info-content">
          <h3>{{ soLuongGoiPt }}</h3>
          <p>Gói có kèm HLV 1:1</p>
        </div>
      </div>
    </div>

    <!-- Thông báo kết quả tác vụ -->
    <div
      v-if="thongBao"
      class="alert d-flex align-items-center gap-2 p-3 mb-4 rounded-3 animate__animated animate__fadeIn"
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
      ></i>
      <span class="small fw-semibold flex-grow-1">{{ thongBao }}</span>
      <button type="button" class="btn-close" @click="thongBao = ''" aria-label="Đóng"></button>
    </div>

    <!-- Thanh tìm kiếm & Bộ lọc -->
    <form class="package-filters-panel shadow-sm mb-4" @submit.prevent="apDungBoLoc">
      <div class="filter-field-search">
        <label for="tim-goi" class="form-label d-flex align-items-center gap-1">
          <i class="bi bi-search text-success"></i>
          <span>Tìm kiếm gói</span>
        </label>
        <div class="input-group-modern">
          <span class="input-icon-prefix"><i class="bi bi-search"></i></span>
          <input
            id="tim-goi"
            v-model="boLoc.tu_khoa"
            class="form-control form-control-sm has-prefix"
            maxlength="100"
            placeholder="Nhập tên gói tập cần tìm…"
          />
        </div>
      </div>

      <div class="filter-field-item">
        <label for="loai-goi-loc" class="form-label d-flex align-items-center gap-1">
          <i class="bi bi-boxes text-primary"></i>
          <span>Loại gói</span>
        </label>
        <select id="loai-goi-loc" v-model="boLoc.loai_goi" class="form-select form-select-sm">
          <option value="">Tất cả loại gói</option>
          <option value="CHATBOT">Chatbot riêng</option>
          <option value="PT_CHATBOT">PT kèm chatbot</option>
        </select>
      </div>

      <div v-if="quanTri" class="filter-field-item">
        <label for="trang-thai-goi" class="form-label d-flex align-items-center gap-1">
          <i class="bi bi-toggle-on text-amber"></i>
          <span>Trạng thái mở bán</span>
        </label>
        <select id="trang-thai-goi" v-model="boLoc.trang_thai" class="form-select form-select-sm">
          <option value="">Tất cả trạng thái</option>
          <option value="HOAT_DONG">Đang mở bán</option>
          <option value="NGUNG_SU_DUNG">Ngừng bán</option>
        </select>
      </div>

      <div class="filter-actions-group">
        <button type="submit" class="btn btn-primary btn-sm flex-grow-1" :disabled="dangTai">
          <i class="bi bi-funnel-fill"></i>
          <span>Lọc gói</span>
        </button>
        <button
          v-if="coBoLoc"
          type="button"
          class="btn btn-outline-secondary btn-sm"
          title="Đặt lại bộ lọc"
          @click="xoaBoLoc"
        >
          <i class="bi bi-arrow-counterclockwise"></i>
        </button>
      </div>
    </form>

    <!-- Khối trạng thái (Lỗi, Tải, Rỗng) -->
    <div
      v-if="loiTai"
      class="catalog-state-box alert alert-danger border-danger-subtle p-5 text-center"
      role="alert"
    >
      <i class="bi bi-exclamation-triangle-fill fs-1 text-danger mb-2"></i>
      <h3 class="h5 fw-bold text-danger mb-2">Chưa thể tải dữ liệu gói tập</h3>
      <p class="mb-3 small">{{ loiTai }}</p>
      <button class="btn btn-outline-danger btn-sm" @click="taiDanhSach">
        <i class="bi bi-arrow-clockwise me-1"></i>Thử tải lại
      </button>
    </div>

    <div
      v-else-if="dangTai"
      class="catalog-state-box p-5 text-center bg-white rounded-4 border shadow-sm"
      role="status"
    >
      <div class="spinner-border text-success mb-3" role="status"></div>
      <p class="text-muted small mb-0">Đang cập nhật danh mục gói tập…</p>
    </div>

    <div
      v-else-if="!danhSach.length"
      class="catalog-state-box p-5 text-center bg-white rounded-4 border shadow-sm"
    >
      <div class="empty-icon-ring mx-auto mb-3">
        <i class="bi bi-box-seam fs-2 text-muted"></i>
      </div>
      <h2 class="h5 fw-bold mb-1">
        {{
          coBoLoc || meta.total
            ? 'Không tìm thấy gói tập phù hợp'
            : quanTri
              ? 'Chưa có gói tập nào trong hệ thống'
              : 'Hiện chưa có gói tập nào đang mở bán'
        }}
      </h2>
      <p class="text-muted small mb-3">
        {{
          coBoLoc || meta.total
            ? 'Hãy thử thay đổi điều kiện tìm kiếm hoặc đặt lại bộ lọc.'
            : quanTri
              ? 'Tạo gói tập mới và kiểm tra quyền lợi trước khi công khai mở bán.'
              : 'Vui lòng quay lại sau để cập nhật các chương trình ưu đãi mới nhất.'
        }}
      </p>
      <button
        v-if="coBoLoc || meta.total"
        class="btn btn-outline-secondary btn-sm"
        @click="xoaBoLoc"
      >
        <i class="bi bi-arrow-counterclockwise me-1"></i>Xem tất cả gói tập
      </button>
    </div>

    <!-- Danh sách gói tập hiển thị -->
    <template v-else>
      <div class="d-flex align-items-center justify-content-between mb-3">
        <span class="badge bg-secondary-subtle text-body border px-3 py-2" role="status">
          {{ dinhDangSo(meta.total) }} gói {{ quanTri ? 'trong danh mục quản trị' : 'đang mở bán' }}
        </span>
      </div>

      <!-- Giao diện Admin: Dạng hàng Card quản trị -->
      <ul v-if="quanTri" class="admin-package-list shadow-sm">
        <li v-for="goi in danhSach" :key="goi.id" class="admin-package-row">
          <div class="package-name-col">
            <div class="d-flex align-items-center gap-2 mb-1">
              <span
                class="badge-role"
                :class="goi.so_buoi_pt > 0 ? 'badge-role-pt' : 'badge-role-khach'"
              >
                <i
                  :class="
                    goi.so_buoi_pt > 0 ? 'bi bi-person-check-fill' : 'bi bi-chat-square-text-fill'
                  "
                ></i>
                <span>{{ goi.so_buoi_pt > 0 ? 'PT kèm chatbot' : 'Chatbot riêng' }}</span>
              </span>
              <span class="badge bg-light text-muted border font-monospace">#{{ goi.id }}</span>
            </div>

            <RouterLink :to="'/admin/goi-tap/' + goi.id + '/sua'" class="package-title-link">
              {{ goi.ten_goi }}
            </RouterLink>

            <div class="small text-muted d-flex align-items-center gap-2 flex-wrap mt-1">
              <span
                ><i class="bi bi-calendar3 me-1"></i>{{ dinhDangSo(goi.thoi_han_ngay) }} ngày</span
              >
              <span>•</span>
              <span :class="goi.so_buoi_pt > 0 ? 'text-success fw-semibold' : ''">
                <i class="bi bi-person-video3 me-1"></i>{{ dinhDangSo(goi.so_buoi_pt) }} buổi PT
              </span>
              <span>•</span>
              <span
                ><i class="bi bi-robot me-1"></i>{{ dinhDangSo(goi.so_luot_chatbot_moi_ngay) }} lượt
                chatbot/ngày</span
              >
            </div>
          </div>

          <div class="package-amount-col">
            <strong class="package-price-bold">{{ dinhDangGia(goi.gia) }}</strong>
            <span
              class="status-pill mt-1"
              :class="{ 'status-locked': goi.trang_thai !== 'HOAT_DONG' }"
            >
              <span
                class="status-dot"
                :class="{ 'dot-locked': goi.trang_thai !== 'HOAT_DONG' }"
              ></span>
              <span>{{ goi.trang_thai === 'HOAT_DONG' ? 'Đang mở bán' : 'Ngừng bán' }}</span>
            </span>
          </div>

          <div class="package-actions-col">
            <RouterLink
              class="btn btn-outline-secondary btn-sm"
              :to="'/admin/goi-tap/' + goi.id + '/sua'"
            >
              <i class="bi bi-pencil-square"></i>
              <span>Sửa</span>
            </RouterLink>

            <button
              class="btn btn-sm"
              :class="goi.trang_thai === 'HOAT_DONG' ? 'btn-danger-soft' : 'btn-soft'"
              :disabled="dangDoi !== null"
              :aria-label="
                (goi.trang_thai === 'HOAT_DONG' ? 'Ngừng bán ' : 'Mở bán ') + goi.ten_goi
              "
              @click="doiTrangThai(goi)"
            >
              <span
                v-if="dangDoi === goi.id"
                class="spinner-border spinner-border-sm me-1"
                role="status"
              ></span>
              <i
                v-else
                :class="goi.trang_thai === 'HOAT_DONG' ? 'bi bi-pause-circle' : 'bi bi-play-circle'"
              ></i>
              <span>
                {{
                  dangDoi === goi.id
                    ? 'Đang lưu…'
                    : goi.trang_thai === 'HOAT_DONG'
                      ? 'Ngừng bán'
                      : 'Mở bán'
                }}
              </span>
            </button>
          </div>
        </li>
      </ul>

      <!-- Giao diện Bảng giá công khai: Dạng Card Pricing SaaS hiện đại -->
      <div v-else class="public-pricing-grid">
        <article
          v-for="goi in danhSach"
          :key="goi.id"
          class="pricing-card-modern shadow-sm"
          :class="{ 'is-featured': goi.so_buoi_pt > 0 }"
        >
          <!-- Huy hiệu đề xuất gói PT -->
          <div v-if="goi.so_buoi_pt > 0" class="featured-ribbon">
            <i class="bi bi-star-fill me-1"></i>PHỔ BIẾN NHẤT
          </div>

          <div class="pricing-card-header">
            <div class="package-type-pill" :class="goi.so_buoi_pt > 0 ? 'pill-pt' : 'pill-bot'">
              <i
                :class="
                  goi.so_buoi_pt > 0 ? 'bi bi-person-check-fill' : 'bi bi-chat-square-text-fill'
                "
              ></i>
              <span>{{ goi.so_buoi_pt > 0 ? 'HUẤN LUYỆN 1:1 & AI' : 'TRỢ LÝ CHATBOT AI' }}</span>
            </div>
            <h2 class="h4 fw-bold mt-2 mb-1">{{ goi.ten_goi }}</h2>
            <p class="text-muted small mb-0">
              {{
                goi.so_buoi_pt > 0
                  ? 'Giáo án kèm cặp tận tay cùng HLV chuyên nghiệp.'
                  : 'Tư vấn giải đáp dinh dưỡng và bài tập 24/7.'
              }}
            </p>
          </div>

          <div class="pricing-card-body">
            <div class="price-display-wrap">
              <span class="price-main">{{ dinhDangGia(goi.gia) }}</span>
              <span class="price-duration text-muted small"
                >/ {{ dinhDangSo(goi.thoi_han_ngay) }} ngày</span
              >
            </div>

            <!-- Quyền lợi chi tiết -->
            <div class="pricing-facts-container mt-3 mb-4">
              <QuyenLoiGoiTap :goi="goi" />
            </div>

            <RouterLink
              class="btn btn-primary w-100 shadow-sm"
              :to="{ name: 'chi-tiet-goi-tap', params: { id: goi.id }, query: queryHienTai }"
            >
              <span>Xem chi tiết quyền lợi</span>
              <i class="bi bi-arrow-right"></i>
            </RouterLink>
          </div>
        </article>
      </div>
    </template>

    <!-- Thanh phân trang -->
    <nav
      v-if="!dangTai && !loiTai && meta.last_page > 1"
      class="package-pagination-bar mt-4 shadow-sm"
      aria-label="Phân trang gói tập"
    >
      <button
        class="btn btn-outline-secondary btn-sm"
        :disabled="meta.current_page <= 1"
        @click="chuyenTrang(meta.current_page - 1)"
      >
        <i class="bi bi-chevron-left me-1"></i>Trang trước
      </button>
      <span class="small fw-semibold text-muted">
        Trang {{ meta.current_page }} / {{ meta.last_page }}
      </span>
      <button
        class="btn btn-outline-secondary btn-sm"
        :disabled="meta.current_page >= meta.last_page"
        @click="chuyenTrang(meta.current_page + 1)"
      >
        Trang sau<i class="bi bi-chevron-right ms-1"></i>
      </button>
    </nav>
  </div>
</template>

<script>
import goiTapService from '../services/goiTapService'
import QuyenLoiGoiTap from './QuyenLoiGoiTap.vue'
import { dinhDangGia, dinhDangSo, docBoLocGoiTap, taoQueryGoiTap } from '../utils/goiTap'
import { layLoiApi } from '../utils/loiApi'

export default {
  name: 'DanhSachGoiTap',
  components: { QuyenLoiGoiTap },
  props: { quanTri: { type: Boolean, default: false } },
  data() {
    return {
      boLoc: docBoLocGoiTap(this.$route.query, this.quanTri),
      danhSach: [],
      meta: { total: 0, current_page: 1, last_page: 1 },
      dangTai: false,
      loiTai: '',
      thongBao: '',
      coLoi: false,
      dangDoi: null,
      lanTai: 0,
      boHuy: null,
      daDong: false,
    }
  },
  computed: {
    coBoLoc() {
      return !!(this.boLoc.tu_khoa || this.boLoc.loai_goi || this.boLoc.trang_thai)
    },
    queryHienTai() {
      return taoQueryGoiTap(
        docBoLocGoiTap(this.$route.query, this.quanTri),
        this.meta.current_page,
        this.quanTri,
      )
    },
    soLuongDangBan() {
      return this.danhSach.filter((g) => g.trang_thai === 'HOAT_DONG').length
    },
    soLuongNgungBan() {
      return this.danhSach.filter((g) => g.trang_thai !== 'HOAT_DONG').length
    },
    soLuongGoiPt() {
      return this.danhSach.filter((g) => Number(g.so_buoi_pt) > 0).length
    },
  },
  watch: {
    '$route.query': {
      handler() {
        this.boLoc = docBoLocGoiTap(this.$route.query, this.quanTri)
        this.taiDanhSach()
      },
    },
  },
  mounted() {
    this.taiDanhSach()
  },
  beforeUnmount() {
    this.daDong = true
    this.lanTai++
    this.boHuy?.abort()
  },
  methods: {
    dinhDangGia,
    dinhDangSo,
    async taiDanhSach() {
      this.boHuy?.abort()
      const boHuy = new AbortController()
      this.boHuy = boHuy
      const lanTai = ++this.lanTai
      this.dangTai = true
      this.loiTai = ''
      try {
        const phanHoi = await goiTapService.taiDanhSach(
          docBoLocGoiTap(this.$route.query, this.quanTri),
          boHuy.signal,
          this.quanTri,
        )
        if (this.daDong || lanTai !== this.lanTai) return
        this.danhSach = phanHoi.data
        this.meta = phanHoi.meta
      } catch (loi) {
        if (this.daDong || lanTai !== this.lanTai || boHuy.signal.aborted) return
        this.loiTai = layLoiApi(loi).thongBao
      } finally {
        if (!this.daDong && lanTai === this.lanTai) this.dangTai = false
      }
    },
    apDungBoLoc() {
      const query = taoQueryGoiTap(this.boLoc, 1, this.quanTri)
      if (JSON.stringify(query) === JSON.stringify(this.$route.query)) this.taiDanhSach()
      else this.$router.push({ query })
    },
    xoaBoLoc() {
      this.boLoc = docBoLocGoiTap({}, this.quanTri)
      this.apDungBoLoc()
    },
    chuyenTrang(page) {
      this.$router.push({
        query: taoQueryGoiTap(docBoLocGoiTap(this.$route.query, this.quanTri), page, this.quanTri),
      })
    },
    async doiTrangThai(goi) {
      if (!this.quanTri || this.dangDoi !== null) return
      this.dangDoi = goi.id
      this.thongBao = ''
      try {
        const phanHoi = await goiTapService.datTrangThai(goi.id, {
          trang_thai: goi.trang_thai === 'HOAT_DONG' ? 'NGUNG_SU_DUNG' : 'HOAT_DONG',
          updated_at: goi.updated_at,
        })
        if (this.daDong) return
        this.thongBao = phanHoi.message
        this.coLoi = false
        await this.taiDanhSach()
      } catch (loi) {
        if (this.daDong) return
        this.thongBao = layLoiApi(loi).thongBao
        this.coLoi = true
        if (loi.response?.status === 409) this.loiTai = this.thongBao
      } finally {
        if (!this.daDong) this.dangDoi = null
      }
    },
  },
}
</script>

<style scoped>
.package-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 28px;
}

.max-w-700 {
  max-width: 720px;
}

/* Thanh bộ lọc */
.package-filters-panel {
  display: flex;
  align-items: flex-end;
  gap: 16px;
  background: var(--mau-the, #141414);
  border: 1px solid var(--mau-vien);
  padding: 20px 24px;
  border-radius: var(--bo-goc-lg);
}

.filter-field-search {
  flex: 1.6;
  min-width: 200px;
}

.filter-field-item {
  flex: 1;
  min-width: 150px;
}

.filter-actions-group {
  display: flex;
  gap: 8px;
}

/* Danh sách quản trị dạng Row */
.admin-package-list {
  list-style: none;
  padding: 0;
  margin: 0;
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  background: var(--mau-the, #141414);
  overflow: hidden;
}

.admin-package-row {
  display: grid;
  grid-template-columns: minmax(0, 1.8fr) 200px auto;
  gap: 24px;
  padding: 20px 24px;
  align-items: center;
  border-bottom: 1px solid var(--mau-vien);
  transition: background-color 0.2s ease;
}

.admin-package-row:last-child {
  border-bottom: none;
}

.admin-package-row:hover {
  background-color: var(--mau-the-hover, #1c1c1c);
}

.package-name-col {
  display: flex;
  flex-direction: column;
  gap: 4px;
  overflow-wrap: anywhere;
}

.package-title-link {
  font-weight: 700;
  font-size: 1.15rem;
  color: var(--mau-chu);
  text-decoration: none;
  transition: color 0.2s;
}

.package-title-link:hover {
  color: var(--mau-chinh);
  text-decoration: underline;
}

.package-amount-col {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
}

.package-price-bold {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--mau-chu);
}

.package-actions-col {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Bảng giá công khai (Pricing Cards) */
.public-pricing-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 28px;
}

.pricing-card-modern {
  background: var(--mau-the, #141414);
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-xl);
  padding: 36px 32px;
  position: relative;
  display: flex;
  flex-direction: column;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.pricing-card-modern:hover {
  transform: translateY(-6px);
  box-shadow:
    0 20px 30px -10px rgba(0, 0, 0, 0.6),
    0 0 20px rgba(244, 91, 32, 0.15);
  border-color: rgba(244, 91, 32, 0.4);
}

.pricing-card-modern.is-featured {
  border: 2px solid var(--mau-chinh);
  box-shadow: 0 10px 25px -5px rgba(244, 91, 32, 0.25);
}

.featured-ribbon {
  position: absolute;
  top: -14px;
  right: 28px;
  background: var(--mau-gradient-chinh);
  color: white;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 4px 14px;
  border-radius: var(--bo-goc-tron);
  box-shadow: 0 4px 10px rgba(244, 91, 32, 0.4);
}

.package-type-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  padding: 4px 10px;
  border-radius: var(--bo-goc-tron);
}

.pill-pt {
  background: rgba(244, 91, 32, 0.15);
  color: #ff8c5a;
}

.pill-bot {
  background: #f5f3ff;
  color: #7c3aed;
}

.price-display-wrap {
  margin: 18px 0 20px;
  display: flex;
  align-items: baseline;
  gap: 8px;
  flex-wrap: wrap;
}

.price-main {
  font-size: 2.3rem;
  font-weight: 800;
  letter-spacing: -0.04em;
  color: var(--mau-chu);
  line-height: 1;
}

.pricing-card-body {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.pricing-facts-container {
  margin-top: auto;
  padding-top: 14px;
}

.empty-icon-ring {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: var(--mau-the-hover, #1e1e1e);
  display: grid;
  place-items: center;
}

.package-pagination-bar {
  background: var(--mau-the, #141414);
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  padding: 14px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

@media (max-width: 992px) {
  .package-filters-panel {
    flex-wrap: wrap;
  }
  .filter-field-search {
    flex-basis: 100%;
  }
  .admin-package-row {
    grid-template-columns: minmax(0, 1fr) 180px;
  }
  .package-actions-col {
    grid-column: 1 / -1;
  }
}

@media (max-width: 768px) {
  .public-pricing-grid {
    grid-template-columns: 1fr;
  }
  .admin-package-row {
    grid-template-columns: 1fr;
    padding: 16px;
    gap: 16px;
  }
}

@media (max-width: 640px) {
  .package-heading {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
  .package-filters-panel {
    flex-direction: column;
    align-items: stretch;
    padding: 16px;
  }
  .pricing-card-modern {
    padding: 24px;
  }
}
</style>
