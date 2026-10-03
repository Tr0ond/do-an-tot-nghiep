<template>
  <CaNhanLayout>
    <section class="ga kh-plan">
      <header class="kh-heading">
        <div>
          <span class="ga-eyebrow">
            <i class="bi bi-people-fill me-1 text-emerald" aria-hidden="true"></i>ĐỒNG HÀNH CÙNG HỌC
            VIÊN
          </span>
          <h1 class="h2 fw-bold">Học viên & giáo án</h1>
          <p>
            Quản lý danh sách học viên đang được phân công, thiết kế và tối ưu giáo án cá nhân theo
            tiến độ tập luyện.
          </p>
        </div>
        <div v-if="meta.total" class="d-flex align-items-center">
          <span class="badge bg-secondary-subtle text-body border px-3 py-2 fs-6">
            {{ meta.total }} học viên đang phụ trách
          </span>
        </div>
      </header>

      <!-- Thanh tìm kiếm học viên -->
      <form class="kh-filter-toolbar" @submit.prevent="timKiem">
        <div class="d-flex align-items-center gap-2 flex-grow-1">
          <div class="input-group-modern flex-grow-1" style="max-width: 480px">
            <span class="input-icon-prefix" aria-hidden="true">
              <i class="bi bi-search"></i>
            </span>
            <input
              id="tim-hoc-vien"
              v-model="tuKhoa"
              maxlength="100"
              class="form-control form-control-sm has-prefix"
              placeholder="Tìm theo họ tên học viên…"
              aria-label="Tìm học viên"
            />
          </div>
          <button type="submit" class="btn btn-primary btn-sm" :disabled="dangTai">
            <i class="bi bi-search me-1" aria-hidden="true"></i>Tìm kiếm
          </button>
          <button
            v-if="tuKhoa"
            type="button"
            class="btn btn-outline-secondary btn-sm"
            @click="xoaTimKiem"
          >
            <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Xóa lọc
          </button>
        </div>
      </form>

      <!-- Trạng thái tải -->
      <div v-if="dangTai" class="p-5 text-center" role="status">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <p class="text-muted small mb-0">Đang tải danh sách học viên từ máy chủ…</p>
      </div>

      <!-- Báo lỗi -->
      <div v-else-if="loi" class="kh-notice" role="alert">
        <div class="d-flex align-items-start gap-3">
          <i
            class="bi bi-exclamation-triangle-fill text-danger fs-4 flex-shrink-0"
            aria-hidden="true"
          ></i>
          <div>
            <h2 class="h6 fw-bold mb-1 text-danger">Chưa tải được danh sách học viên</h2>
            <p class="mb-2 small">{{ loi }}</p>
            <button class="btn btn-outline-secondary btn-sm" @click="taiDanhSach">Thử lại</button>
          </div>
        </div>
      </div>

      <!-- Trạng thái rỗng -->
      <div v-else-if="!danhSach.length" class="ga-panel text-center py-5">
        <div
          class="empty-icon-ring mx-auto mb-3"
          style="
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--mau-the-sub, #1e1e1e);
            display: grid;
            place-items: center;
          "
        >
          <i class="bi bi-person-x fs-2 text-muted" aria-hidden="true"></i>
        </div>
        <h2 class="h5 fw-bold mb-2">
          {{ tuKhoa ? 'Không tìm thấy học viên phù hợp' : 'Chưa có học viên được phân công' }}
        </h2>
        <p class="text-muted small mb-3" style="max-width: 440px; margin: 0 auto">
          {{
            tuKhoa
              ? 'Vui lòng kiểm tra lại từ khóa tìm kiếm hoặc bấm Xóa lọc để xem tất cả.'
              : 'Học viên sẽ tự động xuất hiện tại đây ngay khi Admin hoàn tất phân công huấn luyện viên cho gói tập.'
          }}
        </p>
        <button
          v-if="tuKhoa"
          type="button"
          class="btn btn-outline-secondary btn-sm"
          @click="xoaTimKiem"
        >
          <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Xóa bộ lọc
        </button>
      </div>

      <!-- Danh sách thẻ học viên -->
      <div v-else class="kh-cards">
        <article v-for="kh in danhSach" :key="kh.id" class="kh-card">
          <div class="kh-card-top">
            <span class="badge bg-success-subtle text-success border border-success-subtle">
              <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Đang phụ trách
            </span>
            <span class="badge bg-secondary-subtle text-body border small">#{{ kh.id }}</span>
          </div>

          <div class="kh-student-card">
            <div class="kh-student-avatar" aria-hidden="true">
              {{ kh.ho_ten ? kh.ho_ten.charAt(0).toUpperCase() : 'H' }}
            </div>
            <div class="min-width-0">
              <h2 class="h5 fw-bold mb-1 text-truncate">{{ kh.ho_ten }}</h2>
              <span class="small text-muted d-flex align-items-center gap-1">
                <i class="bi bi-journal-text text-primary" aria-hidden="true"></i>Giáo án cá nhân
              </span>
            </div>
          </div>

          <p class="kh-card-desc mb-3">
            Sẵn sàng lập kế hoạch huấn luyện, nhập mức tạ, số hiệp và gửi giáo án cho học viên xác
            nhận trong 24 giờ.
          </p>

          <div class="kh-card-footer pt-3 mt-auto border-top d-flex flex-column gap-2">
            <RouterLink
              :to="`/pt/hoc-vien/${kh.id}/lich-tap`"
              class="btn btn-outline-secondary w-100"
              ><i class="bi bi-calendar2-week" aria-hidden="true"></i> Lịch & nhật ký học
              viên</RouterLink
            >
            <RouterLink
              :to="`/pt/hoc-vien/${kh.id}/ke-hoach`"
              class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2"
            >
              <span>Xem và soạn giáo án</span>
              <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </RouterLink>
            <RouterLink
              :to="`/pt/hoc-vien/${kh.id}/ke-hoach/them`"
              class="btn btn-primary btn-sm w-100 d-flex align-items-center justify-content-center gap-1"
            >
              <i class="bi bi-plus-lg" aria-hidden="true"></i>
              <span>Soạn giáo án mới</span>
            </RouterLink>
          </div>
        </article>
      </div>

      <!-- Phân trang -->
      <nav v-if="meta.last_page > 1" class="kh-pages" aria-label="Phân trang học viên">
        <button
          class="btn btn-outline-secondary btn-sm"
          :disabled="dangTai || page <= 1"
          @click="chuyenTrang(-1)"
        >
          <i class="bi bi-chevron-left me-1" aria-hidden="true"></i>Trước
        </button>
        <span class="badge bg-secondary-subtle text-body border px-3 py-2 small">
          Trang {{ page }} / {{ meta.last_page }} ({{ meta.total }} mục)
        </span>
        <button
          class="btn btn-outline-secondary btn-sm"
          :disabled="dangTai || page >= meta.last_page"
          @click="chuyenTrang(1)"
        >
          Sau<i class="bi bi-chevron-right ms-1" aria-hidden="true"></i>
        </button>
      </nav>
    </section>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../../layouts/CaNhanLayout.vue'
