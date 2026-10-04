<template>
  <CaNhanLayout>
    <!-- Tiêu đề trang phân công PT -->
    <div class="m03-heading">
      <div>
        <span class="m03-kicker">
          <i class="bi bi-person-badge-fill text-emerald me-1" aria-hidden="true"></i>
          Quản trị huấn luyện
        </span>
        <h1 class="h2 fw-bold mb-1">Phân công PT</h1>
        <p class="text-muted small mb-0">
          Khách có gói PT khả dụng hoặc đang có huấn luyện viên phụ trách.
        </p>
      </div>
      <button
        class="btn btn-outline-secondary btn-sm"
        :disabled="dangTai || dangLuu"
        @click="taiDuLieu"
      >
        <i
          class="bi bi-arrow-clockwise me-1"
          :class="{ 'spin-anim': dangTai }"
          aria-hidden="true"
        ></i>
        <span>Tải lại</span>
      </button>
    </div>

    <!-- Thông báo lỗi / cảnh báo -->
    <div
      v-if="loi"
      class="alert alert-warning d-flex align-items-center gap-2 p-3 mb-4 rounded-3"
      role="alert"
    >
      <i
        class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0"
        aria-hidden="true"
      ></i>
      <span class="small fw-semibold">{{ loi }}</span>
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
      <p class="small mb-0">Đang tải danh sách phân công…</p>
    </div>

    <!-- Khung chính 2 cột: Danh sách học viên + Form phân công -->
    <div v-else class="m03-grid">
      <!-- Cột 1: Danh sách học viên cần phân công / đổi PT -->
      <section class="m03-panel shadow-sm">
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
          <h2 class="h6 fw-bold mb-0 text-body d-flex align-items-center gap-2">
            <i class="bi bi-people-fill text-emerald" aria-hidden="true"></i>
            <span>Học viên có gói PT</span>
          </h2>
          <span class="badge bg-secondary-subtle text-body border small">
            {{ danhSach.length }} học viên
          </span>
        </div>

        <div v-if="!danhSach.length" class="m03-empty">
          <div class="empty-icon-ring mx-auto mb-3">
            <i class="bi bi-person-check fs-2 text-muted" aria-hidden="true"></i>
          </div>
          <h2 class="h5 fw-bold mt-2">Chưa có khách cần phân công</h2>
          <p class="text-muted small">
            Khách có gói PT khả dụng sẽ xuất hiện tại đây sau khi thanh toán được xác minh.
          </p>
        </div>

        <div v-if="danhSach.length" class="st-table-frame">
          <table class="st-data-table st-assignment-table">
            <thead>
              <tr>
                <th scope="col">Học viên</th>
                <th scope="col">PT phụ trách</th>
                <th scope="col">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="k in danhSach"
                :key="k.khach_hang_id"
                :class="{ 'is-selected': khachDaChon?.khach_hang_id === k.khach_hang_id }"
              >
                <td>
                  <div class="st-person-cell">
                    <span class="st-avatar" aria-hidden="true">{{
                      k.ho_ten?.charAt(0).toUpperCase() || 'H'
                    }}</span>
                    <div>
                      <strong>{{ k.ho_ten }}</strong
                      ><small>#{{ k.khach_hang_id }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <span
                    class="badge"
                    :class="
                      k.pt
                        ? 'bg-success-subtle text-success'
                        : 'bg-warning-subtle text-warning-emphasis'
                    "
                    >{{ k.pt?.ho_ten || 'Chưa phân công' }}</span
                  >
                </td>
                <td>
                  <button
                    class="btn btn-sm"
                    :class="k.pt ? 'btn-outline-secondary' : 'btn-primary'"
                    :disabled="dangLuu"
                    :aria-label="`${k.pt ? 'Đổi PT cho' : 'Phân công PT cho'} ${k.ho_ten}`"
                    @click="chonKhach(k)"
                  >
                    <i
                      :class="k.pt ? 'bi bi-arrow-repeat' : 'bi bi-person-plus'"
                      aria-hidden="true"
                    ></i>
                    {{ k.pt ? 'Đổi PT' : 'Phân công' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Phân trang -->
        <div v-if="meta.last_page > 1" class="m03-pagination">
          <button
            class="btn btn-outline-secondary btn-sm"
            :disabled="meta.current_page <= 1 || dangLuu"
            @click="doiTrang(meta.current_page - 1)"
          >
            <i class="bi bi-chevron-left me-1" aria-hidden="true"></i>Trang trước
          </button>
          <span class="small fw-semibold text-muted">
            {{ meta.current_page }} / {{ meta.last_page }}
          </span>
          <button
            class="btn btn-outline-secondary btn-sm"
            :disabled="meta.current_page >= meta.last_page || dangLuu"
            @click="doiTrang(meta.current_page + 1)"
          >
            Trang sau<i class="bi bi-chevron-right ms-1" aria-hidden="true"></i>
          </button>
        </div>
      </section>

      <!-- Cột 2: Form Phân công / Đổi PT -->
      <section class="m03-panel shadow-sm">
        <template v-if="khachDaChon">
          <div class="panel-heading mb-3 pb-2 border-bottom">
            <span class="m03-kicker mb-1 d-block">
              <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Thao tác điều phối
            </span>
            <h2 class="h5 fw-bold mb-0 text-body">
              {{ khachDaChon.pt ? 'Đổi PT phụ trách' : 'Phân công huấn luyện viên' }}
            </h2>
          </div>

          <!-- Thông tin học viên được chọn -->
          <div class="selected-summary-card p-3 rounded-3 bg-body-secondary border mb-3">
            <span class="small text-muted d-block mb-1">Học viên được chọn:</span>
            <div class="d-flex align-items-center justify-content-between">
              <strong class="fs-6 text-body">{{ khachDaChon.ho_ten }}</strong>
              <span v-if="khachDaChon.pt" class="badge bg-body text-muted border small">
                PT hiện tại: {{ khachDaChon.pt.ho_ten }}
              </span>
            </div>
          </div>

          <div v-if="khachDaChon.pt" class="alert alert-warning p-3 rounded-3 small mb-3">
            <div class="d-flex align-items-start gap-2">
              <i
                class="bi bi-exclamation-triangle-fill text-warning fs-5 flex-shrink-0"
                aria-hidden="true"
              ></i>
              <p class="mb-0 text-body">
                Đổi PT sẽ hủy lịch chưa bắt đầu và đề xuất chưa duyệt. Kế hoạch đã duyệt và lịch sử
                được giữ lại.
              </p>
            </div>
          </div>

          <form @submit.prevent="luuPhanCong">
            <div class="mb-3">
              <label for="pt-phu-trach" class="form-label fw-bold small">Huấn luyện viên</label>
              <select
                id="pt-phu-trach"
                v-model.number="duLieu.huan_luyen_vien_id"
                class="form-select"
                required
                :disabled="dangLuu"
              >
                <option value="">Chọn PT hoạt động</option>
                <option v-for="p in danhSachPT" :key="p.id" :value="p.id">
                  {{ p.ho_ten }}{{ p.chuyen_mon ? ` · ${p.chuyen_mon}` : '' }}
                </option>
              </select>
            </div>

            <button
              v-if="trangPT < tongTrangPT"
              type="button"
              class="btn btn-outline-secondary btn-sm mb-3 w-100"
              :disabled="dangTaiPT || dangLuu"
              @click="taiPT(trangPT + 1)"
            >
              <span
                v-if="dangTaiPT"
                class="spinner-border spinner-border-sm me-1"
                role="status"
              ></span>
              <i v-else class="bi bi-arrow-down-circle me-1" aria-hidden="true"></i>
              <span>{{ dangTaiPT ? 'Đang tải…' : 'Tải thêm PT' }}</span>
            </button>

            <div v-if="khachDaChon.pt" class="mb-3">
              <label for="ly-do-pt" class="form-label fw-bold small">Lý do đổi PT</label>
              <textarea
                id="ly-do-pt"
                v-model="duLieu.ly_do"
                class="form-control"
                maxlength="1000"
                rows="3"
                placeholder="Ghi rõ lý do điều chuyển huấn luyện viên cho học viên..."
                :required="Boolean(khachDaChon.pt)"
                :disabled="dangLuu"
              ></textarea>
            </div>

            <p v-if="loiForm" class="text-danger small mt-2" role="alert">
              <i class="bi bi-exclamation-circle-fill me-1" aria-hidden="true"></i>{{ loiForm }}
            </p>

            <div class="m03-actions mt-3">
              <button
                class="btn btn-primary shadow-sm"
                :disabled="dangLuu || !duLieu.huan_luyen_vien_id"
              >
                <span
                  v-if="dangLuu"
                  class="spinner-border spinner-border-sm me-1"
                  role="status"
                ></span>
                <i v-else class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>
                <span>{{ dangLuu ? 'Đang lưu…' : 'Xác nhận phân công' }}</span>
              </button>
              <button
                type="button"
                class="btn btn-outline-secondary"
                :disabled="dangLuu"
                @click="khachDaChon = null"
              >
                Đóng
              </button>
            </div>
          </form>
        </template>

        <!-- Trạng thái chưa chọn học viên -->
        <template v-else>
          <div class="text-center py-5">
            <div class="guide-icon-circle mx-auto mb-3">
              <i class="bi bi-person-check-fill fs-2 text-emerald" aria-hidden="true"></i>
            </div>
            <h2 class="h5 fw-bold mb-2">Một PT đồng hành</h2>
            <p class="text-muted small mb-0 px-3">
              Chọn khách hàng để phân công hoặc đổi huấn luyện viên phụ trách.
            </p>
          </div>
        </template>
      </section>
    </div>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../../layouts/CaNhanLayout.vue'
import muaGoiService from '../../../services/muaGoiService'
import { useXacThucStore } from '../../../stores/xacThuc'
import { layLoiApi } from '../../../utils/loiApi'
import '../../../assets/muaGoi.css'

export default {
  name: 'PhanCongPage',
  components: { CaNhanLayout },
  data() {
    return {
      danhSach: [],
      danhSachPT: [],
      meta: {},
      khachDaChon: null,
      duLieu: {},
      loi: '',
      loiForm: '',
      thongBao: '',
      dangTai: false,
      dangTaiPT: false,
      dangLuu: false,
      boHuy: null,
      lanTai: 0,
      daDong: false,
      trangPT: 0,
      tongTrangPT: 1,
    }
  },
  computed: {
    taiKhoan() {
      return useXacThucStore().taiKhoan
    },
  },
  watch: { '$route.query.page': 'taiDuLieu', taiKhoan: 'taiDuLieu' },
  mounted() {
    this.taiDuLieu()
  },
  beforeUnmount() {
    this.daDong = true
    this.lanTai++
    this.boHuy?.abort()
  },
  methods: {
    async taiDuLieu() {
      this.boHuy?.abort()
      this.boHuy = new AbortController()
      const signal = this.boHuy.signal
      const lan = ++this.lanTai
      this.danhSach = []
      this.danhSachPT = []
      this.khachDaChon = null
      this.loi = ''
      this.dangTai = false
      this.trangPT = 0
      if (!this.taiKhoan) return
      this.dangTai = true
      try {
        const r = await muaGoiService.taiPhanCong(Number(this.$route.query.page) || 1, signal)
        if (this.daDong || lan !== this.lanTai) return
        this.danhSach = r.data
        this.meta = r.meta
        await this.taiPT(1)
      } catch (e) {
        if (!signal.aborted && !this.daDong && lan === this.lanTai) this.loi = layLoiApi(e).thongBao
      } finally {
        if (!this.daDong && lan === this.lanTai) this.dangTai = false
      }
    },
    async taiPT(page) {
      if (this.dangTaiPT) return
      this.dangTaiPT = true
      const lan = this.lanTai
      const signal = this.boHuy.signal
      try {
        const r = await muaGoiService.taiPT(page, signal)
        if (!this.daDong && lan === this.lanTai) {
          this.danhSachPT.push(...r.data)
          this.trangPT = r.meta.current_page
          this.tongTrangPT = r.meta.last_page
        }
      } catch (e) {
        if (!signal.aborted && !this.daDong && lan === this.lanTai) this.loi = layLoiApi(e).thongBao
      } finally {
        if (!this.daDong) this.dangTaiPT = false
      }
    },
    doiTrang(page) {
      this.$router.push({ query: page > 1 ? { page } : {} })
    },
    chonKhach(k) {
      this.khachDaChon = { ...k }
      this.loiForm = ''
      this.duLieu = {
        khach_hang_id: k.khach_hang_id,
        phan_cong_hien_tai_id: k.phan_cong_hien_tai_id,
        huan_luyen_vien_id: '',
        ly_do: '',
        client_request_id: crypto.randomUUID(),
      }
    },
    async luuPhanCong() {
      if (this.dangLuu) return
      this.dangLuu = true
      this.loiForm = ''
      const lan = this.lanTai
      try {
        await muaGoiService.phanCong(this.duLieu)
        if (!this.daDong && lan === this.lanTai) {
          this.thongBao = 'Đã lưu phân công PT.'
          await this.taiDuLieu()
        }
      } catch (e) {
        if (!this.daDong && lan === this.lanTai) this.loiForm = layLoiApi(e).thongBao
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

.pc-khach {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  background: var(--mau-the, var(--mau-the));
  border: 1px solid var(--mau-vien, rgba(255, 255, 255, 0.08));
  transition: all 0.15s ease;
}

.pc-khach:hover {
  background: var(--mau-the-hover, var(--mau-the-hover));
  border-color: color-mix(in srgb, var(--mau-chinh) 30%, transparent);
}

.pc-khach.selected-customer {
  background: color-mix(in srgb, var(--mau-chinh) 15%, transparent);
  border-color: var(--mau-chinh);
  box-shadow: var(--bong-nhe);
}

.user-avatar-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--mau-the-sub);
  color: #ffffff;
  display: grid;
  place-items: center;
  font-weight: 700;
  font-size: 0.95rem;
  flex-shrink: 0;
}

.empty-icon-ring,
.guide-icon-circle {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: var(--mau-the-sub, var(--mau-the-hover));
  border: 1px solid var(--mau-vien);
  display: flex;
  align-items: center;
  justify-content: center;
}

.guide-icon-circle {
  background: var(--mau-chinh-nhat);
}

.spin-anim {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
