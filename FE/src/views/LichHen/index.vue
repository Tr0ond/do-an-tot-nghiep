<template>
  <CaNhanLayout>
    <div class="m04-page">
      <!-- Tiêu đề trang và điều hướng chính -->
      <div class="m04-heading">
        <div>
          <span class="m04-kicker">
            <i class="bi bi-calendar2-range me-1" aria-hidden="true"></i>
            Lịch huấn luyện cá nhân
          </span>
          <h1 class="h2 fw-bold">{{ chiTiet ? 'Chi tiết buổi tập' : 'Lịch hẹn huấn luyện' }}</h1>
          <p>
            {{
              khuVuc === 'admin'
                ? 'Theo dõi lịch của phòng tập và xử lý các buổi quá hạn xác nhận.'
                : khuVuc === 'pt'
                  ? 'Xác nhận yêu cầu và ghi nhận kết quả các buổi tập với học viên.'
                  : 'Theo dõi yêu cầu đặt lịch, giờ tập và các buổi đã hoàn thành.'
            }}
          </p>
        </div>
        <RouterLink v-if="chiTiet" :to="`/${khuVuc}/lich-hen`" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Về danh sách
        </RouterLink>
        <RouterLink
          v-else-if="khuVuc !== 'admin'"
          :to="khuVuc === 'pt' ? '/pt/khung-gio' : '/khach-hang/dat-lich'"
          class="btn btn-primary"
        >
          <i class="bi bi-calendar-plus me-2" aria-hidden="true"></i>
          {{ khuVuc === 'pt' ? 'Mở giờ rảnh' : 'Đặt lịch với PT' }}
        </RouterLink>
      </div>

      <!-- Thông báo thành công -->
      <div
        v-if="thongBao"
        class="alert alert-success d-flex align-items-center gap-2 rounded-3"
        role="status"
      >
        <i class="bi bi-check-circle-fill text-success flex-shrink-0" aria-hidden="true"></i>
        <span>{{ thongBao }}</span>
      </div>

      <!-- Báo lỗi và thử lại -->
      <div
        v-if="loi"
        class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2 rounded-3"
        role="alert"
      >
        <div class="d-flex align-items-center gap-2">
          <i
            class="bi bi-exclamation-triangle-fill text-warning flex-shrink-0"
            aria-hidden="true"
          ></i>
          <span>{{ loi }}</span>
        </div>
        <button
          class="btn btn-outline-secondary btn-sm"
          :disabled="dangLuu || dangTai"
          @click="taiDuLieu"
        >
          <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Thử lại
        </button>
      </div>

      <!-- Thống kê ca tập tuần này (Stitch Screen 19 KPI Ribbon) -->
      <div v-if="!chiTiet && ds.length" class="st-kpi-ribbon">
        <div class="st-kpi-chip">
          <span>Tổng số ca:</span>
          <strong>{{ ds.length }} ca</strong>
        </div>
        <div class="st-kpi-chip">
          <span class="st-kpi-dot primary"></span>
          <span>Đã xác nhận:</span>
          <strong>{{ demTrangThai('DA_XAC_NHAN') }}</strong>
        </div>
        <div class="st-kpi-chip">
          <span class="st-kpi-dot warning"></span>
          <span>Chờ duyệt:</span>
          <strong>{{ demTrangThai('CHO_XAC_NHAN') }}</strong>
        </div>
        <div class="st-kpi-chip">
          <span class="st-kpi-dot danger"></span>
          <span>Vắng mặt:</span>
          <strong>{{ demTrangThai('VANG_MAT') }}</strong>
        </div>
      </div>

      <!-- Thanh công cụ bộ lọc danh sách -->
      <form v-if="!chiTiet" class="m04-toolbar" @submit.prevent="locLich">
        <div>
          <label for="ngay-lich">
            <i class="bi bi-calendar-event me-1 text-muted" aria-hidden="true"></i>
            Ngày tập · giờ Việt Nam
          </label>
          <input
            id="ngay-lich"
            v-model="ngay"
            class="form-control"
            type="date"
            :disabled="dangLuu"
          />
        </div>
        <div>
          <label for="trang-thai-lich">
            <i class="bi bi-funnel me-1 text-muted" aria-hidden="true"></i>
            Trạng thái
          </label>
          <select id="trang-thai-lich" v-model="trangThai" class="form-select" :disabled="dangLuu">
            <option value="">Tất cả trạng thái</option>
            <option
              v-for="(ten, maTrangThai) in trangThaiLich"
              :key="maTrangThai"
              :value="maTrangThai"
            >
              {{ ten }}
            </option>
          </select>
        </div>
        <button class="btn btn-outline-secondary" :disabled="dangTai || dangLuu">
          <i class="bi bi-search me-1" aria-hidden="true"></i>
          {{ dangTai ? 'Đang tải…' : 'Lọc lịch hẹn' }}
        </button>

        <!-- Bộ lọc nhanh trạng thái dạng Pills (Stitch Screen 19) -->
        <div class="st-filter-pills">
          <span class="small text-muted me-1">Trạng thái:</span>
          <button
            type="button"
            class="st-pill-btn"
            :class="{ 'is-active': trangThai === '' }"
            @click="chonPillTrangThai('')"
          >
            Tất cả ({{ ds.length }})
          </button>
          <button
            v-for="(ten, maTrangThai) in trangThaiLich"
            :key="maTrangThai"
            type="button"
            class="st-pill-btn"
            :class="{ 'is-active': trangThai === maTrangThai }"
            @click="chonPillTrangThai(maTrangThai)"
          >
            <span class="status-dot" :class="maTrangThai"></span>
            {{ ten }}
          </button>
        </div>
      </form>

      <!-- Trạng thái đang tải -->
      <div v-if="dangTai" class="m04-panel m04-empty" role="status">
        <div class="spinner-border text-success mb-3" role="status"></div>
        <p class="mb-0">Đang tải lịch hẹn…</p>
      </div>

      <!-- Chi tiết buổi tập -->
      <template v-else-if="chiTiet && lich">
        <div class="m04-details">
          <!-- Bảng thông tin buổi tập -->
          <section class="m04-panel">
            <div
              class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 pb-3 border-bottom"
            >
              <div class="d-flex align-items-center gap-2">
                <span class="m04-state" :class="lich.trang_thai">
                  <span class="status-dot"></span>
                  <span>{{ trangThaiLich[lich.trang_thai] }}</span>
                </span>
                <span class="badge bg-light text-muted border font-monospace"
                  >Buổi #{{ lich.id }}</span
                >
              </div>
              <span class="small text-muted d-flex align-items-center gap-1">
                <i class="bi bi-clock-history text-emerald" aria-hidden="true"></i>
                Thời lượng 60 phút
              </span>
            </div>

            <!-- Các thuộc tính buổi tập -->
            <dl>
              <div>
                <dt>
                  <i class="bi bi-play-circle text-emerald me-1" aria-hidden="true"></i>
                  Bắt đầu
                </dt>
                <dd>{{ dinhDangLuc(lich.bat_dau_luc) }}</dd>
              </div>
              <div>
                <dt>
                  <i class="bi bi-stop-circle text-muted me-1" aria-hidden="true"></i>
                  Kết thúc
                </dt>
                <dd>{{ dinhDangLuc(lich.ket_thuc_luc) }}</dd>
              </div>
              <div>
                <dt>
                  <i class="bi bi-person text-primary me-1" aria-hidden="true"></i>
                  Học viên
                </dt>
                <dd class="d-flex align-items-center gap-2">
                  <span class="user-avatar-tiny">{{
                    lich.khach_hang?.charAt(0).toUpperCase()
                  }}</span>
                  <span>{{ lich.khach_hang }}</span>
                </dd>
              </div>
              <div>
                <dt>
                  <i class="bi bi-award text-emerald me-1" aria-hidden="true"></i>
                  Huấn luyện viên
                </dt>
                <dd class="d-flex align-items-center gap-2">
                  <span class="user-avatar-tiny" style="background: #e0f2fe; color: #0369a1">{{
                    lich.pt?.charAt(0).toUpperCase()
                  }}</span>
                  <span>{{ lich.pt }}</span>
                </dd>
              </div>
              <div>
                <dt>
                  <i class="bi bi-box-seam text-muted me-1" aria-hidden="true"></i>
                  Gói gắn với buổi tập
                </dt>
                <dd>#{{ lich.dang_ky_goi_tap_id }}</dd>
              </div>
              <div>
                <dt>
                  <i class="bi bi-check2-circle text-emerald me-1" aria-hidden="true"></i>
                  Buổi PT tiêu hao
                </dt>
                <dd>
                  {{
                    lich.tieu_hao_luc
                      ? '1 buổi · ' + dinhDangLuc(lich.tieu_hao_luc)
                      : 'Chưa trừ buổi'
                  }}
                </dd>
              </div>
            </dl>

            <!-- Ghi chú và hạn xác nhận -->
            <div
              v-if="lich.trang_thai === 'CHO_XAC_NHAN'"
              class="m04-note alert-warning-theme mt-4"
            >
              <i class="bi bi-hourglass-split me-1 text-amber" aria-hidden="true"></i>
              PT cần xác nhận trước {{ dinhDangLuc(lich.han_xac_nhan_dat_lich) }}. Quá thời hạn, yêu
              cầu tự hết hiệu lực.
            </div>
            <div v-if="lich.trang_thai === 'DA_XAC_NHAN' && khuVuc === 'pt'" class="m04-note mt-4">
              <i class="bi bi-info-circle me-1 text-emerald" aria-hidden="true"></i>
              Ghi nhận sau giờ kết thúc, trước {{ dinhDangLuc(lich.han_xac_nhan_hoan_thanh) }}. Hoàn
              thành trừ 1 buổi; vắng mặt không trừ lượt.
            </div>
            <div v-if="lich.ly_do_huy" class="m04-note alert-danger-theme mt-4">
              <i class="bi bi-x-circle me-1 text-danger" aria-hidden="true"></i>
              <strong>Lý do hủy:</strong> {{ lich.ly_do_huy }}
              <span class="d-block small text-muted mt-1">{{ dinhDangLuc(lich.huy_luc) }}</span>
            </div>
            <div v-if="lich.ly_do_ghi_nhan" class="m04-note mt-4">
              <i class="bi bi-journal-text me-1 text-primary" aria-hidden="true"></i>
              <strong>Ghi nhận từ PT:</strong> {{ lich.ly_do_ghi_nhan }}
              <span class="d-block small text-muted mt-1">{{
                dinhDangLuc(lich.ghi_nhan_luc)
              }}</span>
            </div>
            <div v-if="lich.dong_xu_ly_luc" class="m04-note alert-warning-theme mt-4">
              <i class="bi bi-archive me-1 text-amber" aria-hidden="true"></i>
              <strong>Đã đóng xử lý:</strong> {{ dinhDangLuc(lich.dong_xu_ly_luc) }}
              <span class="d-block mt-1">{{ lich.ly_do_dong_xu_ly }}</span>
            </div>
            <div
              v-if="lich.trang_thai === 'QUA_HAN_XAC_NHAN' && !lich.dong_xu_ly_luc"
              class="m04-note alert-warning-theme mt-4"
            >
              <i class="bi bi-exclamation-triangle me-1 text-amber" aria-hidden="true"></i>
              Buổi này quá hạn xác nhận. Chờ quản trị viên xem xét và đóng xử lý; chưa trừ buổi PT.
            </div>
          </section>

          <!-- Thao tác xử lý buổi tập (nếu có quyền) -->
          <section v-if="lich.hanh_dong?.length" class="m04-panel">
            <h2 class="h5 fw-bold mb-3 d-flex align-items-center gap-2">
              <i class="bi bi-gear-fill text-emerald" aria-hidden="true"></i>
              Xử lý buổi tập
            </h2>
            <div class="m04-actions">
              <button
                v-for="hd in lich.hanh_dong"
                :key="hd"
                class="btn"
                :class="
                  hd === 'hoan-thanh' || hd === 'xac-nhan'
                    ? 'btn-primary'
                    : hd === 'huy' || hd === 'tu-choi'
                      ? 'btn-outline-danger'
                      : 'btn-outline-secondary'
                "
                :disabled="dangLuu"
                @click="chonHanhDong(hd)"
              >
                <i
                  class="me-1"
                  :class="{
                    'bi bi-check-circle-fill': hd === 'hoan-thanh',
                    'bi bi-check2': hd === 'xac-nhan',
                    'bi bi-x-circle': hd === 'huy',
                    'bi bi-slash-circle': hd === 'tu-choi',
                    'bi bi-person-x': hd === 'vang-mat',
                    'bi bi-archive': hd === 'dong-xu-ly',
                  }"
                  aria-hidden="true"
                ></i>
                {{ tenHanhDong[hd] }}
              </button>
            </div>

            <!-- Biểu mẫu xác nhận thao tác -->
            <form v-if="hanhDong" class="mt-4 border-top pt-4" @submit.prevent="luu">
              <h3 class="h6 fw-bold text-body">{{ tenHanhDong[hanhDong] }} buổi #{{ lich.id }}</h3>
              <p class="text-muted small">
                {{
                  hanhDong === 'hoan-thanh'
                    ? 'Xác nhận học viên đã hoàn thành. Gói gắn với buổi này sẽ được trừ đúng 1 buổi.'
                    : hanhDong === 'dong-xu-ly'
                      ? 'Ghi lý do xem xét. Đóng xử lý không trừ buổi và không ghi thành hoàn thành.'
                      : 'Kiểm tra thông tin trước khi xác nhận.'
                }}
              </p>
              <template v-if="canLyDo">
                <label for="ly-do-lich" class="form-label fw-semibold">
                  Lý do <span aria-hidden="true">*</span>
                </label>
                <textarea
                  id="ly-do-lich"
                  v-model="lyDo"
                  class="form-control mb-3"
                  rows="3"
                  maxlength="1000"
                  required
                  placeholder="Nhập lý do cụ thể..."
                  :disabled="dangLuu"
                ></textarea>
              </template>
              <div class="m04-actions">
                <button class="btn btn-primary" :disabled="dangLuu">
                  <span
                    v-if="dangLuu"
                    class="spinner-border spinner-border-sm me-1"
                    role="status"
                    aria-hidden="true"
                  ></span>
                  <i v-else class="bi bi-check2-circle me-1" aria-hidden="true"></i>
                  {{ dangLuu ? 'Đang lưu…' : 'Xác nhận thao tác' }}
                </button>
                <button
                  type="button"
                  class="btn btn-outline-secondary"
                  :disabled="dangLuu"
                  @click="boQua"
                >
                  Bỏ qua
                </button>
              </div>
            </form>
          </section>

          <!-- Tải lại trạng thái -->
          <button
            class="btn btn-outline-secondary align-self-start"
            :disabled="dangLuu || dangTai"
            @click="taiDuLieu"
          >
            <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>
            Tải lại trạng thái
          </button>
        </div>
      </template>

      <!-- Danh sách lịch hẹn -->
      <template v-else-if="!chiTiet && !loi">
        <div v-if="ds.length" class="st-master-detail">
          <div class="st-table-wrap">
            <table class="table align-middle st-appointment-table">
              <caption class="visually-hidden">
                Danh sách lịch hẹn huấn luyện
              </caption>
              <thead>
                <tr>
                  <th scope="col">Buổi</th>
                  <th scope="col">Ngày & giờ</th>
                  <th scope="col">Học viên / PT</th>
                  <th scope="col">Trạng thái</th>
                  <th scope="col">Chi tiết</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="buoi in ds"
                  :key="buoi.id"
                  :class="{ 'st-selected-row': buoiChon?.id === buoi.id }"
                >
                  <td>#{{ buoi.id }}</td>
                  <td>
                    <strong>{{ gioVietNam(buoi.bat_dau_luc) }}</strong
                    ><small class="d-block text-muted">{{
                      ngayVietNam(new Date(buoi.bat_dau_luc))
                    }}</small>
                  </td>
                  <td>
                    <div class="st-person">
                      <span class="st-avatar" aria-hidden="true">{{
                        (khuVuc === 'khach-hang' ? buoi.pt : buoi.khach_hang)
                          ?.charAt(0)
                          .toUpperCase()
                      }}</span>
                      <div>
                        <strong>{{ khuVuc === 'khach-hang' ? buoi.pt : buoi.khach_hang }}</strong
                        ><small v-if="khuVuc === 'admin'">PT: {{ buoi.pt }}</small>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="m04-state" :class="buoi.trang_thai">{{
                      trangThaiLich[buoi.trang_thai]
                    }}</span
                    ><small v-if="buoi.dong_xu_ly_luc" class="d-block text-muted"
                      >Đã đóng xử lý</small
                    >
                  </td>
                  <td>
                    <button
                      type="button"
                      class="btn btn-outline-secondary"
                      :aria-label="`Xem nhanh buổi ${buoi.id}`"
                      title="Xem nhanh buổi tập"
                      @click="buoiChon = buoi"
                    >
                      <i class="bi bi-layout-sidebar-reverse" aria-hidden="true"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <aside v-if="buoiChon" class="st-detail-rail" aria-label="Xem nhanh lịch hẹn">
            <div class="st-rail-heading">
              <h2>Buổi #{{ buoiChon.id }}</h2>
              <button
                type="button"
                class="btn btn-outline-secondary"
                aria-label="Đóng xem nhanh lịch hẹn"
                @click="buoiChon = null"
              >
                <i class="bi bi-x-lg" aria-hidden="true"></i>
              </button>
            </div>
            <span class="m04-state" :class="buoiChon.trang_thai">{{
              trangThaiLich[buoiChon.trang_thai]
            }}</span>
            <dl class="st-fact-list">
              <div>
                <dt>Bắt đầu</dt>
                <dd>{{ dinhDangLuc(buoiChon.bat_dau_luc) }}</dd>
              </div>
              <div>
                <dt>Thời lượng</dt>
                <dd>60 phút</dd>
              </div>
              <div>
                <dt>Học viên</dt>
                <dd>{{ buoiChon.khach_hang }}</dd>
              </div>
              <div>
                <dt>Huấn luyện viên</dt>
                <dd>{{ buoiChon.pt }}</dd>
              </div>
            </dl>
            <RouterLink
              :to="`/${khuVuc}/lich-hen/${buoiChon.id}`"
              class="btn btn-primary w-100 mt-3"
              >Xem buổi tập <i class="bi bi-arrow-right" aria-hidden="true"></i
            ></RouterLink>
          </aside>
          <aside v-else class="st-detail-rail st-empty-rail">
            <i class="bi bi-calendar2-check" aria-hidden="true"></i>
            <h2>Chi tiết lịch hẹn</h2>
            <p>Chưa chọn buổi tập.</p>
          </aside>
        </div>

        <!-- Trạng thái trống -->
        <section v-else class="m04-panel m04-empty">
          <i class="bi bi-calendar2-check" aria-hidden="true"></i>
          <h2 class="h5">Chưa có lịch hẹn phù hợp</h2>
          <p>Chọn ngày hoặc trạng thái khác để xem lịch.</p>
        </section>

        <!-- Phân trang -->
        <nav
          v-if="meta.last_page > 1"
          class="m04-actions justify-content-center mt-4"
          aria-label="Trang lịch hẹn"
        >
          <button
            class="btn btn-outline-secondary btn-sm"
            :disabled="page <= 1 || dangTai"
            @click="doiTrang(page - 1)"
          >
            <i class="bi bi-chevron-left me-1" aria-hidden="true"></i>Trước
          </button>
          <span class="badge bg-light text-muted border px-3 py-2 align-self-center font-monospace"
            >{{ page }} / {{ meta.last_page }}</span
          >
          <button
            class="btn btn-outline-secondary btn-sm"
            :disabled="page >= meta.last_page || dangTai"
            @click="doiTrang(page + 1)"
          >
            Sau<i class="bi bi-chevron-right ms-1" aria-hidden="true"></i>
          </button>
        </nav>
      </template>
    </div>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../layouts/CaNhanLayout.vue'
