<template>
  <section class="ga">
    <!-- Header trang -->
    <header class="ga-head">
      <div>
        <span class="ga-eyebrow">
          <i class="bi bi-collection-play-fill" aria-hidden="true"></i>
          {{ quanTri ? 'Danh mục huấn luyện' : 'Thư viện dành cho PT' }}
        </span>
        <h1>Giáo án mẫu</h1>
        <p>
          {{
            quanTri
              ? 'Soạn lịch tập theo ngày và duyệt giáo án để huấn luyện viên tham khảo.'
              : 'Tham khảo các giáo án đã duyệt, với bài tập và khối lượng tập theo từng ngày.'
          }}
        </p>
      </div>
      <RouterLink v-if="quanTri" to="/admin/giao-an-mau/them" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm giáo án
      </RouterLink>
    </header>

    <!-- Banner gợi ý nhanh phong cách thể thao hiện đại (Ảnh 1) -->
    <aside class="ga-prompt-banner">
      <div class="d-flex align-items-center gap-3">
        <div class="ga-num-circle" style="border-color: #34d399; color: #34d399">
          <i class="bi bi-lightning-charge-fill"></i>
        </div>
        <p>Lộ trình huấn luyện mẫu được thiết kế bài bản theo chu kỳ từng ngày tập.</p>
      </div>
      <div class="ga-pill-group">
        <span class="btn-pill btn-pill-primary">{{ quanTri ? 'Quản trị' : 'Thư viện PT' }}</span>
      </div>
    </aside>

    <!-- Bộ lọc tìm kiếm và trạng thái -->
    <form class="ga-filter ga-panel" @submit.prevent="timKiem">
      <div>
        <label for="tim-giao-an">
          <i class="bi bi-search me-1" aria-hidden="true"></i> Tên hoặc mục tiêu
        </label>
        <input
          id="tim-giao-an"
          v-model="tuKhoa"
          maxlength="100"
          placeholder="Ví dụ: Tăng cơ, Toàn thân, Giảm mỡ…"
        />
      </div>
      <div v-if="quanTri">
        <label for="loc-giao-an">
          <i class="bi bi-funnel me-1" aria-hidden="true"></i> Trạng thái
        </label>
        <select id="loc-giao-an" v-model="trangThai">
          <option value="">Tất cả trạng thái</option>
          <option v-for="(nhan, ma) in nhanTrangThai" :key="ma" :value="ma">{{ nhan }}</option>
        </select>
      </div>
      <button class="btn btn-primary" type="submit">
        <i class="bi bi-search" aria-hidden="true"></i> Tìm giáo án
      </button>
    </form>

    <!-- Thông báo lỗi -->
    <div v-if="loi" class="alert alert-danger" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
      {{ loi }}
      <div class="mt-2">
        <RouterLink v-if="hetPhien" to="/dang-nhap" class="btn btn-outline-secondary">
          Đăng nhập lại
        </RouterLink>
        <button v-else class="btn btn-outline-secondary" @click="taiDanhSach">Thử lại</button>
      </div>
    </div>

    <!-- Trạng thái đang tải -->
    <div v-if="dangTai" class="ga-panel ga-empty" role="status">
      <div class="spinner-border text-success mb-3" role="status"></div>
      <h2>Đang tải danh sách giáo án…</h2>
      <p>Vui lòng đợi giây lát trong khi hệ thống đồng bộ dữ liệu.</p>
    </div>

    <!-- Danh sách giáo án dạng Routine Cards (Ảnh 1) -->
    <template v-else-if="!loi">
      <p class="mb-3 fw-bold text-muted small" aria-live="polite">
        <i class="bi bi-check2-circle text-success me-1"></i>
        Tìm thấy {{ meta.total }} giáo án {{ quanTri ? '' : 'đã duyệt sẵn sàng sử dụng' }}
      </p>

      <div v-if="!danhSach.length" class="ga-panel ga-empty">
        <i class="bi bi-journal-x" aria-hidden="true"></i>
        <h2>
          {{ tuKhoa || trangThai ? 'Không tìm thấy giáo án phù hợp' : 'Chưa có giáo án mẫu' }}
        </h2>
        <p>
          {{
            quanTri
              ? 'Tạo giáo án đầu tiên từ thư viện bài tập hiện có.'
              : 'Giáo án sẽ xuất hiện tại đây sau khi Admin duyệt.'
          }}
        </p>
      </div>

      <div v-else class="ga-list">
        <article v-for="giaoAn in danhSach" :key="giaoAn.id" class="ga-routine-card ga-item">
          <!-- Routine Card Header -->
          <div class="ga-routine-header">
            <div class="ga-routine-title-wrap">
              <h2>
                {{ giaoAn.ten_giao_an }}
                <span class="ga-status" :class="giaoAn.trang_thai">
                  {{ nhanTrangThai[giaoAn.trang_thai] }}
                </span>
              </h2>
              <p>{{ giaoAn.muc_tieu || 'Chưa ghi mục tiêu cụ thể' }}</p>
            </div>
            <div class="ga-routine-actions-top">
              <span class="ga-meta-badge">
                <i class="bi bi-calendar3" aria-hidden="true"></i> {{ giaoAn.so_ngay_tap }} ngày
              </span>
            </div>
          </div>

          <!-- Danh sách ngày tập ①, ②, ③, ④ theo phong cách Ảnh 1 -->
          <div class="ga-routine-days">
            <div v-for="ngay in Math.min(giaoAn.so_ngay_tap, 4)" :key="ngay" class="ga-routine-row">
              <div class="ga-routine-row-left">
                <div class="ga-num-circle">
                  {{ ['①', '②', '③', '④', '⑤', '⑥', '⑦'][ngay - 1] || ngay }}
                </div>
                <h3 class="ga-routine-row-name">Ngày {{ ngay }}: {{ giaoAn.ten_giao_an }}</h3>
              </div>

              <!-- Miniature Thumbnail Cluster với +3 badge (Ảnh 1) -->
              <div class="ga-thumb-cluster" title="Xem bài tập trong ngày">
                <div class="ga-thumb-box">
                  <i class="bi bi-activity text-info"></i>
                </div>
                <div class="ga-thumb-box">
                  <i class="bi bi-heart-pulse text-warning"></i>
                </div>
                <div class="ga-thumb-box">
                  <i class="bi bi-lightning-charge text-success"></i>
                </div>
                <span class="ga-thumb-more">
                  +{{
                    Math.max(1, Math.round((giaoAn.so_bai_tap || 4) / (giaoAn.so_ngay_tap || 1)))
                  }}
                </span>
              </div>
            </div>

            <!-- Nếu giáo án có nhiều hơn 4 ngày -->
            <div
              v-if="giaoAn.so_ngay_tap > 4"
              class="text-center small py-1"
              style="color: #94a3b8"
            >
              +{{ giaoAn.so_ngay_tap - 4 }} ngày tập khác trong giáo án này
            </div>
          </div>

          <!-- Routine Footer -->
          <div class="ga-routine-footer">
            <div class="ga-routine-meta-pills">
              <span class="ga-meta-badge">
                <i class="bi bi-list-check" aria-hidden="true"></i>
                {{ giaoAn.so_bai_tap }} bài tập
              </span>
              <span v-if="giaoAn.duyet_luc" class="ga-meta-badge">
                <i class="bi bi-check-circle" aria-hidden="true"></i> Đã duyệt
              </span>
            </div>

            <RouterLink
              :to="
                quanTri
                  ? '/admin/giao-an-mau/' + giaoAn.id + '/sua'
                  : '/pt/giao-an-mau/' + giaoAn.id
              "
              class="btn btn-outline-secondary"
            >
              <span>{{ quanTri ? 'Mở giáo án & sửa' : 'Xem chi tiết' }}</span>
              <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </RouterLink>
          </div>
        </article>
      </div>

      <!-- Phân trang -->
      <nav v-if="meta.last_page > 1" class="ga-pagination" aria-label="Phân trang giáo án">
        <button
          class="btn btn-outline-secondary"
          :disabled="page <= 1"
          @click="chuyenTrang(page - 1)"
        >
          <i class="bi bi-chevron-left" aria-hidden="true"></i> Trước
        </button>
        <span class="px-2">Trang {{ page }} / {{ meta.last_page }}</span>
        <button
          class="btn btn-outline-secondary"
          :disabled="page >= meta.last_page"
          @click="chuyenTrang(page + 1)"
        >
          Sau <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </button>
      </nav>
    </template>
  </section>
