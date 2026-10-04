<template>
  <CaNhanLayout>
    <div class="m04-page">
      <!-- Tiêu đề trang và nút điều hướng -->
      <div class="m04-heading">
        <div>
          <span class="m04-kicker">
            <i class="bi bi-clock-history me-1" aria-hidden="true"></i>
            Lịch huấn luyện · 60 phút / buổi
          </span>
          <h1 class="h2 fw-bold">{{ laPT ? 'Khung giờ của tôi' : 'Đặt lịch với PT' }}</h1>
          <p>
            {{
              laPT
                ? 'Mở giờ rảnh để học viên gửi yêu cầu tập luyện.'
                : 'Chọn giờ phù hợp với huấn luyện viên đang phụ trách bạn.'
            }}
          </p>
        </div>
        <RouterLink :to="`/${khuVuc}/lich-hen`" class="btn btn-outline-secondary">
          <i class="bi bi-calendar-check me-2" aria-hidden="true"></i>Lịch hẹn
        </RouterLink>
      </div>

      <!-- Thông báo thành công và lỗi -->
      <div
        v-if="thongBao"
        class="alert alert-success d-flex align-items-center gap-2 rounded-3"
        role="status"
      >
        <i class="bi bi-check-circle-fill text-success flex-shrink-0" aria-hidden="true"></i>
        <span>{{ thongBao }}</span>
      </div>
      <div
        v-if="loi"
        class="alert alert-warning d-flex align-items-center gap-2 rounded-3"
        role="alert"
      >
        <i
          class="bi bi-exclamation-triangle-fill text-warning flex-shrink-0"
          aria-hidden="true"
        ></i>
        <span>{{ loi }}</span>
      </div>

      <!-- Thống kê trạng thái slot trong ngày (Stitch Screen 20 Slot Metrics) -->
      <div v-if="!loi && ds.length" class="st-kpi-ribbon">
        <div class="st-kpi-chip">
          <span>Tổng số slot:</span>
          <strong>{{ ds.length }} ca</strong>
        </div>
        <div class="st-kpi-chip">
          <span class="st-kpi-dot success"></span>
          <span>Còn trống:</span>
          <strong>{{ ds.filter((s) => s.trang_thai === 'MO' && !s.dang_giu).length }} slot</strong>
        </div>
        <div class="st-kpi-chip">
          <span class="st-kpi-dot warning"></span>
          <span>Đã có lịch:</span>
          <strong>{{ ds.filter((s) => s.dang_giu).length }} slot</strong>
        </div>
      </div>

      <!-- Bố cục 2 cột chính -->
      <div class="m04-layout">
        <!-- Cột trái: Chọn ngày và danh sách khung giờ -->
        <section class="m04-panel" aria-label="Khung giờ theo ngày">
          <!-- Bộ chọn ngày -->
          <form class="m04-toolbar" @submit.prevent="taiDuLieu(1)">
            <div>
              <label for="ngay-khung">
                <i class="bi bi-calendar-event me-1 text-muted" aria-hidden="true"></i>
                Ngày tập · giờ Việt Nam
              </label>
              <input
                id="ngay-khung"
                v-model="ngay"
                class="form-control"
                type="date"
                required
                :disabled="dangLuu"
                @change="doiNgay"
              />
            </div>
            <button class="btn btn-outline-secondary" :disabled="dangTai || dangLuu">
              <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>
              {{ dangTai ? 'Đang tải…' : 'Tải lại' }}
            </button>
          </form>

          <!-- Thẻ tóm tắt thông tin PT phụ trách (dành cho Khách hàng - Screen 20) -->
          <div v-if="meta.pt" class="pt-companion-card mb-4">
            <div class="d-flex align-items-center gap-3">
              <div class="pt-avatar-circle">
                {{ meta.pt.ho_ten ? meta.pt.ho_ten.charAt(0).toUpperCase() : 'P' }}
              </div>
              <div>
                <strong class="d-block text-body fs-6">{{ meta.pt.ho_ten }}</strong>
                <span class="d-block text-muted small">
                  {{ meta.pt.chuyen_mon || 'Huấn luyện viên cá nhân' }}
                </span>
              </div>
            </div>
            <span
              class="m04-state DA_XAC_NHAN font-monospace fw-bold"
            >
              {{ meta.so_buoi_con_lai }} buổi còn lại
            </span>
          </div>

          <!-- Các trạng thái danh sách giờ -->
          <div v-if="dangTai" class="m04-empty" role="status">
            <div class="spinner-border text-success mb-3" role="status"></div>
            <p class="mb-0">Đang tải giờ tập…</p>
          </div>
          <div v-else-if="loi" class="m04-empty">
            <i class="bi bi-exclamation-circle text-danger" aria-hidden="true"></i>
            <h2 class="h5">Chưa tải được khung giờ</h2>
            <p>Vui lòng tải lại để xem giờ tập còn trống.</p>
          </div>
          <div v-else-if="meta.ly_do" class="m04-note alert-warning-theme">
            <i class="bi bi-info-circle me-1 text-amber" aria-hidden="true"></i>
            {{ meta.ly_do }}
            <RouterLink to="/khach-hang/goi-cua-toi" class="fw-semibold"
              >Xem gói của tôi</RouterLink
            >
          </div>
          <div v-else-if="!ds.length" class="m04-empty">
            <i class="bi bi-calendar2-week" aria-hidden="true"></i>
            <h2 class="h5">
              {{ laPT ? 'Chưa có khung giờ trong ngày này' : 'Chưa có giờ phù hợp trong ngày này' }}
            </h2>
            <p>
              {{
                laPT
                  ? 'Thêm giờ bắt đầu ở bên cạnh để mở lịch.'
                  : 'Hãy chọn ngày khác. Chỉ hiện giờ còn trống, trước ít nhất 4 giờ và kết thúc trong hạn gói.'
              }}
            </p>
          </div>

          <!-- Lưới các khung giờ trong ngày -->
          <div v-else class="m04-slots">
            <template v-for="slot in ds" :key="slot.id">
              <!-- Hiển thị cho PT -->
              <article v-if="laPT" class="m04-slot">
                <strong
                  >{{ gioVietNam(slot.bat_dau_luc) }} – {{ gioVietNam(slot.ket_thuc_luc) }}</strong
                >
                <small class="mb-3 d-flex align-items-center gap-1">
                  <span
                    class="status-dot"
                    :class="{
                      'bg-warning': slot.dang_giu,
                      'bg-success': !slot.dang_giu && slot.trang_thai === 'MO',
                      'bg-secondary': !slot.dang_giu && slot.trang_thai !== 'MO',
                    }"
                  ></span>
                  {{
                    slot.dang_giu
                      ? 'Đang có lịch hẹn'
                      : slot.trang_thai === 'MO'
                        ? 'Đang mở'
                        : 'Đã đóng'
                  }}
                </small>
                <button
                  v-if="slot.co_the_doi"
                  class="btn btn-outline-secondary w-100 btn-sm"
                  :disabled="dangLuu"
                  @click="doiKhung(slot)"
                >
                  {{ slot.trang_thai === 'MO' ? 'Đóng giờ' : 'Mở lại' }}
                </button>
              </article>

              <!-- Hiển thị cho Khách hàng chọn giờ -->
              <button
                v-else
                class="m04-slot"
                :class="{ chon: chon?.id === slot.id }"
                :aria-pressed="chon?.id === slot.id"
                :disabled="dangLuu"
                @click="chonGio(slot)"
              >
                <strong
                  >{{ gioVietNam(slot.bat_dau_luc) }} – {{ gioVietNam(slot.ket_thuc_luc) }}</strong
                >
                <small class="d-flex align-items-center gap-1">
                  <i
                    v-if="chon?.id === slot.id"
                    class="bi bi-check-circle-fill text-emerald"
                    aria-hidden="true"
                  ></i>
                  <span v-else class="status-dot bg-success"></span>
                  {{ chon?.id === slot.id ? 'Đã chọn giờ này' : 'Còn trống · Chọn giờ' }}
                </small>
              </button>
            </template>
          </div>

          <!-- Phân trang khung giờ -->
          <nav
            v-if="meta.last_page > 1"
            class="m04-actions justify-content-center mt-4"
            aria-label="Trang khung giờ"
          >
            <button
              class="btn btn-outline-secondary btn-sm"
              :disabled="page <= 1 || dangTai || dangLuu"
              @click="taiDuLieu(page - 1)"
            >
              <i class="bi bi-chevron-left me-1" aria-hidden="true"></i>Trước
            </button>
            <span
              class="badge bg-light text-muted border px-3 py-2 align-self-center font-monospace"
              >{{ page }} / {{ meta.last_page }}</span
            >
            <button
              class="btn btn-outline-secondary btn-sm"
              :disabled="page >= meta.last_page || dangTai || dangLuu"
              @click="taiDuLieu(page + 1)"
            >
              Sau<i class="bi bi-chevron-right ms-1" aria-hidden="true"></i>
            </button>
          </nav>
        </section>

        <!-- Cột phải: Thao tác mở giờ (PT) hoặc Đặt lịch (KH) -->
        <aside class="m04-panel">
          <!-- Khung PT: Mở một khung giờ rảnh -->
          <template v-if="laPT">
            <h2 class="h5 fw-bold d-flex align-items-center gap-2 mb-2">
              <i class="bi bi-calendar-plus text-emerald" aria-hidden="true"></i>
              Mở một khung giờ
            </h2>
            <p class="text-muted small mb-3">
              Mỗi khung giờ kéo dài 60 phút. Giờ đã có lịch hẹn không thể đóng.
            </p>
            <form @submit.prevent="taoKhung">
              <label class="form-label fw-semibold" for="gio-mo">
                <i class="bi bi-clock me-1 text-muted" aria-hidden="true"></i>
                Giờ bắt đầu · {{ ngay }}
              </label>
              <input
                id="gio-mo"
                v-model="gio"
                type="time"
                class="form-control mb-3"
                required
                :disabled="dangLuu"
              />
              <button class="btn btn-primary w-100" :disabled="dangLuu || dangTai">
                <span
                  v-if="dangLuu"
                  class="spinner-border spinner-border-sm me-1"
                  role="status"
                  aria-hidden="true"
                ></span>
                <i v-else class="bi bi-plus-circle me-1" aria-hidden="true"></i>
                {{ dangLuu ? 'Đang lưu…' : 'Mở giờ rảnh' }}
              </button>
            </form>
          </template>

          <!-- Khung KH: Gửi yêu cầu đặt lịch -->
          <template v-else>
            <h2 class="h5 fw-bold d-flex align-items-center gap-2 mb-2">
              <i class="bi bi-calendar-check text-emerald" aria-hidden="true"></i>
              Yêu cầu đặt lịch
            </h2>
            <div v-if="chon" class="p-3 bg-body-secondary rounded-3 border mb-3">
              <div class="small text-muted text-uppercase fw-semibold mb-1">
                <i class="bi bi-check-circle-fill text-emerald me-1" aria-hidden="true"></i>
                Khung giờ đã chọn
              </div>
              <p class="fw-bold fs-6 mb-1 text-body">
                {{ dinhDangLuc(chon.bat_dau_luc) }}
              </p>
              <span class="d-block text-muted small">
                Đến {{ gioVietNam(chon.ket_thuc_luc) }} · 60 phút
              </span>
            </div>
            <p v-else class="text-muted small mb-3">Chọn một giờ còn trống để tiếp tục.</p>
            <button
              class="btn btn-primary w-100"
              :disabled="!chon || dangLuu || dangTai"
              @click="datLich"
            >
              <span
                v-if="dangLuu"
                class="spinner-border spinner-border-sm me-1"
                role="status"
                aria-hidden="true"
              ></span>
              <i v-else class="bi bi-send-check me-1" aria-hidden="true"></i>
              {{ dangLuu ? 'Đang gửi…' : 'Gửi yêu cầu cho PT' }}
            </button>
          </template>

          <!-- Chính sách đặt & hủy lịch -->
          <div class="m04-note mt-4 small">
            <i class="bi bi-shield-check text-emerald me-1" aria-hidden="true"></i>
            Đặt trước ít nhất 4 giờ. PT có tối đa 2 giờ để xác nhận. KH được hủy trước ít nhất 2
            giờ; chỉ buổi hoàn thành mới trừ lượt.
          </div>
        </aside>
      </div>
    </div>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../../layouts/CaNhanLayout.vue'
