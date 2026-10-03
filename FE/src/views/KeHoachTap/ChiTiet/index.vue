<template>
  <CaNhanLayout>
    <section class="ga kh-plan">
      <RouterLink :to="quayLai" class="btn btn-outline-secondary kh-back-btn mb-3">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Danh sách giáo án
      </RouterLink>
      <p v-if="dangTai" role="status" class="text-secondary">Đang tải giáo án…</p>
      <div v-if="loi" class="kh-notice" role="alert">
        <div class="d-flex align-items-center gap-2 mb-2 text-danger fw-bold">
          <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
          <span>Có lỗi xảy ra</span>
        </div>
        <p class="mb-2">{{ loi }}</p>
        <ul v-if="Object.keys(loiTruong).length" class="mb-3">
          <li v-for="(giaTri, truong) in loiTruong" :key="truong">{{ giaTri.join(' ') }}</li>
        </ul>
        <button class="btn btn-outline-secondary btn-sm" :disabled="dangLuu" @click="taiChiTiet">
          <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i> Tải lại
        </button>
      </div>
      <template v-if="keHoach && !dangTai">
        <div class="ga-hero-dark kh-plan-hero">
          <header class="kh-heading mb-0">
            <div>
              <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="kh-status" :class="keHoach.trang_thai_hien_thi">
                  <i
                    v-if="keHoach.trang_thai_hien_thi === 'DANG_AP_DUNG'"
                    class="bi bi-check-circle-fill me-1"
                    aria-hidden="true"
                  ></i>
                  <i
                    v-else-if="keHoach.trang_thai_hien_thi === 'CHO_DUYET'"
                    class="bi bi-clock-history me-1"
                    aria-hidden="true"
                  ></i>
                  <i
                    v-else-if="keHoach.trang_thai_hien_thi === 'NHAP'"
                    class="bi bi-pencil me-1"
                    aria-hidden="true"
                  ></i>
                  <i
                    v-else-if="keHoach.trang_thai_hien_thi === 'LUU_TRU'"
                    class="bi bi-archive me-1"
                    aria-hidden="true"
                  ></i>
                  <i v-else class="bi bi-slash-circle me-1" aria-hidden="true"></i>
                  {{ nhanTrangThai }}
                </span>
                <span class="kh-source-tag">
                  <i
                    :class="
                      keHoach.nguon_tao === 'KHACH_HANG' ? 'bi bi-person' : 'bi bi-person-badge'
                    "
                    aria-hidden="true"
                  ></i>
                  {{ keHoach.nguon_tao === 'KHACH_HANG' ? 'KH tự tạo' : 'Giáo án PT giao' }}
                </span>
                <span v-if="keHoach.da_an" class="kh-status DA_HUY">
                  <i class="bi bi-eye-slash me-1" aria-hidden="true"></i> KH đã ẩn
                </span>
              </div>
              <h1>{{ keHoach.ten_ke_hoach }}</h1>
              <p class="mb-2">
                {{
                  keHoach.nguon_tao === 'KHACH_HANG'
                    ? 'KH tự tạo · PT phụ trách có thể xem, không sửa nội dung.'
                    : 'Giáo án do Huấn luyện viên phân công thiết kế riêng cho bạn.'
                }}
              </p>
              <p v-if="keHoach.muc_tieu" class="text-secondary small mb-3">
                <i class="bi bi-bullseye me-1 text-primary" aria-hidden="true"></i>
                Mục tiêu: <strong>{{ keHoach.muc_tieu }}</strong>
              </p>
              <div class="ga-stats-pill-row mb-0 mt-3">
                <span class="ga-stats-pill">
                  <i class="bi bi-calendar3" aria-hidden="true"></i> {{ keHoach.so_ngay_tap }} ngày
                  tập
                </span>
                <span class="ga-stats-pill">
                  <i class="bi bi-activity" aria-hidden="true"></i> {{ keHoach.so_bai_tap }} bài tập
                </span>
                <span class="ga-stats-pill">
                  <i class="bi bi-layers" aria-hidden="true"></i> {{ tongHiep }} tổng hiệp
                </span>
                <span v-if="keHoach.gui_luc" class="ga-stats-pill">
                  <i class="bi bi-send" aria-hidden="true"></i> Đã gửi
                  {{ thoiGian(keHoach.gui_luc) }}
                </span>
              </div>
            </div>
            <div class="kh-actions">
              <RouterLink
                v-if="keHoach.trang_thai === 'DANG_AP_DUNG'"
                :to="
                  laPt ? `/pt/hoc-vien/${keHoach.khach_hang_id}/lich-tap` : '/khach-hang/lich-tap'
                "
                class="btn btn-outline-secondary"
                ><i class="bi bi-calendar2-week" aria-hidden="true"></i> Lên lịch tự tập</RouterLink
              >
              <button
                v-if="keHoach.co_the_an"
                type="button"
                class="btn btn-outline-secondary"
                :disabled="dangLuu"
                @click="deNghiThaoTac('an')"
              >
                <i class="bi bi-eye-slash" aria-hidden="true"></i> Ẩn giáo án
              </button>
              <button
                v-if="keHoach.co_the_hien_lai"
                type="button"
                class="btn btn-primary"
                :disabled="dangLuu"
                @click="deNghiThaoTac('hien-lai')"
              >
                <i class="bi bi-eye" aria-hidden="true"></i> Hiện lại giáo án
              </button>
              <button
                v-if="keHoach.co_the_ap_dung"
                type="button"
                class="btn btn-primary"
                :disabled="dangLuu"
                @click="deNghiThaoTac('ap-dung')"
              >
                <i class="bi bi-play-circle-fill me-1" aria-hidden="true"></i> {{ nhanApDung }}
              </button>
              <button
                v-if="keHoach.co_the_luu_tru"
                type="button"
                class="btn btn-outline-secondary"
                :disabled="dangLuu"
                @click="deNghiThaoTac('luu-tru')"
              >
                <i class="bi bi-archive me-1" aria-hidden="true"></i> Ngừng áp dụng
              </button>
              <RouterLink
                v-if="keHoach.co_the_sua"
                :to="`/${laPt ? 'pt' : 'khach-hang'}/ke-hoach/${keHoach.id}/sua`"
                class="btn btn-outline-secondary"
              >
                <i class="bi bi-pencil me-1" aria-hidden="true"></i> Sửa bản nháp
              </RouterLink>
              <button
                v-if="keHoach.co_the_gui"
                class="btn btn-primary"
                :disabled="dangLuu"
                @click="deNghiThaoTac('gui')"
              >
                <i class="bi bi-send-fill me-1" aria-hidden="true"></i>
                {{ dangLuu ? 'Đang xử lý…' : 'Gửi cho học viên' }}
              </button>
              <button
                v-if="keHoach.co_the_xac_nhan && !hetHan"
                class="btn btn-primary"
                :disabled="dangLuu"
                @click="deNghiThaoTac('xac-nhan')"
              >
                <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>
                {{ dangLuu ? 'Đang xử lý…' : 'Xác nhận áp dụng' }}
              </button>
              <button
                v-if="keHoach.co_the_huy"
                class="btn btn-outline-secondary"
                :disabled="dangLuu"
                @click="deNghiThaoTac('huy')"
              >
                <i class="bi bi-x-circle me-1" aria-hidden="true"></i> Hủy giáo án
              </button>
            </div>
          </header>
        </div>

        <!-- HỘP XÁC NHẬN THAO TÁC -->
        <section
          v-if="hanhDongCho"
          ref="xacNhan"
          tabindex="-1"
          class="kh-notice"
          aria-label="Xác nhận thao tác giáo án"
          @keydown.esc="hanhDongCho = ''"
        >
          <h2 class="mb-2">{{ nhanHanhDong[hanhDongCho] }}</h2>
          <p>{{ thongBaoHanhDong[hanhDongCho] }}</p>
          <div class="kh-actions">
            <button
              type="button"
              class="btn btn-primary"
              :disabled="dangLuu"
              @click="thaoTac(hanhDongCho)"
            >
              {{ dangLuu ? 'Đang xử lý…' : 'Đồng ý, tiếp tục' }}
            </button>
            <button
              type="button"
              class="btn btn-outline-secondary"
              :disabled="dangLuu"
              @click="hanhDongCho = ''"
            >
              Để sau
            </button>
          </div>
        </section>

        <!-- THÔNG BÁO THÀNH CÔNG -->
        <p v-if="thanhCong" class="kh-notice text-success" role="status">
          <i class="bi bi-check-circle-fill me-2" aria-hidden="true"></i>{{ thanhCong }}
        </p>

        <!-- THÔNG BÁO ĐÃ ẨN -->
        <p v-if="keHoach.da_an" class="kh-notice">
          <i class="bi bi-eye-slash me-2 text-warning" aria-hidden="true"></i>
          {{
            laPt
              ? 'KH đã ẩn giáo án này khỏi danh sách của mình. Bạn vẫn có thể xem lịch sử.'
              : 'Giáo án đang ở mục “Đã ẩn”. Nội dung và lịch sử vẫn được giữ; hãy hiện lại nếu muốn áp dụng bản lưu trữ.'
          }}
        </p>

        <!-- HẠN XÁC NHẬN 24 GIỜ KHI CHO_DUYET -->
        <div v-if="keHoach.trang_thai === 'CHO_DUYET'" class="kh-notice">
          <div
            class="d-flex align-items-center gap-2 mb-2 fw-bold"
            :class="hetHan ? 'text-danger' : 'text-warning'"
          >
            <i class="bi bi-clock-history" aria-hidden="true"></i>
            <span>{{
              hetHan ? 'Thời hạn xác nhận đã kết thúc' : 'Hạn xác nhận giáo án trong 24 giờ'
            }}</span>
          </div>
          <p class="mb-1">
            {{
              hetHan
                ? 'Đã hết thời hạn xác nhận. Hãy trao đổi với PT để nhận bản mới.'
                : `Xác nhận trước ${thoiGian(keHoach.han_duyet)} (giờ Việt Nam).`
            }}
          </p>
          <p v-if="keHoach.thay_the_ke_hoach_id" class="small mb-0 text-secondary">
            Khi xác nhận, giáo án này thay bản đang áp dụng. Bản cũ và kết quả tập được giữ trong
            lịch sử.
          </p>
        </div>

        <div v-if="!keHoach.bai_tap.length" class="ga-panel text-center py-5">
          <i class="bi bi-clipboard-x fs-1 text-secondary mb-3 d-block" aria-hidden="true"></i>
          <p class="text-secondary mb-0">
            Bản nháp chưa có bài tập. PT cần thêm bài trước khi gửi.
          </p>
        </div>

        <div class="ga-detail-container kh-plan-detail">
          <nav class="ga-day-switcher-bar" aria-label="Chọn ngày xem bài tập">
            <button
              v-for="ngay in cacNgay"
              :key="ngay"
              type="button"
              class="ga-day-tab"
              :class="{ 'is-active': ngayChon === ngay }"
              :aria-pressed="ngayChon === ngay"
              aria-controls="kh-plan-day"
              @click="ngayChon = ngay"
            >
              <span>Ngày {{ ngay }}</span>
              <span class="tab-badge">{{ baiTheoNgay(ngay).length }} bài</span>
            </button>
          </nav>
          <section id="kh-plan-day" class="ga-exercise-sheet" aria-labelledby="kh-plan-day-title">
            <header class="ga-exercise-sheet-header">
              <h2 id="kh-plan-day-title">
                <i class="bi bi-calendar2-day me-2" aria-hidden="true"></i>Lịch tập Ngày
                {{ ngayChon }}
              </h2>
              <span class="sheet-meta"
                >{{ baiTheoNgay(ngayChon).length }} bài tập · {{ tongHiepNgay }} hiệp</span
              >
            </header>
            <p v-if="!baiTheoNgay(ngayChon).length" class="kh-plan-empty">
              Ngày này chưa có bài tập.
            </p>
            <div v-else class="ga-exercise-list">
              <article v-for="b in baiTheoNgay(ngayChon)" :key="b.id" class="ga-exercise-card">
                <div class="ga-exercise-card-left">
                  <AnhBaiTap
                    class="ga-exercise-thumb"
                    :src="urlMedia(b.anh_url)"
                    :alt="b.ten_bai_tap"
                  />
                  <div class="ga-exercise-info">
                    <h3>{{ b.thu_tu }}. {{ b.ten_bai_tap }}</h3>
                    <div class="ga-exercise-meta">
                      <span v-if="b.dung_cu || b.nhom_co" class="meta-tag">{{
                        b.dung_cu || b.nhom_co
                      }}</span>
                      <span>{{ b.so_hiep }} hiệp × {{ b.so_lan_lap }} lần</span>
                      <span>Nghỉ {{ b.nghi_giay }} giây</span>
                      <span v-if="b.muc_ta_kg != null">Tạ {{ b.muc_ta_kg }} kg</span>
                    </div>
                    <p v-if="b.ghi_chu" class="kh-plan-note">
                      <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>{{ b.ghi_chu }}
                    </p>
                  </div>
                </div>
                <div class="ga-exercise-card-right">
                  <button
                    type="button"
                    class="ga-btn-guide"
                    :aria-label="`Xem hướng dẫn ${b.ten_bai_tap}`"
                    @click="baiHuongDan = b"
                  >
                    <i class="bi bi-play-circle me-1" aria-hidden="true"></i>Xem hướng dẫn
                  </button>
                </div>
              </article>
            </div>
          </section>
        </div>
        <HuongDanBaiTapGiaoAn
          v-if="baiHuongDan"
          :bai-tap="baiHuongDan"
          @dong="baiHuongDan = null"
        />
      </template>
    </section>
  </CaNhanLayout>
