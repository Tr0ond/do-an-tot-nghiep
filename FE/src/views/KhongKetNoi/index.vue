<template>
  <div class="error-page-container">
    <div class="error-card-modern animate__animated animate__fadeInUp">
      <!-- Biểu tượng mất mạng với vòng sóng radar -->
      <div class="error-icon-halo error-halo-offline mx-auto">
        <i class="bi bi-wifi-off"></i>
      </div>

      <div class="eyebrow mx-auto mb-2 text-secondary bg-secondary-subtle border-secondary-subtle">
        <i class="bi bi-broadcast me-1"></i>
        <span>KẾT NỐI TẠM THỜI GIÁN ĐOẠN</span>
      </div>

      <h1 class="h3 fw-bold mb-2">Không thể kết nối máy chủ</h1>

      <p class="text-muted small mb-4 mx-auto" style="max-width: 480px">
        Hệ thống không nhận được phản hồi từ máy chủ hoặc đường truyền Internet của bạn đang gặp sự
        cố. Vui lòng kiểm tra lại thiết bị hoặc bấm nút thử lại bên dưới.
      </p>

      <!-- Trạng thái mạng trực tiếp của trình duyệt -->
      <div class="p-3 bg-light rounded-3 border mb-4 text-start">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="small text-muted">Trạng thái mạng thiết bị:</span>
          <span
            class="badge"
            :class="
              mangTrucTuyen ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'
            "
          >
            <i :class="mangTrucTuyen ? 'bi bi-wifi' : 'bi bi-wifi-off'" class="me-1"></i>
            {{ mangTrucTuyen ? 'Đã kết nối Internet' : 'Thiết bị đang Offline' }}
          </span>
        </div>

        <div class="d-flex align-items-center justify-content-between">
          <span class="small text-muted">Kết nối máy chủ Backend:</span>
          <span
            class="badge"
            :class="
              ketNoiMayChuOk ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'
            "
          >
            <i :class="ketNoiMayChuOk ? 'bi bi-server' : 'bi bi-arrow-repeat'" class="me-1"></i>
            {{ ketNoiMayChuOk ? 'Đã thông tuyến' : 'Chưa nhận được phản hồi' }}
          </span>
        </div>
      </div>

      <!-- Thông báo kết quả kiểm tra -->
      <div
        v-if="thongBaoKetNoi"
        class="alert d-flex align-items-center gap-2 p-3 mb-4 rounded-3 text-start small animate__animated animate__fadeIn"
        :class="
          ketNoiMayChuOk
            ? 'alert-success border-success-subtle'
            : 'alert-danger border-danger-subtle'
        "
        role="alert"
      >
        <i
          :class="
            ketNoiMayChuOk
              ? 'bi bi-check-circle-fill text-success'
              : 'bi bi-exclamation-circle-fill text-danger'
          "
          class="fs-5 flex-shrink-0"
        ></i>
        <div>{{ thongBaoKetNoi }}</div>
      </div>

      <!-- Nút hành động thử lại -->
      <div class="d-flex gap-2 justify-content-center flex-wrap mb-4">
        <button class="btn btn-primary" :disabled="dangKiemTra" @click="thuLaiKetNoi">
          <span
            v-if="dangKiemTra"
            class="spinner-border spinner-border-sm me-1"
            role="status"
          ></span>
          <i v-else class="bi bi-arrow-clockwise me-1"></i>
          <span>{{ dangKiemTra ? 'Đang kiểm tra kết nối…' : 'Kiểm tra & Thử lại' }}</span>
        </button>

        <RouterLink class="btn btn-outline-secondary" to="/dang-nhap">
          <i class="bi bi-box-arrow-in-right me-1"></i>
          <span>Về trang đăng nhập</span>
        </RouterLink>

        <RouterLink class="btn btn-outline-secondary" to="/">
          <i class="bi bi-house me-1"></i>
          <span>Về trang chủ</span>
        </RouterLink>
      </div>

      <!-- Hướng dẫn xử lý sự cố mạng -->
      <div class="text-start bg-body-tertiary p-3 rounded-3 border">
        <div class="small fw-bold text-dark mb-2">
          <i class="bi bi-tools text-primary me-1"></i>Các bước kiểm tra gợi ý:
        </div>
        <ul class="small text-muted mb-0 ps-3">
          <li>Kiểm tra đường truyền Wi-Fi, dây mạng LAN hoặc kết nối 4G/5G trên thiết bị.</li>
          <li>Kiểm tra xem máy chủ backend có đang hoạt động trên cổng được chỉ định không.</li>
          <li>Tắt các ứng dụng proxy hoặc VPN nếu đường truyền bị gián đoạn kéo dài.</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
import heThongService from '../../services/heThongService'
import { useXacThucStore } from '../../stores/xacThuc'

export default {
  name: 'TrangKhongKetNoi',
  data() {
    return {
      dangKiemTra: false,
      mangTrucTuyen: typeof navigator !== 'undefined' ? navigator.onLine : true,
      ketNoiMayChuOk: false,
      thongBaoKetNoi: '',
    }
  },
  mounted() {
    window.addEventListener('online', this.capNhatTrangThaiMang)
    window.addEventListener('offline', this.capNhatTrangThaiMang)
  },
  beforeUnmount() {
    window.removeEventListener('online', this.capNhatTrangThaiMang)
    window.removeEventListener('offline', this.capNhatTrangThaiMang)
  },
  methods: {
    capNhatTrangThaiMang() {
      this.mangTrucTuyen = navigator.onLine
    },
    async thuLaiKetNoi() {
      if (this.dangKiemTra) return
      this.dangKiemTra = true
      this.thongBaoKetNoi = ''
      try {
        await heThongService.kiemTraKetNoi()
        this.ketNoiMayChuOk = true
        this.thongBaoKetNoi = 'Kết nối máy chủ thành công! Hệ thống đang chuyển tiếp…'
        const xacThuc = useXacThucStore()
        setTimeout(() => {
          if (xacThuc.daDangNhap) {
            this.$router.replace(xacThuc.duongDanCaNhan)
          } else {
            this.$router.replace('/dang-nhap')
          }
        }, 1200)
      } catch {
        this.ketNoiMayChuOk = false
        this.thongBaoKetNoi =
          'Chưa thể kết nối tới máy chủ API. Vui lòng kiểm tra mạng hoặc thử lại sau ít phút.'
      } finally {
        this.dangKiemTra = false
      }
    },
  },
}
</script>
