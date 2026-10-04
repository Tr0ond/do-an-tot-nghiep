<template>
  <CaNhanLayout>
    <header class="st-page-heading">
      <div>
        <span class="st-kicker">DỊCH VỤ CỦA BẠN</span>
        <h1>Gói của tôi</h1>
        <p>Quyền lợi, thời hạn và huấn luyện viên đang phụ trách.</p>
      </div>
      <RouterLink to="/khach-hang/don-hang" class="btn btn-outline-secondary"
        ><i class="bi bi-receipt" aria-hidden="true"></i> Đơn hàng</RouterLink
      >
    </header>
    <p v-if="dangTai" role="status" class="st-empty">Đang tải gói của bạn…</p>
    <div v-else-if="loi" class="alert alert-warning" role="alert">
      {{ loi }} <button class="btn btn-outline-secondary" @click="taiDuLieu">Thử lại</button>
    </div>
    <template v-else-if="goi">
      <section class="st-package-band">
        <div>
          <span class="st-status"
            ><i class="bi bi-check-circle" aria-hidden="true"></i> Đang sử dụng</span
          >
          <h2>{{ goi.ten_goi }}</h2>
          <p>Mã đơn #{{ goi.ma_don_payos || goi.id }}</p>
        </div>
        <dl>
          <div>
            <dt>Kích hoạt</dt>
            <dd>{{ dinhDangLuc(goi.kich_hoat_luc) }}</dd>
          </div>
          <div>
            <dt>Hết hạn</dt>
            <dd>{{ dinhDangLuc(goi.het_han_luc) }}</dd>
          </div>
        </dl>
      </section>
      <span class="visually-hidden">{{ goi.so_buoi_con_lai }} / {{ goi.so_buoi_pt }} buổi</span>
      <div class="st-entitlements">
        <section class="st-section">
          <h2><i class="bi bi-person-arms-up" aria-hidden="true"></i> Buổi tập cùng PT</h2>
          <div class="st-quota">
            <strong>{{ goi.so_buoi_con_lai }}</strong
            ><span>/ {{ goi.so_buoi_pt }} buổi còn lại</span>
          </div>
          <progress
            :value="goi.so_buoi_con_lai"
            :max="Math.max(1, goi.so_buoi_pt)"
            aria-label="Buổi PT còn lại"
          ></progress>
          <p>Chỉ trừ buổi khi PT xác nhận hoàn thành.</p>
          <RouterLink
            v-if="pt && goi.so_buoi_con_lai > 0"
            to="/khach-hang/dat-lich"
            class="btn btn-primary"
            ><i class="bi bi-calendar-plus" aria-hidden="true"></i> Đặt lịch với PT</RouterLink
          ><RouterLink v-else to="/khach-hang/lich-hen" class="btn btn-outline-secondary"
            >Xem lịch hẹn</RouterLink
          >
        </section>
        <section class="st-section">
          <h2><i class="bi bi-robot" aria-hidden="true"></i> Quyền Tr0ond AI</h2>
          <div class="st-quota">
            <strong>{{ goi.so_luot_chatbot_moi_ngay }}</strong
            ><span>lượt tối đa / ngày</span>
          </div>
          <p>Hạn mức theo gói đã mua. Lượt còn lại hôm nay được kiểm tra tại Tr0ond AI.</p>
          <RouterLink to="/khach-hang/chatbot" class="btn btn-outline-secondary"
            ><i class="bi bi-robot" aria-hidden="true"></i> Mở Tr0ond AI</RouterLink
          >
        </section>
        <section class="st-section">
          <h2><i class="bi bi-person-badge" aria-hidden="true"></i> Huấn luyện viên phụ trách</h2>
          <div v-if="pt" class="st-person">
            <span class="st-avatar" aria-hidden="true">{{
              pt.ho_ten?.charAt(0).toUpperCase() || 'P'
            }}</span>
            <div>
              <strong>{{ pt.ho_ten }}</strong
              ><small>{{ pt.chuyen_mon || 'Huấn luyện viên cá nhân' }}</small>
            </div>
          </div>
          <p v-else>
            {{
              goi.so_buoi_pt > 0
                ? 'Đang chờ quản trị viên phân công PT đồng hành.'
                : 'Gói chatbot này không cần phân công PT.'
            }}
          </p>
          <RouterLink v-if="pt" to="/khach-hang/tin-nhan" class="btn btn-outline-secondary"
            ><i class="bi bi-chat-left-text" aria-hidden="true"></i> Nhắn tin</RouterLink
          >
        </section>
      </div>
      <section class="st-section">
        <h2><i class="bi bi-box-seam" aria-hidden="true"></i> Quyền lợi gói đã mua</h2>
        <QuyenLoiGoiTap :goi="goi" /><RouterLink
          :to="`/khach-hang/don-hang/${goi.id}`"
          class="st-text-link"
          >Xem đơn đã mua <i class="bi bi-arrow-up-right" aria-hidden="true"></i
        ></RouterLink>
      </section>
      <p class="st-rule-note">
        <i class="bi bi-info-circle" aria-hidden="true"></i> Khi dùng hết buổi PT, quyền chatbot vẫn
        giữ đến hết hạn gói. Mỗi khách hàng có một gói khả dụng.
      </p>
    </template>
    <section v-else class="st-empty">
      <i class="bi bi-box-seam" aria-hidden="true"></i>
      <h2>Bạn chưa có gói còn hiệu lực</h2>
      <p>Khám phá gói phù hợp hoặc kiểm tra đơn đang chờ thanh toán.</p>
      <div class="st-row-actions">
        <RouterLink to="/goi-tap" class="btn btn-primary">Khám phá gói tập</RouterLink
        ><RouterLink to="/khach-hang/don-hang" class="btn btn-outline-secondary"
          >Xem đơn hàng</RouterLink
        >
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
  color: var(--mau-thong-tin);
}

.text-amber {
  color: #d97706;
}

.fact-highlight-pt {
  background: var(--mau-chinh-nhat) !important;
  border-color: color-mix(in srgb, var(--mau-chinh) 25%, transparent) !important;
}

.pt-avatar-circle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--mau-the-sub);
  color: #ffffff;
  display: grid;
  place-items: center;
  font-weight: 700;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.surface-card {
  background: var(--mau-the-sub, rgba(255, 255, 255, 0.05));
  border: 1px solid var(--mau-vien);
}

.empty-icon-ring {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: var(--mau-the-sub, var(--mau-the-hover));
  border: 1px solid var(--mau-vien);
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
