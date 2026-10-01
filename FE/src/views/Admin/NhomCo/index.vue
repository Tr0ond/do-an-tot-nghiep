<template>
  <CaNhanLayout>
    <!-- Header Quản lý nhóm cơ -->
    <header class="nhom-heading">
      <div>
        <div class="nhom-eyebrow">
          <i class="bi bi-shield-check" aria-hidden="true"></i>
          <span>QUẢN TRỊ VIÊN · DANH MỤC THỂ HÌNH</span>
        </div>
        <h1 class="h2 fw-bold mb-1">Quản lý nhóm cơ</h1>
        <p class="text-muted small mb-0">
          Sắp xếp hệ thống bài tập theo các vùng vận động cơ bắp, hỗ trợ PT lên giáo án và phân loại
          khoa học.
        </p>
      </div>
      <RouterLink to="/admin/nhom-co/them" class="btn btn-primary shadow-sm">
        <i class="bi bi-plus-circle-fill" aria-hidden="true"></i>
        <span>Thêm nhóm cơ</span>
      </RouterLink>
    </header>

    <!-- Thẻ thống kê tổng quan -->
    <div class="stats-grid mb-4">
      <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-emerald">
          <i class="bi bi-diagram-3-fill" aria-hidden="true"></i>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ phanTrang.total || 0 }}</div>
          <div class="stat-label">Tổng số nhóm cơ</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-blue">
          <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ soNhomHoatDong }}</div>
          <div class="stat-label">Đang hoạt động (trang này)</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-amber">
          <i class="bi bi-pause-circle-fill" aria-hidden="true"></i>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ soNhomNgung }}</div>
          <div class="stat-label">Ngừng sử dụng (trang này)</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-purple">
          <i class="bi bi-collection-play-fill" aria-hidden="true"></i>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ tongSoBaiTap }}</div>
          <div class="stat-label">Bài tập liên kết (trang này)</div>
        </div>
      </div>
    </div>

    <!-- Thông báo tác vụ -->
    <div
      v-if="thongBao"
      class="alert d-flex align-items-center gap-2 p-3 mb-4 rounded-3"
      :class="coLoi ? 'alert-danger border-danger-subtle' : 'alert-success border-success-subtle'"
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
        <RouterLink v-if="hetPhien" to="/dang-nhap" class="ms-1 fw-bold text-decoration-underline">
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

    <!-- Khung chính: Bộ lọc + Danh sách nhóm cơ -->
    <section class="nhom-panel shadow-sm" aria-label="Danh sách nhóm cơ">
      <!-- Bộ lọc -->
      <form class="nhom-filters" @submit.prevent="apDungBoLoc">
        <div class="search-field">
          <label for="nhom-tu-khoa" class="form-label d-flex align-items-center gap-1">
            <i class="bi bi-search text-emerald" aria-hidden="true"></i>
            <span>Tìm nhóm cơ</span>
          </label>
          <div class="input-group-modern">
            <span class="input-icon-prefix"><i class="bi bi-search" aria-hidden="true"></i></span>
            <input
              id="nhom-tu-khoa"
              v-model.trim="boLoc.tu_khoa"
              class="form-control form-control-sm has-prefix"
              placeholder="Tên tiếng Việt hoặc mã nhóm cơ..."
              maxlength="100"
            />
          </div>
        </div>

        <div>
          <label for="nhom-trang-thai" class="form-label d-flex align-items-center gap-1">
            <i class="bi bi-toggle-on text-emerald" aria-hidden="true"></i>
            <span>Trạng thái</span>
          </label>
          <select
            id="nhom-trang-thai"
            v-model="boLoc.trang_thai"
            class="form-select form-select-sm"
          >
            <option value="">Tất cả trạng thái</option>
            <option value="HOAT_DONG">Đang hoạt động</option>
            <option value="NGUNG_SU_DUNG">Ngừng sử dụng</option>
          </select>
        </div>

        <div class="nhom-filter-actions">
          <button class="btn btn-primary btn-sm flex-grow-1" :disabled="dangDoi" type="submit">
            <i class="bi bi-funnel-fill" aria-hidden="true"></i>
            <span>Tìm kiếm</span>
          </button>
          <button
            class="btn btn-outline-secondary btn-sm"
            :disabled="dangDoi"
            type="button"
            title="Đặt lại bộ lọc"
            @click="xoaBoLoc"
          >
            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
            <span>Đặt lại</span>
          </button>
        </div>
      </form>

      <!-- Thanh kết quả và ghi chú -->
      <div class="nhom-result" aria-live="polite">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="badge bg-secondary-subtle text-dark border">
            {{ dangTai ? 'Đang tải…' : `${phanTrang.total} nhóm cơ` }}
          </span>
          <span class="text-muted small">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Số bài hiển thị chỉ tính bài và
            nhóm đang hoạt động.
          </span>
        </div>
      </div>

      <!-- Trạng thái Đang tải -->
      <div v-if="dangTai" class="nhom-state p-5 text-center" role="status">
        <div class="spinner-border text-success mb-3" role="status"></div>
        <p class="text-muted mb-0 small">Đang tải danh sách nhóm cơ từ máy chủ…</p>
      </div>

      <!-- Trạng thái Lỗi tải -->
      <div v-else-if="loiTai" class="nhom-state p-5 text-center text-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-1 text-danger mb-2" aria-hidden="true"></i>
        <p class="mb-3">{{ loiTai }}</p>
        <div class="d-flex justify-content-center gap-2">
          <RouterLink v-if="hetPhien" to="/dang-nhap" class="btn btn-outline-primary btn-sm">
            Đăng nhập lại
          </RouterLink>
          <button class="btn btn-outline-danger btn-sm" @click="taiDanhSach">
            <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Thử lại
          </button>
        </div>
      </div>

      <!-- Trạng thái Không tìm thấy kết quả -->
      <div v-else-if="!danhSach.length" class="nhom-state p-5 text-center">
        <div class="empty-icon-ring mx-auto mb-3">
          <i class="bi bi-search fs-2 text-muted" aria-hidden="true"></i>
        </div>
        <h2 class="h5 fw-bold mb-1">Không tìm thấy nhóm cơ phù hợp</h2>
        <p class="text-muted small mb-3">
          Thử thay đổi từ khóa hoặc đặt lại tiêu chí lọc để xem toàn bộ danh mục.
        </p>
        <button class="btn btn-outline-secondary btn-sm" @click="xoaBoLoc">
          <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Đặt lại bộ lọc
        </button>
      </div>

      <!-- Bảng danh sách nhóm cơ -->
      <template v-else>
        <div class="nhom-row nhom-labels" aria-hidden="true">
          <span>NHÓM CƠ VẬN ĐỘNG</span>
          <span>BÀI TẬP LIÊN KẾT</span>
          <span>TRẠNG THÁI HIỂN THỊ</span>
          <span class="text-end">THAO TÁC</span>
        </div>

        <article v-for="nhom in danhSach" :key="nhom.id" class="nhom-row">
          <!-- Cột 1: Thông tin nhóm cơ -->
          <div class="nhom-name">
            <div class="nhom-icon" aria-hidden="true">
              <i class="bi bi-diagram-3-fill"></i>
            </div>
            <div class="nhom-info-wrap">
              <h2 class="nhom-title">{{ nhom.ten_nhom_co }}</h2>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="nhom-code font-monospace">#{{ nhom.ma_nhom_co }}</span>
                <span
                  v-if="nhom.ten_nguon && nhom.ten_nguon !== nhom.ma_nhom_co"
                  class="badge bg-light text-muted border small"
                >
                  Gốc: {{ nhom.ten_nguon }}
                </span>
              </div>
            </div>
          </div>

          <!-- Cột 2: Số bài tập liên kết -->
          <div>
            <RouterLink
              :to="{ name: 'admin-bai-tap', query: { nhom_co_id: String(nhom.id) } }"
              class="nhom-count"
              :aria-label="`Xem ${nhom.so_bai_tap} bài thuộc nhóm ${nhom.ten_nhom_co}`"
            >
              <div class="nhom-count-badge">
                <i class="bi bi-collection-play me-1 text-emerald" aria-hidden="true"></i>
                <strong>{{ nhom.so_bai_tap }} bài</strong>
              </div>
              <span class="nhom-count-sub">
                {{ nhom.so_bai_hien_thi }} đang hiển thị
                <i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i>
              </span>
            </RouterLink>
          </div>

          <!-- Cột 3: Trạng thái hoạt động -->
          <div>
            <span
              class="nhom-status"
              :class="nhom.trang_thai === 'HOAT_DONG' ? 'nhom-active' : 'nhom-inactive'"
            >
              <span
                class="status-dot"
                :class="nhom.trang_thai === 'HOAT_DONG' ? 'dot-active' : 'dot-inactive'"
              ></span>
              <span>{{
                nhom.trang_thai === 'HOAT_DONG' ? 'Đang hoạt động' : 'Ngừng sử dụng'
              }}</span>
            </span>
          </div>

          <!-- Cột 4: Thao tác -->
          <div class="nhom-actions">
            <RouterLink
              :to="`/admin/nhom-co/${nhom.id}/sua`"
              class="btn btn-outline-secondary btn-sm"
              :aria-label="`Sửa nhóm ${nhom.ten_nhom_co}`"
            >
              <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Sửa
            </RouterLink>
            <button
              class="btn btn-sm"
              :class="
                nhom.trang_thai === 'HOAT_DONG' ? 'btn-outline-danger' : 'btn-outline-success'
              "
              :disabled="dangDoi"
              :aria-label="`${nhom.trang_thai === 'HOAT_DONG' ? 'Ngừng' : 'Khôi phục'} nhóm ${nhom.ten_nhom_co}`"
              @click="moXacNhan(nhom)"
            >
              <i
                :class="
                  nhom.trang_thai === 'HOAT_DONG' ? 'bi-pause-circle me-1' : 'bi-arrow-repeat me-1'
                "
                aria-hidden="true"
              ></i>
              <span>{{ nhom.trang_thai === 'HOAT_DONG' ? 'Ngừng' : 'Khôi phục' }}</span>
            </button>
          </div>
        </article>

        <!-- Phân trang -->
        <nav class="nhom-pagination" aria-label="Phân trang nhóm cơ">
          <div class="text-muted small">
            Trang <strong class="text-dark">{{ phanTrang.current_page }}</strong> /
            {{ phanTrang.last_page }}
          </div>
          <div class="d-flex gap-2">
            <button
              class="btn btn-outline-secondary btn-sm"
              :disabled="phanTrang.current_page <= 1 || dangDoi"
              @click="chuyenTrang(phanTrang.current_page - 1)"
            >
              <i class="bi bi-chevron-left me-1" aria-hidden="true"></i>Trước
            </button>
            <button
              class="btn btn-outline-secondary btn-sm"
              :disabled="phanTrang.current_page >= phanTrang.last_page || dangDoi"
              @click="chuyenTrang(phanTrang.current_page + 1)"
            >
              Sau<i class="bi bi-chevron-right ms-1" aria-hidden="true"></i>
            </button>
          </div>
        </nav>
      </template>
    </section>

    <!-- Hộp thoại xác nhận thay đổi trạng thái nhóm cơ -->
    <dialog
      ref="xacNhan"
      class="nhom-dialog shadow-lg"
      aria-labelledby="nhom-xac-nhan-title"
      @cancel="huyXacNhan"
      @close="nhomChon = null"
    >
      <template v-if="nhomChon">
        <div class="nhom-dialog-header">
          <div
            class="dialog-icon-wrapper"
            :class="
              nhomChon.trang_thai === 'HOAT_DONG'
                ? 'bg-danger-subtle text-danger'
                : 'bg-success-subtle text-success'
            "
          >
            <i
              :class="
                nhomChon.trang_thai === 'HOAT_DONG'
                  ? 'bi bi-exclamation-triangle-fill'
                  : 'bi bi-check-circle-fill'
              "
              aria-hidden="true"
            ></i>
          </div>
          <div>
            <div class="nhom-dialog-eyebrow">XÁC NHẬN THAY ĐỔI TRẠNG THÁI</div>
            <h2 id="nhom-xac-nhan-title" class="h5 fw-bold mb-0">
              {{ nhomChon.trang_thai === 'HOAT_DONG' ? 'Ngừng sử dụng' : 'Khôi phục' }} nhóm
              {{ nhomChon.ten_nhom_co }}?
            </h2>
          </div>
        </div>

        <div class="nhom-dialog-body">
          <div
            class="alert p-3 mb-3 small"
            :class="
              nhomChon.trang_thai === 'HOAT_DONG'
                ? 'alert-warning border-warning-subtle'
                : 'alert-info border-info-subtle'
            "
          >
            <p v-if="nhomChon.trang_thai === 'HOAT_DONG'" class="mb-0">
              <strong>{{ nhomChon.so_bai_hien_thi }} bài</strong> đang hiển thị sẽ được ẩn khỏi danh
              mục công khai. Giáo án chứa bài thuộc nhóm này sẽ không thể duyệt.
            </p>
            <p v-else class="mb-0">
              <strong>{{ nhomChon.so_bai_hoat_dong }} bài</strong> còn hoạt động sẽ được hiển thị
              lại. Bài đã ngừng riêng vẫn được giữ ở trạng thái ngừng.
            </p>
          </div>

          <p class="text-muted small mb-0">
            <i class="bi bi-shield-check text-success me-1" aria-hidden="true"></i>
            Bài tập, giáo án và lịch sử tập luyện được giữ nguyên vẹn trên hệ thống.
          </p>
        </div>

        <div class="nhom-dialog-actions">
          <button class="btn btn-outline-secondary btn-sm" :disabled="dangDoi" @click="dongXacNhan">
            Hủy bỏ
          </button>
          <button
            class="btn btn-sm"
            :class="nhomChon.trang_thai === 'HOAT_DONG' ? 'btn-danger' : 'btn-primary'"
            :disabled="dangDoi"
            @click="doiTrangThai"
          >
            <span v-if="dangDoi" class="spinner-border spinner-border-sm me-1" role="status"></span>
            {{ dangDoi ? 'Đang cập nhật…' : 'Xác nhận thay đổi' }}
          </button>
        </div>
      </template>
    </dialog>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../../layouts/CaNhanLayout.vue'
