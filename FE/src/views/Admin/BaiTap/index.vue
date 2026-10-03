<template>
  <CaNhanLayout>
    <!-- Header Dashboard Quản trị bài tập -->
    <header class="catalog-heading animate__animated animate__fadeIn">
      <div>
        <div class="eyebrow mb-2">
          <i class="bi bi-shield-check"></i>
          <span>QUẢN TRỊ VIÊN · DANH MỤC THỂ HÌNH</span>
        </div>
        <h1 class="h2 fw-bold mb-1">Quản lý thư viện bài tập</h1>
        <p class="text-muted small mb-0">
          Biên tập nội dung tiếng Việt, cập nhật nhóm cơ và điều chỉnh trạng thái hiển thị động tác
          trong thư viện.
        </p>
      </div>

      <div class="d-flex align-items-center gap-2">
        <RouterLink
          :to="{ name: 'admin-them-bai-tap', query: queryHienTai }"
          class="btn btn-primary shadow-sm"
        >
          <i class="bi bi-plus-circle-fill"></i>
          <span>Thêm bài tập mới</span>
        </RouterLink>
      </div>
    </header>

    <!-- Thẻ thống kê tổng quan danh mục Admin -->
    <div class="stats-grid mb-4">
      <div class="stat-item-card">
        <div class="stat-icon-wrapper stat-icon-emerald">
          <i class="bi bi-collection-play-fill"></i>
        </div>
        <div class="stat-info-content">
          <h3>{{ phanTrang.total || 0 }}</h3>
          <p>Tổng số bài tập</p>
        </div>
      </div>

      <div class="stat-item-card">
        <div class="stat-icon-wrapper stat-icon-blue">
          <i class="bi bi-check-circle-fill"></i>
        </div>
        <div class="stat-info-content">
          <h3>{{ soLuongHoatDong }}</h3>
          <p>Đang bật hiển thị</p>
        </div>
      </div>

      <div class="stat-item-card">
        <div class="stat-icon-wrapper stat-icon-amber">
          <i class="bi bi-pause-circle-fill"></i>
        </div>
        <div class="stat-info-content">
          <h3>{{ soLuongNgung }}</h3>
          <p>Tạm ngừng hiển thị</p>
        </div>
      </div>

      <div class="stat-item-card">
        <div class="stat-icon-wrapper stat-icon-purple">
          <i class="bi bi-diagram-3-fill"></i>
        </div>
        <div class="stat-info-content">
          <h3>{{ nhomCo.length || 0 }}</h3>
          <p>Nhóm cơ khả dụng</p>
        </div>
      </div>
    </div>

    <!-- Thông báo kết quả tác vụ -->
    <div
      v-if="$route.query.da_them"
      class="alert alert-success d-flex align-items-center gap-2 p-3 mb-4 rounded-3 border-success-subtle animate__animated animate__fadeIn"
      role="status"
    >
      <i class="bi bi-check-circle-fill fs-5 text-success"></i>
      <span class="small fw-semibold">Đã thêm bài tập mới thành công vào danh mục hệ thống.</span>
    </div>

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

    <!-- Khung quản lý: Bộ lọc + Danh sách bài tập -->
    <section class="catalog-panel shadow-sm" aria-label="Danh sách quản lý bài tập">
      <!-- Bộ lọc quản trị -->
      <form class="catalog-filters" @submit.prevent="apDungBoLoc">
        <div class="search-field">
          <label for="tu-khoa-admin" class="form-label d-flex align-items-center gap-1">
            <i class="bi bi-search text-success"></i>
            <span>Tìm kiếm bài tập</span>
          </label>
          <div class="input-group-modern">
            <span class="input-icon-prefix"><i class="bi bi-search"></i></span>
            <input
              id="tu-khoa-admin"
              v-model="boLoc.tu_khoa"
              class="form-control form-control-sm has-prefix"
              maxlength="100"
              placeholder="Tên tiếng Việt, tên gốc tiếng Anh hoặc mã..."
            />
          </div>
        </div>

        <div>
          <label for="nhom-co-admin" class="form-label d-flex align-items-center gap-1">
            <i class="bi bi-person-arms-up text-primary"></i>
            <span>Nhóm cơ</span>
          </label>
          <select id="nhom-co-admin" v-model="boLoc.nhom_co_id" class="form-select form-select-sm">
            <option value="">Tất cả nhóm cơ</option>
            <option v-for="nhom in nhomCo" :key="nhom.id" :value="String(nhom.id)">
              {{ nhom.ten_nhom_co }}{{ nhom.trang_thai !== 'HOAT_DONG' ? ' · Ngừng dùng' : '' }}
            </option>
          </select>
        </div>

        <div>
          <label for="trang-thai-admin" class="form-label d-flex align-items-center gap-1">
            <i class="bi bi-toggle-on text-amber"></i>
            <span>Trạng thái</span>
          </label>
          <select
            id="trang-thai-admin"
            v-model="boLoc.trang_thai"
            class="form-select form-select-sm"
          >
            <option value="">Tất cả trạng thái</option>
            <option value="HOAT_DONG">Đang hoạt động</option>
            <option value="NGUNG_SU_DUNG">Ngừng hiển thị</option>
          </select>
        </div>

        <div class="d-flex gap-2">
          <button
            class="btn btn-primary btn-sm flex-grow-1"
            :disabled="dangDoi !== null"
            type="submit"
          >
            <i class="bi bi-funnel-fill"></i>
            <span>Tìm kiếm</span>
          </button>
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            :disabled="dangDoi !== null"
            title="Đặt lại bộ lọc"
            @click="xoaBoLoc"
          >
            <i class="bi bi-arrow-counterclockwise"></i>
            <span>Đặt lại</span>
          </button>
        </div>
      </form>

      <!-- Báo lỗi bộ lọc nhóm cơ nếu có -->
      <div
        v-if="loiBoLoc"
        class="alert alert-warning mx-3 mt-3 d-flex align-items-center justify-content-between p-2 small"
        role="alert"
      >
        <span
          ><i class="bi bi-exclamation-triangle me-1"></i>Chưa tải được danh mục nhóm cơ từ máy
          chủ.</span
        >
        <button class="btn btn-outline-secondary btn-sm py-0" @click="taiBoLoc">Thử lại</button>
      </div>

      <!-- Tiêu đề kết quả & Trạng thái -->
      <div class="result-heading bg-light border-bottom" aria-live="polite">
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-secondary-subtle text-body border">
            {{ dangTai ? 'Đang cập nhật…' : `${phanTrang.total} bài tập` }}
          </span>
          <span class="text-muted small">Bao gồm cả bài tập đang bật và tạm ngừng hiển thị</span>
        </div>
      </div>

      <!-- Trạng thái Đang tải -->
      <div v-if="dangTai" class="catalog-state p-5 text-center" role="status">
        <div class="spinner-border text-success mb-3" role="status"></div>
        <p class="text-muted mb-0 small">Đang tải danh sách bài tập từ máy chủ…</p>
      </div>

      <!-- Trạng thái Báo lỗi -->
      <div v-else-if="loiTai" class="catalog-state p-5 text-center text-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-1 text-danger mb-2"></i>
        <p class="mb-3">{{ loiTai }}</p>
        <button class="btn btn-outline-danger btn-sm" @click="taiDanhSach">
          <i class="bi bi-arrow-clockwise me-1"></i>Tải lại danh sách
        </button>
      </div>

      <!-- Trạng thái Không tìm thấy kết quả -->
      <div v-else-if="!danhSach.length" class="catalog-state p-5 text-center">
        <div class="empty-icon-ring mx-auto mb-3">
          <i class="bi bi-search fs-2 text-muted"></i>
        </div>
        <h2 class="h5 fw-bold mb-1">Không tìm thấy bài tập phù hợp</h2>
        <p class="text-muted small mb-3">
          Thử thay đổi từ khóa hoặc đặt lại tiêu chí lọc để xem toàn bộ danh mục.
        </p>
        <button class="btn btn-outline-secondary btn-sm" @click="xoaBoLoc">
          <i class="bi bi-arrow-counterclockwise me-1"></i>Đặt lại bộ lọc
        </button>
      </div>

      <!-- Danh sách bài tập dạng hàng card hiện đại -->
      <ul v-else class="exercise-rows">
        <li v-for="bai in danhSach" :key="bai.id" class="exercise-row">
          <!-- Cột 1: Thumbnail ảnh minh họa -->
          <div class="exercise-thumbnail">
            <AnhBaiTap :src="urlMedia(bai.anh_url)" :alt="bai.ten_tieng_viet || bai.ten_bai_tap" />
          </div>

          <!-- Cột 2: Nội dung mô tả bài tập -->
          <div class="exercise-copy">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
              <span class="badge-role badge-role-pt">
                <i class="bi bi-fire"></i>
                <span>{{ bai.nhom_co.ten_nhom_co }}</span>
              </span>
              <span class="badge bg-light text-muted border font-monospace"
                >#{{ bai.ma_nguon || bai.id }}</span
              >
            </div>

            <RouterLink
              :to="{ name: 'admin-sua-bai-tap', params: { id: bai.id }, query: queryHienTai }"
              class="exercise-title"
            >
              {{ bai.ten_tieng_viet || bai.ten_bai_tap }}
            </RouterLink>

            <span
              v-if="bai.ten_tieng_viet && bai.ten_tieng_viet !== bai.ten_bai_tap"
              class="small text-muted"
              lang="en"
            >
              <i class="bi bi-globe me-1"></i>{{ bai.ten_bai_tap }}
            </span>

            <span class="small text-muted mt-1 d-flex align-items-center gap-1">
              <i class="bi bi-tools"></i>
              <span>{{ bai.dung_cu || 'Không cần dụng cụ / Thể trọng' }}</span>
            </span>
          </div>

          <!-- Cột 3: Trạng thái hiển thị -->
          <div class="exercise-status">
            <span class="status-pill" :class="{ 'status-locked': bai.trang_thai !== 'HOAT_DONG' }">
              <span
                class="status-dot"
                :class="{ 'dot-locked': bai.trang_thai !== 'HOAT_DONG' }"
              ></span>
              <span>{{
                bai.trang_thai === 'HOAT_DONG' ? 'Đang hoạt động' : 'Ngừng hiển thị'
              }}</span>
            </span>

            <small
              v-if="bai.nhom_co_trang_thai !== 'HOAT_DONG'"
              class="badge bg-warning-subtle text-warning-emphasis border mt-1"
            >
              <i class="bi bi-exclamation-triangle me-1"></i>Nhóm cơ ngừng dùng
            </small>
          </div>

          <!-- Cột 4: Nút tác vụ -->
          <div class="exercise-actions">
            <RouterLink
              class="btn btn-outline-secondary btn-sm"
              :to="{ name: 'admin-sua-bai-tap', params: { id: bai.id }, query: queryHienTai }"
              :aria-label="'Sửa ' + (bai.ten_tieng_viet || bai.ten_bai_tap)"
            >
              <i class="bi bi-pencil-square"></i>
              <span>Sửa</span>
            </RouterLink>

            <button
              class="btn btn-sm"
              :class="bai.trang_thai === 'HOAT_DONG' ? 'btn-danger-soft' : 'btn-soft'"
              :disabled="dangDoi !== null"
              :aria-label="
                (bai.trang_thai === 'HOAT_DONG' ? 'Ngừng hiển thị ' : 'Bật hiển thị ') +
                (bai.ten_tieng_viet || bai.ten_bai_tap)
              "
              @click="doiTrangThai(bai)"
            >
              <span
                v-if="dangDoi === bai.id"
                class="spinner-border spinner-border-sm me-1"
                role="status"
              ></span>
              <i
                v-else
                :class="bai.trang_thai === 'HOAT_DONG' ? 'bi bi-pause-circle' : 'bi bi-play-circle'"
              ></i>
              <span>
                {{
                  dangDoi === bai.id
                    ? 'Đang lưu…'
                    : bai.trang_thai === 'HOAT_DONG'
                      ? 'Ngừng hiển thị'
                      : 'Bật hiển thị'
                }}
              </span>
            </button>
          </div>
        </li>
      </ul>

      <!-- Thanh phân trang quản trị -->
      <nav
        v-if="phanTrang.last_page > 1 && !loiTai"
        class="catalog-pagination"
        aria-label="Phân trang bài tập quản trị"
      >
        <button
          class="btn btn-outline-secondary btn-sm"
          :disabled="dangTai || dangDoi !== null || phanTrang.current_page <= 1"
          @click="chuyenTrang(phanTrang.current_page - 1)"
        >
          <i class="bi bi-chevron-left me-1"></i>Trang trước
        </button>
        <span class="small fw-semibold text-muted">
          Trang {{ phanTrang.current_page }} / {{ phanTrang.last_page }} ({{ phanTrang.total }} mục)
        </span>
        <button
          class="btn btn-outline-secondary btn-sm"
          :disabled="dangTai || dangDoi !== null || phanTrang.current_page >= phanTrang.last_page"
          @click="chuyenTrang(phanTrang.current_page + 1)"
        >
          Trang sau<i class="bi bi-chevron-right ms-1"></i>
        </button>
      </nav>
    </section>

    <!-- Lưu ý chân trang quản trị -->
    <p class="small text-muted mt-3">
      <i class="bi bi-info-circle me-1"></i>Minh họa trong dataset: © Gym visual. Các bài ngừng hiển
      thị vẫn được bảo toàn nguyên vẹn trong hệ thống.
    </p>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../../layouts/CaNhanLayout.vue'
