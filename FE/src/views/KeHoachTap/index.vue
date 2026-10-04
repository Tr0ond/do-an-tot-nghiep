<template>
  <CaNhanLayout>
    <section class="ga st-plans">
      <RouterLink v-if="laPt" to="/pt/hoc-vien" class="st-text-link"
        ><i class="bi bi-arrow-left" aria-hidden="true"></i> Học viên của tôi</RouterLink
      >
      <header class="st-page-heading">
        <div>
          <span class="st-kicker">GIÁO ÁN CÁ NHÂN</span>
          <h1>
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
                ? 'Giáo án PT giao và giáo án học viên tự tạo.'
                : 'Tự tạo miễn phí hoặc nhận giáo án từ PT phụ trách.'
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
          class="btn btn-primary"
          ><i class="bi bi-plus-lg" aria-hidden="true"></i>
          {{ laPt ? 'Soạn giáo án mới' : 'Tự tạo giáo án' }}</RouterLink
        >
      </header>
      <div class="st-toolbar">
        <label for="nguon-giao-an"
          >Nguồn giáo án<select
            id="nguon-giao-an"
            v-model="nguonTao"
            class="form-select"
            @change="doiNguon"
          >
            <option value="">Tất cả giáo án</option>
            <option value="KHACH_HANG">KH tự tạo</option>
            <option value="PT">PT giao</option>
          </select></label
        >
        <label v-if="!laPt" for="hien-thi-giao-an"
          >Hiển thị<select
            id="hien-thi-giao-an"
            :value="daAn ? '1' : '0'"
            class="form-select"
            @change="doiHienThi"
          >
            <option value="0">Giáo án của tôi</option>
            <option value="1">Đã ẩn</option>
          </select></label
        >
        <span class="st-result-count">{{ meta.total || danhSach.length }} giáo án</span>
      </div>
      <p v-if="dangTai" role="status" class="st-empty">Đang tải danh sách giáo án…</p>
      <div v-else-if="loi" class="alert alert-danger" role="alert">
        {{ loi }} <button class="btn btn-outline-secondary" @click="taiDanhSach">Thử lại</button>
      </div>
      <div v-else class="st-plan-workspace">
        <div>
          <div v-if="!danhSach.length" class="st-empty">
            <i class="bi bi-journal" aria-hidden="true"></i>
            <h2>{{ daAn ? 'Chưa có giáo án đã ẩn' : 'Chưa có giáo án nào' }}</h2>
            <p>
              {{
                daAn
                  ? 'Giáo án đã ẩn vẫn giữ nội dung và lịch sử.'
                  : 'Bắt đầu bằng một giáo án mới từ thư viện bài tập.'
              }}
            </p>
          </div>
          <article v-for="k in danhSach" :key="k.id" class="st-plan-row">
            <div class="st-plan-symbol" aria-hidden="true">
              <i
                class="bi"
                :class="
                  k.trang_thai_hien_thi === 'DANG_AP_DUNG' ? 'bi-journal-check' : 'bi-journal-text'
                "
              ></i>
            </div>
            <div class="st-plan-copy">
              <div class="st-row-actions">
                <span class="kh-status" :class="k.trang_thai_hien_thi">{{ nhanTrangThai(k) }}</span
                ><span v-if="k.da_an" class="kh-status">KH đã ẩn</span
                ><small>{{ k.nguon_tao === 'KHACH_HANG' ? 'KH tự tạo' : 'PT giao' }}</small>
              </div>
              <h2>{{ k.ten_ke_hoach }}</h2>
              <p>{{ k.muc_tieu || 'Giáo án tập luyện cá nhân' }}</p>
              <div class="st-plan-meta">
                <span
                  ><i class="bi bi-calendar3" aria-hidden="true"></i> {{ k.so_ngay_tap }} ngày
                  tập</span
                ><span
                  ><i class="bi bi-activity" aria-hidden="true"></i> {{ k.so_bai_tap }} bài
                  tập</span
                ><span v-if="k.trang_thai_hien_thi === 'CHO_DUYET'" class="text-warning"
                  >Xác nhận trước {{ thoiGian(k.han_duyet) }}</span
                >
              </div>
            </div>
            <RouterLink
              :to="`/${laPt ? 'pt' : 'khach-hang'}/ke-hoach/${k.id}`"
              class="btn btn-outline-secondary"
              >Xem giáo án <i class="bi bi-arrow-up-right" aria-hidden="true"></i
            ></RouterLink>
          </article>
          <nav v-if="meta.last_page > 1" class="st-pagination" aria-label="Phân trang giáo án">
            <button
              class="btn btn-outline-secondary"
              :disabled="dangTai || page <= 1"
              @click="chuyenTrang(-1)"
            >
              Trước</button
            ><span>{{ page }} / {{ meta.last_page }}</span
            ><button
              class="btn btn-outline-secondary"
              :disabled="dangTai || page >= meta.last_page"
              @click="chuyenTrang(1)"
            >
              Sau
            </button>
          </nav>
        </div>
        <aside class="st-plan-rail">
          <h2><i class="bi bi-check2-circle" aria-hidden="true"></i> Đang áp dụng</h2>
          <template
            v-for="k in danhSach
              .filter((k) => k.trang_thai_hien_thi === 'DANG_AP_DUNG')
              .slice(0, 1)"
            :key="k.id"
            ><h3>{{ k.ten_ke_hoach }}</h3>
            <p>{{ k.muc_tieu }}</p>
            <dl class="st-fact-list">
              <div>
                <dt>Ngày tập</dt>
                <dd>{{ k.so_ngay_tap }}</dd>
              </div>
              <div>
                <dt>Bài tập</dt>
                <dd>{{ k.so_bai_tap }}</dd>
              </div>
            </dl>
            <RouterLink
              :to="laPt ? `/pt/hoc-vien/${$route.params.khachId}/lich-tap` : '/khach-hang/lich-tap'"
              class="btn btn-primary"
              ><i class="bi bi-calendar-plus" aria-hidden="true"></i> Lên lịch tự tập</RouterLink
            ></template
          >
          <p v-if="!danhSach.some((k) => k.trang_thai_hien_thi === 'DANG_AP_DUNG')">
            Không có bản đang áp dụng trong danh sách này.
          </p>
          <div class="st-rule-note">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <p>
              {{
                daAn
                  ? 'Mở giáo án để hiện lại. Nội dung và lịch sử tập vẫn được giữ.'
                  : 'Chỉ một giáo án được áp dụng tại một thời điểm. Áp dụng bản mới sẽ lưu trữ bản cũ.'
              }}
            </p>
          </div>
        </aside>
      </div>
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