import nhomCoService from '../../../services/nhomCoService'
import { layLoiApi } from '../../../utils/loiApi'

function docBoLoc(query = {}) {
  return {
    tu_khoa: typeof query.tu_khoa === 'string' ? query.tu_khoa : '',
    trang_thai: typeof query.trang_thai === 'string' ? query.trang_thai : '',
  }
}

export default {
  name: 'AdminNhomCo',
  components: { CaNhanLayout },
  data() {
    return {
      boLoc: docBoLoc(),
      danhSach: [],
      phanTrang: { total: 0, current_page: 1, last_page: 1 },
      dangTai: false,
      dangDoi: false,
      loiTai: '',
      thongBao: '',
      coLoi: false,
      hetPhien: false,
      nhomChon: null,
      lanTai: 0,
      huyTai: null,
      daDong: false,
    }
  },
  computed: {
    soNhomHoatDong() {
      return this.danhSach.filter((n) => n.trang_thai === 'HOAT_DONG').length
    },
    soNhomNgung() {
      return this.danhSach.filter((n) => n.trang_thai === 'NGUNG_SU_DUNG').length
    },
    tongSoBaiTap() {
      return this.danhSach.reduce((tong, n) => tong + (Number(n.so_bai_tap) || 0), 0)
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
  beforeUnmount() {
    this.daDong = true
    this.lanTai++
    this.huyTai?.abort()
    this.$refs.xacNhan?.close()
  },
  methods: {
    async taiDanhSach() {
      const lanTai = ++this.lanTai
      this.huyTai?.abort()
      this.huyTai = new AbortController()
      this.dangTai = true
      this.loiTai = ''
      this.hetPhien = false
      try {
        const query = this.$route.query
        const phanHoi = await nhomCoService.taiDanhSach(
          { ...docBoLoc(query), page: query.page ?? 1, per_page: 12 },
          this.huyTai.signal,
        )
        if (this.daDong || lanTai !== this.lanTai) return
        this.danhSach = phanHoi.data
        this.phanTrang = phanHoi.meta
      } catch (loi) {
        if (this.daDong || lanTai !== this.lanTai || loi.code === 'ERR_CANCELED') return
        const ketQua = layLoiApi(loi)
        this.loiTai = ketQua.thongBao
        this.hetPhien = ketQua.hetPhien
      } finally {
        if (!this.daDong && lanTai === this.lanTai) this.dangTai = false
      }
    },
    async apDungBoLoc() {
      if (this.dangDoi) return
      const query = Object.fromEntries(
        Object.entries(this.boLoc).filter(([, giaTri]) => giaTri !== ''),
      )
      const queryCu = JSON.stringify(this.$route.query)
      await this.$router.push({ name: 'admin-nhom-co', query })
      if (!this.daDong && queryCu === JSON.stringify(this.$route.query)) await this.taiDanhSach()
    },
    async xoaBoLoc() {
      this.boLoc = docBoLoc()
      await this.apDungBoLoc()
    },
    async chuyenTrang(page) {
      if (this.dangDoi) return
      await this.$router.push({
        name: 'admin-nhom-co',
        query: { ...this.$route.query, page: String(page) },
      })
    },
    async moXacNhan(nhom) {
      if (this.dangDoi) return
      this.nhomChon = { ...nhom }
      await this.$nextTick()
      if (!this.daDong) this.$refs.xacNhan.showModal()
    },
    dongXacNhan() {
      if (!this.dangDoi) this.$refs.xacNhan.close()
    },
    huyXacNhan(suKien) {
      if (this.dangDoi) suKien.preventDefault()
    },
    async doiTrangThai() {
      if (this.dangDoi || !this.nhomChon) return
      const nhom = this.nhomChon
      this.dangDoi = true
      this.thongBao = ''
      this.hetPhien = false
      try {
        const phanHoi = await nhomCoService.datTrangThai(nhom.id, {
          trang_thai: nhom.trang_thai === 'HOAT_DONG' ? 'NGUNG_SU_DUNG' : 'HOAT_DONG',
          updated_at: nhom.updated_at,
        })
        if (this.daDong) return
        this.coLoi = false
        this.thongBao = phanHoi.message
        this.$refs.xacNhan.close()
        await this.taiDanhSach()
      } catch (loi) {
        if (this.daDong) return
        const ketQua = layLoiApi(loi)
        this.coLoi = true
        this.hetPhien = ketQua.hetPhien
        this.thongBao = ketQua.thongBao
        this.$refs.xacNhan.close()
        if (loi.response?.status === 409) await this.taiDanhSach()
      } finally {
        if (!this.daDong) this.dangDoi = false
      }
    },
  },
}
</script>

<style scoped>
/* Biến màu sắc và phong cách giao diện */
.text-emerald {
  color: var(--mau-chinh);
}

.nhom-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 24px;
  margin-bottom: 28px;
}

.nhom-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  font-weight: 750;
  letter-spacing: 0.1em;
  color: var(--mau-chinh-dam);
  margin-bottom: 8px;
}

