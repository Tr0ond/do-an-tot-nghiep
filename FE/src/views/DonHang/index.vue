<template>
  <CaNhanLayout>
    <!-- Tiêu đề trang đơn hàng -->
    <div class="m03-heading">
      <div>
        <span class="m03-kicker">
          <i
            :class="laAdmin ? 'bi bi-shield-lock-fill' : 'bi bi-bag-check-fill'"
            class="me-1"
            aria-hidden="true"
          ></i>
          {{ laAdmin ? 'Quản trị dịch vụ' : 'Hành trình của bạn' }}
        </span>
        <h1 class="h2 fw-bold mb-1">{{ $route.params.id ? 'Chi tiết đơn hàng' : 'Đơn hàng' }}</h1>
        <p class="text-muted small mb-0">
          {{
            laAdmin
              ? 'Theo dõi đơn mua gói và các khoản thu cần đối soát.'
              : 'Xem đơn đã đặt và tiếp tục thanh toán gói tập.'
          }}
        </p>
      </div>
      <RouterLink class="btn btn-outline-secondary" :to="$route.params.id ? goc : '/goi-tap'">
        <i
          :class="$route.params.id ? 'bi bi-arrow-left' : 'bi bi-box-seam'"
          class="me-1"
          aria-hidden="true"
        ></i>
        <span>{{ $route.params.id ? 'Danh sách đơn' : 'Xem gói tập' }}</span>
      </RouterLink>
    </div>

    <!-- Thông báo cảnh báo / lỗi -->
    <div
      v-if="loi"
      class="alert alert-warning d-flex align-items-center justify-content-between gap-2 p-3 mb-4 rounded-3"
      role="alert"
    >
      <div class="d-flex align-items-center gap-2">
        <i
          class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0"
          aria-hidden="true"
        ></i>
        <span class="small fw-semibold">{{ loi }}</span>
      </div>
      <button v-if="!dangLuu" class="btn btn-sm btn-outline-dark" @click="taiDuLieu">
        <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Tải lại
      </button>
    </div>

    <!-- Thông báo thành công -->
    <div
      v-if="thongBao"
      class="alert alert-success d-flex align-items-center gap-2 p-3 mb-4 rounded-3"
      role="status"
    >
      <i class="bi bi-check-circle-fill fs-5 text-success flex-shrink-0" aria-hidden="true"></i>
      <span class="small fw-semibold">{{ thongBao }}</span>
    </div>

    <!-- Trạng thái đang tải -->
    <div v-if="dangTai" class="p-5 text-center text-muted" role="status">
      <div class="spinner-border text-success mb-3" role="status"></div>
      <p class="small mb-0">Đang tải đơn hàng…</p>
    </div>

    <!-- MÀN HÌNH CHI TIẾT ĐƠN HÀNG -->
    <template v-else-if="don">
      <div class="m03-grid">
        <!-- Cột 1: Thông tin gói & Quyền lợi -->
        <section class="m03-panel shadow-sm">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <span class="m03-state" :class="don.trang_thai">
              <span class="status-dot"></span>
              <span>{{ nhanTrangThai[don.trang_thai] }}</span>
            </span>
            <span class="badge bg-light text-muted border font-monospace small">
              #{{ don.ma_don_payos }}
            </span>
          </div>

          <h2 class="h4 fw-bold mb-1 text-dark">{{ don.ten_goi }}</h2>
          <p class="text-muted small mb-3">
            Mã đơn #{{ don.ma_don_payos }}
            <span v-if="don.khach_hang" class="ms-1"
              >· Khách hàng: <strong>{{ don.khach_hang.ho_ten }}</strong></span
            >
          </p>

          <div class="package-facts-wrapper my-3 p-3 bg-light rounded-3 border">
            <QuyenLoiGoiTap :goi="don" />
          </div>

          <dl class="m03-facts">
            <div>
              <dt><i class="bi bi-clock me-1 text-muted" aria-hidden="true"></i>Ngày đặt</dt>
              <dd>{{ dinhDangLuc(don.created_at) }}</dd>
            </div>
            <div>
              <dt>
                <i class="bi bi-hourglass-split me-1 text-muted" aria-hidden="true"></i>Hạn thanh
                toán
              </dt>
              <dd>{{ dinhDangLuc(don.han_thanh_toan) }}</dd>
            </div>
            <div>
              <dt>
                <i class="bi bi-lightning-charge me-1 text-muted" aria-hidden="true"></i>Kích hoạt
              </dt>
              <dd>{{ dinhDangLuc(don.kich_hoat_luc) }}</dd>
            </div>
            <div>
              <dt>
                <i class="bi bi-calendar-check me-1 text-muted" aria-hidden="true"></i>Hết hạn gói
              </dt>
              <dd>{{ dinhDangLuc(don.het_han_luc) }}</dd>
            </div>
          </dl>
        </section>

        <!-- Cột 2: Thanh toán & Thao tác -->
        <section class="m03-panel shadow-sm">
          <span class="m03-kicker mb-2 d-block">
            <i class="bi bi-cash-stack me-1" aria-hidden="true"></i>Tổng tiền thanh toán
          </span>
          <p class="m03-money">{{ dinhDangGia(don.gia) }}</p>

          <div
            v-if="don.trang_thai === 'DANG_SU_DUNG'"
            class="alert alert-success d-flex align-items-center gap-2 p-3 rounded-3 mb-3 small"
          >
            <i
              class="bi bi-check-circle-fill fs-5 text-success flex-shrink-0"
              aria-hidden="true"
            ></i>
            <span>Thanh toán đã được xác minh và gói đã kích hoạt.</span>
          </div>

          <div
            v-else-if="don.trang_thai === 'CAN_DOI_SOAT'"
            class="alert alert-warning d-flex align-items-center gap-2 p-3 rounded-3 mb-3 small"
          >
            <i
              class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0"
              aria-hidden="true"
            ></i>
            <span
              >Khoản thu đang cần đối soát. Gói chưa được kích hoạt; vui lòng liên hệ quản trị
              viên.</span
            >
          </div>

          <div
            v-else-if="conCho"
            class="alert alert-info d-flex align-items-center gap-2 p-3 rounded-3 mb-3 small"
          >
            <i class="bi bi-shield-lock-fill fs-5 text-info flex-shrink-0" aria-hidden="true"></i>
            <span>
              Giá và quyền lợi được giữ đến {{ dinhDangLuc(don.han_thanh_toan) }}. Thanh toán an
              toàn qua payOS.
            </span>
          </div>

          <div
            v-else
            class="alert alert-secondary d-flex align-items-center gap-2 p-3 rounded-3 mb-3 small"
          >
            <i class="bi bi-info-circle fs-5 text-muted flex-shrink-0" aria-hidden="true"></i>
            <span>
              Đơn không còn trong thời gian thanh toán. Nếu bạn đã chuyển tiền, hãy kiểm tra lại kết
              quả.
            </span>
          </div>

          <!-- Các nút thao tác thanh toán cho Khách hàng -->
          <div v-if="!laAdmin" class="m03-actions">
            <button
              v-if="conCho && !don.url_thanh_toan"
              class="btn btn-primary w-100 shadow-sm"
              :disabled="dangLuu"
              @click="thaoTac('link-thanh-toan')"
            >
              <span
                v-if="dangLuu"
                class="spinner-border spinner-border-sm me-1"
                role="status"
              ></span>
              <i v-else class="bi bi-credit-card-2-front-fill me-1" aria-hidden="true"></i>
              <span>{{ dangLuu ? 'Đang xử lý…' : 'Tạo liên kết thanh toán' }}</span>
            </button>

            <a
              v-if="conCho && linkPayosHopLe(don.url_thanh_toan)"
              :href="don.url_thanh_toan"
              class="btn btn-primary w-100 shadow-sm"
              target="_blank"
              rel="noopener noreferrer"
            >
              <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
              <span>Thanh toán qua payOS</span>
              <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i>
            </a>

            <button
              class="btn btn-outline-secondary w-100"
              :disabled="dangLuu"
              @click="thaoTac('dong-bo')"
            >
              <span
                v-if="dangLuu"
                class="spinner-border spinner-border-sm me-1"
                role="status"
              ></span>
              <i v-else class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>
              <span>{{ dangLuu ? 'Đang kiểm tra…' : 'Kiểm tra thanh toán' }}</span>
            </button>

            <RouterLink
              v-if="don.kich_hoat_luc"
              to="/khach-hang/goi-cua-toi"
              class="btn btn-outline-success w-100"
            >
              <i class="bi bi-award-fill me-1" aria-hidden="true"></i>
              <span>Gói của tôi</span>
            </RouterLink>
          </div>
        </section>
      </div>

      <!-- Phần khoản thu & Ghi kết quả đối soát / hoàn tiền (Admin) -->
      <section v-if="don.thanh_toan?.length" class="m03-panel shadow-sm">
        <div class="d-flex align-items-center gap-2 mb-3">
          <i class="bi bi-receipt text-emerald fs-5" aria-hidden="true"></i>
          <h2 class="h5 fw-bold mb-0">Khoản thu đã xác minh</h2>
        </div>

        <article
          v-for="t in don.thanh_toan"
          :key="t.id"
          class="m03-receipt p-3 rounded-3 bg-light border"
        >
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fs-5 fw-bold text-dark">{{ dinhDangGia(t.so_tien) }}</span>
            <span class="m03-state" :class="t.trang_thai">
              <span class="status-dot"></span>
              <span>{{ nhanTrangThai[t.trang_thai] }}</span>
            </span>
          </div>

          <p class="small text-muted mt-2 mb-1 font-monospace">
            Mã giao dịch: <strong>{{ t.ma_giao_dich }}</strong> · Thời gian:
            {{ dinhDangLuc(t.thanh_toan_luc) }}
          </p>

          <p v-if="t.ly_do_doi_soat" class="small text-warning mb-1">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>{{ t.ly_do_doi_soat }}
          </p>

          <p v-if="t.so_tien_hoan" class="small text-danger mb-1 fw-semibold">
            <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>
            Đã ghi nhận hoàn {{ dinhDangGia(t.so_tien_hoan) }} · {{ t.ma_hoan_tien }}
          </p>

          <div v-if="laAdmin && t.trang_thai === 'CAN_DOI_SOAT' && !doiSoat" class="mt-3">
            <button class="btn btn-outline-danger btn-sm" @click="moDoiSoat(t)">
              <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Ghi kết quả hoàn tiền
            </button>
          </div>

          <!-- Biểu mẫu đối soát hoàn tiền của Admin -->
          <form
            v-if="doiSoat?.id === t.id"
            class="mt-3 p-3 bg-white border rounded-3"
            @submit.prevent="luuDoiSoat"
          >
            <div class="alert alert-light border p-2 mb-3 small text-muted">
              <i class="bi bi-shield-exclamation text-amber me-1" aria-hidden="true"></i>
              Ghi nhận sau khi đã hoàn tiền thủ công. Thao tác này chỉ lưu kết quả.
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold small" for="tien-hoan">Số tiền đã hoàn (VND)</label>
              <input
                id="tien-hoan"
                v-model.number="doiSoat.so_tien_hoan"
                class="form-control"
                type="number"
                min="1"
                :max="t.so_tien"
                required
                :disabled="dangLuu"
              />
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold small" for="ma-hoan">Mã giao dịch hoàn tiền</label>
              <input
                id="ma-hoan"
                v-model="doiSoat.ma_hoan_tien"
                class="form-control"
                required
                maxlength="191"
                placeholder="Ví dụ: REF-10023498"
                :disabled="dangLuu"
              />
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold small" for="ly-do-hoan">Lý do / kết quả</label>
              <textarea
                id="ly-do-hoan"
                v-model="doiSoat.ly_do"
                class="form-control"
                required
                minlength="5"
                maxlength="1000"
                rows="3"
                placeholder="Ghi rõ lý do hoàn tiền và bằng chứng xác nhận chuyển khoản..."
                :disabled="dangLuu"
              ></textarea>
            </div>

            <p v-if="loiTruong" class="text-danger small mt-2" role="alert">
              <i class="bi bi-exclamation-circle-fill me-1" aria-hidden="true"></i>{{ loiTruong }}
            </p>

            <div class="m03-actions mt-3">
              <button class="btn btn-primary btn-sm" :disabled="dangLuu">
                <span
                  v-if="dangLuu"
                  class="spinner-border spinner-border-sm me-1"
                  role="status"
                ></span>
                <i v-else class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>
                <span>{{ dangLuu ? 'Đang lưu…' : 'Lưu kết quả' }}</span>
              </button>
              <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                :disabled="dangLuu"
                @click="doiSoat = null"
              >
                Đóng
              </button>
            </div>
          </form>
        </article>
      </section>
    </template>

    <!-- MÀN HÌNH DANH SÁCH ĐƠN HÀNG -->
    <section v-else-if="!$route.params.id && !dangTai" class="m03-panel shadow-sm">
      <!-- Bộ lọc trạng thái đơn -->
      <div class="row align-items-center mb-4 g-2">
        <div class="col-sm-auto">
          <label
            for="trang-thai-don"
            class="form-label fw-bold small mb-0 d-flex align-items-center gap-1"
          >
            <i class="bi bi-funnel-fill text-emerald" aria-hidden="true"></i>
            <span>Trạng thái đơn:</span>
          </label>
        </div>
        <div class="col-sm-4">
          <select
            id="trang-thai-don"
            v-model="boLoc"
            class="form-select form-select-sm"
            @change="doiTrang(1)"
          >
            <option value="">Tất cả</option>
            <option v-for="(nhan, ma) in trangThaiDon" :key="ma" :value="ma">{{ nhan }}</option>
          </select>
        </div>
      </div>

      <!-- Trạng thái danh sách rỗng -->
      <div v-if="!danhSach.length" class="m03-empty">
        <div class="empty-icon-ring mx-auto mb-3">
          <i class="bi bi-receipt fs-2 text-muted" aria-hidden="true"></i>
        </div>
        <h2 class="h5 fw-bold mt-2">Chưa có đơn phù hợp</h2>
        <p class="text-muted small">
          {{
            laAdmin
              ? 'Đơn mua gói của khách hàng sẽ xuất hiện tại đây.'
              : 'Chọn gói tập để bắt đầu hành trình của bạn.'
          }}
        </p>
      </div>

      <!-- Bảng danh sách đơn hàng -->
      <div v-else class="table-responsive">
        <table class="m03-table">
          <caption class="visually-hidden">
            Danh sách đơn mua gói
          </caption>
          <thead>
            <tr>
              <th scope="col">Gói tập</th>
              <th scope="col">Tổng tiền</th>
              <th scope="col">Trạng thái</th>
              <th scope="col">Ngày đặt</th>
              <th scope="col" class="text-end">Chi tiết</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in danhSach" :key="d.id">
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="package-icon-box">
                    <i class="bi bi-box-seam-fill text-emerald" aria-hidden="true"></i>
                  </div>
                  <div>
                    <strong class="text-dark d-block">{{ d.ten_goi }}</strong>
                    <div v-if="d.khach_hang" class="small text-muted font-monospace">
                      Khách: {{ d.khach_hang.ho_ten }}
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <span class="fw-bold text-dark">{{ dinhDangGia(d.gia) }}</span>
              </td>
              <td>
                <span class="m03-state" :class="d.trang_thai">
                  <span class="status-dot"></span>
                  <span>{{ nhanTrangThai[d.trang_thai] }}</span>
                </span>
              </td>
              <td>
                <span class="text-muted small font-monospace">{{ dinhDangLuc(d.created_at) }}</span>
              </td>
              <td class="text-end">
                <RouterLink
                  :to="`${goc}/${d.id}`"
                  class="btn btn-sm btn-outline-secondary"
                  :aria-label="`Xem đơn ${d.ma_don_payos}`"
                >
                  <i class="bi bi-eye me-1" aria-hidden="true"></i>Xem đơn
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Phân trang -->
      <div v-if="meta.last_page > 1" class="m03-pagination">
        <button
          class="btn btn-outline-secondary btn-sm"
          :disabled="meta.current_page <= 1"
          @click="doiTrang(meta.current_page - 1)"
        >
          <i class="bi bi-chevron-left me-1" aria-hidden="true"></i>Trang trước
        </button>
        <span class="small fw-semibold text-muted">
          {{ meta.current_page }} / {{ meta.last_page }}
        </span>
        <button
          class="btn btn-outline-secondary btn-sm"
          :disabled="meta.current_page >= meta.last_page"
          @click="doiTrang(meta.current_page + 1)"
        >
          Trang sau<i class="bi bi-chevron-right ms-1" aria-hidden="true"></i>
        </button>
      </div>
    </section>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../layouts/CaNhanLayout.vue'
