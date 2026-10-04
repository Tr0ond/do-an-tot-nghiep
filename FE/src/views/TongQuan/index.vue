<template>
  <CaNhanLayout>
    <section class="dashboard" :aria-busy="dangTai">
      <!-- Header Dashboard -->
      <header v-if="laAdmin" class="dashboard-heading">
        <div>
          <div class="eyebrow mb-1">
            <i
              :class="laAdmin ? 'bi bi-shield-lock-fill' : 'bi bi-lightning-charge-fill'"
              class="text-emerald me-1"
              aria-hidden="true"
            ></i>
            <span>{{ laAdmin ? 'TRUNG TÂM ĐIỀU HÀNH HỆ THỐNG' : 'KHÔNG GIAN CỦA BẠN' }}</span>
          </div>
          <h1 class="h2 fw-bold mb-1">
            {{ laAdmin ? 'Tổng quan hệ thống' : 'Chào ' + tenGoi + '!' }}
          </h1>
          <p class="text-muted small mb-0">
            Theo dõi doanh thu, lịch hẹn và hoạt động huấn luyện trong một nơi.
          </p>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button
            class="btn btn-outline-secondary btn-sm shadow-sm text-nowrap"
            :disabled="dangTai"
            title="Làm mới số liệu"
            @click="capNhatTongQuan"
          >
            <i
              class="bi bi-arrow-clockwise me-1"
              :class="{ 'spin-anim': dangTai }"
              aria-hidden="true"
            ></i>
            <span>{{ dangTai ? 'Đang tải…' : 'Cập nhật' }}</span>
          </button>
        </div>
      </header>

      <!-- Thông báo lỗi nếu có -->
      <div
        v-if="thongBao"
        class="alert alert-danger d-flex align-items-center gap-2 p-3 mb-4 rounded-3"
        role="alert"
      >
        <i
          class="bi bi-exclamation-triangle-fill fs-5 text-danger flex-shrink-0"
          aria-hidden="true"
        ></i>
        <div class="small fw-semibold flex-grow-1">{{ thongBao }}</div>
        <RouterLink v-if="hetPhien" to="/dang-nhap" class="btn btn-primary btn-sm">
          Đăng nhập lại
        </RouterLink>
        <button v-else class="btn btn-outline-danger btn-sm" @click="taiTongQuan">Thử lại</button>
      </div>

      <!-- Trạng thái đang tải toàn trang -->
      <div v-else-if="dangTai && !duLieu" class="dashboard-loading p-5 text-center" role="status">
        <span class="spinner-border text-success mb-3" aria-hidden="true"></span>
        <p class="text-muted mb-0 small">Đang tải thống kê của bạn…</p>
      </div>

      <!-- Nội dung Dashboard khi có dữ liệu -->
      <template v-if="duLieu && vaiTro">
        <BaoCaoAdmin v-if="laAdmin" ref="baoCao" :tong-quan="duLieu" />
        <HanhTrinhKhachHang
          v-if="laKh && duLieu.hanh_trinh"
          :hanh-trinh="duLieu.hanh_trinh"
          :ten-goi="tenGoi"
          :dang-tai="dangTai"
          @cap-nhat="taiTongQuan"
          @doi-khoang="doiKhoang"
        />
        <TongQuanPt
          v-if="laPt && duLieu.huan_luyen"
          :huan-luyen="duLieu.huan_luyen"
          :ten-goi="tenGoi"
          :dang-tai="dangTai"
          @cap-nhat="taiTongQuan"
        />

        <p class="updated-label">
          <i class="bi bi-clock-history me-1" aria-hidden="true"></i>
          Cập nhật lúc {{ thoiGianCapNhat }}
        </p>
      </template>
    </section>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../layouts/CaNhanLayout.vue'