/* Thẻ thống kê */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.stat-card {
  background: var(--mau-the);
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
}

.stat-icon-wrapper {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.stat-icon-emerald {
  background: var(--mau-chinh-nhat);
  color: var(--mau-chinh-dam);
}

.stat-icon-blue {
  background: #eff6ff;
  color: #2563eb;
}

.stat-icon-amber {
  background: #fffbeb;
  color: #d97706;
}

.stat-icon-purple {
  background: #f5f3ff;
  color: #7c3aed;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--mau-chu);
  line-height: 1.2;
}

.stat-label {
  font-size: 0.8rem;
  color: var(--mau-phu);
  margin-top: 2px;
}

/* Panel quản lý danh sách */
.nhom-panel {
  background: var(--mau-the);
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  overflow: hidden;
}

/* Bộ lọc */
.nhom-filters {
  display: grid;
  grid-template-columns: minmax(240px, 1fr) 220px auto;
  gap: 16px;
  align-items: end;
  padding: 20px 24px;
  background: #fcfdfd;
  border-bottom: 1px solid var(--mau-vien);
}

.nhom-filters label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--mau-chu);
  margin-bottom: 6px;
}

.input-group-modern {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon-prefix {
  position: absolute;
  left: 12px;
  color: var(--mau-phu);
  pointer-events: none;
  font-size: 0.875rem;
}

.form-control.has-prefix {
  padding-left: 36px;
}

.form-control-sm,
.form-select-sm {
  min-height: 40px;
  border-radius: 8px;
  border-color: var(--mau-vien);
}

.form-control-sm:focus,
.form-select-sm:focus {
  border-color: var(--mau-chinh);
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}

.nhom-filter-actions {
  display: flex;
  gap: 8px;
}

/* Thanh kết quả */
.nhom-result {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 24px;
  background: var(--mau-nen);
  border-bottom: 1px solid var(--mau-vien);
  font-size: 0.875rem;
}

/* Các hàng danh sách */
.nhom-row {
  display: grid;
  grid-template-columns: minmax(220px, 1fr) 180px 170px 190px;
  align-items: center;
  gap: 16px;
  padding: 18px 24px;
  border-bottom: 1px solid var(--mau-vien);
  transition: background-color 0.15s ease;
}

.nhom-row:last-of-type {
  border-bottom: none;
}

.nhom-row:not(.nhom-labels):hover {
  background-color: #f8fafc;
}

.nhom-labels {
  padding-block: 10px;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--mau-phu);
  letter-spacing: 0.05em;
  background-color: #f8fafc;
  border-bottom: 1px solid var(--mau-vien);
}

