<template>
  <DanhMucLayout>
    <!-- Tiêu đề trang Thư viện bài tập với giao diện hiện đại -->
    <header class="library-heading animate__animated animate__fadeIn">
      <div>
        <div class="eyebrow mb-2">
          <i class="bi bi-stars"></i>
          <span>THƯ VIỆN VẬN ĐỘNG & BÀI TẬP CHUẨN FORM</span>
        </div>
        <h1 class="h2 fw-bold mb-2">Danh mục bài tập thể hình</h1>
        <p class="library-intro text-muted">
          Tra cứu hơn 1,300+ động tác theo nhóm cơ và dụng cụ. Xem kỹ thuật từng bước cùng hình ảnh
          minh họa và chuyển động trực quan.
        </p>
      </div>

      <div class="d-none d-sm-flex align-items-center">
        <span class="status-pill">
          <span class="status-dot"></span>
          <span>Truy cập mở miễn phí</span>
        </span>
      </div>
    </header>

    <!-- Thanh tìm kiếm chính phong cách SaaS -->
    <form class="exercise-search-box shadow-sm mb-4" @submit.prevent="apDungBoLoc">
      <div class="search-input-wrap">
        <i class="bi bi-search search-icon" aria-hidden="true"></i>
        <label for="tu-khoa-bai-tap" class="visually-hidden">Tên hoặc mã bài tập</label>
        <input
          id="tu-khoa-bai-tap"
          v-model="boLoc.tu_khoa"
          type="search"
          maxlength="100"
          class="search-native-input"
          placeholder="Tìm kiếm bài tập theo tên tiếng Việt hoặc tên gốc tiếng Anh…"
          autocomplete="off"
        />
        <button
          v-if="boLoc.tu_khoa"
          type="button"
          class="btn-clear-search"
          title="Xóa từ khóa"
          @click="boLoc.tu_khoa = ''"
        >
          <i class="bi bi-x-circle-fill"></i>
        </button>
      </div>
      <button class="btn btn-primary search-submit-btn" type="submit" :disabled="dangTai">
        <i class="bi bi-search"></i>
        <span class="d-none d-sm-inline">Tìm kiếm</span>
      </button>
    </form>

    <!-- Thân trang: Bộ lọc bên trái + Lưới bài tập bên phải -->
    <div class="library-body">
      <!-- Cột bộ lọc bên trái -->
      <aside class="library-filters" aria-label="Bộ lọc bài tập">
        <div class="filter-card">
          <div class="filters-heading">
            <h2 class="h6 fw-bold mb-0 d-flex align-items-center gap-2">
              <i class="bi bi-funnel-fill text-success"></i>
              <span>Bộ lọc tìm kiếm</span>
            </h2>
            <button
              v-if="coBoLocDangChon"
              type="button"
              class="clear-filters-btn"
              @click="xoaBoLoc"
            >
              <i class="bi bi-arrow-counterclockwise"></i>
              <span>Đặt lại</span>
            </button>
          </div>

          <form @submit.prevent="apDungBoLoc">
            <!-- Nhóm cơ -->
            <div class="filter-field mb-3">
              <label for="nhom-co" class="form-label d-flex align-items-center gap-1">
                <i class="bi bi-person-arms-up text-primary"></i>
                <span>Nhóm cơ tác động</span>
              </label>
              <select
                id="nhom-co"
                v-model="boLoc.nhom_co_id"
                class="form-select form-select-sm"
                :disabled="dangTaiBoLoc"
              >
                <option value="">Tất cả nhóm cơ</option>
                <option v-for="nhom in danhSachNhomCo" :key="nhom.id" :value="String(nhom.id)">
                  {{ nhom.ten_nhom_co }} ({{ nhom.so_bai_tap }})
                </option>
              </select>
            </div>

            <!-- Dụng cụ -->
            <div class="filter-field mb-3">
              <label for="dung-cu" class="form-label d-flex align-items-center gap-1">
                <i class="bi bi-tools text-amber"></i>
                <span>Dụng cụ tập luyện</span>
              </label>
              <select
                id="dung-cu"
                v-model="boLoc.dung_cu_nguon"
                class="form-select form-select-sm"
                :disabled="dangTaiBoLoc"
              >
                <option value="">Tất cả dụng cụ</option>
                <option
                  v-for="dungCu in danhSachDungCu"
                  :key="dungCu.dung_cu_nguon"
                  :value="dungCu.dung_cu_nguon"
                >
                  {{ dungCu.dung_cu }}
                </option>
              </select>
            </div>

            <!-- Nút áp dụng bộ lọc -->
            <button
              class="btn btn-primary w-100 btn-sm shadow-sm"
              type="submit"
              :disabled="dangTai"
            >
              <i class="bi bi-check2-circle"></i>
              <span>Áp dụng bộ lọc</span>
            </button>
          </form>

          <div v-if="dangTaiBoLoc" class="filter-note mt-3 text-muted small" role="status">
            <span class="spinner-border spinner-border-sm me-1"></span>
            Đang tải dữ liệu bộ lọc…
          </div>

          <div v-if="loiBoLoc" class="filter-error mt-3 alert alert-warning p-2 small" role="alert">
            <p class="mb-1">{{ loiBoLoc }}</p>
            <button type="button" class="btn btn-outline-secondary btn-sm w-100" @click="taiBoLoc">
              Tải lại bộ lọc
            </button>
          </div>

          <div class="filter-tip-box mt-3 p-3 bg-light rounded-3 border">
            <div class="small fw-bold text-dark mb-1 d-flex align-items-center gap-1">
              <i class="bi bi-lightbulb-fill text-warning"></i>
              <span>Mẹo tra cứu:</span>
            </div>
            <p class="small text-muted mb-0">
              Có thể tìm bằng tên tiếng Anh như <em>bench press</em>, <em>squat</em> hoặc tên cơ thể
              tiếng Việt.
            </p>
          </div>
        </div>
      </aside>

      <!-- Cột kết quả bên phải -->
      <section
        ref="ketQua"
        class="library-results"
        aria-label="Kết quả bài tập"
        :aria-busy="dangTai"
      >
        <!-- Thanh trạng thái kết quả & Tags đang lọc -->
        <div class="results-header mb-3">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h2 class="h5 fw-bold mb-0 d-flex align-items-center gap-2">
              <i class="bi bi-grid-fill text-success"></i>
              <span>Danh sách bài tập</span>
            </h2>
            <span
              class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2"
              role="status"
              aria-live="polite"
            >
              {{ dangTai ? 'Đang tìm kiếm bài tập…' : `${dinhDangSo(phanTrang.total)} bài tập` }}
            </span>
          </div>

          <!-- Huy hiệu các điều kiện lọc đang chọn -->
          <div
            v-if="coBoLocDangChon"
            class="active-filter-chips mt-2 d-flex flex-wrap gap-2 align-items-center"
          >
            <span class="small text-muted me-1">Đang lọc theo:</span>
            <span v-if="boLoc.tu_khoa" class="filter-chip">
              <span>Từ khóa: "{{ boLoc.tu_khoa }}"</span>
              <button type="button" class="chip-remove-btn" @click="xoaTuKhoa">×</button>
            </span>
            <span v-if="tenNhomCoHienTai" class="filter-chip">
              <span>Cơ: {{ tenNhomCoHienTai }}</span>
              <button type="button" class="chip-remove-btn" @click="xoaNhomCo">×</button>
            </span>
            <span v-if="tenDungCuHienTai" class="filter-chip">
              <span>Dụng cụ: {{ tenDungCuHienTai }}</span>
              <button type="button" class="chip-remove-btn" @click="xoaDungCu">×</button>
            </span>
          </div>
        </div>

        <!-- Trạng thái Đang tải -->
        <div
          v-if="dangTai"
          class="catalog-state-box animate__animated animate__fadeIn"
          role="status"
        >
          <div
            class="spinner-border text-success mb-3"
            style="width: 3rem; height: 3rem"
            role="status"
          ></div>
          <h3 class="h5 fw-bold mb-1">Đang tải danh sách bài tập…</h3>
          <p class="text-muted small mb-0">Hệ thống đang đồng bộ kho dữ liệu bài tập.</p>
        </div>

        <!-- Trạng thái Báo lỗi -->
        <div
          v-else-if="thongBao"
          class="catalog-state-box alert alert-danger border-danger-subtle"
          role="alert"
        >
          <i class="bi bi-exclamation-triangle-fill fs-1 text-danger mb-2"></i>
          <h3 class="h5 fw-bold text-danger mb-2">Chưa tải được danh sách bài tập</h3>
          <p class="mb-3">{{ thongBao }}</p>
          <p v-for="(loi, truong) in loiTruong" :key="truong" class="small text-muted">
            {{ loi.join(' ') }}
          </p>
          <button class="btn btn-outline-danger btn-sm" @click="taiDanhSach">
            <i class="bi bi-arrow-clockwise me-1"></i>Thử lại
          </button>
        </div>

        <!-- Trạng thái Không có kết quả -->
        <div v-else-if="!danhSach.length" class="catalog-state-box">
          <div class="empty-icon-ring mb-3">
            <i class="bi bi-search fs-2 text-muted"></i>
          </div>
          <h3 class="h5 fw-bold mb-2">Không tìm thấy bài tập nào</h3>
          <p class="text-muted small mb-3">
            Không có động tác nào khớp với tiêu chí tìm kiếm hiện tại. Hãy thử chọn nhóm cơ khác
            hoặc đặt lại bộ lọc.
          </p>
          <button class="btn btn-outline-secondary btn-sm" @click="xoaBoLoc">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Xem tất cả bài tập
          </button>
        </div>

        <!-- Lưới thẻ bài tập hiện đại -->
        <div v-else class="exercise-grid">
          <RouterLink
            v-for="bai in danhSach"
            :key="bai.id"
            class="exercise-modern-card"
            :to="{ name: 'chi-tiet-bai-tap', params: { id: bai.id }, query: $route.query }"
          >
            <!-- Ảnh bài tập có bo góc và tỷ lệ 4:3 chuẩn -->
            <div class="card-image-container">
              <AnhBaiTap
                :src="urlMedia(bai.anh_url)"
                :alt="bai.ten_tieng_viet || bai.ten_bai_tap"
              />
              <span class="badge-card-group">
                <i class="bi bi-fire me-1"></i>{{ bai.nhom_co.ten_nhom_co }}
              </span>
            </div>

            <!-- Thân thẻ thông tin bài tập -->
            <div class="exercise-card-body">
              <h3 class="exercise-title text-truncate-2">
                {{ bai.ten_tieng_viet || bai.ten_bai_tap }}
              </h3>
              <p
                v-if="bai.ten_tieng_viet && bai.ten_tieng_viet !== bai.ten_bai_tap"
                class="exercise-subtitle text-truncate"
              >
                {{ bai.ten_bai_tap }}
              </p>

              <div class="exercise-equipment-tag">
                <i class="bi bi-tools text-muted"></i>
                <span class="text-truncate">{{ bai.dung_cu || 'Không cần dụng cụ' }}</span>
              </div>

              <div class="exercise-card-footer">
                <span class="open-link-text">
                  <span>Chi tiết động tác</span>
                  <i class="bi bi-arrow-right open-arrow"></i>
                </span>
              </div>
            </div>
          </RouterLink>
        </div>

        <!-- Phân trang hiện đại -->
        <nav
          v-if="!dangTai && !thongBao && phanTrang.last_page > 1"
          class="exercise-pagination-bar mt-4 shadow-sm"
          aria-label="Phân trang bài tập"
        >
          <button
            class="btn btn-outline-secondary btn-sm"
            :disabled="phanTrang.current_page <= 1"
            @click="chuyenTrang(phanTrang.current_page - 1)"
          >
            <i class="bi bi-chevron-left me-1"></i>Trang trước
          </button>
          <span class="small fw-semibold text-muted" aria-live="polite">
            Trang {{ phanTrang.current_page }} / {{ phanTrang.last_page }}
          </span>
          <button
            class="btn btn-outline-secondary btn-sm"
            :disabled="phanTrang.current_page >= phanTrang.last_page"
            @click="chuyenTrang(phanTrang.current_page + 1)"
          >
            Trang sau<i class="bi bi-chevron-right ms-1"></i>
          </button>
        </nav>
      </section>
    </div>
  </DanhMucLayout>