import BaoCaoAdmin from './BaoCaoAdmin.vue'
import HanhTrinhKhachHang from './HanhTrinhKhachHang.vue'
import TongQuanPt from './TongQuanPt.vue'
import { useXacThucStore } from '../../stores/xacThuc'
import tongQuanService from '../../services/tongQuanService'
import { layLoiApi } from '../../utils/loiApi'

export default {
  name: 'TrangTongQuan',
  components: { CaNhanLayout, BaoCaoAdmin, HanhTrinhKhachHang, TongQuanPt },
  data() {
    return {
      duLieu: null,
      dangTai: false,
      thongBao: '',
      hetPhien: false,
      boHuy: null,
      lanTai: 0,
      soNgay: 30,
    }
  },
  computed: {
    xacThuc() {
      return useXacThucStore()
    },
    vaiTro() {
      return this.xacThuc.taiKhoan?.vai_tro
    },
    laAdmin() {
      return this.vaiTro === 'ADMIN'
    },
    laPt() {
      return this.vaiTro === 'HUAN_LUYEN_VIEN'
    },
    laKh() {
      return this.vaiTro === 'KHACH_HANG'
    },
    tenGoi() {
      return this.xacThuc.taiKhoan?.ho_ten?.trim().split(/\s+/).at(-1) || 'bạn'
    },
    thoiGianCapNhat() {
      return new Date(this.duLieu.cap_nhat_luc).toLocaleString('vi-VN', {
        timeZone: 'Asia/Ho_Chi_Minh',
        hour: '2-digit',
        minute: '2-digit',
        day: '2-digit',
        month: '2-digit',
      })
    },
  },
  watch: {
    '$route.path'() {
      this.duLieu = null
      this.taiTongQuan()
    },
  },
  mounted() {
    this.taiTongQuan()
  },
  beforeUnmount() {
    this.lanTai++
    this.boHuy?.abort()
  },
  methods: {
    capNhatTongQuan() {
      this.taiTongQuan()
      const baoCao = this.$refs?.baoCao
      if (baoCao) baoCao.taiBaoCao(baoCao.trangPt, Boolean(baoCao.boLocDaTai))
    },
    doiKhoang(soNgay) {
      if (![7, 30, 90].includes(soNgay)) return
      this.soNgay = soNgay
      this.taiTongQuan()
    },
    async taiTongQuan() {
      this.boHuy?.abort()
      const lanTai = ++this.lanTai
      this.boHuy = new AbortController()
      this.dangTai = true
      this.thongBao = ''
      this.hetPhien = false
      try {
        const ketQua = await tongQuanService.taiTongQuan(
          this.vaiTro,
          this.boHuy.signal,
          this.laKh ? { so_ngay: this.soNgay } : undefined,
        )
        if (lanTai !== this.lanTai) return
        this.duLieu = ketQua.data
      } catch (loi) {
        if (lanTai !== this.lanTai || loi.code === 'ERR_CANCELED') return
        const ketQua = layLoiApi(loi)
        this.thongBao = ketQua.thongBao
        this.hetPhien = ketQua.hetPhien
        this.duLieu = null
        if (this.hetPhien) this.xacThuc.taiKhoan = null
      } finally {
        if (lanTai === this.lanTai) this.dangTai = false
      }
    },
  },
}
</script>

<style scoped>
.text-emerald {
  color: var(--mau-chinh);
}
.dashboard-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 28px;
}
.dashboard-heading h1 {
  font-family: var(--font-chinh);
  font-size: 2rem;
  letter-spacing: 0;
  margin: 4px 0 6px;
  color: var(--mau-chu);
  overflow-wrap: anywhere;
}
.dashboard-heading p {
  margin: 0;
  color: var(--mau-phu);
}
.updated-label {
  margin: 24px 0 0;
  font-size: 0.78rem;
  color: var(--mau-phu);
  text-align: right;
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
@media (max-width: 700px) {
  .dashboard-heading {
    flex-wrap: wrap;
  }
}
@media (prefers-reduced-motion: reduce) {
  .spin-anim {
    animation: none;
  }
}
</style>
