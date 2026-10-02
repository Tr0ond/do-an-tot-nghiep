<template>
  <CaNhanLayout>
    <!-- Tiêu đề trang -->
    <div class="m03-heading">
      <div>
        <span class="m03-kicker">
          <i class="bi bi-award-fill text-emerald me-1" aria-hidden="true"></i>
          Dịch vụ của bạn
        </span>
        <h1 class="h2 fw-bold mb-1">Gói của tôi</h1>
        <p class="text-muted small mb-0">
          Quyền lợi, thời hạn sử dụng và huấn luyện viên đồng hành cùng bạn.
        </p>
      </div>
      <RouterLink class="btn btn-outline-secondary" to="/khach-hang/don-hang">
        <i class="bi bi-receipt me-1" aria-hidden="true"></i>Đơn hàng
      </RouterLink>
    </div>

    <!-- Trạng thái đang tải -->
    <div v-if="dangTai" class="p-5 text-center text-muted" role="status">
      <div class="spinner-border text-success mb-3" role="status"></div>
      <p class="small mb-0">Đang tải gói của bạn…</p>
    </div>

    <!-- Báo lỗi -->
    <div
      v-else-if="loi"
      class="alert alert-warning d-flex align-items-center justify-content-between p-3 rounded-3"
      role="alert"
    >
      <span>{{ loi }}</span>
      <button class="btn btn-outline-secondary btn-sm" @click="taiDuLieu">
        <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Thử lại
      </button>
    </div>

    <!-- Hiển thị khi có gói đang hoạt động -->
    <div v-else-if="goi" class="m03-grid">
      <!-- Cột 1: Thông tin gói & Quyền lợi chi tiết -->
      <section class="m03-panel shadow-sm">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <span class="m03-state DANG_SU_DUNG">
            <span class="status-dot"></span>
            <span>Đang sử dụng</span>
          </span>
          <span class="badge bg-light text-muted border small font-monospace">
            Mã đơn #{{ goi.ma_don_payos || goi.id }}
          </span>
        </div>

        <h2 class="h3 fw-bold mb-2 text-dark">{{ goi.ten_goi }}</h2>

        <!-- Khung quyền lợi -->
        <div class="p-3 my-3 bg-light rounded-3 border">
          <QuyenLoiGoiTap :goi="goi" />
        </div>

        <!-- Hàng thẻ thông số gói -->
        <dl class="m03-facts">
          <div>
            <dt>
              <i class="bi bi-lightning-charge me-1 text-muted" aria-hidden="true"></i>Kích hoạt
            </dt>
            <dd>{{ dinhDangLuc(goi.kich_hoat_luc) }}</dd>
          </div>
          <div>
            <dt><i class="bi bi-calendar-check me-1 text-muted" aria-hidden="true"></i>Hết hạn</dt>
            <dd>{{ dinhDangLuc(goi.het_han_luc) }}</dd>
          </div>
          <div class="fact-highlight-pt">
            <dt>
              <i class="bi bi-person-arms-up me-1 text-emerald" aria-hidden="true"></i>Buổi PT còn
              lại
            </dt>
            <dd class="text-emerald">{{ goi.so_buoi_con_lai }} / {{ goi.so_buoi_pt }} buổi</dd>
          </div>
          <div>
            <dt><i class="bi bi-robot me-1 text-purple" aria-hidden="true"></i>Chatbot mỗi ngày</dt>
            <dd>{{ goi.so_luot_chatbot_moi_ngay }} lượt</dd>
          </div>
        </dl>

        <div
          class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2"
        >
          <RouterLink
            class="btn btn-outline-secondary btn-sm"
            :to="`/khach-hang/don-hang/${goi.id}`"
          >
            <i class="bi bi-file-text me-1" aria-hidden="true"></i>Xem đơn đã mua
          </RouterLink>
          <span class="small text-muted">
            <i class="bi bi-shield-check text-success me-1" aria-hidden="true"></i>Gói tập chính
            hãng được bảo hộ
          </span>
        </div>
      </section>

      <!-- Cột 2: Huấn luyện viên phụ trách -->
      <aside class="m03-panel shadow-sm">
        <div class="d-flex align-items-center gap-2 mb-3">
          <i class="bi bi-award-fill text-emerald fs-5" aria-hidden="true"></i>
          <h2 class="h5 fw-bold mb-0">Huấn luyện viên phụ trách</h2>
        </div>

        <!-- Nếu đã có PT -->
        <template v-if="pt">
          <div class="pt-profile-card p-3 rounded-3 bg-light border mb-3">
            <div class="d-flex align-items-center gap-3">
              <div class="pt-avatar-circle">
                {{ pt.ho_ten ? pt.ho_ten.charAt(0).toUpperCase() : 'P' }}
              </div>
              <div>
                <strong class="d-block text-dark fs-6">{{ pt.ho_ten }}</strong>
                <span class="small text-muted">{{
                  pt.chuyen_mon || 'Huấn luyện viên cá nhân'
                }}</span>
              </div>
            </div>
          </div>
          <div class="badge-assigned mb-3">
            <i class="bi bi-check-circle-fill text-emerald me-1" aria-hidden="true"></i>
            <span class="small fw-semibold text-dark">Đang phụ trách hướng dẫn bạn</span>
          </div>
        </template>

        <!-- Nếu chưa có PT -->
        <div v-else class="alert alert-light border p-3 rounded-3 mb-3">
          <div class="d-flex align-items-start gap-2">
            <i class="bi bi-hourglass-split text-amber fs-5 flex-shrink-0" aria-hidden="true"></i>
            <p class="text-muted small mb-0">
              {{
                goi.so_buoi_pt > 0
                  ? 'Đang chờ quản trị viên phân công PT đồng hành.'
                  : 'Gói chatbot này không cần phân công PT.'
              }}
            </p>
          </div>
        </div>

        <div class="p-3 bg-light-subtle rounded-3 border small text-muted">
          <i class="bi bi-info-circle me-1 text-emerald" aria-hidden="true"></i>
          Khi dùng hết buổi PT, quyền chatbot vẫn giữ đến hết hạn gói.
        </div>
      </aside>
    </div>

    <!-- Màn hình rỗng khi chưa có gói hiệu lực -->
    <section v-else class="m03-panel m03-empty shadow-sm">
      <div class="empty-icon-ring mx-auto mb-3">
        <i class="bi bi-bag-check fs-2 text-muted" aria-hidden="true"></i>
      </div>
      <h2 class="h4 fw-bold mt-2">Bạn chưa có gói còn hiệu lực</h2>
      <p class="text-muted small">Khám phá gói phù hợp hoặc kiểm tra đơn đang chờ thanh toán.</p>
      <div class="d-flex justify-content-center flex-wrap gap-2 mt-4">
        <RouterLink to="/goi-tap" class="btn btn-primary">
          <i class="bi bi-compass me-1" aria-hidden="true"></i>Khám phá gói tập
        </RouterLink>
        <RouterLink to="/khach-hang/don-hang" class="btn btn-outline-secondary">
          <i class="bi bi-receipt me-1" aria-hidden="true"></i>Xem đơn hàng
        </RouterLink>
      </div>
    </section>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../../layouts/CaNhanLayout.vue'