</template>

<script>
import DanhMucLayout from '../../layouts/DanhMucLayout.vue'
import AnhBaiTap from '../../components/AnhBaiTap.vue'
import baiTapService from '../../services/baiTapService'
import { docBoLoc, taoQueryBoLoc } from '../../utils/baiTap'
import { layLoiApi } from '../../utils/loiApi'

export default {
  name: 'DanhSachBaiTap',
  components: { DanhMucLayout, AnhBaiTap },
  data() {
    return {
      boLoc: docBoLoc(),
      danhSach: [],
      danhSachNhomCo: [],
      danhSachDungCu: [],
      phanTrang: { current_page: 1, last_page: 1, total: 0 },
      dangTai: false,
      dangTaiBoLoc: false,
      thongBao: '',
      loiTruong: {},
      loiBoLoc: '',
      soLanTai: 0,
      huyYeuCau: null,
      daDong: false,
      canCuonKetQua: false,
    }
  },
  computed: {
    coBoLocDangChon() {
      return Boolean(this.boLoc.tu_khoa || this.boLoc.nhom_co_id || this.boLoc.dung_cu_nguon)
    },
    tenNhomCoHienTai() {
      if (!this.boLoc.nhom_co_id) return ''
      const nhom = this.danhSachNhomCo.find((n) => String(n.id) === String(this.boLoc.nhom_co_id))
      return nhom ? nhom.ten_nhom_co : ''
    },
    tenDungCuHienTai() {
      if (!this.boLoc.dung_cu_nguon) return ''
      const dc = this.danhSachDungCu.find((d) => d.dung_cu_nguon === this.boLoc.dung_cu_nguon)
      return dc ? dc.dung_cu : this.boLoc.dung_cu_nguon
    },
  },
  watch: {
    '$route.query': {
      immediate: true,
      handler(query) {
        this.boLoc = docBoLoc(query)
        this.taiDanhSach()
      },
    },
  },
  created() {
    this.taiBoLoc()
  },
  beforeUnmount() {
    this.daDong = true
    this.soLanTai++
    this.huyYeuCau?.abort()
  },
  methods: {
    urlMedia: baiTapService.urlMedia,
    dinhDangSo(so) {
      return new Intl.NumberFormat('vi-VN').format(so)
    },
    async taiBoLoc() {
      if (this.dangTaiBoLoc) return
      this.dangTaiBoLoc = true
      this.loiBoLoc = ''
      try {
        const ketQua = await baiTapService.taiBoLoc()
        if (this.daDong) return
        this.danhSachNhomCo = ketQua.data.nhom_co
        this.danhSachDungCu = ketQua.data.dung_cu
      } catch (loi) {
        if (!this.daDong) this.loiBoLoc = layLoiApi(loi).thongBao
      } finally {
        if (!this.daDong) this.dangTaiBoLoc = false
      }
    },
    async taiDanhSach() {
      const lanTai = ++this.soLanTai
      this.huyYeuCau?.abort()
      this.huyYeuCau = new AbortController()
      this.dangTai = true
      this.thongBao = ''
      this.loiTruong = {}
      try {
        const ketQua = await baiTapService.taiDanhSach(
          docBoLoc(this.$route.query),
          this.huyYeuCau.signal,
        )
        if (lanTai !== this.soLanTai) return
        this.danhSach = ketQua.data
        this.phanTrang = ketQua.meta
      } catch (loi) {
        if (lanTai !== this.soLanTai) return
        const ketQua = layLoiApi(loi)
        this.thongBao = ketQua.thongBao
        this.loiTruong = ketQua.loiTruong
      } finally {
        if (lanTai === this.soLanTai) {
          this.dangTai = false
          if (this.canCuonKetQua) await this.cuonDenKetQua()
        }
      }
    },
    async apDungBoLoc() {
      this.canCuonKetQua = true
      await this.$router.push({ name: 'bai-tap', query: taoQueryBoLoc(this.boLoc) })
      await this.cuonDenKetQua()
    },
    async xoaBoLoc() {
      this.boLoc = docBoLoc()
      await this.$router.push({ name: 'bai-tap' })
    },
    async xoaTuKhoa() {
      this.boLoc.tu_khoa = ''
      await this.apDungBoLoc()
    },
    async xoaNhomCo() {
      this.boLoc.nhom_co_id = ''
      await this.apDungBoLoc()
    },
    async xoaDungCu() {
      this.boLoc.dung_cu_nguon = ''
      await this.apDungBoLoc()
    },
    async chuyenTrang(page) {
      this.canCuonKetQua = true
      await this.$router.push({
        name: 'bai-tap',
        query: taoQueryBoLoc(docBoLoc(this.$route.query), page),
      })
      await this.cuonDenKetQua()
    },
    async cuonDenKetQua() {
      if (this.dangTai || !this.canCuonKetQua) return
      await this.$nextTick()
      this.canCuonKetQua = false
      this.$refs.ketQua?.scrollIntoView({ block: 'start', behavior: 'auto' })
    },
  },
}
</script>