import lichHenService from '../../services/lichHenService'
import { useXacThucStore } from '../../stores/xacThuc'
import {
  trangThaiLich,
  tenHanhDong,
  ngayVietNam,
  gioVietNam,
  khuVucVaiTro,
} from '../../utils/lichHen'
import { dinhDangLuc } from '../../utils/donHang'
import { layLoiApi } from '../../utils/loiApi'
import '../../assets/lichHen.css'

export default {
  name: 'LichHenPage',
  components: { CaNhanLayout },
  data() {
    return {
      ds: [],
      buoiChon: null,
      lich: null,
      meta: {},
      ngay: '',
      trangThai: '',
      page: 1,
      hanhDong: '',
      lyDo: '',
      thongBao: '',
      loi: '',
      dangTai: false,
      dangLuu: false,
      boHuy: null,
      lanTai: 0,
      daDong: false,
      timer: null,
    }
  },
  computed: {
    taiKhoan() {
      return useXacThucStore().taiKhoan
    },
    khuVuc() {
      return khuVucVaiTro(this.taiKhoan?.vai_tro)
    },
    chiTiet() {
      return !!this.$route.params.id
    },
    canLyDo() {
      return ['huy', 'tu-choi', 'vang-mat', 'dong-xu-ly'].includes(this.hanhDong)
    },
    trangThaiLich() {
      return trangThaiLich
    },
    tenHanhDong() {
      return tenHanhDong
    },
  },
  watch: { '$route.fullPath': 'docRoute', taiKhoan: 'docRoute' },
  mounted() {
    this.docRoute()
  },
  beforeUnmount() {
    this.daDong = true
    this.lanTai++
    this.boHuy?.abort()
    clearTimeout(this.timer)
  },
  methods: {
    demTrangThai(ma) {
      if (!Array.isArray(this.ds)) return 0
      return this.ds.filter((b) => b.trang_thai === ma).length
    },
    chonPillTrangThai(ma) {
      this.trangThai = ma
      this.locLich()
    },
    boQua() {
      this.hanhDong = ''
      this.lyDo = ''
    },
    dinhDangLuc,
    ngayVietNam,
    gioVietNam,
    docRoute() {
      this.hanhDong = ''
      this.lyDo = ''
      this.thongBao = ''
      this.ngay = typeof this.$route.query.ngay === 'string' ? this.$route.query.ngay : ''
      this.trangThai =
        typeof this.$route.query.trang_thai === 'string' ? this.$route.query.trang_thai : ''
      this.page = Math.max(1, Number(this.$route.query.page) || 1)
      this.taiDuLieu()
    },
    locLich() {
      this.doiTrang(1)
    },
    async doiTrang(page) {
      const duongDanCu = this.$route.fullPath
      const query = { page }
      if (this.ngay) query.ngay = this.ngay
      if (this.trangThai) query.trang_thai = this.trangThai
      await this.$router.push({ path: `/${this.khuVuc}/lich-hen`, query })
      if (this.$route.fullPath === duongDanCu) {
        this.page = page
        this.taiDuLieu()
      }
    },
    chonHanhDong(hd) {
      if (this.dangLuu || !this.lich?.hanh_dong?.includes(hd)) return
      this.hanhDong = hd
      this.lyDo = ''
      this.thongBao = ''
      this.loi = ''
    },
    async taiDuLieu() {
      this.boHuy?.abort()
      clearTimeout(this.timer)
      this.boHuy = new AbortController()
      const signal = this.boHuy.signal
      const lan = ++this.lanTai
      this.ds = []
      this.buoiChon = null
      this.lich = null
      this.meta = {}
      this.loi = ''
      this.dangTai = false
      if (!this.khuVuc) return
      this.dangTai = true
      try {
        const r = this.chiTiet
          ? await lichHenService.taiChiTiet(this.khuVuc, this.$route.params.id, signal)
          : await lichHenService.taiLich(
              this.khuVuc,
              {
                page: this.page,
                ...(this.ngay ? { ngay: this.ngay } : {}),
                ...(this.trangThai ? { trang_thai: this.trangThai } : {}),
              },
              signal,
            )
        if (this.daDong || lan !== this.lanTai) return
        if (this.chiTiet) {
          this.lich = r.data
          if (!r.data.hanh_dong.includes(this.hanhDong)) this.hanhDong = ''
        } else {
          this.ds = r.data
          this.meta = r.meta
        }
        this.timer = setTimeout(() => {
          if (!this.dangLuu && !this.hanhDong) this.taiDuLieu()
        }, 60000)
      } catch (e) {
        if (!signal.aborted && !this.daDong && lan === this.lanTai) this.loi = layLoiApi(e).thongBao
      } finally {
        if (!this.daDong && lan === this.lanTai) this.dangTai = false
      }
    },
    async luu() {
      if (this.dangLuu || !this.lich || !this.lich.hanh_dong.includes(this.hanhDong)) return
      if (this.canLyDo && !this.lyDo.trim()) {
        this.loi = 'Vui lòng ghi lý do.'
        return
      }
      const idNguoi = this.taiKhoan?.id
      const duongDan = this.$route.fullPath
      this.dangLuu = true
      this.loi = ''
      this.thongBao = ''
      try {
        const r = await lichHenService.thaoTac(
          this.khuVuc,
          this.lich.id,
          this.hanhDong,
          this.lyDo.trim(),
        )
        if (!this.daDong && this.taiKhoan?.id === idNguoi && this.$route.fullPath === duongDan) {
          this.lich = r.data
          this.hanhDong = ''
          this.lyDo = ''
          this.thongBao = r.message
        }
      } catch (e) {
        if (!this.daDong && this.taiKhoan?.id === idNguoi && this.$route.fullPath === duongDan)
          this.loi = layLoiApi(e).thongBao
      } finally {
        if (!this.daDong) this.dangLuu = false
      }
    },
  },
}
</script>