import AnhBaiTap from '../../../components/AnhBaiTap.vue'
import baiTapAdminService from '../../../services/baiTapAdminService'
import baiTapService from '../../../services/baiTapService'
import { docBoLocAdmin, taoQueryAdmin } from '../../../utils/baiTapAdmin'
import { layLoiApi } from '../../../utils/loiApi'

export default {
  name: 'QuanLyBaiTap',
  components: { CaNhanLayout, AnhBaiTap },
  data() {
    return {
      boLoc: docBoLocAdmin(),
      danhSach: [],
      nhomCo: [],
      phanTrang: { total: 0, current_page: 1, last_page: 1 },
      dangTai: false,
      dangDoi: null,
      loiTai: '',
      loiBoLoc: false,
      thongBao: '',
      coLoi: false,
      lanTai: 0,
      huyTai: null,
      daDong: false,
    }
  },
  computed: {
    queryHienTai() {
      return taoQueryAdmin(docBoLocAdmin(this.$route.query), docBoLocAdmin(this.$route.query).page)
    },
    soLuongHoatDong() {
      return this.danhSach.filter((b) => b.trang_thai === 'HOAT_DONG').length
    },
    soLuongNgung() {
      return this.danhSach.filter((b) => b.trang_thai !== 'HOAT_DONG').length
    },
  },
  watch: {
    '$route.query': {
      immediate: true,
      handler(query) {
        this.boLoc = docBoLocAdmin(query)
        this.taiDanhSach()
      },
    },
  },
  created() {
    this.taiBoLoc()
  },
  beforeUnmount() {
    this.daDong = true
    this.lanTai++
    this.huyTai?.abort()
  },
  methods: {
    urlMedia: baiTapService.urlMedia,
    async taiBoLoc() {
      this.loiBoLoc = false
      try {
        const phanHoi = await baiTapAdminService.taiBoLoc()
        if (!this.daDong) this.nhomCo = phanHoi.data.nhom_co
      } catch {
        if (!this.daDong) this.loiBoLoc = true
      }
    },
    async taiDanhSach() {
      const lanTai = ++this.lanTai
      this.huyTai?.abort()
      this.huyTai = new AbortController()
      this.dangTai = true
      this.loiTai = ''
      try {
        const phanHoi = await baiTapAdminService.taiDanhSach(
          { ...docBoLocAdmin(this.$route.query), per_page: 12 },
          this.huyTai.signal,
        )
        if (this.daDong || lanTai !== this.lanTai) return
        this.danhSach = phanHoi.data
        this.phanTrang = phanHoi.meta
      } catch (loi) {
        if (!this.daDong && lanTai === this.lanTai && loi.code !== 'ERR_CANCELED')
          this.loiTai = layLoiApi(loi).thongBao
      } finally {
        if (!this.daDong && lanTai === this.lanTai) this.dangTai = false
      }
    },
    async apDungBoLoc() {
      if (this.dangDoi !== null) return
      await this.$router.push({ name: 'admin-bai-tap', query: taoQueryAdmin(this.boLoc) })
    },
    async xoaBoLoc() {
      this.boLoc = docBoLocAdmin()
      await this.apDungBoLoc()
    },
    async chuyenTrang(page) {
      await this.$router.push({
        name: 'admin-bai-tap',
        query: taoQueryAdmin(docBoLocAdmin(this.$route.query), page),
      })
    },
    async doiTrangThai(bai) {
      if (this.dangDoi !== null) return
      this.dangDoi = bai.id
      this.thongBao = ''
      try {
        const phanHoi = await baiTapAdminService.datTrangThai(bai.id, {
          trang_thai: bai.trang_thai === 'HOAT_DONG' ? 'NGUNG_SU_DUNG' : 'HOAT_DONG',
          updated_at: bai.updated_at,
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
.catalog-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 24px;
}

.catalog-panel {
  background: var(--mau-the, #141414);
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  overflow: hidden;
}

.catalog-filters {
  display: grid;
  grid-template-columns: minmax(200px, 1.6fr) minmax(150px, 1fr) minmax(150px, 1fr) auto;
  gap: 16px;
  padding: 20px 24px;
  align-items: end;
  background: var(--mau-table-header-bg, #181818);
  border-bottom: 1px solid var(--mau-vien);
}

.result-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 14px 24px;
}

.exercise-rows {
  list-style: none;
  margin: 0;
  padding: 0;
}

.exercise-row {
  display: grid;
  grid-template-columns: 88px minmax(0, 1fr) 160px auto;
  align-items: center;
  gap: 20px;
  padding: 18px 24px;
  border-bottom: 1px solid var(--mau-vien);
  transition: background-color 0.2s ease;
}

.exercise-row:hover {
  background-color: var(--mau-the-hover, #1c1c1c);
}

.exercise-thumbnail {
  width: 88px;
  height: 66px;
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-md);
  overflow: hidden;
  background: #101010;
}

.exercise-copy {
  display: flex;
  flex-direction: column;
  gap: 2px;
  overflow-wrap: anywhere;
}

.exercise-title {
  color: var(--mau-chu);
  font-weight: 700;
  text-decoration: none;
  font-size: 1.05rem;
  line-height: 1.35;
  transition: color 0.2s ease;
}

.exercise-title:hover {
  color: var(--mau-chinh);
  text-decoration: underline;
}

.exercise-status {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
}

.exercise-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.empty-icon-ring {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #1e1e1e;
  display: grid;
  place-items: center;
}

.catalog-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 24px;
  gap: 12px;
  background: var(--mau-the, #141414);
  border-top: 1px solid var(--mau-vien);
}

@media (max-width: 1024px) {
  .catalog-filters {
    grid-template-columns: 1fr 1fr;
  }
  .search-field {
    grid-column: 1 / -1;
  }
  .exercise-row {
    grid-template-columns: 80px minmax(0, 1fr) 140px;
  }
  .exercise-actions {
    grid-column: 2 / -1;
  }
}

@media (max-width: 640px) {
  .catalog-heading {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
  .catalog-filters {
    grid-template-columns: 1fr;
    padding: 16px;
  }
  .exercise-row {
    grid-template-columns: 64px minmax(0, 1fr);
    padding: 14px 16px;
    gap: 14px;
  }
  .exercise-thumbnail {
    width: 64px;
    height: 48px;
  }
  .exercise-status,
  .exercise-actions {
    grid-column: 2;
  }
  .exercise-actions {
    flex-wrap: wrap;
  }
  .catalog-pagination {
    padding: 14px 16px;
    flex-wrap: wrap;
  }
}
</style>