import keHoachTapService from '../../../services/keHoachTapService'
import { layLoiApi } from '../../../utils/loiApi'
import '../../../assets/giaoAnMau.css'
import '../../../assets/keHoachTap.css'

export default {
  name: 'HocVienPT',
  components: { CaNhanLayout },
  data() {
    return {
      danhSach: [],
      tuKhoa: '',
      page: 1,
      meta: { last_page: 1, total: 0 },
      dangTai: false,
      loi: '',
      huyTai: null,
    }
  },
  mounted() {
    this.taiDanhSach()
  },
  beforeUnmount() {
    this.huyTai?.abort()
  },
  methods: {
    timKiem() {
      this.page = 1
      this.taiDanhSach()
    },
    xoaTimKiem() {
      this.tuKhoa = ''
      this.timKiem()
    },
    chuyenTrang(huong) {
      this.page += huong
      this.taiDanhSach()
    },
    async taiDanhSach() {
      this.huyTai?.abort()
      const huy = new AbortController()
      this.huyTai = huy
      this.dangTai = true
      this.loi = ''
      try {
        const k = await keHoachTapService.taiHocVien(
          { tu_khoa: this.tuKhoa.trim(), page: this.page },
          huy.signal,
        )
        if (!huy.signal.aborted) {
          this.danhSach = k.data
          this.meta = k.meta
        }
      } catch (e) {
        if (!huy.signal.aborted) this.loi = layLoiApi(e).thongBao
      } finally {
        if (this.huyTai === huy) this.dangTai = false
      }
    },
  },
}
</script>