</template>

<script>
import giaoAnMauService from '../services/giaoAnMauService'
import { nhanTrangThai } from '../utils/giaoAnMau'
import { layLoiApi } from '../utils/loiApi'
import '../assets/giaoAnMau.css'

export default {
  name: 'DanhSachGiaoAnMau',
  props: { quanTri: Boolean },
  data() {
    return {
      nhanTrangThai,
      tuKhoa: '',
      trangThai: '',
      page: 1,
      danhSach: [],
      meta: { total: 0, last_page: 1 },
      dangTai: false,
      loi: '',
      hetPhien: false,
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
    chuyenTrang(page) {
      this.page = page
      this.taiDanhSach()
    },
    async taiDanhSach() {
      this.huyTai?.abort()
      const huy = new AbortController()
      this.huyTai = huy
      this.dangTai = true
      this.loi = ''
      this.hetPhien = false
      try {
        const ketQua = await giaoAnMauService.taiDanhSach(
          {
            tu_khoa: this.tuKhoa.trim(),
            ...(this.quanTri ? { trang_thai: this.trangThai } : {}),
            page: this.page,
          },
          huy.signal,
          this.quanTri,
        )
        if (huy.signal.aborted) return
        this.danhSach = ketQua.data
        this.meta = ketQua.meta
      } catch (loi) {
        if (!huy.signal.aborted) {
          const ketQua = layLoiApi(loi)
          this.loi = ketQua.thongBao
          this.hetPhien = ketQua.hetPhien
        }
      } finally {
        if (this.huyTai === huy) this.dangTai = false
      }
    },
  },
}
</script>
