<template>
  <CaNhanLayout>
    <section class="ga kh-plan">
      <RouterLink v-if="laPt" to="/pt/hoc-vien" class="btn btn-outline-secondary kh-back-btn">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        <span>Học viên của tôi</span>
      </RouterLink>

      <header class="kh-heading">
        <div>
          <span class="ga-eyebrow">
            <i class="bi bi-journal-text me-1 text-emerald" aria-hidden="true"></i>GIÁO ÁN CÁ NHÂN
          </span>
          <h1 class="h2 fw-bold">
            {{
              laPt
                ? hocVien
                  ? `Giáo án của ${hocVien.ho_ten}`
                  : 'Giáo án học viên'
                : daAn
                  ? 'Giáo án đã ẩn'
                  : 'Giáo án của tôi'
            }}
          </h1>
          <p>
            {{
              laPt
                ? 'Theo dõi toàn bộ giáo án PT giao và giáo án học viên tự tạo, bao gồm cả bản nháp và kế hoạch đang áp dụng.'
                : 'Tự tạo giáo án miễn phí từ thư viện hoặc xem giáo án do PT giao. Bạn có một giáo án đang áp dụng; chọn bản mới sẽ tự động lưu trữ bản cũ.'
            }}
          </p>
        </div>

        <RouterLink
          v-if="!laPt || hocVien"
          :to="
            laPt
              ? `/pt/hoc-vien/${$route.params.khachId}/ke-hoach/them`
              : '/khach-hang/ke-hoach/them'
          "
          class="btn btn-primary d-inline-flex align-items-center gap-2"
        >
          <i class="bi bi-plus-lg" aria-hidden="true"></i>
          <span>{{ laPt ? 'Soạn giáo án mới' : 'Tự tạo giáo án' }}</span>
        </RouterLink>
      </header>

      <!-- Thanh công cụ lọc nguồn & trạng thái hiển thị -->
      <div class="kh-filter-toolbar">
        <div class="kh-filter-group">
          <div class="kh-filter-item">
            <label for="nguon-giao-an">
              <i class="bi bi-funnel text-emerald" aria-hidden="true"></i>Nguồn giáo án:
            </label>
            <select
              id="nguon-giao-an"
              v-model="nguonTao"
              class="kh-filter-select"
              @change="doiNguon"
            >
              <option value="">Tất cả giáo án</option>
              <option value="KHACH_HANG">KH tự tạo</option>
              <option value="PT">PT giao</option>
            </select>
          </div>

          <div v-if="!laPt" class="kh-filter-item">
            <label for="hien-thi-giao-an">
              <i class="bi bi-eye text-primary" aria-hidden="true"></i>Hiển thị:
            </label>
            <select
              id="hien-thi-giao-an"
              :value="daAn ? '1' : '0'"
              class="kh-filter-select"
              @change="doiHienThi"
            >
              <option value="0">Giáo án của tôi</option>
              <option value="1">Đã ẩn</option>
            </select>
          </div>
        </div>

        <div v-if="!laPt" class="kh-filter-hint">
          <span>
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
            {{
              daAn
                ? 'Mở giáo án để hiện lại. Nội dung và lịch sử tập vẫn được giữ an toàn.'
                : 'Có thể ẩn giáo án tự tạo đã hủy hoặc ngừng áp dụng trong trang chi tiết.'
            }}
          </span>
        </div>
      </div>

      <!-- Trạng thái tải -->
      <div v-if="dangTai" class="p-5 text-center" role="status">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <p class="text-muted small mb-0">Đang tải danh sách giáo án…</p>
      </div>

      <!-- Báo lỗi -->
      <div v-else-if="loi" class="kh-notice" role="alert">
        <div class="d-flex align-items-start gap-3">
          <i
            class="bi bi-exclamation-triangle-fill text-danger fs-4 flex-shrink-0"
            aria-hidden="true"
          ></i>
          <div>
            <h2 class="h6 fw-bold mb-1 text-danger">Chưa tải được danh sách giáo án</h2>
            <p class="mb-2 small">{{ loi }}</p>
            <button class="btn btn-outline-secondary btn-sm" @click="taiDanhSach">Thử lại</button>
          </div>
        </div>
      </div>

      <!-- Danh sách rỗng -->
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
          <i class="bi bi-journal-x fs-2 text-muted" aria-hidden="true"></i>
        </div>
        <h2 class="h5 fw-bold mb-2">
          {{ daAn ? 'Chưa có giáo án đã ẩn' : 'Chưa có giáo án nào' }}
        </h2>
        <p class="text-muted small mb-3" style="max-width: 460px; margin: 0 auto">
          {{
            daAn
              ? 'Giáo án bạn ẩn sẽ xuất hiện tại đây để xem lại hoặc khôi phục hiển thị.'
              : laPt
                ? 'Bắt đầu soạn giáo án mới từ giáo án mẫu đã duyệt hoặc chọn bài tập trong thư viện.'
                : 'Bạn có thể tự chọn bài từ thư viện bài tập và lưu giáo án ngay, kể cả khi chưa có huấn luyện viên.'
          }}
        </p>
        <RouterLink
          v-if="!daAn && (!laPt || hocVien)"
          :to="
            laPt
              ? `/pt/hoc-vien/${$route.params.khachId}/ke-hoach/them`
              : '/khach-hang/ke-hoach/them'
          "
          class="btn btn-primary btn-sm"
        >
          <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
          {{ laPt ? 'Soạn giáo án ngay' : 'Tự tạo giáo án mới' }}
        </RouterLink>
      </div>

      <!-- Danh sách giáo án dạng thẻ -->
      <div v-else class="kh-cards">
        <article v-for="k in danhSach" :key="k.id" class="kh-card">
          <div class="kh-card-top">
            <div class="kh-card-badges">
              <span class="kh-status" :class="k.trang_thai_hien_thi">
                <i
                  :class="{
                    'bi bi-check-circle-fill': k.trang_thai_hien_thi === 'DANG_AP_DUNG',
                    'bi bi-hourglass-split': k.trang_thai_hien_thi === 'CHO_DUYET',
                    'bi bi-pencil-square': k.trang_thai_hien_thi === 'NHAP',
                    'bi bi-archive': k.trang_thai_hien_thi === 'LUU_TRU',
                    'bi bi-x-circle':
                      k.trang_thai_hien_thi === 'DA_HUY' || k.trang_thai_hien_thi === 'QUA_HAN',
                  }"
                  aria-hidden="true"
                ></i>
                {{ nhanTrangThai(k) }}
              </span>
              <span v-if="k.da_an" class="kh-status ms-1">
                <i class="bi bi-eye-slash" aria-hidden="true"></i> KH đã ẩn
              </span>
            </div>

            <span class="kh-source-tag">
              <i
                :class="`bi ${k.nguon_tao === 'KHACH_HANG' ? 'bi-person text-secondary' : 'bi-award-fill text-primary'}`"
                aria-hidden="true"
              ></i>
              {{ k.nguon_tao === 'KHACH_HANG' ? 'KH tự tạo' : 'PT giao' }}
            </span>
          </div>

          <h2>{{ k.ten_ke_hoach }}</h2>
          <p class="kh-card-desc">{{ k.muc_tieu || 'Giáo án tập luyện cá nhân' }}</p>

          <div class="kh-meta">
            <span class="kh-meta-item">
              <i class="bi bi-calendar3" aria-hidden="true"></i>
              <span
                ><strong>{{ k.so_ngay_tap }}</strong> ngày tập</span
              >
            </span>
            <span class="kh-meta-item">
              <i class="bi bi-activity" aria-hidden="true"></i>
              <span
                ><strong>{{ k.so_bai_tap }}</strong> bài tập</span
              >
            </span>
          </div>

          <div v-if="k.trang_thai_hien_thi === 'CHO_DUYET'" class="kh-deadline-callout">
            <i class="bi bi-clock-history" aria-hidden="true"></i>
            <span
              >Xác nhận trước <strong>{{ thoiGian(k.han_duyet) }}</strong></span
            >
          </div>

          <div class="kh-card-footer">
            <RouterLink
              :to="`/${laPt ? 'pt' : 'khach-hang'}/ke-hoach/${k.id}`"
              class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2"
            >
              <span>Xem giáo án</span>
              <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </RouterLink>
          </div>
        </article>
      </div>

      <!-- Phân trang -->
      <nav v-if="meta.last_page > 1" class="kh-pages" aria-label="Phân trang giáo án">
        <button
          class="btn btn-outline-secondary btn-sm"
          :disabled="dangTai || page <= 1"
          @click="chuyenTrang(-1)"
        >
          <i class="bi bi-chevron-left me-1" aria-hidden="true"></i>Trước
        </button>
        <span class="badge bg-secondary-subtle text-body border px-3 py-2 small">
          Trang {{ page }} / {{ meta.last_page }}
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
import CaNhanLayout from '../../layouts/CaNhanLayout.vue'
import keHoachTapService from '../../services/keHoachTapService'
import { layLoiApi } from '../../utils/loiApi'
import { nhanKeHoach, thoiGianKeHoach } from '../../utils/keHoachTap'
import '../../assets/giaoAnMau.css'
import '../../assets/keHoachTap.css'