</template>
<script>
import CaNhanLayout from '../../../layouts/CaNhanLayout.vue'
import AnhBaiTap from '../../../components/AnhBaiTap.vue'
import HuongDanBaiTapGiaoAn from '../../../components/HuongDanBaiTapGiaoAn.vue'
import baiTapService from '../../../services/baiTapService'
import keHoachTapService from '../../../services/keHoachTapService'
import { layLoiApi } from '../../../utils/loiApi'
import { nhanKeHoach, thoiGianKeHoach } from '../../../utils/keHoachTap'
import '../../../assets/giaoAnMau.css'
import '../../../assets/keHoachTap.css'
import '../../../assets/chiTietKeHoach.css'
export default {
  name: 'ChiTietKeHoachTap',
  components: { CaNhanLayout, AnhBaiTap, HuongDanBaiTapGiaoAn },
  data() {
    return {
      keHoach: null,
      dangTai: false,
      dangLuu: false,
      loi: '',
      loiTruong: {},
      thanhCong: '',
      hanhDongCho: '',
      ngayChon: 1,
      baiHuongDan: null,
      nhanHanhDong: {
        an: 'Ẩn giáo án này?',
        'hien-lai': 'Hiện lại giáo án này?',
        'ap-dung': 'Áp dụng giáo án này?',
        'luu-tru': 'Ngừng áp dụng giáo án này?',
        gui: 'Gửi giáo án cho học viên?',
        'xac-nhan': 'Áp dụng giáo án này?',
        huy: 'Hủy giáo án này?',
      },
      thongBaoHanhDong: {
        an: 'Giáo án chuyển sang mục “Đã ẩn”. Nội dung và lịch sử tập vẫn được giữ; PT phụ trách vẫn xem được. Bạn có thể hiện lại bất cứ lúc nào.',
        'hien-lai':
          'Giáo án trở lại danh sách của bạn và giữ nguyên trạng thái trước đó. Thao tác này không tự áp dụng giáo án.',
        'ap-dung':
          'Bản này trở thành giáo án đang dùng, không cần PT duyệt. Giáo án đang áp dụng trước đó được lưu trữ, kể cả giáo án PT giao. Nội dung và lịch sử tập vẫn được giữ.',
        'luu-tru':
          'Giáo án chuyển sang lưu trữ và bạn không còn giáo án đang áp dụng. Nội dung và lịch sử tập vẫn được giữ. Bạn có thể chọn áp dụng lại bản tự tạo hoặc bản PT đã xác nhận trước đây.',
        gui: 'Học viên có 24 giờ để xác nhận. Nội dung đã gửi sẽ được giữ nguyên.',
        'xac-nhan':
          'Giáo án này trở thành bản đang áp dụng. Bản cũ và kết quả tập được giữ trong lịch sử.',
        huy: 'Học viên sẽ không thể xác nhận bản đã hủy. Nội dung vẫn được giữ trong lịch sử.',
      },
      huyTai: null,
      mocHienTai: Date.now(),
      boDem: null,
    }
  },
  computed: {
    laPt() {
      return this.$route.meta.vaiTro === 'HUAN_LUYEN_VIEN'
    },
    nhanApDung() {
      return this.keHoach?.trang_thai === 'LUU_TRU' ? 'Áp dụng lại giáo án' : 'Áp dụng giáo án này'
    },
    quayLai() {
      return this.laPt
        ? this.keHoach
          ? `/pt/hoc-vien/${this.keHoach.khach_hang_id}/ke-hoach`
          : '/pt/hoc-vien'
        : this.keHoach?.da_an
          ? '/khach-hang/ke-hoach?da_an=1'
          : '/khach-hang/ke-hoach'
    },
    nhanTrangThai() {
      return this.hetHan && this.keHoach?.trang_thai === 'CHO_DUYET'
        ? nhanKeHoach.QUA_HAN
        : this.laPt && this.keHoach?.trang_thai_hien_thi === 'CHO_DUYET'
          ? 'Chờ KH xác nhận'
          : nhanKeHoach[this.keHoach?.trang_thai_hien_thi]
    },
    hetHan() {
      return (
        this.keHoach?.han_duyet && new Date(this.keHoach.han_duyet).getTime() <= this.mocHienTai
      )
    },
    cacNgay() {
      return Array.from({ length: this.keHoach?.so_ngay_tap || 0 }, (_, i) => i + 1)
    },
    tongHiep() {
      return (this.keHoach?.bai_tap || []).reduce((tong, b) => tong + Number(b.so_hiep), 0)
    },
    tongHiepNgay() {
      return this.baiTheoNgay(this.ngayChon).reduce((tong, b) => tong + Number(b.so_hiep), 0)
    },
  },
  watch: {
    '$route.params.id'() {
      this.thanhCong = ''
      this.taiChiTiet()
    },
  },
  mounted() {
    this.taiChiTiet()
    this.boDem = setInterval(() => {
      this.mocHienTai = Date.now()
    }, 1000)
  },
  beforeUnmount() {
    this.huyTai?.abort()
    clearInterval(this.boDem)
  },
  methods: {
    urlMedia: baiTapService.urlMedia,
    thoiGian: thoiGianKeHoach,
    baiTheoNgay(ngay) {
      return this.keHoach.bai_tap.filter((b) => b.ngay_thu === ngay)
    },
    async taiChiTiet() {
      this.hanhDongCho = ''
      this.baiHuongDan = null
      this.ngayChon = 1
      this.huyTai?.abort()
      const huy = new AbortController()
      this.huyTai = huy
      this.dangTai = true
      this.keHoach = null
      this.loi = ''
      this.loiTruong = {}
      try {
        const k = await keHoachTapService.taiChiTiet(this.$route.params.id, this.laPt, huy.signal)
        if (!huy.signal.aborted) this.keHoach = k.data
      } catch (e) {
        if (!huy.signal.aborted) this.loi = layLoiApi(e).thongBao
      } finally {
        if (this.huyTai === huy) this.dangTai = false
      }
    },
    async deNghiThaoTac(hanhDong) {
      if (this.dangLuu) return
      this.hanhDongCho = hanhDong
      await this.$nextTick()
      this.$refs.xacNhan?.focus()
    },
    async thaoTac(hanhDong) {
      if (this.dangLuu || !this.keHoach) return
      this.dangLuu = true
      this.loi = ''
      this.loiTruong = {}
      this.thanhCong = ''
      const id = this.keHoach.id
      try {
        const k = await keHoachTapService.thaoTac(id, this.laPt, hanhDong, this.keHoach.updated_at)
        if (String(this.$route.params.id) === String(id)) {
          this.keHoach = k.data
          this.hanhDongCho = ''
          this.thanhCong = {
            an: 'Đã ẩn giáo án. Bạn có thể xem và hiện lại trong mục “Đã ẩn”.',
            'hien-lai': 'Đã hiện lại giáo án trong danh sách của bạn.',
            'ap-dung': 'Đã áp dụng giáo án.',
            'luu-tru': 'Đã ngừng áp dụng và lưu trữ giáo án.',
            gui: 'Đã gửi giáo án cho học viên.',
            'xac-nhan': 'Đã xác nhận áp dụng giáo án.',
            huy: 'Đã hủy giáo án.',
          }[hanhDong]
        }
      } catch (e) {
        if (String(this.$route.params.id) === String(id)) {
          const loi = layLoiApi(e)
          this.loi = loi.thongBao
          this.loiTruong = loi.loiTruong
        }
      } finally {
        this.dangLuu = false
      }
    },
  },
}
</script>
