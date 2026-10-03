<template>
  <aside class="ga-panel ga-picker shadow-sm">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h2>
        <i class="bi bi-plus-circle-fill text-primary me-2"></i>
        Thêm bài vào Ngày {{ ngay }}
      </h2>
      <span class="badge bg-secondary-subtle text-body border">Ngày {{ ngay }}</span>
    </div>

    <!-- Ô tìm kiếm bài tập -->
    <div class="mb-3">
      <label for="tim-bai-giao-an" class="small fw-bold text-muted">
        Tìm kiếm bài tập theo tên
      </label>
      <div class="input-group">
        <input
          id="tim-bai-giao-an"
          v-model="tuKhoa"
          maxlength="100"
          placeholder="Nhập tên bài tập…"
          :disabled="voHieu"
          class="form-control"
          @keydown.enter.prevent="timKiem"
        />
        <button type="button" class="btn btn-primary" :disabled="voHieu" @click="timKiem">
          <i class="bi bi-search" aria-hidden="true"></i> Tìm
        </button>
      </div>
    </div>

    <!-- Trạng thái đang tìm -->
    <div v-if="dangTai" class="text-center py-4 text-muted small" role="status">
      <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
      Đang tìm bài tập…
    </div>

    <!-- Trạng thái lỗi -->
    <div v-else-if="loi" class="ga-errors alert alert-danger p-2 small" role="alert">
      {{ loi }}
      <button type="button" class="btn btn-outline-secondary btn-sm mt-2 w-100" @click="taiBaiTap">
        Thử lại
      </button>
    </div>

    <!-- Kết quả tìm kiếm -->
    <template v-else>
      <p v-if="!danhSach.length" class="text-muted small py-3 text-center mb-0">
        <i class="bi bi-search me-1"></i> Không tìm thấy bài tập đang hoạt động.
      </p>

      <div class="d-flex flex-column gap-2">
        <div
          v-for="bai in danhSach"
          :key="bai.id"
          class="ga-picker-result rounded-3 p-2 border"
          @pointerenter="xemGif(bai, $event)"
          @pointerleave="dungGif(bai)"
          @focusin="xemGif(bai, $event)"
          @focusout="roiTheBai(bai, $event)"
        >
          <AnhBaiTap
            :key="anhHienThi(bai)"
            class="ga-picker-thumb"
            :src="anhHienThi(bai)"
            :alt="bai.ten_tieng_viet || bai.ten_bai_tap"
            :loading="baiDangXem === bai.id ? 'eager' : 'lazy'"
            @loi="loiAnh(bai)"
          />
          <div class="ga-picker-info">
            <strong>{{ bai.ten_tieng_viet || bai.ten_bai_tap }}</strong>
            <div class="d-flex gap-2 align-items-center flex-wrap mt-1">
              <small class="badge bg-secondary-subtle text-body border">
                {{ bai.nhom_co.ten_nhom_co }}
              </small>
              <small v-if="bai.dung_cu" class="text-muted" style="font-size: 0.75rem">
                {{ bai.dung_cu }}
              </small>
            </div>
          </div>

          <button
            type="button"
            class="btn btn-primary ga-icon flex-shrink-0"
            :disabled="voHieu"
            :aria-label="'Thêm ' + (bai.ten_tieng_viet || bai.ten_bai_tap) + ' vào ngày ' + ngay"
            @click="$emit('chon', bai)"
          >
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
          </button>
        </div>
      </div>

      <!-- Phân trang thư viện chọn bài -->
      <nav
        v-if="meta.last_page > 1"
        class="ga-picker-pages pt-3 border-top mt-3"
        aria-label="Phân trang chọn bài"
      >
        <button
          type="button"
          class="btn btn-outline-secondary ga-icon btn-sm"
          :disabled="voHieu || page <= 1"
          aria-label="Trang bài trước"
          @click="chuyenTrang(-1)"
        >
          <i class="bi bi-chevron-left" aria-hidden="true"></i>
        </button>

        <small class="fw-bold text-muted">Trang {{ page }} / {{ meta.last_page }}</small>

        <button
          type="button"
          class="btn btn-outline-secondary ga-icon btn-sm"
          :disabled="voHieu || page >= meta.last_page"
          aria-label="Trang bài sau"
          @click="chuyenTrang(1)"
        >
          <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </button>
      </nav>
      <p v-if="danhSach.some((bai) => bai.anh_url)" class="ga-picker-credit">
        Minh họa:
        <a href="https://gymvisual.com/" target="_blank" rel="noopener noreferrer">Gym Visual</a>
      </p>
    </template>
  </aside>
</template>

