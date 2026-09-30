<template>
  <main class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <p class="text-success fw-semibold mb-2">QUẢN LÝ HUẤN LUYỆN CÁ NHÂN</p>
        <h1 class="mb-3">Bắt đầu dự án</h1>
        <p class="text-secondary mb-4">
          Frontend đã sẵn sàng. Kiểm tra kết nối để xác nhận Backend cũng đang hoạt động.
        </p>
        <section class="card border-0 shadow-sm" aria-labelledby="tieu-de-ket-noi">
          <div class="card-body p-4">
            <h2 id="tieu-de-ket-noi" class="h5 mb-3">Kết nối Backend</h2>
            <div
              class="alert"
              :class="coKetNoi ? 'alert-success' : 'alert-secondary'"
              role="status"
              aria-live="polite"
            >
              {{ thongBao }}
            </div>
            <button class="btn btn-success" :disabled="dangTai" @click="kiemTraKetNoi">
              {{ dangTai ? 'Đang kiểm tra…' : 'Kiểm tra kết nối' }}
            </button>
          </div>
        </section>
      </div>
    </div>
  </main>
</template>

<script>
import heThongService from '../../services/heThongService'

export default {
  name: 'TrangChu',
  data() {
    return {
      dangTai: false,
      coKetNoi: false,
      thongBao: 'Chưa kiểm tra kết nối.',
    }
  },
  methods: {
    async kiemTraKetNoi() {
      if (this.dangTai) return

      this.dangTai = true
      this.coKetNoi = false

      try {
        const phanHoi = await heThongService.kiemTraKetNoi()
        this.coKetNoi = phanHoi.status === true
        this.thongBao = this.coKetNoi
          ? 'Kết nối thành công. Backend đang hoạt động.'
          : 'Backend chưa sẵn sàng. Vui lòng thử lại.'
      } catch {
        this.thongBao = 'Chưa kết nối được. Hãy khởi động Backend rồi kiểm tra lại.'
      } finally {
        this.dangTai = false
      }
    },
  },
}
</script>