import QuyenLoiGoiTap from '../../components/QuyenLoiGoiTap.vue'
import muaGoiService from '../../services/muaGoiService'
import { useXacThucStore } from '../../stores/xacThuc'
import { dinhDangGia } from '../../utils/goiTap'
import { nhanTrangThai, dinhDangLuc, conChoThanhToan, linkPayosHopLe } from '../../utils/donHang'
import { layLoiApi } from '../../utils/loiApi'
import '../../assets/muaGoi.css'

export default {
  name: 'DonHangPage',
  components: { CaNhanLayout, QuyenLoiGoiTap },
  data() {
    return {
      don: null,
      danhSach: [],
      meta: {},
      boLoc: '',
      dangTai: false,
      dangLuu: false,
      loi: '',
      loiTruong: '',
      thongBao: '',
      doiSoat: null,
      boHuy: null,
      lanTai: 0,
      daDong: false,
      dongHo: Date.now(),
      timer: null,
    }
  },
  computed: {
    nhanTrangThai() {
      return nhanTrangThai
    },
    xacThuc() {
      return useXacThucStore()
    },
    laAdmin() {
      return useXacThucStore().taiKhoan?.vai_tro === 'ADMIN'
    },
    goc() {
      return this.laAdmin ? '/admin/don-hang' : '/khach-hang/don-hang'
    },
    conCho() {
      return conChoThanhToan(this.don, this.dongHo)
    },
    trangThaiDon() {
      return Object.fromEntries(
        Object.entries(nhanTrangThai).filter(
          ([ma]) => !['DA_XAC_MINH', 'DA_HOAN_TIEN'].includes(ma),
        ),
      )
    },
  },
  watch: { '$route.fullPath': 'taiDuLieu', 'xacThuc.taiKhoan': 'taiDuLieu' },
  mounted() {
    this.taiDuLieu()
    this.timer = setInterval(() => {
      this.dongHo = Date.now()
    }, 1000)
  },
  beforeUnmount() {
    this.daDong = true
    this.lanTai++
    this.boHuy?.abort()
    clearInterval(this.timer)
  },
  methods: {
    dinhDangGia,
    dinhDangLuc,
    linkPayosHopLe,
    async taiDuLieu() {
      this.boHuy?.abort()
      this.boHuy = new AbortController()
      const lan = ++this.lanTai
      const signal = this.boHuy.signal
      this.don = null
      this.danhSach = []
      this.loi = ''
      this.doiSoat = null
      this.dangTai = true
      if (!this.xacThuc.taiKhoan) {
        this.dangTai = false
        return
      }
      try {
        const id = this.$route.params.id
        let r = id
          ? await muaGoiService.taiChiTiet(id, signal, this.laAdmin)
          : await muaGoiService.taiDonHang(
              { page: Number(this.$route.query.page) || 1, trang_thai: this.boLoc },
              signal,
              this.laAdmin,
            )
        if (this.daDong || lan !== this.lanTai) return
        if (id) this.don = r.data
        else {
          this.danhSach = r.data
          this.meta = r.meta
        }
      } catch (e) {
        if (!signal.aborted && !this.daDong && lan === this.lanTai) this.loi = layLoiApi(e).thongBao
      } finally {
        if (!this.daDong && lan === this.lanTai) this.dangTai = false
      }
    },
    doiTrang(page) {
      const query = page > 1 ? { page } : {}
      if (this.$route.query.page === query.page || (!this.$route.query.page && !query.page))
        this.taiDuLieu()
      else this.$router.push({ path: this.goc, query })
    },
    async thaoTac(hanhDong) {
      if (this.dangLuu || !this.don) return
      this.dangLuu = true
      this.loi = ''
      this.thongBao = ''
      const id = this.don.id
      const lan = this.lanTai
      try {
        const r = await muaGoiService.thaoTacDon(id, hanhDong)
        if (!this.daDong && lan === this.lanTai) {
          this.don = r.data
          this.thongBao =
            hanhDong === 'dong-bo'
              ? 'Đã kiểm tra kết quả thanh toán.'
              : 'Liên kết đã sẵn sàng. Bấm Thanh toán qua payOS để tiếp tục.'
        }
      } catch (e) {
        if (!this.daDong && lan === this.lanTai) this.loi = layLoiApi(e).thongBao
      } finally {
        if (!this.daDong) this.dangLuu = false
      }
    },
    moDoiSoat(t) {
      this.doiSoat = { id: t.id, so_tien_hoan: t.so_tien, ma_hoan_tien: '', ly_do: '' }
      this.loiTruong = ''
    },
    async luuDoiSoat() {
      if (this.dangLuu || !this.doiSoat) return
      this.dangLuu = true
      this.loiTruong = ''
      const lan = this.lanTai
      try {
        const { id, ...duLieu } = this.doiSoat
        await muaGoiService.doiSoat(id, duLieu)
        if (!this.daDong && lan === this.lanTai) {
          this.thongBao = 'Đã lưu kết quả hoàn tiền thủ công.'
          await this.taiDuLieu()
        }
      } catch (e) {
        if (!this.daDong && lan === this.lanTai) this.loiTruong = layLoiApi(e).thongBao
      } finally {
        if (!this.daDong) this.dangLuu = false
      }
    },
  },
}
</script>

<style scoped>
.text-emerald {
  color: var(--mau-chinh);
}

.package-icon-box {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: var(--mau-chinh-nhat);
  display: grid;
  place-items: center;
  font-size: 1rem;
  flex-shrink: 0;
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

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  display: inline-block;
  background-color: currentColor;
}
</style>
