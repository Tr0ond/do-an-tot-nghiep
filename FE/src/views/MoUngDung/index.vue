<template>
  <main class="mo-app container py-5">
    <section class="card p-4 mx-auto" style="max-width: 480px">
      <h1 class="h3">Quay lại FitForge</h1>
      <p>
        Ứng dụng sẽ kiểm tra trạng thái thanh toán từ hệ thống. Trang này không xác nhận giao dịch
        đã thành công.
      </p>
      <a v-if="lienKet" :href="lienKet" class="btn btn-primary">Mở FitForge</a>
      <p v-if="!lienKet" role="alert">Liên kết không hợp lệ. Hãy mở Đơn hàng trong app.</p>
      <p class="small mt-3 mb-0">
        Nếu chưa mở được, hãy mở bản cài FitForge trên điện thoại và xem Đơn hàng. Expo Go chưa hỗ
        trợ đường dẫn của bản cài riêng.
      </p>
      <router-link v-if="lienKet" :to="`/khach-hang/don-hang/${$route.params.id}`" class="mt-3"
        >Xem đơn trên website</router-link
      >
    </section>
  </main>
</template>

<script>
export default {
  name: 'MoUngDungThanhToan',
  computed: {
    lienKet() {
      const id = this.$route.params.id
      return /^[1-9][0-9]*$/.test(id) && Number.isSafeInteger(Number(id))
        ? `fitforge://don-hang/${id}`
        : null
    },
  },
}
</script>