.nhom-name {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.nhom-icon {
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  flex-shrink: 0;
  border-radius: 12px;
  background: var(--mau-chinh-nhat);
  color: var(--mau-chinh);
  font-size: 1.25rem;
  border: 1px solid rgba(5, 150, 105, 0.2);
}

.nhom-info-wrap {
  min-width: 0;
}

.nhom-title {
  font-size: 0.98rem;
  font-weight: 700;
  margin: 0 0 4px;
  color: var(--mau-chu);
  overflow-wrap: anywhere;
}

.nhom-code {
  font-size: 0.78rem;
  color: var(--mau-chinh-dam);
  background: rgba(5, 150, 105, 0.08);
  padding: 2px 8px;
  border-radius: 6px;
  border: 1px solid rgba(5, 150, 105, 0.15);
}

/* Cột số bài tập */
.nhom-count {
  display: inline-flex;
  flex-direction: column;
  gap: 3px;
  text-decoration: none;
  color: var(--mau-chu);
  padding: 6px 10px;
  border-radius: 8px;
  transition: all 0.15s ease;
}

.nhom-count:hover {
  background: #f1f5f9;
}

.nhom-count-badge {
  font-size: 0.9rem;
  color: var(--mau-chu);
}

.nhom-count:hover .nhom-count-badge strong {
  color: var(--mau-chinh);
  text-decoration: underline;
}

.nhom-count-sub {
  font-size: 0.75rem;
  color: var(--mau-phu);
}

/* Cột trạng thái */
.nhom-status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.8rem;
  font-weight: 650;
  border-radius: 20px;
  padding: 5px 12px;
  border: 1px solid transparent;
}