import lichHenService from '../../../services/lichHenService'
import { useXacThucStore } from '../../../stores/xacThuc'
import { ngayVietNam, gioVietNam, tuGioVietNam, khuVucVaiTro } from '../../../utils/lichHen'
import { dinhDangLuc } from '../../../utils/donHang'
import { layLoiApi } from '../../../utils/loiApi'
import '../../../assets/lichHen.css'

export default {
  name: 'KhungGioPage',
  components: { CaNhanLayout },
  data() {
    return {
      ngay: ngayVietNam(),
      gio: '08:00',
      ds: [],
      meta: {},
      page: 1,
      chon: null,
      ma: null,
      dangTai: false,
      dangLuu: false,
      loi: '',
      thongBao: '',
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
    laPT() {
      return this.khuVuc === 'pt'
    },
  },
  watch: {
    taiKhoan() {
      this.chon = null
      this.ma = null
      this.thongBao = ''
      this.taiDuLieu(1)
    },
  },
  mounted() {
    this.taiDuLieu(1)
  },
  beforeUnmount() {
    this.daDong = true
    this.lanTai++
    this.boHuy?.abort()
    clearTimeout(this.timer)
  },
  methods: {
    gioVietNam,
    dinhDangLuc,
    doiNgay() {
      this.chon = null
      this.ma = null
      this.thongBao = ''
      this.taiDuLieu(1)
    },
    chonGio(slot) {
      if (this.dangLuu) return
      if (this.chon?.id !== slot.id) this.ma = crypto.randomUUID()
      this.chon = slot
      this.loi = ''
    },
    async taiDuLieu(page = this.page) {
      this.boHuy?.abort()
      clearTimeout(this.timer)
      const lan = ++this.lanTai
      this.boHuy = new AbortController()
      const signal = this.boHuy.signal
      this.ds = []
      this.meta = {}
      this.loi = ''
      this.dangTai = false
      if (!['pt', 'khach-hang'].includes(this.khuVuc) || !this.ngay) return
      this.dangTai = true
      try {
        const r = await lichHenService.taiKhung(this.khuVuc, { ngay: this.ngay, page }, signal)
        if (this.daDong || lan !== this.lanTai) return
        this.ds = r.data
        this.meta = r.meta
        this.page = r.meta.current_page
        if (this.chon && !this.ds.some((s) => s.id === this.chon.id)) {
          this.chon = null
          this.ma = null
        }
      } catch (e) {
        if (!signal.aborted && !this.daDong && lan === this.lanTai) this.loi = layLoiApi(e).thongBao
      } finally {
        if (!this.daDong && lan === this.lanTai) this.dangTai = false
      }
    },
    async taoKhung() {
      if (this.dangLuu || this.dangTai) return
      const luc = tuGioVietNam(this.ngay, this.gio)
      if (!luc) {
        this.loi = 'Hãy nhập ngày và giờ hợp lệ.'
        return
      }
      await this.luu(() => lichHenService.taoKhung({ bat_dau_luc: luc }))
    },
    async doiKhung(slot) {
      if (this.dangLuu) return
      await this.luu(() =>
        lichHenService.doiKhung(slot.id, slot.trang_thai === 'MO' ? 'DONG' : 'MO'),
      )
    },
    async datLich() {
      if (this.dangLuu || this.dangTai || !this.chon || !this.ma) return
      const idNguoi = this.taiKhoan?.id
      this.dangLuu = true
      this.loi = ''
      this.thongBao = ''
      try {
        const r = await lichHenService.datLich({
          khung_gio_id: this.chon.id,
          client_request_id: this.ma,
        })
        if (!this.daDong && this.taiKhoan?.id === idNguoi)
          await this.$router.push(`/khach-hang/lich-hen/${r.data.id}`)
      } catch (e) {
        if (!this.daDong && this.taiKhoan?.id === idNguoi) this.loi = layLoiApi(e).thongBao
      } finally {
        if (!this.daDong) this.dangLuu = false
      }
    },
    async luu(goi) {
      if (this.dangLuu) return
      const idNguoi = this.taiKhoan?.id
      this.dangLuu = true
      this.loi = ''
      this.thongBao = ''
      try {
        const r = await goi()
        if (!this.daDong && this.taiKhoan?.id === idNguoi) {
          await this.taiDuLieu()
          this.thongBao = r.message
        }
      } catch (e) {
        if (!this.daDong && this.taiKhoan?.id === idNguoi) this.loi = layLoiApi(e).thongBao
      } finally {
        if (!this.daDong) this.dangLuu = false
      }
    },
  },
}
</script>