export default {
  name: 'DanhSachKeHoachTap',
  components: { CaNhanLayout },
  data() {
    return {
      danhSach: [],
      hocVien: null,
      page: 1,
      nguonTao: '',
      meta: { last_page: 1 },
      dangTai: false,
      loi: '',
      huyTai: null,
    }
  },
  computed: {
    laPt() {
      return this.$route.meta.vaiTro === 'HUAN_LUYEN_VIEN'
    },
    daAn() {
      return !this.laPt && this.$route.query?.da_an === '1'
    },
  },
  watch: {
    '$route.query.da_an'() {
      this.page = 1
      this.taiDanhSach()
    },
    '$route.params.khachId'() {
      this.page = 1
      this.taiDanhSach()
    },
  },
  mounted() {
    this.taiDanhSach()
  },
  beforeUnmount() {
    this.huyTai?.abort()
  },
  methods: {
    doiHienThi(event) {
      const query = { ...this.$route.query }
      if (event.target.value === '1') query.da_an = '1'
      else delete query.da_an
      this.$router.push({ query })
    },
    doiNguon() {
      this.page = 1
      this.taiDanhSach()
    },
    thoiGian: thoiGianKeHoach,
    nhanTrangThai(k) {
      return this.laPt && k.trang_thai_hien_thi === 'CHO_DUYET'
        ? 'Chờ KH xác nhận'
        : nhanKeHoach[k.trang_thai_hien_thi]
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
      this.hocVien = null
      try {
        const k = await keHoachTapService.taiDanhSach(
          this.laPt ? this.$route.params.khachId : null,
          {
            page: this.page,
            ...(!this.laPt ? { da_an: this.daAn ? 1 : 0 } : {}),
            ...(this.nguonTao ? { nguon_tao: this.nguonTao } : {}),
          },
          huy.signal,
        )
        if (!huy.signal.aborted) {
          this.danhSach = k.data
          this.meta = k.meta
          if (this.laPt && k.meta.hoc_vien) this.hocVien = k.meta.hoc_vien
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