<script>
import baiTapService from '../services/baiTapService'
import AnhBaiTap from './AnhBaiTap.vue'
import { layLoiApi } from '../utils/loiApi'

export default {
  name: 'ChonBaiTapGiaoAn',
  components: { AnhBaiTap },
  props: { ngay: { type: Number, required: true }, voHieu: Boolean },
  emits: ['chon'],
  data() {
    return {
      tuKhoa: '',
      page: 1,
      danhSach: [],
      meta: { last_page: 1 },
      dangTai: false,
      loi: '',
      huyTai: null,
      baiDangXem: null,
      gifBiLoi: [],
    }
  },
  mounted() {
    this.taiBaiTap()
  },
  beforeUnmount() {
    this.huyTai?.abort()
  },
  methods: {
    urlMedia: baiTapService.urlMedia,
    xemGif(bai, suKien) {
      if (suKien.pointerType === 'touch') return
      if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) return
      if (this.urlMedia(bai.gif_url) && !this.gifBiLoi.includes(bai.gif_url)) {
        this.baiDangXem = bai.id
      }
    },
    dungGif(bai) {
      if (this.baiDangXem === bai.id) this.baiDangXem = null
    },
    roiTheBai(bai, suKien) {
      if (!suKien.currentTarget.contains(suKien.relatedTarget)) this.dungGif(bai)
    },
    anhHienThi(bai) {
      return this.baiDangXem === bai.id && !this.gifBiLoi.includes(bai.gif_url)
        ? this.urlMedia(bai.gif_url) || this.urlMedia(bai.anh_url)
        : this.urlMedia(bai.anh_url)
    },
    loiAnh(bai) {
      if (this.baiDangXem === bai.id && this.urlMedia(bai.gif_url)) {
        this.gifBiLoi.push(bai.gif_url)
        this.dungGif(bai)
      }
    },
    timKiem() {
      this.page = 1
      this.taiBaiTap()
    },
    chuyenTrang(huong) {
      this.page += huong
      this.taiBaiTap()
    },
    async taiBaiTap() {
      this.huyTai?.abort()
      this.baiDangXem = null
      const huy = new AbortController()
      this.huyTai = huy
      this.dangTai = true
      this.loi = ''
      try {
        const ketQua = await baiTapService.taiDanhSach(
          { tu_khoa: this.tuKhoa.trim(), page: this.page, per_page: 6 },
          huy.signal,
        )
        if (!huy.signal.aborted) {
          this.danhSach = ketQua.data
          this.meta = ketQua.meta
        }
      } catch (loi) {
        if (!huy.signal.aborted) this.loi = layLoiApi(loi).thongBao
      } finally {
        if (this.huyTai === huy) this.dangTai = false
      }
    },
  },
}
</script>

<style scoped>
.ga-picker-result {
  gap: 10px;
  position: relative;
  transition:
    transform 180ms ease,
    border-color 180ms ease,
    box-shadow 180ms ease;
}

.ga-picker-result:focus-within {
  border-color: var(--mau-chinh) !important;
  box-shadow: 0 6px 18px color-mix(in srgb, var(--mau-chinh) 18%, transparent);
  z-index: 1;
}

@media (hover: hover) and (pointer: fine) {
  .ga-picker-result:hover {
    transform: scale(1.03);
    border-color: var(--mau-chinh) !important;
    box-shadow: 0 6px 18px color-mix(in srgb, var(--mau-chinh) 18%, transparent);
    z-index: 1;
  }
}

@media (prefers-reduced-motion: reduce) {
  .ga-picker-result {
    transition: none;
  }

  .ga-picker-result:hover {
    transform: none;
  }
}

.ga-picker-thumb {
  width: 48px;
  height: 48px;
  aspect-ratio: 1;
  flex: 0 0 48px;
  border-radius: 12px;
  background: var(--mau-the);
  border: 1px solid var(--mau-vien);
}

.ga-picker-thumb :deep(.image-fallback) {
  padding: 0;
  gap: 0;
}

.ga-picker-thumb :deep(.image-fallback span) {
  display: none;
}

.ga-picker-thumb :deep(.fallback-icon-ring) {
  width: 30px;
  height: 30px;
  font-size: 1rem;
}

.ga-picker-thumb :deep(.skeleton-pulse) {
  width: 22px;
  height: 22px;
}

.ga-picker-info {
  flex: 1;
  min-width: 0;
}

.ga-picker-result .ga-icon {
  width: 44px;
  height: 44px;
  min-height: 44px;
}

.ga-picker-credit {
  margin: 12px 0 0;
  font-size: 0.75rem;
  color: var(--mau-phu);
}

.ga-picker-credit a {
  color: inherit;
}
</style>