<style scoped>
.library-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 24px;
  margin-bottom: 28px;
}

.library-intro {
  max-width: 680px;
  margin: 0;
  line-height: 1.6;
}

/* Thanh tìm kiếm lớn */
.exercise-search-box {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 6px 6px 6px 18px;
  background: white;
  border: 1.5px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  transition: all 0.25s ease;
}

.exercise-search-box:focus-within {
  border-color: var(--mau-chinh);
  box-shadow: 0 0 0 3.5px rgba(16, 185, 129, 0.18);
}

.search-input-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  min-width: 0;
}

.search-icon {
  color: var(--mau-chinh);
  font-size: 1.15rem;
}

.search-native-input {
  width: 100%;
  border: none;
  background: transparent;
  outline: none;
  font-size: 0.95rem;
  color: var(--mau-chu);
  padding: 10px 0;
}

.btn-clear-search {
  border: none;
  background: transparent;
  color: #94a3b8;
  font-size: 1.1rem;
  padding: 4px;
  cursor: pointer;
  transition: color 0.2s;
}

.btn-clear-search:hover {
  color: #475569;
}

.search-submit-btn {
  padding: 0.55rem 1.4rem;
  border-radius: var(--bo-goc-md);
  font-size: 0.9rem;
}

/* Thân trang: Grid 2 cột */
.library-body {
  display: grid;
  grid-template-columns: 260px minmax(0, 1fr);
  gap: 32px;
}

