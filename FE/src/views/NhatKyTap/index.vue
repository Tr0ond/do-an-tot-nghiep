<template>
  <CaNhanLayout>
    <section class="nk">
      <RouterLink v-if="laPt" to="/pt/hoc-vien" class="nk-back">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Danh sách học viên
      </RouterLink>
      <header class="nk-heading">
        <div>
          <p class="nk-eyebrow">
            <i class="bi bi-fire" aria-hidden="true"></i> TỪNG BUỔI TẬP, TỪNG BƯỚC TIẾN
          </p>
          <h1>Lịch & nhật ký tập</h1>
          <p>
            {{
              laPt
                ? `Theo dõi tiến độ của ${meta.hoc_vien?.ho_ten || 'học viên'} và thêm nhận xét sau buổi tập.`
                : 'Lên lịch tự tập, ghi kết quả thực tế và theo dõi sự đều đặn của bạn.'
            }}
          </p>
        </div>
        <button
          class="btn btn-outline-secondary"
          :disabled="dangTai || dangLuu"
          @click="taiDanhSach"
        >
          <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i> Cập nhật
        </button>
      </header>

      <!-- Thông báo alert / status -->
      <div v-if="loi" class="nk-alert" role="alert">
        <div class="d-flex align-items-center gap-2 fw-bold">
          <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
          <span>Có lỗi xảy ra</span>
        </div>
        <p class="mb-0">{{ loi }}</p>
      </div>

      <div v-if="thanhCong" class="nk-success" role="status">
        <div class="d-flex align-items-center gap-2 fw-bold">
          <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
          <span>{{ thanhCong }}</span>
        </div>
      </div>

      <!-- Bộ lọc thời gian & trạng thái -->
      <form class="nk-filter nk-panel" @submit.prevent="taiDanhSach(1)">
        <label>
          Từ ngày
          <input
            v-model="boLoc.tu_ngay"
            type="date"
            required
            class="form-control"
            :disabled="dangTai"
          />
        </label>
        <label>
          Đến ngày
          <input
            v-model="boLoc.den_ngay"
            type="date"
            required
            class="form-control"
            :disabled="dangTai"
          />
        </label>
        <label>
          Trạng thái
          <select v-model="boLoc.trang_thai" class="form-select" :disabled="dangTai">
            <option value="">Tất cả buổi tập</option>
            <option v-for="(nhan, ma) in nhanTrangThai" :key="ma" :value="ma">{{ nhan }}</option>
          </select>
        </label>
        <button class="btn btn-primary" :disabled="dangTai">
          <i class="bi bi-funnel me-1" aria-hidden="true"></i> Xem kết quả
        </button>
      </form>

      <p v-if="dangTai" role="status" class="text-secondary py-3">
        <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i> Đang tải lịch và kết quả…
      </p>

      <template v-if="daTai && !dangTai">
        <!-- STITCH PROGRESS SUMMARY BAR & LEGEND (Screen 10) -->
        <div class="st-progress-bar-card mb-4 p-4 rounded-3 border bg-body-tertiary">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-4 flex-wrap">
              <div>
                <span class="small text-muted d-block">Tiến độ ghi nhận</span>
                <div class="fs-6 fw-bold text-body mt-1 d-flex align-items-center gap-2">
                  <span>{{ thongKe.so_buoi || 0 }} buổi hoàn thành</span>
                  <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">
                    Đạt chuẩn
                  </span>
                </div>
              </div>
              <div class="border-start ps-4 d-none d-sm-block" style="height: 36px"></div>
              <div>
                <span class="small text-muted d-block">Tổng hiệp & tải trọng</span>
                <div class="fs-6 fw-bold text-primary mt-1">
                  {{ so(thongKe.so_hiep || 0) }} hiệp · {{ so(thongKe.tong_khoi_luong_kg || 0) }} kg
                </div>
              </div>
            </div>
            <!-- Chú thích màu sắc (Color Legend) -->
            <div class="d-flex align-items-center gap-3 flex-wrap">
              <div class="d-flex align-items-center gap-1.5">
                <span class="st-kpi-dot primary"></span>
                <span class="small text-muted">Lịch PT (#2864D7)</span>
              </div>
              <div class="d-flex align-items-center gap-1.5">
                <span class="st-kpi-dot success"></span>
                <span class="small text-muted">Tự tập (#087F75)</span>
              </div>
              <div class="d-flex align-items-center gap-1.5">
                <span class="st-kpi-dot" style="background-color: #94a3b8"></span>
                <span class="small text-muted">Nghỉ phục hồi</span>
              </div>
            </div>
          </div>
        </div>

        <!-- KPI STAT CARDS -->
        <div class="nk-stats">
          <div class="nk-panel">
            <div class="nk-stat-icon">
              <i class="bi bi-calendar-check" aria-hidden="true"></i>
            </div>
            <span>Buổi hoàn thành</span>
            <strong>{{ thongKe.so_buoi || 0 }}</strong>
          </div>
          <div class="nk-panel">
            <div class="nk-stat-icon">
              <i class="bi bi-stack" aria-hidden="true"></i>
            </div>
            <span>Hiệp đã tập</span>
            <strong>{{ so(thongKe.so_hiep || 0) }}</strong>
          </div>
          <div class="nk-panel">
            <div class="nk-stat-icon">
              <i class="bi bi-repeat" aria-hidden="true"></i>
            </div>
            <span>Lần thực hiện</span>
            <strong>{{ so(thongKe.so_lan || 0) }}</strong>
          </div>
          <div class="nk-panel">
            <div class="nk-stat-icon">
              <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
            </div>
            <span>Tổng tạ × số lần</span>
            <strong>{{ so(thongKe.tong_khoi_luong_kg || 0) }} <small>kg</small></strong>
            <small>{{ thongKe.so_hiep_co_ta || 0 }} hiệp có ghi tạ</small>
          </div>
        </div>

        <div class="nk-grid">
          <div class="nk-main">
            <section class="st-calendar" aria-label="Lịch tự tập theo ngày">
              <div class="st-calendar-heading">
                <h2>Lịch tự tập</h2>
                <label
                  >Ngày bắt đầu tuần<input
                    v-model="tuanBatDau"
                    type="date"
                    class="form-control"
                    :disabled="dangTai"
                    @change="doiTuan"
                /></label>
              </div>
              <p class="small text-secondary">
                Lịch hiển thị các buổi trong trang dữ liệu hiện tại{{
                  meta.last_page > 1 ? ` (${meta.current_page}/${meta.last_page})` : ''
                }}.
              </p>
              <div class="st-week-grid">
                <div
                  v-for="n in cacNgayTrongTuan"
                  :key="n.ngay"
                  class="st-week-day"
                  :class="{ 'is-today': n.ngay === meta.hom_nay }"
                >
                  <header>
                    <span>{{ n.thu }}</span
                    ><strong>{{ n.nhan }}</strong>
                  </header>
                  <RouterLink
                    v-for="l in n.cacBuoi"
                    :key="l.id"
                    :to="`/${laPt ? 'pt' : 'khach-hang'}/lich-tap/${l.id}`"
                    class="st-calendar-event"
                    :class="l.trang_thai"
                    ><span>{{ nhanTrangThai[l.trang_thai] }}</span
                    ><strong>{{ l.ten_buoi || l.ten_ke_hoach || 'Buổi tự tập' }}</strong
                    ><small
                      >Ngày {{ l.ngay_thu }} · {{ l.so_bai_tap || l.so_bai || 0 }} bài</small
                    ></RouterLink
                  >
                  <span v-if="!n.cacBuoi.length" class="st-no-event">Không có lịch đã tải</span>
                </div>
              </div>
            </section>
            <!-- DANH SÁCH BUỔI TẬP -->
            <section class="nk-panel">
              <div class="nk-section-heading">
                <div>
                  <p class="nk-eyebrow">
                    <i class="bi bi-activity" aria-hidden="true"></i> SỰ ĐỀU ĐẶN
                  </p>
                  <h2>Buổi tập của bạn</h2>
                </div>
                <span>{{ meta.total || 0 }} buổi trong khoảng chọn</span>
              </div>

              <p v-if="!danhSach.length" class="nk-empty">
                Chưa có buổi tập trong khoảng này. Chọn ngày từ giáo án đang dùng để bắt đầu.
              </p>

              <RouterLink
                v-for="l in danhSach"
                :key="l.id"
                :to="`/${laPt ? 'pt' : 'khach-hang'}/lich-tap/${l.id}`"
                class="nk-schedule"
              >
                <div class="nk-date">
                  <strong>{{ l.ngay_tap.slice(8) }}</strong>
                  <small>{{ l.ngay_tap.slice(5, 7) }}/{{ l.ngay_tap.slice(0, 4) }}</small>
                </div>
                <div class="nk-schedule-content">
                  <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="nk-badge" :class="l.trang_thai">
                      <i
                        v-if="l.trang_thai === 'HOAN_THANH'"
                        class="bi bi-check-circle-fill"
                        aria-hidden="true"
                      ></i>
                      <i
                        v-else-if="l.trang_thai === 'DANG_TAP'"
                        class="bi bi-play-circle-fill"
                        aria-hidden="true"
                      ></i>
                      <i
                        v-else-if="l.trang_thai === 'DA_LEN_LICH'"
                        class="bi bi-calendar3"
                        aria-hidden="true"
                      ></i>
                      <i v-else class="bi bi-x-circle" aria-hidden="true"></i>
                      {{ nhanTrangThai[l.trang_thai] }}
                    </span>
                    <span class="small text-secondary">
                      <i class="bi bi-clock me-1" aria-hidden="true"></i>{{ ngay(l.ngay_tap) }}
                    </span>
                  </div>
                  <h3>{{ l.ten_ke_hoach }}</h3>
                  <p>
                    <span
                      ><i class="bi bi-calendar-event me-1" aria-hidden="true"></i>Ngày
                      {{ l.ngay_thu }}</span
                    >
                    <span>·</span>
                    <span
                      ><i
                        :class="l.nguon_tao === 'PT' ? 'bi bi-person-badge' : 'bi bi-person'"
                        class="me-1"
                        aria-hidden="true"
                      ></i
                      >{{ l.nguon_tao === 'PT' ? 'PT giao' : 'Tự tạo' }}</span
                    >
                  </p>
                </div>
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
              </RouterLink>

              <!-- Phân trang lịch -->
              <nav
                v-if="meta.last_page > 1"
                class="nk-pagination"
                aria-label="Phân trang lịch tự tập"
              >
                <button
                  class="btn btn-outline-secondary btn-sm"
                  :disabled="meta.current_page <= 1"
                  @click="taiDanhSach(meta.current_page - 1)"
                >
                  <i class="bi bi-chevron-left" aria-hidden="true"></i> Trước
                </button>
                <span>{{ meta.current_page }} / {{ meta.last_page }}</span>
                <button
                  class="btn btn-outline-secondary btn-sm"
                  :disabled="meta.current_page >= meta.last_page"
                  @click="taiDanhSach(meta.current_page + 1)"
                >
                  Sau <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
              </nav>
            </section>

            <!-- BIỂU ĐỒ MỨC TẠ THEO BÀI TẬP -->
            <section class="nk-panel">
              <p class="nk-eyebrow">
                <i class="bi bi-graph-up" aria-hidden="true"></i> TIẾN ĐỘ CÓ SỐ LIỆU
              </p>
              <h2>Mức tạ theo bài tập</h2>
              <p class="nk-muted mb-3">
                Mức tạ cao nhất mỗi ngày từ những buổi đã hoàn thành. So sánh cùng một bài tập để
                theo dõi sự tiến bộ sức mạnh.
              </p>

              <p v-if="!thongKe.theo_bai?.length" class="nk-empty">
                Khi bạn hoàn thành buổi tập có ghi mức tạ, biểu đồ tiến độ sẽ xuất hiện tại đây.
              </p>

              <template v-else>
                <label class="nk-chart-select">
                  Chọn bài tập theo dõi
                  <select v-model="baiChon" class="form-select">
                    <option v-for="b in thongKe.theo_bai" :key="b.bai_tap_id" :value="b.bai_tap_id">
                      {{ b.ten_bai_tap }}
                    </option>
                  </select>
                </label>

                <div
                  class="nk-chart"
                  role="img"
                  :aria-label="`Mức tạ theo ngày của ${tienDo?.ten_bai_tap || ''}; số liệu chi tiết ở bảng bên dưới.`"
                >
                  <svg v-if="cacMoc.length" viewBox="0 0 600 180" aria-hidden="true">
                    <defs>
                      <linearGradient id="nkAreaGradient" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="var(--mau-chinh)" stop-opacity="0.35" />
                        <stop offset="100%" stop-color="var(--mau-chinh)" stop-opacity="0.0" />
                      </linearGradient>
                    </defs>
                    <line x1="24" y1="30" x2="576" y2="30" class="nk-axis" />
                    <line x1="24" y1="92" x2="576" y2="92" class="nk-axis" />
                    <line x1="24" y1="155" x2="576" y2="155" class="nk-axis" />
                    <polygon v-if="cacMoc.length > 1" :points="diemVungBieuDo" class="nk-area" />
                    <polyline :points="diemBieuDo" class="nk-line" />
                    <circle
                      v-for="(m, i) in cacMoc"
                      :key="m.ngay_tap"
                      :cx="toaDoX(i)"
                      :cy="toaDoY(m.muc_ta_kg)"
                      :r="m.muc_ta_kg === tienDo?.muc_ta_cao_nhat ? 6 : 4"
                      class="nk-point"
                    >
                      <title>{{ ngay(m.ngay_tap) }}: {{ so(m.muc_ta_kg) }} kg</title>
                    </circle>
                  </svg>
                  <div v-if="cacMoc.length" class="nk-chart-dates" aria-hidden="true">
                    <span>{{ ngay(cacMoc[0].ngay_tap) }}</span>
                    <span v-if="cacMoc.length > 1">{{
                      ngay(cacMoc[cacMoc.length - 1].ngay_tap)
                    }}</span>
                  </div>
                </div>

                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                  <span class="nk-pr-badge">
                    <i class="bi bi-trophy-fill" aria-hidden="true"></i> Mức tạ cao nhất (PR):
                    <strong>{{ so(tienDo?.muc_ta_cao_nhat || 0) }} kg</strong>
                  </span>
                  <span class="text-secondary small">Hiển thị tối đa 10 bài có ghi tạ</span>
                </div>

                <details class="nk-guidance">
                  <summary>
                    <i class="bi bi-table me-1" aria-hidden="true"></i> Xem số liệu từng ngày
                  </summary>
                  <table class="nk-data-table">
                    <thead>
                      <tr>
                        <th>Ngày tập</th>
                        <th>Tạ cao nhất</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="m in cacMoc" :key="m.ngay_tap">
                        <td>{{ ngay(m.ngay_tap) }}</td>
                        <td>
                          <strong>{{ so(m.muc_ta_kg) }} kg</strong>
                          <span
                            v-if="m.muc_ta_kg === tienDo?.muc_ta_cao_nhat"
                            class="badge bg-warning-subtle text-warning ms-2"
                          >
                            PR
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </details>
              </template>
            </section>
          </div>

          <!-- ASIDE LÊN LỊCH TỰ TẬP -->
          <aside class="nk-panel nk-create">
            <p class="nk-eyebrow">
              <i class="bi bi-calendar-plus" aria-hidden="true"></i> DÀNH THỜI GIAN CHO BẢN THÂN
            </p>
            <h2>Lên lịch tự tập</h2>

            <template v-if="meta.giao_an_dang_dung">
              <div class="nk-plan">
                <small>Giáo án đang áp dụng</small>
                <strong>{{ meta.giao_an_dang_dung.ten_ke_hoach }}</strong>
                <span>
                  <i
                    :class="
                      meta.giao_an_dang_dung.nguon_tao === 'PT'
                        ? 'bi bi-person-badge'
                        : 'bi bi-person'
                    "
                    class="me-1"
                    aria-hidden="true"
                  ></i>
                  {{ meta.giao_an_dang_dung.nguon_tao === 'PT' ? 'PT giao' : 'KH tự tạo' }}
                </span>
              </div>

              <form @submit.prevent="taoLich">
                <label>
                  Ngày thực tế
                  <input
                    v-model="lichMoi.ngay_tap"
                    type="date"
                    :min="meta.hom_nay"
                    required
                    class="form-control"
                    :disabled="dangLuu"
                  />
                </label>
                <label>
                  Ngày trong giáo án
                  <select
                    v-model="lichMoi.ngay_thu"
                    class="form-select"
                    required
                    :disabled="dangLuu"
                  >
                    <option
                      v-for="n in meta.giao_an_dang_dung.cac_ngay"
                      :key="n.ngay_thu"
                      :value="n.ngay_thu"
                    >
                      Ngày {{ n.ngay_thu }} · {{ n.so_bai }} bài
                    </option>
                  </select>
                </label>
                <ul class="nk-preview">
                  <li v-for="(ten, i) in ngayTrongGiaoAn?.ten_bai || []" :key="i">{{ ten }}</li>
                </ul>
                <button class="btn btn-primary w-100" :disabled="dangLuu">
                  <i class="bi bi-calendar-plus me-1" aria-hidden="true"></i>
                  {{ dangLuu ? 'Đang lên lịch…' : 'Thêm buổi tự tập' }}
                </button>
              </form>
            </template>

            <template v-else>
              <p class="nk-empty">
                {{
                  laPt
                    ? 'Học viên chưa áp dụng giáo án. Hãy gửi giáo án để học viên xác nhận.'
                    : 'Bạn cần áp dụng một giáo án trước khi lên lịch.'
                }}
              </p>
              <RouterLink
                :to="
                  laPt ? `/pt/hoc-vien/${$route.params.khachId}/ke-hoach` : '/khach-hang/ke-hoach'
                "
                class="btn btn-outline-secondary w-100"
              >
                <i class="bi bi-journal-text me-1" aria-hidden="true"></i> Xem giáo án
              </RouterLink>
            </template>

            <p class="nk-footnote">
              <i class="bi bi-info-circle" aria-hidden="true"></i>
              <span>
                Tự tập hoàn toàn không trừ lượt PT. Đổi hoặc ngừng giáo án vẫn bảo toàn các lịch đã
                tạo và nhật ký cũ.
              </span>
            </p>
          </aside>
        </div>
      </template>
    </section>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../layouts/CaNhanLayout.vue'
import nhatKyTapService from '../../services/nhatKyTapService'
import { khoangMacDinh, nhanTrangThaiTap } from '../../utils/nhatKyTap'
import '../../assets/nhatKyTap.css'

export default {
  name: 'LichVaNhatKyTap',
  components: { CaNhanLayout },
  data() {
    return {
      danhSach: [],
      meta: {},
      boLoc: { ...khoangMacDinh(), trang_thai: '' },
      lichMoi: { ngay_tap: '', ngay_thu: 1 },
      dangTai: false,
      dangLuu: false,
      daTai: false,
      tuanBatDau: '',
      loi: '',
      thanhCong: '',
      baiChon: null,
      nhanTrangThai: nhanTrangThaiTap,
      maTao: null,
      noiDungTao: null,
      lanTai: 0,
      boHuy: null,
    }
  },
  computed: {
    cacNgayTrongTuan() {
      const moc = new Date(`${this.tuanBatDau}T00:00:00Z`)
      if (!Number.isFinite(moc.getTime())) return []
      return Array.from({ length: 7 }, (_, i) => {
        const ngay = new Date(moc)
        ngay.setUTCDate(ngay.getUTCDate() + i)
        const ma = ngay.toISOString().slice(0, 10)
        return {
          ngay: ma,
          thu: ['CN', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'][ngay.getUTCDay()],
          nhan: ma.slice(8) + '/' + ma.slice(5, 7),
          cacBuoi: this.danhSach.filter((l) => l.ngay_tap === ma),
        }
      })
    },
    laPt() {
      return this.$route.meta.vaiTro === 'HUAN_LUYEN_VIEN'
    },
    thongKe() {
      return this.meta.thong_ke || {}
    },
    ngayTrongGiaoAn() {
      return this.meta.giao_an_dang_dung?.cac_ngay.find(
        (n) => n.ngay_thu === Number(this.lichMoi.ngay_thu),
      )
    },
    tienDo() {
      return this.thongKe.theo_bai?.find((b) => b.bai_tap_id === this.baiChon)
    },
    cacMoc() {
      return this.tienDo?.cac_moc || []
    },
    diemBieuDo() {
      return this.cacMoc.map((m, i) => `${this.toaDoX(i)},${this.toaDoY(m.muc_ta_kg)}`).join(' ')
    },
    diemVungBieuDo() {
      if (this.cacMoc.length <= 1) return ''
      const dau = `${this.toaDoX(0)},155`
      const cuoi = `${this.toaDoX(this.cacMoc.length - 1)},155`
      return `${dau} ${this.diemBieuDo} ${cuoi}`
    },
  },
  watch: {
    '$route.params.khachId'() {
      this.maTao = null
      this.noiDungTao = null
      this.thanhCong = ''
      this.taiDanhSach()
    },
  },
  mounted() {
    this.taiDanhSach()
  },
  beforeUnmount() {
    this.lanTai++
    this.boHuy?.abort()
  },
  methods: {
    doiTuan() {
      const moc = new Date(`${this.tuanBatDau}T00:00:00Z`)
      if (!Number.isFinite(moc.getTime())) return
      moc.setUTCDate(moc.getUTCDate() + 6)
      this.boLoc.tu_ngay = this.tuanBatDau
      this.boLoc.den_ngay = moc.toISOString().slice(0, 10)
      this.taiDanhSach(1)
    },
    ngay(v) {
      return v.split('-').reverse().join('/')
    },
    so(v) {
      return Number(v).toLocaleString('vi-VN', { maximumFractionDigits: 2 })
    },
    toaDoX(i) {
      return this.cacMoc.length <= 1 ? 300 : 24 + (i * 552) / (this.cacMoc.length - 1)
    },
    toaDoY(v) {
      const max = Math.max(1, ...this.cacMoc.map((m) => Number(m.muc_ta_kg)))
      return 155 - (Number(v) / max) * 125
    },
    async taiDanhSach(page = 1) {
      if (typeof page !== 'number') page = 1
      const lan = ++this.lanTai
      this.boHuy?.abort()
      this.boHuy = new AbortController()
      this.dangTai = true
      this.loi = ''
      try {
        const r = await nhatKyTapService.taiDanhSach(
          this.laPt ? this.$route.params.khachId : null,
          {
            tu_ngay: this.boLoc.tu_ngay,
            den_ngay: this.boLoc.den_ngay,
            ...(this.boLoc.trang_thai ? { trang_thai: this.boLoc.trang_thai } : {}),
            page,
          },
          this.boHuy.signal,
        )
        if (lan !== this.lanTai) return
        this.danhSach = r.data
        this.meta = r.meta
        this.tuanBatDau ||= r.meta.hom_nay || this.boLoc.tu_ngay
        this.daTai = true
        this.lichMoi.ngay_tap ||= r.meta.hom_nay
        if (
          !r.meta.giao_an_dang_dung?.cac_ngay.some(
            (n) => n.ngay_thu === Number(this.lichMoi.ngay_thu),
          )
        )
          this.lichMoi.ngay_thu = r.meta.giao_an_dang_dung?.cac_ngay[0]?.ngay_thu || 1
        if (!this.thongKe.theo_bai?.some((b) => b.bai_tap_id === this.baiChon))
          this.baiChon = this.thongKe.theo_bai?.[0]?.bai_tap_id || null
      } catch (e) {
        if (lan === this.lanTai && e.code !== 'ERR_CANCELED') {
          this.loi = e.response?.data?.message || 'Không tải được lịch tự tập.'
          this.daTai = false
        }
      } finally {
        if (lan === this.lanTai) this.dangTai = false
      }
    },
    async taoLich() {
      if (this.dangLuu || !this.meta.giao_an_dang_dung) return
      const noiDung = {
        ke_hoach_tap_id: this.meta.giao_an_dang_dung.id,
        ngay_thu: Number(this.lichMoi.ngay_thu),
        ngay_tap: this.lichMoi.ngay_tap,
      }
      const chuoi = JSON.stringify(noiDung)
      if (chuoi !== this.noiDungTao) {
        this.maTao = crypto.randomUUID()
        this.noiDungTao = chuoi
      }
      this.dangLuu = true
      this.loi = ''
      this.thanhCong = ''
      try {
        const r = await nhatKyTapService.tao(this.laPt ? this.$route.params.khachId : null, {
          ...noiDung,
          client_request_id: this.maTao,
        })
        this.maTao = null
        this.noiDungTao = null
        await this.$router.push(`/${this.laPt ? 'pt' : 'khach-hang'}/lich-tap/${r.data.id}`)
      } catch (e) {
        this.loi =
          Object.values(e.response?.data?.errors || {})
            .flat()
            .join(' ') ||
          e.response?.data?.message ||
          'Chưa tạo được lịch. Bạn có thể thử lại.'
      } finally {
        this.dangLuu = false
      }
    },
  },
}
</script>