.nhom-active {
  background: var(--mau-chinh-nhat);
  color: var(--mau-chinh-dam);
  border-color: rgba(5, 150, 105, 0.2);
}

.nhom-inactive {
  background: #f1f5f9;
  color: #64748b;
  border-color: #e2e8f0;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  display: inline-block;
}

.dot-active {
  background-color: var(--mau-chinh);
  box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.3);
}

.dot-inactive {
  background-color: #94a3b8;
}

/* Thao tác */
.nhom-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

.nhom-actions .btn,
.nhom-filter-actions .btn {
  min-height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.85rem;
}

/* Phân trang */
.nhom-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 24px;
  background: var(--mau-nen);
  border-top: 1px solid var(--mau-vien);
}

.nhom-pagination .btn {
  min-height: 36px;
}

/* Dialog xác nhận */
.nhom-dialog {
  max-width: 500px;
  width: calc(100% - 32px);
  padding: 24px;
  border: 1px solid var(--mau-vien);
  border-radius: 16px;
  color: var(--mau-chu);
  background: var(--mau-the);
  box-shadow:
    0 20px 25px -5px rgba(0, 0, 0, 0.1),
    0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.nhom-dialog::backdrop {
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
}

.nhom-dialog-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 18px;
}

.dialog-icon-wrapper {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.nhom-dialog-eyebrow {
  font-size: 0.7rem;
  font-weight: 750;
  letter-spacing: 0.1em;
  color: var(--mau-phu);
  margin-bottom: 4px;
}

.nhom-dialog-body {
  margin-bottom: 24px;
}

.nhom-dialog-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.nhom-dialog-actions .btn {
  min-height: 40px;
  padding: 0 18px;
  border-radius: 8px;
  font-weight: 600;
}

.empty-icon-ring {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #f1f5f9;
  display: flex;
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

/* Responsive */
@media (max-width: 1080px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .nhom-row {
    grid-template-columns: minmax(180px, 1fr) 150px 140px;
  }

  .nhom-row > :last-child {
    grid-column: 1 / -1;
    justify-content: flex-start;
    padding-top: 8px;
    border-top: 1px dashed var(--mau-vien);
  }

  .nhom-labels {
    display: none;
  }

  .nhom-filters {
    grid-template-columns: 1fr 1fr;
  }

  .nhom-filter-actions {
    grid-column: 1 / -1;
  }
}

@media (max-width: 640px) {
  .nhom-heading {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .nhom-filters {
    grid-template-columns: 1fr;
    padding: 16px;
  }

  .nhom-row {
    grid-template-columns: 1fr;
    gap: 12px;
    padding: 16px;
  }

  .nhom-pagination {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .nhom-pagination .d-flex {
    justify-content: space-between;
  }

  .nhom-pagination .btn {
    flex: 1;
  }
}
</style>