.filter-card {
  background: white;
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  padding: 22px;
  box-shadow: var(--bong-nhe);
  position: sticky;
  top: 90px;
}

.filters-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--mau-vien);
  padding-bottom: 14px;
  margin-bottom: 18px;
}

.clear-filters-btn {
  border: none;
  background: transparent;
  color: var(--mau-chinh);
  font-size: 0.8rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  padding: 4px;
}

.clear-filters-btn:hover {
  text-decoration: underline;
}

.text-amber {
  color: #d97706;
}

/* Chips bộ lọc đang chọn */
.filter-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  padding: 3px 10px;
  border-radius: var(--bo-goc-tron);
  font-size: 0.78rem;
  font-weight: 600;
  color: #334155;
}

.chip-remove-btn {
  border: none;
  background: #e2e8f0;
  color: #475569;
  border-radius: 50%;
  width: 16px;
  height: 16px;
  display: grid;
  place-items: center;
  font-size: 0.8rem;
  line-height: 1;
  cursor: pointer;
}

.chip-remove-btn:hover {
  background: #cbd5e1;
}

/* Khung trạng thái (Loading, Error, Empty) */
.catalog-state-box {
  min-height: 360px;
  background: white;
  border: 1px dashed var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 24px;
  text-align: center;
}

.empty-icon-ring {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #f1f5f9;
  display: grid;
  place-items: center;
}