import QuyenLoiGoiTap from '../../../components/QuyenLoiGoiTap.vue'
import muaGoiService from '../../../services/muaGoiService'
import { useXacThucStore } from '../../../stores/xacThuc'
import { dinhDangLuc } from '../../../utils/donHang'
import { layLoiApi } from '../../../utils/loiApi'
import '../../../assets/muaGoi.css'

export default {
  name: 'GoiCuaToiPage',
  components: { CaNhanLayout, QuyenLoiGoiTap },
  data() {
    return {
      goi: null,
      pt: null,
      dangTai: false,
      loi: '',
      boHuy: null,
      daDong: false,
      lanTai: 0,
      timer: null,
    }
  },
  computed: {
    taiKhoan() {
      return useXacThucStore().taiKhoan
    },
  },
  watch: { taiKhoan: 'taiDuLieu' },
  mounted() {
    this.taiDuLieu()
  },
  beforeUnmount() {
    this.daDong = true
    this.lanTai++
    this.boHuy?.abort()
    clearTimeout(this.timer)
  },
  methods: {
    dinhDangLuc,
    async taiDuLieu() {
      this.boHuy?.abort()
      clearTimeout(this.timer)
      this.boHuy = new AbortController()
      const signal = this.boHuy.signal
      const lan = ++this.lanTai
      this.goi = null
      this.pt = null
      this.loi = ''
      this.dangTai = false
      if (!this.taiKhoan) return
      this.dangTai = true
      try {
        const r = await muaGoiService.taiGoiCuaToi(signal)
        if (!this.daDong && lan === this.lanTai) {
          this.goi = r.data.goi
          this.pt = r.data.pt
          if (this.goi) {
            const delai = new Date(this.goi.het_han_luc).getTime() - Date.now()
            this.timer = setTimeout(
              () => this.taiDuLieu(),
              Math.max(1000, Math.min(delai, 2147483647)),
            )
          }
        }
      } catch (e) {
        if (!signal.aborted && !this.daDong && lan === this.lanTai) this.loi = layLoiApi(e).thongBao
      } finally {
        if (!this.daDong && lan === this.lanTai) this.dangTai = false
      }
    },
  },
}
</script>

<style scoped>
.text-emerald {
  color: var(--mau-chinh);
}

.text-purple {
  color: #7c3aed;
}

.text-amber {
  color: #d97706;
}

.fact-highlight-pt {
  background: var(--mau-chinh-nhat) !important;
  border-color: rgba(5, 150, 105, 0.25) !important;
}

.pt-avatar-circle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, #059669, #10b981);
  color: #ffffff;
  display: grid;
  place-items: center;
  font-weight: 700;
  font-size: 1.1rem;
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