/* Lưới thẻ bài tập */
.exercise-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 22px;
}

.exercise-modern-card {
  background: white;
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  overflow: hidden;
  text-decoration: none;
  color: var(--mau-chu);
  display: flex;
  flex-direction: column;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: var(--bong-nhe);
}

.exercise-modern-card:hover {
  transform: translateY(-5px);
  border-color: #cbd5e1;
  box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.08);
}

.card-image-container {
  position: relative;
  background: #f8fafc;
  border-bottom: 1px solid var(--mau-vien);
}

.badge-card-group {
  position: absolute;
  top: 12px;
  left: 12px;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(8px);
  color: var(--mau-chinh-dam);
  font-size: 0.72rem;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: var(--bo-goc-tron);
  border: 1px solid rgba(16, 185, 129, 0.2);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
  z-index: 2;
}

.exercise-card-body {
  padding: 18px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.exercise-title {
  font-size: 1rem;
  font-weight: 700;
  line-height: 1.4;
  margin-bottom: 4px;
  color: var(--mau-chu);
}

.exercise-subtitle {
  font-size: 0.78rem;
  color: var(--mau-phu);
  margin-bottom: 12px;
}

.exercise-equipment-tag {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.8rem;
  color: var(--mau-phu);
  margin-top: auto;
  padding-top: 10px;
  border-top: 1px dashed var(--mau-vien);
}

.exercise-card-footer {
  margin-top: 12px;
}

.open-link-text {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--mau-chinh);
  transition: color 0.2s;
}

.open-arrow {
  transition: transform 0.2s ease;
}

.exercise-modern-card:hover .open-arrow {
  transform: translateX(4px);
}

.text-truncate-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Phân trang */
.exercise-pagination-bar {
  background: white;
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  padding: 14px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

@media (max-width: 1080px) {
  .library-body {
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 24px;
  }
  .exercise-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .library-body {
    grid-template-columns: 1fr;
  }
  .filter-card {
    position: static;
  }
}

@media (max-width: 540px) {
  .exercise-grid {
    grid-template-columns: 1fr;
  }
  .library-heading {
    flex-direction: column;
    align-items: flex-start;
  }
  .exercise-search-box {
    padding: 6px;
  }
}
</style>
